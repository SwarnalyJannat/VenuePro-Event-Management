<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== ALL VENUES ===\n";
$s = $db->query("SELECT id, name FROM venues ORDER BY id");
$venues = $s->fetchAll(PDO::FETCH_ASSOC);
foreach ($venues as $v) {
    echo "ID:{$v['id']} | Name:{$v['name']}\n";
}

echo "\n=== WHICH VENUES HAVE ASSIGNED CATERERS? ===\n";
foreach ($venues as $v) {
    $vid = $v['id'];
    $vname = $v['name'];
    $s2 = $db->prepare("
        SELECT u.id, u.name, cp.business_name, cp.assigned_venue
        FROM users u
        JOIN caterer_profiles cp ON u.id = cp.user_id
        WHERE u.role = 'caterer' AND u.status = 'active' AND cp.approval_status = 'approved'
          AND (
              cp.assigned_venue = ? 
              OR cp.assigned_venue = ? 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR cp.assigned_venue LIKE ?
          )
    ");
    $s2->execute([$vname, (string)$vid, (string)$vid, $vname, "%$vname%"]);
    $caterers = $s2->fetchAll(PDO::FETCH_ASSOC);
    $catererNames = array_map(fn($c) => "{$c['id']}:{$c['business_name']}", $caterers);
    echo "Venue {$vid} ({$vname}): " . (empty($caterers) ? "NO CATERER ASSIGNED" : implode(', ', $catererNames)) . "\n";
}
