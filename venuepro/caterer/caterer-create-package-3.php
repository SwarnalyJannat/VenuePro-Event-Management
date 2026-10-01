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
  <title>VenuePro – Create Package - Step 3</title>
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
        <a href="caterer-create-package-1.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
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
    <div class="step-label">Pricing & Publish</div>
  </div>
</div>

<div class="card" style="max-width:800px; margin:0 auto;">
  <h2 class="mb-4">Step 3: Pricing & Platform Publishing</h2>
  <p class="mb-24">Set financial parameters and publish directly to the customer package catalog.</p>

  <form action="succeed-package.php">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Base Rate per Event (USD)</label>
        <input type="number" class="form-control" value="1950" required>
      </div>
      <div class="form-group">
        <label class="form-label">Per Person Surcharge (Over 100 pax)</label>
        <input type="number" class="form-control" value="45" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Kitchen Preparation Lead Time</label>
        <select class="form-control">
          <option>48 Hours Notice</option>
          <option>72 Hours Notice</option>
          <option>5 Business Days</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Catalog Status</label>
        <select class="form-control">
          <option>Active / Immediately Bookable</option>
          <option>Draft / Hidden from Catalog</option>
        </select>
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
</body>
</html>