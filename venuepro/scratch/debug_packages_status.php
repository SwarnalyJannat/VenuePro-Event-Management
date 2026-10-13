<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== ALL CATERING PACKAGES (recent 15) ===\n";
$stmt = $db->query("SELECT cp.id, cp.title, cp.status, cp.caterer_id, u.name as caterer_name, pr.assigned_venue, pr.approval_status as caterer_approval, u.status as caterer_status FROM catering_packages cp LEFT JOIN users u ON cp.caterer_id = u.id LEFT JOIN caterer_profiles pr ON u.id = pr.user_id ORDER BY cp.id DESC LIMIT 15");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID:{$row['id']} | CatererID:{$row['caterer_id']} ({$row['caterer_name']}) | Title:{$row['title']} | PkgStatus:{$row['status']} | CatererApproval:{$row['caterer_approval']} | CatererActive:{$row['caterer_status']} | AssignedVenue:".($row['assigned_venue'] ?? 'NULL')."\n";
}

echo "\n=== CATERER PROFILES ===\n";
$stmt2 = $db->query("SELECT cp.user_id, u.name, u.status, cp.approval_status, cp.assigned_venue FROM caterer_profiles cp JOIN users u ON cp.user_id = u.id ORDER BY cp.user_id");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "UserID:{$row['user_id']} | Name:{$row['name']} | UserStatus:{$row['status']} | Approval:{$row['approval_status']} | AssignedVenue:".($row['assigned_venue'] ?? 'NULL')."\n";
}
