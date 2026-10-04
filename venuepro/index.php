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
  <title>VenuePro – Enterprise Venue & Event Management</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .landing-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 48px; background: #fff; border-bottom: 1px solid var(--gray-200);
      position: sticky; top: 0; z-index: 100;
    }
    .landing-hero {
      padding: 80px 24px 60px; text-align: center;
      background: radial-gradient(circle at 50% 20%, #eff6ff 0%, #ffffff 70%);
    }
    .landing-hero h1 {
      font-size: 3.2rem; font-weight: 800; max-width: 800px; margin: 0 auto 16px;
      line-height: 1.15; letter-spacing: -0.02em;
    }
    .landing-hero p {
      font-size: 1.2rem; color: var(--gray-600); max-width: 620px; margin: 0 auto 32px;
    }
    .hero-ctas { display: flex; justify-content: center; gap: 16px; margin-bottom: 48px; }
    .features-section { padding: 60px 48px; max-width: 1200px; margin: 0 auto; }
    .roles-section { padding: 60px 48px 80px; background: var(--gray-100); text-align: center; }
  </style>
</head>
<body>
  <header class="landing-header">
    <div class="flex-center gap-12">
      <img src="assets/logo.png" alt="VenuePro" class="header-logo-img">
        <div>
          <div style="font-size:1.2rem; font-weight:800; color:var(--gray-900);">VenuePro</div>
        <div style="font-size:0.75rem; color:var(--gray-500);">Enterprise Event Logistics</div>
      </div>
    </div>
    <nav class="flex-center gap-16">
      <a href="venues.php" style="color:var(--gray-700); font-weight:600; font-size:0.9rem;">Venues</a>
      <a href="packages.php" style="color:var(--gray-700); font-weight:600; font-size:0.9rem;">Catering Packages</a>
      <a href="venues.php" class="btn btn-outline btn-sm">Sign In</a>
      <a href="signup-role.php" class="btn btn-primary btn-sm">Get Started</a>
    </nav>
  </header>

  <section class="landing-hero">
    <span class="stat-badge positive" style="margin-bottom:16px;">Next-Gen Event Operations</span>
    <h1>The Enterprise Venue & Catering Orchestration Platform</h1>
    <p>Seamlessly coordinate premium spaces, bespoke catering services, and multi-tier event staff in one unified operating system.</p>
    <div class="hero-ctas">
      <a href="venues.php" class="btn btn-primary btn-lg font-bold">Explore Venues →</a>
    </div>
  </section>

  <section class="features-section">
    <div class="grid-3">
      <div class="card" style="padding:28px;">
        <div class="role-icon">🏛️</div>
        <h3 class="mb-8">Curated Enterprise Venues</h3>
        <p>Explore 100+ vetted luxury ballrooms, rooftops, and tech pavilions equipped with verified specifications and real-time calendar availability.</p>
      </div>
      <div class="card" style="padding:28px;">
        <div class="role-icon">🍳</div>
        <h3 class="mb-8">Bespoke Catering Tiers</h3>
        <p>Pre-configured Gold, Platinum, and Custom signature packages managed directly by certified culinary partners and kitchen crews.</p>
      </div>
      <div class="card" style="padding:28px;">
        <div class="role-icon">📊</div>
        <h3 class="mb-8">Full-Lifecycle Logistics</h3>
        <p>Complete multi-role portals for Customers, Caterers, Coordinators, and Administrators with instant invoicing and operational oversight.</p>
      </div>
    </div>
  </section>

  <section class="roles-section">
    <h2 class="mb-8">Select Your Portal</h2>
    <p class="mb-32">VenuePro delivers tailored workflows for every stakeholder in event execution.</p>
    <div class="role-grid" style="margin: 0 auto;">
      <a href="customer/customer-dashboard.php" class="role-card" style="text-align:left; text-decoration:none;">
        <div class="role-icon">🛍️</div>
        <div class="role-name" style="color:var(--gray-900);">Customer Portal</div>
        <div class="role-desc">Search premium venues, customize catering, and monitor booking status.</div>
      </a>
      <a href="caterer/caterer-dashboard.php" class="role-card" style="text-align:left; text-decoration:none;">
        <div class="role-icon">👨‍🍳</div>
        <div class="role-name" style="color:var(--gray-900);">Caterer Portal</div>
        <div class="role-desc">Real-time kitchen queues, food packages, and order fulfillment.</div>
      </a>
      <a href="staff/staff-dashboard.php" class="role-card" style="text-align:left; text-decoration:none;">
        <div class="role-icon">👥</div>
        <div class="role-name" style="color:var(--gray-900);">Staff Portal</div>
        <div class="role-desc">Event day checklists, setup timeline, and direct coordinator dispatch.</div>
      </a>
      <a href="admin/admin-dashboard.php" class="role-card" style="text-align:left; text-decoration:none;">
        <div class="role-icon">🛡️</div>
        <div class="role-name" style="color:var(--gray-900);">Admin Portal</div>
        <div class="role-desc">System governance, booking approvals, revenue analytics, and reports.</div>
      </a>
    </div>
  </section>

        <footer class="page-footer">
    <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
  </footer>
<script src="js/app.js"></script>
</body>
</html>