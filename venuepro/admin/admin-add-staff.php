<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

// Fetch all venues for multi-assign
$venues = $db->query("SELECT id, name FROM venues ORDER BY name ASC")->fetchAll();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'")->fetchColumn();
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
            <span class="badge"><?= $pendingCount ?></span>
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
          <input type="password" name="password" class="form-control" placeholder="e.g. •••••••• (min. 6 characters)" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <div class="password-wrap">
          <input type="password" name="confirm_password" class="form-control" placeholder="e.g. •••••••• (re-enter password)" required>
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
    <div class="section-divider">Verification Documents *</div>
    <p class="text-sm text-muted mb-12">Verification documents must be submitted prior to onboarding. CV / Resume and National Identity Document (NID) scan are required.</p>
    <div class="grid-2 gap-20 mb-24">
      <div>
        <label class="form-label">Curriculum Vitae (CV / Resume) *</label>
        <div class="upload-zone" id="zone-cv" onclick="document.getElementById('cv-upload').click()">
          <div id="preview-cv">
            <span class="upload-icon">📄</span>
            <span class="upload-text font-semibold">Click to upload CV / Resume</span>
            <span class="upload-hint">PDF, DOC, DOCX — max 10 MB</span>
          </div>
        </div>
        <input id="cv-upload" type="file" accept=".pdf,.doc,.docx" style="display:none;" onchange="handleDocUpload(this, 'zone-cv', 'preview-cv')">
      </div>
      <div>
        <label class="form-label">National Identity Document (NID) Scan *</label>
        <div class="upload-zone" id="zone-nid" onclick="document.getElementById('nid-upload').click()">
          <div id="preview-nid">
            <span class="upload-icon">🪪</span>
            <span class="upload-text font-semibold">Click to upload NID Scan</span>
            <span class="upload-hint">JPG, PNG, PDF — max 5 MB</span>
          </div>
        </div>
        <input id="nid-upload" type="file" accept=".jpg,.jpeg,.png,.pdf" style="display:none;" onchange="handleDocUpload(this, 'zone-nid', 'preview-nid')">
      </div>
    </div>
    <div id="staffAlertBottom" style="display:none; padding:12px 16px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

    <div class="flex gap-12 mt-24">
      <a href="admin-staff-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
      <button type="submit" id="btnAddStaff" class="btn btn-primary" style="flex:2;">
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
var uploadedFiles = {};

function handleDocUpload(input, zoneId, previewId) {
  var zone = document.getElementById(zoneId);
  var preview = document.getElementById(previewId) || zone;
  if (!zone || !input.files || !input.files[0]) return;
  var file = input.files[0];
  uploadedFiles[zoneId] = file;

  zone.style.borderColor = 'var(--success)';
  zone.style.background = '#f0fdf4';

  if (file.type.startsWith('image/')) {
    var reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<div style="padding:12px; text-align:center;">' +
        '<img src="' + e.target.result + '" style="max-height:80px; max-width:100%; border-radius:6px; margin-bottom:8px; display:inline-block;" alt="Selected Document">' +
        '<div style="font-size:13px; font-weight:600; color:var(--success);">✓ ' + escapeHtml(file.name) + ' (' + Math.round(file.size / 1024) + ' KB)</div>' +
        '<div style="font-size:11px; color:var(--gray-500); margin-top:4px;">Click to change</div>' +
      '</div>';
    };
    reader.readAsDataURL(file);
  } else {
    preview.innerHTML = '<div style="padding:16px; text-align:center;">' +
      '<div style="font-size:32px; margin-bottom:6px;">📄</div>' +
      '<div style="font-size:13px; font-weight:600; color:var(--navy-900);">✓ ' + escapeHtml(file.name) + '</div>' +
      '<div style="font-size:12px; color:var(--success); font-weight:500; margin-top:2px;">Ready (' + Math.round(file.size / 1024) + ' KB)</div>' +
      '<div style="font-size:11px; color:var(--gray-500); margin-top:4px;">Click to change</div>' +
    '</div>';
  }
}

