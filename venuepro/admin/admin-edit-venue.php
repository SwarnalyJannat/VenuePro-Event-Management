<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

$venueId = (int)($_GET['id'] ?? 0);
$venue = null;
if ($venueId > 0) {
    $stmt = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
    $stmt->execute([$venueId]);
    $venue = $stmt->fetch();
}

if (!$venue) {
    header('Location: venue-management.php');
    exit;
}

$amenities = !empty($venue['amenities']) ? json_decode($venue['amenities'], true) : [];
if (!is_array($amenities)) $amenities = [];

// Fetch existing uploaded photos from dedicated venue database
try {
    require_once __DIR__ . '/../config/venue_db.php';
    $venueDb = getVenueDBConnection($venueId);
    $stmtPh = $venueDb->prepare("SELECT id, venue_id, file_name, caption, is_cover, sort_order FROM photos ORDER BY sort_order ASC, id ASC");
    $stmtPh->execute();
    $rawPhotos = $stmtPh->fetchAll();
    $venuePhotos = [];
    foreach ($rawPhotos as $r) {
        $venuePhotos[] = [
            'id' => (int)$r['id'],
            'venue_id' => $venueId,
            'photo_url' => "api/venue-photo.php?venue_id={$venueId}&id={$r['id']}",
            'caption' => $r['caption'],
            'is_cover' => (int)$r['is_cover'],
            'sort_order' => (int)$r['sort_order']
        ];
    }
} catch (Exception $e) {
    $stmtPh = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
    $stmtPh->execute([$venueId]);
    $venuePhotos = $stmtPh->fetchAll();
}

