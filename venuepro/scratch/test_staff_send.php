<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDBConnection();
// Log in as staff (Sarah Jenkins, id 3)
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'staff' LIMIT 1");
$stmt->execute();
$staff = $stmt->fetch();
loginUser($staff);

echo "Logged in as: " . $staff['name'] . " (ID: " . $staff['id'] . ", Role: " . $staff['role'] . ")\n";

// Now test calling api/chat.php?action=send
$_GET['action'] = 'send';
$_SERVER['REQUEST_METHOD'] = 'POST';

// We simulate getJsonInput with php://input or test directly
$testPayload = json_encode([
    'receiver_id' => 1,
    'message' => 'Test message from staff to customer'
]);

// Let's test what api/chat.php does
ob_start();
// simulate json input
$GLOBALS['test_json'] = $testPayload;
// Let's inspect getJsonInput() in config/helpers.php
require_once __DIR__ . '/../config/helpers.php';
echo "helpers loaded.\n";
