<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $status = sanitize($_GET['status'] ?? '');
        $sql = "SELECT u.id, u.name, u.email, u.phone, u.avatar_text,
                       cp.business_name, cp.owner_name, cp.kitchen_address, cp.specialization,
                       cp.rating, cp.review_count, cp.approval_status
                FROM users u
                JOIN caterer_profiles cp ON u.id = cp.user_id
                WHERE u.role = 'caterer'";
        $params = [];
        if (!empty($status)) {
            $sql .= " AND cp.approval_status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY cp.business_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $caterers = $stmt->fetchAll();
        jsonResponse(true, 'Caterers retrieved', ['caterers' => $caterers]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, 'Missing caterer ID', null, 400);
        }
        $stmt = $db->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.avatar_text,
                    cp.business_name, cp.owner_name, cp.kitchen_address, cp.specialization,
                    cp.rating, cp.review_count, cp.approval_status
             FROM users u
             JOIN caterer_profiles cp ON u.id = cp.user_id
             WHERE u.id = ? AND u.role = 'caterer' LIMIT 1"
        );
        $stmt->execute([$id]);
        $caterer = $stmt->fetch();
        if (!$caterer) {
            jsonResponse(false, 'Caterer not found', null, 404);
        }
        jsonResponse(true, 'Caterer details', ['caterer' => $caterer]);
        break;

    case 'update_status':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $catererId = (int)($input['caterer_id'] ?? ($_GET['caterer_id'] ?? 0));
        $status = sanitize($input['status'] ?? ($_GET['status'] ?? ''));

        if ($catererId <= 0) {
            jsonResponse(false, 'Valid caterer ID is required', null, 400);
        }

        $validStatuses = ['approved', 'under_review', 'rejected'];
        if (!in_array($status, $validStatuses, true)) {
            jsonResponse(false, 'Invalid approval status', null, 400);
        }

        $stmt = $db->prepare("UPDATE caterer_profiles SET approval_status = ? WHERE user_id = ?");
        $stmt->execute([$status, $catererId]);

        jsonResponse(true, 'Caterer approval status updated to ' . $status, ['approval_status' => $status]);
        break;

    case 'create':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $businessName = sanitize($input['business_name'] ?? '');
        $ownerName = sanitize($input['owner_name'] ?? ($input['name'] ?? ''));
        $email = sanitize($input['email'] ?? '');
        $phone = sanitize($input['phone'] ?? '');
        $kitchenAddress = sanitize($input['kitchen_address'] ?? ($input['address'] ?? ''));
        $specialization = sanitize($input['specialization'] ?? 'Fine Dining');
        $password = $input['password'] ?? 'password123';

        if (empty($businessName) || empty($email)) {
            jsonResponse(false, 'Business name and email are required', null, 400);
        }

        // Check if user already exists
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            jsonResponse(false, 'A user with this email address already exists', null, 409);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $avatarText = strtoupper(substr($ownerName ?: $businessName, 0, 2));

        $stmtUser = $db->prepare("INSERT INTO users (name, email, password, phone, role, avatar_text, status) VALUES (?, ?, ?, ?, 'caterer', ?, 'active')");
        $stmtUser->execute([$ownerName ?: $businessName, $email, $hashed, $phone, $avatarText]);
        $newUserId = (int)$db->lastInsertId();

        $stmtProfile = $db->prepare("INSERT INTO caterer_profiles (user_id, business_name, owner_name, kitchen_address, specialization, rating, review_count, approval_status) VALUES (?, ?, ?, ?, ?, 5.0, 1, 'approved')");
        $stmtProfile->execute([$newUserId, $businessName, $ownerName, $kitchenAddress, $specialization]);

        jsonResponse(true, 'Caterer registered and approved successfully', ['caterer_id' => $newUserId], 201);
        break;

    default:
        jsonResponse(false, 'Invalid caterer action', null, 400);
        break;
}
