<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$venueId = (int)($_GET['venue_id'] ?? (int)(session_id() ? ($_SESSION['vp_venue_id'] ?? 0) : 0));
$stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtV->execute([$venueId]);
$venue = $stmtV->fetch();

$guests = (int)($_GET['guests'] ?? ($_GET['guest_count'] ?? 0));
if ($guests <= 0 && !empty($_SESSION['vp_guests'])) {
    $guests = (int)$_SESSION['vp_guests'];
}
if ($guests <= 0) {
    $guests = 100;
}

$eventDate     = sanitize($_GET['date'] ?? ($_GET['event_date'] ?? ''));
$startTime     = sanitize($_GET['start_time'] ?? '10:00:00');
$endTime       = sanitize($_GET['end_time'] ?? '22:00:00');
$plan          = sanitize($_GET['plan'] ?? 'enterprise');
$planRate      = (float)($_GET['plan_rate'] ?? 0);
$durationHours = (int)($_GET['duration_hours'] ?? 12);
$format        = sanitize($_GET['format'] ?? '');

$queryParams = [
    'venue_id'       => $venueId,
    'guests'         => $guests,
    'guest_count'    => $guests,
    'date'           => $eventDate,
    'start_time'     => $startTime,
    'end_time'       => $endTime,
    'plan'           => $plan,
    'plan_rate'      => $planRate,
    'duration_hours' => $durationHours,
    'format'         => $format
];
$baseQuery = http_build_query($queryParams);

$catererId = (int)($_GET['caterer_id'] ?? 0);

// Resolve caterers assigned to this specific designated venue by admin
$venueName = $venue['name'] ?? '';
$venueIdStr = (string)$venueId;

$stmtCaterers = $db->prepare("
    SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
    FROM users u
    JOIN caterer_profiles cp ON u.id = cp.user_id
    JOIN catering_packages pkg ON pkg.caterer_id = u.id
    WHERE u.role = 'caterer' 
      AND u.status = 'active' 
      AND cp.approval_status = 'approved'
      AND pkg.status IN ('published', 'active')
      AND (
          cp.assigned_venue = ? 
          OR cp.assigned_venue = ? 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR cp.assigned_venue LIKE ?
      )
    ORDER BY cp.business_name ASC
");
$stmtCaterers->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
$caterersList = $stmtCaterers ? $stmtCaterers->fetchAll(PDO::FETCH_ASSOC) : [];
$assignedCatererIds = array_map('intval', array_column($caterersList, 'id'));

if ($catererId > 0 && !in_array($catererId, $assignedCatererIds, true)) {
    $catererId = 0;
}

// Fetch packages strictly from caterers assigned to this venue
$packages = [];
if (!empty($assignedCatererIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($assignedCatererIds), '?'));
    $sqlPkgs = "SELECT cp.*, u.name AS caterer_name, pr.business_name, pr.assigned_venue
        FROM catering_packages cp
        LEFT JOIN users u ON cp.caterer_id = u.id
        LEFT JOIN caterer_profiles pr ON u.id = pr.user_id
        WHERE cp.status IN ('published', 'active')
          AND cp.caterer_id IN ($inPlaceholders)";
    $pkgParams = $assignedCatererIds;

    if ($catererId > 0) {
        $sqlPkgs .= " AND cp.caterer_id = ?";
        $pkgParams[] = $catererId;
    }
    $sqlPkgs .= " ORDER BY cp.price_per_event ASC";
    $stmtPkgs = $db->prepare($sqlPkgs);
    $stmtPkgs->execute($pkgParams);
    $packages = $stmtPkgs->fetchAll(PDO::FETCH_ASSOC);
}
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
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
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
          <div class="flex-between mb-20">
            <div>
              <h1 style="font-size:1.9rem; font-weight:800; margin-bottom:4px;">Select Catering Package</h1>
              <p>Curated dining tiers and bespoke custom options for <strong><?= number_format($guests) ?> attendees</strong><?= $venue ? ' at ' . e($venue['name']) : '' ?><?= !empty($eventDate) ? ' on ' . date('M j, Y', strtotime($eventDate)) : '' ?>.</p>
            </div>
          </div>

          <?php if (count($caterersList) > 1 || $catererId > 0): ?>
          <div class="flex gap-8 mb-24 flex-wrap" style="align-items:center;">
            <span class="text-xs font-bold text-muted">Filter by Caterer:</span>
            <a href="booking-catering-packages.php?<?= $baseQuery ?>"
               class="btn btn-sm <?= $catererId <= 0 ? 'btn-primary' : 'btn-outline' ?>"
               style="border-radius:20px; font-size:0.75rem; padding:4px 14px;">
              All Caterers (<?= count($caterersList) ?>)
            </a>
            <?php foreach ($caterersList as $cat): ?>
            <a href="booking-catering-packages.php?<?= $baseQuery ?>&caterer_id=<?= $cat['id'] ?>"
               class="btn btn-sm <?= $catererId === (int)$cat['id'] ? 'btn-primary' : 'btn-outline' ?>"
               style="border-radius:20px; font-size:0.75rem; padding:4px 14px;">
              👨‍🍳 <?= e($cat['business_name'] ?: $cat['name']) ?>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Packages Grid - Dynamic from DB -->
          <div class="packages-grid mb-32">
