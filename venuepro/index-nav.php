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
  <title>VenuePro – Role-Based Directory &amp; Sitemap</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { background: #f8fafc; padding: 40px 24px; font-family: var(--font-sans, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif); }
    .nav-container { max-width: 1180px; margin: 0 auto; }
    .nav-header {
      background: #fff; padding: 28px 36px; border-radius: var(--radius);
      border: 1px solid var(--gray-200); box-shadow: var(--shadow-sm);
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
      margin-bottom: 32px;
    }
    .nav-group { margin-bottom: 40px; }
    .nav-group-title {
      font-size: 1.3rem; font-weight: 800; color: var(--gray-900);
      margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid var(--gray-200);
      display: flex; align-items: center; justify-content: space-between;
    }
    .folder-badge {
      font-size: 0.75rem; font-weight: 700; font-family: monospace;
      padding: 4px 10px; border-radius: 6px;
      background: var(--gray-100); color: var(--gray-700);
      border: 1px solid var(--gray-300);
    }
    .page-link-card {
      background: #fff; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 16px 20px; text-decoration: none; color: var(--gray-800);
      transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: space-between;
    }
    .page-link-card:hover {
      border-color: var(--primary); box-shadow: 0 4px 12px rgba(37,99,235,0.1);
      transform: translateY(-2px); text-decoration: none;
    }
    .page-link-name {
      font-weight: 700; font-size: 0.95rem; color: var(--primary);
      margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
    }
    .page-link-desc { font-size: 0.8rem; color: var(--gray-500); line-height: 1.45; }
    .role-stats { display: flex; gap: 12px; margin-top: 8px; flex-wrap: wrap; }
    .role-chip {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.8rem; font-weight: 600; padding: 6px 14px;
      border-radius: 20px; background: #fff; border: 1px solid var(--gray-200);
    }
  </style>
</head>
<body>
  <div class="nav-container">
    <header class="nav-header">
      <div class="flex-center gap-16">
        <div class="sidebar-logo-icon" style="width:52px; height:52px; font-size:26px; border-radius:12px;">V</div>
        <div>
          <h1 style="font-size:1.9rem; font-weight:800; margin:0 0 4px; color:var(--gray-900);">VenuePro Role Architecture</h1>
          <p style="margin:0; color:var(--gray-500); font-size:0.95rem;">Dedicated Guest Experience (Root) + 4 Authenticated Role Workspaces: <code>customer/</code>, <code>admin/</code>, <code>caterer/</code>, <code>staff/</code>.</p>
        </div>
      </div>
      <div class="flex gap-10">
        <a href="index.php" class="btn btn-outline">Guest Home →</a>
        <a href="venues.php" class="btn btn-primary">Role Sign In →</a>
      </div>
    </header>

    <div class="role-stats mb-32">
      <div class="role-chip" style="background:#f0fdf4; border-color:#86efac; color:#15803d;"><span>🌐</span> <strong>Guest Mode</strong> (Public Browse)</div>
      <div class="role-chip"><span>🛍️</span> <strong>21</strong> Customer Pages (Logged-In)</div>
      <div class="role-chip"><span>🛡️</span> <strong>19</strong> Admin Pages</div>
      <div class="role-chip"><span>👨‍🍳</span> <strong>11</strong> Caterer Pages</div>
      <div class="role-chip"><span>👥</span> <strong>4</strong> Staff Pages</div>
    </div>

    <!-- 1. Guest Browsing Mode -->
    <div class="nav-group">
      <div class="nav-group-title">
        <span class="flex-center gap-8"><span>🌐</span> Guest Mode (Unauthenticated Public Experience)</span>
        <span class="folder-badge" style="background:#f0fdf4; color:#15803d; border-color:#86efac;">📂 / (Root - Public Pages)</span>
      </div>
      <div class="grid-3 gap-16">
        <a href="index.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">index.html <span class="pill pill-confirmed" style="font-size:0.6rem;">GUEST HOME</span></div>
          <div class="page-link-desc">Public homepage with Login/Signup buttons in header, platform features, and role gateway cards.</div>
        </a>
        <a href="venues.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">venues.html <span class="pill pill-confirmed" style="font-size:0.6rem;">GUEST VENUES</span></div>
          <div class="page-link-desc">Guest venue explorer: search &amp; filter 128 venues; "View Photos" opens gallery; "Book Now" prompts sign-in.</div>
        </a>
        <a href="venue-details.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">venue-details.html <span class="pill pill-confirmed" style="font-size:0.6rem;">GUEST GALLERY</span></div>
          <div class="page-link-desc">Grand Emerald Ballroom specs with interactive <strong>+14 Photos Lightbox Modal</strong>; booking panel requires sign-in.</div>
        </a>
        <a href="packages.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">packages.html <span class="pill pill-confirmed" style="font-size:0.6rem;">GUEST PACKAGES</span></div>
          <div class="page-link-desc">Browse 4 catering package tiers (Gold, Platinum, Custom Signature, Bespoke); ordering prompts sign-in.</div>
        </a>
        <a href="venues.php" class="page-link-card">
          <div class="page-link-name">login-role.html</div>
          <div class="page-link-desc">Universal authentication gateway routing users to their designated role portal.</div>
        </a>
        <a href="signup-role.php" class="page-link-card">
          <div class="page-link-name">signup-role.html</div>
          <div class="page-link-desc">Registration role selector for new Customer, Caterer, Staff, or Admin accounts.</div>
        </a>
        <a href="registration-successful.php" class="page-link-card">
          <div class="page-link-name">registration-successful.html</div>
          <div class="page-link-desc">Account registration success confirmation with instant return-to-login button.</div>
        </a>
      </div>
    </div>

    <!-- 2. Customer Folder (Logged-In) -->
    <div class="nav-group">
      <div class="nav-group-title">
        <span class="flex-center gap-8"><span>🛍️</span> Customer Experience &amp; Booking Funnel (Logged-In)</span>
        <span class="folder-badge">📂 /customer/ (20 files)</span>
      </div>
      <div class="grid-3 gap-16">
        <a href="customer/customer-dashboard.php" class="page-link-card">
          <div class="page-link-name">customer-dashboard.html</div>
          <div class="page-link-desc">Mahmud's logged-in dashboard: recent activities, countdown card, and Events Invoice button.</div>
        </a>
        <a href="customer/venue-listings.php" class="page-link-card">
          <div class="page-link-name">venue-listings.html</div>
          <div class="page-link-desc">Logged-in customer venue inventory with direct 1-click booking flow into reservation wizard.</div>
        </a>
        <a href="customer/venue-details.php" class="page-link-card">
          <div class="page-link-name">venue-details.html</div>
          <div class="page-link-desc">Grand Emerald Ballroom showcase with photo gallery, pricing tiers &amp; direct booking form.</div>
        </a>
        <a href="customer/booking-catering-packages.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">booking-catering-packages.html <span class="pill pill-confirmed" style="font-size:0.6rem;">4 ALIGNED CARDS</span></div>
          <div class="page-link-desc">4 perfectly aligned cards: Gold, Platinum, Custom Signature, and Custom Bespoke Package Builder.</div>
        </a>
        <a href="customer/caterer-custom-package.php" class="page-link-card" style="border-color:#059669; background:#f0fdf4;">
          <div class="page-link-name">caterer-custom-package.html <span class="pill pill-confirmed" style="font-size:0.6rem;">BESPOKE BUILDER</span></div>
          <div class="page-link-desc">Full custom package builder: configure per-head count, multi-course dishes, culinary style, date &amp; dietary specs.</div>
        </a>
        <a href="customer/book-venue.php" class="page-link-card">
          <div class="page-link-name">book-venue.html</div>
          <div class="page-link-desc">Complete 3-step interactive booking wizard: date &amp; guests, catering tier selection &amp; card payment.</div>
        </a>
        <a href="customer/customer-catering-progress.php" class="page-link-card">
          <div class="page-link-name">customer-catering-progress.html</div>
          <div class="page-link-desc">Real-time catering task monitor: watch Chef Rivera's kitchen milestones dynamically update.</div>
        </a>
        <a href="customer/booking-date-guests.php" class="page-link-card">
          <div class="page-link-name">booking-date-guests.html</div>
          <div class="page-link-desc">Booking Step 1: Calendar date picker, event duration, and headcount adjuster.</div>
        </a>
        <a href="customer/booking-summary-payment.php" class="page-link-card">
          <div class="page-link-name">booking-summary-payment.html</div>
          <div class="page-link-desc">Booking Step 3: Secure card payment form and itemized cost breakdown receipt.</div>
        </a>
        <a href="customer/booking-success.php" class="page-link-card">
          <div class="page-link-name">booking-success.html</div>
          <div class="page-link-desc">Confirmation screen with booking reference code and invoice download button.</div>
        </a>
                <a href="customer/customer-live-progress.php" class="page-link-card">
          <div class="page-link-name">customer-live-progress.html</div>
          <div class="page-link-desc">Unified Live Progress: Select booked venues & track real-time setup + catering milestones.</div>
        </a>
        <a href="customer/booking-status-timeline.php" class="page-link-card">
          <div class="page-link-name">booking-status-timeline.html</div>
          <div class="page-link-desc">Live vertical lifecycle timeline from submission to post-event signoff.</div>
        </a>
        <a href="customer/browse-caterers.php" class="page-link-card">
          <div class="page-link-name">browse-caterers.html</div>
          <div class="page-link-desc">Marketplace of verified catering partners, cuisine tags, and menu packages.</div>
        </a>
        <a href="customer/customer-chat.php" class="page-link-card">
          <div class="page-link-name">customer-chat.html</div>
          <div class="page-link-desc">Direct messaging thread with assigned Event Coordinator Sarah Jenkins.</div>
        </a>
        <a href="customer/customer-notifications.php" class="page-link-card">
          <div class="page-link-name">customer-notifications.html</div>
          <div class="page-link-desc">Categorized notification center for approvals, catering updates, and receipts.</div>
        </a>
        <a href="customer/client-invoice.php" class="page-link-card">
          <div class="page-link-name">client-invoice.html</div>
          <div class="page-link-desc">Print-friendly client tax invoice with bank details, itemized fees, and PDF export.</div>
        </a>
        <a href="customer/venue-selection.php" class="page-link-card">
          <div class="page-link-name">venue-selection.html</div>
          <div class="page-link-desc">Visual venue selector cards with capacity badges and direct booking CTAs.</div>
        </a>
        <a href="customer/booking-date-conflict.php" class="page-link-card">
          <div class="page-link-name">booking-date-conflict.html</div>
          <div class="page-link-desc">Date collision warning modal suggesting alternative available weekend dates.</div>
        </a>
        <a href="admin/booking-rejected.php" class="page-link-card">
          <div class="page-link-name">booking-rejected.html</div>
          <div class="page-link-desc">Admin reservation rejection screen with compliance feedback.</div>
        </a>
        <a href="customer/customer-login.php" class="page-link-card">
          <div class="page-link-name">customer-login.html</div>
          <div class="page-link-desc">Dedicated customer sign-in portal with email and Google single sign-on.</div>
        </a>
        <a href="customer/customer-register.php" class="page-link-card">
          <div class="page-link-name">customer-register.html</div>
          <div class="page-link-desc">New customer account registration form with terms acceptance.</div>
        </a>
        <a href="customer/customer-profile.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">customer-profile.php <span class="pill pill-confirmed" style="font-size:0.6rem;">SELF SERVICE</span></div>
          <div class="page-link-desc">Customer self-service profile editor: update name, phone, avatar initials/color, and change password securely.</div>
        </a>
      </div>
    </div>

    <!-- 3. Admin Folder -->
    <div class="nav-group">
      <div class="nav-group-title">
        <span class="flex-center gap-8"><span>🛡️</span> Administrator Governance &amp; Analytics</span>
        <span class="folder-badge">📂 /admin/ (17 files)</span>
      </div>
      <div class="grid-3 gap-16">
        <a href="admin/admin-dashboard.php" class="page-link-card">
          <div class="page-link-name">admin-dashboard.html</div>
          <div class="page-link-desc">Executive metrics, CSS revenue trend bar chart, and recent booking review queue.</div>
        </a>
        <a href="admin/admin-reports.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">admin-reports.html <span class="pill pill-confirmed" style="font-size:0.6rem;">ROUND 2</span></div>
          <div class="page-link-desc">Full analytics hub: monthly bar chart, month/range filter, transaction register, staff &amp; caterer performance.</div>
        </a>
        <a href="admin/admin-add-staff.php" class="page-link-card">
          <div class="page-link-name">admin-add-staff.html</div>
          <div class="page-link-desc">Staff provisioning form with CV/Resume upload, NID scan upload, and role assignments.</div>
        </a>
        <a href="admin/admin-add-caterer.php" class="page-link-card">
          <div class="page-link-name">admin-add-caterer.html</div>
          <div class="page-link-desc">Onboard catering partner with Chef CV, NID scan, health certificates, and kitchen license uploads.</div>
        </a>
        <a href="admin/admin-pending-bookings.php" class="page-link-card">
          <div class="page-link-name">admin-pending-bookings.html</div>
          <div class="page-link-desc">Pending reservations queue with multi-property filters and one-click inspection.</div>
        </a>
        <a href="admin/admin-booking-approval.php" class="page-link-card">
          <div class="page-link-name">admin-booking-approval.html</div>
          <div class="page-link-desc">Detailed booking approval inspector with approve, decline, and notes capabilities.</div>
        </a>
        <a href="admin/admin-approval-success.php" class="page-link-card">
          <div class="page-link-name">admin-approval-success.html</div>
          <div class="page-link-desc">Approval confirmed banner and automated notification dispatch state.</div>
        </a>
        <a href="admin/admin-staff-management.php" class="page-link-card">
          <div class="page-link-name">admin-staff-management.html</div>
          <div class="page-link-desc">Coordinator directory with assigned properties, contact badges, and status controls.</div>
        </a>
        <a href="admin/admin-catering-management.php" class="page-link-card">
          <div class="page-link-name">admin-catering-management.html</div>
          <div class="page-link-desc">Catering partners oversight with approved vendors and pending review applications.</div>
        </a>
        <a href="admin/admin-review-caterer.php" class="page-link-card">
          <div class="page-link-name">admin-review-caterer.html</div>
          <div class="page-link-desc">Commercial kitchen license and health audit console with approval actions.</div>
        </a>
        <a href="admin/venue-management.php" class="page-link-card">
          <div class="page-link-name">venue-management.html</div>
          <div class="page-link-desc">Enterprise property catalog with occupancy percentages and pricing configuration.</div>
        </a>
        <a href="admin/add-new-venue.php" class="page-link-card">
          <div class="page-link-name">add-new-venue.html</div>
          <div class="page-link-desc">Publish new venue with amenity toggles, layout specs, and photo upload.</div>
        </a>
        <a href="admin/admin-export-revenue.php" class="page-link-card">
          <div class="page-link-name">admin-export-revenue.html</div>
          <div class="page-link-desc">Custom financial export wizard (PDF, Excel, CSV) with section checkboxes.</div>
        </a>
        <a href="admin/admin-export-success.php" class="page-link-card">
          <div class="page-link-name">admin-export-success.html</div>
          <div class="page-link-desc">Report generation confirmation screen with instant download trigger.</div>
        </a>
        <a href="admin/notification-center.php" class="page-link-card">
          <div class="page-link-name">notification-center.html</div>
          <div class="page-link-desc">System administrator notification center for bookings, caterers, and shifts.</div>
        </a>
        <a href="admin/admin-login.php" class="page-link-card">
          <div class="page-link-name">admin-login.html</div>
          <div class="page-link-desc">Restricted administrator authentication terminal with security credentials.</div>
        </a>
        <a href="admin/admin-signup.php" class="page-link-card">
          <div class="page-link-name">admin-signup.html</div>
          <div class="page-link-desc">System administrator provisioning console with internal access key validation.</div>
        </a>
        <a href="admin/admin-profile.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">admin-profile.php <span class="pill pill-confirmed" style="font-size:0.6rem;">SELF SERVICE</span></div>
          <div class="page-link-desc">Administrator self-service profile editor: manage administrative credentials, avatar styling, and secure password updates.</div>
        </a>
        <a href="admin/admin-user-management.php" class="page-link-card" style="border-color:var(--primary); background:#eff6ff;">
          <div class="page-link-name">admin-user-management.php <span class="pill pill-confirmed" style="font-size:0.6rem;">GOVERNANCE</span></div>
          <div class="page-link-desc">User account governance console: list, filter, suspend, and safely remove customer and administrator accounts with safety rule enforcement.</div>
        </a>
      </div>
    </div>

    <!-- 4. Caterer Folder -->
    <div class="nav-group">
      <div class="nav-group-title">
        <span class="flex-center gap-8"><span>👨‍🍳</span> Caterer &amp; Kitchen Operations</span>
        <span class="folder-badge">📂 /caterer/ (11 files)</span>
      </div>
      <div class="grid-3 gap-16">
        <a href="caterer/caterer-dashboard.php" class="page-link-card">
          <div class="page-link-name">caterer-dashboard.html</div>
          <div class="page-link-desc">Live kitchen queue with selectable orders, preparation milestones, and package performance.</div>
        </a>
        <a href="caterer/caterer-food-packages.php" class="page-link-card">
          <div class="page-link-name">caterer-food-packages.html</div>
          <div class="page-link-desc">Catalog with CSS tabs for Fixed Tier Packages vs. Custom Bespoke Packages (per-head pricing).</div>
        </a>
        <a href="caterer/caterer-create-package-1.php" class="page-link-card">
          <div class="page-link-name">caterer-create-package-1.html</div>
          <div class="page-link-desc">Step 1: Package tier, capacity, and professional food photography upload zone.</div>
        </a>
        <a href="caterer/caterer-create-package-2.php" class="page-link-card">
          <div class="page-link-name">caterer-create-package-2.html</div>
          <div class="page-link-desc">Step 2: Course breakdown table (Appetizers, Mains, Desserts, Beverages).</div>
        </a>
        <a href="caterer/caterer-create-package-3.php" class="page-link-card">
          <div class="page-link-name">caterer-create-package-3.html</div>
          <div class="page-link-desc">Step 3: Per-head pricing, minimum order thresholds, and publishing controls.</div>
        </a>
                <a href="caterer/caterer-notifications.php" class="page-link-card">
          <div class="page-link-name">caterer-notifications.html</div>
          <div class="page-link-desc">Kitchen orders queue alerts, dispatch reminders, and menu requests.</div>
        </a>
        <a href="caterer/package-details.php" class="page-link-card">
          <div class="page-link-name">package-details.html</div>
          <div class="page-link-desc">Individual package specification card with course details, pricing, and active bookings.</div>
        </a>
        <a href="caterer/succeed-package.php" class="page-link-card">
          <div class="page-link-name">succeed-package.html</div>
          <div class="page-link-desc">Package published confirmation card with marketplace preview links.</div>
        </a>
        <a href="caterer/caterer-order-details.php" class="page-link-card">
          <div class="page-link-name">caterer-order-details.html</div>
          <div class="page-link-desc">Detailed order sheet with special dietary requirements, ingredient specs, and timestamps.</div>
        </a>
        <a href="caterer/caterer-login.php" class="page-link-card">
          <div class="page-link-name">caterer-login.html</div>
          <div class="page-link-desc">Culinary partner sign-in screen with kitchen operations branding.</div>
        </a>
        <a href="caterer/caterer-signup.php" class="page-link-card">
          <div class="page-link-name">caterer-signup.html</div>
          <div class="page-link-desc">Caterer partner onboarding form with kitchen specs and food safety declaration.</div>
        </a>
        <a href="caterer/caterer-pending.php" class="page-link-card">
          <div class="page-link-name">caterer-pending.html</div>
          <div class="page-link-desc">Application under review notice with review status progression indicator.</div>
        </a>
      </div>
    </div>

    <!-- 5. Staff Folder -->
    <div class="nav-group">
      <div class="nav-group-title">
        <span class="flex-center gap-8"><span>👥</span> Staff Operations &amp; Floor Logistics</span>
        <span class="folder-badge">📂 /staff/ (4 files)</span>
      </div>
      <div class="grid-3 gap-16">
        <a href="staff/staff-dashboard.php" class="page-link-card">
          <div class="page-link-name">staff-dashboard.html</div>
          <div class="page-link-desc">Daily coordination hub: selectable event schedule table and interactive checklist tasks.</div>
        </a>
        <a href="staff/staff-event-setup.php" class="page-link-card">
        <a href="staff/staff-chat.php" class="page-link-card">
          <div class="page-link-name">staff-chat.html</div>
          <div class="page-link-desc">Direct customer event coordination chat for on-site staff.</div>
        </a>
        <a href="staff/staff-notifications.php" class="page-link-card">
          <div class="page-link-name">staff-notifications.html</div>
          <div class="page-link-desc">Staff shift alerts, setup checklists, and client message alerts.</div>
        </a>
          <div class="page-link-name">staff-event-setup.html</div>
          <div class="page-link-desc">Detailed setup checklist (click-to-complete), timeline, contacts, and Mark Event Complete action.</div>
        </a>
        <a href="staff/staff-login.php" class="page-link-card">
          <div class="page-link-name">staff-login.html</div>
          <div class="page-link-desc">Staff operations portal sign in with employee credentials.</div>
        </a>
        <a href="staff/staff-signup.php" class="page-link-card">
          <div class="page-link-name">staff-signup.html</div>
          <div class="page-link-desc">Staff account activation form requiring invitation token.</div>
        </a>
      </div>
    </div>
    <!-- Legal, Support & Public Pages -->
    <div class="nav-section">
      <div class="nav-section-title">
        <span class="badge" style="background:#f1f5f9; color:var(--gray-700);">Legal & Support</span>
        Compliance, Service Terms & Concierge
      </div>
      <div class="grid-3 gap-16">
        <a href="privacy-policy.php" class="page-link-card">
          <div class="page-link-name">privacy-policy.html</div>
          <div class="page-link-desc">Data governance, SOC-2 compliance, encryption, and client rights policy.</div>
        </a>
        <a href="terms-of-service.php" class="page-link-card">
          <div class="page-link-name">terms-of-service.html</div>
          <div class="page-link-desc">Enterprise terms, booking rules, cancellation, and catering obligations.</div>
        </a>
        <a href="contact-support.php" class="page-link-card">
          <div class="page-link-name">contact-support.html</div>
          <div class="page-link-desc">24/7 concierge contact channels, support ticket form, and FAQ directory.</div>
        </a>
      </div>
    </div>


          <footer class="page-footer">
    <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
  </footer>
  </div>
<script src="js/app.js"></script>
</body>
</html>