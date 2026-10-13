<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$venueId = (int)($_GET['venue_id'] ?? (session_id() ? ($_SESSION['vp_venue_id'] ?? 1) : 1));
$stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtV->execute([$venueId]);
$venue = $stmtV->fetch() ?: $db->query("SELECT * FROM venues ORDER BY id ASC LIMIT 1")->fetch();
$venueId = (int)$venue['id'];

$guestCount = (int)($_GET['guests'] ?? ($_GET['guest_count'] ?? 0));
if ($guestCount <= 0 && !empty($_SESSION['vp_guests'])) {
    $guestCount = (int)$_SESSION['vp_guests'];
}
if ($guestCount <= 0) {
    $guestCount = 100;
}
$eventDate = sanitize($_GET['date'] ?? ($_GET['event_date'] ?? ''));
$startTime = sanitize($_GET['start_time'] ?? '10:00:00');
$endTime   = sanitize($_GET['end_time'] ?? '22:00:00');

// Determine caterer strictly (from GET parameter, assigned venue, or approved caterers for this venue)
$venueName = $venue['name'] ?? '';
$venueIdStr = (string)$venueId;

$stmtAssigned = $db->prepare("
    SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
    FROM users u
    JOIN caterer_profiles cp ON u.id = cp.user_id
    WHERE u.role = 'caterer' 
      AND u.status = 'active' 
      AND cp.approval_status = 'approved'
      AND (
          cp.assigned_venue = ? 
          OR cp.assigned_venue = ? 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR cp.assigned_venue LIKE ?
      )
    ORDER BY cp.business_name ASC
");
$stmtAssigned->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
$assignedCaterers = $stmtAssigned ? $stmtAssigned->fetchAll(PDO::FETCH_ASSOC) : [];
$assignedCatererIds = array_map('intval', array_column($assignedCaterers, 'id'));

$catererId = (int)($_GET['caterer_id'] ?? 0);
if ($catererId > 0 && !in_array($catererId, $assignedCatererIds, true)) {
    $catererId = 0;
}
if ($catererId <= 0 && !empty($assignedCatererIds)) {
    $catererId = $assignedCatererIds[0];
}

$catererProfile = null;
$packages = [];
$allItems = [];
$catererBizName = '';
$catererChefName = '';

if ($catererId > 0) {
    // Get Caterer Profile
    $stmtCp = $db->prepare("SELECT cp.*, u.name as owner_name, u.email as caterer_email FROM caterer_profiles cp JOIN users u ON cp.user_id = u.id WHERE u.id = ? LIMIT 1");
    $stmtCp->execute([$catererId]);
    $catererProfile = $stmtCp->fetch();
    $catererBizName = $catererProfile['business_name'] ?? 'Partner Catering Kitchen';
    $catererChefName = $catererProfile['owner_name'] ?? 'Executive Chef';

    // Fetch all published packages strictly for this caterer
    $stmtPkgs = $db->prepare("SELECT * FROM catering_packages WHERE caterer_id = ? AND status IN ('published', 'active') ORDER BY id ASC");
    $stmtPkgs->execute([$catererId]);
    $packages = $stmtPkgs->fetchAll();

    // Fetch all dishes across this caterer's packages
    $stmtItems = $db->prepare("
        SELECT pmi.*, cp.title AS package_title, cp.tier AS package_tier, cp.price_per_guest AS pkg_per_guest
        FROM package_menu_items pmi
        JOIN catering_packages cp ON pmi.package_id = cp.id
        WHERE cp.caterer_id = ? AND cp.status IN ('published', 'active')
        ORDER BY FIELD(pmi.course, 'Appetizers', 'Main Course', 'Desserts', 'Beverages'), pmi.package_id ASC, pmi.id ASC
    ");
    $stmtItems->execute([$catererId]);
    $allItems = $stmtItems->fetchAll();
}

// Group dishes by course
$courses = [
    'Appetizers' => [
        'title' => 'Course 1: Appetizers & Hors d\'oeuvres',
        'icon' => '🥗',
        'badge' => 'Cocktail Hour & Starters',
        'desc' => 'Mix and match light bites, salads, canapés and seafood starters from across any package tier.',
        'items' => []
    ],
    'Main Course' => [
        'title' => 'Course 2: Main Courses & Entrées',
        'icon' => '🥩',
        'badge' => 'Plated Dinner Service',
        'desc' => 'Select premium center-plate meats, fresh seafood, and artisan vegetarian dishes from Gold, Platinum, or Signature menus.',
        'items' => []
    ],
    'Desserts' => [
        'title' => 'Course 3: Plated Desserts & Confections',
        'icon' => '🍮',
        'badge' => 'Sweet Finale',
        'desc' => 'Artisanal pastry creations, warm molten cakes, fruit tarts, and signature dessert domes.',
        'items' => []
    ],
    'Beverages' => [
        'title' => 'Course 4: Bar, Wine & Beverage Service',
        'icon' => '🍷',
        'badge' => 'Liquid Hospitality',
        'desc' => 'Top-shelf open bar, sommelier reserve wine pairings, craft mocktail stations, or artisanal hot tea & coffee.',
        'items' => []
    ]
];

foreach ($allItems as $item) {
    $c = $item['course'] ?? 'Main Course';
    if (!isset($courses[$c])) {
        $courses[$c] = [
            'title' => $c,
            'icon' => '🍽️',
            'badge' => 'Course',
            'desc' => 'Select dishes for this course.',
            'items' => []
        ];
    }
    $courses[$c]['items'][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Custom Multi-Package Catering Builder</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .builder-section {
      background: #fff;
      border: 1px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 24px 28px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .builder-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
      padding-bottom: 12px;
      border-bottom: 1.5px solid var(--gray-100);
      flex-wrap: wrap;
      gap: 10px;
    }
    .builder-section-title {
      font-size: 1.18rem;
      font-weight: 800;
      color: var(--gray-900);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .builder-section-badge {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 4px 10px;
      border-radius: 20px;
    }

    /* Package Filter Tabs */
    .pkg-filter-bar {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }
    .filter-btn {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.78rem;
      font-weight: 700;
      border: 1.5px solid var(--gray-300);
      background: #fff;
      color: var(--gray-700);
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .filter-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
    }
    .filter-btn.active {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }
    .filter-btn.active.filter-gold {
      background: #d97706;
      border-color: #d97706;
    }
    .filter-btn.active.filter-platinum {
      background: #2563eb;
      border-color: #2563eb;
    }
    .filter-btn.active.filter-signature {
      background: #7c3aed;
      border-color: #7c3aed;
    }

    /* Course Items Checkbox Card */
    .course-item-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 14px 18px;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      margin-bottom: 10px;
      background: #fff;
      cursor: pointer;
      transition: all 0.18s ease;
      position: relative;
    }
    .course-item-card:hover {
      border-color: #059669;
      background: #fcfdfd;
      transform: translateY(-1px);
    }
    .course-check { display: none; }
    .course-check:checked + .course-item-card {
      background: #f0fdf4;
      border-color: #059669;
      box-shadow: 0 2px 6px rgba(5, 150, 105, 0.12);
    }
    .cb-box {
      width: 22px;
      height: 22px;
      border-radius: 5px;
      flex-shrink: 0;
      border: 2px solid var(--gray-300);
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.82rem;
      font-weight: 800;
      transition: all 0.15s ease;
    }
    .course-check:checked + .course-item-card .cb-box {
      background: #059669;
      border-color: #059669;
      color: #fff;
    }
    .course-check:checked + .course-item-card .cb-box::after {
      content: '✓';
    }
    .course-info { flex: 1; }
    .course-title-row {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 4px;
      flex-wrap: wrap;
    }
    .course-title {
      font-weight: 700;
      font-size: 0.92rem;
      color: var(--gray-900);
    }
    .course-desc {
      font-size: 0.77rem;
      color: var(--gray-600);
      line-height: 1.4;
    }
    .course-price-tag {
      font-size: 0.9rem;
      font-weight: 800;
      color: #059669;
      flex-shrink: 0;
      text-align: right;
    }
    .course-price-sub {
      font-size: 0.7rem;
      color: var(--gray-500);
      font-weight: 500;
    }

    /* Tier Origin Badges */
    .tier-badge {
      font-size: 0.68rem;
      font-weight: 800;
      padding: 2px 8px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .tier-badge.gold {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
    }
    .tier-badge.platinum {
      background: #eff6ff;
      color: #1e40af;
      border: 1px solid #bfdbfe;
    }
    .tier-badge.signature {
      background: #f3e8ff;
      color: #6b21a8;
      border: 1px solid #e9d5ff;
    }
    .diet-badge {
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      background: #f1f5f9;
      color: var(--gray-700);
    }

    /* Stepper & Headcount */
    .headcount-wrap {
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .headcount-stepper {
      display: flex;
      align-items: center;
      border: 2px solid var(--gray-300);
      border-radius: var(--radius-sm);
      overflow: hidden;
      background: #fff;
    }
    .stepper-btn {
      width: 44px;
      height: 44px;
      border: none;
      background: var(--gray-100);
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--gray-700);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
    }
    .stepper-btn:hover { background: var(--gray-200); }
    .stepper-val {
      width: 84px;
      height: 44px;
      border: none;
      text-align: center;
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--primary);
    }
    .preset-chip {
      padding: 8px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      border: 1.5px solid var(--gray-200);
      background: #fff;
      color: var(--gray-700);
      cursor: pointer;
      transition: all 0.15s;
    }
    .preset-chip:hover { border-color: var(--primary); color: var(--primary); }
    .preset-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

    /* Sticky Sidebar Summary */
    .sticky-summary {
      position: sticky;
      top: 80px;
      background: #fff;
      border: 2px solid #059669;
      border-radius: var(--radius);
      padding: 24px;
      box-shadow: var(--shadow-md);
    }
    .summary-metric {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.85rem;
      margin-bottom: 10px;
      color: var(--gray-700);
    }
    .summary-total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 14px;
      margin-top: 14px;
      border-top: 2px solid var(--gray-200);
    }
    .selected-dish-pill {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 6px 8px;
      border-radius: 6px;
      background: #f8fafc;
      border: 1px solid var(--gray-200);
      font-size: 0.78rem;
      margin-bottom: 6px;
    }
    .selected-dish-pill .pill-del {
      cursor: pointer;
      color: #ef4444;
      font-weight: 700;
      padding: 0 4px;
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">

    <!-- Customer Sidebar -->
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub">Customer Portal</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Switch Role / Logout
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
        <!-- Breadcrumb -->
        <div class="breadcrumb mb-16">
          <a href="customer-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <a href="booking-catering-packages.php?venue_id=<?= $venueId ?>">Catering Packages</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Custom Multi-Package Builder</span>
        </div>

        <?php if ($catererId <= 0 || empty($catererProfile)): ?>
        <div style="text-align:center;padding:70px 24px;background:#fff;border-radius:16px;border:1px dashed var(--gray-300);max-width:650px;margin:40px auto;">
          <div style="font-size:3.5rem;margin-bottom:14px;">🍽️</div>
          <h2 style="font-size:1.4rem;font-weight:800;margin-bottom:8px;">No Catering Partner Assigned to <?= e($venue['name'] ?? 'This Venue') ?></h2>
          <p style="color:var(--gray-600);margin-bottom:24px;line-height:1.5;">
            The administration has not assigned dedicated catering partners to this venue. As a result, custom culinary packages cannot be built for this property. You may proceed with venue rental only or select another venue.
          </p>
          <div class="flex gap-12" style="justify-content:center;flex-wrap:wrap;">
            <a href="venues.php" class="btn btn-outline">Explore Other Venues</a>
            <a href="booking-summary-payment.php?venue_id=<?= $venueId ?>&no_catering=1" class="btn btn-primary">Continue Booking Without Catering →</a>
          </div>
        </div>
        <?php else: ?>

        <!-- Page Header -->
        <div class="flex-between mb-24">
          <div>
            <h1 style="font-size:2rem; font-weight:800; margin-bottom:4px;">Custom Catering Package Builder</h1>
            <p style="color:var(--gray-600); max-width:820px;">
              Customize your dining experience completely across <strong>all packages</strong> offered by your caterer
              (<strong><?= e($catererBizName) ?></strong>). Mix and match appetizers, main entrées, desserts, and beverages freely from
              <span class="tier-badge gold">Gold</span>, <span class="tier-badge platinum">Platinum</span>, and <span class="tier-badge signature">Signature</span> tiers!
            </p>
          </div>
          <div class="flex gap-8">
            <a href="booking-catering-packages.php?venue_id=<?= $venueId ?>" class="btn btn-ghost btn-sm">← View Standard Tiers</a>
          </div>
        </div>

        <?php if (count($assignedCaterers) > 1): ?>
        <div class="flex gap-8 mb-20 flex-wrap" style="align-items:center;">
          <span class="text-xs font-bold text-muted">Assigned Caterer:</span>
          <?php foreach ($assignedCaterers as $ac): ?>
          <a href="caterer-custom-package.php?venue_id=<?= $venueId ?>&caterer_id=<?= $ac['id'] ?>&guests=<?= $guestCount ?>"
             class="btn btn-sm <?= $catererId === (int)$ac['id'] ? 'btn-primary' : 'btn-outline' ?>"
             style="border-radius:20px; font-size:0.75rem; padding:4px 14px;">
            👨‍🍳 <?= e($ac['business_name'] ?: $ac['name']) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form & Sticky Layout -->
        <div class="grid-2" style="grid-template-columns: 2.3fr 1.15fr; gap: 28px; align-items: start;">

          <!-- Left Column: Dishes & Options Builder -->
          <div>

            <!-- Section 1: Headcount & Venue Scope -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>👥</span> 1. Event Scale &amp; Headcount
                </div>
                <span class="builder-section-badge" style="background:#eff6ff; color:#1d4ed8;">Dynamic Cover Count</span>
              </div>

              <div class="flex-between flex-wrap gap-16 mb-16">
                <div>
                  <div class="font-bold text-sm mb-4">Venue: <?= e($venue['name']) ?></div>
                  <div class="text-xs text-muted">Base Capacity: <?= (int)$venue['capacity'] ?> guests · Rate: $<?= number_format($venue['base_rate'], 2) ?></div>
                </div>
                <div class="headcount-wrap">
                  <div class="headcount-stepper">
                    <button type="button" class="stepper-btn" onclick="stepGuests(-10)">−</button>
                    <input type="number" id="guest-count-input" class="stepper-val" value="<?= $guestCount ?>" min="10" max="1000" step="5" onchange="onGuestChange()">
                    <button type="button" class="stepper-btn" onclick="stepGuests(10)">+</button>
                  </div>
                  <span class="text-sm font-bold text-muted">Guests</span>
                </div>
              </div>

              <div class="headcount-presets">
                <button type="button" class="preset-chip <?= $guestCount === 50 ? 'active' : '' ?>" onclick="setGuests(50)">50 Guests</button>
                <button type="button" class="preset-chip <?= $guestCount === 75 ? 'active' : '' ?>" onclick="setGuests(75)">75 Guests</button>
                <button type="button" class="preset-chip <?= $guestCount === 100 ? 'active' : '' ?>" id="chip-100" onclick="setGuests(100)">100 Guests</button>
                <button type="button" class="preset-chip <?= $guestCount === 150 ? 'active' : '' ?>" onclick="setGuests(150)">150 Guests</button>
                <button type="button" class="preset-chip <?= $guestCount === 200 ? 'active' : '' ?>" onclick="setGuests(200)">200 Guests</button>
                <button type="button" class="preset-chip <?= $guestCount === 300 ? 'active' : '' ?>" onclick="setGuests(300)">300 Guests</button>
              </div>
            </div>

            <!-- Section 2: Multi-Package Dish Picker -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>🍽️</span> 2. Select Dishes Across All Catering Packages
                </div>
                <span class="builder-section-badge" style="background:#f0fdf4; color:#15803d;">Mix &amp; Match Any Tier</span>
              </div>

              <p class="text-xs text-muted mb-16">
                You can freely choose any combination of items (e.g., 2 dishes from Gold, some from Platinum, and desserts from Signature). Check any item to include it in your bespoke package:
              </p>

              <!-- Global Package Filter Bar -->
              <div class="pkg-filter-bar">
                <span class="text-xs font-bold text-muted flex-center" style="margin-right:6px;">Filter By Origin:</span>
                <button type="button" class="filter-btn active" id="f-all" onclick="filterTier('all')">All Tiers (<?= count($allItems) ?> Dishes)</button>
                <button type="button" class="filter-btn filter-gold" id="f-gold" onclick="filterTier('Gold')">🟡 Gold Package Only</button>
                <button type="button" class="filter-btn filter-platinum" id="f-plat" onclick="filterTier('Platinum')">🔵 Platinum Package Only</button>
                <button type="button" class="filter-btn filter-signature" id="f-sign" onclick="filterTier('Signature')">🟣 Signature Package Only</button>
              </div>

              <!-- Courses Iteration -->
              <?php foreach ($courses as $courseKey => $courseData): ?>
              <div class="course-group mb-24" data-course="<?= e($courseKey) ?>">
                <div class="flex-between mb-10 pb-6" style="border-bottom: 1.5px dashed var(--gray-200);">
                  <div class="flex-center gap-8">
                    <span style="font-size:1.3rem;"><?= $courseData['icon'] ?></span>
                    <div>
                      <div class="font-bold text-sm" style="color:var(--gray-900);"><?= e($courseData['title']) ?></div>
                      <div class="text-xs text-muted"><?= e($courseData['desc']) ?></div>
                    </div>
                  </div>
                  <span class="builder-section-badge" style="background:#f8fafc; color:var(--gray-700);"><?= e($courseData['badge']) ?></span>
                </div>

                <!-- Dishes List -->
                <div class="dishes-list">
                  <?php foreach ($courseData['items'] as $dish):
                    $tierClass = strtolower($dish['package_tier'] ?? 'gold');
                    // Pre-check a balanced starter set
                    $defaultChecked = in_array((int)$dish['id'], [1, 6, 7, 4]) ? 'checked' : '';
                  ?>
                  <div class="dish-item-wrapper" data-tier="<?= e($dish['package_tier']) ?>">
                    <input type="checkbox"
                           id="dish-<?= (int)$dish['id'] ?>"
                           class="course-check dish-checkbox"
                           data-id="<?= (int)$dish['id'] ?>"
                           data-name="<?= e($dish['item_name']) ?>"
                           data-course="<?= e($dish['course']) ?>"
                           data-tier="<?= e($dish['package_tier']) ?>"
                           data-pkg-title="<?= e($dish['package_title']) ?>"
                           data-diet="<?= e($dish['dietary_tag']) ?>"
                           data-price="<?= number_format((float)$dish['unit_value'], 2, '.', '') ?>"
                           onchange="onDishToggle(this)"
                           <?= $defaultChecked ?>>
                    <label for="dish-<?= (int)$dish['id'] ?>" class="course-item-card">
                      <span class="cb-box"></span>
                      <div class="course-info">
                        <div class="course-title-row">
                          <span class="course-title"><?= e($dish['item_name']) ?></span>
                          <span class="tier-badge <?= $tierClass ?>">From <?= e($dish['package_tier']) ?> Package</span>
                          <?php if (!empty($dish['dietary_tag'])): ?>
                            <span class="diet-badge"><?= e($dish['dietary_tag']) ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="course-desc"><?= e($dish['portion_note'] ?? 'Culinary preparation designed for banquets and events.') ?></div>
                      </div>
                      <div class="course-price-tag">
                        +$<?= number_format((float)$dish['unit_value'], 2) ?>
                        <div class="course-price-sub">per guest</div>
                      </div>
                    </label>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endforeach; ?>

            </div>

            <!-- Section 3: Dining Format & Execution -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>👔</span> 3. Dining Service Style
                </div>
                <span class="builder-section-badge" style="background:#eff6ff; color:#1e40af;">Floor Format</span>
              </div>

              <div class="grid-3 gap-12 mb-12">
                <label class="card flex gap-10 service-style-card active" style="padding:14px; cursor:pointer; border:2px solid var(--primary); background:#eff6ff;" onclick="setServiceStyle(this, 'Synchronized Plated')">
                  <input type="radio" name="service_style" value="Synchronized Plated" checked style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Synchronized Plated</div>
                    <div class="text-xs text-muted">White-glove simultaneous table service course by course.</div>
                  </div>
                </label>

                <label class="card flex gap-10 service-style-card" style="padding:14px; cursor:pointer; border:1.5px solid var(--gray-200);" onclick="setServiceStyle(this, 'Interactive Action Stations')">
                  <input type="radio" name="service_style" value="Interactive Action Stations" style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Interactive Action Stations</div>
                    <div class="text-xs text-muted">Culinary chefs prepare and carve dishes live in front of guests.</div>
                  </div>
                </label>

                <label class="card flex gap-10 service-style-card" style="padding:14px; cursor:pointer; border:1.5px solid var(--gray-200);" onclick="setServiceStyle(this, 'Family-Style Elevated Feast')">
                  <input type="radio" name="service_style" value="Family-Style Elevated Feast" style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Family-Style Feast</div>
                    <div class="text-xs text-muted">Generous shared artisan platters presented at each guest table.</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Section 4: Dietary Accommodations & Kitchen Notes -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>🥗</span> 4. Dietary Accommodations &amp; Chef Instructions
                </div>
                <span class="builder-section-badge" style="background:#fee2e2; color:#b91c1c;">Kitchen Precautions</span>
              </div>

              <div class="grid-2 gap-12 mb-16">
                <label class="card flex-center gap-8 text-sm" style="padding:10px 14px; cursor:pointer;">
                  <input type="checkbox" id="diet-halal" checked style="accent-color:#059669;">
                  <span>Halal-Certified Protein Prep</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px 14px; cursor:pointer;">
                  <input type="checkbox" id="diet-vegan" checked style="accent-color:#059669;">
                  <span>Dedicated Vegan / Plant-Based Course Options</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px 14px; cursor:pointer;">
                  <input type="checkbox" id="diet-celiac" checked style="accent-color:#059669;">
                  <span>Strict Celiac / Gluten-Free Prep</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px 14px; cursor:pointer;">
                  <input type="checkbox" id="diet-nut" checked style="accent-color:#059669;">
                  <span>Nut-Free Kitchen Surface Sanitization</span>
                </label>
              </div>

              <div class="form-group">
                <label class="form-label font-bold text-sm">Special Kitchen Notes / Executive Chef Instructions</label>
                <textarea id="chef-notes" class="form-control" rows="3" placeholder="e.g. 2 VIP guests have severe shellfish allergies. Please serve bridal party hors d'oeuvres in the green room prior to ballroom doors opening."></textarea>
              </div>
            </div>

            <!-- Section 5: Staffing & Equipment Upgrades -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>👔</span> 5. Optional Staffing &amp; Equipment Add-ons
                </div>
                <span class="builder-section-badge" style="background:var(--gray-100); color:var(--gray-700);">Optional Enhancements</span>
              </div>

              <div class="grid-2 gap-12">
                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" class="upgrade-check" data-price="350" data-name="Executive Chef Live Plating" onchange="updateSummary()" style="accent-color:#059669;">
                    <span>Executive Chef on-site live plating</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$350.00</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" class="upgrade-check" data-price="250" data-name="Dedicated Sommelier Service" onchange="updateSummary()" style="accent-color:#059669;">
                    <span>Dedicated Master Sommelier</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$250.00</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" class="upgrade-check" data-price="540" data-name="Uniformed Banquet Servers (4x)" onchange="updateSummary()" style="accent-color:#059669;">
                    <span>Uniformed Banquet Servers (4 staff)</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$540.00</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" class="upgrade-check" data-price="640" data-name="Gold Cutlery & Linen Rental" onchange="updateSummary()" style="accent-color:#059669;">
                    <span>Gold Cutlery &amp; Luxury Linen Rental</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$640.00</span>
                </label>
              </div>
            </div>

          </div>

          <!-- Right Column: Sticky Live Summary & Action -->
          <div>
            <div class="sticky-summary">
              <div class="flex-between mb-16">
                <div>
                  <div class="text-xs text-muted font-bold" style="text-transform:uppercase; letter-spacing:0.05em;">BESPOKE ESTIMATE</div>
                  <h3 style="font-size:1.3rem; font-weight:800; margin:2px 0 0; color:#059669;">Custom Package</h3>
                </div>
                <span class="pill pill-in-progress" style="font-size:0.65rem; background:#dcfce7; color:#166534;">LIVE SYNC</span>
              </div>

              <!-- Culinary Partner Card -->
              <div class="card mb-16" style="background:#f8fafc; border:1px solid var(--gray-200); padding:12px;">
                <div class="text-xs text-muted">Culinary Kitchen</div>
                <div class="font-bold text-sm" style="color:var(--gray-900);"><?= e($catererBizName) ?></div>
                <div class="text-xs text-primary font-semibold"><?= e($catererChefName) ?></div>
                <div class="text-xs text-muted mt-4">Venue: <?= e($venue['name']) ?></div>
              </div>

              <!-- Headcount Metric -->
              <div class="summary-metric">
                <span>Headcount (Guest Covers)</span>
                <strong id="summary-guests-val">100 Guests</strong>
              </div>

              <!-- Dishes Breakdown Heading -->
              <div style="border-top:1px dashed var(--gray-200); padding-top:12px; margin-top:12px;">
                <div class="flex-between text-xs font-bold mb-8">
                  <span style="color:#059669;">Selected Dishes (<span id="selected-count-val">0</span> items)</span>
                  <span id="tier-counts-pill" class="text-muted" style="font-size:0.7rem;">0 Gold · 0 Plat · 0 Sign</span>
                </div>
                <!-- Dynamic list of selected dishes -->
                <div id="selected-dishes-container" style="max-height:220px; overflow-y:auto; margin-bottom:12px; padding-right:4px;">
                  <!-- Rendered via JS -->
                </div>
              </div>

              <!-- Financial Metric Rows -->
              <div class="summary-metric">
                <span>Per-Guest Food Rate</span>
                <strong id="per-guest-rate-val" style="color:#059669;">$0.00 / guest</strong>
              </div>
              <div class="summary-metric">
                <span>Catering Food Subtotal</span>
                <span id="food-subtotal-val">$0.00</span>
              </div>
              <div class="summary-metric" id="upgrades-row" style="display:none;">
                <span>Staffing &amp; Equipment Addons</span>
                <span id="upgrades-val">+$0.00</span>
              </div>
              <div class="summary-metric">
                <span>Dietary Precautions</span>
                <span class="text-success font-semibold">Complimentary</span>
              </div>

              <!-- Estimated Subtotal -->
              <div class="summary-metric" style="border-top:1.5px dashed var(--gray-300); padding-top:10px; margin-top:10px;">
                <span class="font-bold">Package Estimate Total</span>
                <strong id="custom-subtotal-val" style="font-size:1.15rem; color:var(--gray-900);">$0.00</strong>
              </div>

              <!-- Total Card -->
              <div class="summary-total">
                <div>
                  <div style="font-size:0.72rem; color:var(--gray-500); font-weight:700; text-transform:uppercase;">TOTAL PACKAGE RATE</div>
                  <div style="font-size:1.65rem; font-weight:900; color:#059669;" id="grand-estimate-val">$0.00</div>
                </div>
                <div style="text-align:right;">
                  <div class="text-xs text-muted">Rate / Cover</div>
                  <div class="font-bold text-success" style="font-size:1rem;" id="effective-rate-val">$0.00</div>
                </div>
              </div>

              <!-- Action Button -->
              <div style="margin-top:20px;">
                <button type="button" class="btn btn-primary btn-full" id="btn-proceed" onclick="proceedToPayment()" style="background:#059669; border-color:#059669; font-weight:800; padding:13px; font-size:0.95rem;">
                  Proceed to Review &amp; Pay →
                </button>
                <div class="text-center text-xs text-muted mt-8">
                  Individual dishes subject to culinary chef review upon receipt
                </div>
              </div>

            </div>
          </div>

        </div><!-- /grid-2 -->
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

  <?php if ($catererId > 0 && !empty($catererProfile)): ?>
  <script>
    const VENUE_ID = <?= $venueId ?>;
    const initialSavedGuests = parseInt(sessionStorage.getItem('vp_guest_count') || sessionStorage.getItem('vp_guests') || '<?= $guestCount ?>') || 100;
    let guestCount = initialSavedGuests;
    let activeFilter = 'all';
    let serviceStyle = 'Synchronized Plated';

    // State of selected dishes
    const selectedDishes = new Map();

    function initBuilder() {
      // Synchronize initial headcount input and presets
      const inp = document.getElementById('guest-count-input');
      if (inp) inp.value = guestCount;
      document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('active'));
      const activeChip = Array.from(document.querySelectorAll('.preset-chip')).find(c => c.textContent.trim().startsWith(guestCount.toString()));
      if (activeChip) activeChip.classList.add('active');

      // Read initial checkboxes
      document.querySelectorAll('.dish-checkbox:checked').forEach(inp => {
        addDishFromInput(inp);
      });
      updateSummary();
    }

    function addDishFromInput(inp) {
      const id = inp.getAttribute('data-id');
      selectedDishes.set(id, {
        id: id,
        name: inp.getAttribute('data-name'),
        course: inp.getAttribute('data-course'),
        tier: inp.getAttribute('data-tier'),
        pkgTitle: inp.getAttribute('data-pkg-title'),
        diet: inp.getAttribute('data-diet'),
        price: parseFloat(inp.getAttribute('data-price')) || 0
      });
    }

    function onDishToggle(inp) {
      const id = inp.getAttribute('data-id');
      if (inp.checked) {
        addDishFromInput(inp);
      } else {
        selectedDishes.delete(id);
      }
      updateSummary();
    }

    function removeDishById(id) {
      const cb = document.getElementById('dish-' + id);
      if (cb) cb.checked = false;
      selectedDishes.delete(id.toString());
      updateSummary();
    }

    function filterTier(tier) {
      activeFilter = tier;
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      if (tier === 'all') document.getElementById('f-all').classList.add('active');
      if (tier === 'Gold') document.getElementById('f-gold').classList.add('active');
      if (tier === 'Platinum') document.getElementById('f-plat').classList.add('active');
      if (tier === 'Signature') document.getElementById('f-sign').classList.add('active');

      document.querySelectorAll('.dish-item-wrapper').forEach(wrap => {
        const itemTier = wrap.getAttribute('data-tier');
        if (tier === 'all' || itemTier.toLowerCase() === tier.toLowerCase()) {
          wrap.style.display = 'block';
        } else {
          wrap.style.display = 'none';
        }
      });
    }

    function stepGuests(delta) {
      const inp = document.getElementById('guest-count-input');
      let val = (parseInt(inp.value) || 100) + delta;
      val = Math.max(20, Math.min(val, 1000));
      inp.value = val;
      onGuestChange();
    }

    function setGuests(val) {
      document.getElementById('guest-count-input').value = val;
      document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('active'));
      const chip = Array.from(document.querySelectorAll('.preset-chip')).find(c => c.textContent.includes(val.toString()));
      if (chip) chip.classList.add('active');
      onGuestChange();
    }

    function onGuestChange() {
      const inp = document.getElementById('guest-count-input');
      guestCount = Math.max(20, Math.min(parseInt(inp.value) || 100, 1000));
      inp.value = guestCount;
      updateSummary();
    }

    function setServiceStyle(card, style) {
      serviceStyle = style;
      document.querySelectorAll('.service-style-card').forEach(c => {
        c.style.borderColor = 'var(--gray-200)';
        c.style.background = '#fff';
      });
      card.style.borderColor = 'var(--primary)';
      card.style.background = '#eff6ff';
    }

    function updateSummary() {
      let perGuestRate = 0;
      let tierCounts = { Gold: 0, Platinum: 0, Signature: 0 };
      const container = document.getElementById('selected-dishes-container');
      container.innerHTML = '';

      if (selectedDishes.size === 0) {
        container.innerHTML = '<div class="text-xs text-muted" style="padding:10px 0; text-align:center;">No dishes selected yet. Please check items on the left to build your package!</div>';
      } else {
        selectedDishes.forEach(dish => {
          perGuestRate += dish.price;
          if (dish.tier in tierCounts) tierCounts[dish.tier]++;
          else tierCounts[dish.tier] = (tierCounts[dish.tier] || 0) + 1;

          const pill = document.createElement('div');
          pill.className = 'selected-dish-pill';
          const tierCls = (dish.tier || 'gold').toLowerCase();
          pill.innerHTML = `
            <div style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
              <span class="tier-badge ${tierCls}" style="font-size:0.6rem; padding:1px 5px;">${dish.tier}</span>
              <strong style="margin-left:4px;">${dish.name}</strong>
            </div>
            <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
              <span style="font-weight:700; color:#059669;">+$${dish.price.toFixed(2)}</span>
              <span class="pill-del" onclick="removeDishById(${dish.id})" title="Remove dish">×</span>
            </div>
          `;
          container.appendChild(pill);
        });
      }

      // Upgrades calculation
      let upgradesTotal = 0;
      document.querySelectorAll('.upgrade-check:checked').forEach(uc => {
        upgradesTotal += parseFloat(uc.getAttribute('data-price')) || 0;
      });

      const foodSubtotal = perGuestRate * guestCount;
      const customSubtotal = foodSubtotal + upgradesTotal;
      const effectiveRate = guestCount > 0 ? (customSubtotal / guestCount) : 0;

      // Update UI elements
      document.getElementById('summary-guests-val').textContent = guestCount + ' Guests';
      document.getElementById('selected-count-val').textContent = selectedDishes.size;
      document.getElementById('tier-counts-pill').textContent = `${tierCounts.Gold || 0} Gold · ${tierCounts.Platinum || 0} Plat · ${tierCounts.Signature || 0} Sign`;
      document.getElementById('per-guest-rate-val').textContent = '$' + perGuestRate.toFixed(2) + ' / guest';
      document.getElementById('food-subtotal-val').textContent = '$' + foodSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      
      const upgRow = document.getElementById('upgrades-row');
      if (upgradesTotal > 0) {
        upgRow.style.display = 'flex';
        document.getElementById('upgrades-val').textContent = '+$' + upgradesTotal.toFixed(2);
      } else {
        upgRow.style.display = 'none';
      }

      document.getElementById('custom-subtotal-val').textContent = '$' + customSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('grand-estimate-val').textContent = '$' + customSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('effective-rate-val').textContent = '$' + effectiveRate.toFixed(2) + ' / guest';

      const btn = document.getElementById('btn-proceed');
      if (selectedDishes.size === 0) {
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.textContent = 'Select At Least 1 Dish';
      } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.textContent = `Proceed with ${selectedDishes.size} Dishes ($${customSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}) →`;
      }
    }

    function proceedToPayment() {
      if (selectedDishes.size === 0) {
        alert('Please select at least one dish for your custom catering package.');
        return;
      }

      const dishesArr = Array.from(selectedDishes.values());
      let perGuestRate = 0;
      dishesArr.forEach(d => perGuestRate += d.price);

      let upgradesTotal = 0;
      const upgradeNames = [];
      document.querySelectorAll('.upgrade-check:checked').forEach(uc => {
        upgradesTotal += parseFloat(uc.getAttribute('data-price')) || 0;
        upgradeNames.push(uc.getAttribute('data-name'));
      });

      const totalFoodCost = perGuestRate * guestCount;
      const totalCustomPackageCost = totalFoodCost + upgradesTotal;
      const chefNotes = document.getElementById('chef-notes').value || '';

      // Dietary specs
      const diets = [];
      if (document.getElementById('diet-halal').checked) diets.push('Halal-Certified');
      if (document.getElementById('diet-vegan').checked) diets.push('Vegan/Plant-Based');
      if (document.getElementById('diet-celiac').checked) diets.push('Strict Gluten-Free');
      if (document.getElementById('diet-nut').checked) diets.push('Nut-Free Kitchen');

      // Save to sessionStorage for booking-summary-payment.php
      sessionStorage.setItem('vp_is_custom_package', '1');
      sessionStorage.setItem('vp_custom_title', `Custom Hybrid Package (${dishesArr.length} Dishes)`);
      sessionStorage.setItem('vp_custom_rate', perGuestRate.toFixed(2));
      sessionStorage.setItem('vp_custom_total', totalCustomPackageCost.toFixed(2));
      sessionStorage.setItem('vp_guest_count', guestCount.toString());
      sessionStorage.setItem('vp_guests', guestCount.toString());
      sessionStorage.setItem('vp_venue_id', VENUE_ID.toString());
      sessionStorage.setItem('vp_custom_dishes', JSON.stringify(dishesArr));
      sessionStorage.setItem('vp_chef_notes', chefNotes);
      sessionStorage.setItem('vp_service_style', serviceStyle);
      sessionStorage.setItem('vp_dietary_specs', JSON.stringify(diets));
      sessionStorage.setItem('vp_upgrades_total', upgradesTotal.toFixed(2));
      sessionStorage.setItem('vp_upgrades_names', JSON.stringify(upgradeNames));
      sessionStorage.setItem('vp_caterer_id', '<?= $catererId ?>');

      // Redirect to Booking Step 3 (Payment)
      const curDate = sessionStorage.getItem('vp_date') || sessionStorage.getItem('vp_event_date') || '<?= e($eventDate) ?>';
      const curStart = sessionStorage.getItem('vp_start_time') || '<?= e($startTime) ?>';
      const curEnd = sessionStorage.getItem('vp_end_time') || '<?= e($endTime) ?>';
      const qParams = new URLSearchParams({
        venue_id: VENUE_ID,
        custom_package: '1',
        caterer_id: '<?= $catererId ?>',
        guests: guestCount,
        guest_count: guestCount,
        date: curDate,
        start_time: curStart,
        end_time: curEnd
      });
      window.location.href = `booking-summary-payment.php?` + qParams.toString();
    }

    document.addEventListener('DOMContentLoaded', initBuilder);
  </script>
  <?php endif; ?>
  <script src="../js/app.js"></script>
</body>
</html>