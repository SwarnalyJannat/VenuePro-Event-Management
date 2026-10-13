<?php
$files = glob(__DIR__ . '/../admin/*.php');
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (strpos($content, 'sidebar-nav') !== false) {
        $hasStaff = strpos($content, 'admin-staff-management.php') !== false ? 'YES' : 'NO';
        $hasReports = strpos($content, 'admin-reports.php') !== false ? 'YES' : 'NO';
        $hasUserGov = strpos($content, 'admin-user-management.php') !== false ? 'YES' : 'NO';
        echo basename($f) . ": staff=$hasStaff, reports=$hasReports, userGov=$hasUserGov\n";
    }
}
