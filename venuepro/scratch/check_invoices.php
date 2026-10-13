<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
echo "--- INVOICES COLUMNS ---\n";
$cols = $db->query("DESCRIBE invoices")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} ({$c['Type']})\n";
}

echo "--- BOOKINGS COLUMNS ---\n";
$bcols = $db->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($bcols as $c) {
    if (strpos($c['Field'], 'tax') !== false || strpos($c['Field'], 'vat') !== false) {
        echo "{$c['Field']} ({$c['Type']})\n";
    }
}
