<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Test for each venue name
$venues = ['The Brick & Steel Gallery', 'Waterfront Glasshouse', 'try', 'Grand Emerald Ballroom'];

foreach ($venues as $venueName) {
    echo "\n=== Testing venue: '$venueName' ===\n";
    $venueIdStr = '0';

    $stmtCaterers = $db->prepare("
        SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
        FROM users u
        JOIN caterer_profiles cp ON u.id = cp.user_id
        JOIN catering_packages pkg ON pkg.caterer_id = u.id
        WHERE u.role = 'caterer' 
          AND u.status = 'active' 
          AND cp.approval_status = 'approved'
          AND pkg.status IN ('published', 'active')
          AND (
              cp.assigned_venue = ? 
              OR cp.assigned_venue = ? 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR FIND_IN_SET(?, cp.assigned_venue) > 0 
              OR cp.assigned_venue LIKE ?
          )
        ORDER BY cp.business_name ASC
    ");
    $stmtCaterers->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
    $caterersList = $stmtCaterers->fetchAll(PDO::FETCH_ASSOC);

    echo "Caterers found: " . count($caterersList) . "\n";
    foreach ($caterersList as $c) {
        echo "  - ID:{$c['id']} Name:{$c['name']} BizName:{$c['business_name']} AssignedVenue:{$c['assigned_venue']}\n";
    }

    $assignedCatererIds = array_map('intval', array_column($caterersList, 'id'));
    if (!empty($assignedCatererIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($assignedCatererIds), '?'));
        $stmtPkgs = $db->prepare("
            SELECT cp.id, cp.title, cp.status, cp.caterer_id, u.name AS caterer_name
            FROM catering_packages cp
            LEFT JOIN users u ON cp.caterer_id = u.id
            WHERE cp.status IN ('published', 'active')
              AND cp.caterer_id IN ($inPlaceholders)
            ORDER BY cp.price_per_event ASC
        ");
        $stmtPkgs->execute($assignedCatererIds);
        $packages = $stmtPkgs->fetchAll(PDO::FETCH_ASSOC);
        echo "Packages found: " . count($packages) . "\n";
        foreach ($packages as $p) {
            echo "  - PkgID:{$p['id']} Title:{$p['title']} Status:{$p['status']} CatererID:{$p['caterer_id']} ({$p['caterer_name']})\n";
        }
    } else {
        echo "Packages found: 0 (no assigned caterers)\n";
    }
}

// Also check what FIND_IN_SET does on the comma-separated assigned_venue
echo "\n=== FIND_IN_SET test ===\n";
$stmt = $db->query("SELECT user_id, assigned_venue, FIND_IN_SET('The Brick & Steel Gallery', assigned_venue) as found FROM caterer_profiles");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "UserID:{$row['user_id']} | assigned_venue:{$row['assigned_venue']} | FIND_IN_SET result:{$row['found']}\n";
}