<?php if (empty($packages)): ?>
<div style="grid-column:1/-1;text-align:center;padding:60px;background:#fff;border-radius:16px;border:1px dashed var(--gray-300);">
  <div style="font-size:3.5rem;margin-bottom:12px;">🍽️</div>
  <?php if ($catererId > 0): ?>
    <h3 style="font-size:1.25rem;font-weight:700;margin-bottom:8px;">No Packages from Selected Caterer</h3>
    <p style="color:var(--gray-500);max-width:500px;margin:0 auto 20px;">This caterer currently has no active packages published for this venue.</p>
    <div>
      <a href="booking-catering-packages.php?<?= $baseQuery ?>" class="btn btn-outline btn-sm">View All Assigned Caterers</a>
    </div>
  <?php else: ?>
    <h3 style="font-size:1.25rem;font-weight:700;margin-bottom:8px;">No Catering Assigned to This Venue</h3>
    <p style="color:var(--gray-500);max-width:520px;margin:0 auto 20px;">
      The administration has not assigned dedicated catering partners to <strong><?= e($venueName ?: 'this venue') ?></strong> yet. You can continue booking with self-catering or choose an alternative venue.
    </p>
    <div class="flex gap-12" style="justify-content:center;flex-wrap:wrap;">
      <a href="venues.php" class="btn btn-outline btn-sm">Explore Other Venues</a>
      <a href="booking-summary-payment.php?<?= $baseQuery ?>&no_catering=1" class="btn btn-primary btn-sm">Continue Without Catering →</a>
    </div>
  <?php endif; ?>
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
      <div class="flex-between mb-6" style="align-items:center;">
        <span class="pill pill-pending" style="font-size:0.68rem; font-weight:700;">👨‍🍳 <?= e($pkg['business_name'] ?: ($pkg['caterer_name'] ?: 'Culinary Partner')) ?></span>
        <?php if (!empty($pkg['assigned_venue'])): ?>
        <span class="text-xs text-muted" title="Assigned Venue(s)" style="font-size:0.7rem;">📍 <?= e($pkg['assigned_venue']) ?></span>
        <?php endif; ?>
      </div>
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
    <a href="booking-summary-payment.php?<?= $baseQuery ?>&package_id=<?= $pkg['id'] ?>&caterer_id=<?= $pkg['caterer_id'] ?>"
       class="<?= $style['btn'] ?>"
       onclick="sessionStorage.setItem('vp_package_id','<?= $pkg['id'] ?>');sessionStorage.setItem('vp_caterer_id','<?= $pkg['caterer_id'] ?>');sessionStorage.setItem('vp_venue_id','<?= $venueId ?>');sessionStorage.setItem('vp_guests','<?= $guests ?>');sessionStorage.setItem('vp_guest_count','<?= $guests ?>');sessionStorage.setItem('vp_date','<?= e($eventDate) ?>');sessionStorage.setItem('vp_event_date','<?= e($eventDate) ?>');sessionStorage.setItem('vp_start_time','<?= e($startTime) ?>');sessionStorage.setItem('vp_end_time','<?= e($endTime) ?>');sessionStorage.setItem('vp_duration_hours','<?= $durationHours ?>');sessionStorage.setItem('vp_is_custom_package','0');">
      Choose <?= e($pkg['title']) ?>
    </a>
  </div>
