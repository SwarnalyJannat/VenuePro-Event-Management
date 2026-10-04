<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$venueId = (int)($_GET['id'] ?? 1);
$stmtVR  = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtVR->execute([$venueId]);
$venueRow = $stmtVR->fetch();
if (!$venueRow) {
    $venueRow = $db->query("SELECT * FROM venues WHERE status='active' LIMIT 1")->fetch();
    $venueId  = $venueRow ? (int)$venueRow['id'] : 1;
}

// Load photos from DB
$stmtPh = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
$stmtPh->execute([$venueId]);
$dbPhotos = $stmtPh->fetchAll();

$stockImages = [
    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=800&q=80',
    'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800&q=80',
    'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80',
    'https://images.unsplash.com/photo-1533105079780-92b9be482077?w=800&q=80',
    'https://images.unsplash.com/photo-1555244162-803834f70033?w=800&q=80',
];
$heroImg  = !empty($venueRow['image_url']) ? '../'.$venueRow['image_url'] : $stockImages[$venueId % count($stockImages)];
$rating   = number_format($venueRow['rating'] ?? 4.8, 1);
$capacity = number_format($venueRow['max_capacity'] ?? 0);
$rate     = number_format($venueRow['base_rate'] ?? 0, 0);

// Build gallery photo list
$galleryPhotos = [];
foreach ($dbPhotos as $ph) {
    $galleryPhotos[] = ['id' => $ph['id'], 'url' => $ph['photo_url'], 'caption' => $ph['caption'] ?? '', 'from_db' => true];
}
$galleryPhotos = array_merge(
    [['id' => null, 'url' => $heroImg, 'caption' => ($venueRow['name'] ?? 'Venue') . ' — Main Space', 'from_db' => false]],
    $galleryPhotos
);
$stockCaptions = ['Interior & Lighting Setup', 'Cocktail Reception Foyer', 'Breakout Lounge', 'Evening Ambiance', 'Property Terrace'];
$si = 1;
while (count($galleryPhotos) < 6) {
    $galleryPhotos[] = ['id' => null, 'url' => $stockImages[($venueId + $si) % count($stockImages)], 'caption' => $stockCaptions[($si-1) % count($stockCaptions)], 'from_db' => false];
    $si++;
}
$totalPhotos = count($galleryPhotos);
?>

