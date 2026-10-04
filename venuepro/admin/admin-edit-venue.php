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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Edit Venue Properties</title>
  <link rel="stylesheet" href="../css/style.css">
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
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
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
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
          <input type="text" placeholder="Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
          <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Admin')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="venue-management.php">Venue Management</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Edit Properties: <?= e($venue['name']) ?></span>
        </div>

        <div class="card" style="max-width:840px; margin:0 auto;">
          <div class="flex-between mb-16">
            <div>
              <h2 class="mb-4">Edit Venue Properties</h2>
              <p class="text-muted">Modify specifications, operational rates, and capabilities for this venue.</p>
            </div>
            <span class="pill pill-<?= $venue['status'] === 'active' ? 'confirmed' : 'pending' ?>">
              <?= strtoupper($venue['status'] ?? 'ACTIVE') ?>
            </span>
          </div>

          <form id="editVenueForm">
            <input type="hidden" name="id" value="<?= $venue['id'] ?>">

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Venue Name</label>
                <input type="text" name="name" class="form-control" value="<?= e($venue['name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Property Type</label>
                <select name="venue_type" class="form-control">
                  <?php
                  $types = ['Ballroom & Grand Hall', 'Rooftop Terrace & Lounge', 'Contemporary Industrial Gallery', 'Conference & Tech Pavilion', 'Garden & Outdoor Space'];
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
                <input type="text" name="address" class="form-control" value="<?= e($venue['address'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">District / Location</label>
                <input type="text" name="district" class="form-control" value="<?= e($venue['district'] ?? '') ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Maximum Guest Capacity</label>
                <input type="number" name="capacity" class="form-control" value="<?= e($venue['capacity'] ?? 100) ?>" required>
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
                <input type="number" step="0.01" name="base_rate" class="form-control" value="<?= e($venue['base_rate'] ?? 2000) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Hourly Overtime Fee (USD)</label>
                <input type="number" step="0.01" name="additional_hour_rate" class="form-control" value="<?= e($venue['additional_hour_rate'] ?? 300) ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description &amp; Space Overview</label>
              <textarea name="description" class="form-control" rows="4" placeholder="Enter space overview, architectural style, sound and lighting capabilities..."><?= e($venue['description'] ?? '') ?></textarea>
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
              <button type="submit" class="btn btn-primary" id="saveBtn" style="flex:2;">Save Changes →</button>
            </div>
          </form>
        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('editVenueForm');
    var saveBtn = document.getElementById('saveBtn');
    var alertBox = document.getElementById('formAlert');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';
      alertBox.style.display = 'none';

      var checkedAmenities = [];
      form.querySelectorAll('input[name="amenities[]"]:checked').forEach(function(cb) {
        checkedAmenities.push(cb.value);
      });

      var data = {
        id: form.querySelector('input[name="id"]').value,
        name: form.querySelector('input[name="name"]').value.trim(),
        venue_type: form.querySelector('select[name="venue_type"]').value,
        address: form.querySelector('input[name="address"]').value.trim(),
        district: form.querySelector('input[name="district"]').value.trim(),
        capacity: parseInt(form.querySelector('input[name="capacity"]').value, 10),
        status: form.querySelector('select[name="status"]').value,
        base_rate: parseFloat(form.querySelector('input[name="base_rate"]').value),
        additional_hour_rate: parseFloat(form.querySelector('input[name="additional_hour_rate"]').value),
        description: form.querySelector('textarea[name="description"]').value.trim(),
        amenities: checkedAmenities
      };

      try {
        var res = await fetch('../api/venues.php?action=update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(data)
        });
        var d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#d1fae5';
          alertBox.style.color = '#065f46';
          alertBox.textContent = '✓ Venue updated successfully! Redirecting...';
          setTimeout(function() {
            window.location.href = 'venue-management.php';
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating venue');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Changes →';
        }
      } catch (err) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Connection error. Please try again.';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes →';
      }
    });
  });
  </script>
</body>
</html>
