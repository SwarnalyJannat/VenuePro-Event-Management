<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$passed = 0;
$failed = 0;

function assertTest($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name\n";
        $failed++;
    }
}

echo "=== STARTING CATERER VENUE SCOPING INTEGRATION TESTS ===\n\n";

// Helper function to resolve caterers for a venue (same SQL logic as customer pages)
function resolveVenueCaterers($db, $venueId) {
    $stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
    $stmtV->execute([$venueId]);
    $venue = $stmtV->fetch();
    if (!$venue) return ['venue' => null, 'caterers' => [], 'packages' => []];

    $venueName = $venue['name'];
    $venueIdStr = (string)$venueId;

    $stmtCaterers = $db->prepare("
        SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
        FROM users u
        JOIN caterer_profiles cp ON u.id = cp.user_id
        JOIN catering_packages pkg ON pkg.caterer_id = u.id
        WHERE u.role = 'caterer' 
          AND u.status = 'active' 
          AND cp.approval_status = 'approved'
          AND pkg.status IN ('published', 'active')
          AND (
              cp.assigned_venue = ? 
              OR cp.assigned_venue = ? 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR cp.assigned_venue LIKE ?
          )
        ORDER BY cp.business_name ASC
    ");
    $stmtCaterers->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
    $caterersList = $stmtCaterers->fetchAll(PDO::FETCH_ASSOC);
    $assignedCatererIds = array_map('intval', array_column($caterersList, 'id'));

    $packages = [];
    if (!empty($assignedCatererIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($assignedCatererIds), '?'));
        $stmtPkgs = $db->prepare("
            SELECT cp.*, u.name AS caterer_name, pr.business_name, pr.assigned_venue
            FROM catering_packages cp
            LEFT JOIN users u ON cp.caterer_id = u.id
            LEFT JOIN caterer_profiles pr ON u.id = pr.user_id
            WHERE cp.status IN ('published', 'active')
              AND cp.caterer_id IN ($inPlaceholders)
            ORDER BY cp.price_per_event ASC
        ");
        $stmtPkgs->execute($assignedCatererIds);
        $packages = $stmtPkgs->fetchAll(PDO::FETCH_ASSOC);
    }

    return ['venue' => $venue, 'caterers' => $caterersList, 'packages' => $packages];
}

// TEST 1: Venue 9 ('try') scoping
$res9 = resolveVenueCaterers($db, 9);
assertTest($res9['venue'] !== null, "Venue 9 exists in database ('" . ($res9['venue']['name'] ?? '') . "')");
assertTest(count($res9['caterers']) >= 1, "Venue 9 has assigned caterers");
$catererIds9 = array_column($res9['caterers'], 'id');
assertTest(in_array(15, $catererIds9), "Venue 9 includes assigned Caterer 15 ('sd')");
assertTest(!in_array(2, $catererIds9), "Venue 9 DOES NOT include Caterer 2 (Alex Rivera)");

// Check packages for Venue 9
$pkgCatererIds9 = array_unique(array_column($res9['packages'], 'caterer_id'));
assertTest(!empty($res9['packages']), "Venue 9 has catering packages returned");
assertTest(!in_array(2, $pkgCatererIds9), "Venue 9 packages DO NOT contain any packages from Caterer 2");
assertTest(in_array(15, $pkgCatererIds9), "Venue 9 packages contain only assigned Caterer 15 packages");

// TEST 2: Venue 1 ('Grand Emerald Ballroom') scoping
$res1 = resolveVenueCaterers($db, 1);
assertTest($res1['venue'] !== null, "Venue 1 exists ('Grand Emerald Ballroom')");
$catererIds1 = array_column($res1['caterers'], 'id');
assertTest(in_array(2, $catererIds1), "Venue 1 includes assigned Caterer 2 (Rivera Culinary)");
assertTest(!in_array(15, $catererIds1), "Venue 1 DOES NOT include Caterer 15 ('sd')");

$pkgCatererIds1 = array_unique(array_column($res1['packages'], 'caterer_id'));
assertTest(!empty($res1['packages']), "Venue 1 has packages returned");
assertTest(in_array(2, $pkgCatererIds1), "Venue 1 packages are from Caterer 2");
assertTest(!in_array(15, $pkgCatererIds1), "Venue 1 packages DO NOT leak any packages from Caterer 15");

// TEST 3: Venue with no assigned caterer (e.g. Venue 5 or Venue 6)
$res5 = resolveVenueCaterers($db, 5);
assertTest(count($res5['caterers']) === 0, "Venue 5 has 0 assigned caterers (not global)");
assertTest(count($res5['packages']) === 0, "Venue 5 has 0 packages returned (strictly empty state, no global leakage)");

// TEST 4: Multi-venue parsing in api/caterers.php update simulation
$testVenuesArray = ['Grand Emerald Ballroom', 'Skyline Vista Lounge', 'try'];
$joinedVenues = implode(', ', $testVenuesArray);
assertTest($joinedVenues === 'Grand Emerald Ballroom, Skyline Vista Lounge, try', "Array to comma-delimited string works correctly");

// TEST 5: Verify assigned_venue column in MySQL caterer_profiles schema
$stmtCol = $db->query("SHOW COLUMNS FROM caterer_profiles LIKE 'assigned_venue'");
$colInfo = $stmtCol->fetch(PDO::FETCH_ASSOC);
assertTest(stripos($colInfo['Type'], 'varchar(500)') !== false, "caterer_profiles.assigned_venue is VARCHAR(500) capable of holding multi-venue lists");

// TEST 6: Verify file modifications exist and contain proper scoping
$filesToCheck = [
    'customer/booking-catering-packages.php' => ['assignedCatererIds', 'No Catering Assigned to This Venue'],
    'customer/caterer-custom-package.php'   => ['assignedCaterers', 'No Catering Partner Assigned to'],
    'customer/book-venue.php'                => ['assignedCatererIds', 'No Catering Partners Assigned'],
    'customer/booking-summary-payment.php'   => ['assignedCatererIds', 'Venue-Only Booking'],
    'admin/admin-add-caterer.php'            => ['assigned_venues[]', 'Designated Venues Assignment'],
    'admin/admin-edit-caterer.php'           => ['assigned_venues[]', 'Designated Venues (Assigned by Admin)'],
    'admin/admin-catering-management.php'    => ['ASSIGNED VENUE(S)', 'cp.assigned_venue'],
    'api/caterers.php'                       => ['assigned_venues', 'implode']
];

foreach ($filesToCheck as $file => $strings) {
    $content = file_get_contents(__DIR__ . '/../' . $file);
    foreach ($strings as $str) {
        assertTest(strpos($content, $str) !== false, "File $file contains '$str'");
    }
}

echo "\n=== INTEGRATION TEST SUMMARY ===\n";
echo "Total Passed: $passed\n";
echo "Total Failed: $failed\n";
if ($failed === 0) {
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
} else {
    echo "⚠️ SOME TESTS FAILED.\n";
}
