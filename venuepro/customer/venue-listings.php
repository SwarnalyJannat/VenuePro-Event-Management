<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Venue Listings</title>
  <link rel="stylesheet" href="../css/style.css">
  
  <style>
    /* Sign In Modal */
    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(4px);
    }
    .modal-backdrop:target { display: flex; }
    .modal-card {
      background: #fff; border-radius: var(--radius); padding: 32px;
      max-width: 440px; width: 90%; box-shadow: var(--shadow-xl);
      text-align: center; position: relative;
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
        <a href="../logout.php" class="nav-item" style="color:var(--gray-400);">
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
          <input type="text" placeholder="e.g. Search event venues, bookings, menus...">
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
            <div class="user-avatar" style="background:<?= e($currentUser['avatar_bg'] ?? '#2563eb') ?>;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
<?php
$search     = htmlspecialchars(trim($_GET['search'] ?? ''), ENT_QUOTES);
$typeFilter = htmlspecialchars(trim($_GET['type'] ?? ''), ENT_QUOTES);
$minCap     = (int)($_GET['min_capacity'] ?? 0);
$maxPrice   = (int)($_GET['max_price'] ?? 0);
$sortBy     = htmlspecialchars(trim($_GET['sort'] ?? 'popular'), ENT_QUOTES);

$perPage    = 5;
$page       = max(1, (int)($_GET['page'] ?? 1));

// Base filter conditions and params
$whereSql = "WHERE v.status = 'active'";
$params = [];

if (!empty($search)) {
    $whereSql .= " AND (v.name LIKE ? OR v.district LIKE ? OR v.description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if (!empty($typeFilter)) {
    $whereSql .= " AND LOWER(v.venue_type) = LOWER(?)";
    $params[] = $typeFilter;
}
if ($minCap > 0) {
    $whereSql .= " AND v.capacity >= ?";
    $params[] = $minCap;
}
if ($maxPrice > 0) {
    $whereSql .= " AND v.base_rate <= ?";
    $params[] = $maxPrice;
}

// Total count matching filters
$countStmt = $db->prepare("SELECT COUNT(*) FROM venues v $whereSql");
$countStmt->execute($params);
$totalVenues = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalVenues / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$orderMap = [
    'popular'       => 'v.id ASC',
    'price_asc'     => 'v.base_rate ASC',
    'price_desc'    => 'v.base_rate DESC',
    'capacity_desc' => 'v.capacity DESC',
];
$orderBy = $orderMap[$sortBy] ?? 'v.id ASC';

$sql = "SELECT v.*, (SELECT COUNT(*) FROM venue_photos vp WHERE vp.venue_id = v.id) AS photo_count 
        FROM venues v 
        $whereSql 
        ORDER BY $orderBy 
        LIMIT $perPage OFFSET $offset";

$stmtVenues = $db->prepare($sql);
$stmtVenues->execute($params);
$venueRows  = $stmtVenues->fetchAll();

$startItem = $totalVenues > 0 ? ($offset + 1) : 0;
$endItem   = min($offset + count($venueRows), $totalVenues);

function getVenuePageUrl($targetPage, $search, $typeFilter, $minCap, $maxPrice, $sortBy) {
    $p = [];
    if ($targetPage > 1) {
        $p['page'] = $targetPage;
    }
    if (!empty($search)) {
        $p['search'] = $search;
    }
    if (!empty($typeFilter)) {
        $p['type'] = $typeFilter;
    }
    if ($minCap > 0) {
        $p['min_capacity'] = $minCap;
    }
    if ($maxPrice > 0) {
        $p['max_price'] = $maxPrice;
    }
    if (!empty($sortBy) && $sortBy !== 'popular') {
        $p['sort'] = $sortBy;
    }
    return 'venue-listings.php' . (!empty($p) ? ('?' . http_build_query($p)) : '');
}
?>
        
<div class="flex-between mb-16">
  <div>
    <h1 style="font-size:1.8rem; font-weight:800;">Premium Venues</h1>
    <p>Showing <?= $startItem ?>–<?= $endItem ?> of <?= $totalVenues ?> venue<?= $totalVenues !== 1 ? 's' : '' ?> matching your criteria<?= $totalPages > 1 ? " (Page $page of $totalPages)" : "" ?></p>
  </div>
  <div class="flex-center gap-12">
    <form method="GET" action="venue-listings.php" id="customer-sort-form" style="display:flex; align-items:center; gap:8px;">
      <input type="hidden" name="page" value="1">
      <?php if (!empty($search)): ?>
      <input type="hidden" name="search" value="<?= e($search) ?>">
      <?php endif; ?>
      <?php if (!empty($typeFilter)): ?>
      <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
      <?php endif; ?>
      <?php if ($minCap > 0): ?>
      <input type="hidden" name="min_capacity" value="<?= $minCap ?>">
      <?php endif; ?>
      <span class="text-sm text-muted">Sort by:</span>
      <select name="sort" class="form-control" style="width:170px; padding:6px 12px;" onchange="this.form.submit()">
        <option value="popular" <?= $sortBy==='popular' ? 'selected' : '' ?>>Most Popular</option>
        <option value="price_asc" <?= $sortBy==='price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
        <option value="price_desc" <?= $sortBy==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
        <option value="capacity_desc" <?= $sortBy==='capacity_desc' ? 'selected' : '' ?>>Capacity: High to Low</option>
      </select>
    </form>
  </div>
</div>

<div class="flex gap-24" style="align-items:flex-start;">
  <!-- Filters Sidebar Form -->
  <aside class="filters-panel">
    <form method="GET" action="venue-listings.php" id="venue-filter-form">
      <input type="hidden" name="page" value="1">
      <input type="hidden" name="sort" value="<?= e($sortBy) ?>">
      <div class="flex-between mb-16">
        <span class="font-bold" style="font-size:0.95rem;">Filters</span>
        <a href="venue-listings.php" class="text-xs text-primary font-semibold">Clear All</a>
      </div>

      <div class="filter-group">
        <div class="filter-label">Search Keyword</div>
        <input type="text" name="search" class="form-control" placeholder="e.g. Venue name or district..." value="<?= e($search) ?>" style="font-size:0.85rem;">
      </div>

      <div class="filter-group">
        <div class="filter-label">Venue Type</div>
        <select name="type" class="form-control" style="font-size:0.85rem;" onchange="this.form.submit()">
          <option value="">All Categories</option>
          <option value="Ballroom" <?= strcasecmp($typeFilter, 'Ballroom')===0 ? 'selected' : '' ?>>Ballrooms</option>
          <option value="Rooftop" <?= strcasecmp($typeFilter, 'Rooftop')===0 ? 'selected' : '' ?>>Rooftops</option>
          <option value="Garden" <?= strcasecmp($typeFilter, 'Garden')===0 ? 'selected' : '' ?>>Garden Spaces</option>
          <option value="Gallery" <?= strcasecmp($typeFilter, 'Gallery')===0 ? 'selected' : '' ?>>Industrial Lofts</option>
          <option value="Auditorium" <?= strcasecmp($typeFilter, 'Auditorium')===0 ? 'selected' : '' ?>>Tech Pavilions</option>
        </select>
      </div>

      <div class="filter-group">
        <div class="filter-label">Min Capacity</div>
        <select name="min_capacity" class="form-control" style="font-size:0.85rem;" onchange="this.form.submit()">
          <option value="0">Any Capacity</option>
          <option value="50" <?= $minCap===50 ? 'selected' : '' ?>>50+ Guests</option>
          <option value="150" <?= $minCap===150 ? 'selected' : '' ?>>150+ Guests</option>
          <option value="300" <?= $minCap===300 ? 'selected' : '' ?>>300+ Guests</option>
          <option value="500" <?= $minCap===500 ? 'selected' : '' ?>>500+ Guests</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-sm mt-12">Apply Filters</button>
    </form>
  </aside>

  <!-- Venue Cards Grid -->
  <div style="flex:1;">
      <?php if (empty($venueRows)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px 24px;color:var(--gray-400);">
          <div style="font-size:3.5rem;margin-bottom:12px;">🏛️</div>
          <div style="font-weight:600;margin-bottom:6px;">No venues match your criteria</div>
          <a href="venue-listings.php" style="color:var(--primary);text-decoration:underline;">Clear all filters</a>
        </div>
      <?php else: foreach ($venueRows as $idx => $v):
        // Fix image path: resolve uploaded image or local project picture
        $rawImg = $v['image_url'] ?? '';
        if (str_starts_with($rawImg, '../')) $rawImg = substr($rawImg, 3);
        $isDefault = (empty($rawImg) || str_contains($rawImg, 'venue-default'));
        $img = !$isDefault ? ('../' . $rawImg) : '../assets/venues pic/images.jpg';
        $dbCount = (int)($v['photo_count'] ?? 0);
        $photoLabel = $dbCount > 0 ? ($dbCount . ' Photos') : 'Cover Photo';
      ?>
      <!-- Venue: <?= e($v['name']) ?> -->
      <div class="venue-card">
        <div class="venue-card-img-wrap">
          <div class="venue-card-img" style="background:url('<?= e($img) ?>') center/cover;"></div>
          <?php if (!empty($v['badge'])): ?>
          <span class="venue-badge"><?= e($v['badge']) ?></span>
          <?php endif; ?>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-4">
            <div class="venue-card-name"><?= e($v['name']) ?></div>
          </div>
          <div class="venue-card-location"><?= e($v['district']) ?></div>
          <div class="venue-card-meta">
            <span class="venue-capacity">👥 <?= number_format($v['capacity']) ?> Guests</span>
            <span class="venue-price">$<?= number_format($v['base_rate'], 0) ?><small class="text-muted">/day</small></span>
          </div>
        </div>
        <div class="venue-card-actions">
          <a href="venue-details.php?id=<?= $v['id'] ?>#venue-gallery" class="btn btn-outline" title="Inspect verified photo gallery">📸 <?= $photoLabel ?></a>
          <a href="venue-details.php?id=<?= $v['id'] ?>" class="btn btn-outline">Details</a>
          <a href="venue-details.php?id=<?= $v['id'] ?>#pricing" class="btn btn-primary">Book Now</a>
        </div>
      </div>
      <?php endforeach; endif; ?>

    <!-- Dynamic Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="flex-center justify-center gap-8 mt-24">
      <?php if ($page > 1): ?>
        <a href="<?= getVenuePageUrl($page - 1, $search, $typeFilter, $minCap, $maxPrice, $sortBy) ?>" class="btn btn-ghost btn-sm" title="Previous Page">‹</a>
      <?php else: ?>
        <span class="btn btn-ghost btn-sm" style="opacity:0.35; cursor:not-allowed;" title="First Page">‹</span>
      <?php endif; ?>

      <?php
      // Smart pagination window
      $window = 2;
      $startPage = max(1, $page - $window);
      $endPage = min($totalPages, $page + $window);

      if ($startPage > 1) {
          echo '<a href="' . getVenuePageUrl(1, $search, $typeFilter, $minCap, $maxPrice, $sortBy) . '" class="btn btn-ghost btn-sm">1</a>';
          if ($startPage > 2) {
              echo '<span class="text-muted" style="padding:0 4px;">...</span>';
          }
      }

      for ($p = $startPage; $p <= $endPage; $p++) {
          if ($p === $page) {
              echo '<span class="btn btn-primary btn-sm" style="pointer-events:none; font-weight:700;">' . $p . '</span>';
          } else {
              echo '<a href="' . getVenuePageUrl($p, $search, $typeFilter, $minCap, $maxPrice, $sortBy) . '" class="btn btn-ghost btn-sm">' . $p . '</a>';
          }
      }

      if ($endPage < $totalPages) {
          if ($endPage < $totalPages - 1) {
              echo '<span class="text-muted" style="padding:0 4px;">...</span>';
          }
          echo '<a href="' . getVenuePageUrl($totalPages, $search, $typeFilter, $minCap, $maxPrice, $sortBy) . '" class="btn btn-ghost btn-sm">' . $totalPages . '</a>';
      }
      ?>

      <?php if ($page < $totalPages): ?>
        <a href="<?= getVenuePageUrl($page + 1, $search, $typeFilter, $minCap, $maxPrice, $sortBy) ?>" class="btn btn-ghost btn-sm" title="Next Page">›</a>
      <?php else: ?>
        <span class="btn btn-ghost btn-sm" style="opacity:0.35; cursor:not-allowed;" title="Last Page">›</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
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