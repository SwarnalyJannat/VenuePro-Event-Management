<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
﻿<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Customer Dashboard</title>
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
        <a href="customer-dashboard.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item">
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
        
<div class="flex-between mb-24">
  <div>
    <h1 style="font-size:2rem; font-weight:800; margin-bottom:4px;">Welcome back, <?= e($currentUser['name']) ?>!</h1>
    <p>Here is what is happening with your venues today.</p>
  </div>
</div>

<!-- Recent Activities -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Recent Activities</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Event Name</th>
          <th>Venue</th>
          <th>Date</th>
          <th>Status</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="bookings-table-body">
<?php
$stmtB = $db->prepare("SELECT b.*, v.name AS venue_name, cp.title AS package_title
    FROM bookings b
    JOIN venues v ON b.venue_id = v.id
    LEFT JOIN catering_packages cp ON b.package_id = cp.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC LIMIT 10");
$stmtB->execute([$currentUser['id']]);
$dashBookings = $stmtB->fetchAll();
if (empty($dashBookings)): ?>
<tr><td colspan="5" style="text-align:center; color:var(--gray-400); padding:24px;">No bookings yet. <a href="venue-listings.php">Browse venues</a> to get started!</td></tr>
<?php else: foreach ($dashBookings as $bk):
    $statusClass = strtolower($bk['booking_status']);
    $statusLabel = strtoupper($bk['booking_status']);
    $eventDate   = date('M d, Y', strtotime($bk['event_date']));
?>
<tr>
  <td class="font-semibold"><?= e($bk['event_name']) ?></td>
  <td><?= e($bk['venue_name']) ?></td>
  <td><?= $eventDate ?></td>
  <td><span class="pill pill-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
  <td style="text-align:center;">
    <a href="booking-status-timeline.php?booking_id=<?= $bk['id'] ?>" class="btn btn-outline btn-sm" style="padding:4px 10px;font-size:0.75rem;display:inline-flex;align-items:center;gap:4px;" title="View Progress">📊 View</a>
    <?php if (in_array($bk['booking_status'], ['confirmed','pending'])): ?>
    <button class="btn btn-sm" style="padding:4px 10px;font-size:0.75rem;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;border-radius:6px;"
      onclick="cancelBooking(<?= $bk['id'] ?>, '<?= e($bk['event_name']) ?>')">✕ Cancel</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody>
    </table>
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

<script>
async function cancelBooking(bookingId, eventName) {
  if (!confirm('Cancel booking for "' + eventName + '"?\nThis action cannot be undone.')) return;
  const reason = prompt('Reason for cancellation (optional):', 'Customer requested cancellation') || 'Customer requested cancellation';
  try {
    const res = await fetch('../api/bookings.php?action=cancel', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({booking_id: bookingId, reason: reason})
    });
    const data = await res.json();
    if (data.success) {
      alert('✔ Booking cancelled successfully.');
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  } catch(e) {
    alert('Server connection error.');
  }
}
// Dynamic notification badge
(async function(){
  try {
    const r = await fetch('../api/notifications.php?action=unread_count');
    const d = await r.json();
    if (d.success && d.data && d.data.count > 0) {
      const badge = document.querySelector('.topbar-actions .badge');
      if (badge) badge.textContent = d.data.count;
    }
  } catch(e){}
})();
</script>


<script>
async function cancelBooking(bookingId, eventName) {
  if (!confirm('Cancel booking for "' + eventName + '"?\nThis action cannot be undone.')) return;
  const reason = prompt('Reason for cancellation (optional):', 'Customer requested cancellation') || 'Customer requested cancellation';
  try {
    const res = await fetch('../api/bookings.php?action=cancel', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({booking_id: bookingId, reason: reason})
    });
    const data = await res.json();
    if (data.success) {
      alert('✔ Booking cancelled successfully.');
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  } catch(e) {
    alert('Server connection error.');
  }
}
// Dynamic notification badge
(async function(){
  try {
    const r = await fetch('../api/notifications.php?action=unread_count');
    const d = await r.json();
    if (d.success && d.data && d.data.count > 0) {
      const badge = document.querySelector('.topbar-actions .badge');
      if (badge) badge.textContent = d.data.count;
    }
  } catch(e){}
})();
</script>

</body>
</html>
