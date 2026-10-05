<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Venue Selection</title>
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
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Live Progress
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
          <input type="text" placeholder="Search event venues, bookings, menus...">
        </div>
        <div class="topbar-actions">
          <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">3</span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <a href="customer-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </a>
        </div>
      </header>
      <main class="page-body">
<div class="flex-between mb-24">
  <div>
    <h1>Select a Venue</h1>
    <p>Choose an enterprise space to proceed with booking parameters.</p>
  </div>
</div>

<div class="grid-3 gap-20">
  <div class="venue-card" style="border:2px solid var(--primary);">
    <div class="venue-card-img" style="background:url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=600&q=80') center/cover;"></div>
    <div class="venue-card-body">
      <div class="venue-card-name">Grand Emerald Ballroom</div>
      <div class="venue-card-location">Downtown Business District</div>
      <div class="venue-card-meta">
        <span>👥 500 Guests</span>
        <span class="venue-price">$1,200/day</span>
      </div>
      <a href="booking-date-guests.php" class="btn btn-primary btn-full mt-12">Select This Venue ✓</a>
    </div>
  </div>

  <div class="venue-card">
    <div class="venue-card-img" style="background:url('https://images.unsplash.com/photo-1533105079780-92b9be482077?w=600&q=80') center/cover;"></div>
    <div class="venue-card-body">
      <div class="venue-card-name">Skyline Vista Lounge</div>
      <div class="venue-card-location">North Waterfront District</div>
      <div class="venue-card-meta">
        <span>👥 150 Guests</span>
        <span class="venue-price">$2,450/day</span>
      </div>
      <a href="booking-date-guests.php" class="btn btn-outline btn-full mt-12">Select This Venue</a>
    </div>
  </div>

  <div class="venue-card">
    <div class="venue-card-img" style="background:url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80') center/cover;"></div>
    <div class="venue-card-body">
      <div class="venue-card-name">The Brick & Steel Gallery</div>
      <div class="venue-card-location">Arts District</div>
      <div class="venue-card-meta">
        <span>👥 300 Guests</span>
        <span class="venue-price">$1,800/day</span>
      </div>
      <a href="booking-date-guests.php" class="btn btn-outline btn-full mt-12">Select This Venue</a>
    </div>
  </div>
</div>
</main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
        <div class="footer-links">
          <a href="../privacy-policy.php">Privacy Policy</a>
          <a href="../terms-of-service.php">Terms of Service</a>
          <a href="../contact-support.php">Contact Support</a>
        </div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>