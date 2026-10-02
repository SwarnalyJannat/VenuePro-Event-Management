<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Create Package Step 3</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>.sidebar-logo-sub { color: #10b981; }</style>
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
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Package Library
        </a>
        <a href="caterer-menu-items.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg> Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item logout-link" style="color:var(--gray-400);">
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>
      <main class="page-body">

<div class="steps">
  <div class="step done">
    <div class="step-circle">✓</div>
    <div class="step-label">Basic Information</div>
  </div>
  <div class="step-line done"></div>
  <div class="step done">
    <div class="step-circle">✓</div>
    <div class="step-label">Menu Courses</div>
  </div>
  <div class="step-line done"></div>
  <div class="step active">
    <div class="step-circle">3</div>
    <div class="step-label">Pricing &amp; Publish</div>
  </div>
</div>

<div class="card" style="max-width:800px; margin:0 auto;">
  <h2 class="mb-4">Step 3: Pricing &amp; Platform Publishing</h2>
  <p class="mb-24">Set financial parameters and publish directly to the customer package catalog.</p>

  <div id="publishAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

  <form id="step3Form">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Base Rate per Event (USD) *</label>
        <input type="number" name="price_per_event" class="form-control" placeholder="e.g. 1950" required>
      </div>
      <div class="form-group">
        <label class="form-label">Per Guest Rate (USD) *</label>
        <input type="number" name="price_per_guest" class="form-control" placeholder="e.g. 45" step="0.01" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Minimum Guests</label>
        <input type="number" name="min_guests" class="form-control" placeholder="e.g. 30">
      </div>
      <div class="form-group">
        <!-- commented out by user, do not uncomment -->
      </div>
    </div>

    <div class="flex gap-12 mt-24">
      <a href="caterer-create-package-2.php" class="btn btn-ghost" style="flex:1;">← Back to Step 2</a>
      <button type="submit" class="btn btn-primary" style="flex:2; background:#059669; border-color:#059669;">Publish Package to Catalog 🚀</button>
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
  var alertBox = document.getElementById('publishAlert');

  // Guard: must have step1 data
  var step1 = JSON.parse(sessionStorage.getItem('pkg_step1') || 'null');
  if (!step1 || !step1.title) {
    window.location.href = 'caterer-create-package-1.php';
    return;
  }

  var form = document.getElementById('step3Form');
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    alertBox.style.display = 'none';
    var btn = form.querySelector('[type="submit"]');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Publishing...'; }

    // Gather step2 courses as features list
    var courses = JSON.parse(sessionStorage.getItem('pkg_step2_courses') || '[]');
    var featuresList = [];
    courses.forEach(function(c) {
      c.items.forEach(function(item) { if (item) featuresList.push(item); });
    });

    var payload = {
      title:           step1.title,
      tier:            step1.tier || 'Gold',
      max_capacity:    step1.max_capacity || 100,
      cuisine_type:    step1.cuisine_type || 'Fine Dining',
      description:     step1.description,
      price_per_event: parseFloat((form.querySelector('[name="price_per_event"]') || {}).value) || 1200,
      price_per_guest: parseFloat((form.querySelector('[name="price_per_guest"]') || {}).value) || 24,
      min_guests:      parseInt((form.querySelector('[name="min_guests"]') || {}).value) || 30,
      features:        featuresList,
      status:          'published'
    };

    try {
      var res = await fetch('../api/packages.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      var d = await res.json();
      if (d.success) {
        // Clear wizard data
        sessionStorage.removeItem('pkg_step1');
        sessionStorage.removeItem('pkg_step2_courses');

        alertBox.style.display = 'block';
        alertBox.style.background = '#dcfce7';
        alertBox.style.color = '#166534';
        alertBox.textContent = '✓ Package published successfully! Redirecting to package library...';
        setTimeout(function() {
          window.location.href = 'caterer-food-packages.php';
        }, 1000);
      } else {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ ' + (d.message || 'Failed to publish package');
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