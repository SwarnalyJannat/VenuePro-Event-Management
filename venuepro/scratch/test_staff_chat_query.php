<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
try {
    $stmtCust = $db->query("SELECT DISTINCT u.id, u.name, u.email, u.avatar_text, u.avatar_bg, b.id as booking_id, b.booking_code, b.event_name, v.name as venue_name
FROM users u
LEFT JOIN bookings b ON b.customer_id = u.id
LEFT JOIN venues v ON b.venue_id = v.id
WHERE u.role = 'customer' AND u.status = 'active'
GROUP BY u.id
ORDER BY u.id ASC");
    $customers = $stmtCust->fetchAll(PDO::FETCH_ASSOC);
    echo "Query executed successfully, found " . count($customers) . " customers:\n";
    print_r($customers);
} catch (Exception $e) {
    echo "Error in query: " . $e->getMessage() . "\n";
}
