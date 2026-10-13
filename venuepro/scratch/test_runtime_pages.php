<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Fetch staff and customer users
$staff = $db->query("SELECT * FROM users WHERE role = 'staff' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$customer = $db->query("SELECT * FROM users WHERE role = 'customer' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

function testPage($pageName, $user, $getParams = []) {
    $_SESSION = [];
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user'] = $user;
    $_GET = $getParams;

    ob_start();
    try {
        include __DIR__ . '/../' . $pageName;
        $output = ob_get_clean();
        $hasError = (stripos($output, 'Fatal error') !== false || stripos($output, 'Uncaught PDOException') !== false || stripos($output, 'Parse error') !== false);
        if ($hasError) {
            echo "❌ ERROR in {$pageName}:\n" . substr($output, 0, 500) . "\n";
            return false;
        } else {
            echo "✅ SUCCESS: {$pageName} rendered cleanly (" . strlen($output) . " bytes)\n";
            return true;
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "❌ EXCEPTION in {$pageName}: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "=== TESTING STAFF PAGES ===\n";
testPage('staff/staff-dashboard.php', $staff);
testPage('staff/staff-event-setup.php', $staff, ['booking_id' => 1]);

echo "\n=== TESTING CUSTOMER PAGES ===\n";
testPage('customer/customer-live-progress.php', $customer, ['booking_id' => 1]);
testPage('customer/booking-status-timeline.php', $customer, ['booking_id' => 1]);
testPage('customer/customer-dashboard.php', $customer);
