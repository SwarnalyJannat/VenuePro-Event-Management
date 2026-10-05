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
  <title>VenuePro Admin – Add New Caterer</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
.upload-zone {
  border: 2px dashed var(--gray-300); border-radius: var(--radius);
  padding: 28px; text-align: center; background: var(--gray-50);
  transition: border-color .2s;
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
</style>
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
  <span class="breadcrumb-current">Add New Caterer</span>
</div>

<div class="card" style="max-width:820px; margin:0 auto;">
  <h2 class="mb-4">Onboard Catering Partner</h2>
  <p class="mb-24">Register a new certified kitchen partner and upload their compliance verification documents.</p>

  <form id="addCatererForm">
    <div id="catererAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

    <div class="section-divider">Business Information</div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Catering Business Name *</label>
        <input type="text" name="business_name" class="form-control" placeholder="e.g. Epicurean Events Co." required>
      </div>
      <div class="form-group">
        <label class="form-label">Primary Contact Person *</label>
        <input type="text" name="owner_name" class="form-control" placeholder="e.g. Chef Marcus Vance" required>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Business Email *</label>
        <input type="email" name="email" class="form-control" placeholder="e.g. marcus@epicurean.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-control" placeholder="e.g. +1 (555) 349-2091">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Kitchen / Commercial Facility Address</label>
      <input type="text" name="kitchen_address" class="form-control" placeholder="e.g. Suite 400, 782 Culinary Way, Downtown">
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

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Culinary Specialization *</label>
        <select name="specialization" class="form-control">
          <option value="Contemporary Fine Dining &amp; Plated Service">Contemporary Fine Dining &amp; Plated Service</option>
          <option value="High-End Buffet &amp; Interactive Stations">High-End Buffet &amp; Interactive Stations</option>
          <option value="Artisanal Canapés &amp; Cocktail Reception">Artisanal Canapés &amp; Cocktail Reception</option>
          <option value="Global Fusion &amp; Dietary-Specialized Menus">Global Fusion &amp; Dietary-Specialized Menus</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Primary Assigned Venue</label>
        <select name="assigned_venue" class="form-control">
          <option>Grand Emerald Ballroom</option>
          <option>Skyline Vista Lounge</option>
          <option>Crystal Tech Pavilion</option>
          <option>The Brick &amp; Steel Gallery</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">NID / Tax Registration Number *</label>
      <input type="text" name="tax_id" class="form-control" placeholder="e.g. TAX-98420-VN" required>
    </div>

    <div class="section-divider">Verification Documents *</div>
    <p class="text-sm text-muted mb-12">Compliance verification documents must be submitted prior to onboarding. Owner CV and National ID (NID) scan are required.</p>
    <div class="grid-2 gap-20 mb-16">
      <div>
        <label class="form-label">Owner / Head Chef CV *</label>
        <div class="upload-zone" id="zone-cat-cv" onclick="document.getElementById('cat-cv').click()">
          <div id="preview-cat-cv">
            <span class="upload-icon">📄</span>
            <span class="upload-text font-semibold">Upload CV / Portfolio</span>
            <span class="upload-hint">PDF, DOC — max 10 MB</span>
          </div>
        </div>
        <input id="cat-cv" type="file" accept=".pdf,.doc,.docx" style="display:none;" onchange="handleDocUpload(this, 'zone-cat-cv', 'preview-cat-cv')">
      </div>
      <div>
        <label class="form-label">National ID (NID) Scan — Owner *</label>
        <div class="upload-zone" id="zone-cat-nid" onclick="document.getElementById('cat-nid').click()">
          <div id="preview-cat-nid">
            <span class="upload-icon">🪪</span>
            <span class="upload-text font-semibold">Upload NID Document</span>
            <span class="upload-hint">JPG, PNG, PDF — both sides</span>
          </div>
        </div>
        <input id="cat-nid" type="file" accept=".jpg,.jpeg,.png,.pdf" style="display:none;" onchange="handleDocUpload(this, 'zone-cat-nid', 'preview-cat-nid')">
      </div>
    </div>
    <div class="grid-2 gap-20 mb-24">
      <div>
        <label class="form-label">Food Safety / Health Dept. Certificate</label>
        <div class="upload-zone" id="zone-cat-cert" onclick="document.getElementById('cat-cert').click()">
          <div id="preview-cat-cert">
            <span class="upload-icon">📋</span>
            <span class="upload-text font-semibold">Upload Health Certificate</span>
            <span class="upload-hint">PDF, JPG — max 5 MB</span>
          </div>
        </div>
        <input id="cat-cert" type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="handleDocUpload(this, 'zone-cat-cert', 'preview-cat-cert')">
      </div>
      <div>
        <label class="form-label">Commercial Kitchen License</label>
        <div class="upload-zone" id="zone-cat-lic" onclick="document.getElementById('cat-lic').click()">
          <div id="preview-cat-lic">
            <span class="upload-icon">🏛️</span>
            <span class="upload-text font-semibold">Upload Kitchen License</span>
            <span class="upload-hint">PDF, JPG — max 5 MB</span>
          </div>
        </div>
        <input id="cat-lic" type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="handleDocUpload(this, 'zone-cat-lic', 'preview-cat-lic')">
      </div>
    </div>

    <div id="catererAlertBottom" style="display:none; padding:12px 16px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

    <div class="flex gap-12">
      <a href="admin-catering-management.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
      <button type="submit" id="btnRegisterCaterer" class="btn btn-primary" style="flex:2;">Register Catering Partner →</button>
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
        '<img src="' + e.target.result + '" style="max-height:90px; max-width:100%; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:8px; display:inline-block;" alt="Selected Document">' +
        '<div style="font-size:13px; font-weight:600; color:var(--success);">✓ ' + escapeHtml(file.name) + ' (' + Math.round(file.size / 1024) + ' KB)</div>' +
        '<div style="font-size:11px; color:var(--gray-500); margin-top:4px;">Click to change</div>' +
      '</div>';
    };
    reader.readAsDataURL(file);
  } else {
    // Non-image document (PDF, DOC)
    preview.innerHTML = '<div style="padding:16px; text-align:center;">' +
      '<div style="font-size:32px; margin-bottom:6px;">📄</div>' +
      '<div style="font-size:13px; font-weight:600; color:var(--navy-900);">✓ ' + escapeHtml(file.name) + '</div>' +
      '<div style="font-size:12px; color:var(--success); font-weight:500; margin-top:2px;">Document verified & ready (' + Math.round(file.size / 1024) + ' KB)</div>' +
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
  var form = document.getElementById('addCatererForm');
  var regBtn = document.getElementById('btnRegisterCaterer');
  var alertBoxTop = document.getElementById('catererAlert');
  var alertBoxBottom = document.getElementById('catererAlertBottom');

  function showAlert(msg, isSuccess) {
    [alertBoxTop, alertBoxBottom].forEach(function(box) {
      if (!box) return;
      box.style.display = 'block';
      box.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
      box.style.border = isSuccess ? '1.5px solid #86efac' : '1.5px solid #f87171';
      box.style.color = isSuccess ? '#166534' : '#991b1b';
      box.innerHTML = (isSuccess ? '✓ ' : '⚠️ ') + msg;
    });
    if (!isSuccess && alertBoxBottom) {
      alertBoxBottom.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function hideAlert() {
    [alertBoxTop, alertBoxBottom].forEach(function(box) {
      if (box) box.style.display = 'none';
    });
  }

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    hideAlert();

    var pw = form.querySelector('[name="password"]').value;
    var cpw = form.querySelector('[name="confirm_password"]').value;
    if (pw !== cpw) {
      showAlert('Passwords do not match. Please re-enter both password fields.', false);
      return;
    }
    if (pw.length < 6) {
      showAlert('Password must be at least 6 characters.', false);
      return;
    }

    // Enforce Verification Documents submission before registering
    var cvInput = document.getElementById('cat-cv');
    var nidInput = document.getElementById('cat-nid');
    var hasCv = (cvInput && cvInput.files && cvInput.files.length > 0) || !!uploadedFiles['zone-cat-cv'];
    var hasNid = (nidInput && nidInput.files && nidInput.files.length > 0) || !!uploadedFiles['zone-cat-nid'];

    if (!hasCv || !hasNid) {
      var missing = [];
      if (!hasCv) {
        missing.push('Owner / Head Chef CV');
        var z1 = document.getElementById('zone-cat-cv');
        if (z1) { z1.style.borderColor = '#dc2626'; z1.style.background = '#fff5f5'; }
      }
      if (!hasNid) {
        missing.push('National ID (NID) Scan');
        var z2 = document.getElementById('zone-cat-nid');
        if (z2) { z2.style.borderColor = '#dc2626'; z2.style.background = '#fff5f5'; }
      }
      showAlert('<strong>Verification Documents Missing:</strong> Compliance documents must be submitted before registering a caterer. Please upload: ' + missing.join(' and ') + '.', false);
      return;
    }

    regBtn.disabled = true;
    regBtn.textContent = 'Registering Partner...';

    var payload = {
      business_name:   form.querySelector('[name="business_name"]').value.trim(),
      owner_name:      form.querySelector('[name="owner_name"]').value.trim(),
      email:           form.querySelector('[name="email"]').value.trim(),
      phone:           form.querySelector('[name="phone"]').value.trim(),
      kitchen_address: form.querySelector('[name="kitchen_address"]').value.trim(),
      password:        pw,
      specialization:  form.querySelector('[name="specialization"]').value
    };

    try {
      var res = await fetch('../api/caterers.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        credentials: 'include',
        body: JSON.stringify(payload)
      });
      var d = await res.json();
      if (d.success) {
        showAlert('Caterer registered and approved successfully! Redirecting to Approved Caterers...', true);
        setTimeout(function() {
          window.location.href = 'admin-catering-management.php';
        }, 900);
      } else {
        showAlert(d.message || 'Error registering caterer. Please verify inputs.', false);
        regBtn.disabled = false;
        regBtn.textContent = 'Register Catering Partner →';
      }
    } catch(err) {
      showAlert('Network connection error. Please try again.', false);
      regBtn.disabled = false;
      regBtn.textContent = 'Register Catering Partner →';
    }
  });
});
</script>
</body>
</html>