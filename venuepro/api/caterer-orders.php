<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $uRole = requireRole(['caterer', 'admin']);
        $sql = "SELECT co.*, b.booking_code, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.package_cost, b.addons_cost, b.total_amount, b.caterer_status, v.name AS venue_name, v.district AS venue_district, cp.title AS package_title, cp.tier AS package_tier, u.name AS customer_name 
                FROM caterer_orders co 
                JOIN bookings b ON co.booking_id = b.id 
                JOIN venues v ON b.venue_id = v.id 
                LEFT JOIN catering_packages cp ON b.package_id = cp.id 
                JOIN users u ON b.customer_id = u.id 
                WHERE b.booking_status IN ('confirmed', 'completed')";
        $params = [];
        if ($uRole['role'] === 'caterer') {
            $sql .= " AND co.caterer_id = ?";
            $params[] = $uRole['id'];
        }
        $sql .= " ORDER BY b.event_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
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

        // Send notification to customer
        try {
            $stmtCust = $db->prepare("SELECT b.id, b.customer_id, b.booking_code FROM bookings b JOIN caterer_orders co ON b.id = co.booking_id WHERE co.id = ? LIMIT 1");
            $stmtCust->execute([$orderId]);
            $bkRow = $stmtCust->fetch();
            if ($bkRow) {
                $notifTitle = ($decision === 'Accepted') ? "Catering Confirmed ({$bkRow['booking_code']})" : "Catering Declined ({$bkRow['booking_code']})";
                $notifMsg = ($decision === 'Accepted') ? "The executive caterer has accepted your menu selection." : "The caterer was unable to accept this order: " . ($reason ?: 'Capacity reached');
                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, 'order', ?, 0)");
                $stmtNotif->execute([$bkRow['customer_id'], $notifTitle, $notifMsg, 'customer-live-progress.php?booking_id=' . $bkRow['id']]);
            }
        } catch(Exception $e) {}

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

        $stmtCheck = $db->prepare("SELECT item_status, booking_id FROM booking_singular_items WHERE id = ? LIMIT 1");
        $stmtCheck->execute([$itemId]);
        $itemRow = $stmtCheck->fetch();
        if (!$itemRow) {
            jsonResponse(false, 'Singular item not found', null, 404);
        }
        $currentStatus = $itemRow['item_status'];
        $targetBookingId = (int)$itemRow['booking_id'];

        // Stock check: only enforced when accepting
        if ($decision === 'Accepted') {
            // Get ordered quantity and singular_item_id from booking_singular_items
            $stmtQty = $db->prepare("SELECT quantity, singular_item_id FROM booking_singular_items WHERE id = ? LIMIT 1");
            $stmtQty->execute([$itemId]);
            $bsiRow = $stmtQty->fetch();
            $orderedQty = (int)($bsiRow['quantity'] ?? 0);
            $singularItemId = (int)($bsiRow['singular_item_id'] ?? 0);

            if ($singularItemId > 0 && $orderedQty > 0) {
                $stmtStock = $db->prepare("SELECT quantity_available, name FROM singular_menu_items WHERE id = ? LIMIT 1");
                $stmtStock->execute([$singularItemId]);
                $stockRow = $stmtStock->fetch();
                $stockAvailable = (int)($stockRow['quantity_available'] ?? 0);
                if ($orderedQty > $stockAvailable) {
                    http_response_code(422);
                    echo json_encode([
                        'success'          => false,
                        'stock_insufficient' => true,
                        'stock_available'  => $stockAvailable,
                        'message'          => "Cannot accept: customer ordered {$orderedQty} units of \"{$stockRow['name']}\" but only {$stockAvailable} are available in stock. Increase the stock in Singular Menu Items first."
                    ]);
                    exit;
                }
            }
        }

        $stmt = $db->prepare("UPDATE booking_singular_items SET item_status = ? WHERE id = ?");
        $stmt->execute([$decision, $itemId]);

        // Synchronize bookings.caterer_status
        if ($targetBookingId > 0) {
            $stmtPending = $db->prepare("SELECT COUNT(*) FROM booking_singular_items WHERE booking_id = ? AND item_status = 'Pending'");
            $stmtPending->execute([$targetBookingId]);
            if ((int)$stmtPending->fetchColumn() === 0) {
                $stmtAcc = $db->prepare("SELECT COUNT(*) FROM booking_singular_items WHERE booking_id = ? AND item_status = 'Accepted'");
                $stmtAcc->execute([$targetBookingId]);
                $accCount = (int)$stmtAcc->fetchColumn();
                $newCatererStatus = ($accCount > 0) ? 'accepted' : 'rejected';
                $db->prepare("UPDATE bookings SET caterer_status = ? WHERE id = ?")->execute([$newCatererStatus, $targetBookingId]);
            }
        }

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

        // Stock check: if accepting, verify all items have sufficient stock
        if ($decision === 'Accepted') {
            $stmtPendingItems = $db->prepare("
                SELECT bsi.id, bsi.item_name, bsi.quantity, smi.quantity_available
                FROM booking_singular_items bsi
                JOIN singular_menu_items smi ON bsi.singular_item_id = smi.id
                WHERE bsi.booking_id = ?
            ");
            $stmtPendingItems->execute([$bookingId]);
            $pendingWithStock = $stmtPendingItems->fetchAll();
            $insufficientList = [];
            foreach ($pendingWithStock as $pi) {
                if ((int)$pi['quantity'] > (int)$pi['quantity_available']) {
                    $insufficientList[] = "\"{$pi['item_name']}\" (ordered: {$pi['quantity']}, stock left: {$pi['quantity_available']})";
                }
            }
            if (!empty($insufficientList)) {
                http_response_code(422);
                echo json_encode([
                    'success'            => false,
                    'stock_insufficient' => true,
                    'message'            => "Cannot accept all: insufficient stock for " . implode(', ', $insufficientList) . ". Increase stock in Singular Menu Items first or decide items individually."
                ]);
                exit;
            }
        }

        // Update all items for this booking
        $stmt = $db->prepare("UPDATE booking_singular_items SET item_status = ? WHERE booking_id = ?");
        $stmt->execute([$decision, $bookingId]);
        $updatedCount = $stmt->rowCount();

        // Synchronize bookings.caterer_status
        $newCatererStatus = ($decision === 'Accepted') ? 'accepted' : 'rejected';
        $db->prepare("UPDATE bookings SET caterer_status = ? WHERE id = ?")->execute([$newCatererStatus, $bookingId]);

        jsonResponse(true, "All singular items updated to $decision");
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
