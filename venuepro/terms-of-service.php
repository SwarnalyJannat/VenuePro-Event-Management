<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();
?>
<?php
$stmtPol = $db->prepare("SELECT * FROM site_policies WHERE policy_key = ? LIMIT 1");
$stmtPol->execute(['terms_of_service']);
$policyRow = $stmtPol->fetch();
$policyTitle = $policyRow['title'] ?? 'Policy';
$policyHtml = $policyRow['content'] ?? '<p>Policy information is being updated.</p>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Terms of Service</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .legal-container { max-width: 960px; margin: 0 auto; padding: 40px 24px 60px; }
    .legal-header { margin-bottom: 28px; border-bottom: 1px solid var(--gray-200); padding-bottom: 20px; }
    .legal-section { margin-bottom: 28px; }
    .legal-section h2 { font-size: 1.25rem; font-weight: 700; margin-bottom: 10px; color: var(--gray-900); }
    .legal-section p, .legal-section li { font-size: 0.95rem; line-height: 1.65; color: var(--gray-700); margin-bottom: 12px; }
    .legal-section ul { padding-left: 24px; margin-bottom: 16px; }
  </style>
</head>
<body style="background:#f8fafc;">

  <!-- Header with ONLY Back Button -->
  <header class="landing-header" style="display:flex; align-items:center; justify-content:space-between; padding:18px 48px; background:#fff; border-bottom:1px solid var(--gray-200); position:sticky; top:0; z-index:100;">
    <div class="flex-center gap-12">
      <a href="customer/customer-dashboard.php" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
        <img src="assets/logo.png" alt="VenuePro" class="header-logo-img">
        <div>
          <div style="font-size:1.2rem; font-weight:800; color:var(--gray-900);">VenuePro</div>
          <div style="font-size:0.7rem; color:var(--gray-500); font-weight:500;">Customer Legal Portal</div>
        </div>
      </a>
    </div>
    <nav class="flex-center gap-12">
      <button onclick="if(window.history.length > 1){window.history.back();}else{window.location.href='customer/customer-dashboard.php';}" class="btn btn-outline btn-sm flex-center gap-8" style="font-weight:700; cursor:pointer; padding:7px 16px;">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Back</span>
      </button>
    </nav>
  </header>

  <main class="legal-container">
    <div class="legal-header">
      <span class="stat-badge positive mb-8">TERMS OF AGREEMENT</span>
      <h1 style="font-size:2.2rem; font-weight:800; margin:10px 0 6px;">Terms of Service</h1>
      <p class="text-sm text-muted">Version 2.4 · Effective Date: September 6, 2026 · Governing VenuePro Customer Reservations</p>
    </div>

    <div class="card mb-32" style="padding:28px;">
      <div class="legal-section">
        <h2>1. Agreement to Terms</h2>
        <p>By registering, accessing, browsing, or booking facilities through VenuePro ("Platform"), you agree to be legally bound by these Terms of Service. If you are contracting on behalf of a corporate entity or organization, you represent that you possess authorization to bind that entity to these provisions.</p>
      </div>

      <div class="legal-section">
        <h2>2. Role Obligations &amp; Platform Use</h2>
        <ul>
          <li><strong>Clients &amp; Customers:</strong> Responsible for supplying accurate attendee headcounts, adhering to property policies, and settling invoices in accordance with payment milestones.</li>
          <li><strong>Catering Partners:</strong> Required to maintain municipal food safety certifications, complete verified prep checklists, and uphold agreed menu specifications.</li>
          <li><strong>Event Staff:</strong> Obligated to verify on-site safety checklists, audio-visual readiness, and coordinate timely event schedules.</li>
          <li><strong>System Administrators:</strong> Responsible for reviewing pending bookings, validating caterer/staff credentials, and auditing revenue operations.</li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>3. Booking Confirmation, Rescheduling &amp; Cancellations</h2>
        <p>Reservations submitted via the platform enter a "Pending Review" status until confirmed by venue administration. Confirmed bookings guarantee exclusive space allocation for the scheduled duration.</p>
        <ul>
          <li><strong>Free Cancellation:</strong> Permitted up to 30 calendar days prior to event commencement.</li>
          <li><strong>Date Adjustments:</strong> Requests to change dates are subject to venue availability. In case of scheduling conflicts, alternative dates will be provided via the booking portal.</li>
          <li><strong>Catering Adjustments:</strong> Final guest headcount locks 7 days prior to the event date to allow kitchen procurement.</li>
        </ul>
      </div>

      <div class="legal-section">
        <h2>4. Financial Terms, Invoicing &amp; VAT</h2>
        <p>All pricing listed in the venue catalog and catering tiers reflects standard base rates. Published rates are subject to applicable municipal service fees (10%) and Value Added Tax (VAT 8%) as itemized on formal invoices. Downloadable PDF invoices are accessible directly through the client dashboard.</p>
      </div>

      <div class="legal-section">
        <h2>5. Property Care, Security &amp; Conduct</h2>
        <p>Organizers and attendees must adhere to fire code limits, local sound curfews, and facility guidelines. Damage to audio-visual infrastructure, structural architectural elements, or garden grounds will be assessed to the organizer's primary billing profile.</p>
      </div>

      <div class="legal-section">
        <h2>6. Dispute Resolution &amp; Governing Law</h2>
        <p>These terms are governed by commercial event logistics arbitration regulations. Any disputes arising from platform transactions shall first seek resolution through VenuePro Executive Dispute Concierge.</p>
      </div>
    </div>
  </main>

  <footer class="page-footer">
    <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
    <div class="footer-links">
      <a href="privacy-policy.php">Privacy Policy</a>
      <a href="terms-of-service.php">Terms of Service</a>
      <a href="contact-support.php">Contact Support</a>
    </div>
  </footer>
<script src="js/app.js"></script>
</body>
</html>
