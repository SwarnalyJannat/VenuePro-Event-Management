<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
$db = getDBConnection();

echo "=======================================================\n";
echo "TESTING REAL-TIME BOOKING PROGRESSION LIFECYCLE\n";
echo "=======================================================\n";

// 1. Create a clean test booking
$testCode = 'TEST-' . rand(1000, 9999);
$custUser = $db->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetch();
$custId = $custUser ? $custUser['id'] : 1;
$venueId = 1;
$packageId = 1;

$db->prepare("DELETE FROM bookings WHERE booking_code LIKE 'TEST-%'")->execute();

$stmtIns = $db->prepare("INSERT INTO bookings (booking_code, customer_id, venue_id, package_id, event_name, event_date, start_time, end_time, guest_count, duration_hours, venue_cost, package_cost, staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount, payment_status, booking_status, caterer_status, special_notes, created_at) VALUES (?, ?, ?, ?, 'Automated Live Progress Test Event', '2026-12-25', '18:00:00', '22:00:00', 100, 4, 2500, 1850, 640, 0, 4990, 499, 399.20, 5888.20, 'paid', 'pending', 'pending', 'Test booking lifecycle notes', NOW())");
$stmtIns->execute([$testCode, $custId, $venueId, $packageId]);
$testBookingId = (int)$db->lastInsertId();

// Create caterer order
$stmtOrd = $db->prepare("INSERT INTO caterer_orders (order_code, booking_id, caterer_id, covers_count, preparation_status, package_decision) VALUES (?, ?, 2, 100, 'Preparing', 'Pending')");
$stmtOrd->execute(['ORD-' . rand(1000,9999), $testBookingId]);
$orderId = (int)$db->lastInsertId();

// Create staff assignment
$stmtStaff = $db->prepare("INSERT INTO staff_assignments (booking_id, staff_id, role_title, shift_time, setup_status, completion_pct) VALUES (?, 3, 'Lead Coordinator', '16:00 - 23:00', 'pending', 0)");
$stmtStaff->execute([$testBookingId]);
$assignId = (int)$db->lastInsertId();

function fetchProgress($db, $bId) {
    $stmt = $db->prepare("SELECT b.*, v.name AS venue_name, cp.title AS package_title, sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff, co.package_decision FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id LEFT JOIN staff_assignments sa ON sa.booking_id=b.id LEFT JOIN caterer_orders co ON co.booking_id=b.id WHERE b.id=? LIMIT 1");
    $stmt->execute([$bId]);
    $bk = $stmt->fetch();
    return getBookingProgressTimeline($bk);
}

// -------------------------------------------------------------
// STAGE 1: FRESH BOOKING (Pending Admin & Pending Caterer)
// -------------------------------------------------------------
echo "\n--- STAGE 1: Newly Submitted Booking ---\n";
$steps1 = fetchProgress($db, $testBookingId);
foreach ($steps1 as $s) {
    $mark = $s['done'] ? '✓' : '○';
    echo "  Step {$s['step']}: {$s['label']} [{$mark}] (Badge: {$s['badge']}, Status: {$s['status']})\n";
}
assert($steps1[0]['done'] === true, 'Step 1 must be true');
assert($steps1[1]['done'] === false, 'Step 2 Under Review must be false while pending');
assert($steps1[2]['done'] === false, 'Step 3 Caterer must be false while pending');
assert($steps1[3]['done'] === false, 'Step 4 Approved & Confirmed must be false while pending');
assert($steps1[4]['done'] === false, 'Step 5 Event Prep must be false while pending');
assert($steps1[5]['done'] === false, 'Step 6 Event Complete must be false while pending');
echo "✓ Stage 1 Passed: Under Review is pending, only Booking Submitted is checked.\n";

