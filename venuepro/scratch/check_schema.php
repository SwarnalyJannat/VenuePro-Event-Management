<?php
$db = new PDO("mysql:host=localhost;dbname=venuepro", "root", "");
foreach (["staff_profiles", "caterer_profiles", "venues", "staff_assignments", "caterer_orders", "bookings"] as $tbl) {
    echo "=== $tbl ===\n";
    $cols = $db->query("DESCRIBE $tbl")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  " . $c["Field"] . " (" . $c["Type"] . ")\n";
    }
}
