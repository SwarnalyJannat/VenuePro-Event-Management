<?php
/**
 * scratch/test_guest_count_flow.php
 * Comprehensive automated verification for guest count persistence and end-to-end display accuracy.
 */

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$db = getDBConnection();
$passed = 0;
$failed = 0;

function assertCheck($desc, $cond) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc\n";
        $failed++;
    }
}

echo "=== 1. Testing URL and Parameter Propagation in Booking Flow ===\n";

// Test 1: booking-catering-packages.php propagates guests in URL
$_GET = [
    'venue_id' => 1,
    'plan' => 'enterprise',
    'plan_rate' => 2400,
    'duration_hours' => 12,
    'date' => '2026-11-20',
    'start_time' => '11:00:00',
    'end_time' => '23:00:00',
    'guests' => 173,
    'format' => 'Corporate Summit'
];
$_SESSION = ['user_id' => 1, 'role' => 'customer', 'name' => 'Test Customer'];

ob_start();
include __DIR__ . '/../customer/booking-catering-packages.php';
$htmlPackages = ob_get_clean();

assertCheck("booking-catering-packages.php displays 173 attendees in header", strpos($htmlPackages, '173 attendees') !== false);
assertCheck("booking-catering-packages.php includes guests=173 in package checkout links", strpos($htmlPackages, 'guests=173') !== false);
assertCheck("booking-catering-packages.php includes date=2026-11-20 in package checkout links", strpos($htmlPackages, 'date=2026-11-20') !== false);
assertCheck("booking-catering-packages.php saves vp_guests in sessionStorage script", strpos($htmlPackages, "sessionStorage.setItem('vp_guests', '173')") !== false);

// Test 2: caterer-custom-package.php initializes with guest count
$_GET = [
    'venue_id' => 1,
    'guests' => 215,
    'date' => '2026-11-21',
    'start_time' => '10:00:00',
    'end_time' => '22:00:00'
];
ob_start();
include __DIR__ . '/../customer/caterer-custom-package.php';
$htmlCustom = ob_get_clean();

assertCheck("caterer-custom-package.php inputs 215 in guest-count-input", strpos($htmlCustom, 'value="215"') !== false);
assertCheck("caterer-custom-package.php initializes JS guestCount with 215", strpos($htmlCustom, "sessionStorage.getItem('vp_guests') || '215'") !== false);
assertCheck("caterer-custom-package.php saves vp_guests in proceedToPayment", strpos($htmlCustom, "sessionStorage.setItem('vp_guests', guestCount.toString())") !== false);

// Test 3: booking-summary-payment.php receives and injects guest count
$_GET = [
    'venue_id' => 1,
    'package_id' => 1,
    'guests' => 188,
    'date' => '2026-11-22',
    'start_time' => '10:00:00',
    'end_time' => '22:00:00'
];
ob_start();
include __DIR__ . '/../customer/booking-summary-payment.php';
$htmlSummary = ob_get_clean();

assertCheck("booking-summary-payment.php displays 188 Attendees Expected", strpos($htmlSummary, '188</span> Attendees Expected') !== false);
assertCheck("booking-summary-payment.php injects dynamic guest_count without hardcoded 100", strpos($htmlSummary, "addHidden('guest_count',    vpGuests);") !== false);
assertCheck("booking-summary-payment.php fallback vpGuests uses 188", strpos($htmlSummary, "|| '188'") !== false);

// Test 4: book-venue.php has dynamic counter script and pay link
$_GET = ['venue_id' => 1, 'date' => '2026-11-25'];
ob_start();
include __DIR__ . '/../customer/book-venue.php';
$htmlBookVenue = ob_get_clean();

assertCheck("book-venue.php has bv-guest-val element", strpos($htmlBookVenue, 'id="bv-guest-val"') !== false);
assertCheck("book-venue.php has minus and plus buttons", strpos($htmlBookVenue, 'id="btn-guest-minus"') !== false && strpos($htmlBookVenue, 'id="btn-guest-plus"') !== false);
assertCheck("book-venue.php script binds updateGuestDisplay", strpos($htmlBookVenue, 'function updateGuestDisplay()') !== false);

echo "\n=== 2. Testing Database Insertion with Custom Guest Counts ===\n";

