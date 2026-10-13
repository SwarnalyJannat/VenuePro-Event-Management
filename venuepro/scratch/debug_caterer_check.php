<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== CATERER PROFILES (all) ===\n";
$s = $db->query("SELECT cp.user_id, u.name, u.role, u.status, cp.approval_status, cp.business_name, cp.assigned_venue FROM caterer_profiles cp JOIN users u ON cp.user_id = u.id");
foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "UID:{$r['user_id']} | name:{$r['name']} | role:{$r['role']} | ustatus:{$r['status']} | approval:{$r['approval_status']} | biz:{$r['business_name']} | venue:" . ($r['assigned_venue'] ?? 'NULL') . "\n";
}

echo "\n=== USERS with role=caterer ===\n";
$s2 = $db->query("SELECT id, name, role, status FROM users WHERE role='caterer'");
foreach ($s2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "ID:{$r['id']} | name:{$r['name']} | role:{$r['role']} | status:{$r['status']}\n";
}

echo "\n=== PACKAGES with NULL caterer_id ===\n";
$s3 = $db->query("SELECT id, title, caterer_id, status FROM catering_packages WHERE caterer_id IS NULL OR caterer_id = 0");
foreach ($s3->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "PkgID:{$r['id']} | title:{$r['title']} | caterer_id:" . ($r['caterer_id'] ?? 'NULL') . " | status:{$r['status']}\n";
}