﻿<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Venue Details</title>
  <link rel="stylesheet" href="../css/style.css">
  
  <style>
    /* Interactive Photo Gallery Lightbox Modal */
    .gallery-modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.88);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(8px); padding: 24px;
    }
    .gallery-modal-backdrop:target { display: flex; }
    .gallery-modal-card {
      background: #0f172a; border-radius: var(--radius); padding: 24px;
      max-width: 980px; width: 100%; max-height: 90vh; overflow-y: auto;
      box-shadow: var(--shadow-xl); position: relative; color: #fff;
    }
    .gallery-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 16px;
    }
    .gallery-item {
      border-radius: var(--radius-sm); overflow: hidden; position: relative; height: 180px;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .gallery-item img {
      width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;
    }
    .gallery-item:hover img { transform: scale(1.05); }
    .gallery-item-caption {
      position: absolute; bottom: 0; inset-inline: 0; background: linear-gradient(transparent, rgba(0,0,0,0.85));
      padding: 8px 12px; font-size: 0.75rem; font-weight: 600; color: #fff;
    }

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
        
<div class="breadcrumb">
  <a href="venue-listings.php">Venues</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current"><?= e($venueRow['name'] ?? 'Venue') ?></span>
</div>

<div class="mb-32">
  <!-- Main Venue Info -->
  <div class="mb-24">
    <?php $vs = $venueRow['status'] ?? 'active'; ?>
<span class="stat-badge <?= $vs==='active' ? 'positive' : 'negative' ?>" style="margin-bottom:8px;"><?= $vs==='active' ? 'Available for Booking' : 'Unavailable' ?></span>
    <div class="flex-between">
      <h1 style="font-size:2.4rem; font-weight:800;"><?= e($venueRow['name'] ?? 'Venue') ?></h1>
    </div>
    <div class="flex-center gap-16 text-sm text-muted mt-8">
      <span>📍 <?= e($venueRow['district'] ?? '') ?> · <?= e($venueRow['address'] ?? '') ?></span>
    </div>
  </div>

  <!-- Gallery -->
  <div class="grid-2 mb-32" style="gap:12px; height:380px;">
    <div style="background:url('<?= e($galleryPhotos[0]['url']) ?>') center/cover; border-radius:var(--radius); height:100%; position:relative;">
      <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.6);color:#fff;font-size:0.75rem;padding:4px 10px;border-radius:4px;"><?= e($venueRow['name']) ?> Main Space</span>
    </div>
    <div style="display:flex; flex-direction:column; gap:12px; height:100%;">
      <div style="flex:1; background:url('<?= e($galleryPhotos[1]['url']) ?>') center/cover; border-radius:var(--radius); position:relative;">
        <span style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,0.6);color:#fff;font-size:0.7rem;padding:3px 8px;border-radius:4px;"><?= e($galleryPhotos[1]['caption']) ?></span>
      </div>
      <div style="flex:1; background:url('<?= e($galleryPhotos[2]['url']) ?>') center/cover; border-radius:var(--radius); position:relative; overflow:hidden;">
        <a href="#venue-gallery" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.15rem;text-decoration:none;">
          <span style="font-size:1.8rem; margin-bottom:4px;">📸</span>
          <span>+<?= $totalPhotos ?> PHOTOS</span>
          <span style="font-size:0.75rem; font-weight:400; opacity:0.9;">Click to Open Gallery</span>
        </a>
      </div>
    </div>
  </div>

  <!-- About Section -->
  <div class="card mb-24">
    <h3 class="mb-12">About the Venue</h3>
    <p class="mb-12"><?= e($venueRow['description'] ?? 'A premium event venue offering world-class facilities and services.') ?></p>
    <p>📍 <?= e($venueRow['address'] ?? '') ?> &nbsp;·&nbsp; Capacity: <?= $capacity ?> guests &nbsp;·&nbsp; ★<?= $rating ?></p>
  </div>

  <!-- Pricing Tiers -->
  <div class="card mb-24">
    <h3 class="mb-16 text-center">Flexible Pricing Tiers</h3>
    <div class="pricing-grid">
      <div class="pricing-card">
        <div class="pricing-tier">ESSENTIAL</div>
        <div class="pricing-price">$<?= number_format(($venueRow['base_rate']??2400)*0.5, 0) ?> <span>/session</span></div>
        <ul class="pricing-features">
          <li>✓ Venue Access (6h)</li>
          <li>✓ Basic A/V setup</li>
          <li>✓ Tables &amp; Chairs</li>
        </ul>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>&plan=essential" class="btn btn-outline btn-full">Select Plan</a>
      </div>

      <div class="pricing-card featured" style="border-color:var(--primary); background:#eff6ff;">
        <div class="pricing-popular">MOST POPULAR</div>
        <div class="pricing-tier" style="color:var(--primary);">ENTERPRISE</div>
        <div class="pricing-price">$<?= $rate ?> <span>/session</span></div>
        <ul class="pricing-features">
          <li>✓ Full Day Access (12h)</li>
          <li>✓ Premium A/V &amp; Tech</li>
          <li>✓ Basic Beverage Package</li>
          <li>✓ Logistics Manager</li>
        </ul>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>&plan=enterprise" class="btn btn-primary btn-full">Select Plan</a>
      </div>

      <div class="pricing-card">
        <div class="pricing-tier">ELITE GALA</div>
        <div class="pricing-price">$<?= number_format(($venueRow['base_rate']??8500)*1.75, 0) ?> <span>/session</span></div>
        <ul class="pricing-features">
          <li>✓ 24h Exclusive Access</li>
          <li>✓ Full Custom Catering</li>
          <li>✓ Valet &amp; Security Detail</li>
          <li>✓ Post-event Cleaning</li>
        </ul>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>&plan=elite" class="btn btn-outline btn-full">Select Plan</a>
      </div>
    </div>
  </div>
</div>

      </main>

  <!-- Dynamic Photo Gallery Lightbox -->
  <div id="venue-gallery" class="gallery-modal-backdrop">
    <div class="gallery-modal-card">
      <div class="flex-between pb-12" style="border-bottom:1px solid rgba(255,255,255,0.15);">
        <div>
          <h2 style="font-size:1.5rem; font-weight:800; color:#fff; margin:0 0 4px;"><?= e($venueRow['name'] ?? 'Venue') ?> — Photo Gallery</h2>
          <p style="margin:0; font-size:0.85rem; color:#94a3b8;" id="photo-count-label"><?= $totalPhotos ?> photos — high-resolution interior photography, banquet staging &amp; architectural highlights.</p>
        </div>
        <a href="#" class="btn btn-outline btn-sm" style="color:#fff; border-color:rgba(255,255,255,0.3);">✕ Close</a>
      </div>

      <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($galleryPhotos as $ph): ?>
        <div class="gallery-item">
          <img src="<?= e($ph['url']) ?>" alt="<?= e($ph['caption']) ?>" loading="lazy" onerror="this.src='<?= e($stockImages[0]) ?>'">
          <div class="gallery-item-caption"><?= e($ph['caption']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="flex-between mt-24 pt-16" style="border-top:1px solid rgba(255,255,255,0.15);">
        <div class="text-xs" style="color:#94a3b8;">High-definition verified property photographs.</div>
        <a href="booking-date-guests.php?venue_id=<?= $venueId ?>" class="btn btn-primary btn-sm">Book This Venue Now →</a>
      </div>
    </div>
  </div>

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
var vpVenueId = new URLSearchParams(window.location.search).get('id') || '1';
sessionStorage.setItem('vp_venue_id', vpVenueId);
</script>
</body>
</html>
