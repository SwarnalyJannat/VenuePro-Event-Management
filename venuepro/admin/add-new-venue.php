<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Add New Venue</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div>
        </div>
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
        <a href="admin-reports.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item logout-link" style="color:var(--gray-400);">
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'admin')) ?></div>
            </div>
          </a>
        </div>
      </header>
      <main class="page-body">

<div class="breadcrumb">
  <a href="venue-management.php">Venue Management</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current">Add New Venue</span>
</div>

<div class="card" style="max-width:840px; margin:0 auto;">
  <h2 class="mb-4">Add New Enterprise Venue</h2>
  <p class="mb-24">Publish an enterprise space to the verified VenuePro inventory.</p>

  <div id="venueAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

  <form id="addVenueForm">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Venue Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. The Glass House Pavillion" required>
      </div>
      <div class="form-group">
        <label class="form-label">Property Type</label>
        <select name="venue_type" class="form-control">
          <option value="Ballroom">Ballroom &amp; Grand Hall</option>
          <option value="Rooftop">Rooftop Terrace &amp; Lounge</option>
          <option value="Gallery">Contemporary Industrial Gallery</option>
          <option value="Conference">Conference &amp; Tech Pavilion</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Full Address *</label>
      <input type="text" name="address" class="form-control" placeholder="e.g. 12 Enterprise Blvd, Level 3, Downtown" required>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">District / Location</label>
        <input type="text" name="district" class="form-control" placeholder="e.g. West Garden Hills">
      </div>
      <div class="form-group">
        <label class="form-label">Maximum Guest Capacity</label>
        <input type="number" name="capacity" class="form-control" placeholder="e.g. 400" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Daily Base Rate (USD)</label>
        <input type="number" name="base_rate" class="form-control" placeholder="e.g. 2800" required>
      </div>
      <div class="form-group">
        <label class="form-label">Hourly Overtime Fee</label>
        <input type="number" name="additional_hour_rate" class="form-control" placeholder="e.g. 350">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Venue Description</label>
      <textarea name="description" class="form-control" rows="3" placeholder="e.g. A stunning waterfront ballroom with panoramic city views, featuring state-of-the-art AV systems..."></textarea>
    </div>

    <div class="form-group">
      <label class="form-label">Venue Image</label>
      <div style="border: 2px dashed var(--gray-300); border-radius: 8px; padding: 24px 16px; text-align: center; background: var(--gray-50); cursor: pointer;" onclick="document.getElementById('venue-image-input').click()">
        <div style="font-size: 32px; line-height: 1; margin-bottom: 8px;">🖼️</div>
        <div style="font-weight: 600; font-size: 14px; color: var(--navy-900);">Click to upload venue image or drag and drop</div>
        <div style="font-size: 12px; color: var(--gray-500); margin-top: 4px;">Supports PNG, JPG, or WEBP (Max 10MB)</div>
        <input type="file" id="venue-image-input" name="venue_image" accept="image/*" style="display:none;" onchange="previewVenueImage(this)">
        <div id="venue-image-preview-container" style="display:none; margin-top:12px; align-items:center; justify-content:center; gap:8px;">
          <span id="venue-image-name" style="font-size:13px; font-weight:600; color:var(--success);"></span>
        </div>
      </div>
      <div style="margin-top: 8px;">
        <input type="text" name="image_url" class="form-control" placeholder="e.g. assets/venues pic/images.jpg (or upload file above)">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Key Amenities Included</label>
      <div class="grid-4 gap-8">
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Fiber WiFi"> Fiber WiFi</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Sound Rig"> Sound Rig</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="In-House Catering"> In-House Catering</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Valet Parking"> Valet Parking</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="ADA Elevator"> ADA Elevator</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Breakout Rooms"> Breakout Rooms</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Security Detail"> Security Detail</label>
        <label class="filter-check"><input type="checkbox" name="amenities[]" value="Loading Dock"> Loading Dock</label>
      </div>
    </div>

    <div class="flex gap-12 mt-24">
      <a href="venue-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
      <button type="submit" class="btn btn-primary" style="flex:2;">Save &amp; Publish Venue →</button>
    </div>
  </form>
</div>
</main>
      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
  <script>
    function previewVenueImage(input) {
      if (input.files && input.files[0]) {
        const container = document.getElementById('venue-image-preview-container');
        const nameSpan = document.getElementById('venue-image-name');
        nameSpan.textContent = '✓ Selected: ' + input.files[0].name + ' (' + Math.round(input.files[0].size / 1024) + ' KB)';
        container.style.display = 'flex';
      }
    }
  </script>
<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('addVenueForm');
  var alertBox = document.getElementById('venueAlert');
  if (!form) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    alertBox.style.display = 'none';
    var btn = form.querySelector('[type="submit"]');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Publishing Venue & Uploading Image...'; }

    var nameVal = (form.querySelector('[name="name"]') || {}).value || '';
    var addrVal = (form.querySelector('[name="address"]') || {}).value || '';

    if (!nameVal.trim() || !addrVal.trim()) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Venue Name and Address are required.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
      return;
    }

    var formData = new FormData(form);

    try {
      var res = await fetch('../api/venues.php?action=create', {
        method: 'POST',
        body: formData
      });
      var d = await res.json();
      if (d.success) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#dcfce7';
        alertBox.style.color = '#166534';
        alertBox.textContent = '✓ Venue published with cover photo successfully! Redirecting...';
        setTimeout(function() { window.location.href = 'venue-management.php'; }, 900);
      } else {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ ' + (d.message || 'Error creating venue');
        if (btn) { btn.disabled = false; btn.textContent = origText; }
      }
    } catch(err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Connection error. Please try again.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
    }
  });
});
</script>
</body>
</html>