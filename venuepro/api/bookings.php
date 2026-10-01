<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'my_bookings';
$db = getDBConnection();

switch ($action) {
    case 'check_availability':
        $venueId   = (int)($_GET['venue_id'] ?? 0);
        $date      = sanitize($_GET['date'] ?? '');
        $startTime = sanitize($_GET['start_time'] ?? '');
        $endTime   = sanitize($_GET['end_time'] ?? '');

        if ($venueId <= 0 || empty($date)) {
            jsonResponse(false, 'Missing venue ID or date', null, 400);
        }

        // If start/end times provided → check for time-slot overlap
        if (!empty($startTime) && !empty($endTime)) {
            // Overlap condition: existing booking's time range overlaps with requested range
            // (existing.start < new.end) AND (existing.end > new.start)
            $stmt = $db->prepare(
                "SELECT id, booking_code, event_name, start_time, end_time
                 FROM bookings
                 WHERE venue_id = ?
                   AND event_date = ?
                   AND booking_status IN ('confirmed','pending')
                   AND start_time < ?
                   AND end_time   > ?
                 LIMIT 1"
            );
            $stmt->execute([$venueId, $date, $endTime, $startTime]);
        } else {
            // No times given — fall back to full-day date check
            $stmt = $db->prepare(
                "SELECT id, booking_code, event_name, start_time, end_time
                 FROM bookings
                 WHERE venue_id = ?
                   AND event_date = ?
                   AND booking_status IN ('confirmed','pending')
                 LIMIT 1"
            );
            $stmt->execute([$venueId, $date]);
        }

        $conflict = $stmt->fetch();

        if ($conflict) {
            $conflictMsg = "Time-slot conflict detected! Venue is already booked on $date";
            if (!empty($conflict['start_time'])) {
                $conflictMsg .= " from " . substr($conflict['start_time'], 0, 5) . " to " . substr($conflict['end_time'], 0, 5);
            }
            $conflictMsg .= ". Please choose a different date or time.";
            jsonResponse(true, $conflictMsg, [
                'available' => false,
                'conflict'  => $conflict
            ]);
        } else {
            jsonResponse(true, 'Date and time slot is available for booking', ['available' => true]);
        }
        break;

    case 'create':
        $user = requireLogin();
        $input = !empty($_POST) ? $_POST : getJsonInput();

        $venueId = (int)($input['venue_id'] ?? 1);
        $packageId = !empty($input['package_id']) ? (int)$input['package_id'] : null;
        $eventName = sanitize($input['event_name'] ?? 'Corporate Event');
        $eventDate = sanitize($input['event_date'] ?? date('Y-m-d', strtotime('+14 days')));
        $startTime = sanitize($input['start_time'] ?? '18:00:00');
        $endTime = sanitize($input['end_time'] ?? '22:00:00');
        $guestCount = (int)($input['guest_count'] ?? 100);
        $durationHours = (int)($input['duration_hours'] ?? 4);
        $cardLast4 = sanitize($input['card_last4'] ?? '4242');
        $specialNotes = sanitize($input['special_notes'] ?? '');
        $singularItems = isset($input['singular_items']) && is_array($input['singular_items']) ? $input['singular_items'] : [];

        // Check venue existence
        $stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
        $stmtV->execute([$venueId]);
        $venue = $stmtV->fetch();
        if (!$venue) {
            jsonResponse(false, 'Invalid venue selected.', null, 400);
        }

        // Check for date + time-slot conflict (overlap logic)
        $stmtConf = $db->prepare(
            "SELECT id, booking_code, start_time, end_time FROM bookings
             WHERE venue_id = ?
               AND event_date = ?
               AND booking_status IN ('confirmed','pending')
               AND start_time < ?
               AND end_time   > ?
             LIMIT 1"
        );
        $stmtConf->execute([$venueId, $eventDate, $endTime, $startTime]);
        $existingBooking = $stmtConf->fetch();
        if ($existingBooking) {
            $existStart = substr($existingBooking['start_time'], 0, 5);
            $existEnd   = substr($existingBooking['end_time'], 0, 5);
            jsonResponse(false,
                "⚠️ Time-slot conflict: The venue '{$venue['name']}' is already reserved on $eventDate from $existStart to $existEnd. Please choose a different date or time.",
                ['conflict' => true, 'conflicting_booking' => $existingBooking['booking_code']],
                409
            );
        }

        // Calculate pricing
        $venueCost = (float)$venue['base_rate'];
        $packageCost = 0.00;
        if ($packageId) {
            $stmtP = $db->prepare("SELECT * FROM catering_packages WHERE id = ? LIMIT 1");
            $stmtP->execute([$packageId]);
            $package = $stmtP->fetch();
            if ($package) {
                $packageCost = (float)$package['price_per_event'];
            }
        }
        $staffingCost = 640.00; // Base professional staffing: 4 waitstaff + 2 bartenders + 1 coordinator

        // Calculate Add-on singular items
        $addonsCost = 0.00;
        $itemsToInsert = [];
        if (!empty($singularItems)) {
            $stmtSi = $db->prepare("SELECT * FROM singular_menu_items WHERE id = ? AND status = 'active' LIMIT 1");
            foreach ($singularItems as $si) {
                $itemId = (int)($si['id'] ?? 0);
                $qty = (int)($si['qty'] ?? 0);
                if ($itemId > 0 && $qty > 0) {
                    $stmtSi->execute([$itemId]);
                    $itemRow = $stmtSi->fetch();
                    if ($itemRow) {
                        $itemSub = $qty * (float)$itemRow['price'];
                        $addonsCost += $itemSub;
                        $itemsToInsert[] = [
                            'item_id'    => $itemRow['id'],
                            'name'       => $itemRow['name'],
                            'emoji'      => $itemRow['emoji'],
                            'unit_price' => (float)$itemRow['price'],
                            'qty'        => $qty,
                            'subtotal'   => $itemSub,
                            'notes'      => $si['notes'] ?? ''
                        ];
                    }
                }
            }
        }

        $subtotal = $venueCost + $packageCost + $staffingCost + $addonsCost;
        $serviceFee = $subtotal * 0.10; // 10%
        $taxVat = $subtotal * 0.08;     // 8% VAT
        $totalAmount = $subtotal + $serviceFee + $taxVat;
        $bookingCode = 'BK-' . rand(8000, 9999);

        $db->beginTransaction();
        try {
            // 1. Insert Booking
            $stmtB = $db->prepare("INSERT INTO bookings (booking_code, customer_id, venue_id, package_id, event_name, event_date, start_time, end_time, guest_count, duration_hours, venue_cost, package_cost, staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount, payment_status, payment_card_last4, booking_status, caterer_status, special_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, 'confirmed', 'pending', ?)");
            $stmtB->execute([
                $bookingCode, $user['id'], $venueId, $packageId, $eventName, $eventDate,
                $startTime, $endTime, $guestCount, $durationHours, $venueCost, $packageCost,
                $staffingCost, $addonsCost, $subtotal, $serviceFee, $taxVat, $totalAmount,
                $cardLast4, $specialNotes
            ]);
            $bookingId = (int)$db->lastInsertId();

            // 2. Insert Singular Add-On Items
            $stmtItemIns = $db->prepare("INSERT INTO booking_singular_items (booking_id, singular_item_id, item_name, emoji, unit_price, quantity, subtotal, item_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
            foreach ($itemsToInsert as $ins) {
                $stmtItemIns->execute([$bookingId, $ins['item_id'], $ins['name'], $ins['emoji'], $ins['unit_price'], $ins['qty'], $ins['subtotal'], $ins['notes']]);
            }

            // 3. Create Caterer Order if catering requested
            if ($packageId || !empty($itemsToInsert)) {
                $catererId = 2; // Default master caterer Alex Rivera
                $orderCode = 'ORD-' . rand(2000, 3999);
                $stmtOrd = $db->prepare("INSERT INTO caterer_orders (order_code, booking_id, caterer_id, covers_count, preparation_status, package_decision) VALUES (?, ?, ?, ?, 'Preparing', 'Pending')");
                $stmtOrd->execute([$orderCode, $bookingId, $catererId, $guestCount]);
            }

            // 4. Assign default lead coordinator (Sarah Jenkins)
            $defaultChecklist = json_encode([
                "Banquet Tables & High-Tops Arranged ✓",
                "Stage & Audio-Visual Acoustics Tested ✓",
                "Catering Service Kitchen Station Briefed ○",
                "Emergency Exits & Security Briefed ○"
            ]);
            $stmtStaff = $db->prepare("INSERT INTO staff_assignments (booking_id, staff_id, role_title, shift_time, setup_status, checklist_json) VALUES (?, 3, 'Lead Event Coordinator', '15:00 - 23:00', 'in_progress', ?)");
            $stmtStaff->execute([$bookingId, $defaultChecklist]);

            // 5. Create Invoice
            $invCode = 'INV-' . date('Y') . '-' . rand(1000, 9999);
            $stmtInv = $db->prepare("INSERT INTO invoices (invoice_code, booking_id, customer_id, issue_date, due_date, subtotal, service_fee, vat_tax, total_amount, payment_status) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 'paid')");
            $stmtInv->execute([$invCode, $bookingId, $user['id'], $eventDate, $subtotal, $serviceFee, $taxVat, $totalAmount]);

            // 6. Notify Customer & Caterer
            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url) VALUES (?, ?, ?, ?, ?)");
            $stmtNotif->execute([$user['id'], "Booking Confirmed ($bookingCode)", "Your reservation for {$venue['name']} on $eventDate has been confirmed.", 'booking', 'booking-status-timeline.php']);
            $stmtNotif->execute([2, "New Catering Order ($bookingCode)", "Customer {$user['name']} placed order for {$venue['name']}.", 'order', 'caterer-order-details.php']);

            $db->commit();

            jsonResponse(true, 'Booking and payment completed successfully!', [
                'booking_id'   => $bookingId,
                'booking_code' => $bookingCode,
                'invoice_code' => $invCode,
                'total_amount' => $totalAmount,
                'redirect'     => 'booking-success.php?booking_id=' . $bookingId
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Booking error: " . $e->getMessage());
            jsonResponse(false, 'Transaction failed due to a server error. Please try again.', null, 500);
        }
        break;

    case 'my_bookings':
        $user = requireLogin();
        $stmt = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, v.district AS venue_district, cp.title AS package_title FROM bookings b JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id WHERE b.customer_id = ? ORDER BY b.event_date DESC");
        $stmt->execute([$user['id']]);
        $bookings = $stmt->fetchAll();

        jsonResponse(true, 'Customer bookings retrieved', ['bookings' => $bookings]);
        break;

    case 'all_bookings':
        requireRole(['admin', 'staff']);
        $status = sanitize($_GET['status'] ?? '');
        $sql = "SELECT b.*, u.name AS customer_name, u.email AS customer_email, v.name AS venue_name, cp.title AS package_title FROM bookings b JOIN users u ON b.customer_id = u.id JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id";
        $params = [];

        if (!empty($status) && $status !== 'All') {
            $sql .= " WHERE b.booking_status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY b.event_date DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        jsonResponse(true, 'All bookings retrieved', ['bookings' => $bookings]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $code = sanitize($_GET['code'] ?? '');

        if ($id > 0) {
            $stmt = $db->prepare("SELECT b.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, v.name AS venue_name, v.address AS venue_address, v.district AS venue_district, cp.title AS package_title, cp.tier AS package_tier FROM bookings b JOIN users u ON b.customer_id = u.id JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id WHERE b.id = ? LIMIT 1");
            $stmt->execute([$id]);
        } else if (!empty($code)) {
            $stmt = $db->prepare("SELECT b.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, v.name AS venue_name, v.address AS venue_address, v.district AS venue_district, cp.title AS package_title, cp.tier AS package_tier FROM bookings b JOIN users u ON b.customer_id = u.id JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id WHERE b.booking_code = ? LIMIT 1");
            $stmt->execute([$code]);
        } else {
            jsonResponse(false, 'Missing booking ID or code', null, 400);
        }

        $booking = $stmt->fetch();
        if (!$booking) {
            jsonResponse(false, 'Booking record not found', null, 404);
        }

        // Get singular add-on items
        $stmtSi = $db->prepare("SELECT * FROM booking_singular_items WHERE booking_id = ?");
        $stmtSi->execute([$booking['id']]);
        $booking['singular_items'] = $stmtSi->fetchAll();

        // Get coordinator
        $stmtCoord = $db->prepare("SELECT sa.*, u.name AS staff_name, u.phone AS staff_phone FROM staff_assignments sa JOIN users u ON sa.staff_id = u.id WHERE sa.booking_id = ? LIMIT 1");
        $stmtCoord->execute([$booking['id']]);
        $booking['coordinator'] = $stmtCoord->fetch();

        jsonResponse(true, 'Booking retrieved', ['booking' => $booking]);
        break;

    case 'update_status':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $bookingId = (int)($input['booking_id'] ?? 0);
        $newStatus = sanitize($input['status'] ?? 'confirmed');
        $notes = sanitize($input['notes'] ?? '');

        if ($bookingId <= 0) {
            jsonResponse(false, 'Invalid booking ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE bookings SET booking_status = ?, special_notes = CONCAT(COALESCE(special_notes,''), '
[Admin Update: ', ?, ']') WHERE id = ?");
        $stmt->execute([$newStatus, $notes, $bookingId]);

        jsonResponse(true, "Booking status updated to " . ucfirst($newStatus));
        break;

    case 'cancel':
        $user  = requireLogin();
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $bookingId = (int)($input['booking_id'] ?? 0);
        $reason    = sanitize($input['reason'] ?? 'Cancelled by customer');

        if ($bookingId <= 0) {
            jsonResponse(false, 'Invalid booking ID', null, 400);
        }

        // Fetch booking to check ownership / status
        $stmtFetch = $db->prepare("SELECT id, customer_id, booking_status, booking_code, event_name FROM bookings WHERE id = ? LIMIT 1");
        $stmtFetch->execute([$bookingId]);
        $bk = $stmtFetch->fetch();

        if (!$bk) {
            jsonResponse(false, 'Booking not found', null, 404);
        }

        // Only the owning customer or admin can cancel
        $isAdmin    = ($_SESSION['user_role'] ?? '') === 'admin';
        $isOwner    = (int)$bk['customer_id'] === (int)$user['id'];
        if (!$isOwner && !$isAdmin) {
            jsonResponse(false, 'Unauthorized: You may not cancel this booking', null, 403);
        }

        // Cannot cancel already-completed or already-cancelled bookings
        if (in_array($bk['booking_status'], ['completed', 'cancelled'])) {
            jsonResponse(false, "Booking is already {$bk['booking_status']} and cannot be cancelled", null, 409);
        }

        $stmtCancel = $db->prepare(
            "UPDATE bookings SET booking_status = 'cancelled',
             special_notes = CONCAT(COALESCE(special_notes,''), '\n[Cancelled: ', ?, ']')
             WHERE id = ?"
        );
        $stmtCancel->execute([$reason, $bookingId]);

        // Notify customer
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url) VALUES (?, ?, ?, 'booking', 'customer-live-progress.php')");
        $notifStmt->execute([
            $bk['customer_id'],
            "Booking Cancelled ({$bk['booking_code']})",
            "Your booking for {$bk['event_name']} has been cancelled. Reason: $reason"
        ]);

        jsonResponse(true, 'Booking cancelled successfully', ['booking_code' => $bk['booking_code']]);
        break;

    default:
        jsonResponse(false, 'Invalid bookings action', null, 400);
        break;
}
