<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

$db = getDBConnection();
$staff = $db->query("SELECT * FROM users WHERE role = 'staff' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
loginUser($staff);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/venuepro/staff/staff-chat.php';
$_SERVER['REQUEST_URI'] = '/venuepro/staff/staff-chat.php';

ob_start();
include __DIR__ . '/../staff/staff-chat.php';
$html = ob_get_clean();

echo "HTML Length: " . strlen($html) . "\n";
// Let's check for errors or warnings in output
if (strpos($html, 'Fatal error') !== false || strpos($html, 'Warning:') !== false || strpos($html, 'Notice:') !== false) {
    echo "Found errors/warnings in HTML:\n";
    $lines = explode("\n", $html);
    foreach ($lines as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'warning') !== false) {
            echo "  $line\n";
        }
    }
} else {
    echo "No PHP errors or warnings rendered in staff-chat.php.\n";
}
