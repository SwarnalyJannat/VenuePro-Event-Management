<?php
$res = @file_get_contents('http://localhost/venuepro/api/bookings.php?action=my_bookings');
echo "HTTP response: " . substr((string)$res, 0, 100) . "\n";
