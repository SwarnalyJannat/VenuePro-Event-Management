<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();

$catererFilter = (int)($_GET['caterer_id'] ?? 0);

// Fetch approved caterers offering active packages for filter tabs
$stmtCaterers = $db->query("
    SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
    FROM users u
    JOIN caterer_profiles cp ON u.id = cp.user_id
    JOIN catering_packages pkg ON pkg.caterer_id = u.id
    WHERE u.role = 'caterer' AND u.status = 'active' AND pkg.status IN ('published', 'active')
    ORDER BY cp.business_name ASC
");
$caterersList = $stmtCaterers ? $stmtCaterers->fetchAll(PDO::FETCH_ASSOC) : [];

$sqlPkgs = "SELECT cp.*, u.name AS caterer_name, pr.business_name, pr.assigned_venue
    FROM catering_packages cp
    LEFT JOIN users u ON cp.caterer_id = u.id
    LEFT JOIN caterer_profiles pr ON u.id = pr.user_id
    WHERE cp.status IN ('published', 'active')";
$pkgParams = [];
if ($catererFilter > 0) {
    $sqlPkgs .= " AND cp.caterer_id = ?";
    $pkgParams[] = $catererFilter;
}
$sqlPkgs .= " ORDER BY cp.price_per_event ASC";
$stmtPkgs = $db->prepare($sqlPkgs);
$stmtPkgs->execute($pkgParams);
$packages = $stmtPkgs ? $stmtPkgs->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Catering Packages (Guest Mode)</title>
  <link rel="stylesheet" href="css/style.css">
  <style>

    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(5px);
    }
    .modal-backdrop:target { display: flex; }
    .modal-card {
      background: #fff; border-radius: var(--radius); padding: 32px;
      max-width: 460px; width: 90%; box-shadow: var(--shadow-xl);
      text-align: center; position: relative; animation: modalPop 0.2s ease-out;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .modal-icon {
      width: 58px; height: 58px; border-radius: 50%;
      background: #eff6ff; color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin: 0 auto 16px;
    }
    .modal-close {
      position: absolute; top: 16px; right: 16px; text-decoration: none;
      color: var(--gray-400); font-size: 1.2rem; font-weight: 700;
      width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
      border-radius: 50%; background: var(--gray-100);
    }
    .modal-close:hover { background: var(--gray-200); color: var(--gray-800); }
    
    .guest-banner-bar {
      background: #eff6ff; border-bottom: 1px solid #bfdbfe;
      padding: 10px 48px; display: flex; align-items: center; justify-content: space-between;
      font-size: 0.85rem; color: var(--primary);
    }

    .public-page { max-width: 1240px; margin: 0 auto; padding: 32px 24px 60px; }
    .packages-grid {
      display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; align-items: stretch;
    }
    @media (max-width: 1024px) { .packages-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .packages-grid { grid-template-columns: 1fr; } }

    .package-card {
      display: flex; flex-direction: column; justify-content: space-between;
      height: 100%; padding: 24px; border-radius: var(--radius);
      border: 2px solid var(--gray-200); background: #fff; transition: all 0.2s ease;
    }
    .package-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary); }
    .package-card.highlight { border-color: var(--primary); background: #eff6ff; }
    .package-card.custom-tier { border-color: #059669; background: #f0fdf4; }
    .package-card.signature-tier { border-color: #8b5cf6; background: #faf5ff; }
    .pkg-header { min-height: 110px; margin-bottom: 12px; }
    .pkg-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; color: var(--gray-900); }
    .pkg-price-block { margin-bottom: 12px; min-height: 48px; }
    .pkg-price { font-size: 1.5rem; font-weight: 800; color: var(--primary); line-height: 1.1; }
    .pkg-subprice { font-size: 0.75rem; color: var(--gray-500); margin-top: 3px; }
    .pkg-desc { font-size: 0.8rem; color: var(--gray-600); line-height: 1.45; min-height: 52px; margin-bottom: 16px; }
    .pkg-features { list-style: none; padding: 0; margin: 0 0 20px; flex-grow: 1; display: flex; flex-direction: column; gap: 8px; }
    .pkg-features li { font-size: 0.78rem; color: var(--gray-700); line-height: 1.4; display: flex; align-items: flex-start; gap: 6px; }
    .pkg-features li span { color: #16a34a; font-weight: 700; flex-shrink: 0; }
  </style>
</head>
<body style="background:#f8fafc;">

  <header class="landing-header" style="display:flex; align-items:center; justify-content:space-between; padding:18px 48px; background:#fff; border-bottom:1px solid var(--gray-200); position:sticky; top:0; z-index:100;">
    <div class="flex-center gap-12">
      <a href="index.php" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
        <img src="assets/logo.png" alt="VenuePro" class="header-logo-img">
        <div>
          <div style="font-size:1.2rem; font-weight:800; color:var(--gray-900);">VenuePro</div>
          <div style="font-size:0.7rem; color:var(--gray-500); font-weight:500;">Enterprise Event Platform</div>
        </div>
      </a>
    </div>
    <nav class="flex-center gap-20">
      <a href="venues.php" style="color:var(--gray-700); font-weight:600; text-decoration:none; font-size:0.9rem;">Venues</a>
      <a href="packages.php" style="color:var(--gray-700); font-weight:600; text-decoration:none; font-size:0.9rem;">Catering Packages</a>
      <a href="login-role.php" class="btn btn-outline btn-sm" style="font-weight:600;">Sign In</a>
      <a href="signup-role.php" class="btn btn-primary btn-sm" style="font-weight:600;">Sign Up</a>
    </nav>
  </header>

  <div class="guest-banner-bar" style="justify-content:center;">
    <div class="flex-center gap-8">
      <span>👋</span>
      <span><strong>Guest Browsing Mode:</strong> Explore our curated catering tiers and bespoke custom menus. Sign in to place orders.</span>
    </div>
    
  </div>

  <main class="public-page">
    <div class="flex-between mb-24">
      <div>
        <h1 style="font-size:2.2rem; font-weight:800; margin-bottom:6px;">Catering Packages &amp; Menus</h1>
        <p>Pre-configured dining tiers and bespoke custom packages prepared by our certified commercial partner kitchens.</p>
      </div>
      <a href="venues.php" class="btn btn-outline btn-sm">Explore Venues →</a>
    </div>

    <?php if (count($caterersList) > 1 || $catererFilter > 0): ?>
    <div class="flex gap-8 mb-24 flex-wrap" style="align-items:center;">
      <span class="text-xs font-bold text-muted">Filter by Caterer:</span>
      <a href="packages.php"
         class="btn btn-sm <?= $catererFilter <= 0 ? 'btn-primary' : 'btn-outline' ?>"
         style="border-radius:20px; font-size:0.75rem; padding:4px 14px;">
        All Caterers (<?= count($caterersList) ?>)
      </a>
      <?php foreach ($caterersList as $cat): ?>
      <a href="packages.php?caterer_id=<?= $cat['id'] ?>"
         class="btn btn-sm <?= $catererFilter === (int)$cat['id'] ? 'btn-primary' : 'btn-outline' ?>"
         style="border-radius:20px; font-size:0.75rem; padding:4px 14px;">
        👨‍🍳 <?= e($cat['business_name'] ?: $cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="packages-grid mb-32">
      <?php if (empty($packages)): ?>
      <div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--gray-400);">No catering packages currently available from this caterer.</div>
      <?php else:
      $tierStyles = [
        0 => ['class' => 'package-card',                'badge' => 'CLASSIC TIER',    'badge_class' => 'stat-badge neutral mb-8', 'btn' => 'btn btn-outline btn-full btn-sm font-semibold'],
        1 => ['class' => 'package-card highlight',       'badge' => 'MOST REQUESTED',  'badge_class' => 'stat-badge positive mb-8', 'btn' => 'btn btn-primary btn-full btn-sm font-semibold'],
        2 => ['class' => 'package-card signature-tier',  'badge' => "CHEF'S TASTING",  'badge_class' => 'stat-badge neutral mb-8" style="background:#ede9fe; color:#6d28d9;', 'btn' => 'btn btn-outline btn-full btn-sm font-semibold" style="border-color:#8b5cf6; color:#6d28d9;'],
      ];
      foreach ($packages as $pi => $pkg):
        $st = $tierStyles[$pi % 3];
        $ppEvent = number_format((float)($pkg['price_per_event'] ?? 0), 0);
        $minG = (int)($pkg['min_guests'] ?? 30);
        $perHead = $minG > 0 ? number_format((float)($pkg['price_per_event'] ?? 0) / $minG, 2) : '0.00';
        $features = [];
        if (!empty($pkg['features'])) {
            $features = is_array($pkg['features']) ? $pkg['features'] : (json_decode($pkg['features'], true) ?: []);
        }
        if (empty($features)) {
            $features = ['Multi-course chef preparation', 'Artisanal beverage service', 'Complete banquet setup & linens'];
        }
      ?>
      <div class="<?= $st['class'] ?>">
        <div>
          <div class="pkg-header">
            <div class="flex-between mb-8" style="align-items:center;">
              <span class="<?= $st['badge_class'] ?>" style="margin-bottom:0;"><?= $st['badge'] ?></span>
              <span class="pill pill-pending" style="font-size:0.68rem; font-weight:700;">👨‍🍳 <?= e($pkg['business_name'] ?: ($pkg['caterer_name'] ?: 'Culinary Partner')) ?></span>
            </div>
            <div class="pkg-title"><?= e($pkg['title']) ?></div>
            <div class="pkg-price-block">
              <div class="pkg-price">$<?= $ppEvent ?> <small class="text-muted" style="font-size:0.8rem; font-weight:500;">/ event</small></div>
              <div class="pkg-subprice">$<?= $perHead ?> per guest · Min <?= $minG ?> guests</div>
            </div>
          </div>
          <div class="pkg-desc"><?= e($pkg['description'] ?? '') ?></div>
          <ul class="pkg-features">
            <?php foreach (array_slice($features, 0, 4) as $feat): ?>
            <li><span>✓</span> <?= e($feat) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div>
          <a href="#signin-modal" class="<?= $st['btn'] ?>">🔒 Sign In to Order</a>
        </div>
      </div>
      <?php endforeach; endif; ?>

      <!-- Card 4: Bespoke Custom Hybrid Package -->
      <div class="package-card custom-tier">
        <div>
          <div class="pkg-header">
            <span class="stat-badge neutral mb-8" style="background:#dcfce7; color:#15803d;">BESPOKE BUILDER</span>
            <div class="pkg-title">Custom Package</div>
            <div class="pkg-price-block">
              <div class="pkg-price">Flexible <small class="text-muted" style="font-size:0.8rem; font-weight:500;">/ per head</small></div>
              <div class="pkg-subprice">Custom guest count · Tailored pricing</div>
            </div>
          </div>
          <div class="pkg-desc">Build your dream menu from scratch: choose global cuisines, multi-course dishes, and dietary specs.</div>
          <ul class="pkg-features">
            <li><span>✓</span> Choose from 6 World-Class Culinary Cuisines</li>
            <li><span>✓</span> Hand-Pick Each Appetizer, Entrée &amp; Dessert</li>
            <li><span>✓</span> Halal, Vegan, Celiac &amp; Nut Allergy Protocols</li>
            <li><span>✓</span> Dynamic Live Cost Calculator &amp; Stepper</li>
          </ul>
        </div>
        <div>
          <a href="#signin-modal" class="btn btn-primary btn-full btn-sm font-semibold" style="background:#059669; border-color:#059669;">🔒 Sign In to Customize</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Sign In Required Modal (for Guest Mode) -->
  <div id="signin-modal" class="modal-backdrop">
    <div class="modal-card">
      <a href="#" class="modal-close">✕</a>
      <div class="modal-icon">🔒</div>
      <h3 style="font-size:1.35rem; font-weight:800; margin-bottom:8px; color:var(--gray-900);">Sign In Required</h3>
      <p class="text-sm text-muted mb-20">You are browsing in <strong>Guest Mode</strong>. You can view all venues, inspect high-resolution photo galleries, and explore catering menus freely.<br><br>To place a reservation or order catering, please sign in to your account.</p>
      <div class="flex gap-12 mb-12">
        <a href="venues.php" class="btn btn-primary btn-full font-bold">Sign In to Continue →</a>
        <a href="signup-role.php" class="btn btn-outline btn-full font-semibold">Create Account</a>
      </div>
      <div class="text-xs text-muted">Authorized access for Customers, Staff, Caterers &amp; Admins.</div>
    </div>
  </div>

        <footer class="page-footer">
    <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
  </footer>
<script src="js/app.js"></script>
</body>
</html>