<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

// Fetch all bookings for this customer
$stmtAll = $db->prepare(
    "SELECT b.*, v.name AS venue_name, v.district AS venue_district,
            cp.title AS package_title, cp.tier AS package_tier
     FROM bookings b
     JOIN venues v ON b.venue_id = v.id
     LEFT JOIN catering_packages cp ON b.package_id = cp.id
     WHERE b.customer_id = ?
     ORDER BY b.event_date ASC"
);
$stmtAll->execute([$currentUser['id']]);
$allBookings = $stmtAll->fetchAll();

// Selected booking for detail view
$selectedId = (int)($_GET['booking_id'] ?? 0);
$selected   = null;
$timeline   = [];
if ($selectedId > 0) {
    foreach ($allBookings as $bk) {
        if ((int)$bk['id'] === $selectedId) { $selected = $bk; break; }
    }
    if (!$selected) {
        $stmtSel = $db->prepare("SELECT b.*, v.name AS venue_name, v.district AS venue_district, cp.title AS package_title FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id WHERE b.id=? AND b.customer_id=? LIMIT 1");
        $stmtSel->execute([$selectedId, $currentUser['id']]);
        $selected = $stmtSel->fetch();
    }
    if ($selected) {
        // Build timeline steps based on status
        $status = strtolower($selected['booking_status']);
        $steps = [
            ['label' => 'Booking Submitted',          'done' => true],
            ['label' => 'Under Review',                'done' => in_array($status, ['pending','confirmed','completed'])],
            ['label' => 'Awaiting Caterer Confirmation','done' => in_array($status, ['confirmed','completed'])],
            ['label' => 'Approved & Confirmed',        'done' => in_array($status, ['confirmed','completed'])],
            ['label' => 'Event Day Preparation',       'done' => $status === 'completed'],
            ['label' => 'Event Complete',              'done' => $status === 'completed'],
        ];
        if ($status === 'cancelled') {
            $steps = [
                ['label' => 'Booking Submitted', 'done' => true],
                ['label' => 'Cancelled',          'done' => true, 'cancelled' => true],
            ];
        }
        $timeline = $steps;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Live Progress</title>
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
      <a href="../api/auth.php?action=logout" class="nav-item" style="color:var(--gray-400);">
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
        <input type="text" placeholder="Search event venues, bookings...">
      </div>
      <div class="topbar-actions">
        <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
          <span class="badge" id="notif-badge" style="display:none;">0</span>🔔
        </a>
        <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">💬</a>
        <div class="topbar-user">
          <div class="user-avatar" style="background:<?= e($currentUser['avatar_bg'] ?? '#2563eb') ?>;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
          <div class="user-info">
            <div class="user-name"><?= e($currentUser['name']) ?></div>
            <div class="user-role"><?= ucfirst(e($currentUser['role'])) ?></div>
          </div>
        </div>
      </div>
    </header>

    <main class="page-body">
      <div class="flex-between mb-24">
        <div>
          <h1 style="font-size:2rem;font-weight:800;margin-bottom:4px;">📊 Live Progress</h1>
          <p>Track the real-time status of your booked venues and events.</p>
        </div>
      </div>

      <?php if (empty($allBookings)): ?>
      <div class="card" style="text-align:center;padding:48px;">
        <div style="font-size:4rem;margin-bottom:16px;">🏛️</div>
        <h2 style="margin-bottom:8px;">No Bookings Yet</h2>
        <p style="color:var(--gray-500);margin-bottom:24px;">You haven't made any bookings. Browse venues to get started!</p>
        <a href="venue-listings.php" class="btn btn-primary">Browse Venues</a>
      </div>
      <?php else: ?>

      <div style="display:grid;grid-template-columns:<?= $selected ? '340px 1fr' : '1fr' ?>;gap:24px;align-items:start;">
        <!-- Booking List -->
        <div class="card" style="overflow:hidden;">
          <div class="card-header" style="padding:16px 20px;">
            <div class="card-title">Your Bookings</div>
          </div>
          <div style="padding:0;">
            <?php foreach ($allBookings as $bk):
              $isActive   = $selected && (int)$selected['id'] === (int)$bk['id'];
              $stClass    = strtolower($bk['booking_status']);
              $eventDate  = date('M j, Y', strtotime($bk['event_date']));
            ?>
            <a href="customer-live-progress.php?booking_id=<?= $bk['id'] ?>" style="display:block;padding:16px 20px;border-bottom:1px solid var(--gray-100);text-decoration:none;background:<?= $isActive ? 'var(--primary-light, #eff6ff)' : '#fff' ?>;transition:background 0.15s;" onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background='<?= $isActive ? '#eff6ff' : '#fff' ?>'">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                <div class="font-semibold" style="font-size:0.95rem;color:var(--gray-900);"><?= e($bk['event_name']) ?></div>
                <span class="pill pill-<?= $stClass ?>" style="font-size:0.7rem;"><?= strtoupper($stClass) ?></span>
              </div>
              <div style="font-size:0.8rem;color:var(--gray-500);">🏛️ <?= e($bk['venue_name']) ?></div>
              <div style="font-size:0.8rem;color:var(--gray-500);">📅 <?= $eventDate ?></div>
              <?php if (!empty($bk['package_title'])): ?>
              <div style="font-size:0.8rem;color:var(--gray-500);">🍽️ <?= e($bk['package_title']) ?></div>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <?php if ($selected): ?>
        <!-- Detail Panel -->
        <div>
          <!-- Progress Timeline Card -->
          <div class="card mb-24">
            <div class="card-header" style="padding:20px 24px 0;">
              <div class="card-title">📍 Booking Progress — <?= e($selected['booking_code']) ?></div>
            </div>
            <div style="padding:24px;">
              <div style="position:relative;padding-left:32px;">
                <?php foreach ($timeline as $i => $step):
                  $dotColor = isset($step['cancelled']) ? '#dc2626' : ($step['done'] ? '#16a34a' : '#d1d5db');
                  $lineColor = ($step['done'] && !isset($step['cancelled'])) ? '#16a34a' : '#e5e7eb';
                ?>
                <div style="position:relative;margin-bottom:<?= $i < count($timeline)-1 ? '0' : '0' ?>;">
                  <!-- Connector line -->
                  <?php if ($i < count($timeline)-1): ?>
                  <div style="position:absolute;left:-20px;top:16px;width:2px;height:40px;background:<?= $lineColor ?>;"></div>
                  <?php endif; ?>
                  <!-- Dot -->
                  <div style="position:absolute;left:-28px;top:4px;width:16px;height:16px;border-radius:50%;background:<?= $dotColor ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $dotColor ?>;"></div>
                  <!-- Content -->
                  <div style="padding-bottom:32px;">
                    <div style="font-weight:<?= $step['done'] ? '600' : '400' ?>;color:<?= $step['done'] ? 'var(--gray-900)' : 'var(--gray-400)' ?>;">
                      <?= e($step['label']) ?>
                      <?php if ($step['done'] && !isset($step['cancelled'])): ?>
                      <span style="color:#16a34a;margin-left:6px;">✓</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Booking Detail Card -->
          <div class="card mb-24">
            <div class="card-header" style="padding:20px 24px 0;">
              <div class="card-title">📋 Booking Details</div>
            </div>
            <div style="padding:24px;">
              <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                <tr><td style="padding:8px 0;color:var(--gray-500);width:140px;">Event Name</td><td style="padding:8px 0;font-weight:600;"><?= e($selected['event_name']) ?></td></tr>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Venue</td><td style="padding:8px 0;"><?= e($selected['venue_name']) ?></td></tr>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Date</td><td style="padding:8px 0;"><?= date('F j, Y', strtotime($selected['event_date'])) ?></td></tr>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Time</td><td style="padding:8px 0;"><?= substr($selected['start_time'],0,5) ?> – <?= substr($selected['end_time'],0,5) ?></td></tr>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Guests</td><td style="padding:8px 0;"><?= $selected['guest_count'] ?></td></tr>
                <?php if (!empty($selected['package_title'])): ?>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Package</td><td style="padding:8px 0;"><?= e($selected['package_title']) ?></td></tr>
                <?php endif; ?>
                <tr style="border-top:1px solid var(--gray-200);">
                  <td style="padding:12px 0 4px;color:var(--gray-500);">Total Amount</td>
                  <td style="padding:12px 0 4px;font-weight:700;color:var(--primary);font-size:1.1rem;">$<?= number_format($selected['total_amount'], 2) ?></td>
                </tr>
              </table>

              <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
                <a href="client-invoice.php?booking_id=<?= $selected['id'] ?>" class="btn btn-outline" style="font-size:0.85rem;">📄 View Invoice</a>
                <?php if (in_array(strtolower($selected['booking_status']), ['confirmed','pending'])): ?>
                <button class="btn" style="font-size:0.85rem;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;border-radius:6px;padding:8px 16px;"
                  onclick="cancelBooking(<?= $selected['id'] ?>, '<?= e($selected['event_name']) ?>')">✕ Cancel Booking</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php elseif (count($allBookings) > 0): ?>
        <div class="card" style="text-align:center;padding:48px;color:var(--gray-400);">
          <div style="font-size:3rem;margin-bottom:16px;">👈</div>
          <div style="font-size:1rem;font-weight:600;">Select a booking</div>
          <div style="font-size:0.85rem;margin-top:4px;">Click any booking on the left to see its live progress.</div>
        </div>
        <?php endif; ?>
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

<script>
async function cancelBooking(bookingId, eventName) {
  if (!confirm('Cancel booking for "' + eventName + '"?\nThis cannot be undone.')) return;
  const reason = prompt('Reason for cancellation (optional):', 'Customer requested cancellation') || 'Customer requested cancellation';
  try {
    const res = await fetch('../api/bookings.php?action=cancel', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({booking_id: bookingId, reason: reason})
    });
    const data = await res.json();
    if (data.success) { alert('✔ Booking cancelled successfully.'); location.reload(); }
    else { alert('Error: ' + data.message); }
  } catch(e) { alert('Server connection error.'); }
}
// Dynamic notification badge
(async function(){
  try {
    const r = await fetch('../api/notifications.php?action=unread_count');
    const d = await r.json();
    if (d.success && d.data && d.data.count > 0) {
      const b = document.getElementById('notif-badge');
      if (b) { b.textContent = d.data.count; b.style.display = 'inline'; }
    }
  } catch(e){}
})();
</script>
<script src="../js/app.js"></script>
</body>
</html>
