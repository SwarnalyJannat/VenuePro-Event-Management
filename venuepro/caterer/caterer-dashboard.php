<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();

// Active orders where caterer accepted the package
$stmtAO = $db->prepare(
    "SELECT co.id AS order_id, co.booking_id, co.preparation_status, co.package_decision,
     b.event_name, b.event_date, b.guest_count,
     v.name AS venue_name,
     cp.title AS package_title, cp.tier AS package_tier
     FROM caterer_orders co
     JOIN bookings b ON co.booking_id = b.id
     JOIN venues v ON b.venue_id = v.id
     LEFT JOIN catering_packages cp ON b.package_id = cp.id
     WHERE co.caterer_id = ?
       AND co.package_decision = 'accepted'
       AND (co.preparation_status IS NULL OR co.preparation_status != 'Delivered')
     ORDER BY b.event_date ASC
     LIMIT 10"
);
$stmtAO->execute([$currentUser['id']]);
$activeOrders = $stmtAO->fetchAll();

// Stats
$totalActive  = count($activeOrders);
$readyCount   = 0;
foreach ($activeOrders as $o) {
    if (($o['preparation_status'] ?? '') === 'Ready') $readyCount++;
}

// Recent orders (last 8 regardless of status)
$stmtRO = $db->prepare(
    "SELECT co.id AS order_id, co.booking_id, co.package_decision, co.preparation_status,
     b.event_name, b.event_date, v.name AS venue_name, cp.title AS package_title, cp.tier AS package_tier
     FROM caterer_orders co
     JOIN bookings b ON co.booking_id = b.id
     JOIN venues v ON b.venue_id = v.id
     LEFT JOIN catering_packages cp ON b.package_id = cp.id
     WHERE co.caterer_id = ?
     ORDER BY co.id DESC
     LIMIT 8"
);
$stmtRO->execute([$currentUser['id']]);
$recentOrders = $stmtRO->fetchAll();

// Tier colour helper
function tierPill($tier) {
    $map = [
        'Platinum' => 'background:#ede9fe;color:#4c1d95;',
        'Gold'     => 'background:#fef9c3;color:#92400e;',
        'Signature'=> 'background:#d1fae5;color:#065f46;',
        'Custom'   => 'background:#e0e7ff;color:#3730a3;'
    ];
    $s = $map[$tier] ?? 'background:var(--gray-100);color:var(--gray-700);';
    return '<span class="pill" style="' . $s . '">' . htmlspecialchars($tier ?: '—') . '</span>';
}

function decisionPill($d) {
    if ($d === 'accepted') return '<span class="pill pill-confirmed">Accepted</span>';
    if ($d === 'rejected') return '<span class="pill" style="background:#fee2e2;color:#991b1b;">Rejected</span>';
    return '<span class="pill pill-inquiry">Pending</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Caterer – Kitchen Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .sidebar-logo-sub { color:#10b981; }
    .order-row { border-bottom:1px solid var(--gray-100); }
    .order-row:hover { background:var(--gray-50); }
    .live-dot { width:7px; height:7px; border-radius:50%; background:#16a34a; display:inline-block; animation:pulse 1.5s infinite; }
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
    .queue-grid { display:grid; grid-template-columns:50px 1fr 110px 90px; gap:0 12px; align-items:center; padding:12px 16px; }
    .queue-header { font-size:.7rem; font-weight:700; text-transform:uppercase; color:var(--gray-500); border-bottom:2px solid var(--gray-200); }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div></div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Package Library
        </a>
        <a href="caterer-menu-items.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
          Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item logout-link" style="color:var(--gray-400);">
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
          <input type="text" placeholder="Search kitchen orders, menus, packages...">
        </div>
        <div class="topbar-actions">
          <a href="caterer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">5</span>
            🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#059669;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>
      <main class="page-body">

<div class="flex-between mb-24">
  <div>
    <h1>Kitchen Operations Center</h1>
    <p>Live accepted order queue — click any row to view full order details.</p>
  </div>
  <span style="font-size:.8rem; font-weight:600; display:flex; align-items:center; gap:6px;"><span class="live-dot"></span> Live Kitchen Feed</span>
</div>

<div class="stats-grid mb-24">
  <div class="stat-card green"><div class="stat-label">Active Orders</div><div class="stat-value"><?= $totalActive ?></div></div>
  <div class="stat-card"><div class="stat-label">Ready for Dispatch</div><div class="stat-value"><?= $readyCount ?></div></div>
  <div class="stat-card orange"><div class="stat-label">Total Packages</div><div class="stat-value"><?= count($recentOrders) ?></div></div>
  <div class="stat-card"><div class="stat-label">Completed This Week</div><div class="stat-value">—</div></div>
</div>

<!-- Active Kitchen Queue (DB-connected) -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Active Kitchen Queue</div>
    <span style="font-size:.75rem; font-weight:700; color:#16a34a; padding:3px 10px; background:#dcfce7; border-radius:20px;">ACCEPTED ORDERS</span>
  </div>

  <!-- Table header -->
  <div class="queue-grid queue-header">
    <span>ORDER</span>
    <span>EVENT &amp; VENUE</span>
    <span>PACKAGE</span>
    <span>STATUS</span>
  </div>

  <?php if (empty($activeOrders)): ?>
  <div style="padding:32px; text-align:center; color:var(--gray-500);">
    <div style="font-size:2rem; margin-bottom:8px;">🍽️</div>
    <div class="font-semibold">No accepted orders yet</div>
    <div class="text-sm mt-4">Orders you accept will appear here.</div>
  </div>
  <?php else: ?>
  <?php foreach ($activeOrders as $ord): ?>
  <a href="caterer-order-details.php?booking_id=<?= (int)$ord['booking_id'] ?>" class="order-row" style="display:block; text-decoration:none; color:inherit;">
    <div class="queue-grid">
      <span class="font-bold text-primary text-sm">#<?= (int)$ord['order_id'] ?></span>
      <div>
        <div class="font-semibold text-sm"><?= e($ord['event_name']) ?></div>
        <div class="text-xs text-muted"><?= e($ord['venue_name']) ?> — <?= (int)$ord['guest_count'] ?> guests</div>
      </div>
      <?= tierPill($ord['package_tier'] ?? $ord['package_title']) ?>
      <span class="pill pill-confirmed" style="font-size:.7rem;"><?= e($ord['preparation_status'] ?? 'Preparing') ?></span>
    </div>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Recent Orders -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Recent Orders</div>
  </div>
  <table class="table">
    <thead>
      <tr>
        <th>Order</th>
        <th>Event Name</th>
        <th>Venue</th>
        <th>Package</th>
        <th>Decision</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($recentOrders)): ?>
      <tr><td colspan="5" style="text-align:center; color:var(--gray-400); padding:24px;">No orders yet.</td></tr>
      <?php else: ?>
      <?php foreach ($recentOrders as $ro): ?>
      <tr>
        <td><a href="caterer-order-details.php?booking_id=<?= (int)$ro['booking_id'] ?>" class="font-bold text-primary">#<?= (int)$ro['order_id'] ?></a></td>
        <td><?= e($ro['event_name']) ?></td>
        <td><?= e($ro['venue_name']) ?></td>
        <td><?= tierPill($ro['package_tier'] ?? $ro['package_title']) ?></td>
        <td><?= decisionPill($ro['package_decision'] ?? 'pending') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

      </main>
      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>