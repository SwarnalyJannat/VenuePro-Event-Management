<?php
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();
$passed = 0;
$failed = 0;

function assertTest($cond, $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $msg\n";
        $passed++;
    } else {
        echo "  [FAIL] $msg\n";
        $failed++;
    }
}

echo "=== 1. PHP Syntax & Lint Check ===\n";
$files = [
    'api/singular-items.php',
    'api/caterer-orders.php',
    'api/bookings.php',
    'caterer/caterer-menu-items.php',
    'caterer/caterer-order-details.php',
    'customer/booking-summary-payment.php',
    'database/seed_demos.php'
];

foreach ($files as $f) {
    $full = __DIR__ . '/../' . $f;
    $out = shell_exec("C:\\xampp\\php\\php.exe -l " . escapeshellarg($full));
    assertTest(strpos($out, 'No syntax errors detected') !== false, "Lint: $f");
}

echo "\n=== 2. Asset Images Check ===\n";
$expectedImages = [
    'dish-shrimp.jpg', 'drink-coke.jpg', 'dish-yogurt.jpg', 'dish-dessert.jpg',
    'drink-water.jpg', 'dish-bruschetta.jpg', 'dish-cheese.jpg', 'dish-macaron.jpg'
];
foreach ($expectedImages as $img) {
    $path = __DIR__ . '/../assets/' . $img;
    assertTest(file_exists($path), "Image asset exists: assets/$img (" . (file_exists($path) ? filesize($path) : 0) . " bytes)");
}

echo "\n=== 3. Database Schema Check ===\n";
$colSmi = $db->query("SHOW COLUMNS FROM singular_menu_items LIKE 'image_url'")->fetch();
assertTest(!empty($colSmi), "singular_menu_items has image_url column");
$colBsi = $db->query("SHOW COLUMNS FROM booking_singular_items LIKE 'image_url'")->fetch();
assertTest(!empty($colBsi), "booking_singular_items has image_url column");

echo "\n=== 4. Singular Menu Item CRUD with Image URL ===\n";
// Clean old test item if exists
$db->query("DELETE FROM singular_menu_items WHERE item_key LIKE 'test-caviar-%'");