// Resolve image URL for admin display
$currentCover = $venue['image_url'] ?? '';
if (str_starts_with($currentCover, '../')) {
    $coverDisplay = $currentCover;
} else if (!empty($currentCover) && !str_starts_with($currentCover, 'http')) {
    $coverDisplay = '../' . $currentCover;
} else if (empty($currentCover)) {
    $coverDisplay = '../assets/venues pic/images.jpg';
} else {
    $coverDisplay = $currentCover;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Edit Venue &amp; Photos: <?= e($venue['name']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .photo-grid-admin {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;
    }
    .photo-card-admin {
      background: #fff; border: 1.5px solid var(--gray-200); border-radius: 8px; overflow: hidden;
      display: flex; flex-direction: column; transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .photo-card-admin:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px);
    }
    .photo-thumb-wrap {
      height: 140px; position: relative; background: #0f172a; overflow: hidden;
    }
    .photo-thumb-wrap img {
      width: 100%; height: 100%; object-fit: cover;
    }
    .photo-cover-badge {
      position: absolute; top: 8px; left: 8px; background: #2563eb; color: #fff;
      font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 4px;
      text-transform: uppercase; letter-spacing: 0.5px;
    }
    .photo-card-body {
      padding: 10px 12px; display: flex; flex-direction: column; gap: 8px; flex: 1; justify-content: space-between;
    }
    .drop-zone {
      border: 2px dashed var(--gray-300); border-radius: 8px; padding: 20px 16px; text-align: center;
      background: var(--gray-50); cursor: pointer; transition: border-color 0.15s, background-color 0.15s;
    }
    .drop-zone:hover {
      border-color: var(--primary); background: #eff6ff;
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div class="sidebar-logo-text">VenuePro</div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Governance</div>
        <a href="admin-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Staff Directory
        </a>
        <a href="admin-user-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> User Governance
        </a>
        <a href="admin-reports.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item" style="color:var(--gray-400);">
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
          <input type="text" placeholder="e.g. Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
          <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Admin')) ?></div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="venue-management.php">Venue Management</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Edit: <?= e($venue['name']) ?></span>
        </div>

        <div style="max-width:920px; margin:0 auto; display:flex; flex-direction:column; gap:24px;">

          <!-- SECTION 1: Core Venue Specifications Form -->
          <div class="card">
            <div class="flex-between mb-16">
              <div>
                <h2 class="mb-4">Edit Venue Specifications</h2>
                <p class="text-muted">Modify specifications, operational rates, and capabilities for this venue.</p>
              </div>
              <span class="pill pill-<?= $venue['status'] === 'active' ? 'confirmed' : 'pending' ?>">
                <?= strtoupper($venue['status'] ?? 'ACTIVE') ?>
              </span>
            </div>

            <form id="editVenueForm" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?= $venue['id'] ?>">

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Venue Name</label>
                  <input type="text" name="name" class="form-control" value="<?= e($venue['name']) ?>" placeholder="e.g. Grand Emerald Ballroom" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Property Type</label>
                  <select name="venue_type" class="form-control">
                    <?php
                    $types = ['Ballroom', 'Rooftop Lounge', 'Industrial Loft', 'Tech Pavilion', 'Garden Space'];
                    foreach ($types as $t):
                      $sel = ($venue['venue_type'] === $t || strpos($venue['venue_type'], explode(' ', $t)[0]) !== false) ? 'selected' : '';
                    ?>
                      <option value="<?= e($t) ?>" <?= $sel ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Address</label>
                  <input type="text" name="address" class="form-control" value="<?= e($venue['address'] ?? '') ?>" placeholder="e.g. 742 Evergreen Terrace" required>
                </div>
                <div class="form-group">
                  <label class="form-label">District / Location</label>
                  <input type="text" name="district" class="form-control" value="<?= e($venue['district'] ?? '') ?>" placeholder="e.g. Downtown Financial District" required>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Maximum Guest Capacity</label>
                  <input type="number" name="capacity" class="form-control" value="<?= e($venue['capacity'] ?? 100) ?>" placeholder="e.g. 500" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Operating Status</label>
                  <select name="status" class="form-control">
                    <option value="active" <?= ($venue['status'] === 'active') ? 'selected' : '' ?>>Active &amp; Bookable</option>
                    <option value="maintenance" <?= ($venue['status'] === 'maintenance') ? 'selected' : '' ?>>Under Maintenance</option>
                    <option value="inactive" <?= ($venue['status'] === 'inactive') ? 'selected' : '' ?>>Inactive / Archived</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Daily Base Rate (USD)</label>
                  <input type="number" step="0.01" name="base_rate" class="form-control" value="<?= e($venue['base_rate'] ?? 2000) ?>" placeholder="e.g. 2400.00" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Hourly Overtime Fee (USD)</label>
                  <input type="number" step="0.01" name="additional_hour_rate" class="form-control" value="<?= e($venue['additional_hour_rate'] ?? 300) ?>" placeholder="e.g. 350.00" required>
                </div>
              </div>

              <!-- Primary Venue Cover Image Selector -->
              <div class="form-group" style="padding:16px; background:#f8fafc; border:1px solid var(--gray-200); border-radius:8px;">
                <label class="form-label font-bold" style="font-size:0.95rem; margin-bottom:8px;">Primary Venue Cover Image</label>
                <p class="text-xs text-muted mb-12">This image will appear on the venue card in search results, customer listings, and header banners.</p>
                
                <div style="display:flex; gap:20px; align-items:center; flex-wrap:wrap;">
                  <div style="width:180px; height:110px; border-radius:8px; overflow:hidden; border:2px solid var(--gray-300); background:#0f172a; position:relative; flex-shrink:0;">
                    <img id="coverPreviewImg" src="<?= e($coverDisplay) ?>" alt="Cover Image" style="width:100%; height:100%; object-fit:cover;">
                    <span id="coverBadge" class="photo-cover-badge">CURRENT COVER</span>
                  </div>
                  <div style="flex:1; min-width:260px;">
                    <div class="drop-zone" onclick="document.getElementById('edit-venue-image-input').click()">
                      <div style="font-size:24px; margin-bottom:4px;">🖼️</div>
                      <div class="font-bold text-sm" style="color:var(--gray-900);">Click to Upload New Cover Image</div>
                      <div class="text-xs text-muted">Supports JPG, PNG, WEBP (Saved directly to project <code>assets/venues pic/</code>)</div>
                      <input type="file" name="venue_image" id="edit-venue-image-input" accept="image/*" style="display:none;" onchange="previewEditCover(this)">
                      <div id="new-cover-name" style="display:none; margin-top:8px; font-size:12px; font-weight:700; color:var(--success);"></div>
                    </div>
                    <div style="margin-top:8px;">
                      <input type="text" name="image_url" id="inputImageUrl" class="form-control text-xs" value="<?= e($venue['image_url'] ?? '') ?>" placeholder="e.g. assets/venues pic/images.jpg">
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Description &amp; Space Overview</label>
                <textarea name="description" class="form-control" rows="4" placeholder="e.g. Grand hall featuring high ceilings, professional stage lighting, and acoustic insulation..."><?= e($venue['description'] ?? '') ?></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Key Amenities Included</label>
                <div class="grid-4 gap-8" id="amenitiesGroup">
                  <?php
                  $availableAmenities = ['Fiber WiFi', 'Sound Rig', 'In-House Catering', 'Valet Parking', 'ADA Elevator', 'Breakout Rooms', 'Security Detail', 'Loading Dock'];
                  foreach ($availableAmenities as $am):
                    $checked = in_array($am, $amenities) ? 'checked' : '';
                  ?>
                    <label class="filter-check"><input type="checkbox" name="amenities[]" value="<?= e($am) ?>" <?= $checked ?>> <?= e($am) ?></label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div id="formAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

              <div class="flex gap-12 mt-24">
                <a href="venue-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
                <button type="submit" class="btn btn-primary" id="saveBtn" style="flex:2;">Save Venue Changes →</button>
              </div>
            </form>
          </div>

          <!-- SECTION 2: Venue Photo Gallery Management (Add, Edit, Remove Photos) -->
          <div class="card" id="gallery-management">
            <div class="flex-between mb-16">
              <div>
                <h3 style="margin-bottom:4px;">Venue Photo Gallery (<span id="photoCount"><?= count($venuePhotos) ?></span> Photos)</h3>
                <p class="text-xs text-muted">Manage all high-resolution pictures for this property. Only uploaded photos will be displayed to guests and customers.</p>
              </div>
            </div>

            <!-- Upload New Photos Form -->
            <div style="background:#f8fafc; border:1.5px solid var(--gray-200); border-radius:8px; padding:18px; margin-bottom:20px;">
              <h4 style="font-size:0.95rem; margin-bottom:10px; color:var(--gray-900);">➕ Add New Photos to Gallery</h4>
              <div style="display:grid; grid-template-columns: 1fr auto; gap:12px; align-items:center;">
                <div>
                  <div class="drop-zone" onclick="document.getElementById('galleryFilesInput').click()" style="padding:14px;">
                    <span style="font-size:18px;">📸</span>
                    <span class="text-xs font-bold" id="galleryFilesLabel">Click or drag &amp; drop photos to upload (multiple selection supported)</span>
                    <input type="file" id="galleryFilesInput" multiple accept="image/*" style="display:none;" onchange="handleGalleryFilesSelected(this)">
                  </div>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                  <input type="text" id="galleryCaptionInput" class="form-control" style="font-size:0.8rem; padding:8px 12px;" placeholder="e.g. Cocktail Reception Foyer">
                  <button type="button" id="btnUploadGallery" class="btn btn-primary btn-sm" onclick="uploadGalleryPhotos()" style="white-space:nowrap;">Upload Photos Now ↑</button>
                </div>
              </div>
              <div id="galleryUploadAlert" style="display:none; margin-top:12px; padding:8px 12px; border-radius:6px; font-size:0.85rem;"></div>
            </div>

            <!-- Existing Photos Grid -->
            <div id="photosGrid" class="photo-grid-admin">
              <?php if (empty($venuePhotos)): ?>
                <div id="emptyPhotosMsg" style="grid-column:1/-1; text-align:center; padding:32px 16px; color:var(--gray-400); background:#fff; border:1px dashed var(--gray-300); border-radius:8px;">
                  <div style="font-size:2rem; margin-bottom:6px;">📷</div>
                  <div class="font-bold text-sm">No gallery photos uploaded yet.</div>
                  <div class="text-xs text-muted mt-4">Upload pictures above. Only real uploaded photos will appear when guests and customers click "View Photos".</div>
                </div>
              <?php else:
                foreach ($venuePhotos as $ph):
                  $pUrl = $ph['photo_url'];
                  $displayPUrl = str_starts_with($pUrl, 'http') ? $pUrl : ('../' . preg_replace('/^(\.\.\/)+/', '', $pUrl));
                  $isCurrentCover = ($venue['image_url'] === $pUrl || str_ends_with($venue['image_url'], basename($pUrl)));
              ?>
                <div class="photo-card-admin" id="photo-card-<?= $ph['id'] ?>">
                  <div class="photo-thumb-wrap">
                    <img src="<?= e($displayPUrl) ?>" alt="Venue Photo">
                    <?php if ($isCurrentCover): ?>
                      <span class="photo-cover-badge">COVER</span>
                    <?php endif; ?>
                  </div>
                  <div class="photo-card-body">
                    <div>
                      <input type="text" class="form-control text-xs" id="cap-<?= $ph['id'] ?>" value="<?= e($ph['caption'] ?? '') ?>" placeholder="e.g. Caption...">
                    </div>
                    <div style="display:flex; gap:6px; justify-content:space-between; align-items:center; margin-top:4px;">
                      <button type="button" class="btn btn-ghost btn-xs" onclick="savePhotoCaption(<?= $ph['id'] ?>)" title="Save caption">💾 Save</button>
                      <button type="button" class="btn btn-outline btn-xs" onclick="setAsCover(<?= $venue['id'] ?>, <?= $ph['id'] ?>, '<?= e($displayPUrl) ?>')" title="Set as primary cover image">⭐ Cover</button>
                      <button type="button" class="btn btn-ghost btn-xs" style="color:#dc2626;" onclick="deletePhoto(<?= $ph['id'] ?>)" title="Delete photo">🗑️</button>
                    </div>
                  </div>
                </div>
              <?php endforeach; endif; ?>
            </div>

          </div>

        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script>
  function previewEditCover(input) {
    if (input.files && input.files[0]) {
      var file = input.files[0];
      var reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('coverPreviewImg').src = e.target.result;
        var badge = document.getElementById('coverBadge');
        if (badge) badge.textContent = 'NEW SELECTED';
        var nameSpan = document.getElementById('new-cover-name');
        nameSpan.textContent = '✓ ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        nameSpan.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  }

  function handleGalleryFilesSelected(input) {
    var label = document.getElementById('galleryFilesLabel');
    if (input.files && input.files.length > 0) {
      label.textContent = '✓ Selected ' + input.files.length + ' image file(s)';
      label.style.color = 'var(--primary)';
    } else {
      label.textContent = 'Click or drag & drop photos to upload (multiple selection supported)';
      label.style.color = '';
    }
  }

  // Submit edit venue form with FormData
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('editVenueForm');
    var saveBtn = document.getElementById('saveBtn');
    var alertBox = document.getElementById('formAlert');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving Changes...';
      alertBox.style.display = 'none';

      var formData = new FormData(form);

      try {
        var res = await fetch('../api/venues.php?action=update', {
          method: 'POST',
          body: formData
        });
        var d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#d1fae5';
          alertBox.style.color = '#065f46';
          alertBox.textContent = '✓ Venue specifications and cover picture updated successfully!';
          if (d.data && d.data.image_url) {
            document.getElementById('inputImageUrl').value = d.data.image_url;
            var newCoverUrl = d.data.image_url.startsWith('http') ? d.data.image_url : ('../' + d.data.image_url.replace(/^(\.\.\/)+/, ''));
            document.getElementById('coverPreviewImg').src = newCoverUrl;
          }
          setTimeout(function() {
            alertBox.style.display = 'none';
          }, 3500);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating venue');
        }
      } catch(err) {
        console.error(err);
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Network communication error. Please try again.';
      } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Venue Changes →';
      }
    });
  });

  // Upload photos to gallery
  async function uploadGalleryPhotos() {
    var fileInput = document.getElementById('galleryFilesInput');
    var captionInput = document.getElementById('galleryCaptionInput');
    var alertBox = document.getElementById('galleryUploadAlert');
    var btn = document.getElementById('btnUploadGallery');

    if (!fileInput.files || fileInput.files.length === 0) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Please select at least one image file to upload.';
      return;
    }

    var formData = new FormData();
    formData.append('venue_id', '<?= $venue['id'] ?>');
    formData.append('caption', captionInput.value.trim());

    for (var i = 0; i < fileInput.files.length; i++) {
      formData.append('photos[]', fileInput.files[i]);
    }

    var origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Uploading...';
    alertBox.style.display = 'none';

    try {
      var res = await fetch('../api/venues.php?action=upload_photos', {
        method: 'POST',
        body: formData
      });
      var d = await res.json();
      if (d.success) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#d1fae5';
        alertBox.style.color = '#065f46';
        alertBox.textContent = '✓ ' + (d.message || 'Photos uploaded successfully!');
        fileInput.value = '';
        captionInput.value = '';
        document.getElementById('galleryFilesLabel').textContent = 'Click or drag & drop photos to upload';

        // Reload photos list in grid
        loadVenuePhotos();
      } else {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ ' + (d.message || 'Error uploading photos');
      }
    } catch (err) {
      console.error(err);
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Failed to upload photos. Please try again.';
    } finally {
      btn.disabled = false;
      btn.textContent = origText;
    }
  }

  // Load photos asynchronously
  async function loadVenuePhotos() {
    try {
      var res = await fetch('../api/venues.php?action=list_photos&venue_id=<?= $venue['id'] ?>');
      var d = await res.json();
      if (d.success && d.data) {
        var photos = d.data.photos || [];
        var venue = d.data.venue || {};
        var countSpan = document.getElementById('photoCount');
        if (countSpan) countSpan.textContent = photos.length;

        var grid = document.getElementById('photosGrid');
        if (photos.length === 0) {
          grid.innerHTML = '<div id="emptyPhotosMsg" style="grid-column:1/-1; text-align:center; padding:32px 16px; color:var(--gray-400); background:#fff; border:1px dashed var(--gray-300); border-radius:8px;">' +
            '<div style="font-size:2rem; margin-bottom:6px;">📷</div>' +
            '<div class="font-bold text-sm">No gallery photos uploaded yet.</div>' +
            '<div class="text-xs text-muted mt-4">Upload pictures above. Only real uploaded photos will appear when guests and customers click "View Photos".</div>' +
            '</div>';
          return;
        }

        var html = '';
        photos.forEach(function(ph) {
          var pUrl = ph.photo_url;
          var displayPUrl = pUrl.startsWith('http') ? pUrl : ('../' + pUrl.replace(/^(\.\.\/)+/, ''));
          var isCover = (ph.is_cover == 1) || (venue.image_url === pUrl || (venue.image_url && venue.image_url.endsWith(pUrl.split('/').pop())));
          var coverBadgeHtml = isCover ? '<span class="photo-cover-badge">COVER</span>' : '';

          html += '<div class="photo-card-admin" id="photo-card-' + ph.id + '">' +
            '<div class="photo-thumb-wrap">' +
              '<img src="' + displayPUrl + '" alt="Venue Photo">' +
              coverBadgeHtml +
            '</div>' +
            '<div class="photo-card-body">' +
              '<div>' +
                '<input type="text" class="form-control text-xs" id="cap-' + ph.id + '" value="' + escapeHtml(ph.caption || '') + '" placeholder="e.g. Caption...">' +
              '</div>' +
              '<div style="display:flex; gap:6px; justify-content:space-between; align-items:center; margin-top:4px;">' +
                '<button type="button" class="btn btn-ghost btn-xs" onclick="savePhotoCaption(' + ph.id + ')" title="Save caption">💾 Save</button>' +
                '<button type="button" class="btn btn-outline btn-xs" onclick="setAsCover(' + venue.id + ', ' + ph.id + ', \'' + displayPUrl + '\')" title="Set as primary cover image">⭐ Cover</button>' +
                '<button type="button" class="btn btn-ghost btn-xs" style="color:#dc2626;" onclick="deletePhoto(' + ph.id + ')" title="Delete photo">🗑️</button>' +
              '</div>' +
            '</div>' +
          '</div>';
        });
        grid.innerHTML = html;
      }
    } catch(err) {
      console.error(err);
    }
  }

  // Save photo caption
  async function savePhotoCaption(photoId) {
    var capInput = document.getElementById('cap-' + photoId);
    if (!capInput) return;
    try {
      var res = await fetch('../api/venues.php?action=update_photo', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ venue_id: <?= (int)$venue['id'] ?>, photo_id: photoId, caption: capInput.value.trim() })
      });
      var d = await res.json();
      if (d.success) {
        capInput.style.borderColor = 'var(--success)';
        setTimeout(function() { capInput.style.borderColor = ''; }, 1500);
      }
    } catch(err) {
      console.error(err);
    }
  }

  // Set photo as primary cover
  async function setAsCover(venueId, photoId, displayUrl) {
    try {
      var res = await fetch('../api/venues.php?action=set_cover_photo', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ venue_id: venueId, photo_id: photoId })
      });
      var d = await res.json();
      if (d.success) {
        document.getElementById('coverPreviewImg').src = displayUrl;
        document.getElementById('inputImageUrl').value = d.data.image_url;
        loadVenuePhotos();
      }
    } catch(err) {
      console.error(err);
    }
  }

  // Delete photo from gallery
  async function deletePhoto(photoId) {
    if (!confirm('Are you sure you want to permanently remove this photo from the venue gallery?')) return;
    try {
      var res = await fetch('../api/venues.php?action=delete_photo', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ venue_id: <?= (int)$venue['id'] ?>, photo_id: photoId })
      });
      var d = await res.json();
      if (d.success) {
        var card = document.getElementById('photo-card-' + photoId);
        if (card) card.remove();
        loadVenuePhotos();
      } else {
        alert(d.message || 'Error removing photo');
      }
    } catch(err) {
      console.error(err);
    }
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
  }
  </script>
</body>
</html>
