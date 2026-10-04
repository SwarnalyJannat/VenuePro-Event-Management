<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
header('Location: customer-live-progress.php');
exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Live Catering Progress</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Pulse animation for the "live" task that is in-progress */
    @keyframes progressPulse {
      0%,100% { box-shadow: 0 0 0 0 rgba(37,99,235,.25); }
      50%      { box-shadow: 0 0 0 8px rgba(37,99,235,0); }
    }
    .task-live { animation: progressPulse 2s infinite; border-color: var(--primary) !important; }
    .task-row {
      display:flex; align-items:center; gap:14px;
      padding:13px 16px; border-radius:var(--radius-sm);
      border:1.5px solid var(--gray-200); margin-bottom:8px;
      background:#fff; transition:all .2s;
    }
    .task-row.done { background:#f0fdf4; border-color:#86efac; }
    .task-row.live { border-color:var(--primary); background:#eff6ff; }
    .task-row.pending { opacity:.55; }
    .t-icon { font-size:1.2rem; flex-shrink:0; }
    .t-name { flex:1; font-size:.875rem; font-weight:600; }
    .t-time { font-size:.7rem; color:var(--gray-500); }
    .t-badge { flex-shrink:0; }
    /* Animated progress fill */
    @keyframes fillAnim { from{width:0} to{width:var(--fill-target)} }
    .progress-anim .progress-fill {
      animation: fillAnim 1.5s ease-out forwards;
    }

    .live-indicator {
      display:inline-flex; align-items:center; gap:6px;
      font-size:.75rem; font-weight:700; color:#059669;
    }
    .live-dot { width:8px; height:8px; border-radius:50%; background:#059669; animation:pulse 1.5s infinite; }
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
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

<div class="breadcrumb">
  <a href="customer-dashboard.php">Dashboard</a>
  <span class="breadcrumb-sep">›</span>
  <a href="booking-status-timeline.php">Booking #BK-9847</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current">Catering Live Progress</span>
</div>

<div class="flex-between mb-24">
  <div>
    <h1>Live Catering Progress</h1>
    <p>Artisan Catering Co. — Grand Sovereign Banquet · 150 Guests · Grand Emerald Ballroom</p>
  </div>
  <div class="live-indicator"><span class="live-dot"></span> Updating every 30 seconds</div>
</div>

<div class="grid-2" style="grid-template-columns:2fr 1fr; gap:28px; align-items:start;">
  <div>
    <!-- Overall Progress -->
    <div class="card mb-20">
      <div class="card-header">
        <div class="card-title">Overall Preparation Progress</div>
        <span class="font-bold text-primary">65% Complete</span>
      </div>
      <div class="progress-bar progress-anim" style="height:14px; margin-bottom:8px;">
        <div class="progress-fill" style="--fill-target:65%; width:65%; height:14px;"></div>
      </div>
      <div class="flex-between text-xs text-muted">
        <span>Started: 14:30 (on schedule)</span>
        <span>Estimated service: 18:30</span>
      </div>
    </div>

    <!-- Task List -->
    <div class="card">
      <div class="card-header">
        <div class="card-title">Kitchen Task Updates</div>
        <span class="text-xs text-muted">Last refreshed 2 min ago</span>
      </div>

      <div class="task-row done">
        <span class="t-icon">✅</span>
        <div style="flex:1;">
          <div class="t-name">Mise en Place — All ingredients prepped &amp; portioned</div>
          <div class="t-time">Completed at 14:45 — Chef Rivera's team</div>
        </div>
        <span class="pill pill-confirmed t-badge">DONE</span>
      </div>

      <div class="task-row done">
        <span class="t-icon">✅</span>
        <div style="flex:1;">
          <div class="t-name">Canapés &amp; Hors d'oeuvres Plated (150 portions)</div>
          <div class="t-time">Completed at 15:20 — Station A</div>
        </div>
        <span class="pill pill-confirmed t-badge">DONE</span>
      </div>

      <div class="task-row done">
        <span class="t-icon">✅</span>
        <div style="flex:1;">
          <div class="t-name">Protein Pre-Cook — Filet mignon sous-vide (150 portions)</div>
          <div class="t-time">Completed at 15:55 — Station B</div>
        </div>
        <span class="pill pill-confirmed t-badge">DONE</span>
      </div>

      <div class="task-row live task-live">
        <span class="t-icon">🔄</span>
        <div style="flex:1;">
          <div class="t-name" style="color:var(--primary);">🔵 Entrée Final Plating — Finalizing presentations for 150 covers</div>
          <div class="t-time" style="color:var(--primary);">In progress — due in 22 minutes — Chef Rivera leading</div>
          <div class="progress-bar progress-anim" style="margin-top:8px; height:6px;">
            <div class="progress-fill" style="--fill-target:70%; width:70%; height:6px;"></div>
          </div>
        </div>
        <span class="pill pill-in-progress t-badge">ACTIVE</span>
      </div>

      <div class="task-row pending">
        <span class="t-icon">⏳</span>
        <div style="flex:1;">
          <div class="t-name">Dessert Assembly — Chocolate fondant + berry coulis</div>
          <div class="t-time">Scheduled: 17:30</div>
        </div>
        <span class="pill pill-pending t-badge">PENDING</span>
      </div>

      <div class="task-row pending">
        <span class="t-icon">⏳</span>
        <div style="flex:1;">
          <div class="t-name">Bar &amp; Beverages Station Final Setup</div>
          <div class="t-time">Scheduled: 17:45</div>
        </div>
        <span class="pill pill-pending t-badge">PENDING</span>
      </div>

      <div class="task-row pending">
        <span class="t-icon">⏳</span>
        <div style="flex:1;">
          <div class="t-name">Service Delivery — First cover served to ballroom tables</div>
          <div class="t-time">Scheduled: 18:30 (Event Start)</div>
        </div>
        <span class="pill pill-pending t-badge">PENDING</span>
      </div>
    </div>
  </div>

  <!-- Sidebar Info -->
  <div>
    <div class="card mb-16" style="border:2px solid #059669;">
      <div class="card-header">
        <div class="card-title">Catering Partner</div>
      </div>
      <div class="flex-center gap-12 mb-12">
        <div class="user-avatar" style="background:#059669; width:48px; height:48px;">AR</div>
        <div>
          <div class="font-bold">Chef Alex Rivera</div>
          <div class="text-xs text-muted">Artisan Catering Co. — Lead Chef</div>
        </div>
      </div>
      <div class="text-xs mb-8"><span class="text-muted">Package:</span> <span class="font-bold">Grand Sovereign Banquet</span></div>
      <div class="text-xs mb-8"><span class="text-muted">Guests:</span> <span class="font-bold">150 covers</span></div>
      <a href="customer-chat.php" class="btn btn-outline btn-full btn-sm">💬 Contact Chef</a>
    </div>

    <div class="card mb-16" style="background:#f0fdf4; border:1px solid #86efac; padding:16px;">
      <div class="font-bold text-sm mb-4" style="color:#16a34a;">✓ Schedule Status: ON TRACK</div>
      <div class="text-xs text-muted">All preparation milestones are within the expected timeline. Your dining experience begins at 18:30 as planned.</div>
    </div>

    <div class="card" style="padding:16px; text-align:center;">
      <div class="text-xs text-muted mb-4">Any concerns or special requests?</div>
      <a href="customer-chat.php" class="btn btn-primary btn-full btn-sm">Open Support Chat</a>
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