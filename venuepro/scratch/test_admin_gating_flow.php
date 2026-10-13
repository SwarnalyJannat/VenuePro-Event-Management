<?php
/**
 * Test Admin-First Gating & Dual-Dispatch Workflow
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$db = getDBConnection();

echo "=======================================================\n";
echo "TEST: ADMIN-FIRST GATING & DUAL-DISPATCH WORKFLOW\n";
echo "=======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($cond, $name) {
    global $passCount, $failCount;
    if ($cond) {
        echo "  [PASS] $name\n";
        $passCount++;
    } else {
        echo "  [FAIL] $name\n";
        $failCount++;
    }
}

function runApiCli($scriptPath, $sessionRole, $sessionUserId, $getParams, $postParams) {
    $code = '<?php '
          . 'error_reporting(0); @ini_set("display_errors", "0"); '
          . '$_SERVER["SCRIPT_NAME"] = "/venuepro/api/bookings.php"; '
          . '$_SERVER["HTTP_ACCEPT"] = "application/json"; '
          . 'session_start(); '
          . '$_SESSION["user_role"] = ' . var_export($sessionRole, true) . '; '
          . '$_SESSION["user_id"] = ' . var_export($sessionUserId, true) . '; '
          . '$_GET = ' . var_export($getParams, true) . '; '
          . '$_POST = ' . var_export($postParams, true) . '; '
          . 'require ' . var_export($scriptPath, true) . '; ';

    $tmpFile = __DIR__ . '/tmp_runner_' . uniqid() . '.php';
    file_put_contents($tmpFile, $code);
    $output = shell_exec('C:\\xampp\\php\\php.exe ' . escapeshellarg($tmpFile));
    @unlink($tmpFile);
    $clean = trim((string)$output);
    $pos = strpos($clean, '{');
    if ($pos !== false) {
        $clean = substr($clean, $pos);
    }
    return json_decode($clean, true);
}

// -------------------------------------------------------------
// 1. CUSTOMER BOOKING CREATION
// -------------------------------------------------------------
echo "1. Customer books a venue (Grand Emerald Ballroom, Package 1)...\n";
$customerUser = $db->query("SELECT * FROM users WHERE role = 'customer' LIMIT 1")->fetch();
$adminUser    = $db->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch();
$venue = $db->query("SELECT * FROM venues WHERE id = 1 LIMIT 1")->fetch(); // Venue 1: Grand Emerald Ballroom
$package = $db->query("SELECT * FROM catering_packages WHERE id = 1 LIMIT 1")->fetch(); // Package 1: caterer_id = 2

$bookingCode = 'VP-TEST-' . rand(10000, 99999);
$eventDate = date('Y-m-d', strtotime('+90 days'));

// Insert booking via api/bookings.php?action=create runner
$createResp = runApiCli(
    __DIR__ . '/../api/bookings.php',
    'customer',
    $customerUser['id'],
    ['action' => 'create'],
    [
        'venue_id' => $venue['id'],
        'package_id' => $package['id'],
        'event_name' => 'Corporate Annual Gala',
        'event_date' => $eventDate,
        'start_time' => '17:00',
        'end_time' => '23:00',
        'guest_count' => 120,
        'special_notes' => 'Admin First Gate Test',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvv' => '123'
    ]
);

assertTest($createResp && $createResp['success'] === true, "Booking created via api/bookings.php?action=create");
$bookingId = (int)($createResp['data']['booking_id'] ?? 0);
$actualBookingCode = $createResp['data']['booking_code'] ?? '';

// Verify booking table state
$stmtCheckBk = $db->prepare("SELECT * FROM bookings WHERE id = ?");
$stmtCheckBk->execute([$bookingId]);
$createdBk = $stmtCheckBk->fetch();

assertTest($createdBk['booking_status'] === 'pending', "Booking status is 'pending' (quarantined under admin review)");
assertTest($createdBk['caterer_status'] === 'pending', "Caterer status is 'pending'");

// Verify quarantine: caterer_orders and staff_assignments must NOT exist
$stmtCheckCo = $db->prepare("SELECT COUNT(*) FROM caterer_orders WHERE booking_id = ?");
$stmtCheckCo->execute([$bookingId]);
assertTest((int)$stmtCheckCo->fetchColumn() === 0, "No caterer_orders created at customer booking creation");

$stmtCheckSa = $db->prepare("SELECT COUNT(*) FROM staff_assignments WHERE booking_id = ?");
$stmtCheckSa->execute([$bookingId]);
assertTest((int)$stmtCheckSa->fetchColumn() === 0, "No staff_assignments created at customer booking creation");

// Verify caterer dashboard query excludes pending booking
$stmtCatAO = $db->prepare("SELECT COUNT(*) FROM caterer_orders co JOIN bookings b ON co.booking_id = b.id WHERE co.booking_id = ? AND b.booking_status IN ('confirmed', 'completed')");
$stmtCatAO->execute([$bookingId]);
assertTest((int)$stmtCatAO->fetchColumn() === 0, "Caterer dashboard excludes unconfirmed booking");

// Verify staff dashboard query excludes pending booking
$stmtStaffAO = $db->prepare("SELECT COUNT(*) FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id WHERE sa.booking_id = ? AND b.booking_status IN ('confirmed', 'completed')");
$stmtStaffAO->execute([$bookingId]);
assertTest((int)$stmtStaffAO->fetchColumn() === 0, "Staff dashboard excludes unconfirmed booking");

// Verify customer live progress timeline in pending state
$timeline1 = getBookingProgressTimeline($createdBk);
assertTest($timeline1[0]['done'] === true, "Timeline Step 1 (Booking Submitted) is DONE");
assertTest($timeline1[1]['done'] === false && $timeline1[1]['status'] === 'in_progress', "Timeline Step 2 (Under Review) is IN PROGRESS");
assertTest($timeline1[2]['done'] === false && $timeline1[2]['status'] === 'waiting', "Timeline Step 3 (Caterer Confirmation) is WAITING");
assertTest($timeline1[3]['done'] === false && $timeline1[3]['status'] === 'waiting', "Timeline Step 4 (Approved & Confirmed) is WAITING");

// -------------------------------------------------------------
// 2. ADMIN ACCEPTS AND CONFIRMS THE BOOKING
// -------------------------------------------------------------
echo "\n2. Admin reviews and approves the booking...\n";
$adminResp = runApiCli(
    __DIR__ . '/../api/bookings.php',
    'admin',
    (int)$adminUser['id'],
    ['action' => 'update_status'],
    ['booking_id' => $bookingId, 'status' => 'confirmed', 'notes' => 'Admin Review Passed']
);

assertTest($adminResp && $adminResp['success'] === true, "Admin approval executed: " . ($adminResp['message'] ?? ''));

// Verify booking status is now confirmed
$stmtCheckBk->execute([$bookingId]);
$confirmedBk = $stmtCheckBk->fetch();
assertTest($confirmedBk['booking_status'] === 'confirmed', "Booking status is now 'confirmed'");

// Verify caterer_orders was created and forwarded to the package caterer
$stmtCheckCo->execute([$bookingId]);
assertTest((int)$stmtCheckCo->fetchColumn() === 1, "caterer_orders created automatically upon Admin approval");

$stmtGetCo = $db->prepare("SELECT * FROM caterer_orders WHERE booking_id = ?");
$stmtGetCo->execute([$bookingId]);
$coRow = $stmtGetCo->fetch();
assertTest((int)$coRow['caterer_id'] === (int)$package['caterer_id'], "Order forwarded to package caterer (ID {$package['caterer_id']})");
assertTest($coRow['package_decision'] === 'Pending', "Caterer package decision is 'Pending' awaiting caterer");

// Verify staff_assignments was created and forwarded to Venue 1 coordinator
$stmtCheckSa->execute([$bookingId]);
assertTest((int)$stmtCheckSa->fetchColumn() === 1, "staff_assignments created automatically upon Admin approval");

$stmtGetSa = $db->prepare("SELECT * FROM staff_assignments WHERE booking_id = ?");
$stmtGetSa->execute([$bookingId]);
$saRow = $stmtGetSa->fetch();
assertTest((int)$saRow['staff_id'] === 3, "Assignment forwarded to Venue 1 coordinator (Sarah Jenkins, user_id 3)");
assertTest($saRow['setup_status'] === 'pending', "Staff setup_status is 'pending'");

// Verify notifications were delivered to Caterer, Staff, and Customer
$stmtNotifs = $db->prepare("SELECT * FROM notifications WHERE title LIKE ? OR message LIKE ? ORDER BY id DESC");
$stmtNotifs->execute(["%$actualBookingCode%", "%$actualBookingCode%"]);
$generatedNotifs = $stmtNotifs->fetchAll();
$notifUserIds = array_column($generatedNotifs, 'user_id');

assertTest(in_array((int)$coRow['caterer_id'], $notifUserIds), "Caterer received notification for new order");
assertTest(in_array((int)$saRow['staff_id'], $notifUserIds), "Staff received notification for new assignment");
assertTest(in_array((int)$confirmedBk['customer_id'], $notifUserIds), "Customer received notification for admin approval");

// Verify customer live progress timeline after Admin approval
$timeline2 = getBookingProgressTimeline($confirmedBk);
assertTest($timeline2[1]['done'] === true, "Timeline Step 2 (Under Review) is now DONE (Admin Approved)");
assertTest($timeline2[2]['status'] === 'in_progress', "Timeline Step 3 (Caterer) is now IN PROGRESS (Active in Caterer Portal)");
assertTest($timeline2[3]['done'] === false, "Timeline Step 4 (Approved & Confirmed) is waiting on Caterer");

// -------------------------------------------------------------
// 3. CATERER ACCEPTS THE ORDER
// -------------------------------------------------------------
echo "\n3. Caterer accepts the order in caterer portal...\n";
$catResp = runApiCli(
    __DIR__ . '/../api/caterer-orders.php',
    'caterer',
    (int)$coRow['caterer_id'],
    ['action' => 'decide_package'],
    ['order_id' => (int)$coRow['id'], 'decision' => 'Accepted', 'reason' => '']
);

assertTest($catResp && $catResp['success'] === true, "Caterer accepted package: " . ($catResp['message'] ?? ''));

$stmtGetCo->execute([$bookingId]);
$coRowAccepted = $stmtGetCo->fetch();
assertTest($coRowAccepted['package_decision'] === 'Accepted', "caterer_orders package_decision is now 'Accepted'");

$stmtCheckBk->execute([$bookingId]);
$bkCatAccepted = $stmtCheckBk->fetch();
assertTest($bkCatAccepted['caterer_status'] === 'accepted', "bookings caterer_status is now 'accepted'");

// Verify customer live progress timeline after Caterer acceptance
$timeline3 = getBookingProgressTimeline($bkCatAccepted);
assertTest($timeline3[2]['done'] === true, "Timeline Step 3 (Awaiting Caterer Confirmation) is now DONE");
assertTest($timeline3[3]['done'] === true, "Timeline Step 4 (Approved & Confirmed) is now DONE (Both Admin & Caterer Confirmed)");

// -------------------------------------------------------------
// 4. ADMIN REJECTION SCENARIO (QUARANTINE & CLEANUP)
// -------------------------------------------------------------
echo "\n4. Testing Admin Rejection scenario...\n";
$createRejResp = runApiCli(
    __DIR__ . '/../api/bookings.php',
    'customer',
    $customerUser['id'],
    ['action' => 'create'],
    [
        'venue_id' => 2,
        'package_id' => 1,
        'event_name' => 'Rejected Event Case',
        'event_date' => $eventDate,
        'start_time' => '10:00',
        'end_time' => '14:00',
        'guest_count' => 50,
        'special_notes' => 'Rejection Test',
        'card_number' => '4242424242424242',
        'card_expiry' => '12/28',
        'card_cvv' => '123'
    ]
);
$rejBookingId = (int)($createRejResp['data']['booking_id'] ?? 0);
$rejCode = $createRejResp['data']['booking_code'] ?? '';

// Verify initial state
$stmtCheckCo->execute([$rejBookingId]);
assertTest((int)$stmtCheckCo->fetchColumn() === 0, "Rejection test: Initial caterer_orders is 0");
$stmtCheckSa->execute([$rejBookingId]);
assertTest((int)$stmtCheckSa->fetchColumn() === 0, "Rejection test: Initial staff_assignments is 0");

// Admin rejects
$rejResp = runApiCli(
    __DIR__ . '/../api/bookings.php',
    'admin',
    (int)$adminUser['id'],
    ['action' => 'update_status'],
    ['booking_id' => $rejBookingId, 'status' => 'rejected', 'notes' => 'Date unavailable due to maintenance']
);
assertTest($rejResp && $rejResp['success'] === true, "Admin rejection executed successfully");

$stmtCheckBk->execute([$rejBookingId]);
$rejBk = $stmtCheckBk->fetch();
assertTest($rejBk['booking_status'] === 'rejected', "Booking status updated to 'rejected'");

// Verify no caterer order or staff assignment exists
$stmtCheckCo->execute([$rejBookingId]);
assertTest((int)$stmtCheckCo->fetchColumn() === 0, "No caterer order created or remaining after Admin rejection");
$stmtCheckSa->execute([$rejBookingId]);
assertTest((int)$stmtCheckSa->fetchColumn() === 0, "No staff assignment created or remaining after Admin rejection");

$timelineRej = getBookingProgressTimeline($rejBk);
assertTest($timelineRej[1]['status'] === 'rejected', "Timeline Step 2 reflects 'rejected' with admin reason");

// Clean up test records
$db->prepare("DELETE FROM notifications WHERE message LIKE ? OR message LIKE ?")->execute(["%$actualBookingCode%", "%$rejCode%"]);
$db->prepare("DELETE FROM staff_assignments WHERE booking_id IN (?, ?)")->execute([$bookingId, $rejBookingId]);
$db->prepare("DELETE FROM caterer_orders WHERE booking_id IN (?, ?)")->execute([$bookingId, $rejBookingId]);
$db->prepare("DELETE FROM booking_singular_items WHERE booking_id IN (?, ?)")->execute([$bookingId, $rejBookingId]);
$db->prepare("DELETE FROM invoices WHERE booking_id IN (?, ?)")->execute([$bookingId, $rejBookingId]);
$db->prepare("DELETE FROM bookings WHERE id IN (?, ?)")->execute([$bookingId, $rejBookingId]);

echo "\n=======================================================\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "=======================================================\n";
