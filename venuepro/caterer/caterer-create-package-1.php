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
  <title>VenuePro Caterer – Create Package Step 1</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .sidebar-logo-sub { color:#10b981; }
    .upload-zone { border:2px dashed var(--gray-300); border-radius:var(--radius); padding:24px; text-align:center; background:var(--gray-50); transition:all .2s; cursor:pointer; }
    .upload-zone:hover { border-color:var(--primary); background:#eff6ff; }
    .upload-zone input[type=file] { display:none; }
    .upload-zone label { display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; }
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
  <div class="step active"><div class="step-circle">1</div><div class="step-label">Basic Info &amp; Photos</div></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-circle">2</div><div class="step-label">Menu Courses</div></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-circle">3</div><div class="step-label">Pricing &amp; Publish</div></div>
</div>

<div style="max-width:860px; margin:0 auto;">
  <div class="card">
    <h2 class="mb-4">Step 1: Package Info &amp; Photographs</h2>
    <p class="mb-24">Define the package name, service tier, and upload professional food photography.</p>

    <form action="caterer-create-package-2.php" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Package Name *</label>
        <input type="text" class="form-control" value="Grand Sovereign Banquet" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Catering Tier *</label>
          <select class="form-control">
            <option>Platinum Gala Tier</option>
            <option>Gold Luncheon Tier</option>
            <option>Custom Signature Tasting</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Max Capacity (Guests) *</label>
          <input type="number" class="form-control" value="300" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Package Description *</label>
        <textarea class="form-control" rows="3">A lavish four-course culinary experience featuring sustainable local seafood, prime cut dry-aged tenderloin, and molecular dessert pairings.</textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Service Style</label>
        <div class="grid-3 gap-8">
          <label class="card flex-center gap-8" style="padding:10px; cursor:pointer;">
            <input type="radio" name="style" checked>
            <span class="text-sm font-semibold">Synchronized Plated</span>
          </label>
          <label class="card flex-center gap-8" style="padding:10px; cursor:pointer;">
            <input type="radio" name="style">
            <span class="text-sm font-semibold">Interactive Station</span>
          </label>
          <label class="card flex-center gap-8" style="padding:10px; cursor:pointer;">
            <input type="radio" name="style">
            <span class="text-sm font-semibold">Family Style Feast</span>
          </label>
        </div>
      </div>

      <!-- Photo Upload -->
      <div class="form-group">
        <label class="form-label">Package Hero Image (Main Photo) *</label>
        <div class="upload-zone">
          <label for="pkg-hero">
            <span style="font-size:32px;">🍽️</span>
            <span class="font-semibold text-sm">Click to upload hero food photograph</span>
            <span class="text-xs text-muted">JPG, PNG, WebP — min 1200×800 px, max 5 MB</span>
          </label>
          <input id="pkg-hero" type="file" accept="image/*">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Additional Gallery Photos (up to 6)</label>
        <div class="upload-zone" style="padding:16px;">
          <label for="pkg-gallery" style="flex-direction:row; gap:12px;">
            <span style="font-size:24px;">📸</span>
            <div style="text-align:left;">
              <div class="font-semibold text-sm">Upload additional food &amp; setup photos</div>
              <div class="text-xs text-muted">Hold Ctrl/Cmd to select multiple — JPG, PNG, max 3 MB each</div>
            </div>
          </label>
          <input id="pkg-gallery" type="file" accept="image/*" multiple>
        </div>
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
</body>
</html>