<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== CHECKING CURRENT STAFF ASSIGNMENTS ===\n";
$stmt = $db->query("SELECT sa.id, sa.staff_id, sa.booking_id, sa.setup_status, sa.completion_pct, sa.completed_at, b.event_name, b.booking_status 
                    FROM staff_assignments sa 
                    JOIN bookings b ON sa.booking_id = b.id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Event: {$r['event_name']} | Setup: {$r['setup_status']} ({$r['completion_pct']}%) | Booking Status: {$r['booking_status']} | Completed At: {$r['completed_at']}\n";
}

if (!empty($rows)) {
    $first = $rows[0];
    echo "\n=== SIMULATING SUBMIT_REPORT FOR ASSIGNMENT ID {$first['id']} ===\n";
    
    // Simulate what api/staff.php submit_report does
    $checklist = [];
    for ($i = 0; $i < 10; $i++) {
        $checklist['task-' . $i] = true;
    }
    
    $upd = $db->prepare("UPDATE staff_assignments SET setup_status = 'completed', completion_pct = 100, reported_venue = 'Test Venue', reported_staff = 'Lead Coordinator', checklist_json = ?, completed_at = NOW() WHERE id = ?");
    $upd->execute([json_encode($checklist), $first['id']]);
    
    $db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE id = ?")->execute([$first['booking_id']]);
    
    echo "Updated! Re-verifying from DB:\n";
    $verify = $db->prepare("SELECT sa.id, sa.setup_status, sa.completion_pct, sa.completed_at, sa.checklist_json, b.booking_status 
                            FROM staff_assignments sa 
                            JOIN bookings b ON sa.booking_id = b.id 
                            WHERE sa.id = ?");
    $verify->execute([$first['id']]);
    $res = $verify->fetch(PDO::FETCH_ASSOC);
    echo "Verified Setup Status: {$res['setup_status']} ({$res['completion_pct']}%)\n";
    echo "Verified Booking Status: {$res['booking_status']}\n";
    echo "Verified Completed At: {$res['completed_at']}\n";
    echo "Verified Checklist JSON length: " . strlen($res['checklist_json']) . "\n";
}
