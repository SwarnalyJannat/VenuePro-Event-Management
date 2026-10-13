<?php
$files = glob(__DIR__ . '/../admin/*.php');
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (strpos($content, 'sidebar-nav') !== false) {
        $hasUserGov = strpos($content, 'admin-user-management.php') !== false ? 'YES' : 'NO';
        echo basename($f) . ": " . $hasUserGov . "\n";
    }
}
