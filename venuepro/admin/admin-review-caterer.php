<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();

$catererId = (int)($_GET['id'] ?? 0);
$caterer = null;
if ($catererId > 0) {
    $stmtC = $db->prepare("SELECT u.*, cp.business_name, cp.owner_name, cp.kitchen_address, cp.specialization, cp.approval_status FROM users u JOIN caterer_profiles cp ON u.id = cp.user_id WHERE u.id = ? AND u.role = 'caterer' LIMIT 1");
    $stmtC->execute([$catererId]);
    $caterer = $stmtC->fetch();
}
if (!$caterer) {
    $stmtC = $db->query("SELECT u.*, cp.business_name, cp.owner_name, cp.kitchen_address, cp.specialization, cp.approval_status FROM users u JOIN caterer_profiles cp ON u.id = cp.user_id WHERE u.role = 'caterer' ORDER BY (cp.approval_status = 'under_review' OR cp.approval_status = 'pending') DESC, cp.id DESC LIMIT 1");
    $caterer = $stmtC ? $stmtC->fetch() : null;
    $catererId = $caterer ? (int)$caterer['id'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Review Caterer</title>
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
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'admin')) ?></div>
            </div>
          </div>
        </div>
      </header>
      <main class="page-body">
<div class="breadcrumb">
  <a href="admin-catering-management.php">Catering Oversight</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current">Review Application</span>
</div>

<div class="flex-between mb-24">
  <div>
    <h1>Review Catering Application</h1>
    <p>Applicant: <strong><?= e($caterer['business_name'] ?? 'Epicurean Events Co.') ?></strong> (<?= e($caterer['owner_name'] ?? ($caterer['name'] ?? 'Chef')) ?>)</p>
  </div>
  <span class="pill pill-<?= ($caterer['approval_status'] ?? '') === 'approved' ? 'confirmed' : 'pending' ?>"><?= strtoupper($caterer['approval_status'] ?? 'PENDING') ?></span>
</div>

<div class="grid-2" style="grid-template-columns: 2fr 1.2fr; gap:24px;">
  <div class="card">
    <h3 class="mb-16">Culinary Credentials & Commercial License</h3>
    <div class="mb-16">
      <div class="text-xs text-muted">Kitchen Facility</div>
      <div class="font-semibold text-sm"><?= e($caterer['kitchen_address'] ?? 'Suite 400, 782 Culinary Way, Downtown') ?></div>
    </div>
    <div class="mb-16">
      <div class="text-xs text-muted">Specialization</div>
      <div class="font-semibold text-sm"><?= e($caterer['specialization'] ?? 'Contemporary Fine Dining') ?></div>
    </div>
    <div class="mb-16">
      <div class="text-xs text-muted">Contact Information</div>
      <div class="font-semibold text-sm"><?= e($caterer['email'] ?? '') ?> • <?= e($caterer['phone'] ?? '') ?></div>
    </div>
    <div class="card" style="background:var(--gray-50);">
      <div class="font-bold text-sm mb-8">Verified Documentation</div>
      <div class="text-sm">✓ Health Department Grade A Certificate</div>
      <div class="text-sm">✓ Commercial Kitchen General Liability Insurance ($2M)</div>
      <div class="text-sm">✓ Executive Chef ServSafe Certification</div>
    </div>
  </div>

  <div class="card" style="background:var(--gray-50);">
    <h3 class="mb-16">Application Decision</h3>
    <div id="decision-alert" style="display:none; padding:12px; border-radius:6px; margin-bottom:12px; font-size:0.9rem; font-weight:600;"></div>
    <button type="button" id="btn-approve-caterer" class="btn btn-primary btn-full mb-12" style="background:#16a34a; border-color:#16a34a;" onclick="updateCatererStatus('approved')">✓ Grant Certified Caterer Badge</button>
    <button type="button" id="btn-reject-caterer" class="btn btn-danger btn-outline btn-full mb-12" onclick="updateCatererStatus('rejected')">✗ Reject Application</button>
    <!-- <button id="btn-request-inspection" class="btn btn-ghost btn-full" onclick="toggleInspectionBox()">Request Additional Inspection</button> -->
    
    <div id="inspection-form-container" style="display:none; margin-top:16px; padding-top:16px; border-top:1px solid var(--gray-200);">
      <label class="form-label" style="font-weight:600; margin-bottom:8px; display:block;">Specify Inspection Requirements / Notes:</label>
      <textarea id="inspection-notes" class="form-control" rows="4" placeholder="Enter specific health code, equipment compliance, or safety documentation requested..." style="width:100%; margin-bottom:12px; font-family:inherit; resize:vertical;"></textarea>
      <div style="display:flex; gap:8px;">
        <button type="button" class="btn btn-primary btn-sm" style="flex:1;" onclick="submitInspectionRequest()">Send Request</button>
        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleInspectionBox()">Cancel</button>
      </div>
      <div id="inspection-sent-alert" style="display:none; margin-top:12px; padding:10px 14px; background:#dcfce7; color:#166534; border:1px solid #bbf7d0; border-radius:6px; font-size:13px; font-weight:600;">
        ✓ Additional inspection request sent to <?= e($caterer['business_name'] ?? 'caterer') ?>.
      </div>
    </div>
  </div>
