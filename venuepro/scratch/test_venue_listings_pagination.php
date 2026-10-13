<?php
/**
 * Test Customer Venue Listings Dynamic Pagination (5 per page)
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=======================================================\n";
echo "TEST: CUSTOMER VENUE LISTINGS DYNAMIC PAGINATION (5 PER PAGE)\n";
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

function renderVenueListings($getParams = []) {
    $script = '<?php '
            . 'session_start(); '
            . '$_SESSION["user_role"] = "customer"; '
            . '$_SESSION["user_id"] = 1; '
            . '$_GET = ' . var_export($getParams, true) . '; '
            . 'require ' . var_export(__DIR__ . '/../customer/venue-listings.php', true) . '; ';

    $tmp = __DIR__ . '/tmp_venue_test_' . uniqid() . '.php';
    file_put_contents($tmp, $script);
    $out = shell_exec('C:\\xampp\\php\\php.exe ' . escapeshellarg($tmp));
    @unlink($tmp);
    return $out;
}

// -------------------------------------------------------------
// 1. TEST PAGE 1 (DEFAULT)
// -------------------------------------------------------------
echo "1. Testing Page 1 (Default: first 5 venues)...\n";
$html1 = renderVenueListings([]);

// Verify hardcoded page 12 is gone
assertTest(strpos($html1, '>12</a>') === false, "Hardcoded page 12 completely removed");
assertTest(strpos($html1, 'href="#" class="btn btn-primary btn-sm">1</a>') === false, "No static # pagination links");

// Count rendered venue cards on page 1
preg_match_all('/class="venue-card"/', $html1, $matches1);
$cardCount1 = count($matches1[0]);
assertTest($cardCount1 === 5, "Page 1 displays exactly 5 venues (found $cardCount1)");

// Check summary counter text
assertTest(strpos($html1, 'Showing 1–5 of 7 venues') !== false, "Summary counter correctly indicates 'Showing 1–5 of 7 venues'");
assertTest(strpos($html1, 'Page 1 of 2') !== false, "Summary indicates '(Page 1 of 2)'");

// Check pagination controls on page 1
assertTest(strpos($html1, 'btn btn-primary btn-sm" style="pointer-events:none; font-weight:700;">1</span>') !== false, "Page 1 button is active");
assertTest(strpos($html1, 'venue-listings.php?page=2') !== false, "Link to Page 2 exists");
assertTest(preg_match('/<span class="btn btn-ghost btn-sm"[^>]*>‹<\/span>/', $html1) === 1, "Previous button ‹ is disabled on page 1");
assertTest(preg_match('/<a href="[^"]*page=2[^"]*" class="btn btn-ghost btn-sm" title="Next Page">›<\/a>/', $html1) === 1, "Next button › links to page 2");

// -------------------------------------------------------------
// 2. TEST PAGE 2
// -------------------------------------------------------------
echo "\n2. Testing Page 2 (Remaining 2 venues)...\n";
$html2 = renderVenueListings(['page' => 2]);

// Count rendered venue cards on page 2
preg_match_all('/class="venue-card"/', $html2, $matches2);
$cardCount2 = count($matches2[0]);
assertTest($cardCount2 === 2, "Page 2 displays remaining 2 venues (found $cardCount2)");

// Check summary counter text
assertTest(strpos($html2, 'Showing 6–7 of 7 venues') !== false, "Summary counter correctly indicates 'Showing 6–7 of 7 venues'");
assertTest(strpos($html2, 'Page 2 of 2') !== false, "Summary indicates '(Page 2 of 2)'");

// Check pagination controls on page 2
assertTest(strpos($html2, 'btn btn-primary btn-sm" style="pointer-events:none; font-weight:700;">2</span>') !== false, "Page 2 button is active");
assertTest(preg_match('/<a href="venue-listings\.php" class="btn btn-ghost btn-sm"[^>]*>‹<\/a>/', $html2) === 1, "Previous button ‹ links back to page 1");
assertTest(preg_match('/<span class="btn btn-ghost btn-sm"[^>]*>›<\/span>/', $html2) === 1, "Next button › is disabled on last page");

// -------------------------------------------------------------
// 3. TEST PARAMETER PRESERVATION ACROSS PAGINATION
// -------------------------------------------------------------
echo "\n3. Testing Parameter Preservation across Pagination...\n";
$htmlSort = renderVenueListings(['sort' => 'price_asc']);
assertTest(strpos($htmlSort, 'sort=price_asc') !== false, "Sort parameter 'sort=price_asc' is preserved in pagination links");
assertTest(strpos($htmlSort, 'venue-listings.php?page=2&amp;sort=price_asc') !== false || strpos($htmlSort, 'venue-listings.php?page=2&sort=price_asc') !== false, "Page 2 link includes preserved sort parameter");

echo "\n=======================================================\n";
echo "SUMMARY: Passed: $passCount | Failed: $failCount\n";
echo "=======================================================\n";
