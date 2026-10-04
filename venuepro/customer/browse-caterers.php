<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$stmtCat = $db->prepare(
    "SELECT u.id, u.name, cp.business_name, cp.specialization, cp.approval_status,
     COUNT(DISTINCT pkg.id) AS package_count,
     COALESCE(AVG(pkg.price_per_event), 0) AS avg_price
     FROM users u
     JOIN caterer_profiles cp ON u.id = cp.user_id
     LEFT JOIN catering_packages pkg ON u.id = pkg.caterer_id AND pkg.status = 'active'
     WHERE u.role = 'caterer' AND cp.approval_status = 'approved'
     GROUP BY u.id ORDER BY cp.business_name"
);
$stmtCat->execute();
$caterers = $stmtCat->fetchAll();
$catAvatarColors = ['#059669','#2563eb','#7c3aed','#f59e0b','#dc2626','#0284c7'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Browse Caterers</title>
  <link rel="stylesheet" href="../css/style.css">
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
    <h1>Certified Catering Partners</h1>
    <p>Select specialized culinary teams or build a custom bespoke menu package for your event.</p>
  </div>
  <a href="caterer-custom-package.php" class="btn btn-primary" style="background:#059669; border-color:#059669;">✨ Build Custom Package →</a>
</div>

<div class="grid-3 gap-20 mb-32">
<?php if (empty($caterers)): ?>
  <div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--gray-400);">
    <div style="font-size:3rem;margin-bottom:12px;">🍽️</div>
    <p>No caterers are available at the moment.</p>
  </div>
<?php else: foreach ($caterers as $ci => $cat):
  $bg      = $catAvatarColors[$ci % count($catAvatarColors)];
  $initials= strtoupper(substr($cat['business_name'] ?: $cat['name'], 0, 2));
  $avgPriceF = number_format($cat['avg_price'], 0);
  $specs   = explode(',', $cat['specialization'] ?? 'Fine Dining');
?>
  <div class="card" style="display:flex;flex-direction:column;gap:12px;">
    <div style="display:flex;align-items:center;gap:16px;">
      <div style="width:56px;height:56px;border-radius:50%;background:<?= $bg ?>;color:#fff;
                  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;flex-shrink:0;">
        <?= e($initials) ?>
      </div>
      <div>
        <div class="font-bold" style="font-size:1rem;"><?= e($cat['business_name'] ?: $cat['name']) ?></div>
        <div class="text-xs text-muted"><?= e($cat['name']) ?></div>
      </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;">
      <?php foreach($specs as $spec): ?>
      <span class="pill pill-confirmed" style="font-size:.68rem;padding:2px 8px;"><?= e(trim($spec)) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="flex-between text-sm text-muted">
      <span>📦 <?= (int)$cat['package_count'] ?> Packages</span>
      <?php if ($cat['avg_price'] > 0): ?>
      <span>Avg: $<?= $avgPriceF ?>/event</span>
      <?php endif; ?>
    </div>
    <a href="booking-catering-packages.php?caterer_id=<?= $cat['id'] ?>"
       class="btn btn-outline btn-sm" style="margin-top:auto;">
      View Packages →
    </a>
  </div>
<?php endforeach; endif; ?>
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