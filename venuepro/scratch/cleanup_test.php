<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("DELETE FROM staff_assignments WHERE booking_id IN (SELECT id FROM bookings WHERE booking_code LIKE 'TEST-%')");
$db->exec("DELETE FROM caterer_orders WHERE booking_id IN (SELECT id FROM bookings WHERE booking_code LIKE 'TEST-%')");
$db->exec("DELETE FROM bookings WHERE booking_code LIKE 'TEST-%'");
echo "Cleaned up test records\n";
