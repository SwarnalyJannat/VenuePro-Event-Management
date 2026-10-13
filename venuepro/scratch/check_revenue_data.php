<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== BOOKINGS DATES & AMOUNTS ===\n";
$b = $db->query('SELECT id, event_date, created_at, total_amount, booking_status FROM bookings ORDER BY event_date ASC')->fetchAll(PDO::FETCH_ASSOC);
foreach ($b as $r) {
    echo "ID {$r['id']} | Event Date: {$r['event_date']} | Created: {$r['created_at']} | Total: {$r['total_amount']} | Status: {$r['booking_status']}\n";
}

echo "\n=== GROUPED BY YM ===\n";
$trend = $db->query("
    SELECT DATE_FORMAT(event_date, '%Y-%m') AS ym,
           COALESCE(SUM(total_amount), 0) AS rev,
           COUNT(*) AS cnt
    FROM bookings
    WHERE booking_status != 'cancelled'
    GROUP BY ym
")->fetchAll(PDO::FETCH_ASSOC);
print_r($trend);