function escapeHtml(text) {
  var div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('addStaffForm');
  var alertBoxTop = document.getElementById('staffAlert');
  var alertBoxBottom = document.getElementById('staffAlertBottom');
  var btn = document.getElementById('btnAddStaff');
  if (!form) return;

  function showMessage(msg, isSuccess) {
    [alertBoxTop, alertBoxBottom].forEach(function(box) {
      if (!box) return;
      box.style.display = 'block';
      box.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
      box.style.border = isSuccess ? '1px solid #86efac' : '1px solid #f87171';
      box.style.color = isSuccess ? '#166534' : '#991b1b';
      box.innerHTML = (isSuccess ? '✓ ' : '⚠️ ') + msg;
    });
    if (!isSuccess && alertBoxBottom) {
      alertBoxBottom.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function hideMessage() {
    [alertBoxTop, alertBoxBottom].forEach(function(box) {
      if (box) box.style.display = 'none';
    });
  }

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    hideMessage();

    var nameVal  = ((form.querySelector('[name="name"]') || {}).value || '').trim();
    var emailVal = ((form.querySelector('[name="email"]') || {}).value || '').trim();
    var phoneVal = ((form.querySelector('[name="phone"]') || {}).value || '').trim();
    var deptVal  = ((form.querySelector('[name="department"]') || {}).value || 'Event Operations');
    var pw       = (form.querySelector('[name="password"]') || {}).value || '';
    var cpw      = (form.querySelector('[name="confirm_password"]') || {}).value || '';

    if (!nameVal || !emailVal) {
      showMessage('Full legal name and corporate email are required.', false);
      return;
    }

    if (pw.length < 6) {
      showMessage('Password must be at least 6 characters.', false);
      return;
    }

    if (pw !== cpw) {
      showMessage('Passwords do not match. Please re-enter both password fields.', false);
      return;
    }
// Enforce Verification Documents submission before adding the staff member
    var cvInput  = document.getElementById('cv-upload');
    var nidInput = document.getElementById('nid-upload');
    var hasCv  = !!uploadedFiles['zone-cv']  || (cvInput  && cvInput.files  && cvInput.files.length  > 0);
    var hasNid = !!uploadedFiles['zone-nid'] || (nidInput && nidInput.files && nidInput.files.length > 0);

    if (!hasCv || !hasNid) {
      var missing = [];
      if (!hasCv) {
        missing.push('Curriculum Vitae (CV / Resume)');
        var z1 = document.getElementById('zone-cv');
        if (z1) { z1.style.borderColor = '#dc2626'; z1.style.background = '#fff5f5'; }
      }
      if (!hasNid) {
        missing.push('National Identity Document (NID) Scan');
        var z2 = document.getElementById('zone-nid');
        if (z2) { z2.style.borderColor = '#dc2626'; z2.style.background = '#fff5f5'; }
      }
      showMessage('<strong>Verification Documents Missing:</strong> Verification documents must be submitted before adding a staff member. Please upload: ' + missing.join(' and ') + '.', false);
      return;
    }

    // Collect selected venue IDs
    var assignedVenues = [];
    form.querySelectorAll('[name="assigned_venues[]"]:checked').forEach(function(cb) {
      assignedVenues.push(parseInt(cb.value));
    });

    var staffCode = 'STF-' + Math.floor(1000 + Math.random() * 9000);
    var payload = {
      name:            nameVal,
      email:           emailVal,
      phone:           phoneVal,
      password:        pw,
      staff_code:      staffCode,
      department:      deptVal,
      assigned_venues: assignedVenues
    };

    var origText = btn ? btn.textContent : 'Add Staff Member →';
    if (btn) { btn.disabled = true; btn.textContent = 'Adding Staff Member in Database...'; }

    try {
      var res = await fetch('../api/staff.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'include',
        body: JSON.stringify(payload)
      });
      var d = await res.json();
      if (d.success) {
        showMessage('Staff member added successfully! Staff Code: ' + (d.data?.staff_code || staffCode) + '. Redirecting...', true);
        setTimeout(function() { window.location.href = 'admin-staff-management.php'; }, 900);
      } else {
        showMessage(d.message || 'Failed to add staff member. Please check fields.', false);
        if (btn) { btn.disabled = false; btn.textContent = origText; }
      }
    } catch(err) {
      console.error(err);
      showMessage('Network connection error. Please try again.', false);
      if (btn) { btn.disabled = false; btn.textContent = origText; }
    }
  });
});
</script>
</body>
</html>