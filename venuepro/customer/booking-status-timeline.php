<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$bookingId = (int)($_GET['booking_id'] ?? 0);
$bookingTL = null;
$timelineSteps = [];
if ($bookingId > 0) {
    $stmtTL = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, cp.title AS package_title, sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id LEFT JOIN staff_assignments sa ON sa.booking_id = b.id WHERE b.id=? AND b.customer_id=? LIMIT 1");
    $stmtTL->execute([$bookingId, $currentUser['id']]);
    $bookingTL = $stmtTL->fetch();
}
if (!$bookingTL) {
    $stmtTL = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, cp.title AS package_title, sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id LEFT JOIN staff_assignments sa ON sa.booking_id = b.id WHERE b.customer_id=? ORDER BY b.created_at DESC LIMIT 1");
    $stmtTL->execute([$currentUser['id']]);
    $bookingTL = $stmtTL->fetch();
}
if ($bookingTL) {
    $timelineSteps = getBookingProgressTimeline($bookingTL);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Status Timeline</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    @keyframes pulseDot {
      0% { transform: scale(0.92); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
      70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
      100% { transform: scale(0.92); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .live-dot {
      animation: pulseDot 2s infinite ease-in-out;
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    
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
        <a href="venue-listings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

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
            <?= renderNotifBadge() ?>
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
<?php if (!$bookingTL): ?>
<div class="card" style="text-align:center; padding:60px 20px;">
  <div style="font-size:3rem; margin-bottom:16px;">📅</div>
  <h2>No Active Bookings Found</h2>
  <p class="text-muted mb-24">You do not have any active bookings to track yet.</p>
  <a href="venue-listings.php" class="btn btn-primary">Browse Venues & Book Now</a>
</div>
<?php else: 
  $statusClass = strtolower($bookingTL['booking_status']);
  $statusUpper = strtoupper($bookingTL['booking_status']);
  $eventDateF  = date('F j, Y', strtotime($bookingTL['event_date']));
?>
<div class="breadcrumb">
  <a href="customer-dashboard.php">Dashboard</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current"><?= e($bookingTL['booking_code']) ?> Status Timeline</span>
</div>

<div class="flex-between mb-24">
  <div>
    <h1>Booking Status Timeline</h1>
    <p><?= e($bookingTL['event_name']) ?> • Booking Ref <strong><?= e($bookingTL['booking_code']) ?></strong></p>
  </div>
  <div style="display:flex; align-items:center; gap:10px;">
    <span class="pill" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; font-size:0.75rem; display:inline-flex; align-items:center; gap:6px; padding:4px 10px; font-weight:700;">
      <span class="live-dot" style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span>
      <span>Live Sync Active</span>
    </span>
    <span id="tl-status-pill" class="pill pill-<?= $statusClass ?>" style="font-size:0.85rem; padding:6px 14px;"><?= $statusUpper ?></span>
  </div>
</div>

<div class="grid-2" style="grid-template-columns: 2fr 1fr; gap:28px;">
  <div class="card">
    <div class="timeline" id="tl-steps-container">
      <?php foreach ($timelineSteps as $idx => $step): 
        $isDone = !empty($step['done']);
        $isCancelled = !empty($step['cancelled']) || ($step['status'] === 'rejected');
        $itemClass = $isCancelled ? 'cancelled' : ($isDone ? 'done' : 'pending');
        $dot = $isCancelled ? '✕' : ($isDone ? '✓' : '○');
      ?>
      <div class="timeline-item <?= $itemClass ?>">
        <div class="timeline-dot"><?= $dot ?></div>
        <div class="timeline-title">
          <?= e($step['label']) ?>
          <?php if ($isDone && !$isCancelled): ?>
          <span style="color:#16a34a; font-weight:800; font-size:1rem; margin-left:6px;">✓</span>
          <?php elseif ($isCancelled): ?>
          <span style="color:#dc2626; font-weight:800; font-size:0.9rem; margin-left:6px;">✕</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($step['subtext'])): ?>
        <div class="timeline-meta"><?= e($step['subtext']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card" style="background:var(--gray-50);">
    <h3 class="mb-16">Reservation Info</h3>
    <div class="text-sm mb-8"><span class="text-muted">Property:</span> <strong><?= e($bookingTL['venue_name']) ?></strong></div>
    <div class="text-sm mb-8"><span class="text-muted">Date:</span> <strong><?= $eventDateF ?></strong></div>
    <div class="text-sm mb-8"><span class="text-muted">Time Slot:</span> <strong><?= substr($bookingTL['start_time'],0,5) ?> – <?= substr($bookingTL['end_time'],0,5) ?></strong></div>
    <div class="text-sm mb-8"><span class="text-muted">Guests:</span> <strong><?= number_format((int)$bookingTL['guest_count']) ?> Attendees</strong></div>
    <?php if (!empty($bookingTL['package_title'])): ?>
    <div class="text-sm mb-8"><span class="text-muted">Catering:</span> <strong><?= e($bookingTL['package_title']) ?></strong></div>
    <?php endif; ?>
    <div class="text-sm mb-16"><span class="text-muted">Total Paid:</span> <strong class="text-primary">$<?= number_format($bookingTL['total_amount'], 2) ?></strong></div>
    
    <div id="tl-setup-badge">
      <?php 
      $setupStatusVal = strtolower($bookingTL['setup_status'] ?? '');
      $isReady = ($setupStatusVal === 'completed' || (int)($bookingTL['completion_pct'] ?? 0) >= 100 || strtolower($bookingTL['booking_status']) === 'completed');
      if ($isReady): ?>
      <div class="text-sm mb-16" style="background:#dcfce7; color:#166534; padding:10px 12px; border-radius:6px; font-weight:600; display:flex; align-items:center; gap:6px;">
        <span>✓</span> <span>Venue Setup 100% Certified</span>
      </div>
      <?php endif; ?>
    </div>

    <a href="client-invoice.php?booking_id=<?= $bookingTL['id'] ?>" class="btn btn-outline btn-full btn-sm mb-8">View Tax Invoice</a>
    <a href="customer-chat.php" class="btn btn-primary btn-full btn-sm">Chat with Coordinator</a>
  </div>
</div>
<?php endif; ?>
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
const tlBookingId = <?= (int)($bookingTL['id'] ?? 0) ?>;

async function syncTimelineProgress() {
  if (!tlBookingId) return;
  try {
    const res = await fetch('../api/bookings.php?action=live_progress&booking_id=' + tlBookingId);
    if (!res.ok) return;
    const data = await res.json();
    if (data.success && data.data) {
      const steps = data.data.steps || [];
      const booking = data.data.booking || {};
      const isReady = data.data.is_ready;

      // 1. Update timeline steps
      const cont = document.getElementById('tl-steps-container');
      if (cont && steps.length > 0) {
        let html = '';
        steps.forEach(step => {
          const isDone = !!step.done;
          const isCancelled = !!step.cancelled || (step.status === 'rejected');
          const itemClass = isCancelled ? 'cancelled' : (isDone ? 'done' : 'pending');
          const dot = isCancelled ? '✕' : (isDone ? '✓' : '○');

          html += `
            <div class="timeline-item ${itemClass}">
              <div class="timeline-dot">${dot}</div>
              <div class="timeline-title">
                ${escapeHtml(step.label)}
                ${isDone && !isCancelled ? '<span style="color:#16a34a; font-weight:800; font-size:1rem; margin-left:6px;">✓</span>' : ''}
                ${isCancelled ? '<span style="color:#dc2626; font-weight:800; font-size:0.9rem; margin-left:6px;">✕</span>' : ''}
              </div>
              ${step.subtext ? `<div class="timeline-meta">${escapeHtml(step.subtext)}</div>` : ''}
            </div>
          `;
        });
        cont.innerHTML = html;
      }

      // 2. Update status pill
      const pill = document.getElementById('tl-status-pill');
      if (pill && booking.booking_status) {
        const st = booking.booking_status.toLowerCase();
        pill.className = 'pill pill-' + st;
        pill.textContent = st.toUpperCase();
      }

      // 3. Update setup badge
      const setupBadge = document.getElementById('tl-setup-badge');
      if (setupBadge) {
        if (isReady) {
          setupBadge.innerHTML = `
            <div class="text-sm mb-16" style="background:#dcfce7; color:#166534; padding:10px 12px; border-radius:6px; font-weight:600; display:flex; align-items:center; gap:6px;">
              <span>✓</span> <span>Venue Setup 100% Certified</span>
            </div>
          `;
        } else {
          setupBadge.innerHTML = '';
        }
      }
    }
  } catch(e) {}
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

setInterval(syncTimelineProgress, 3000);
document.addEventListener('DOMContentLoaded', syncTimelineProgress);
</script>
</body>
</html>