// -------------------------------------------------------------
// STAGE 2: ADMIN ACCEPTS BOOKING
// -------------------------------------------------------------
echo "\n--- STAGE 2: Admin Accepts Booking ---\n";
$db->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?")->execute([$testBookingId]);
$steps2 = fetchProgress($db, $testBookingId);
foreach ($steps2 as $s) {
    $mark = $s['done'] ? '✓' : '○';
    echo "  Step {$s['step']}: {$s['label']} [{$mark}] (Badge: {$s['badge']}, Status: {$s['status']})\n";
}
assert($steps2[1]['done'] === true, 'Step 2 Under Review MUST get a tick once admin accepts');
assert($steps2[2]['done'] === false, 'Step 3 Caterer still waiting caterer acceptance');
assert($steps2[3]['done'] === false, 'Step 4 not yet confirmed until caterer accepts');
echo "✓ Stage 2 Passed: Under Review received tick ✓ immediately after Admin accepted!\n";

// -------------------------------------------------------------
// STAGE 3: CATERER ACCEPTS ORDER
// -------------------------------------------------------------
echo "\n--- STAGE 3: Caterer Accepts Package & Order ---\n";
$db->prepare("UPDATE caterer_orders SET package_decision = 'Accepted' WHERE id = ?")->execute([$orderId]);
$db->prepare("UPDATE bookings SET caterer_status = 'accepted' WHERE id = ?")->execute([$testBookingId]);
$steps3 = fetchProgress($db, $testBookingId);
foreach ($steps3 as $s) {
    $mark = $s['done'] ? '✓' : '○';
    echo "  Step {$s['step']}: {$s['label']} [{$mark}] (Badge: {$s['badge']}, Status: {$s['status']})\n";
}
assert($steps3[2]['done'] === true, 'Step 3 Caterer Confirmation MUST get a tick');
assert($steps3[3]['done'] === true, 'Step 4 Approved & Confirmed MUST get a tick once both accepted');
echo "✓ Stage 3 Passed: Awaiting Caterer Confirmation & Approved & Confirmed received ticks ✓!\n";

// -------------------------------------------------------------
// STAGE 4: STAFF MARKS EVENT SETUP & COMPLETES EVENT
// -------------------------------------------------------------
echo "\n--- STAGE 4: Staff Completes Event Setup & Submits Completion Report ---\n";
$db->prepare("UPDATE staff_assignments SET setup_status = 'completed', completion_pct = 100, completed_at = NOW(), reported_staff = 'Elena Rostova' WHERE id = ?")->execute([$assignId]);
$db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE id = ?")->execute([$testBookingId]);
$steps4 = fetchProgress($db, $testBookingId);
foreach ($steps4 as $s) {
    $mark = $s['done'] ? '✓' : '○';
    echo "  Step {$s['step']}: {$s['label']} [{$mark}] (Badge: {$s['badge']}, Status: {$s['status']})\n";
}
assert($steps4[4]['done'] === true, 'Step 5 Event Prep MUST get a tick');
assert($steps4[5]['done'] === true, 'Step 6 Event Complete MUST get a tick');
echo "✓ Stage 4 Passed: Event Day Preparation & Event Complete both received ticks ✓!\n";

// -------------------------------------------------------------
// STAGE 5: TEST REAL-TIME API ENDPOINT
// -------------------------------------------------------------
echo "\n--- STAGE 5: Test API Endpoint api/bookings.php?action=live_progress ---\n";
$_SESSION['user_id'] = $custId;
$_SESSION['user_role'] = 'customer';
$_GET['action'] = 'live_progress';
$_GET['booking_id'] = $testBookingId;

ob_start();
require __DIR__ . '/../api/bookings.php';
$jsonOutput = ob_get_clean();
$apiResult = json_decode($jsonOutput, true);

assert($apiResult['success'] === true, 'API response must be successful');
assert(count($apiResult['data']['steps']) === 6, 'Must return all 6 steps');
assert($apiResult['data']['is_ready'] === true, 'is_ready must be true for completed event');
echo "✓ Stage 5 Passed: API returned 6 real-time dynamic steps with full metadata!\n";

// Clean up test booking
$db->prepare("DELETE FROM staff_assignments WHERE id = ?")->execute([$assignId]);
$db->prepare("DELETE FROM caterer_orders WHERE id = ?")->execute([$orderId]);
$db->prepare("DELETE FROM bookings WHERE id = ?")->execute([$testBookingId]);
echo "\n✓ Test booking cleaned up.\n";
echo "\n🎉 ALL REAL-TIME PROGRESSION TESTS PASSED 100%!\n";
