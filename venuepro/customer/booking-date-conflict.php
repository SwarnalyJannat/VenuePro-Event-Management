<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$venueId = (int)($_GET['venue_id'] ?? 1);
$conflictDate = sanitize($_GET['date'] ?? '2026-10-14');

$stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtV->execute([$venueId]);
$venue = $stmtV->fetch();
$venueName = $venue ? $venue['name'] : 'Grand Emerald Ballroom';

$stmtB = $db->prepare("SELECT * FROM bookings WHERE venue_id = ? AND event_date = ? AND booking_status IN ('confirmed','pending') LIMIT 1");
$stmtB->execute([$venueId, $conflictDate]);
$conflictBooking = $stmtB->fetch();
$eventName = $conflictBooking ? $conflictBooking['event_name'] : 'an existing corporate gala';
$timeInfo = $conflictBooking ? " from " . substr($conflictBooking['start_time'], 0, 5) . " to " . substr($conflictBooking['end_time'], 0, 5) : "";

$timestamp = strtotime($conflictDate) ?: strtotime('2026-10-14');
$altDate1 = date('Y-m-d', strtotime('-2 days', $timestamp));
$altDate2 = date('Y-m-d', strtotime('+3 days', $timestamp));
$altLabel1 = date('l, M j, Y', strtotime($altDate1));
$altLabel2 = date('l, M j, Y', strtotime($altDate2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Date Conflict</title>
  <link rel="stylesheet" href="../css/style.css">
  
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Sidebar -->
    
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
            <header class="topbar">
        <label for="sidebar-toggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </label>
        <div class="topbar-search">
          <span class="topbar-search-icon">🔍</span>
          <input type="text" placeholder="Search event venues, bookings, menus...">
        </div>
        <div class="topbar-actions">
          <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">3</span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <a href="customer-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        
<div style="max-width:560px; margin:40px auto;">
  <div class="card" style="padding:36px; text-align:center;">
    <div style="width:64px; height:64px; border-radius:50%; background:#fee2e2; color:#dc2626; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 16px;">⚠️</div>
    <h2 class="mb-8">Date Conflict Alert</h2>
    <p class="mb-24 text-muted"><strong><?= e($venueName) ?></strong> is already reserved for <?= e($eventName) ?> on <strong><?= date('F j, Y', $timestamp) ?></strong><?= e($timeInfo) ?>. Please select an adjacent opening or choose another date.</p>

    <div class="card mb-24" style="background:var(--gray-50); text-align:left;">
      <div class="font-semibold text-sm mb-8">Recommended Adjacent Openings:</div>
      <div class="flex-between text-sm mb-8" style="padding:8px 12px; background:#fff; border-radius:var(--radius-sm);">
        <span><?= e($altLabel1) ?></span>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>&date=<?= $altDate1 ?>" onclick="sessionStorage.setItem('vp_event_date', '<?= $altDate1 ?>'); sessionStorage.setItem('vp_date', '<?= $altDate1 ?>');" class="btn btn-outline btn-sm">Select Date</a>
      </div>
      <div class="flex-between text-sm" style="padding:8px 12px; background:#fff; border-radius:var(--radius-sm);">
        <span><?= e($altLabel2) ?></span>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>&date=<?= $altDate2 ?>" onclick="sessionStorage.setItem('vp_event_date', '<?= $altDate2 ?>'); sessionStorage.setItem('vp_date', '<?= $altDate2 ?>');" class="btn btn-outline btn-sm">Select Date</a>
      </div>
    </div>

    <div class="flex gap-12">
      <a href="booking-date-guests.php?venue_id=<?= $venueId ?>" class="btn btn-ghost" style="flex:1;">Change Date</a>
      <a href="venue-listings.php" class="btn btn-primary" style="flex:1;">Browse Other Venues</a>
    </div>
  </div>
</div>

      </main>

                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
        <div class="footer-links">
          <a href="../privacy-policy.php">Privacy Policy</a>
          <a href="../terms-of-service.php">Terms of Service</a>
          <a href="../contact-support.php">Contact Support</a>
        </div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>