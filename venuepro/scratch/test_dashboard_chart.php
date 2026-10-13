<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$adminUser = $db->query("SELECT id, name, email, role FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch();

if (!$adminUser) {
    die("No active admin user found in database!\n");
}

$_SESSION['user_id'] = $adminUser['id'];
$_SESSION['user_role'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['user_name'] = $adminUser['name'];
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
require __DIR__ . '/../admin/admin-dashboard.php';
$html = ob_get_clean();

echo "Dashboard rendered successfully. Length: " . strlen($html) . " bytes\n";

if (strpos($html, 'id="trendChartWrap"') !== false) {
    echo "✓ Found trendChartWrap container\n";
} else {
    echo "✗ Missing trendChartWrap container\n";
}

if (strpos($html, 'class="bar-track"') !== false) {
    echo "✓ Found bar-track elements\n";
} else {
    echo "✗ Missing bar-track elements\n";
}

preg_match_all('/<div class="bar-col">[\s\S]*?<span class="bar-val"[^>]*>([^<]+)<\/span>[\s\S]*?<div class="bar-track">[\s\S]*?<div class="([^"]+)" style="height:([^;]+);"[^>]*><\/div>[\s\S]*?<\/div>[\s\S]*?<span class="bar-label[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $matches, PREG_SET_ORDER);

echo "Found " . count($matches) . " rendered bars in chart:\n";
foreach ($matches as $bar) {
    echo "  - Month: {$bar[4]} | Val: {$bar[1]} | Height: {$bar[3]} | Class: {$bar[2]}\n";
}

if (strpos($html, 'function switchTrendPeriod') !== false) {
    echo "✓ Found switchTrendPeriod JavaScript function for live period switching\n";
} else {
    echo "✗ Missing switchTrendPeriod function\n";
}
