<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

// Fetch all bookings for this customer
$stmtAll = $db->prepare(
    "SELECT b.*, v.name AS venue_name, v.district AS venue_district,
            cp.title AS package_title, cp.tier AS package_tier,
            sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff
     FROM bookings b
     JOIN venues v ON b.venue_id = v.id
     LEFT JOIN catering_packages cp ON b.package_id = cp.id
     LEFT JOIN staff_assignments sa ON sa.booking_id = b.id
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
        $stmtSel = $db->prepare("SELECT b.*, v.name AS venue_name, v.district AS venue_district, cp.title AS package_title, sa.setup_status, sa.completion_pct, sa.completed_at, sa.reported_staff FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id LEFT JOIN staff_assignments sa ON sa.booking_id = b.id WHERE b.id=? AND b.customer_id=? LIMIT 1");
        $stmtSel->execute([$selectedId, $currentUser['id']]);
        $selected = $stmtSel->fetch();
    }
}
if (!$selected && !empty($allBookings)) {
    $selected = $allBookings[0];
}

if ($selected) {
    $timeline = getBookingProgressTimeline($selected);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Live Progress</title>
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
      <a href="../logout.php" class="nav-item" style="color:var(--gray-400);">
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
        <input type="text" placeholder="e.g. Search event venues, bookings...">
      </div>
      <div class="topbar-actions">
        <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
          <?= renderNotifBadge() ?>🔔
        </a>
        <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">💬</a>
        <a href="customer-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
          <div class="user-avatar" style="background:<?= e($currentUser['avatar_bg'] ?? '#2563eb') ?>;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
          <div class="user-info">
            <div class="user-name"><?= e($currentUser['name']) ?></div>
            <div class="user-role"><?= ucfirst(e($currentUser['role'])) ?></div>
          </div>
        </a>
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
                <span class="pill pill-<?= $stClass ?> lp-pill-status-<?= $bk['id'] ?>" style="font-size:0.7rem;"><?= strtoupper($stClass) ?></span>
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
            <div class="card-header" style="padding:20px 24px 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
              <div class="card-title">📍 Booking Progress — <span id="lp-booking-code"><?= e($selected['booking_code']) ?></span></div>
              <div style="display:flex; align-items:center; gap:8px;">
                <span id="lp-live-badge" class="pill" style="background:#ecfdf5; color:#059669; border:1.5px solid #a7f3d0; font-size:0.75rem; display:inline-flex; align-items:center; gap:6px; padding:4px 10px; font-weight:700;">
                  <span class="live-dot" style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                  <span>Real-Time Sync Active</span>
                </span>
                <span id="lp-sync-time" class="text-xs text-muted" style="font-size:0.7rem;">Synced just now</span>
              </div>
            </div>
            <div style="padding:24px;">
              <div id="lp-timeline-container" style="position:relative;padding-left:32px;">
                <?php foreach ($timeline as $i => $step):
                  $isLast = ($i === count($timeline) - 1);
                  $isDone = !empty($step['done']);
                  $isRejected = ($step['status'] === 'rejected' || !empty($step['cancelled']));
                  $isInProgress = ($step['status'] === 'in_progress');

                  $dotColor = '#d1d5db';
                  if ($isRejected) $dotColor = '#dc2626';
                  elseif ($isDone) $dotColor = '#16a34a';
                  elseif ($isInProgress) $dotColor = '#2563eb';

                  $lineColor = ($isDone && !$isRejected) ? '#16a34a' : '#e5e7eb';
                ?>
                <div style="position:relative;">
                  <?php if (!$isLast): ?>
                  <div style="position:absolute;left:-20px;top:16px;width:2px;height:44px;background:<?= $lineColor ?>;transition:background 0.3s;"></div>
                  <?php endif; ?>
                  <div style="position:absolute;left:-28px;top:4px;width:16px;height:16px;border-radius:50%;background:<?= $dotColor ?>;border:2px solid #fff;box-shadow:0 0 0 2px <?= $dotColor ?>;transition:all 0.3s;"></div>
                  <div style="padding-bottom:28px;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                      <span style="font-weight:<?= $isDone ? '700' : '500' ?>;color:<?= $isDone ? 'var(--gray-900)' : 'var(--gray-500)' ?>;font-size:0.95rem;">
                        <?= e($step['label']) ?>
                      </span>
                      <?php if ($isDone && !$isRejected): ?>
                      <span style="color:#16a34a;font-weight:800;font-size:1rem;">✓</span>
                      <?php endif; ?>
                      <?php if ($isRejected): ?>
                      <span style="color:#dc2626;font-weight:800;font-size:0.9rem;">✗</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($step['subtext'])): ?>
                    <div style="font-size:0.8rem;color:<?= $isDone ? 'var(--gray-600)' : 'var(--gray-400)' ?>;margin-top:3px;line-height:1.4;"><?= e($step['subtext']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div id="lp-setup-banner">
            <?php 
            $setupStatusVal = strtolower($selected['setup_status'] ?? '');
            $isReady = ($setupStatusVal === 'completed' || (int)($selected['completion_pct'] ?? 0) >= 100 || strtolower($selected['booking_status']) === 'completed');
            $inPrep = ($setupStatusVal === 'in_progress' || (int)($selected['completion_pct'] ?? 0) > 0);
            ?>
            <?php if ($isReady): ?>
            <div class="card mb-24" style="background:#f0fdf4; border:1.5px solid #86efac; padding:18px 22px;">
              <div style="display:flex; align-items:center; gap:14px;">
                <div style="font-size:2rem; color:#16a34a; flex-shrink:0;">✓</div>
                <div>
                  <div style="font-weight:700; color:#166534; font-size:1.05rem;">Venue Setup 100% Certified &amp; Ready</div>
                  <div style="color:#15803d; font-size:0.875rem; margin-top:3px; line-height:1.4;">
                    All 10 operational and safety checklists have been verified by <strong><?= e($selected['reported_staff'] ?? 'Venue Operations Staff') ?></strong>.
                    <?= !empty($selected['completed_at']) ? 'Certified on ' . date('M j, Y g:i A', strtotime($selected['completed_at'])) . '.' : '' ?>
                  </div>
                </div>
              </div>
            </div>
            <?php elseif ($inPrep): ?>
            <div class="card mb-24" style="background:#eff6ff; border:1.5px solid #bfdbfe; padding:18px 22px;">
              <div style="display:flex; align-items:center; gap:14px;">
                <div style="font-size:2rem; color:#2563eb; flex-shrink:0;">🛠</div>
                <div>
                  <div style="font-weight:700; color:#1e40af; font-size:1.05rem;">Event Setup in Progress (<?= (int)$selected['completion_pct'] ?>%)</div>
                  <div style="color:#1d4ed8; font-size:0.875rem; margin-top:3px; line-height:1.4;">
                    Our operations team is currently staging tables, audio-visual equipment, and coordinating with catering.
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>
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
                <tr><td style="padding:8px 0;color:var(--gray-500);">Guests</td><td style="padding:8px 0;font-weight:600;"><?= number_format((int)$selected['guest_count']) ?> Attendees</td></tr>
                <?php if (!empty($selected['package_title'])): ?>
                <tr><td style="padding:8px 0;color:var(--gray-500);">Package</td><td style="padding:8px 0;"><?= e($selected['package_title']) ?></td></tr>
                <?php endif; ?>
                <tr style="border-top:1px solid var(--gray-200);">
                  <td style="padding:12px 0 4px;color:var(--gray-500);">Total Amount</td>
                  <td style="padding:12px 0 4px;font-weight:700;color:var(--primary);font-size:1.1rem;">$<?= number_format($selected['total_amount'], 2) ?></td>
                </tr>
              </table>

              <div id="lp-actions-container" style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
                <a href="client-invoice.php?booking_id=<?= $selected['id'] ?>" class="btn btn-outline" style="font-size:0.85rem;">📄 View Invoice</a>
                <?php if (in_array(strtolower($selected['booking_status']), ['pending', 'inquiry'])): ?>
                <button class="btn" style="font-size:0.85rem;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;border-radius:6px;padding:8px 16px;"
                  onclick="cancelBooking(<?= $selected['id'] ?>, '<?= e($selected['event_name']) ?>')">✕ Cancel Booking</button>
                <?php elseif (strtolower($selected['booking_status']) === 'confirmed'): ?>
                <span class="pill pill-confirmed" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:0.85rem;">✓ Confirmed — Non-Cancellable</span>
                <?php elseif (strtolower($selected['booking_status']) === 'completed'): ?>
                <span class="pill pill-completed" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:0.85rem;background:#ecfdf5;color:#059669;border:1px solid #10b981;">✓ Event Concluded &amp; Completed</span>
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
const currentBookingId = <?= (int)($selected['id'] ?? 0) ?>;

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

// -------------------------------------------------------------
// REAL-TIME DYNAMIC PROGRESS POLLER (Runs every 3 seconds)
// -------------------------------------------------------------
async function fetchLiveProgress() {
  if (!currentBookingId) return;
  try {
    const res = await fetch('../api/bookings.php?action=live_progress&booking_id=' + currentBookingId);
    if (!res.ok) return;
    const data = await res.json();
    if (data.success && data.data) {
      updateLiveProgressUI(data.data);
    }
  } catch (err) {
    console.warn('Real-time sync poll failed, retrying in 3s...', err);
  }
}

function updateLiveProgressUI(data) {
  const steps = data.steps || [];
  const booking = data.booking || {};
  const isReady = data.is_ready;
  const inPrep = data.in_prep;
  const setupInfo = data.setup_info || {};

  // 1. Update sync timestamp
  const syncTimeEl = document.getElementById('lp-sync-time');
  if (syncTimeEl) {
    const d = new Date();
    syncTimeEl.textContent = 'Synced ' + d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
  }

  // 2. Render Timeline Steps Dynamically
  const container = document.getElementById('lp-timeline-container');
  if (container && steps.length > 0) {
    let html = '';
    steps.forEach((step, idx) => {
      const isLast = (idx === steps.length - 1);
      const isDone = !!step.done;
      const isRejected = (step.status === 'rejected' || step.cancelled);
      const isInProgress = (step.status === 'in_progress');
      
      let dotColor = '#d1d5db';
      if (isRejected) dotColor = '#dc2626';
      else if (isDone) dotColor = '#16a34a';
      else if (isInProgress) dotColor = '#2563eb';

      let lineColor = (isDone && !isRejected) ? '#16a34a' : '#e5e7eb';

      html += `
        <div style="position:relative;">
          ${!isLast ? `<div style="position:absolute;left:-20px;top:16px;width:2px;height:44px;background:${lineColor};transition:background 0.3s;"></div>` : ''}
          <div style="position:absolute;left:-28px;top:4px;width:16px;height:16px;border-radius:50%;background:${dotColor};border:2px solid #fff;box-shadow:0 0 0 2px ${dotColor};transition:all 0.3s;"></div>
          <div style="padding-bottom:28px;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <span style="font-weight:${isDone ? '700' : '500'};color:${isDone ? 'var(--gray-900)' : 'var(--gray-500)'};font-size:0.95rem;">
                ${escapeHtml(step.label)}
              </span>
              ${isDone && !isRejected ? '<span style="color:#16a34a;font-weight:800;font-size:1rem;">✓</span>' : ''}
              ${isRejected ? '<span style="color:#dc2626;font-weight:800;font-size:0.9rem;">✗</span>' : ''}
            </div>
            ${step.subtext ? `<div style="font-size:0.8rem;color:${isDone ? 'var(--gray-600)' : 'var(--gray-400)'};margin-top:3px;line-height:1.4;">${escapeHtml(step.subtext)}</div>` : ''}
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
  }

  // 3. Render Setup Banner
  const bannerContainer = document.getElementById('lp-setup-banner');
  if (bannerContainer) {
    if (isReady) {
      const staffName = setupInfo.reported_staff || 'Venue Operations Staff';
      const completedTime = setupInfo.completed_at ? 'Certified ' + setupInfo.completed_at : '';
      bannerContainer.innerHTML = `
        <div class="card mb-24" style="background:#f0fdf4; border:1.5px solid #86efac; padding:18px 22px;">
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="font-size:2rem; color:#16a34a; flex-shrink:0;">✓</div>
            <div>
              <div style="font-weight:700; color:#166534; font-size:1.05rem;">Venue Setup 100% Certified &amp; Ready</div>
              <div style="color:#15803d; font-size:0.875rem; margin-top:3px; line-height:1.4;">
                All 10 operational and safety checklists have been verified by <strong>${escapeHtml(staffName)}</strong>. ${escapeHtml(completedTime)}
              </div>
            </div>
          </div>
        </div>
      `;
      bannerContainer.style.display = 'block';
    } else if (inPrep) {
      bannerContainer.innerHTML = `
        <div class="card mb-24" style="background:#eff6ff; border:1.5px solid #bfdbfe; padding:18px 22px;">
          <div style="display:flex; align-items:center; gap:14px;">
            <div style="font-size:2rem; color:#2563eb; flex-shrink:0;">🛠</div>
            <div>
              <div style="font-weight:700; color:#1e40af; font-size:1.05rem;">Event Setup in Progress (${parseInt(setupInfo.pct || 0)}%)</div>
              <div style="color:#1d4ed8; font-size:0.875rem; margin-top:3px; line-height:1.4;">
                Our operations team is currently staging tables, audio-visual equipment, and coordinating with catering.
              </div>
            </div>
          </div>
        </div>
      `;
      bannerContainer.style.display = 'block';
    } else {
      bannerContainer.innerHTML = '';
      bannerContainer.style.display = 'none';
    }
  }

  // 4. Update status pills in sidebar and list
  const statusPills = document.querySelectorAll('.lp-pill-status-' + currentBookingId);
  const statusStr = (booking.booking_status || 'pending').toLowerCase();
  statusPills.forEach(pill => {
    pill.className = 'pill pill-' + statusStr + ' lp-pill-status-' + currentBookingId;
    pill.textContent = statusStr.toUpperCase();
  });

  // 5. Update actions
  const actionsEl = document.getElementById('lp-actions-container');
  if (actionsEl) {
    if (['pending', 'inquiry'].includes(statusStr)) {
      actionsEl.innerHTML = `
        <a href="client-invoice.php?booking_id=${booking.id}" class="btn btn-outline" style="font-size:0.85rem;">📄 View Invoice</a>
        <button class="btn" style="font-size:0.85rem;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;border-radius:6px;padding:8px 16px;" onclick="cancelBooking(${booking.id}, '${escapeHtml(booking.event_name)}')">✕ Cancel Booking</button>
      `;
    } else if (statusStr === 'confirmed') {
      actionsEl.innerHTML = `
        <a href="client-invoice.php?booking_id=${booking.id}" class="btn btn-outline" style="font-size:0.85rem;">📄 View Invoice</a>
        <span class="pill pill-confirmed" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:0.85rem;">✓ Confirmed — Non-Cancellable</span>
      `;
    } else if (statusStr === 'completed') {
      actionsEl.innerHTML = `
        <a href="client-invoice.php?booking_id=${booking.id}" class="btn btn-outline" style="font-size:0.85rem;">📄 View Invoice</a>
        <span class="pill pill-completed" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:0.85rem;background:#ecfdf5;color:#059669;border:1px solid #10b981;">✓ Event Concluded &amp; Completed</span>
      `;
    }
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Start live sync poller every 3 seconds
setInterval(fetchLiveProgress, 3000);
document.addEventListener('DOMContentLoaded', fetchLiveProgress);

</script>
<script src="../js/app.js"></script>
</body>
</html>
