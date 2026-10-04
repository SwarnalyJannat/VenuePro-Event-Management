<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();
?>
<?php
$catUser = $currentUser;
$stmtMP  = $db->prepare(
    "SELECT cp.*, COUNT(b.id) AS active_bookings
     FROM catering_packages cp
     LEFT JOIN bookings b ON b.package_id = cp.id AND b.booking_status IN ('confirmed','pending')
     WHERE cp.caterer_id = ?
     GROUP BY cp.id
     ORDER BY cp.created_at DESC"
);
$stmtMP->execute([$catUser['id']]);
$myPackages = $stmtMP->fetchAll();
if (empty($myPackages)) {
    $stmtAllP = $db->query(
        "SELECT cp.*, COUNT(b.id) AS active_bookings
         FROM catering_packages cp
         LEFT JOIN bookings b ON b.package_id = cp.id AND b.booking_status IN ('confirmed','pending')
         GROUP BY cp.id
         ORDER BY cp.id ASC"
    );
    $myPackages = $stmtAllP->fetchAll();
}
$tierPills  = ['Gold'=>'pill-pending','Platinum'=>'pill-confirmed','Signature'=>'pill-inquiry','Custom'=>'pill-inquiry'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Caterer – Food Packages Library</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .menu-section { margin-bottom:14px; padding:12px 14px; background:var(--gray-50); border-radius:var(--radius-sm); }
    .menu-section-title { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--primary); margin-bottom:6px; }
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
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item active">
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
    <h1>Food Packages Library</h1>
    <p>Manage all your catering packages available on the VenuePro marketplace.</p>
  </div>
  <a href="caterer-create-package-1.php" class="btn btn-primary" style="background:#059669; border-color:#059669;">+ Create New Package</a>
</div>

<div class="grid-3 gap-20">
<?php if (empty($myPackages)): ?>
  <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--gray-400);">
    <div style="font-size:3rem;margin-bottom:12px;">📦</div>
    <p class="mb-16">You haven't created any packages yet.</p>
    <a href="caterer-create-package-1.php" class="btn btn-primary" style="background:#059669;border-color:#059669;">+ Create Your First Package</a>
  </div>
<?php else: foreach ($myPackages as $pkg):
  $tierKey   = ucfirst(strtolower(explode(' ',$pkg['title'])[0]));
  $pillClass = $tierPills[$tierKey] ?? 'pill-pending';
  $price     = number_format($pkg['price_per_event'] ?? 0, 0);
  $ppGuest   = $pkg['min_guests'] > 0 ? number_format($pkg['price_per_event'] / $pkg['min_guests'], 2) : '0.00';
  $features  = [];
  if (!empty($pkg['features'])) { $features = is_array($pkg['features']) ? $pkg['features'] : (json_decode($pkg['features'], true) ?? []); }
  if (empty($features)) { $features = ['Professional service','Complete table setup','Full menu as described']; }
?>
  <div class="card" <?= ($pkg['status'] ?? 'active') === 'active' ? 'style="border:2px solid #059669;"' : '' ?>>
    <div class="flex-between mb-8">
      <span class="pill <?= $pillClass ?>"><?= strtoupper(e($pkg['tier'] ?? $pkg['title'])) ?></span>
      <span class="text-sm font-bold text-success">$<?= $price ?> / event</span>
    </div>
    <h3 class="mb-4"><?= e($pkg['title']) ?></h3>
    <div class="text-xs text-muted mb-8">$<?= $ppGuest ?> per guest · Min <?= (int)$pkg['min_guests'] ?> guests</div>
    <div class="menu-section">
      <div class="menu-section-title">Package Includes</div>
      <?php foreach(array_slice((array)$features,0,3) as $f): ?>
      <div class="text-xs">• <?= e(is_string($f) ? $f : ($f['name'] ?? $f)) ?></div>
      <?php endforeach; ?>
    </div>
    <div class="flex-between text-xs text-muted mb-16">
      <span><?= (int)$pkg['active_bookings'] ?> active bookings</span>
      <span class="pill <?= ($pkg['status'] ?? 'active') === 'active' ? 'pill-confirmed' : 'pill-cancelled' ?>" style="font-size:.65rem;"><?= strtoupper($pkg['status'] ?? 'active') ?></span>
    </div>
    <div class="flex gap-8">
      <a href="package-details.php?id=<?= $pkg['id'] ?>" class="btn btn-outline btn-sm" style="flex:1;">View Details</a>
      <a href="caterer-edit-package.php?id=<?= $pkg['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
    </div>
  </div>
<?php endforeach; endif; ?>
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