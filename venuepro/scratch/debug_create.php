<?php
$code = '<?php '
      . 'session_start(); '
      . '$_SESSION["user_role"] = "customer"; '
      . '$_SESSION["user_id"] = 1; '
      . '$_GET = ["action" => "create"]; '
      . '$_POST = [
            "venue_id" => 1,
            "package_id" => 1,
            "event_name" => "Test Event",
            "event_date" => "2026-10-25",
            "start_time" => "17:00",
            "end_time" => "23:00",
            "guest_count" => 100,
            "card_number" => "4242424242424242",
            "card_expiry" => "12/28",
            "card_cvv" => "123"
        ]; '
      . 'require "c:/xampp/htdocs/venuepro/api/bookings.php"; ';

$tmpFile = __DIR__ . '/test_create_debug.php';
file_put_contents($tmpFile, $code);
$out = shell_exec('C:\\xampp\\php\\php.exe ' . escapeshellarg($tmpFile));
@unlink($tmpFile);
echo "OUTPUT WAS:\n" . $out . "\n";