</div>
<?php endforeach; ?>

<?php if (!empty($assignedCatererIds)): 
  $customCatererId = ($catererId > 0 && in_array($catererId, $assignedCatererIds, true)) ? $catererId : $assignedCatererIds[0];
?>
<!-- 4th Tier: Custom Hybrid Package Builder (Multi-Package Selection) -->
<div class="package-card custom-tier">
  <div>
    <div class="pkg-popular-badge" style="background:#059669; color:#fff;">✨ FULLY BESPOKE</div>
    <div class="pkg-header">
      <div class="pkg-title">Custom Hybrid Package</div>
      <div class="pkg-price-block">
        <div class="pkg-price" style="color:#059669; font-size:1.35rem;">Mix &amp; Match <small class="text-muted" style="font-size:0.75rem;font-weight:500;">across tiers</small></div>
        <div class="pkg-subprice">Custom rates per chosen dish &amp; guest count</div>
      </div>
    </div>
    <div class="pkg-desc">Build your personal package by hand-picking favorite dishes freely across Gold, Platinum, and Signature menus.</div>
    <ul class="pkg-features">
      <li><span>✓</span> Choose any dishes across assigned caterer tiers</li>
      <li><span>✓</span> Real-time per-head dynamic calculator</li>
      <li><span>✓</span> Specific dietary &amp; chef prep accommodations</li>
      <li><span>✓</span> Line-by-line culinary kitchen review</li>
    </ul>
  </div>
  <div class="pkg-actions">
    <a href="caterer-custom-package.php?<?= $baseQuery ?>&caterer_id=<?= $customCatererId ?>"
       class="btn btn-primary btn-full btn-sm font-semibold"
       style="background:#059669; border-color:#059669;"
       onclick="sessionStorage.setItem('vp_venue_id','<?= $venueId ?>');sessionStorage.setItem('vp_caterer_id','<?= $customCatererId ?>');sessionStorage.setItem('vp_guests','<?= $guests ?>');sessionStorage.setItem('vp_guest_count','<?= $guests ?>');sessionStorage.setItem('vp_date','<?= e($eventDate) ?>');sessionStorage.setItem('vp_event_date','<?= e($eventDate) ?>');sessionStorage.setItem('vp_start_time','<?= e($startTime) ?>');sessionStorage.setItem('vp_end_time','<?= e($endTime) ?>');sessionStorage.setItem('vp_duration_hours','<?= $durationHours ?>');">
      Build Custom Package →
    </a>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  sessionStorage.setItem('vp_venue_id', '<?= $venueId ?>');
  sessionStorage.setItem('vp_guests', '<?= $guests ?>');
  sessionStorage.setItem('vp_guest_count', '<?= $guests ?>');
  if ('<?= e($eventDate) ?>') {
    sessionStorage.setItem('vp_date', '<?= e($eventDate) ?>');
    sessionStorage.setItem('vp_event_date', '<?= e($eventDate) ?>');
  }
  if ('<?= e($startTime) ?>') sessionStorage.setItem('vp_start_time', '<?= e($startTime) ?>');
  if ('<?= e($endTime) ?>') sessionStorage.setItem('vp_end_time', '<?= e($endTime) ?>');
  if ('<?= $durationHours ?>') sessionStorage.setItem('vp_duration_hours', '<?= $durationHours ?>');
});
</script>
</body>
</html>