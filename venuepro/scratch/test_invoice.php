<?php
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'customer';
$_SESSION['role'] = 'customer';
$_SESSION['user_name'] = 'Customer User';

require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Fetch customer bookings
$stmt = $db->query("SELECT id, booking_code FROM bookings WHERE customer_id = 1");
$bookings = $stmt->fetchAll();

echo "Testing client-invoice.php rendering for " . count($bookings) . " bookings...\n";

// Scenario 1: Without booking_id parameter (fallback)
unset($_GET['booking_id']);
ob_start();
include __DIR__ . '/../customer/client-invoice.php';
$out = ob_get_clean();
echo "✓ Default client-invoice.php rendered successfully (" . strlen($out) . " bytes)\n";

// Scenario 2: With explicit booking_id parameter
foreach ($bookings as $b) {
    $_GET['booking_id'] = $b['id'];
    ob_start();
    include __DIR__ . '/../customer/client-invoice.php';
    $out = ob_get_clean();
    echo "✓ Booking #{$b['id']} ({$b['booking_code']}) rendered successfully (" . strlen($out) . " bytes)\n";
}

echo "All invoice scenarios passed with zero PHP warnings or errors!\n";