</div>
</main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
  <script>
    async function updateCatererStatus(newStatus) {
      const alertBox = document.getElementById('decision-alert');
      const btnApprove = document.getElementById('btn-approve-caterer');
      const btnReject = document.getElementById('btn-reject-caterer');
      if (btnApprove) btnApprove.disabled = true;
      if (btnReject) btnReject.disabled = true;

      try {
        const res = await fetch('../api/caterers.php?action=update_status', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            caterer_id: <?= $catererId ?>,
            status: newStatus
          })
        });
        const d = await res.json();
        if (d.success) {
          alertBox.style.display = 'block';
          if (newStatus === 'approved') {
            alertBox.style.background = '#dcfce7';
            alertBox.style.color = '#166534';
            alertBox.textContent = '✓ Caterer certified and approved! Redirecting...';
          } else {
            alertBox.style.background = '#fee2e2';
            alertBox.style.color = '#991b1b';
            alertBox.textContent = '✗ Caterer application rejected. Redirecting...';
          }
          setTimeout(() => {
            window.location.href = 'admin-catering-management.php';
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (d.message || 'Error updating status');
          if (btnApprove) btnApprove.disabled = false;
          if (btnReject) btnReject.disabled = false;
        }
      } catch(err) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ Connection error. Please try again.';
        if (btnApprove) btnApprove.disabled = false;
        if (btnReject) btnReject.disabled = false;
      }
    }

    function toggleInspectionBox() {
      const box = document.getElementById('inspection-form-container');
      const isHidden = box.style.display === 'none';
      box.style.display = isHidden ? 'block' : 'none';
      if (isHidden) {
        document.getElementById('inspection-notes').focus();
      }
    }

    function submitInspectionRequest() {
      const notes = document.getElementById('inspection-notes');
      const text = notes.value.trim();
      if (!text) {
        alert('Please enter your inspection requirements before sending.');
        notes.focus();
        return;
      }
      document.getElementById('inspection-sent-alert').style.display = 'block';
      notes.disabled = true;
      const statusPill = document.querySelector('.pill-pending');
      if (statusPill) {
        statusPill.textContent = 'ADDITIONAL INFO REQUESTED';
        statusPill.style.background = '#fef3c7';
        statusPill.style.color = '#b45309';
      }
      setTimeout(() => {
        const btn = document.getElementById('btn-request-inspection');
        btn.textContent = 'Inspection Requested ✓';
        btn.disabled = true;
        btn.style.opacity = '0.7';
      }, 600);
    }
  </script>
<script src="../js/app.js"></script>
</body>
</html>