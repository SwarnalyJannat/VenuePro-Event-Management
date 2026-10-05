<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Browse Venues (Guest Mode)</title>
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
      <span><strong>Guest Browsing Mode:</strong> You are viewing our venue catalog. Sign in to place reservations.</span>
    </div>
  </div>

  <main class="public-page">
    <?php
    $sortBy = htmlspecialchars($_GET['sort'] ?? 'popular', ENT_QUOTES);
    $orderMap = [
        'popular'       => 'id ASC',
        'capacity_desc' => 'capacity DESC',
        'price_asc'     => 'base_rate ASC',
        'price_desc'    => 'base_rate DESC',
    ];
    $orderClause = $orderMap[$sortBy] ?? 'id ASC';


    $search = trim(htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES));
    if (!empty($search)) {
        $stmtVenues = $db->prepare("SELECT * FROM venues WHERE status = 'active' AND (name LIKE ? OR district LIKE ? OR description LIKE ?) ORDER BY $orderClause");
        $stmtVenues->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmtVenues = $db->query("SELECT * FROM venues WHERE status = 'active' ORDER BY $orderClause");
    }
    $venues = $stmtVenues ? $stmtVenues->fetchAll() : [];

    // Photo counts per venue for dynamic "View Photos" badge
    $photoCountMap = [];
    if (!empty($venues)) {
        $ids = implode(',', array_map('intval', array_column($venues, 'id')));
        $pcRows = $db->query("SELECT venue_id, COUNT(*) AS cnt FROM venue_photos WHERE venue_id IN ($ids) GROUP BY venue_id");
        if ($pcRows) foreach ($pcRows as $pc) $photoCountMap[(int)$pc['venue_id']] = (int)$pc['cnt'];
    }
    ?>

    <div class="flex-between mb-24">
      <div>
        <h1 style="font-size:2.2rem; font-weight:800; margin-bottom:6px;">Enterprise Venues Catalog</h1>
        <p>Explore luxury ballrooms, skyline terraces, and tech pavilions. Click any venue to view high-res photo galleries.</p>
      </div>
      <div class="flex-center gap-12">
        <form method="GET" action="venues.php" id="sort-form" style="display:flex; align-items:center; gap:8px;">
          <input type="text" name="search" class="form-control" placeholder="Search venues..." value="<?= e($search) ?>" style="width:190px;">
          <span class="text-sm text-muted">Sort by:</span>
          <select name="sort" class="form-control" style="width:180px;" onchange="this.form.submit()">
            <option value="popular" <?= $sortBy==='popular' ? 'selected' : '' ?>>Most Popular</option>
            <option value="capacity_desc" <?= $sortBy==='capacity_desc' ? 'selected' : '' ?>>Capacity: High to Low</option>
            <option value="price_asc" <?= $sortBy==='price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sortBy==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
          </select>
        </form>
      </div>
    </div>

    <div class="grid-3 gap-24">
      <?php
      if (empty($venues)): ?>
      <div style="grid-column:1/-1; text-align:center; padding:60px 24px; color:var(--gray-400);">
        <div style="font-size:3.5rem; margin-bottom:12px;">🏛️</div>
        <div style="font-size:1.1rem; font-weight:600;">No venues found matching your criteria.</div>
      </div>
      <?php else:
      foreach ($venues as $idx => $v):
        // Resolve image path: uploaded image in assets/venues pic/ or fallback to local project picture
        $rawImg = $v['image_url'] ?? '';
        if (str_starts_with($rawImg, '../')) $rawImg = substr($rawImg, 3);
        $isDefault = (empty($rawImg) || str_contains($rawImg, 'venue-default'));
        $img = !$isDefault ? $rawImg : 'assets/venues pic/images.jpg';
        $badge = !empty($v['badge']) ? $v['badge'] : strtoupper($v['venue_type'] ?? 'VENUE');
      ?>
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('<?= e($img) ?>'); position:relative;">
          <span class="venue-badge"><?= e($badge) ?></span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title"><?= e($v['name']) ?></h3>
          </div>
          <!-- <p class="venue-card-loc">📍 <?= e($v['district']) ?> · <?= number_format($v['max_capacity']) ?> Guests Capacity</p> -->
          <p class="text-xs text-muted mb-16"><?= e(mb_strimwidth($v['description'] ?? '', 0, 120, '...')) ?></p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$<?= number_format($v['base_rate'], 0) ?><small>/day base</small></div>
              <span class="text-xs text-success">✓ Verified Property</span>
            </div>
            <div class="flex gap-8">
              <?php
              $dbCount = (int)($photoCountMap[(int)$v['id']] ?? 0);
              $photoLabel = $dbCount > 0 ? ($dbCount . ' Photos') : 'Cover Photo';
              ?>
              <a href="venue-details.php?id=<?= $v['id'] ?>#photo-gallery" class="btn btn-outline btn-sm">View Photos (<?= $photoLabel ?>)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; endif; ?>
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
        <a href="login-role.php" class="btn btn-primary btn-full font-bold">Sign In to Continue →</a>
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