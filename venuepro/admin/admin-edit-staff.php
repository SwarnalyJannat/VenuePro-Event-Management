<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

$staffId = (int)($_GET['id'] ?? 0);
$staff = null;
if ($staffId > 0) {
    $stmt = $db->prepare("SELECT u.*, sp.staff_code, sp.department, sp.assigned_venues, sp.active_status FROM users u LEFT JOIN staff_profiles sp ON u.id = sp.user_id WHERE u.id = ? AND u.role = 'staff' LIMIT 1");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch();
}

if (!$staff) {
    header('Location: admin-staff-management.php');
    exit;
}

$stmtVenues = $db->query("SELECT id, name FROM venues WHERE status = 'active' ORDER BY name ASC");
$allVenues = $stmtVenues->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Edit Staff Member</title>
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
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item active">
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
          <a href="admin-staff-management.php">Staff Directory</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Edit Staff: <?= e($staff['name']) ?></span>
        </div>

        <div class="card" style="max-width:760px; margin:0 auto;">
          <div class="flex-between mb-16">
            <div>
              <h2 class="mb-4">Edit Staff Information</h2>
              <p class="text-muted">Update contact details, operational department, and venue assignment.</p>
            </div>
            <span class="pill pill-<?= ($staff['status'] ?? 'active') === 'active' ? 'confirmed' : 'pending' ?>">
              <?= strtoupper($staff['status'] ?? 'ACTIVE') ?>
            </span>
          </div>

          <form id="editStaffForm">
            <input type="hidden" name="id" value="<?= $staff['id'] ?>">

            <div id="staffAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e($staff['name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Staff ID / Code *</label>
                <input type="text" name="staff_code" class="form-control" value="<?= e($staff['staff_code'] ?? 'STF-00' . $staff['id']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="<?= e($staff['email']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" value="<?= e($staff['phone'] ?? '') ?>" placeholder="e.g. +1 (555) 019-2834">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Department *</label>
                <select name="department" class="form-control">
                  <?php
                  $depts = ['Event Operations', 'Technical & AV', 'Security & Safety', 'Logistics & Setup', 'Guest Services'];
                  foreach ($depts as $d):
                    $sel = (($staff['department'] ?? '') === $d) ? 'selected' : '';
                  ?>
                    <option value="<?= e($d) ?>" <?= $sel ?>><?= e($d) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Multi-venue assignment -->
            <div class="form-group" style="margin-top:8px;">
              <label class="form-label">Assigned Venues</label>
              <p class="text-xs text-muted mb-8">Select one or more venues for this staff member.</p>
              <?php
              // Parse current assigned venue IDs
              $assignedIds = [];
              $raw = $staff['assigned_venues'] ?? '';
              foreach (explode(',', $raw) as $vid) {
                  $vid = trim($vid);
                  if (is_numeric($vid) && (int)$vid > 0) $assignedIds[] = (int)$vid;
              }
              ?>
              <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:8px; margin-top:4px;">
                <?php if (empty($allVenues)): ?>
                <p class="text-sm text-muted">No venues found.</p>
                <?php else: foreach ($allVenues as $v): $checked = in_array((int)$v['id'], $assignedIds) ? 'checked' : ''; ?>
                <label style="display:flex; align-items:center; gap:8px; padding:8px 12px; border:1.5px solid var(--gray-200); border-radius:6px; cursor:pointer; <?= $checked ? 'border-color:var(--primary); background:#eff6ff;' : '' ?>">
                  <input type="checkbox" name="assigned_venues[]" value="<?= (int)$v['id'] ?>" <?= $checked ?> style="accent-color:var(--primary);">
                  <span class="text-sm font-semibold"><?= e($v['name']) ?></span>
                </label>
                <?php endforeach; endif; ?>
              </div>
            </div>

            <div class="flex gap-12 mt-24">
              <a href="admin-staff-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
              <button type="submit" id="saveStaffBtn" class="btn btn-primary" style="flex:2;">Save Staff Changes →</button>
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
    var form = document.getElementById('editStaffForm');
    var saveBtn = document.getElementById('saveStaffBtn');
    var alertBox = document.getElementById('staffAlert');

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving Changes...';
      alertBox.style.display = 'none';

      // Collect multi-venue IDs
      var venueIds = [];
      form.querySelectorAll('[name="assigned_venues[]"]:checked').forEach(function(cb) {
        venueIds.push(parseInt(cb.value));
      });
      var venueStr = venueIds.join(',');

      var data = {
        id:              form.querySelector('[name="id"]').value,
        name:            form.querySelector('[name="name"]').value.trim(),
        staff_code:      form.querySelector('[name="staff_code"]').value.trim(),
        email:           form.querySelector('[name="email"]').value.trim(),
        phone:           form.querySelector('[name="phone"]').value.trim(),
        department:      form.querySelector('[name="department"]').value,
        assigned_venues: venueStr,
        status:          'active'
      };

      try {
        var res = await fetch('../api/staff.php?action=update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify(data)
        });
        var d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#dcfce7';
          alertBox.style.color = '#166534';
          alertBox.textContent = '✓ Staff member updated successfully! Redirecting...';
          setTimeout(function() {
            window.location.href = 'admin-staff-management.php';
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating staff');
          saveBtn.disabled = false;
          saveBtn.textContent = 'Save Staff Changes →';
        }
      } catch(err) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Connection error. Please try again.';
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Staff Changes →';
      }
    });
  });
  </script>
</body>
</html>

