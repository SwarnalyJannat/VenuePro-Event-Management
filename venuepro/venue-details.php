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
  <title>VenuePro – Grand Emerald Ballroom (Photo Gallery & Specs)</title>
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

    .public-page { max-width: 1240px; margin: 0 auto; padding: 24px 24px 60px; }

    /* Photo Gallery Lightbox */
    .gallery-lightbox {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.92);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(8px); padding: 24px;
    }
    .gallery-lightbox:target { display: flex; }
    .gallery-lightbox-content {
      background: #0f172a; border-radius: var(--radius); padding: 24px;
      max-width: 1040px; width: 100%; max-height: 90vh; overflow-y: auto; color: #fff;
    }
    .gallery-photo-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-top: 20px;
    }
    .gallery-card {
      border-radius: var(--radius-sm); overflow: hidden; position: relative; height: 210px;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .gallery-card img {
      width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;
    }
    .gallery-card:hover img { transform: scale(1.06); }
    .gallery-card-cap {
      position: absolute; bottom: 0; inset-inline: 0; background: linear-gradient(transparent, rgba(0,0,0,0.85));
      padding: 10px 14px; font-size: 0.78rem; font-weight: 600; color: #fff;
    }
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
      <a href="venues.php" class="btn btn-outline btn-sm" style="font-weight:600;">Sign In</a>
      <a href="signup-role.php" class="btn btn-primary btn-sm" style="font-weight:600;">Sign Up</a>
    </nav>
  </header>

  <div class="guest-banner-bar" style="justify-content:center;">
    <div class="flex-center gap-8">
      <span>👋</span>
      <span><strong>Guest Browsing Mode:</strong> You are viewing venue details and high-resolution photo gallery. Sign in to book.</span>
    </div>
    
  </div>

<?php
$venueId = (int)($_GET['id'] ?? 1);
$stmt = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmt->execute([$venueId]);
$venue = $stmt->fetch();
if (!$venue) {
    $venue = $db->query("SELECT * FROM venues WHERE status = 'active' LIMIT 1")->fetch();
    $venueId = $venue ? (int)$venue['id'] : 1;
}

// Load photos from dedicated venue database
try {
    require_once __DIR__ . '/config/venue_db.php';
    $venueDb = getVenueDBConnection($venueId);
    $stmtPh = $venueDb->prepare("SELECT id, venue_id, file_name, caption, is_cover, sort_order FROM photos ORDER BY sort_order ASC, id ASC");
    $stmtPh->execute();
    $rawDbPhotos = $stmtPh->fetchAll();
    $dbPhotos = [];
    foreach ($rawDbPhotos as $ph) {
        $dbPhotos[] = [
            'id'         => (int)$ph['id'],
            'venue_id'   => $venueId,
            'photo_url'  => "api/venue-photo.php?venue_id={$venueId}&id={$ph['id']}",
            'caption'    => $ph['caption'],
            'is_cover'   => (int)$ph['is_cover'],
            'sort_order' => (int)$ph['sort_order']
        ];
    }
} catch (Exception $e) {
    $stmtPh = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
    $stmtPh->execute([$venueId]);
    $dbPhotos = $stmtPh->fetchAll();
}

// Resolve venue image URL
$rawImg = $venue['image_url'] ?? '';
if (str_starts_with($rawImg, '../')) $rawImg = substr($rawImg, 3);
$isDefault = (empty($rawImg) || str_contains($rawImg, 'venue-default'));
$heroImg = !$isDefault ? $rawImg : 'assets/venues pic/images.jpg';

// Build gallery photos from database ONLY (real uploaded photos)
$galleryPhotos = [];
$hasHeroInDb = false;

foreach ($dbPhotos as $ph) {
    $pUrl = $ph['photo_url'];
    if (str_starts_with($pUrl, '../')) $pUrl = substr($pUrl, 3);
    $galleryPhotos[] = [
        'id'       => $ph['id'],
        'url'      => $pUrl,
        'caption'  => $ph['caption'] ?? ($venue['name'] . ' Photo'),
        'from_db'  => true
    ];
    if ($pUrl === $heroImg) {
        $hasHeroInDb = true;
    }
}

// Prepend hero cover photo if not already in DB photos list
if (!$hasHeroInDb && !empty($heroImg)) {
    array_unshift($galleryPhotos, [
        'id'      => null,
        'url'     => $heroImg,
        'caption' => ($venue['name'] ?? 'Venue') . ' — Main Space',
        'from_db' => false
    ]);
}

$baseRate = (float)($venue['base_rate'] ?? 2000);
$capacity = (int)($venue['capacity'] ?? ($venue['max_capacity'] ?? 200));
$essentialRate = round($baseRate * 0.5);
$enterpriseRate = round($baseRate);
$eliteRate = round($baseRate * 1.75);
$isAdmin = ($currentUser && ($currentUser['role'] ?? '') === 'admin');
$totalPhotos = count($galleryPhotos);
?>

  <main class="public-page">
    <div class="breadcrumb mb-16">
      <a href="index.php">Home</a>
      <span class="breadcrumb-sep">›</span>
      <a href="venues.php">Venues</a>
      <span class="breadcrumb-sep">›</span>
      <span class="breadcrumb-current"><?= e($venue['name'] ?? 'Venue Details') ?></span>
    </div>

    <div class="grid-2" style="grid-template-columns: 2.2fr 1fr; align-items:start; gap:28px;">
      <div>
        <div class="mb-16">
          <span class="stat-badge positive mb-8">Available for 2026 Reservations</span>
          <div class="flex-between">
            <h1 style="font-size:2.4rem; font-weight:800; margin:0 0 4px;"><?= e($venue['name'] ?? 'Venue Details') ?></h1>
          </div>
          <div class="flex-center gap-16 text-sm text-muted mt-8">
            <span>📍 <?= e($venue['district'] ?? '') ?><?= !empty($venue['address']) ? ', ' . e($venue['address']) : '' ?></span>
            <span>👥 Up to <?= number_format($capacity) ?> Attendees</span>
          </div>
        </div>

        <!-- Gallery Preview Strip (Real Uploaded Photos Only) -->
        <?php if ($totalPhotos === 0): ?>
        <div class="mb-32" style="height:260px; background:#f1f5f9; border:2px dashed var(--gray-300); border-radius:var(--radius); display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--gray-500);">
          <div style="font-size:2.5rem; margin-bottom:8px;">📷</div>
          <div style="font-weight:700; font-size:1.1rem; color:var(--gray-700);">Photographs Being Updated</div>
          <div style="font-size:0.85rem; margin-top:4px;">Verified high-resolution photos will be published shortly by administration.</div>
        </div>
        <?php elseif ($totalPhotos === 1): ?>
        <div class="mb-32" style="height:380px; position:relative;">
          <div style="background:url('<?= e($galleryPhotos[0]['url']) ?>') center/cover; border-radius:var(--radius); height:100%; position:relative;">
            <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:0.8rem;padding:6px 12px;border-radius:4px;"><?= e($galleryPhotos[0]['caption'] ?: ($venue['name'] . ' — Main Space')) ?></span>
            <a href="#photo-gallery" class="btn btn-primary btn-sm" style="position:absolute;bottom:12px;right:12px;font-weight:700;">📸 View Photo (1 Photo)</a>
          </div>
        </div>
        <?php elseif ($totalPhotos === 2): ?>
        <div class="grid-2 mb-32" style="gap:12px; height:380px;">
          <div style="background:url('<?= e($galleryPhotos[0]['url']) ?>') center/cover; border-radius:var(--radius); height:100%; position:relative;">
            <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:0.75rem;padding:4px 10px;border-radius:4px;"><?= e($galleryPhotos[0]['caption']) ?></span>
          </div>
          <div style="background:url('<?= e($galleryPhotos[1]['url']) ?>') center/cover; border-radius:var(--radius); height:100%; position:relative;">
            <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:0.75rem;padding:4px 10px;border-radius:4px;"><?= e($galleryPhotos[1]['caption']) ?></span>
            <a href="#photo-gallery" class="btn btn-outline btn-sm" style="position:absolute;bottom:12px;right:12px;background:rgba(15,23,42,0.85);color:#fff;border-color:rgba(255,255,255,0.4);font-weight:700;">📸 2 Photos</a>
          </div>
        </div>
        <?php else: ?>
        <div class="grid-2 mb-32" style="gap:12px; height:380px;">
          <div style="background:url('<?= e($galleryPhotos[0]['url']) ?>') center/cover; border-radius:var(--radius); height:100%; position:relative;">
            <span style="position:absolute;bottom:12px;left:12px;background:rgba(0,0,0,0.65);color:#fff;font-size:0.75rem;padding:4px 10px;border-radius:4px;"><?= e($galleryPhotos[0]['caption']) ?></span>
          </div>
          <div style="display:flex; flex-direction:column; gap:12px; height:100%;">
            <div style="flex:1; background:url('<?= e($galleryPhotos[1]['url']) ?>') center/cover; border-radius:var(--radius); position:relative;">
              <span style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,0.65);color:#fff;font-size:0.7rem;padding:3px 8px;border-radius:4px;"><?= e($galleryPhotos[1]['caption']) ?></span>
            </div>
            <a href="#photo-gallery" style="flex:1; background:url('<?= e($galleryPhotos[2]['url']) ?>') center/cover; border-radius:var(--radius); position:relative; overflow:hidden; text-decoration:none; display:block;">
              <div style="position:absolute;inset:0;background:rgba(15,23,42,0.65);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;font-weight:700;">
                <span style="font-size:2rem; margin-bottom:4px;">📸</span>
                <span style="font-size:1.2rem;">+<?= $totalPhotos ?> PHOTOS</span>
                <span style="font-size:0.75rem; font-weight:400; opacity:0.9;">Click to Open Full Gallery</span>
              </div>
            </a>
          </div>
        </div>
        <?php endif; ?>

        <!-- About -->
        <div class="card mb-24">
          <h3 class="mb-12">About the Venue</h3>
          <p class="mb-12"><?= nl2br(e($venue['description'] ?? 'A premier event venue designed for high-profile corporate summits, banquets, and celebrations.')) ?></p>
          <p>📍 <?= e($venue['address'] ?? '') ?> · Maximum Capacity: <?= number_format($capacity) ?> Guests</p>
        </div>

        <!-- Pricing Tiers -->
        <!-- <div class="card mb-24">
          <h3 class="mb-16">Transparent Pricing Tiers</h3>
          <div class="grid-3 gap-16">
            <div class="card" style="background:var(--gray-50);">
              <div class="font-bold text-sm mb-4">Essential Half-Day</div>
              <div class="font-bold text-primary" style="font-size:1.3rem;">$<?= number_format($essentialRate, 0) ?></div>
              <div class="text-xs text-muted mb-8">6 Hours · Standard Setup</div>
            </div>
            <div class="card" style="border:2px solid var(--primary); background:#eff6ff;">
              <div class="font-bold text-sm mb-4">Enterprise Full-Day</div>
              <div class="font-bold text-primary" style="font-size:1.3rem;">$<?= number_format($enterpriseRate, 0) ?></div>
              <div class="text-xs text-muted mb-8">12 Hours · Full Tech Support</div>
            </div>
            <div class="card" style="background:var(--gray-50);">
              <div class="font-bold text-sm mb-4">Elite Gala Weekend</div>
              <div class="font-bold text-primary" style="font-size:1.3rem;">$<?= number_format($eliteRate, 0) ?></div>
              <div class="text-xs text-muted mb-8">24 Hours Exclusive · VIP Lounge</div>
            </div>
          </div>
        </div> -->
      </div>

      <!-- Quick Booking Panel (Guest Mode) -->
      <div style="position:sticky; top:90px;">
        <div class="card mb-16" style="border:2px solid var(--primary); box-shadow:var(--shadow-md);">
          <h3 class="mb-4">Reserve This Venue</h3>
          <p class="text-xs text-muted mb-16">Check available dates &amp; rates</p>
          <div class="form-group">
            <label class="form-label">Event Date</label>
            <input type="date" class="form-control" placeholder="e.g. 2026-10-25">
          </div>
          <div class="form-group">
            <label class="form-label">Expected Attendees</label>
            <input type="number" class="form-control" placeholder="e.g. 150" max="<?= $capacity ?>">
          </div>
          <div style="border-top:1px solid var(--gray-200); padding-top:14px; margin-bottom:16px;">
            <div class="flex-between text-sm mb-6">
              <span class="text-muted">Enterprise Day Rate</span>
              <span class="font-semibold">$<?= number_format($enterpriseRate, 2) ?></span>
            </div>
            <div class="flex-between font-bold" style="font-size:1.2rem; color:var(--primary); margin-top:8px;">
              <span>Base Venue Cost</span>
              <span>$<?= number_format($enterpriseRate, 2) ?></span>
            </div>
          </div>
          <a href="#signin-modal" class="btn btn-primary btn-full mb-10 font-bold" style="padding:12px;">
            🔒 Sign In to Book Venue →
          </a>
          <div class="text-xs text-center text-muted">Sign in required to hold dates and submit booking contracts.</div>
        </div>
      </div>
    </div>
  </main>

  <!-- ============================================================ -->
  <!-- FULL PHOTO GALLERY LIGHTBOX (Real Uploaded Photos Only)      -->
  <!-- ============================================================ -->
  <div id="photo-gallery" class="gallery-lightbox">
    <div class="gallery-lightbox-content">
      <div class="flex-between pb-12" style="border-bottom:1px solid rgba(255,255,255,0.15); margin-bottom:20px;">
        <div>
          <h2 style="font-size:1.6rem; font-weight:800; color:#fff; margin:0 0 4px;"><?= e($venue['name']) ?> — Photo Gallery</h2>
          <p style="margin:0; font-size:0.85rem; color:#94a3b8;" id="photo-count-label"><?= $totalPhotos ?> verified uploaded photo<?= $totalPhotos !== 1 ? 's' : '' ?> — high-resolution interior spaces, seating configurations, and architectural features.</p>
        </div>
        <div class="flex gap-12" style="align-items:center;">
          <?php if ($isAdmin): ?>
          <a href="admin/admin-edit-venue.php?id=<?= $venueId ?>#gallery-management" class="btn btn-outline btn-sm" style="color:#60a5fa; border-color:#3b82f6;">⚙️ Edit Gallery in Admin</a>
          <?php endif; ?>
          <a href="#" class="btn btn-outline btn-sm" style="color:#fff; border-color:rgba(255,255,255,0.3);">✕ Close Gallery</a>
        </div>
      </div>

      <?php if ($isAdmin): ?>
      <!-- Admin: Add Photo Form -->
      <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:16px; margin-bottom:20px;">
        <div class="font-bold text-sm mb-8" style="color:#94a3b8; text-transform:uppercase; letter-spacing:.05em;">🛡 Admin — Add Photo to Gallery</div>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
          <div style="flex:2; min-width:200px;">
            <label style="display:block; font-size:0.78rem; color:#94a3b8; margin-bottom:4px;">Image URL / File Path *</label>
            <input type="text" id="newPhotoUrl" class="form-control" placeholder="e.g. assets/venues pic/image.jpg">
          </div>
          <div style="flex:1; min-width:140px;">
            <label style="display:block; font-size:0.78rem; color:#94a3b8; margin-bottom:4px;">Caption (optional)</label>
            <input type="text" id="newPhotoCaption" class="form-control" placeholder="e.g. Grand Hall Setup">
          </div>
          <button onclick="addPhoto()" class="btn btn-primary btn-sm" style="white-space:nowrap; padding:8px 16px;">+ Add Photo</button>
        </div>
        <div id="addPhotoMsg" style="margin-top:8px; font-size:0.82rem; display:none;"></div>
      </div>
      <?php endif; ?>

      <!-- Photo Grid -->
      <div class="gallery-photo-grid" id="galleryGrid">
        <?php if (empty($galleryPhotos)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:48px 16px; color:#94a3b8;">
          <div style="font-size:2.5rem; margin-bottom:8px;">📷</div>
          <div style="font-weight:700; font-size:1.1rem; color:#fff;">No photos uploaded yet for this venue.</div>
        </div>
        <?php else: ?>
        <?php foreach ($galleryPhotos as $ph): ?>
        <div class="gallery-card" id="photo-card-<?= $ph['id'] ?? 's-' . md5($ph['url']) ?>">
          <img src="<?= e($ph['url']) ?>" alt="<?= e($ph['caption']) ?>" loading="lazy" onerror="this.src='assets/venues pic/images.jpg'">
          <div class="gallery-card-cap"><?= e($ph['caption']) ?></div>
          <?php if ($isAdmin && $ph['from_db'] && $ph['id']): ?>
          <button onclick="removePhoto(<?= (int)$ph['id'] ?>, this)"
            style="position:absolute; top:8px; right:8px; background:rgba(220,38,38,0.9); color:#fff; border:none; border-radius:50%; width:26px; height:26px; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; font-weight:700;"
            title="Remove photo">✕</button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="flex-between mt-24 pt-16" style="border-top:1px solid rgba(255,255,255,0.15);">
        <div class="text-xs" style="color:#94a3b8;">High-definition verified property photographs for <?= e($venue['name']) ?>.</div>
        <!-- <a href="#signin-modal" class="btn btn-primary btn-sm">Sign In to Book This Venue →</a> -->
      </div>
    </div>
  </div>

  <!-- Sign In Modal -->
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
<script>
var VENUE_ID = <?= (int)$venueId ?>;
var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

async function addPhoto() {
  var url = document.getElementById('newPhotoUrl').value.trim();
  var caption = document.getElementById('newPhotoCaption').value.trim();
  var msg = document.getElementById('addPhotoMsg');
  if (!url) { showMsg(msg, '⚠️ Image URL is required.', '#fef3c7', '#92400e'); return; }

  try {
    var res = await fetch('api/venue-photos.php?action=add', {
      method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ venue_id: VENUE_ID, photo_url: url, caption: caption })
    });
    var d = await res.json();
    if (d.success) {
      showMsg(msg, '✓ Photo added!', '#dcfce7', '#166534');
      document.getElementById('newPhotoUrl').value = '';
      document.getElementById('newPhotoCaption').value = '';
      // Inject card into grid
      var grid = document.getElementById('galleryGrid');
      var card = document.createElement('div');
      card.className = 'gallery-card';
      card.id = 'photo-card-' + d.data.id;
      card.style.position = 'relative';
      card.innerHTML = '<img src="' + url + '" alt="' + caption + '" style="width:100%;height:100%;object-fit:cover;">' +
        '<div class="gallery-card-cap">' + (caption || 'Venue Photo') + '</div>' +
        '<button onclick="removePhoto(' + d.data.id + ', this)" style="position:absolute;top:8px;right:8px;background:rgba(220,38,38,0.9);color:#fff;border:none;border-radius:50%;width:26px;height:26px;font-size:14px;cursor:pointer;font-weight:700;" title="Remove photo">✕</button>';
      grid.appendChild(card);
      // Update count label
      var lbl = document.getElementById('photo-count-label');
      if (lbl) { var n = grid.querySelectorAll('.gallery-card').length; lbl.textContent = n + ' photos — high-resolution interior spaces, seating configurations, lighting & architecture.'; }
    } else {
      showMsg(msg, '⚠️ ' + (d.message || 'Failed to add photo.'), '#fee2e2', '#991b1b');
    }
  } catch(e) {
    showMsg(msg, '⚠️ Connection error.', '#fee2e2', '#991b1b');
  }
}

async function removePhoto(id, btn) {
  if (!confirm('Remove this photo from the gallery?')) return;
  btn.disabled = true;
  try {
    var res = await fetch('api/venue-photos.php?action=delete', {
      method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: id })
    });
    var d = await res.json();
    if (d.success) {
      var card = document.getElementById('photo-card-' + id);
      if (card) { card.style.opacity = '0'; card.style.transition = 'opacity .3s'; setTimeout(function(){ card.remove(); }, 320); }
    } else {
      alert(d.message || 'Could not remove photo.');
      btn.disabled = false;
    }
  } catch(e) {
    alert('Connection error.');
    btn.disabled = false;
  }
}

function showMsg(el, text, bg, color) {
  el.style.display = 'block';
  el.style.background = bg;
  el.style.color = color;
  el.style.padding = '8px 12px';
  el.style.borderRadius = '6px';
  el.textContent = text;
}
</script>
</body>
</html>
