<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        requireRole(['caterer', 'admin']);
        $sql = "SELECT co.*, b.booking_code, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.package_cost, b.addons_cost, b.total_amount, b.caterer_status, v.name AS venue_name, v.district AS venue_district, cp.title AS package_title, cp.tier AS package_tier, u.name AS customer_name FROM caterer_orders co JOIN bookings b ON co.booking_id = b.id JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id JOIN users u ON b.customer_id = u.id ORDER BY b.event_date ASC";
        $stmt = $db->query($sql);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$ord) {
            // Singular items
            $stmtSi = $db->prepare("SELECT * FROM booking_singular_items WHERE booking_id = ?");
            $stmtSi->execute([$ord['booking_id']]);
            $ord['singular_items'] = $stmtSi->fetchAll();

            // Course items if package
            if ($ord['package_decision'] !== 'None') {
                $stmtCourse = $db->prepare("SELECT pmi.* FROM package_menu_items pmi JOIN bookings b ON pmi.package_id = b.package_id WHERE b.id = ?");
                $stmtCourse->execute([$ord['booking_id']]);
                $ord['course_items'] = $stmtCourse->fetchAll();
            }
        }

        jsonResponse(true, 'Caterer kitchen orders retrieved', ['orders' => $orders]);
        break;

    case 'decide_package':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $orderId = (int)($input['order_id'] ?? 0);
        $decision = sanitize($input['decision'] ?? 'Accepted'); // 'Accepted' | 'Rejected'
        $reason = sanitize($input['reason'] ?? '');

        if ($orderId <= 0) {
            jsonResponse(false, 'Invalid order ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE caterer_orders SET package_decision = ?, rejection_reason = ? WHERE id = ?");
        $stmt->execute([$decision, $reason, $orderId]);

        // Also update parent booking caterer_status
        $stmtBk = $db->prepare("UPDATE bookings b JOIN caterer_orders co ON b.id = co.booking_id SET b.caterer_status = ?, b.caterer_rejection_reason = ? WHERE co.id = ?");
        $stmtBk->execute([strtolower($decision), $reason, $orderId]);

        jsonResponse(true, "Catering package decision recorded: $decision");
        break;

    case 'decide_singular_item':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $itemId = (int)($input['item_id'] ?? 0);
        $decision = sanitize($input['decision'] ?? 'Accepted'); // 'Accepted' | 'Rejected'

        if ($itemId <= 0) {
            jsonResponse(false, 'Invalid singular item ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE booking_singular_items SET item_status = ? WHERE id = ?");
        $stmt->execute([$decision, $itemId]);

        jsonResponse(true, "Item decision recorded: $decision");
        break;

    case 'decide_all_singular_items':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $bookingId = (int)($input['booking_id'] ?? 0);
        $decision = sanitize($input['decision'] ?? 'Accepted');

        if ($bookingId <= 0) {
            jsonResponse(false, 'Invalid booking ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE booking_singular_items SET item_status = ? WHERE booking_id = ?");
        $stmt->execute([$decision, $bookingId]);

        jsonResponse(true, "All add-on items set to $decision");
        break;

    case 'update_prep_status':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $orderId = (int)($input['order_id'] ?? 0);
        $newStatus = sanitize($input['status'] ?? 'Preparing'); // 'Preparing' | 'Delivering' | 'Completed'

        if ($orderId <= 0) {
            jsonResponse(false, 'Invalid order ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE caterer_orders SET preparation_status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        jsonResponse(true, "Preparation status updated to $newStatus");
        break;

    default:
        jsonResponse(false, 'Invalid caterer order action', null, 400);
        break;
}
