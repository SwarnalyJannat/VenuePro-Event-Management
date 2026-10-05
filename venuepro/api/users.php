<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Administrative user management endpoint — restricted to administrators
$currentAdmin = requireRole('admin');
$currentAdminId = (int)$currentAdmin['id'];
$db = getDBConnection();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'list':
        $roleFilter = sanitize($_GET['role'] ?? '');
        $search = trim(sanitize($_GET['search'] ?? ''));

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.role, u.avatar_text, u.avatar_bg, u.status, u.created_at,
                       COUNT(b.id) AS total_bookings,
                       COALESCE(SUM(b.booking_status IN ('confirmed','pending')), 0) AS active_bookings
                FROM users u
                LEFT JOIN bookings b ON u.id = b.customer_id
                WHERE 1=1";
        $params = [];

        if (!empty($roleFilter) && in_array($roleFilter, ['customer', 'admin', 'staff', 'caterer'])) {
            $sql .= " AND u.role = ?";
            $params[] = $roleFilter;
        }

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " GROUP BY u.id ORDER BY (u.id = $currentAdminId) DESC, u.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        jsonResponse(true, 'Users retrieved', ['users' => $users]);
        break;

    case 'toggle_status':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $targetId = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        $newStatus = sanitize($input['status'] ?? '');

        if ($targetId <= 0) {
            jsonResponse(false, 'Valid user ID is required.', null, 400);
        }

        if (!in_array($newStatus, ['active', 'suspended'])) {
            jsonResponse(false, 'Status must be active or suspended.', null, 400);
        }

        // Cannot suspend self
        if ($targetId === $currentAdminId) {
            jsonResponse(false, 'Security constraint: You cannot suspend your own administrative session.', null, 403);
        }

        // Fetch target user info
        $stmtTarget = $db->prepare("SELECT id, role, status FROM users WHERE id = ? LIMIT 1");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        if (!$targetUser) {
            jsonResponse(false, 'User not found.', null, 404);
        }

        // Cannot suspend the only remaining active admin
        if ($targetUser['role'] === 'admin' && $newStatus === 'suspended') {
            $stmtCountAdmins = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND id != ?");
            $stmtCountAdmins->execute([$targetId]);
            if ((int)$stmtCountAdmins->fetchColumn() === 0) {
                jsonResponse(false, 'Governance constraint: Cannot suspend the only remaining active administrator in the system.', null, 403);
            }
        }

        $stmtUp = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmtUp->execute([$newStatus, $targetId]);

        jsonResponse(true, 'User status successfully updated to ' . strtoupper($newStatus) . '.');
        break;

    case 'delete':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $targetId = (int)($input['id'] ?? ($_GET['id'] ?? 0));

        if ($targetId <= 0) {
            jsonResponse(false, 'Valid user ID is required.', null, 400);
        }

        // Rule 1: Cannot delete self
        if ($targetId === $currentAdminId) {
            jsonResponse(false, 'Security constraint: You cannot delete your own administrative account while logged in.', null, 403);
        }

        // Fetch target user info
        $stmtTarget = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1");
        $stmtTarget->execute([$targetId]);
        $targetUser = $stmtTarget->fetch();
        if (!$targetUser) {
            jsonResponse(false, 'User record does not exist or has already been removed.', null, 404);
        }

        // Rule 2: Cannot delete the last active administrator
        if ($targetUser['role'] === 'admin') {
            $stmtCountAdmins = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND id != ?");
            $stmtCountAdmins->execute([$targetId]);
            if ((int)$stmtCountAdmins->fetchColumn() === 0) {
                jsonResponse(false, 'Governance constraint: Cannot delete the only remaining active administrator in the system.', null, 403);
            }
        }

        // Rule 3: Check for active/pending/confirmed reservations if customer
        if ($targetUser['role'] === 'customer') {
            $stmtActiveBk = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND booking_status IN ('confirmed','pending')");
            $stmtActiveBk->execute([$targetId]);
            $activeBkCount = (int)$stmtActiveBk->fetchColumn();

            if ($activeBkCount > 0) {
                jsonResponse(false, "Cannot permanently delete customer '{$targetUser['name']}': this customer has {$activeBkCount} active/confirmed event reservation(s). Please cancel or settle the reservations first, or suspend the account instead.", null, 409);
            }
        }

        // Rule 4: Clean removal with foreign key integrity
        $db->beginTransaction();
        try {
            // If customer has completed/cancelled bookings, clean up attached invoices and singular items
            $stmtCustomerBookings = $db->prepare("SELECT id FROM bookings WHERE customer_id = ?");
            $stmtCustomerBookings->execute([$targetId]);
            $bkIds = $stmtCustomerBookings->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($bkIds)) {
                $inClause = implode(',', array_map('intval', $bkIds));
                $db->query("DELETE FROM invoices WHERE booking_id IN ($inClause)");
                $db->query("DELETE FROM booking_singular_items WHERE booking_id IN ($inClause)");
                $db->query("DELETE FROM staff_assignments WHERE booking_id IN ($inClause)");
                $db->query("DELETE FROM bookings WHERE customer_id = $targetId");
            }

            // Remove profile records if any
            $db->prepare("DELETE FROM staff_profiles WHERE user_id = ?")->execute([$targetId]);
            $db->prepare("DELETE FROM caterer_profiles WHERE user_id = ?")->execute([$targetId]);

            // Finally remove user
            $stmtDelUser = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmtDelUser->execute([$targetId]);

            $db->commit();
            jsonResponse(true, "User '{$targetUser['name']}' ({$targetUser['role']}) has been permanently and safely removed from the system.");
        } catch (Exception $e) {
            $db->rollBack();
            error_log("User deletion error: " . $e->getMessage());
            jsonResponse(false, 'Failed to remove user: ' . $e->getMessage(), null, 500);
        }
        break;

    default:
        jsonResponse(false, 'Unknown user management action.', null, 400);
        break;
}
