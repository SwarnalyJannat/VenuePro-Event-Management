<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

$catererId = (int)($_GET['id'] ?? 0);
$caterer = null;
if ($catererId > 0) {
    $stmt = $db->prepare(
        "SELECT u.*, cp.business_name, cp.owner_name, cp.kitchen_address, cp.specialization, cp.approval_status 
         FROM users u 
         JOIN caterer_profiles cp ON u.id = cp.user_id 
         WHERE u.id = ? AND u.role = 'caterer' 
         LIMIT 1"
    );
    $stmt->execute([$catererId]);
    $caterer = $stmt->fetch();
}

if (!$caterer) {
    header('Location: admin-catering-management.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Edit Caterer</title>
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item active">
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
          <input type="text" placeholder="Search bookings, venues, staff, caterers...">
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
          <a href="admin-catering-management.php">Catering Oversight</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Edit Caterer: <?= e($caterer['business_name'] ?: $caterer['name']) ?></span>
        </div>

        <div class="card" style="max-width:820px; margin:0 auto;">
          <div class="flex-between mb-16">
            <div>
              <h2 class="mb-4">Edit Catering Partner</h2>
              <p class="text-muted">Update culinary credentials, facility contact details, and partnership status.</p>
            </div>
            <span class="pill pill-<?= ($caterer['approval_status'] ?? 'approved') === 'approved' ? 'confirmed' : 'pending' ?>">
              <?= strtoupper($caterer['approval_status'] ?? 'APPROVED') ?>
            </span>
          </div>

          <form id="editCatererForm">
            <input type="hidden" name="id" value="<?= (int)$caterer['id'] ?>">

            <div id="catererAlert" style="display:none; padding:12px 16px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

            <div class="section-divider" style="font-weight:700; margin-bottom:16px; border-bottom:1px solid var(--gray-200); padding-bottom:8px;">Business &amp; Facility Details</div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Catering Business Name *</label>
                <input type="text" name="business_name" class="form-control" value="<?= e($caterer['business_name'] ?: $caterer['name']) ?>" placeholder="e.g. Epicurean Events Co." required>
              </div>
              <div class="form-group">
                <label class="form-label">Primary Contact Person / Executive Chef *</label>
                <input type="text" name="owner_name" class="form-control" value="<?= e($caterer['owner_name'] ?: $caterer['name']) ?>" placeholder="e.g. Chef Marcus Vance" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Business Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e($caterer['email']) ?>" placeholder="e.g. marcus@epicurean.com" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= e($caterer['phone'] ?? '') ?>" placeholder="e.g. +1 (555) 349-2091">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Kitchen / Commercial Facility Address</label>
              <input type="text" name="kitchen_address" class="form-control" value="<?= e($caterer['kitchen_address'] ?? '') ?>" placeholder="e.g. Suite 400, 782 Culinary Way, Downtown">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Culinary Specialization *</label>
                <select name="specialization" class="form-control">
                  <?php
                  $specs = [
                      'Contemporary Fine Dining & Plated Service',
                      'High-End Buffet & Interactive Stations',
                      'Artisanal Canapés & Cocktail Reception',
                      'Global Fusion & Dietary-Specialized Menus',
                      'Fine Dining, Executive Banquets, Molecular Gastronomy',
                      'Traditional Banquets & Custom Menus'
                  ];
                  $currSpec = $caterer['specialization'] ?? 'Contemporary Fine Dining & Plated Service';
                  if (!in_array($currSpec, $specs) && !empty($currSpec)) {
                      $specs[] = $currSpec;
                  }
                  foreach ($specs as $sp):
                    $sel = ($currSpec === $sp) ? 'selected' : '';
                  ?>
                    <option value="<?= e($sp) ?>" <?= $sel ?>><?= e($sp) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Approval Status *</label>
                <select name="approval_status" class="form-control">
                  <option value="approved" <?= ($caterer['approval_status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved / Certified</option>
                  <option value="under_review" <?= ($caterer['approval_status'] ?? '') === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                  <option value="rejected" <?= ($caterer['approval_status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected / Suspended</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Operating Account Status *</label>
              <select name="status" class="form-control">
                <option value="active" <?= ($caterer['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active (Can receive orders &amp; publish packages)</option>
                <option value="inactive" <?= ($caterer['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden from public catalog)</option>
              </select>
            </div>

            <div class="flex gap-12 mt-24">
              <a href="admin-catering-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
              <button type="submit" id="saveCatererBtn" class="btn btn-primary" style="flex:2;">Save Caterer Changes →</button>
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
    var form = document.getElementById('editCatererForm');
    var saveBtn = document.getElementById('saveCatererBtn');
    var alertBox = document.getElementById('catererAlert');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving Changes in Database...';
      alertBox.style.display = 'none';

      var data = {
        id:              parseInt(form.querySelector('[name="id"]').value),
        business_name:   form.querySelector('[name="business_name"]').value.trim(),
        owner_name:      form.querySelector('[name="owner_name"]').value.trim(),
        email:           form.querySelector('[name="email"]').value.trim(),
        phone:           form.querySelector('[name="phone"]').value.trim(),
        kitchen_address: form.querySelector('[name="kitchen_address"]').value.trim(),
        specialization:  form.querySelector('[name="specialization"]').value,
        approval_status: form.querySelector('[name="approval_status"]').value,
        status:          form.querySelector('[name="status"]').value
      };

      try {
        var res = await fetch('../api/caterers.php?action=update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          credentials: 'include',
          body: JSON.stringify(data)
        });
        var d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#dcfce7';
          alertBox.style.border = '1px solid #86efac';
          alertBox.style.color = '#166534';
          alertBox.textContent = '✓ ' + (d.message || 'Caterer updated successfully in database! Redirecting...');
          setTimeout(function() {
            window.location.href = 'admin-catering-management.php';
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.border = '1px solid #f87171';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating caterer');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Caterer Changes →';
        }
      } catch(err) {
        console.error(err);
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.border = '1px solid #f87171';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Connection error. Please try again.';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Caterer Changes →';
      }
    });
  });
  </script>
</body>
</html>
