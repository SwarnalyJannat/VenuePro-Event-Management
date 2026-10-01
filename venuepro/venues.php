<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Browse Venues (Guest Mode)</title>
  <link rel="stylesheet" href="css/style.css">
  <style>

    .modal-backdrop {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7);
      display: none; align-items: center; justify-content: center; z-index: 1000;
      backdrop-filter: blur(5px);
    }
    .modal-backdrop:target { display: flex; }
    .modal-card {
      background: #fff; border-radius: var(--radius); padding: 32px;
      max-width: 460px; width: 90%; box-shadow: var(--shadow-xl);
      text-align: center; position: relative; animation: modalPop 0.2s ease-out;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .modal-icon {
      width: 58px; height: 58px; border-radius: 50%;
      background: #eff6ff; color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin: 0 auto 16px;
    }
    .modal-close {
      position: absolute; top: 16px; right: 16px; text-decoration: none;
      color: var(--gray-400); font-size: 1.2rem; font-weight: 700;
      width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
      border-radius: 50%; background: var(--gray-100);
    }
    .modal-close:hover { background: var(--gray-200); color: var(--gray-800); }
    
    .guest-banner-bar {
      background: #eff6ff; border-bottom: 1px solid #bfdbfe;
      padding: 10px 48px; display: flex; align-items: center; justify-content: space-between;
      font-size: 0.85rem; color: var(--primary);
    }

    .public-page { max-width: 1240px; margin: 0 auto; padding: 32px 24px 60px; }
  </style>
</head>
<body style="background:#f8fafc;">

  <header class="landing-header" style="display:flex; align-items:center; justify-content:space-between; padding:18px 48px; background:#fff; border-bottom:1px solid var(--gray-200); position:sticky; top:0; z-index:100;">
    <div class="flex-center gap-12">
      <a href="index.php" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
        <img src="assets/logo.png" alt="VenuePro" class="header-logo-img">
        <div>
          <div style="font-size:1.2rem; font-weight:800; color:var(--gray-900);">VenuePro</div>
          <div style="font-size:0.7rem; color:var(--gray-500); font-weight:500;">Enterprise Event Platform</div>
        </div>
      </a>
    </div>
    <nav class="flex-center gap-20">
      <a href="venues.php" style="color:var(--gray-700); font-weight:600; text-decoration:none; font-size:0.9rem;">Venues</a>
      <a href="packages.php" style="color:var(--gray-700); font-weight:600; text-decoration:none; font-size:0.9rem;">Catering Packages</a>
      <a href="login-role.php" class="btn btn-outline btn-sm" style="font-weight:600;">Sign In</a>
      <a href="signup-role.php" class="btn btn-primary btn-sm" style="font-weight:600;">Sign Up</a>
    </nav>
  </header>

  <div class="guest-banner-bar" style="justify-content:center;">
    <div class="flex-center gap-8">
      <span>👋</span>
      <span><strong>Guest Browsing Mode:</strong> You are viewing our venue catalog. Sign in to place reservations.</span>
    </div>
  </div>

  <main class="public-page">
    <div class="flex-between mb-24">
      <div>
        <h1 style="font-size:2.2rem; font-weight:800; margin-bottom:6px;">Enterprise Venues Catalog</h1>
        <p>Explore luxury ballrooms, skyline terraces, and tech pavilions. Click any venue to view high-res photo galleries.</p>
      </div>
      <div class="flex-center gap-12">
        <span class="text-sm text-muted">Sort by:</span>
        <select class="form-control" style="width:160px;">
          <option>Most Popular</option>
          <option>Capacity: High to Low</option>
          <option>Price: Low to High</option>
        </select>
      </div>
    </div>

    <div class="grid-3 gap-24">
      <!-- Venue 1 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=800&q=80'); position:relative;">
          <span class="venue-badge">PREMIUM BALLROOM</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">Grand Emerald Ballroom</h3>
          </div>
          <p class="venue-card-loc">📍 Downtown District, Level 4 · 600 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">Unobstructed 8,500 sq ft hall with crystal chandeliers, acoustic Bose audio rig, and private VIP terrace.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$1,200<small>/day base</small></div>
              <span class="text-xs text-success">✓ In-house Catering</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (14+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Venue 2 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=800&q=80');">
          <span class="venue-badge">PANORAMIC ROOFTOP</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">Skyline Vista Lounge</h3>
          </div>
          <p class="venue-card-loc">📍 North Waterfront District · 250 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">360-degree glass perimeter with sunset views, wrap-around cocktail deck, and ambient architectural lighting.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$2,450<small>/day base</small></div>
              <span class="text-xs text-success">✓ Open Bar Setup</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (10+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Venue 3 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800&q=80');">
          <span class="venue-badge">TECH PAVILION</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">Crystal Tech Pavilion</h3>
          </div>
          <p class="venue-card-loc">📍 Innovation Park District · 400 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">High-tech auditorium equipped with ultra-wide 8K LED video wall, fiber connectivity, and live stream rigs.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$3,200<small>/day base</small></div>
              <span class="text-xs text-success">✓ Full A/V Team</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (8+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Venue 4 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1520854221256-17451cc331bf?w=800&q=80');">
          <span class="venue-badge">HISTORIC LOFT</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">The Brick &amp; Steel Gallery</h3>
          </div>
          <p class="venue-card-loc">📍 Arts &amp; Cultural Quarter · 300 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">Industrial chic aesthetic with exposed century-old red brick, polished concrete floors, and museum track lighting.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$1,800<small>/day base</small></div>
              <span class="text-xs text-success">✓ Flexible Layout</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (12+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Venue 5 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800&q=80');">
          <span class="venue-badge">BOTANICAL GARDEN</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">Grand Emerald Garden</h3>
          </div>
          <p class="venue-card-loc">📍 East Garden District · 450 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">Lush manicured lawn, climate-controlled glass marquee, and evening fountain illuminations for galas.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$2,100<small>/day base</small></div>
              <span class="text-xs text-success">✓ Marquee Canopy</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (15+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Venue 6 -->
      <div class="venue-card">
        <div class="venue-card-img" style="background-image:url('https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&q=80');">
          <span class="venue-badge">HARBOR SUITE</span>
        </div>
        <div class="venue-card-body">
          <div class="flex-between mb-8">
            <h3 class="venue-card-title">Riverside Suite</h3>
          </div>
          <p class="venue-card-loc">📍 South Marina District · 200 Guests Capacity</p>
          <p class="text-xs text-muted mb-16">Private waterfront dock, natural daylight floor-to-ceiling windows, and integrated presentation boardroom.</p>
          <div class="flex-between">
            <div>
              <div class="venue-card-price">$1,650<small>/day base</small></div>
              <span class="text-xs text-success">✓ Marina Access</span>
            </div>
            <div class="flex gap-8">
              <a href="venue-details.php" class="btn btn-outline btn-sm">View Photos (9+)</a>
              <a href="#signin-modal" class="btn btn-primary btn-sm">🔒 Book Now</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Sign In Required Modal (for Guest Mode) -->
  <div id="signin-modal" class="modal-backdrop">
    <div class="modal-card">
      <a href="#" class="modal-close">✕</a>
      <div class="modal-icon">🔒</div>
      <h3 style="font-size:1.35rem; font-weight:800; margin-bottom:8px; color:var(--gray-900);">Sign In Required</h3>
      <p class="text-sm text-muted mb-20">You are browsing in <strong>Guest Mode</strong>. You can view all venues, inspect high-resolution photo galleries, and explore catering menus freely.<br><br>To place a reservation or order catering, please sign in to your account.</p>
      <div class="flex gap-12 mb-12">
        <a href="login-role.php" class="btn btn-primary btn-full font-bold">Sign In to Continue →</a>
        <a href="signup-role.php" class="btn btn-outline btn-full font-semibold">Create Account</a>
      </div>
      <div class="text-xs text-muted">Authorized access for Customers, Staff, Caterers &amp; Admins.</div>
    </div>
  </div>

        <footer class="page-footer">
    <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
  </footer>
<script src="js/app.js"></script>
</body>
</html>