$testKey = 'test-caviar-' . rand(1000, 9999);
$testImgUrl = 'https://example.com/caviar.jpg';
$stmtIns = $db->prepare("
    INSERT INTO singular_menu_items (caterer_id, name, item_key, category, emoji, image_url, price, unit_label, quantity_available, min_order_qty, description, dietary_tags, status)
    VALUES (2, 'Royal Beluga Caviar Tartlets', ?, 'Appetizer', '🐟', ?, 45.00, 'piece', 5, 1, 'Served on crisp buckwheat blinis with creme fraiche.', '[\"GF\"]', 'active')
");
$stmtIns->execute([$testKey, $testImgUrl]);
$newMenuId = (int)$db->lastInsertId();
assertTest($newMenuId > 0, "Inserted test singular menu item with ID $newMenuId");

$fetched = $db->query("SELECT * FROM singular_menu_items WHERE id = $newMenuId")->fetch();
assertTest($fetched['image_url'] === $testImgUrl, "image_url correctly stored in singular_menu_items ($testImgUrl)");
assertTest((int)$fetched['quantity_available'] === 5, "quantity_available is initially 5");

echo "\n=== 5. Stock Check Enforcement When Order Quantity Exceeds Stock ===\n";
// Clean any existing test booking
$db->query("DELETE FROM bookings WHERE booking_code = 'BK-TEST-STK'");

// Create a test booking and booking_singular_items with quantity = 25 (exceeds stock of 5)
$stmtBk = $db->prepare("
    INSERT INTO bookings (booking_code, customer_id, venue_id, package_id, event_name, event_date, start_time, end_time, guest_count, duration_hours, venue_cost, package_cost, staffing_cost, addons_cost, subtotal, service_fee, tax_vat, total_amount, payment_status, payment_card_last4, booking_status, caterer_status, special_notes)
    VALUES ('BK-TEST-STK', 1, 1, 1, 'Stock Check Event', '2026-11-25', '18:00:00', '22:00:00', 50, 4, 1000, 500, 300, 1125, 2925, 292.5, 234, 3451.5, 'paid', '4242', 'confirmed', 'pending', 'Stock test')
");
$stmtBk->execute();
$testBookingId = (int)$db->lastInsertId();

$stmtCo = $db->prepare("
    INSERT INTO caterer_orders (caterer_id, booking_id, order_code, covers_count, preparation_status, package_decision)
    VALUES (2, ?, 'ORD-STK-01', 50, 'Preparing', 'Pending')
");
$stmtCo->execute([$testBookingId]);
$testOrderId = (int)$db->lastInsertId();

$stmtBsi = $db->prepare("
    INSERT INTO booking_singular_items (booking_id, singular_item_id, item_name, emoji, image_url, unit_price, quantity, subtotal, item_status, notes)
    VALUES (?, ?, 'Royal Beluga Caviar Tartlets', '🐟', ?, 45.00, 25, 1125.00, 'Pending', 'VIP tables')
");
$stmtBsi->execute([$testBookingId, $newMenuId, $testImgUrl]);
$testBsiId = (int)$db->lastInsertId();

// Verify bsi has image_url
$bsiRow = $db->query("SELECT * FROM booking_singular_items WHERE id = $testBsiId")->fetch();
assertTest($bsiRow['image_url'] === $testImgUrl, "booking_singular_items stored image_url ($testImgUrl)");
assertTest((int)$bsiRow['quantity'] === 25, "booking_singular_items quantity is 25");

// Check ordered vs stock
$stmtQty = $db->prepare("SELECT quantity, singular_item_id FROM booking_singular_items WHERE id = ?");
$stmtQty->execute([$testBsiId]);
$checkRow = $stmtQty->fetch();
$orderedQty = (int)$checkRow['quantity'];
$stockAvailable = (int)$db->query("SELECT quantity_available FROM singular_menu_items WHERE id = {$checkRow['singular_item_id']}")->fetchColumn();

assertTest($orderedQty > $stockAvailable, "Ordered qty ($orderedQty) strictly exceeds stock ($stockAvailable)");

// Invoke API in isolated CLI execution to test decide_singular_item when stock is insufficient
$runner1 = __DIR__ . '/run_api_decide_item.php';
file_put_contents($runner1, '<?php
session_start();
$_SESSION["user_id"] = 2;
$_SESSION["user_role"] = "caterer";
$_GET["action"] = "decide_singular_item";
$_POST = ["item_id" => ' . $testBsiId . ', "decision" => "Accepted"];
chdir(__DIR__ . "/..");
include "api/caterer-orders.php";
');

$rawResp1 = shell_exec("C:\\xampp\\php\\php.exe " . escapeshellarg($runner1));
unlink($runner1);
$respData1 = json_decode($rawResp1, true);

assertTest(
    !empty($respData1['stock_insufficient']) && $respData1['success'] === false,
    "API correctly blocked acceptance due to insufficient stock (Message: {$respData1['message']})"
);

// Verify item_status is still 'Pending' in DB
$statusAfterBlocked = $db->query("SELECT item_status FROM booking_singular_items WHERE id = $testBsiId")->fetchColumn();
assertTest($statusAfterBlocked === 'Pending', "Item status remains 'Pending' in DB after blocked acceptance");

echo "\n=== 6. Acceptance Allowed After Increasing Stock ===\n";
// Caterer increases stock from 5 to 50 (exceeding requested 25)
$db->query("UPDATE singular_menu_items SET quantity_available = 50 WHERE id = $newMenuId");
$newStock = (int)$db->query("SELECT quantity_available FROM singular_menu_items WHERE id = $newMenuId")->fetchColumn();
assertTest($newStock === 50, "Caterer updated quantity_available to 50");

// Now try accepting again
$runner2 = __DIR__ . '/run_api_decide_item2.php';
file_put_contents($runner2, '<?php
session_start();
$_SESSION["user_id"] = 2;
$_SESSION["user_role"] = "caterer";
$_GET["action"] = "decide_singular_item";
$_POST = ["item_id" => ' . $testBsiId . ', "decision" => "Accepted"];
chdir(__DIR__ . "/..");
include "api/caterer-orders.php";
');

$rawResp2 = shell_exec("C:\\xampp\\php\\php.exe " . escapeshellarg($runner2));
unlink($runner2);
$respData2 = json_decode($rawResp2, true);

assertTest(
    !empty($respData2['success']) && $respData2['success'] === true,
    "API allowed acceptance after increasing stock (Message: {$respData2['message']})"
);

$statusAfterAccepted = $db->query("SELECT item_status FROM booking_singular_items WHERE id = $testBsiId")->fetchColumn();
assertTest($statusAfterAccepted === 'Accepted', "Item status successfully updated to 'Accepted' in DB");

echo "\n=== 7. Bulk 'Accept All' Stock Check Verification ===\n";
// Create another pending item where ordered quantity exceeds available stock
$stmtIns2 = $db->prepare("
    INSERT INTO singular_menu_items (caterer_id, name, item_key, category, emoji, image_url, price, unit_label, quantity_available, min_order_qty, description, dietary_tags, status)
    VALUES (2, 'Kobe Beef Sliders', 'kobe-sliders-test', 'Appetizer', '🍔', 'https://example.com/kobe.jpg', 22.00, 'piece', 2, 1, 'Mini Wagyu sliders', '[]', 'active')
");
$stmtIns2->execute();
$kobeMenuId = (int)$db->lastInsertId();

$stmtBsi2 = $db->prepare("
    INSERT INTO booking_singular_items (booking_id, singular_item_id, item_name, emoji, image_url, unit_price, quantity, subtotal, item_status, notes)
    VALUES (?, ?, 'Kobe Beef Sliders', '🍔', 'https://example.com/kobe.jpg', 22.00, 10, 220.00, 'Pending', 'Mini burgers')
");
$stmtBsi2->execute([$testBookingId, $kobeMenuId]);
$kobeBsiId = (int)$db->lastInsertId();

// Try bulk accept all
$runner3 = __DIR__ . '/run_api_decide_all.php';
file_put_contents($runner3, '<?php
session_start();
$_SESSION["user_id"] = 2;
$_SESSION["user_role"] = "caterer";
$_GET["action"] = "decide_all_singular_items";
$_POST = ["booking_id" => ' . $testBookingId . ', "decision" => "Accepted"];
chdir(__DIR__ . "/..");
include "api/caterer-orders.php";
');

$rawResp3 = shell_exec("C:\\xampp\\php\\php.exe " . escapeshellarg($runner3));
unlink($runner3);
$respData3 = json_decode($rawResp3, true);

assertTest(
    !empty($respData3['stock_insufficient']) && $respData3['success'] === false,
    "decide_all_singular_items correctly blocked bulk accept when Kobe Sliders had only 2 in stock (ordered: 10)"
);

$kobeStatus = $db->query("SELECT item_status FROM booking_singular_items WHERE id = $kobeBsiId")->fetchColumn();
assertTest($kobeStatus === 'Pending', "Kobe Beef Sliders remained 'Pending'");

// Increase stock of Kobe sliders to 30
$db->query("UPDATE singular_menu_items SET quantity_available = 30 WHERE id = $kobeMenuId");

// Run bulk accept all again
$runner4 = __DIR__ . '/run_api_decide_all2.php';
file_put_contents($runner4, '<?php
session_start();
$_SESSION["user_id"] = 2;
$_SESSION["user_role"] = "caterer";
$_GET["action"] = "decide_all_singular_items";
$_POST = ["booking_id" => ' . $testBookingId . ', "decision" => "Accepted"];
chdir(__DIR__ . "/..");
include "api/caterer-orders.php";
');

$rawResp4 = shell_exec("C:\\xampp\\php\\php.exe " . escapeshellarg($runner4));
unlink($runner4);
$respData4 = json_decode($rawResp4, true);

assertTest(
    !empty($respData4['success']) && $respData4['success'] === true,
    "decide_all_singular_items successfully accepted after stock increased"
);

$kobeStatusAfter = $db->query("SELECT item_status FROM booking_singular_items WHERE id = $kobeBsiId")->fetchColumn();
assertTest($kobeStatusAfter === 'Accepted', "Kobe Beef Sliders successfully updated to 'Accepted'");

echo "\n=== 8. Cleanup Test Records ===\n";
$db->query("DELETE FROM booking_singular_items WHERE booking_id = $testBookingId");
$db->query("DELETE FROM caterer_orders WHERE id = $testOrderId");
$db->query("DELETE FROM bookings WHERE id = $testBookingId");
$db->query("DELETE FROM singular_menu_items WHERE id IN ($newMenuId, $kobeMenuId)");
echo "  [PASS] Test records cleaned up cleanly.\n";

echo "\n=== SUMMARY ===\n";
echo "Total Passed: $passed\n";
echo "Total Failed: $failed\n";
if ($failed === 0) {
    echo ">>> ALL SINGULAR ITEM IMAGE AND STOCK VERIFICATIONS PASSED! <<<\n";
} else {
    echo ">>> SOME VERIFICATIONS FAILED! <<<\n";
    exit(1);
}
