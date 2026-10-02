<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();

$pkgId = (int)($_GET['id'] ?? 0);
$pkg = null;
if ($pkgId > 0) {
    $stmt = $db->prepare("SELECT cp.*, u.name AS caterer_name FROM catering_packages cp LEFT JOIN users u ON cp.caterer_id = u.id WHERE cp.id = ? LIMIT 1");
    $stmt->execute([$pkgId]);
    $pkg = $stmt->fetch();
}

if (!$pkg) {
    $stmt = $db->query("SELECT cp.*, u.name AS caterer_name FROM catering_packages cp LEFT JOIN users u ON cp.caterer_id = u.id ORDER BY cp.id ASC LIMIT 1");
    $pkg = $stmt ? $stmt->fetch() : null;
    $pkgId = $pkg ? (int)$pkg['id'] : 0;
}

if (!$pkg) {
    header('Location: caterer-food-packages.php');
    exit;
}

// Fetch features list
$features = [];
if (!empty($pkg['features'])) {
    $features = is_array($pkg['features']) ? $pkg['features'] : (json_decode($pkg['features'], true) ?? []);
}

// Fetch menu course items if available in DB
$stmtItems = $db->prepare("SELECT * FROM package_menu_items WHERE package_id = ? ORDER BY course ASC, id ASC");
$stmtItems->execute([$pkg['id']]);
$menuItems = $stmtItems->fetchAll();

// Group menu items by course
$courses = [];
if (!empty($menuItems)) {
    foreach ($menuItems as $item) {
        $courses[$item['course']][] = $item;
    }
}

// Fetch booking statistics
$stmtStats = $db->prepare("SELECT COUNT(*) AS total_bk, COALESCE(SUM(total_amount), 0) AS total_rev FROM bookings WHERE package_id = ? AND booking_status != 'cancelled'");
$stmtStats->execute([$pkg['id']]);
$stats = $stmtStats->fetch();
$totalBookings = (int)($stats['total_bk'] ?? 0);
$totalRevenue = (float)($stats['total_rev'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Package Details: <?= e($pkg['title']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .sidebar-logo-sub { color: #10b981; }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub">Kitchen Operations</div>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg> Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="caterer-food-packages.php">Packages</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current"><?= e($pkg['title']) ?></span>
        </div>

        <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap:28px;">
          <div>
            <div class="flex-between mb-16">
              <div>
                <span class="pill pill-<?= ($pkg['status'] ?? 'published') === 'published' ? 'confirmed' : 'pending' ?> mb-4">
                  <?= strtoupper(e($pkg['tier'] ?? 'TIER')) ?> · <?= strtoupper(e($pkg['status'] ?? 'PUBLISHED')) ?>
                </span>
                <h1><?= e($pkg['title']) ?></h1>
                <p><?= e($pkg['description'] ?? 'Full banquet dining service package.') ?></p>
              </div>
              <a href="caterer-edit-package.php?id=<?= $pkg['id'] ?>" class="btn btn-outline btn-sm">Edit Package</a>
            </div>

            <div class="card mb-24">
              <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
                <div>
                  <div class="text-xs text-muted font-bold" style="text-transform:uppercase;">Package Specifications</div>
                  <div class="font-semibold text-sm"><?= e($pkg['cuisine_type'] ?? 'Fine Dining') ?> • <?= e($pkg['service_style'] ?? 'Plated Service') ?></div>
                </div>
                <div class="text-right">
                  <div class="text-xs text-muted font-bold" style="text-transform:uppercase;">Guest Range</div>
                  <div class="font-semibold text-sm">Min <?= (int)$pkg['min_guests'] ?> – Max <?= (int)$pkg['max_capacity'] ?> guests</div>
                </div>
              </div>

              <h3 class="mb-12">Included Courses &amp; Menu Inclusions</h3>
              <?php if (!empty($courses)): ?>
                <?php foreach ($courses as $cName => $items): ?>
                  <div class="mb-16">
                    <div class="font-bold text-sm text-primary mb-4"><?= e(ucfirst($cName)) ?></div>
                    <ul style="padding-left:18px; margin:0;" class="text-sm">
                      <?php foreach ($items as $it): ?>
                        <li><?= e($it['item_name']) ?> <?= !empty($it['notes']) ? '<span class="text-xs text-muted">(' . e($it['notes']) . ')</span>' : '' ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endforeach; ?>
              <?php elseif (!empty($features)): ?>
                <ul style="padding-left:18px; margin:0;" class="text-sm">
                  <?php foreach ($features as $f): ?>
                    <li style="margin-bottom:6px;"><?= e(is_string($f) ? $f : ($f['name'] ?? '')) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <div class="mb-16">
                  <div class="font-bold text-sm text-primary mb-4">Course 1: Passed Hors d'oeuvres</div>
                  <p class="text-sm">Handcrafted canapés, artisanal mini tartlets, seasonal savory bites.</p>
                </div>
                <div class="mb-16">
                  <div class="font-bold text-sm text-primary mb-4">Course 2: Plated Entree</div>
                  <p class="text-sm">Premium cut entrees with gourmet sides, seasonal greens, and dietary accommodations.</p>
                </div>
                <div>
                  <div class="font-bold text-sm text-primary mb-4">Course 3: Dessert &amp; Coffee</div>
                  <p class="text-sm">Artisan pastry selection, dessert station, and roasted espresso bar service.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <div class="card mb-20" style="background:var(--gray-50);">
              <h3 class="mb-16">Pricing Breakdown</h3>
              <div class="flex-between text-sm mb-8">
                <span class="text-muted">Base Event Price</span>
                <span class="font-bold text-primary" style="font-size:1.15rem;">$<?= number_format((float)$pkg['price_per_event'], 2) ?></span>
              </div>
              <div class="flex-between text-sm mb-16">
                <span class="text-muted">Per Guest Rate</span>
                <span class="font-semibold">$<?= number_format((float)$pkg['price_per_guest'], 2) ?> / guest</span>
              </div>
              <div class="flex-between text-sm mb-8">
                <span class="text-muted">Minimum Order Total</span>
                <span class="font-semibold">$<?= number_format((float)($pkg['min_guests'] * $pkg['price_per_guest'] + $pkg['price_per_event']), 2) ?></span>
              </div>
            </div>

            <div class="card mb-20">
              <h3 class="mb-16">Performance Metrics</h3>
              <div class="flex-between text-sm mb-8"><span class="text-muted">Total Bookings</span><span class="font-bold"><?= $totalBookings ?> events</span></div>
              <div class="flex-between text-sm mb-16"><span class="text-muted">Generated Revenue</span><span class="font-bold text-success">$<?= number_format($totalRevenue, 2) ?></span></div>
              <a href="caterer-food-packages.php" class="btn btn-outline btn-full mb-8">← Back to Package Library</a>
              <a href="caterer-dashboard.php" class="btn btn-primary btn-full" style="background:#059669; border-color:#059669;">Go to Kitchen Queue</a>
            </div>
          </div>
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