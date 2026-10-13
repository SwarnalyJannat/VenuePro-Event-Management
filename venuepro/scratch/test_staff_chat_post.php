<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDBConnection();
$staff = $db->query("SELECT * FROM users WHERE role = 'staff' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
loginUser($staff);

echo "Session set for staff ID: " . $_SESSION['user_id'] . "\n";

// Let's simulate calling api/chat.php?action=send
$_GET['action'] = 'send';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Let's test with php://input simulation
$GLOBALS['TEST_INPUT'] = [
    'receiver_id' => 1,
    'message' => 'Staff replying back to customer'
];

ob_start();
include __DIR__ . '/../api/chat.php';
$output = ob_get_clean();

echo "Response from api/chat.php: " . $output . "\n";
