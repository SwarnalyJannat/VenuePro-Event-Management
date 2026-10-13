<?php
$php = 'C:\\xampp\\php\\php.exe';

function runStep($code) {
    global $php;
    $file = __DIR__ . '/step_tmp.php';
    file_put_contents($file, "<?php\n" . $code);
    $out = shell_exec("\"$php\" \"$file\" 2>&1");
    @unlink($file);
    return trim($out);
}

echo "=== STEP 1: Staff Sarah Jenkins (ID 3) sends a message to Customer 1 ===\n";
$step1 = runStep('
session_start();
require_once "config/database.php";
require_once "config/auth.php";
$db = getDBConnection();
$sarah = $db->query("SELECT * FROM users WHERE id = 3")->fetch(PDO::FETCH_ASSOC);
loginUser($sarah);
$_GET["action"] = "send";
$_SERVER["REQUEST_METHOD"] = "POST";
$GLOBALS["TEST_INPUT"] = ["receiver_id" => 1, "message" => "Sarah test: Stage setup is ready!"];
include "api/chat.php";
');
echo $step1 . "\n\n";

echo "=== STEP 2: Customer Mahmud (ID 1) fetches messages ===\n";
$step2 = runStep('
session_start();
require_once "config/database.php";
require_once "config/auth.php";
$db = getDBConnection();
$mahmud = $db->query("SELECT * FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
loginUser($mahmud);
$_GET["action"] = "messages";
$_GET["receiver_id"] = 3;
$_SERVER["REQUEST_METHOD"] = "GET";
include "api/chat.php";
');
echo $step2 . "\n\n";

echo "=== STEP 3: Second Staff Marcus Vance (ID 5) fetches messages with Customer 1 ===\n";
$step3 = runStep('
session_start();
require_once "config/database.php";
require_once "config/auth.php";
$db = getDBConnection();
$marcus = $db->query("SELECT * FROM users WHERE id = 5")->fetch(PDO::FETCH_ASSOC);
loginUser($marcus);
$_GET["action"] = "messages";
$_GET["receiver_id"] = 1;
$_SERVER["REQUEST_METHOD"] = "GET";
include "api/chat.php";
');
echo $step3 . "\n\n";

echo "=== STEP 4: Marcus Vance (ID 5) sends reply to Customer 1 ===\n";
$step4 = runStep('
session_start();
require_once "config/database.php";
require_once "config/auth.php";
$db = getDBConnection();
$marcus = $db->query("SELECT * FROM users WHERE id = 5")->fetch(PDO::FETCH_ASSOC);
loginUser($marcus);
$_GET["action"] = "send";
$_SERVER["REQUEST_METHOD"] = "POST";
$GLOBALS["TEST_INPUT"] = ["receiver_id" => 1, "message" => "Marcus test: Bose sound rig acoustics verified!"];
include "api/chat.php";
');
echo $step4 . "\n\n";

echo "=== STEP 5: Customer Mahmud (ID 1) fetches messages (verifies Marcus reply visible) ===\n";
$step5 = runStep('
session_start();
require_once "config/database.php";
require_once "config/auth.php";
$db = getDBConnection();
$mahmud = $db->query("SELECT * FROM users WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
loginUser($mahmud);
$_GET["action"] = "messages";
$_GET["receiver_id"] = 3;
$_SERVER["REQUEST_METHOD"] = "GET";
include "api/chat.php";
');
echo $step5 . "\n\n";

// Cleanup test messages
$clean = runStep('
require_once "config/database.php";
$db = getDBConnection();
$db->exec("DELETE FROM chat_messages WHERE message LIKE \'%Sarah test:%\' OR message LIKE \'%Marcus test:%\'");
$db->exec("DELETE FROM notifications WHERE message LIKE \'%Sarah test:%\' OR message LIKE \'%Marcus test:%\'");
echo "Cleaned up test entries.\n";
');
echo $clean . "\n";
