<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

requireRole('admin');
$action = $_GET['action'] ?? 'metrics';
$db = getDBConnection();

switch ($action) {
    case 'metrics':
        // Total revenue
        $revStmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) AS total_revenue FROM bookings WHERE payment_status = 'paid'");
        $totalRev = (float)$revStmt->fetch()['total_revenue'];

        // Total Bookings
        $bkStmt = $db->query("SELECT COUNT(*) AS total_bookings FROM bookings");
        $totalBookings = (int)$bkStmt->fetch()['total_bookings'];

        // Pending Bookings
        $pendStmt = $db->query("SELECT COUNT(*) AS pending_count FROM bookings WHERE booking_status = 'pending'");
        $pendingCount = (int)$pendStmt->fetch()['pending_count'];

        // Venues Occupancy overview
        $occStmt = $db->query("SELECT v.id, v.name, COUNT(b.id) AS bookings_count, COALESCE(SUM(b.total_amount), 0) AS revenue FROM venues v LEFT JOIN bookings b ON v.id = b.venue_id GROUP BY v.id, v.name ORDER BY revenue DESC LIMIT 5");
        $topVenues = $occStmt->fetchAll();

        jsonResponse(true, 'Dashboard metrics retrieved', [
            'total_revenue'   => $totalRev,
            'total_bookings'  => $totalBookings,
            'pending_count'   => $pendingCount,
            'top_venues'      => $topVenues
        ]);
        break;

    default:
        jsonResponse(false, 'Invalid reports action', null, 400);
        break;
}
