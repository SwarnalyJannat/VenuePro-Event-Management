<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "--- BOOKINGS COLUMNS ---\n";
$cols = $db->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "\n--- SAMPLE BOOKINGS ---\n";
$rows = $db->query("SELECT id, booking_code, booking_status, caterer_status, payment_status, event_date FROM bookings LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    print_r($r);
}

echo "\n--- CATERER ORDERS ---\n";
$co = $db->query("SELECT id, order_code, booking_id, caterer_id, preparation_status, package_decision FROM caterer_orders LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($co as $r) {
    print_r($r);
}

echo "\n--- STAFF ASSIGNMENTS ---\n";
$sa = $db->query("SELECT id, booking_id, staff_id, setup_status, completion_pct, completed_at FROM staff_assignments LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sa as $r) {
    print_r($r);
}
