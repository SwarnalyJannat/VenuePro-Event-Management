<?php
// Test verification for removal of red circular badges
$files = [
    'customer/customer-live-progress.php',
    'customer/booking-status-timeline.php',
    'customer/customer-dashboard.php'
];

$errors = [];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (!file_exists($fullPath)) {
        $errors[] = "File not found: $file";
        continue;
    }
    // Lint check
    $cmd = "C:\\xampp\\php\\php.exe -l " . escapeshellarg($fullPath);
    $out = shell_exec($cmd);
    if (strpos($out, 'No syntax errors detected') === false) {
        $errors[] = "Syntax error in $file: $out";
    }
}

// Check customer-live-progress.php content
$liveProg = file_get_contents(__DIR__ . '/../customer/customer-live-progress.php');
if (strpos($liveProg, "step['badge']") !== false) {
    $errors[] = "customer-live-progress.php still contains step['badge']";
}
if (strpos($liveProg, "step.badge") !== false) {
    $errors[] = "customer-live-progress.php still contains step.badge";
}
if (strpos($liveProg, 'id="lp-live-badge" class="badge"') !== false) {
    $errors[] = "customer-live-progress.php still has class=\"badge\" on lp-live-badge";
}

// Check booking-status-timeline.php content
$bktl = file_get_contents(__DIR__ . '/../customer/booking-status-timeline.php');
if (strpos($bktl, "step['badge']") !== false) {
    $errors[] = "booking-status-timeline.php still contains step['badge']";
}
if (strpos($bktl, "step.badge") !== false) {
    $errors[] = "booking-status-timeline.php still contains step.badge";
}
if (strpos($bktl, 'class="badge" style="background:#ecfdf5') !== false) {
    $errors[] = "booking-status-timeline.php still has class=\"badge\" on live sync pill";
}

// Check for duplicate script in customer-dashboard.php
$dash = file_get_contents(__DIR__ . '/../customer/customer-dashboard.php');
if (substr_count($dash, 'async function cancelBooking') > 1) {
    $errors[] = "customer-dashboard.php still has duplicate cancelBooking function";
}

if (empty($errors)) {
    echo "SUCCESS: All badge removal verification checks passed! No red circular badge texts remain.\n";
} else {
    echo "FAILURES:\n" . implode("\n", $errors) . "\n";
    exit(1);
}
