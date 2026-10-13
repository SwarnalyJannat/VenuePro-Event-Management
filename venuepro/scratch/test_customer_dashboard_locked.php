<?php
session_start();
$_SESSION['user_role'] = 'customer';
$_SESSION['user_id'] = 1;
ob_start();
require __DIR__ . '/../customer/customer-dashboard.php';
$html = ob_get_clean();
if (strpos($html, 'Locked') !== false) {
    echo "FAIL: Found 'Locked' in rendered output.\n";
} else {
    echo "SUCCESS: 'Locked' is completely removed from rendered output!\n";
}
