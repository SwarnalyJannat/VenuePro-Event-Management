<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$db = getDBConnection();

echo "=== START CHAT INTEGRATION TEST ===\n";

// 1. Staff Sarah Jenkins (ID 3) logs in and sends a message to Customer 1
$sarah = $db->query("SELECT * FROM users WHERE id = 3")->fetch(PDO::FETCH_ASSOC);
loginUser($sarah);
echo "1. Logged in as Staff: {$sarah['name']} (ID: {$sarah['id']})\n";

$_GET['action'] = 'send';
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['TEST_INPUT'] = [
    'receiver_id' => 1,
    'message' => 'Sarah test: Stage setup is ready!'
];

ob_start();
include __DIR__ . '/../api/chat.php';
$resp1 = json_decode(ob_get_clean(), true);
echo "Sarah send response: " . ($resp1['success'] ? 'SUCCESS' : 'FAILED: ' . ($resp1['message'] ?? '')) . "\n";

// 2. Customer Mahmud (ID 1) logs in and fetches messages
$mahmud = $db->query("SELECT * FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
loginUser($mahmud);
echo "2. Logged in as Customer: {$mahmud['name']} (ID: {$mahmud['id']})\n";

$_GET['action'] = 'messages';
$_GET['receiver_id'] = 3;
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($GLOBALS['TEST_INPUT']);

ob_start();
include __DIR__ . '/../api/chat.php';
$resp2 = json_decode(ob_get_clean(), true);
$custMsgs = $resp2['data']['messages'] ?? [];
echo "Customer fetched " . count($custMsgs) . " messages from staff channel.\n";
$lastCustMsg = end($custMsgs);
echo "Last message seen by Customer: [{$lastCustMsg['sender_name']}]: {$lastCustMsg['message']}\n";

// 3. Different Staff Marcus Vance (ID 5) logs in
$marcus = $db->query("SELECT * FROM users WHERE id = 5")->fetch(PDO::FETCH_ASSOC);
loginUser($marcus);
echo "3. Logged in as Second Staff: {$marcus['name']} (ID: {$marcus['id']})\n";

$_GET['action'] = 'messages';
$_GET['receiver_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include __DIR__ . '/../api/chat.php';
$resp3 = json_decode(ob_get_clean(), true);
$marcusMsgs = $resp3['data']['messages'] ?? [];
echo "Marcus fetched " . count($marcusMsgs) . " messages from Customer 1 thread.\n";
$foundSarahMsg = false;
foreach ($marcusMsgs as $m) {
    if (strpos($m['message'], 'Sarah test: Stage setup is ready!') !== false) {
        $foundSarahMsg = true;
        break;
    }
}
echo "Marcus can see Sarah's message: " . ($foundSarahMsg ? 'YES (UNIFIED)' : 'NO (SILOED ERROR)') . "\n";

// 4. Marcus sends a reply to Customer 1
$_GET['action'] = 'send';
$_SERVER['REQUEST_METHOD'] = 'POST';
$GLOBALS['TEST_INPUT'] = [
    'receiver_id' => 1,
    'message' => 'Marcus test: Audio acoustics soundcheck complete!'
];

ob_start();
include __DIR__ . '/../api/chat.php';
$resp4 = json_decode(ob_get_clean(), true);
echo "Marcus reply response: " . ($resp4['success'] ? 'SUCCESS' : 'FAILED') . "\n";

// 5. Customer fetches messages again - checks if Marcus's message is visible
loginUser($mahmud);
$_GET['action'] = 'messages';
$_GET['receiver_id'] = 3; // Even if looking at Sarah (or default staff)
$_SERVER['REQUEST_METHOD'] = 'GET';
unset($GLOBALS['TEST_INPUT']);

ob_start();
include __DIR__ . '/../api/chat.php';
$resp5 = json_decode(ob_get_clean(), true);
$custMsgsUpdated = $resp5['data']['messages'] ?? [];
$foundMarcusMsg = false;
foreach ($custMsgsUpdated as $m) {
    if (strpos($m['message'], 'Marcus test: Audio acoustics soundcheck complete!') !== false) {
        $foundMarcusMsg = true;
        break;
    }
}
echo "Customer can see Marcus's reply: " . ($foundMarcusMsg ? 'YES (UNIFIED)' : 'NO (BUG)') . "\n";

// 6. Check notifications table
$notifs = $db->query("SELECT * FROM notifications WHERE type = 'chat' ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
echo "Recent chat notifications in DB: " . count($notifs) . "\n";
foreach ($notifs as $n) {
    echo "- For user {$n['user_id']}: {$n['title']} -> {$n['message']}\n";
}

// Clean up test messages and test notifications
$db->exec("DELETE FROM chat_messages WHERE message LIKE '%Sarah test:%' OR message LIKE '%Marcus test:%'");
$db->exec("DELETE FROM notifications WHERE message LIKE '%Sarah test:%' OR message LIKE '%Marcus test:%'");
echo "Cleaned up test data.\n";
echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
