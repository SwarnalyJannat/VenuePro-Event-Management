<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$venueId = (int)($_GET['venue_id'] ?? 1);
$stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtV->execute([$venueId]);
$venue = $stmtV->fetch() ?: $db->query("SELECT * FROM venues WHERE status = 'active' ORDER BY id ASC LIMIT 1")->fetch();
$venueId = (int)$venue['id'];

// Default dynamic event date (from param or 14 days out)
$defaultEventDate = !empty($_GET['date']) ? sanitize($_GET['date']) : date('Y-m-d', strtotime('+14 days'));
$defaultDateLabel = date('M j, Y', strtotime($defaultEventDate));

// Resolve caterers assigned to this specific designated venue by admin
$venueName = $venue['name'] ?? '';
$venueIdStr = (string)$venueId;

$stmtCaterers = $db->prepare("
    SELECT DISTINCT u.id, u.name, cp.business_name, cp.assigned_venue
    FROM users u
    JOIN caterer_profiles cp ON u.id = cp.user_id
    WHERE u.role = 'caterer' 
      AND u.status = 'active' 
      AND cp.approval_status = 'approved'
      AND (
          cp.assigned_venue = ? 
          OR cp.assigned_venue = ? 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR FIND_IN_SET(?, cp.assigned_venue) > 0 
          OR cp.assigned_venue LIKE ?
      )
    ORDER BY cp.business_name ASC
");
$stmtCaterers->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
$caterersList = $stmtCaterers ? $stmtCaterers->fetchAll(PDO::FETCH_ASSOC) : [];
$assignedCatererIds = array_map('intval', array_column($caterersList, 'id'));

// Load packages strictly from caterers assigned to this venue
$packages = [];
if (!empty($assignedCatererIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($assignedCatererIds), '?'));
    $stmtPkgs = $db->prepare("
        SELECT cp.*, u.name AS caterer_name, pr.business_name, pr.assigned_venue
        FROM catering_packages cp
        LEFT JOIN users u ON cp.caterer_id = u.id
        LEFT JOIN caterer_profiles pr ON u.id = pr.user_id
        WHERE cp.status IN ('published', 'active')
          AND cp.caterer_id IN ($inPlaceholders)
        ORDER BY cp.price_per_event ASC
    ");
    $stmtPkgs->execute($assignedCatererIds);
    $packages = $stmtPkgs->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Book a Venue</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* CSS step wizard using radio buttons */
    .wizard-radio { display:none; }
    #ws1:checked ~ .wizard-body .wizard-step-1 { display:block; }
    #ws2:checked ~ .wizard-body .wizard-step-2 { display:block; }
    #ws3:checked ~ .wizard-body .wizard-step-3 { display:block; }
    .wizard-step-1,.wizard-step-2,.wizard-step-3 { display:none; }

    #ws1:checked ~ .step-indicators .si1,
    #ws2:checked ~ .step-indicators .si2,
    #ws3:checked ~ .step-indicators .si3 {
      background:var(--primary); color:#fff; border-color:var(--primary);
    }
    .step-indicators { display:flex; align-items:center; justify-content:center; gap:0; margin-bottom:32px; }
    .si { width:36px; height:36px; border-radius:50%; border:2px solid var(--gray-300); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.875rem; color:var(--gray-400); background:#fff; }
    .step-line2 { flex:1; height:2px; background:var(--gray-200); max-width:80px; }
    .si-label { font-size:.8rem; font-weight:600; color:var(--gray-500); margin-left:6px; }

    /* Package select cards */
    .pkg-radio { display:none; }
    .pkg-sel-card { border:2px solid var(--gray-200); border-radius:var(--radius); padding:16px; cursor:pointer; transition:all .15s; }
    .pkg-radio:checked + .pkg-sel-card { border-color:var(--primary); background:#eff6ff; }
    .pkg-sel-card:hover { border-color:var(--primary); }

    /* Guest counter display */
    .guest-display { font-size:2rem; font-weight:800; text-align:center; color:var(--primary); min-width:60px; }
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
            <?= renderNotifBadge() ?>
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

<div class="flex-between mb-8">
  <div>
    <h1>Book a Venue Event</h1>
    <p><?= e($venue['name']) ?> — <?= e($venue['district'] ?? 'Prime Location') ?></p>
  </div>
  <a href="venue-listings.php" class="btn btn-ghost btn-sm">← Back to Listings</a>
</div>

<!-- Wizard with CSS steps -->
<input type="radio" class="wizard-radio" name="wizard" id="ws1" checked>
<input type="radio" class="wizard-radio" name="wizard" id="ws2">
<input type="radio" class="wizard-radio" name="wizard" id="ws3">

<!-- Step indicator labels (visual only) -->
<div class="step-indicators">
  <div class="si si1">1</div>
  <span class="si-label" style="margin-right:8px;">Date &amp; Guests</span>
  <div class="step-line2"></div>
  <div class="si si2">2</div>
  <span class="si-label" style="margin-right:8px;">Catering Package</span>
  <div class="step-line2"></div>
  <div class="si si3">3</div>
  <span class="si-label">Review &amp; Pay</span>
</div>

<div class="wizard-body">
  <!-- STEP 1: Venue date + guest count -->
  <div class="wizard-step-1">
    <div class="grid-2" style="gap:28px; align-items:start;">
      <div class="card">
        <h2 class="mb-4">Step 1 — Event Date &amp; Guests</h2>
        <p class="mb-20">Select your event date, duration and expected attendance.</p>
        <div class="form-group">
          <label class="form-label">Event Date *</label>
          <input type="date" class="form-control" id="bv-event-date" value="<?= e($defaultEventDate) ?>" min="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" value="10:00">
          </div>
          <div class="form-group">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" value="22:00">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Expected Guest Count *</label>
          <div class="flex-center gap-12">
            <button type="button" class="btn btn-ghost btn-sm" id="btn-guest-minus" style="width:40px;">−</button>
            <div class="guest-display" id="bv-guest-val">150</div>
            <button type="button" class="btn btn-ghost btn-sm" id="btn-guest-plus" style="width:40px;">+</button>
          </div>
          <div class="form-hint text-center">Maximum capacity: <?= number_format($venue['capacity']) ?> guests</div>
        </div>

        <div class="form-group">
          <label class="form-label">Event Type</label>
          <select class="form-control">
            <option>Corporate Summit / Conference</option>
            <option>Gala Dinner &amp; Awards</option>
            <option>Product Launch &amp; Reception</option>
            <option selected>Wedding &amp; Private Celebration</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Special Requirements (Optional)</label>
          <textarea class="form-control" rows="2" placeholder="ADA accessibility needs, staging requirements, custom AV setup..."></textarea>
        </div>

        <label for="ws2" class="btn btn-primary btn-full" style="cursor:pointer; margin-top:16px;">
          Continue to Select Catering Package →
        </label>
      </div>

      <!-- Summary sidebar -->
      <div class="card" style="background:var(--gray-50);">
        <h3 class="mb-12">Booking Summary</h3>
        <?php
          $rawImg = !empty($venue['image_url']) ? $venue['image_url'] : '../assets/venues pic/images.jpg';
          $img = str_starts_with($rawImg, 'http') ? $rawImg : (str_starts_with($rawImg, '../') ? $rawImg : ('../' . $rawImg));
        ?>
        <div style="width:100%; height:120px; border-radius:var(--radius-sm); background:url('<?= e($img) ?>') center/cover; margin-bottom:12px;"></div>
        <div class="font-bold mb-4"><?= e($venue['name']) ?></div>
        <div class="text-xs text-muted mb-16"><?= e($venue['district'] ?? 'District') ?> · Up to <?= number_format($venue['capacity']) ?> Guests · $<?= number_format($venue['base_rate'], 0) ?>/day base</div>
        <div class="text-sm mb-4"><span class="text-muted">Selected Date:</span> <strong id="bv-summary-date"><?= $defaultDateLabel ?></strong></div>
        <div class="text-sm mb-4"><span class="text-muted">Duration:</span> <strong>12 Hours</strong></div>
        <div class="text-sm mb-16"><span class="text-muted">Guest Count:</span> <strong>150 attendees</strong></div>
        <div style="border-top:1px solid var(--gray-200); padding-top:12px;" class="flex-between">
          <span class="text-muted text-sm">Venue Est.</span>
          <span class="font-bold text-primary">$<?= number_format($venue['base_rate'], 2) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 2: Catering Package selection -->
  <div class="wizard-step-2">
    <div class="grid-2" style="grid-template-columns: 2fr 1fr; gap:28px; align-items:start;">
      <div class="card">
        <h2 class="mb-4">Step 2 — Select Catering Package</h2>
        <p class="mb-20">Choose a catering package or select venue-only booking.</p>

        <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
          <input type="radio" class="pkg-radio" name="pkg" id="pkg-none" checked>
          <label class="pkg-sel-card" for="pkg-none">
            <div class="flex-between">
              <div class="font-bold">No Catering (Venue Only)</div>
              <span class="text-sm text-muted">+$0.00</span>
            </div>
            <div class="text-xs text-muted">I will arrange external catering independently.</div>
          </label>

          <?php if (empty($packages)): ?>
          <div style="padding:16px; background:var(--gray-50); border:1px dashed var(--gray-300); border-radius:var(--radius); text-align:center; color:var(--gray-600); font-size:0.85rem;">
            🍽️ <strong>No Catering Partners Assigned:</strong> The administration has not designated catering partners to this venue yet. You may proceed with Venue Only.
          </div>
          <?php else: ?>
          <?php foreach ($packages as $pi => $pkg):
            $pkgId = (int)$pkg['id'];
            $isPop = ($pi === 1);
            $tierEmoji = ($pi === 0) ? '🥇 ' : (($pi === 1) ? '🏆 ' : '✨ ');
          ?>
          <input type="radio" class="pkg-radio" name="pkg" id="pkg-<?= $pkgId ?>">
          <label class="pkg-sel-card" for="pkg-<?= $pkgId ?>" <?= $isPop ? 'style="border-color:#059669;"' : '' ?>>
            <div class="flex-between mb-4">
              <div class="font-bold flex-center gap-6" style="flex-wrap:wrap;">
                <span><?= $tierEmoji . e($pkg['title']) ?></span>
                <span class="pill pill-pending" style="font-size:0.65rem; padding:2px 6px;">👨‍🍳 <?= e($pkg['business_name'] ?: ($pkg['caterer_name'] ?: 'Culinary Partner')) ?></span>
                <?= $isPop ? '<span class="stat-badge positive" style="font-size:.6rem;">MOST POPULAR</span>' : '' ?>
              </div>
              <span class="font-bold text-primary">+$<?= number_format($pkg['price_per_event'], 2) ?></span>
            </div>
            <div class="text-xs text-muted mb-4">Min <?= (int)$pkg['min_guests'] ?> guests</div>
            <div class="text-xs"><?= e($pkg['description'] ?? '') ?></div>
          </label>
          <?php endforeach; ?>
          <?php endif; ?>

          <?php if (!empty($assignedCatererIds)): ?>
          <input type="radio" class="pkg-radio" name="pkg" id="pkg-custom">
          <label class="pkg-sel-card" for="pkg-custom">
            <div class="flex-between mb-4">
              <div class="font-bold">✨ Bespoke Custom Hybrid Package</div>
              <span class="font-bold text-primary">Dynamic Pricing</span>
            </div>
            <div class="text-xs text-muted mb-4">Mix &amp; match dishes across assigned kitchen tiers</div>
            <div class="text-xs mb-4">Fully customizable courses &amp; staffing add-ons.</div>
            <a href="caterer-custom-package.php?venue_id=<?= $venueId ?>&caterer_id=<?= $assignedCatererIds[0] ?>" class="text-xs font-semibold text-primary">✨ Open Custom Package Builder →</a>
          </label>
          <?php endif; ?>
        </div>

        <div class="flex gap-12">
          <label for="ws1" class="btn btn-ghost" style="flex:1; cursor:pointer;">← Back</label>
          <label for="ws3" class="btn btn-primary" style="flex:2; cursor:pointer;">Review &amp; Proceed to Payment →</label>
        </div>
      </div>

      <div class="card" style="background:var(--gray-50);">
        <h3 class="mb-12">Running Total</h3>
        <div class="text-sm mb-8"><span class="text-muted">Venue (<?= e($venue['name']) ?>):</span> <span class="font-bold">$<?= number_format($venue['base_rate'], 2) ?></span></div>
        <div class="text-sm mb-8"><span class="text-muted">Catering Package:</span> <span class="font-bold">Included / Optional</span></div>
        <div class="text-sm mb-8"><span class="text-muted">Guest Count:</span> <span class="font-bold" id="bv-summary-guests">150</span></div>
        <div style="border-top:1px solid var(--gray-200); padding-top:12px; margin-top:8px;" class="flex-between">
          <span class="text-muted text-sm">Subtotal Est.</span>
          <span class="font-bold text-primary" style="font-size:1.1rem;">$<?= number_format($venue['base_rate'], 2) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- STEP 3: Review & Pay -->
  <div class="wizard-step-3">
    <div class="grid-2" style="gap:32px; align-items:start;">
      <div class="card">
        <h2 class="mb-4">Step 3 — Review &amp; Secure Payment</h2>
        <p class="mb-20">Confirm your booking details and complete payment.</p>

        <div class="card mb-20" style="background:var(--gray-50);">
          <div class="font-bold text-sm mb-12">Booking Confirmation Summary</div>
          <div class="flex-between text-sm mb-6"><span class="text-muted">Venue</span><span class="font-bold"><?= e($venue['name']) ?></span></div>
          <div class="flex-between text-sm mb-6"><span class="text-muted">Date &amp; Duration</span><span class="font-bold"><?= $defaultDateLabel ?> · 12h</span></div>
          <div class="flex-between text-sm mb-6"><span class="text-muted">Guests</span><span class="font-bold" id="bv-step3-guests">150 attendees</span></div>
          <div class="flex-between text-sm mb-6"><span class="text-muted">Catering</span><span class="font-bold">Selected Culinary Tier</span></div>
        </div>

        <div class="form-group">
          <label class="form-label">Cardholder Name</label>
          <input type="text" class="form-control" value="<?= e($currentUser['name'] ?? '') ?>" placeholder="e.g. <?= e($currentUser['name'] ?? 'Cardholder Name') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Card Number</label>
          <input type="text" class="form-control" value="•••• •••• •••• 4242">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expiry</label>
            <input type="text" class="form-control" value="12/28">
          </div>
          <div class="form-group">
            <label class="form-label">CVC</label>
            <input type="password" class="form-control" value="•••">
          </div>
        </div>

        <div class="flex gap-12 mt-16">
          <label for="ws2" class="btn btn-ghost" style="flex:1; cursor:pointer;">← Back</label>
          <a href="booking-summary-payment.php?venue_id=<?= $venueId ?>&date=<?= e($defaultEventDate) ?>&guests=150" id="bv-pay-link" class="btn btn-primary" style="flex:2;">Review &amp; Pay →</a>
        </div>
        <div class="text-xs text-center text-muted mt-8">🔒 Secured by Stripe · End-to-end encrypted</div>
      </div>

      <div class="card">
        <h3 class="mb-16">Final Cost Breakdown</h3>
        <div class="flex-between text-sm mb-8"><span class="text-muted"><?= e($venue['name']) ?></span><span>$<?= number_format($venue['base_rate'], 2) ?></span></div>
        <div class="flex-between text-sm mb-8"><span class="text-muted">Catering &amp; Logistics</span><span>Calculated at Checkout</span></div>
        <div class="flex-between" style="background:#eff6ff; padding:14px; border-radius:var(--radius-sm);">
          <span class="font-bold">BASE VENUE RATE</span>
          <span class="font-bold text-primary" style="font-size:1.5rem;">$<?= number_format($venue['base_rate'], 2) ?></span>
        </div>
        <div class="text-xs text-muted mt-8">Free cancellation within 48h. Refund in 5–10 business days.</div>
      </div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  const maxCap = <?= (int)$venue['capacity'] ?>;
  const initialGuests = parseInt(sessionStorage.getItem('vp_guest_count') || sessionStorage.getItem('vp_guests') || '150') || 150;
  let currentGuests = Math.min(Math.max(10, initialGuests), maxCap);

  const displayEl = document.getElementById('bv-guest-val');
  const sumEl = document.getElementById('bv-summary-guests');
  const step3El = document.getElementById('bv-step3-guests');
  const payLink = document.getElementById('bv-pay-link');
  const dateInp = document.getElementById('bv-event-date');

  function updateGuestDisplay() {
    if (displayEl) displayEl.textContent = currentGuests;
    if (sumEl) sumEl.textContent = currentGuests;
    if (step3El) step3El.textContent = currentGuests + ' attendees';
    sessionStorage.setItem('vp_guest_count', currentGuests.toString());
    sessionStorage.setItem('vp_guests', currentGuests.toString());

    if (payLink) {
      const d = dateInp && dateInp.value ? dateInp.value : '<?= e($defaultEventDate) ?>';
      payLink.href = `booking-summary-payment.php?venue_id=<?= $venueId ?>&date=${encodeURIComponent(d)}&guests=${currentGuests}&guest_count=${currentGuests}`;
    }
  }

  const minusBtn = document.getElementById('btn-guest-minus');
  const plusBtn = document.getElementById('btn-guest-plus');

  if (minusBtn) {
    minusBtn.addEventListener('click', function() {
      currentGuests = Math.max(10, currentGuests - 10);
      updateGuestDisplay();
    });
  }
  if (plusBtn) {
    plusBtn.addEventListener('click', function() {
      currentGuests = Math.min(maxCap, currentGuests + 10);
      updateGuestDisplay();
    });
  }
  if (dateInp) {
    dateInp.addEventListener('change', updateGuestDisplay);
  }

  updateGuestDisplay();
});
</script>
</body>
</html>