<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$admin = $db->query("SELECT * FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$catererId = (int)$db->query("SELECT u.id FROM users u JOIN caterer_profiles cp ON u.id = cp.user_id WHERE u.role = 'caterer' LIMIT 1")->fetchColumn();
$staffId = (int)$db->query("SELECT id FROM users WHERE role = 'staff' LIMIT 1")->fetchColumn();
$venueId = (int)$db->query("SELECT id FROM venues LIMIT 1")->fetchColumn();
$bookingId = (int)$db->query("SELECT id FROM bookings LIMIT 1")->fetchColumn();

$adminFiles = [
    'admin/admin-dashboard.php' => [],
    'admin/admin-pending-bookings.php' => [],
    'admin/admin-booking-approval.php' => ['id' => $bookingId],
    'admin/venue-management.php' => [],
    'admin/add-new-venue.php' => [],
    'admin/admin-edit-venue.php' => ['id' => $venueId],
    'admin/admin-catering-management.php' => [],
    'admin/admin-review-caterer.php' => ['id' => $catererId],
    'admin/admin-add-caterer.php' => [],
    'admin/admin-edit-caterer.php' => ['id' => $catererId],
    'admin/admin-staff-management.php' => [],
    'admin/admin-add-staff.php' => [],
    'admin/admin-edit-staff.php' => ['id' => $staffId],
    'admin/admin-user-management.php' => [],
    'admin/admin-reports.php' => [],
    'admin/admin-export-revenue.php' => [],
    'admin/notification-center.php' => [],
    'admin/admin-profile.php' => [],
    'admin/booking-rejected.php' => ['id' => $bookingId]
];

$pass = 0;
$fail = 0;

session_start();
$_SESSION['user_id'] = $admin['id'];
$_SESSION['user_name'] = $admin['name'];
$_SESSION['user_email'] = $admin['email'];
$_SESSION['user_role'] = 'admin';

foreach ($adminFiles as $file => $params) {
    $_SERVER['REQUEST_URI'] = '/venuepro/' . $file;
    $_SERVER['SCRIPT_NAME'] = '/venuepro/' . $file;
    $_GET = $params;

    ob_start();
    try {
        include __DIR__ . '/../' . $file;
        $out = ob_get_clean();
        
        $hasGov = strpos($out, 'User Governance') !== false;
        $hasProvisionBtn = strpos($out, '+ Provision Administrator') !== false;
        
        if (!$hasGov) {
            echo "❌ {$file}: Missing 'User Governance' in rendered HTML!\n";
            $fail++;
        } elseif ($file === 'admin/admin-user-management.php' && $hasProvisionBtn) {
            echo "❌ {$file}: Still contains '+ Provision Administrator' button!\n";
            $fail++;
        } else {
            echo "✅ {$file}: User Governance link verified" . ($file === 'admin/admin-user-management.php' ? " & + Provision Administrator button confirmed removed" : "") . "\n";
            $pass++;
        }
    } catch (Throwable $e) {
        ob_end_clean();
        echo "❌ {$file}: Exception " . $e->getMessage() . "\n";
        $fail++;
    }
}

echo "\nSummary: {$pass} passed, {$fail} failed.\n";
