<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

// Fetch booking from URL param
$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = null;
$invoice = null;
if ($bookingId > 0) {
    $stmtB = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, cp.title AS package_title FROM bookings b JOIN venues v ON b.venue_id = v.id LEFT JOIN catering_packages cp ON b.package_id = cp.id WHERE b.id = ? AND b.customer_id = ? LIMIT 1");
    $stmtB->execute([$bookingId, $currentUser['id']]);
    $booking = $stmtB->fetch();
    if ($booking) {
        $stmtI = $db->prepare("SELECT * FROM invoices WHERE booking_id = ? LIMIT 1");
        $stmtI->execute([$bookingId]);
        $invoice = $stmtI->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Booking Confirmed</title>
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
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
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
          <div class="topbar-user">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        
<div style="max-width:580px; margin:40px auto; text-align:center;">
  <div class="card" style="padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px;">✓</div>
    <h1 style="font-size:2rem; font-weight:800; margin-bottom:8px;">Booking Confirmed!</h1>
    <p class="text-muted mb-24">Reference: <strong class="text-primary"><?= $booking ? e($booking['booking_code']) : '#BK-XXXXX' ?></strong> • Confirmation receipt emailed to <strong><?= e($currentUser['email'] ?? 'your account email') ?></strong></p>

    <div class="flex gap-12" style="flex-direction:column;">
      <?php if ($booking): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:20px;margin:20px 0;text-align:left;">
  <div style="font-size:0.85rem;color:#166534;margin-bottom:8px;font-weight:600;">📋 Booking Summary</div>
  <table style="width:100%;font-size:0.9rem;border-collapse:collapse;">
    <tr><td style="padding:4px 0;color:#6b7280;">Venue</td><td style="padding:4px 0;font-weight:600;"><?= e($booking['venue_name']) ?></td></tr>
    <tr><td style="padding:4px 0;color:#6b7280;">Event</td><td style="padding:4px 0;"><?= e($booking['event_name']) ?></td></tr>
    <tr><td style="padding:4px 0;color:#6b7280;">Date</td><td style="padding:4px 0;"><?= date('F j, Y', strtotime($booking['event_date'])) ?></td></tr>
    <tr><td style="padding:4px 0;color:#6b7280;">Time</td><td style="padding:4px 0;"><?= substr($booking['start_time'],0,5) ?> – <?= substr($booking['end_time'],0,5) ?></td></tr>
    <tr><td style="padding:4px 0;color:#6b7280;">Guests</td><td style="padding:4px 0;"><?= $booking['guest_count'] ?></td></tr>
    <?php if (!empty($booking['package_title'])): ?>
    <tr><td style="padding:4px 0;color:#6b7280;">Package</td><td style="padding:4px 0;"><?= e($booking['package_title']) ?></td></tr>
    <?php endif; ?>
    <tr><td style="padding:4px 0;color:#166534;font-weight:700;border-top:1px solid #86efac;padding-top:8px;">Total Paid</td><td style="padding:4px 0;color:#166534;font-weight:700;border-top:1px solid #86efac;padding-top:8px;">$<?= number_format($booking['total_amount'], 2) ?></td></tr>
  </table>
</div>
<?php endif; ?>
<div style="display:flex; gap:12px; margin-top:16px;">
  <a href="client-invoice.php<?= $booking ? '?booking_id=' . $booking['id'] : '' ?>" class="btn btn-outline" style="flex:1;">View & Download Invoice</a>
  <a href="customer-dashboard.php" class="btn btn-primary" style="flex:1;">Return to Dashboard</a>
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