<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$venueId = (int)($_GET['venue_id'] ?? (int)(session_id() ? ($_SESSION['vp_venue_id'] ?? 0) : 0));
// Fetch all active catering packages
$stmtPkgs = $db->prepare("SELECT cp.*, u.name AS caterer_name, cp.caterer_id
    FROM catering_packages cp
    JOIN users u ON cp.caterer_id = u.id
    WHERE cp.status = 'active'
    ORDER BY cp.price_per_event ASC");
$stmtPkgs->execute();
$packages = $stmtPkgs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Catering Packages</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* 4-Card Aligned Grid */
    .packages-container {
      max-width: 1240px; margin: 0 auto;
    }
    .packages-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      align-items: stretch;
    }
    @media (max-width: 1024px) {
      .packages-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
      .packages-grid { grid-template-columns: 1fr; }
    }

    .package-card {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
      padding: 24px;
      border-radius: var(--radius);
      border: 2px solid var(--gray-200);
      background: #fff;
      transition: all 0.2s ease;
      position: relative;
    }
    .package-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
      border-color: var(--primary);
    }
    .package-card.highlight {
      border-color: var(--primary);
      background: #eff6ff;
    }
    .package-card.custom-tier {
      border-color: #059669;
      background: #f0fdf4;
    }
    .package-card.signature-tier {
      border-color: #8b5cf6;
      background: #faf5ff;
    }

    .pkg-header { min-height: 110px; margin-bottom: 12px; }
    .pkg-badge { margin-bottom: 10px; display: inline-block; }
    .pkg-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; color: var(--gray-900); }
    .pkg-price-block { margin-bottom: 12px; min-height: 48px; }
    .pkg-price { font-size: 1.5rem; font-weight: 800; color: var(--primary); line-height: 1.1; }
    .pkg-subprice { font-size: 0.75rem; color: var(--gray-500); margin-top: 3px; }
    .pkg-desc { font-size: 0.8rem; color: var(--gray-600); line-height: 1.45; min-height: 52px; margin-bottom: 16px; }
    
    .pkg-features {
      list-style: none; padding: 0; margin: 0 0 20px;
      flex-grow: 1; display: flex; flex-direction: column; gap: 8px;
    }
    .pkg-features li {
      font-size: 0.78rem; color: var(--gray-700); line-height: 1.4;
      display: flex; align-items: flex-start; gap: 6px;
    }
    .pkg-features li span { color: #16a34a; font-weight: 700; flex-shrink: 0; }
    .pkg-actions { margin-top: auto; }

    /* Modal Styles for Sign In Gate */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(4px);
    }
    .modal-backdrop:target { display: flex; }
    .modal-card {
      background: #fff; border-radius: var(--radius); padding: 32px;
      max-width: 440px; width: 90%; box-shadow: var(--shadow-xl);
      text-align: center; position: relative; animation: modalPop 0.25s ease-out;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .modal-icon {
      width: 56px; height: 56px; border-radius: 50%;
      background: #eff6ff; color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px; margin: 0 auto 16px;
    }
    .modal-close {
      position: absolute; top: 16px; right: 16px; text-decoration: none;
      color: var(--gray-400); font-size: 1.2rem; font-weight: 700;
      width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
      border-radius: 50%; background: var(--gray-100);
    }
    .modal-close:hover { background: var(--gray-200); color: var(--gray-800); }

    
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
        <!-- Steps Indicator -->
        <div class="steps mb-24">
          <div class="step done">
            <div class="step-circle">✓</div>
            <div class="step-label">Venue</div>
          </div>
          <div class="step-line done"></div>
          <div class="step active">
            <div class="step-circle">2</div>
            <div class="step-label">Catering Packages</div>
          </div>
          <div class="step-line"></div>
          <div class="step">
            <div class="step-circle">3</div>
            <div class="step-label">Payment</div>
          </div>
        </div>

        <div class="packages-container">
          <!-- Page Header -->
          <div class="flex-between mb-24">
            <div>
              <h1 style="font-size:1.9rem; font-weight:800; margin-bottom:4px;">Select Catering Package</h1>
              <p>Curated dining tiers and bespoke custom options prepared by our certified culinary kitchens.</p>
            </div>
            
          </div>

          <!-- Packages Grid - Dynamic from DB -->
          <div class="packages-grid mb-32">
<?php if (empty($packages)): ?>
<div style="grid-column:1/-1;text-align:center;padding:60px;color:var(--gray-400);">
  <div style="font-size:3rem;">🍽️</div>
  <p>No catering packages available at the moment.</p>
</div>
<?php else:
$tierStyles = [
  0 => ['class'=>'package-card',           'btn'=>'btn btn-outline btn-full btn-sm font-semibold'],
  1 => ['class'=>'package-card highlight',  'btn'=>'btn btn-primary btn-full btn-sm font-semibold'],
  2 => ['class'=>'package-card signature-tier','btn'=>'btn btn-outline btn-full btn-sm font-semibold'],
];
foreach ($packages as $pi => $pkg):
  $style   = $tierStyles[$pi % 3];
  $popular = ($pi === 1) ? '<div class="pkg-popular-badge">MOST POPULAR</div>' : '';
  $ppHead  = number_format($pkg['price_per_event'] ?? 0, 0);
  $ppGuest = !empty($pkg['min_guests']) ? number_format($pkg['price_per_event'] / max(1,$pkg['min_guests']), 2) : '0.00';
  $minG    = $pkg['min_guests'] ?? 30;
?>
<div class="<?= $style['class'] ?>">
  <div>
    <?= $popular ?>
    <div class="pkg-header">
      <div class="pkg-title"><?= e($pkg['title']) ?></div>
      <div class="pkg-price-block">
        <div class="pkg-price">$<?= $ppHead ?> <small class="text-muted" style="font-size:0.8rem;font-weight:500;">/&nbsp;event</small></div>
        <div class="pkg-subprice">$<?= $ppGuest ?> per guest · Min <?= $minG ?> guests</div>
      </div>
    </div>
    <div class="pkg-desc"><?= e($pkg['description'] ?? '') ?></div>
    <ul class="pkg-features">
<?php
  $features = [];
  if (!empty($pkg['features'])) { $features = is_array($pkg['features']) ? $pkg['features'] : json_decode($pkg['features'], true) ?? []; }
  if (empty($features)) { $features = ["Professional service staff","Complete table setup","Full menu as described"]; }
  foreach ((array)$features as $feat): ?>
      <li><span>✓</span> <?= e(is_string($feat) ? $feat : ($feat['name'] ?? '')) ?></li>
<?php endforeach; ?>
    </ul>
  </div>
  <div class="pkg-actions">
    <a href="booking-summary-payment.php?venue_id=<?= $venueId ?>&package_id=<?= $pkg['id'] ?>"
       class="<?= $style['btn'] ?>"
       onclick="sessionStorage.setItem('vp_package_id','<?= $pkg['id'] ?>');sessionStorage.setItem('vp_venue_id','<?= $venueId ?>');">
      Choose <?= e($pkg['title']) ?>
    </a>
  </div>
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