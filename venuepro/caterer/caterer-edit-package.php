<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();

$pkgId = (int)($_GET['id'] ?? 0);
$pkg = null;
if ($pkgId > 0) {
    $stmt = $db->prepare("SELECT * FROM catering_packages WHERE id = ? LIMIT 1");
    $stmt->execute([$pkgId]);
    $pkg = $stmt->fetch();
}

if (!$pkg) {
    header('Location: caterer-food-packages.php');
    exit;
}

$features = [];
if (!empty($pkg['features'])) {
    $features = is_array($pkg['features']) ? $pkg['features'] : (json_decode($pkg['features'], true) ?? []);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Caterer – Edit Package</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .sidebar-logo-sub { color: #10b981; }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub">Kitchen Operations</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Package Library
        </a>
        <a href="caterer-menu-items.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg> Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
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
          <input type="text" placeholder="Search kitchen orders, menus, packages...">
        </div>
        <div class="topbar-actions">
          <a href="caterer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">5</span>
            🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#059669;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="caterer-food-packages.php">Packages</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Edit: <?= e($pkg['title']) ?></span>
        </div>

        <div class="card" style="max-width:820px; margin:0 auto;">
          <div class="flex-between mb-16">
            <div>
              <h2 class="mb-4">Edit Food Package</h2>
              <p class="text-muted">Update pricing, guest minimums, culinary description, and features for this package.</p>
            </div>
            <span class="pill pill-<?= $pkg['status'] === 'published' ? 'confirmed' : 'pending' ?>">
              <?= strtoupper($pkg['status'] ?? 'PUBLISHED') ?>
            </span>
          </div>

          <form id="editPackageForm">
            <input type="hidden" name="id" value="<?= $pkg['id'] ?>">

            <div id="pkgAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Package Title *</label>
                <input type="text" name="title" class="form-control" value="<?= e($pkg['title']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Package Tier *</label>
                <select name="tier" class="form-control">
                  <?php foreach (['Gold', 'Platinum', 'Signature', 'Custom'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($pkg['tier'] === $t) ? 'selected' : '' ?>><?= $t ?> Package</option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Price per Event (USD) *</label>
                <input type="number" step="0.01" name="price_per_event" class="form-control" value="<?= e($pkg['price_per_event']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Price per Guest (USD)</label>
                <input type="number" step="0.01" name="price_per_guest" class="form-control" value="<?= e($pkg['price_per_guest']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Minimum Guests</label>
                <input type="number" name="min_guests" class="form-control" value="<?= e($pkg['min_guests'] ?? 30) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Maximum Capacity</label>
                <input type="number" name="max_capacity" class="form-control" value="<?= e($pkg['max_capacity'] ?? 500) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Cuisine Type</label>
                <input type="text" name="cuisine_type" class="form-control" value="<?= e($pkg['cuisine_type'] ?? 'Contemporary Fine Dining') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Service Style</label>
                <input type="text" name="service_style" class="form-control" value="<?= e($pkg['service_style'] ?? 'Plated 3-Course Banquet') ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Publication Status</label>
              <select name="status" class="form-control">
                <option value="published" <?= ($pkg['status'] === 'published') ? 'selected' : '' ?>>Published (Live on Marketplace)</option>
                <option value="draft" <?= ($pkg['status'] === 'draft') ? 'selected' : '' ?>>Draft (Hidden)</option>
                <option value="archived" <?= ($pkg['status'] === 'archived') ? 'selected' : '' ?>>Archived</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Package Description</label>
              <textarea name="description" class="form-control" rows="4" required><?= e($pkg['description']) ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Package Highlights &amp; Inclusions (one per line)</label>
              <textarea name="features_text" class="form-control" rows="4" placeholder="Enter key package features..."><?php
                if (!empty($features)) {
                    foreach ($features as $f) {
                        echo e(is_string($f) ? $f : ($f['name'] ?? '')) . "\n";
                    }
                } else {
                    echo "Professional banquet setup and table service\nFull menu courses with dietary accommodations\nDedicated culinary event coordinator\nPost-event cleanup and sanitation";
                }
              ?></textarea>
            </div>

            <div class="flex gap-12 mt-24">
              <a href="caterer-food-packages.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
              <button type="submit" id="savePkgBtn" class="btn btn-primary" style="flex:2; background:#059669; border-color:#059669;">Save Package Changes →</button>
            </div>
          </form>
        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>

  <script src="../js/app.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('editPackageForm');
    var saveBtn = document.getElementById('savePkgBtn');
    var alertBox = document.getElementById('pkgAlert');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving Changes...';
      alertBox.style.display = 'none';

      var featuresText = form.querySelector('[name="features_text"]').value.trim();
      var featuresArr = featuresText.split('\n').map(s => s.trim()).filter(s => s.length > 0);

      var data = {
        id:              form.querySelector('[name="id"]').value,
        title:           form.querySelector('[name="title"]').value.trim(),
        tier:            form.querySelector('[name="tier"]').value,
        price_per_event: parseFloat(form.querySelector('[name="price_per_event"]').value),
        price_per_guest: parseFloat(form.querySelector('[name="price_per_guest"]').value),
        min_guests:      parseInt(form.querySelector('[name="min_guests"]').value, 10),
        max_capacity:    parseInt(form.querySelector('[name="max_capacity"]').value, 10),
        cuisine_type:    form.querySelector('[name="cuisine_type"]').value.trim(),
        service_style:   form.querySelector('[name="service_style"]').value.trim(),
        status:          form.querySelector('[name="status"]').value,
        description:     form.querySelector('[name="description"]').value.trim(),
        features:        featuresArr
      };

      try {
        var res = await fetch('../api/packages.php?action=update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(data)
        });
        var d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#dcfce7';
          alertBox.style.color = '#166534';
          alertBox.textContent = '✓ Catering package updated successfully! Redirecting...';
          setTimeout(function() {
            window.location.href = 'caterer-food-packages.php';
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating package');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Package Changes →';
        }
      } catch(err) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Network connection error. Please try again.';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Package Changes →';
      }
    });
  });
  </script>
</body>
</html>
