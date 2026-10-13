<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
$db = getDBConnection();

echo "========================================================\n";
echo "TESTING MODIFIED PAGES RUNTIME RENDERING\n";
echo "========================================================\n\n";

// 1. Test customer/booking-summary-payment.php
echo "1. Testing customer/booking-summary-payment.php...\n";
$_SESSION['user_id'] = 1; // Customer Alex Thompson
$_SESSION['user_role'] = 'customer';
$_GET['venue_id'] = 1;
$_GET['date'] = '2026-11-25';
$_GET['start_time'] = '14:00';
$_GET['end_time'] = '20:00';
ob_start();
include __DIR__ . '/../customer/booking-summary-payment.php';
$outPayment = ob_get_clean();

$hasAddon1 = strpos($outPayment, 'Grilled Tiger Shrimp Skewers') !== false;
$hasAddon8 = strpos($outPayment, 'Handcrafted French Macarons') !== false;
$hasDynamicDate = strpos($outPayment, 'November 25, 2026') !== false;
$hasTime = strpos($outPayment, '14:00 - 20:00') !== false;

echo "  - Contains DB Add-on Item 1 (Shrimp): " . ($hasAddon1 ? "PASS" : "FAIL") . "\n";
echo "  - Contains DB Add-on Item 8 (Macarons): " . ($hasAddon8 ? "PASS" : "FAIL") . "\n";
echo "  - Contains dynamic date (November 25, 2026): " . ($hasDynamicDate ? "PASS" : "FAIL") . "\n";
echo "  - Contains dynamic time (14:00 - 20:00): " . ($hasTime ? "PASS" : "FAIL") . "\n";

// 2. Test customer/book-venue.php
echo "\n2. Testing customer/book-venue.php...\n";
$_GET['venue_id'] = 2; // Skyline Vista Lounge
$_GET['date'] = '2026-12-05';
ob_start();
include __DIR__ . '/../customer/book-venue.php';
$outBookVenue = ob_get_clean();

$hasVenueName = strpos($outBookVenue, 'Skyline Vista Lounge') !== false;
$hasVenueDate = strpos($outBookVenue, 'Dec 5, 2026') !== false;
$hasDynamicPkg = strpos($outBookVenue, 'Gold Luncheon &amp; Seminar Package') !== false && strpos($outBookVenue, 'Platinum Evening Gala Package') !== false;

echo "  - Contains dynamic venue name: " . ($hasVenueName ? "PASS" : "FAIL") . "\n";
echo "  - Contains dynamic event date: " . ($hasVenueDate ? "PASS" : "FAIL") . "\n";
echo "  - Contains DB catering packages: " . ($hasDynamicPkg ? "PASS" : "FAIL") . "\n";

// 3. Test customer/venue-selection.php
echo "\n3. Testing customer/venue-selection.php...\n";
ob_start();
include __DIR__ . '/../customer/venue-selection.php';
$outVenueSel = ob_get_clean();

$hasAllVenues = strpos($outVenueSel, 'Grand Emerald Ballroom') !== false && strpos($outVenueSel, 'Skyline Vista Lounge') !== false && strpos($outVenueSel, 'The Brick &amp; Steel Gallery') !== false && strpos($outVenueSel, 'Waterfront Glasshouse') !== false;
$hasDynamicLinks = strpos($outVenueSel, 'booking-date-guests.php?venue_id=1') !== false;

echo "  - Renders all active database venues: " . ($hasAllVenues ? "PASS" : "FAIL") . "\n";
echo "  - Links to booking-date-guests with venue_id: " . ($hasDynamicLinks ? "PASS" : "FAIL") . "\n";

// 4. Test packages.php (Guest mode)
echo "\n4. Testing packages.php...\n";
unset($_SESSION['user_id']);
unset($_SESSION['user_role']);
ob_start();
include __DIR__ . '/../packages.php';
$outPackages = ob_get_clean();

$hasGold = strpos($outPackages, 'Gold Luncheon &amp; Seminar Package') !== false;
$hasPlat = strpos($outPackages, 'Platinum Evening Gala Package') !== false;
$hasBespoke = strpos($outPackages, 'Bespoke Custom Hybrid Package') !== false || strpos($outPackages, 'Custom Package') !== false;

echo "  - Renders DB Gold package: " . ($hasGold ? "PASS" : "FAIL") . "\n";
echo "  - Renders DB Platinum package: " . ($hasPlat ? "PASS" : "FAIL") . "\n";
echo "  - Renders 4th Bespoke Custom card: " . ($hasBespoke ? "PASS" : "FAIL") . "\n";

// 5. Test caterer/caterer-dashboard.php
echo "\n5. Testing caterer/caterer-dashboard.php...\n";
$_SESSION['user_id'] = 2; // Caterer
$_SESSION['user_role'] = 'caterer';
ob_start();
include __DIR__ . '/../caterer/caterer-dashboard.php';
$outCatDash = ob_get_clean();

$hasCompletedOrders = strpos($outCatDash, 'Completed Orders') !== false;
$noStaticDash = strpos($outCatDash, '<div class="stat-value">—</div>') === false;

echo "  - Dynamic Completed Orders label: " . ($hasCompletedOrders ? "PASS" : "FAIL") . "\n";
echo "  - Static dash eliminated: " . ($noStaticDash ? "PASS" : "FAIL") . "\n";

// 6. Test admin/venue-management.php
echo "\n6. Testing admin/venue-management.php...\n";
$_SESSION['user_id'] = 4; // Admin
$_SESSION['user_role'] = 'admin';
ob_start();
include __DIR__ . '/../admin/venue-management.php';
$outAdminVenues = ob_get_clean();

$hasLiveOcc = strpos($outAdminVenues, 'Average Occupancy') !== false && strpos($outAdminVenues, '74.5%') === false;
echo "  - Static 74.5% eliminated & dynamic occupancy active: " . ($hasLiveOcc ? "PASS" : "FAIL") . "\n";

echo "\nALL RUNTIME RENDERING CHECKS COMPLETED.\n";
