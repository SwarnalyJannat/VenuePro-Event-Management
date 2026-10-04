<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

// Fetch all venues for multi-assign
$venues = $db->query("SELECT id, name FROM venues ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Add New Staff</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
.upload-zone {
  border: 2px dashed var(--gray-300); border-radius: var(--radius);
  padding: 28px; text-align: center; background: var(--gray-50);
  transition: border-color .2s; cursor: pointer;
}
.upload-zone:hover { border-color: var(--primary); background: #eff6ff; }
.upload-zone input[type=file] { display:none; }
.upload-zone label {
  display: flex; flex-direction: column; align-items: center; gap: 10px;
  cursor: pointer;
}
.upload-icon { font-size: 32px; }
.upload-text { font-size: .85rem; color: var(--gray-600); }
.upload-hint { font-size: .7rem; color: var(--gray-400); }
.section-divider {
  font-size: .75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: var(--gray-500);
  display: flex; align-items: center; gap: 12px; margin: 24px 0 16px;
}
.section-divider::after { content:''; flex:1; height:1px; background:var(--gray-200); }
.venue-check-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:8px; margin-top:8px; }
.venue-check-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border:1.5px solid var(--gray-200); border-radius:6px; cursor:pointer; transition:all .15s; }
.venue-check-item:has(input:checked) { border-color:var(--primary); background:#eff6ff; }
.venue-check-item input { accent-color: var(--primary); }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div></div>
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
        <a href="../venues.php" class="nav-item logout-link" style="color:var(--gray-400);">
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
  <span class="breadcrumb-current">Add New Staff Member</span>
</div>

<div class="card" style="max-width:820px; margin:0 auto;">
  <h2 class="mb-4">Add Staff Member</h2>
  <p class="mb-24">Provision credentials, designate department, and assign venues.</p>

  <div id="staffAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

  <form id="addStaffForm">
    <!-- Basic Info -->
    <div class="section-divider">Personal Information</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Full Legal Name *</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Jordan Hayes" required>
      </div>
      <div class="form-group">
        <label class="form-label">Corporate Email *</label>
        <input type="email" name="email" class="form-control" placeholder="e.g. j.hayes@venuepro.com" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-control" placeholder="e.g. +1 (555) 000-0000">
      </div>
      <div class="form-group">
        <label class="form-label">Department</label>
        <select name="department" class="form-control">
          <option value="Event Operations">Event Operations</option>
          <option value="AV & Technology">AV &amp; Technology</option>
          <option value="Security">Security</option>
          <option value="Catering Support">Catering Support</option>
          <option value="Logistics">Logistics</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Password *</label>
        <div class="password-wrap">
          <input type="password" name="password" class="form-control" placeholder="Create staff login password" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-wrap">
          <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required>
        </div>
      </div>
    </div>

    <!-- Venue Assignment -->
    <div class="section-divider">Venue Assignment</div>
    <p class="text-sm text-muted mb-8">Select one or more venues this staff member will be assigned to.</p>
    <div class="venue-check-grid">
      <?php if (empty($venues)): ?>
      <p class="text-sm text-muted">No venues found. Add venues first.</p>
      <?php else: ?>
      <?php foreach ($venues as $v): ?>
      <label class="venue-check-item">
        <input type="checkbox" name="assigned_venues[]" value="<?= (int)$v['id'] ?>">
        <span class="text-sm font-semibold"><?= e($v['name']) ?></span>
      </label>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Document Upload -->
    <div class="section-divider">Verification Documents</div>
    <div class="grid-2 gap-20 mb-24">
      <div>
        <label class="form-label">Curriculum Vitae (CV / Resume)</label>
        <div class="upload-zone" id="zone-cv">
          <label for="cv-upload">
            <span class="upload-icon">📄</span>
            <span class="upload-text font-semibold">Click to upload CV / Resume</span>
            <span class="upload-hint">PDF, DOC, DOCX — max 10 MB</span>
          </label>
          <input id="cv-upload" type="file" accept=".pdf,.doc,.docx" onchange="handleDocUpload(this,'zone-cv')">
        </div>
      </div>
      <div>
        <label class="form-label">National Identity Document (NID) Scan</label>
        <div class="upload-zone" id="zone-nid">
          <label for="nid-upload">
            <span class="upload-icon">🪪</span>
            <span class="upload-text font-semibold">Click to upload NID Scan</span>
            <span class="upload-hint">JPG, PNG, PDF — max 5 MB</span>
          </label>
          <input id="nid-upload" type="file" accept=".jpg,.jpeg,.png,.pdf" onchange="handleDocUpload(this,'zone-nid')">
        </div>
      </div>
    </div>

    <div class="flex gap-12 mt-24">
      <a href="admin-staff-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
      <button type="submit" class="btn btn-primary" style="flex:2;">
        Add Staff Member →
      </button>
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
function handleDocUpload(input, zoneId) {
  var zone = document.getElementById(zoneId);
  if (!zone || !input.files || !input.files[0]) return;
  var file = input.files[0];
  if (file.type.startsWith('image/')) {
    var reader = new FileReader();
    reader.onload = function(ev) {
      zone.innerHTML = '<div style="padding:12px; text-align:center;">' +
        '<img src="' + ev.target.result + '" style="max-height:80px; max-width:100%; border-radius:6px; margin-bottom:8px;">' +
        '<div style="font-size:13px; font-weight:600; color:var(--success);">✓ ' + file.name + ' (' + Math.round(file.size/1024) + ' KB)</div>' +
        '<div style="font-size:11px; color:var(--gray-500); margin-top:4px; cursor:pointer;">Click to change</div>' +
      '</div>';
      zone.style.borderColor = 'var(--success)';
      zone.style.background = '#f0fdf4';
      zone.onclick = function() { input.click(); };
    };
    reader.readAsDataURL(file);
  } else {
    zone.innerHTML = '<div style="padding:16px; text-align:center;">' +
      '<div style="font-size:32px; margin-bottom:6px;">📄</div>' +
      '<div style="font-size:13px; font-weight:600; color:var(--navy-900);">✓ ' + file.name + '</div>' +
      '<div style="font-size:12px; color:var(--success); font-weight:500; margin-top:2px;">Ready (' + Math.round(file.size/1024) + ' KB)</div>' +
      '<div style="font-size:11px; color:var(--gray-500); margin-top:4px; cursor:pointer;">Click to change</div>' +
    '</div>';
    zone.style.borderColor = 'var(--success)';
    zone.style.background = '#f0fdf4';
    zone.onclick = function() { input.click(); };
  }
}

document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('addStaffForm');
  var alertBox = document.getElementById('staffAlert');
  if (!form) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    alertBox.style.display = 'none';
    var btn = form.querySelector('[type="submit"]');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Adding...'; }

    var pw  = (form.querySelector('[name="password"]') || {}).value || '';
    var cpw = (form.querySelector('[name="confirm_password"]') || {}).value || '';
    if (pw !== cpw) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Passwords do not match.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
      return;
    }

    // Collect selected venue IDs
    var assignedVenues = [];
    form.querySelectorAll('[name="assigned_venues[]"]:checked').forEach(function(cb) {
      assignedVenues.push(parseInt(cb.value));
    });

    var staffCode = 'STF-' + Math.floor(1000 + Math.random() * 9000);
    var payload = {
      name:            (form.querySelector('[name="name"]') || {}).value || '',
      email:           (form.querySelector('[name="email"]') || {}).value || '',
      phone:           (form.querySelector('[name="phone"]') || {}).value || '',
      password:        pw,
      role:            'staff',
      staff_id:        staffCode,
      department:      (form.querySelector('[name="department"]') || {}).value || 'Event Operations',
      assigned_venues: assignedVenues
    };

    if (!payload.name || !payload.email) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Name and email are required.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
      return;
    }

    try {
      var res = await fetch('../api/auth.php?action=register', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      var d = await res.json();
      if (d.success) {
        // Also save venue assignments via staff API
        if (assignedVenues.length > 0 && d.data && d.data.user_id) {
          await fetch('../api/staff.php?action=assign_venues', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ user_id: d.data.user_id, venue_ids: assignedVenues })
          });
        }
        alertBox.style.display = 'block';
        alertBox.style.background = '#dcfce7'; alertBox.style.color = '#166534';
        alertBox.textContent = '✓ Staff member added successfully! Staff Code: ' + staffCode + '. Redirecting...';
        setTimeout(function() { window.location.href = 'admin-staff-management.php'; }, 1200);
      } else {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ ' + (d.message || 'Failed to add staff');
        if (btn) { btn.disabled = false; btn.textContent = origText; }
      }
    } catch(err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Connection error. Please try again.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
    }
  });
});
</script>
</body>
</html>