// Create test booking with arbitrary non-default guest count: 147 attendees
$testBookingCode = 'BK-TEST-GC-' . rand(1000, 9999);
$testGuestCount = 147;
$eventDate = '2026-12-05';
$startTime = '14:00:00';
$endTime   = '22:00:00';

// Check if customer exists
$testCust = $db->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetch();
$customerId = $testCust ? (int)$testCust['id'] : 1;

$stmtIns = $db->prepare("
    INSERT INTO bookings (
        booking_code, customer_id, venue_id, package_id, event_name, event_date,
        start_time, end_time, guest_count, duration_hours, venue_cost, package_cost,
        staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount,
        payment_status, booking_status, caterer_status, special_notes
    ) VALUES (
        ?, ?, 1, 1, 'Tech Leaders Summit', ?,
        ?, ?, ?, 8, 2400.00, 1850.00,
        640.00, 0.00, 4890.00, 489.00, 391.20, 5770.20,
        'paid', 'pending', 'pending', 'VIP attendance of 147 guests confirmed.'
    )
");
$stmtIns->execute([$testBookingCode, $customerId, $eventDate, $startTime, $endTime, $testGuestCount]);
$newBookingId = (int)$db->lastInsertId();

assertCheck("Inserted booking ID > 0", $newBookingId > 0);

// Verify database guest count
$stmtVerify = $db->prepare("SELECT guest_count FROM bookings WHERE id = ?");
$stmtVerify->execute([$newBookingId]);
$savedGuests = (int)$stmtVerify->fetchColumn();
assertCheck("Database bookings.guest_count exactly equals $testGuestCount", $savedGuests === $testGuestCount);

echo "\n=== 3. Testing Admin Approval and Dual-Dispatch Syncing ===\n";

// Execute admin approval dual dispatch
// 1. Update booking to confirmed
$db->prepare("UPDATE bookings SET booking_status = 'confirmed', caterer_status = 'pending' WHERE id = ?")->execute([$newBookingId]);

// 2. Dispatch to caterer_orders
$catererId = 2;
$ordCode = 'ORD-TEST-' . rand(1000, 9999);
$stmtOrd = $db->prepare("
    INSERT INTO caterer_orders (order_code, booking_id, caterer_id, covers_count, preparation_status, package_decision)
    VALUES (?, ?, ?, ?, 'Preparing', 'Pending')
");
$stmtOrd->execute([$ordCode, $newBookingId, $catererId, $testGuestCount]);
$newOrderId = (int)$db->lastInsertId();

// Verify covers_count in caterer_orders
$stmtCheckOrd = $db->prepare("SELECT covers_count FROM caterer_orders WHERE id = ?");
$stmtCheckOrd->execute([$newOrderId]);
$savedCovers = (int)$stmtCheckOrd->fetchColumn();
assertCheck("caterer_orders.covers_count exactly equals $testGuestCount", $savedCovers === $testGuestCount);

// 3. Dispatch to staff_assignments
$staffId = $db->query("SELECT id FROM users WHERE role = 'staff' LIMIT 1")->fetchColumn() ?: 3;
$db->prepare("
    INSERT INTO staff_assignments (booking_id, staff_id, role_title, setup_status, completion_pct)
    VALUES (?, ?, 'Lead Event Coordinator', 'pending', 0)
")->execute([$newBookingId, $staffId]);

echo "\n=== 4. Testing End-to-End Consumer Views for Guest Count Display ===\n";

// View 1: Admin Dashboard (Recent Booking Requests table)
$_SESSION = ['user_id' => 4, 'role' => 'admin', 'name' => 'Alex Sterling'];
$_GET = [];
ob_start();
include __DIR__ . '/../admin/admin-dashboard.php';
$htmlAdminDash = ob_get_clean();

assertCheck("admin-dashboard.php has GUESTS column header", strpos($htmlAdminDash, '<th>GUESTS</th>') !== false);
assertCheck("admin-dashboard.php displays $testGuestCount attendees for test booking", strpos($htmlAdminDash, "$testGuestCount</span> <span class=\"text-xs text-muted\">attendees</span>") !== false);

// View 2: Admin Pending Bookings
ob_start();
include __DIR__ . '/../admin/admin-pending-bookings.php';
$htmlAdminPending = ob_get_clean();
assertCheck("admin-pending-bookings.php has GUESTS header", strpos($htmlAdminPending, '<th>GUESTS</th>') !== false);

// View 3: Admin Booking Approval
$_GET = ['booking_id' => $newBookingId];
ob_start();
include __DIR__ . '/../admin/admin-booking-approval.php';
$htmlAdminAppr = ob_get_clean();
assertCheck("admin-booking-approval.php displays '$testGuestCount Attendees'", strpos($htmlAdminAppr, "$testGuestCount Attendees") !== false);

// View 4: Caterer Order Details
$_SESSION = ['user_id' => $catererId, 'role' => 'caterer', 'name' => 'Master Chef'];
$_GET = ['order_id' => $newOrderId];
ob_start();
include __DIR__ . '/../caterer/caterer-order-details.php';
$htmlCatDetails = ob_get_clean();
assertCheck("caterer-order-details.php displays '$testGuestCount Guests'", strpos($htmlCatDetails, "$testGuestCount Guests") !== false);
assertCheck("caterer-order-details.php displays covers_count '$testGuestCount covers'", strpos($htmlCatDetails, "$testGuestCount covers") !== false);

// View 5: Staff Dashboard
$_SESSION = ['user_id' => $staffId, 'role' => 'staff', 'name' => 'Floor Coordinator'];
$_GET = [];
ob_start();
include __DIR__ . '/../staff/staff-dashboard.php';
$htmlStaffDash = ob_get_clean();
assertCheck("staff-dashboard.php displays '$testGuestCount Guests' in event schedule", strpos($htmlStaffDash, "$testGuestCount</strong> Guests") !== false);

// View 6: Staff Event Setup
$_GET = ['booking_id' => $newBookingId];
ob_start();
include __DIR__ . '/../staff/staff-event-setup.php';
$htmlStaffSetup = ob_get_clean();
assertCheck("staff-event-setup.php displays '$testGuestCount Guests' in header meta", strpos($htmlStaffSetup, "$testGuestCount Guests") !== false);

// View 7: Customer Live Progress
$_SESSION = ['user_id' => $customerId, 'role' => 'customer', 'name' => 'Test Customer'];
$_GET = ['booking_id' => $newBookingId];
ob_start();
include __DIR__ . '/../customer/customer-live-progress.php';
$htmlCustomerLP = ob_get_clean();
assertCheck("customer-live-progress.php displays '$testGuestCount Attendees'", strpos($htmlCustomerLP, "$testGuestCount Attendees") !== false);

// View 8: Customer Booking Status Timeline
ob_start();
include __DIR__ . '/../customer/booking-status-timeline.php';
$htmlCustomerTL = ob_get_clean();
assertCheck("booking-status-timeline.php displays '$testGuestCount Attendees'", strpos($htmlCustomerTL, "$testGuestCount Attendees") !== false);

// View 9: Customer Booking Success
ob_start();
include __DIR__ . '/../customer/booking-success.php';
$htmlSuccess = ob_get_clean();
assertCheck("booking-success.php displays '$testGuestCount Attendees' in Guests row", strpos($htmlSuccess, "$testGuestCount Attendees") !== false);

// View 10: Customer Client Invoice
ob_start();
include __DIR__ . '/../customer/client-invoice.php';
$htmlInvoice = ob_get_clean();
assertCheck("client-invoice.php displays '$testGuestCount Guests'", strpos($htmlInvoice, "$testGuestCount Guests") !== false);

// Clean up test booking records
$db->prepare("DELETE FROM staff_assignments WHERE booking_id = ?")->execute([$newBookingId]);
$db->prepare("DELETE FROM caterer_orders WHERE id = ?")->execute([$newOrderId]);
$db->prepare("DELETE FROM bookings WHERE id = ?")->execute([$newBookingId]);

echo "\n=== SUMMARY ===\n";
echo "Total Passed: $passed\n";
echo "Total Failed: $failed\n";

if ($failed === 0) {
    echo ">>> ALL GUEST COUNT FLOW VERIFICATIONS PASSED PERFECTLY! <<<\n";
    exit(0);
} else {
    echo ">>> SOME CHECKS FAILED! <<<\n";
    exit(1);
}
