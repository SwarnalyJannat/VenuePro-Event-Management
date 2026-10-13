<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$admin = $db->query("SELECT * FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

session_start();
$_SESSION['user_id'] = $admin['id'];
$_SESSION['user_name'] = $admin['name'];
$_SESSION['user_email'] = $admin['email'];
$_SESSION['user_role'] = 'admin';
$_SERVER['REQUEST_URI'] = '/venuepro/admin/admin-dashboard.php';
$_SERVER['SCRIPT_NAME'] = '/venuepro/admin/admin-dashboard.php';

ob_start();
include __DIR__ . '/../admin/admin-dashboard.php';
$html = ob_get_clean();

// Extract Revenue Growth Trend card
$start = strpos($html, 'Revenue Growth Trend');
if ($start !== false) {
    $card = substr($html, $start - 100, 1500);
    echo $card;
} else {
    echo "NOT FOUND!";
}
