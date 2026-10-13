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
        $guestCount = max(1, (int)($input['guest_count'] ?? ($input['guests'] ?? 100)));
        $durationHours = (int)($input['duration_hours'] ?? 4);
        $cardLast4 = sanitize($input['card_last4'] ?? '4242');
        $specialNotes = sanitize($input['special_notes'] ?? '');
        $singularItems = isset($input['singular_items']) && is_array($input['singular_items']) ? $input['singular_items'] : [];
        $isCustomPackage = !empty($input['is_custom_package']);
        $customDishes = isset($input['custom_dishes']) && is_array($input['custom_dishes']) ? $input['custom_dishes'] : [];
        $customPackageCost = isset($input['custom_package_cost']) ? (float)$input['custom_package_cost'] : 0.00;

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
        if ($isCustomPackage && $customPackageCost > 0) {
            $packageCost = $customPackageCost;
            $packageId = null;
        } elseif ($packageId) {
            $stmtP = $db->prepare("SELECT * FROM catering_packages WHERE id = ? LIMIT 1");
            $stmtP->execute([$packageId]);
            $package = $stmtP->fetch();
            if ($package) {
                $packageCost = (float)$package['price_per_event'];
            }
        }
        $staffingCost = 640.00; // Base professional staffing: 4 waitstaff + 2 bartenders + 1 coordinator

        if ($isCustomPackage && !empty($customDishes)) {
            $pkgNote = "Custom Hybrid Package: " . count($customDishes) . " dishes chosen across caterer tiers.";
            $specialNotes = trim($pkgNote . " " . $specialNotes);
        }

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
                            'image_url'  => $itemRow['image_url'] ?? null,
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
            // 1. Insert Booking (Starts as 'pending' for Administrative Review)
            $hasCatering = ($packageId || !empty($itemsToInsert) || $isCustomPackage);
            $initialCatererStatus = $hasCatering ? 'pending' : 'accepted';
            $stmtB = $db->prepare("INSERT INTO bookings (booking_code, customer_id, venue_id, package_id, event_name, event_date, start_time, end_time, guest_count, duration_hours, venue_cost, package_cost, staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount, payment_status, payment_card_last4, booking_status, caterer_status, special_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, 'pending', ?, ?)");
            $stmtB->execute([
                $bookingCode, $user['id'], $venueId, $packageId, $eventName, $eventDate,
                $startTime, $endTime, $guestCount, $durationHours, $venueCost, $packageCost,
                $staffingCost, $addonsCost, $subtotal, $serviceFee, $taxVat, $totalAmount,
                $cardLast4, $initialCatererStatus, $specialNotes
            ]);
            $bookingId = (int)$db->lastInsertId();

            // 2. Insert Singular Add-On Items
            $stmtItemIns = $db->prepare("INSERT INTO booking_singular_items (booking_id, singular_item_id, item_name, emoji, image_url, unit_price, quantity, subtotal, item_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
            foreach ($itemsToInsert as $ins) {
                $stmtItemIns->execute([$bookingId, $ins['item_id'], $ins['name'], $ins['emoji'], $ins['image_url'] ?? null, $ins['unit_price'], $ins['qty'], $ins['subtotal'], $ins['notes']]);
            }

            // 2b. Insert Custom Package Dishes (allows caterer to review & accept/reject each customized dish)
            if ($isCustomPackage && !empty($customDishes)) {
                $stmtCustomDishIns = $db->prepare("INSERT INTO booking_singular_items (booking_id, singular_item_id, item_name, emoji, dietary_tag, unit_price, quantity, subtotal, item_status, notes) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
                foreach ($customDishes as $cd) {
                    $cdName = sanitize($cd['name'] ?? 'Custom Dish');
                    $cdTier = sanitize($cd['tier'] ?? 'Package');
                    $cdCourse = sanitize($cd['course'] ?? 'Entrée');
                    $cdDiet = sanitize($cd['diet'] ?? '');
                    $cdPrice = (float)($cd['price'] ?? $cd['unit_value'] ?? 0);
                    $cdQty = $guestCount;
                    $cdSub = $cdPrice * $guestCount;
                    $cdNote = "From " . $cdTier . " Package (" . $cdCourse . ")";
                    
                    $emoji = '🍽️';
                    if (stripos($cdCourse, 'appetizer') !== false) $emoji = '🥗';
                    elseif (stripos($cdCourse, 'main') !== false) $emoji = '🥩';
                    elseif (stripos($cdCourse, 'dessert') !== false) $emoji = '🍮';
                    elseif (stripos($cdCourse, 'beverage') !== false) $emoji = '🍷';

                    $stmtCustomDishIns->execute([$bookingId, $cdName, $emoji, $cdDiet, $cdPrice, $cdQty, $cdSub, $cdNote]);
                }
            }

            // 3. Create Invoice (Paid on checkout)
            $invCode = 'INV-' . date('Y') . '-' . rand(1000, 9999);
            $stmtInv = $db->prepare("INSERT INTO invoices (invoice_code, booking_id, customer_id, issue_date, due_date, subtotal, service_fee, vat_tax, total_amount, payment_status) VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 'paid')");
            $stmtInv->execute([$invCode, $bookingId, $user['id'], $eventDate, $subtotal, $serviceFee, $taxVat, $totalAmount]);

            // 4. Notify Customer & Admin (Quarantined until Admin reviews & accepts/rejects)
            $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, ?, ?, 0)");
            $stmtNotif->execute([$user['id'], "Booking Submitted ($bookingCode)", "Your reservation for {$venue['name']} on $eventDate has been submitted and is currently under review by Venue Administration.", 'booking', 'customer-live-progress.php?booking_id=' . $bookingId]);

            try {
                $stmtAdmins = $db->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                if ($stmtAdmins) {
                    while ($adm = $stmtAdmins->fetch()) {
                        $stmtNotif->execute([(int)$adm['id'], "New Booking Pending Review ($bookingCode)", "Customer {$user['name']} placed a reservation for {$venue['name']} awaiting approval.", 'booking', 'admin-booking-approval.php?booking_id=' . $bookingId]);
                    }
                }
            } catch(Exception $e) {}

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

    case 'live_progress':
        $user = requireLogin();
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $userRole = strtolower($user['role'] ?? 'customer');

        if ($bookingId > 0) {
            $stmt = $db->prepare(
                "SELECT b.*, v.name AS venue_name, v.district AS venue_district, v.capacity AS venue_capacity,
                        cp.title AS package_title, cp.tier AS package_tier,
                        sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff, sa.reported_venue,
                        co.package_decision, co.preparation_status
                 FROM bookings b
                 JOIN venues v ON b.venue_id = v.id
                 LEFT JOIN catering_packages cp ON b.package_id = cp.id
                 LEFT JOIN staff_assignments sa ON sa.booking_id = b.id
                 LEFT JOIN caterer_orders co ON co.booking_id = b.id
                 WHERE b.id = ? AND (b.customer_id = ? OR ? IN ('admin', 'staff'))
                 LIMIT 1"
            );
            $stmt->execute([$bookingId, $user['id'], $userRole]);
            $booking = $stmt->fetch();
        } else {
            $stmt = $db->prepare(
                "SELECT b.*, v.name AS venue_name, v.district AS venue_district, v.capacity AS venue_capacity,
                        cp.title AS package_title, cp.tier AS package_tier,
                        sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff, sa.reported_venue,
                        co.package_decision, co.preparation_status
                 FROM bookings b
                 JOIN venues v ON b.venue_id = v.id
                 LEFT JOIN catering_packages cp ON b.package_id = cp.id
                 LEFT JOIN staff_assignments sa ON sa.booking_id = b.id
                 LEFT JOIN caterer_orders co ON co.booking_id = b.id
                 WHERE b.customer_id = ?
                 ORDER BY b.event_date ASC
                 LIMIT 1"
            );
            $stmt->execute([$user['id']]);
            $booking = $stmt->fetch();
        }

        if (!$booking) {
            jsonResponse(false, 'No active booking record found.', null, 404);
        }

        // Compute 6 dynamic progression steps
        $steps = getBookingProgressTimeline($booking);

        $setupStatusVal = strtolower($booking['setup_status'] ?? '');
        $isReady = ($setupStatusVal === 'completed' || (int)($booking['completion_pct'] ?? 0) >= 100 || strtolower($booking['booking_status']) === 'completed');
        $inPrep  = ($setupStatusVal === 'in_progress' || (int)($booking['completion_pct'] ?? 0) > 0);

        // Fetch customer's full booking list for real-time sidebar/list sync
        $stmtAll = $db->prepare("SELECT id, booking_code, event_name, booking_status, caterer_status, event_date FROM bookings WHERE customer_id = ? ORDER BY event_date ASC");
        $stmtAll->execute([$booking['customer_id']]);
        $allSummaries = $stmtAll->fetchAll();

        jsonResponse(true, 'Live progress updated', [
            'booking'          => $booking,
            'steps'            => $steps,
            'is_ready'         => $isReady,
            'in_prep'          => $inPrep,
            'setup_info'       => [
                'status'         => $booking['setup_status'] ?? 'pending',
                'pct'            => (int)($booking['completion_pct'] ?? 0),
                'completed_at'   => $booking['completed_at'] ?? null,
                'reported_staff' => $booking['reported_staff'] ?? null,
                'reported_venue' => $booking['reported_venue'] ?? null
            ],
            'all_bookings'     => $allSummaries,
            'server_time'      => date('Y-m-d H:i:s')
        ]);
        break;

    case 'update_status':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $bookingId = (int)($input['booking_id'] ?? 0);
        $newStatus = strtolower(sanitize($input['status'] ?? 'confirmed'));
        $notes = sanitize($input['notes'] ?? '');

        if ($bookingId <= 0) {
            jsonResponse(false, 'Invalid booking ID', null, 400);
        }

        $stmtBk = $db->prepare(
            "SELECT b.*, v.name AS venue_name, v.address AS venue_address,
                    u.name AS customer_name, u.email AS customer_email
             FROM bookings b
             JOIN venues v ON b.venue_id = v.id
             JOIN users u ON b.customer_id = u.id
             WHERE b.id = ? LIMIT 1"
        );
        $stmtBk->execute([$bookingId]);
        $bk = $stmtBk->fetch();
        if (!$bk) {
            jsonResponse(false, 'Booking not found', null, 404);
        }

        $stmt = $db->prepare("UPDATE bookings SET booking_status = ?, special_notes = CONCAT(COALESCE(special_notes,''), '\n[Admin Update: ', ?, ']') WHERE id = ?");
        $stmt->execute([$newStatus, $notes, $bookingId]);

        $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, ?, ?, 0)");

        if ($newStatus === 'confirmed') {
            // =========================================================================
            // ADMIN ACCEPTED: Forward to Respective Caterer & Respective Venue Staff
            // =========================================================================

            // 1. CATERER FORWARDING
            $packageId = (int)($bk['package_id'] ?? 0);
            $stmtCountItems = $db->prepare("SELECT COUNT(*) FROM booking_singular_items WHERE booking_id = ?");
            $stmtCountItems->execute([$bookingId]);
            $hasCustomItems = ((int)$stmtCountItems->fetchColumn()) > 0;
            $hasCatering = ($packageId > 0 || $hasCustomItems);

            if ($hasCatering) {
                // Resolve respective caterer
                $catererId = null;
                if ($packageId > 0) {
                    $stmtPkgCat = $db->prepare("SELECT caterer_id FROM catering_packages WHERE id = ? LIMIT 1");
                    $stmtPkgCat->execute([$packageId]);
                    $catererId = $stmtPkgCat->fetchColumn();
                }
                if (!$catererId) {
                    $stmtSingCat = $db->prepare(
                        "SELECT smi.caterer_id 
                         FROM booking_singular_items bsi 
                         JOIN singular_menu_items smi ON bsi.singular_item_id = smi.id 
                         WHERE bsi.booking_id = ? AND smi.caterer_id IS NOT NULL 
                         LIMIT 1"
                    );
                    $stmtSingCat->execute([$bookingId]);
                    $catererId = $stmtSingCat->fetchColumn();
                }
                if (!$catererId && !empty($bk['venue_name'])) {
                    $stmtVcat = $db->prepare("SELECT user_id FROM caterer_profiles WHERE assigned_venue = ? AND approval_status = 'approved' LIMIT 1");
                    $stmtVcat->execute([$bk['venue_name']]);
                    $catererId = $stmtVcat->fetchColumn();
                }
                if (!$catererId) {
                    $stmtCatFirst = $db->query("SELECT user_id FROM caterer_profiles WHERE approval_status = 'approved' ORDER BY id ASC LIMIT 1");
                    $catererId = $stmtCatFirst ? $stmtCatFirst->fetchColumn() : null;
                }
                if (!$catererId) {
                    $stmtUserCat = $db->query("SELECT id FROM users WHERE role = 'caterer' AND status = 'active' ORDER BY id ASC LIMIT 1");
                    $catererId = $stmtUserCat ? $stmtUserCat->fetchColumn() : 2;
                }

                // Create or reactivate caterer_orders record
                $stmtCheckOrd = $db->prepare("SELECT id FROM caterer_orders WHERE booking_id = ? LIMIT 1");
                $stmtCheckOrd->execute([$bookingId]);
                $existingOrderId = $stmtCheckOrd->fetchColumn();

                if (!$existingOrderId) {
                    $orderCode = 'ORD-' . rand(2000, 9999);
                    $stmtOrd = $db->prepare("INSERT INTO caterer_orders (order_code, booking_id, caterer_id, covers_count, preparation_status, package_decision) VALUES (?, ?, ?, ?, 'Preparing', 'Pending')");
                    $stmtOrd->execute([$orderCode, $bookingId, $catererId, (int)$bk['guest_count']]);
                    $orderId = (int)$db->lastInsertId();
                } else {
                    $orderId = (int)$existingOrderId;
                    $db->prepare("UPDATE caterer_orders SET caterer_id = ?, package_decision = 'Pending', preparation_status = 'Preparing', covers_count = ? WHERE id = ?")->execute([$catererId, (int)$bk['guest_count'], $orderId]);
                }

                // Update bookings caterer_status to pending review by caterer
                $db->prepare("UPDATE bookings SET caterer_status = 'pending' WHERE id = ?")->execute([$bookingId]);

                // Notify Caterer
                $stmtNotif->execute([
                    (int)$catererId,
                    "New Catering Order ({$bk['booking_code']})",
                    "Admin has approved the reservation for {$bk['venue_name']} on {$bk['event_date']}. Please review and accept/decline the order.",
                    'order',
                    'caterer-order-details.php?order_id=' . $orderId
                ]);
            } else {
                // No catering requested: mark caterer_status as accepted
                $db->prepare("UPDATE bookings SET caterer_status = 'accepted' WHERE id = ?")->execute([$bookingId]);
            }

            // 2. VENUE STAFF FORWARDING
            $venueId = (int)$bk['venue_id'];
            $stmtStaffVenue = $db->prepare(
                "SELECT sp.user_id, u.name 
                 FROM staff_profiles sp 
                 JOIN users u ON sp.user_id = u.id 
                 WHERE sp.active_status = 'active' 
                   AND (FIND_IN_SET(?, sp.assigned_venues) > 0 OR sp.assigned_venues LIKE ?)
                 LIMIT 1"
            );
            $stmtStaffVenue->execute([$venueId, "%$venueId%"]);
            $staffRow = $stmtStaffVenue->fetch();

            if (!$staffRow) {
                // Fallback to active lead coordinator
                $stmtStaffLead = $db->query(
                    "SELECT sp.user_id, u.name 
                     FROM staff_profiles sp 
                     JOIN users u ON sp.user_id = u.id 
                     WHERE sp.active_status = 'active' 
                     ORDER BY sp.id ASC LIMIT 1"
                );
                $staffRow = $stmtStaffLead ? $stmtStaffLead->fetch() : null;
            }
            if (!$staffRow) {
                $stmtStaffU = $db->query(
                    "SELECT id AS user_id, name 
                     FROM users 
                     WHERE role = 'staff' AND status = 'active' 
                     ORDER BY id ASC LIMIT 1"
                );
                $staffRow = $stmtStaffU ? $stmtStaffU->fetch() : ['user_id' => 3, 'name' => 'Sarah Jenkins'];
            }

            $staffUserId = (int)($staffRow['user_id'] ?? $staffRow['id'] ?? 3);
            $staffName = $staffRow['name'] ?? 'Sarah Jenkins';

            // Check if staff_assignments record exists
            $stmtCheckStaff = $db->prepare("SELECT id FROM staff_assignments WHERE booking_id = ? LIMIT 1");
            $stmtCheckStaff->execute([$bookingId]);
            $existingStaffAsgnId = $stmtCheckStaff->fetchColumn();

            $shiftTime = (!empty($bk['start_time']) && !empty($bk['end_time']))
                ? substr($bk['start_time'], 0, 5) . ' - ' . substr($bk['end_time'], 0, 5)
                : '14:00 - 23:00';

            $defaultChecklist = json_encode([
                "Floor layout and banquet table arrangement verified" => false,
                "Bose audio rig assembly and wireless mic array test" => false,
                "Catering prep station staging and bar glassware stocked" => false,
                "Valet parking cones and directional signage positioned" => false,
                "Security detail radio distribution and perimeter briefing" => false,
                "Guest registration desk setup (badges, lanyards, packets)" => false,
                "LED video wall and main projector presentation feed tested" => false,
                "Floral and decor delivery receipt sign-off with dock staff" => false,
                "ADA accessibility ramp and elevator clearances inspected" => false,
                "Emergency exits verified clear and fire safety sweep complete" => false
            ]);

            if (!$existingStaffAsgnId) {
                $stmtStaffIns = $db->prepare(
                    "INSERT INTO staff_assignments (booking_id, staff_id, role_title, shift_time, setup_status, completion_pct, reported_venue, reported_staff, checklist_json) 
                     VALUES (?, ?, 'Lead Event Coordinator', ?, 'pending', 0, ?, ?, ?)"
                );
                $stmtStaffIns->execute([$bookingId, $staffUserId, $shiftTime, $bk['venue_name'], $staffName, $defaultChecklist]);
            } else {
                $db->prepare(
                    "UPDATE staff_assignments 
                     SET staff_id = ?, shift_time = ?, reported_venue = ?, reported_staff = ? 
                     WHERE id = ?"
                )->execute([$staffUserId, $shiftTime, $bk['venue_name'], $staffName, (int)$existingStaffAsgnId]);
            }

            // Notify Staff Member
            $stmtNotif->execute([
                $staffUserId,
                "New Event Assignment ({$bk['booking_code']})",
                "Admin has approved reservation at {$bk['venue_name']} on {$bk['event_date']}. Event setup checklist is now active in your portal.",
                'assignment',
                'staff-event-setup.php?booking_id=' . $bookingId
            ]);

            // Notify Customer of Admin Approval
            $stmtNotif->execute([
                (int)$bk['customer_id'],
                "Booking Approved ({$bk['booking_code']})",
                "Your reservation for {$bk['event_name']} at {$bk['venue_name']} has been approved by Venue Administration and forwarded for venue and catering preparation.",
                'booking',
                'customer-live-progress.php?booking_id=' . $bookingId
            ]);

        } else if ($newStatus === 'rejected') {
            // =========================================================================
            // ADMIN REJECTED: Quarantine & Purge Any Provisional Caterer/Staff Records
            // =========================================================================
            $db->prepare("DELETE FROM caterer_orders WHERE booking_id = ?")->execute([$bookingId]);
            $db->prepare("DELETE FROM staff_assignments WHERE booking_id = ?")->execute([$bookingId]);

            // Notify Customer of Admin Rejection
            $stmtNotif->execute([
                (int)$bk['customer_id'],
                "Booking Rejected ({$bk['booking_code']})",
                "Your reservation for {$bk['event_name']} was rejected by Venue Administration: " . ($notes ?: 'Scheduling conflict or capacity limit reached.'),
                'booking',
                'customer-live-progress.php?booking_id=' . $bookingId
            ]);
        }

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

        // Customers can ONLY cancel 'pending' or 'inquiry' bookings.
        // Confirmed bookings cannot be cancelled by customers.
        if (!$isAdmin) {
            if ($bk['booking_status'] === 'confirmed') {
                jsonResponse(false, "Confirmed reservations cannot be cancelled online. Please contact VenuePro administration.", null, 403);
            }
            if (!in_array($bk['booking_status'], ['pending', 'inquiry'])) {
                jsonResponse(false, "Only pending reservations may be cancelled. Current status: " . ucfirst($bk['booking_status']) . ".", null, 409);
            }
        } else {
            if (in_array($bk['booking_status'], ['completed', 'cancelled'])) {
                jsonResponse(false, "Booking is already {$bk['booking_status']} and cannot be cancelled", null, 409);
            }
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
