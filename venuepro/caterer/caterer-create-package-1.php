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
  <title>VenuePro – Create Package Step 1</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .sidebar-logo-sub { color:#10b981; }
    .img-thumb { width:80px; height:60px; object-fit:cover; border-radius:6px; }
    .img-thumb.add { border:2px dashed var(--primary); color:var(--primary); font-size:1.6rem; }
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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
          Singular Menu Items
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
  <div class="step active"><div class="step-circle">1</div><div class="step-label">Basic Info</div></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-circle">2</div><div class="step-label">Menu Courses</div></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-circle">3</div><div class="step-label">Pricing &amp; Publish</div></div>
</div>

<div style="max-width:860px; margin:0 auto;">
  <div class="card">
    <h2 class="mb-4">Step 1: Package Basic Information</h2>
    <p class="mb-24">Define the package name, service tier, cuisine type, and a brief description.</p>

    <form id="step1Form">
      <div class="form-group">
        <label class="form-label">Package Name *</label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Grand Sovereign Banquet" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Catering Tier *</label>
          <select name="tier" class="form-control">
            <option value="Platinum">Platinum</option>
            <option value="Gold">Gold</option>
            <option value="Signature">Signature</option>
            <option value="Custom">Custom</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Max Capacity (Guests) *</label>
          <input type="number" name="max_capacity" class="form-control" placeholder="e.g. 300" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Cuisine Type</label>
        <input type="text" name="cuisine_type" class="form-control" placeholder="e.g. Fine Dining, Buffet, Mediterranean">
      </div>

      <div class="form-group">
        <label class="form-label">Package Description *</label>
        <textarea name="description" class="form-control" rows="3" placeholder="e.g. Describe the culinary experience, course features, and presentation style..."></textarea>
      </div>

      <div class="flex gap-12 mt-24">
        <a href="caterer-food-packages.php" class="btn btn-ghost" style="flex:1;">Cancel</a>
        <button type="submit" class="btn btn-primary" style="flex:2; background:#059669; border-color:#059669;">Continue to Step 2: Menu Courses →</button>
      </div>
    </form>
  </div>
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
  // Clear old wizard data when starting fresh
  if (!sessionStorage.getItem('pkg_step1')) {
    sessionStorage.removeItem('pkg_step1');
    sessionStorage.removeItem('pkg_step2');
  }

  var form = document.getElementById('step1Form');
  if (!form) return;

  // Restore previously entered values if navigating back
  var saved = JSON.parse(sessionStorage.getItem('pkg_step1') || '{}');
  if (saved.title)        form.querySelector('[name="title"]').value = saved.title;
  if (saved.tier)         form.querySelector('[name="tier"]').value = saved.tier;
  if (saved.max_capacity) form.querySelector('[name="max_capacity"]').value = saved.max_capacity;
  if (saved.cuisine_type) form.querySelector('[name="cuisine_type"]').value = saved.cuisine_type;
  if (saved.description)  form.querySelector('[name="description"]').value = saved.description;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var data = {
      title:        form.querySelector('[name="title"]').value.trim(),
      tier:         form.querySelector('[name="tier"]').value,
      max_capacity: parseInt(form.querySelector('[name="max_capacity"]').value) || 100,
      cuisine_type: form.querySelector('[name="cuisine_type"]').value.trim(),
      description:  form.querySelector('[name="description"]').value.trim()
    };
    if (!data.title || !data.description) {
      alert('Package Name and Description are required.');
      return;
    }
    sessionStorage.setItem('pkg_step1', JSON.stringify(data));
    window.location.href = 'caterer-create-package-2.php';
  });
});
</script>
</body>
</html>