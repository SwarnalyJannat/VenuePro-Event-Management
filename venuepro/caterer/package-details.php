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
  <title>VenuePro – Package Details</title>
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
        <a href="caterer-food-packages.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Package Library
        </a>
                <a href="caterer-menu-items.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
          Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
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
<div class="breadcrumb">
  <a href="caterer-food-packages.php">Packages</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current">Platinum Evening Banquet</span>
</div>

<div class="grid-2" style="grid-template-columns: 2fr 1fr; gap:28px;">
  <div>
    <div class="flex-between mb-16">
      <div>
        <span class="pill pill-confirmed mb-4">ACTIVE CATALOG</span>
        <h1>Platinum Evening Banquet</h1>
        <p>3-course executive dinner with 4h premium bar & cocktail reception.</p>
      </div>
      <a href="caterer-create-package-1.php" class="btn btn-outline btn-sm">Edit Package</a>
    </div>

    <div class="card mb-24">
      <h3 class="mb-12">Included Courses</h3>
      <div class="mb-16">
        <div class="font-bold text-sm text-primary mb-4">Course 1: Passed Hors d'oeuvres</div>
        <p class="text-sm">Mini Lobster Rolls with Tarragon Aioli, Wild Mushroom Crostini, Duck Confit Tartlet.</p>
      </div>
      <div class="mb-16">
        <div class="font-bold text-sm text-primary mb-4">Course 2: Plated Entree</div>
        <p class="text-sm">Filet Mignon or Pan-Seared Halibut served with Truffle Mashed Potatoes & Grilled Broccolini.</p>
      </div>
      <div>
        <div class="font-bold text-sm text-primary mb-4">Course 3: Dessert Table</div>
        <p class="text-sm">Dark Chocolate Fondant with Salted Caramel Gelato and Fresh Berry Compote.</p>
      </div>
    </div>
  </div>

  <div class="card" style="background:var(--gray-50);">
    <h3 class="mb-16">Performance Metrics</h3>
    <div class="flex-between text-sm mb-8"><span class="text-muted">Total Bookings</span><span class="font-bold">142 events</span></div>
    <div class="flex-between text-sm mb-8"><span class="text-muted">Monthly Revenue</span><span class="font-bold text-success">$52,400.00</span></div>
    <a href="caterer-dashboard.php" class="btn btn-primary btn-full" style="background:#059669; border-color:#059669;">Back to Kitchen Queue</a>
  </div>
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