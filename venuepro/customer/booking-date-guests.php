<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$venueId = (int)($_GET['venue_id'] ?? 1);
$stmtVD = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtVD->execute([$venueId]);
$venueData = $stmtVD->fetch();
if (!$venueData) {
    // fallback to first venue
    $stmtVD2 = $db->query("SELECT * FROM venues WHERE status='active' LIMIT 1");
    $venueData = $stmtVD2->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Booking Step 1</title>
  <link rel="stylesheet" href="../css/style.css">
  
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Sidebar -->
    
    
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

    <!-- Main Content -->
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
          <div class="topbar-user">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        
<div class="steps">
  <div class="step active">
    <div class="step-circle">1</div>
    <div class="step-label">Venue & Logistics</div>
  </div>
  <div class="step-line"></div>
  <div class="step">
    <div class="step-circle">2</div>
    <div class="step-label">Catering</div>
  </div>
  <div class="step-line"></div>
  <div class="step">
    <div class="step-circle">3</div>
    <div class="step-label">Payment</div>
  </div>
</div>

<div class="grid-2" style="max-width:960px; margin:0 auto; gap:28px;">
<?php
$venueId = (int)($_GET['venue_id'] ?? 1);
$stmtVD = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtVD->execute([$venueId]);
$venueData = $stmtVD->fetch();
if (!$venueData) {
    $stmtVD2 = $db->query("SELECT * FROM venues WHERE status='active' LIMIT 1");
    $venueData = $stmtVD2->fetch();
    $venueId = $venueData ? (int)$venueData['id'] : 1;
}

$selectedPlan = strtolower(trim($_GET['plan'] ?? 'enterprise'));
if (!in_array($selectedPlan, ['essential', 'enterprise', 'elite'])) {
    $selectedPlan = 'enterprise';
}

$baseRate = (float)($venueData['base_rate'] ?? 2000);
$maxCapacity = (int)($venueData['max_capacity'] ?? 200);
$planPricing = [
    'essential'  => ['name' => 'Essential Half-Day', 'duration' => 6,  'rate' => round($baseRate * 0.5),  'hours' => '6 Hours',  'desc' => 'Standard floor access & basic AV rig'],
    'enterprise' => ['name' => 'Enterprise Full-Day', 'duration' => 12, 'rate' => round($baseRate),        'hours' => '12 Hours', 'desc' => 'Full day access, AV tech & manager'],
    'elite'      => ['name' => 'Elite Gala Extended', 'duration' => 24, 'rate' => round($baseRate * 1.75), 'hours' => '24 Hours', 'desc' => 'Full day/night access & VIP suite'],
];

$stockImages = [
  'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=300&q=80',
  'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=300&q=80',
  'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=300&q=80',
  'https://images.unsplash.com/photo-1520854221256-17451cc331bf?w=300&q=80',
  'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=300&q=80',
];
$summaryImg = !empty($venueData['image_url']) ? '../' . $venueData['image_url'] : $stockImages[$venueId % count($stockImages)];
?>

<div class="steps">
  <div class="step active">
    <div class="step-circle">1</div>
    <div class="step-label">Venue &amp; Plan</div>
  </div>
  <div class="step-line"></div>
  <div class="step">
    <div class="step-circle">2</div>
    <div class="step-label">Catering</div>
  </div>
  <div class="step-line"></div>
  <div class="step">
    <div class="step-circle">3</div>
    <div class="step-label">Payment</div>
  </div>
</div>

<!-- Plan Selection Cards -->
<div class="mb-24" style="max-width:960px; margin:0 auto 24px;">
  <div class="flex-between mb-12">
    <div>
      <h3 style="font-size:1.25rem; font-weight:800; margin:0 0 4px;">1. Select Venue Rental Plan</h3>
      <p style="margin:0; font-size:0.85rem; color:var(--gray-500);">Tier pricing dynamically loaded from property catalog for <?= e($venueData['name']) ?>.</p>
    </div>
  </div>
  <div class="grid-3 gap-16" id="plan-cards-container">
    <?php foreach ($planPricing as $key => $p):
      $isSel = ($key === $selectedPlan);
    ?>
    <div class="card plan-card <?= $isSel ? 'plan-card-selected' : '' ?>" data-plan="<?= $key ?>" data-rate="<?= $p['rate'] ?>" data-duration="<?= $p['duration'] ?>" data-name="<?= e($p['name']) ?>" style="cursor:pointer; border:2px solid <?= $isSel ? 'var(--primary)' : 'var(--gray-200)' ?>; background:<?= $isSel ? '#eff6ff' : '#fff' ?>; transition:all 0.2s ease; padding:18px;">
      <div class="flex-between mb-8">
        <span class="font-bold text-sm" style="color:var(--gray-900);"><?= strtoupper($key) ?></span>
        <?php if ($key === 'enterprise'): ?>
        <span class="pill pill-confirmed" style="font-size:0.65rem;">POPULAR</span>
        <?php endif; ?>
      </div>
      <div class="font-bold text-primary mb-4" style="font-size:1.4rem;">$<?= number_format($p['rate'], 0) ?></div>
      <div class="text-xs font-semibold text-muted mb-8">⏱ <?= e($p['hours']) ?> Access</div>
      <div class="text-xs text-muted"><?= e($p['desc']) ?></div>
      <div class="mt-12 text-xs font-bold" style="color:<?= $isSel ? 'var(--primary)' : 'var(--gray-400)' ?>;">
        <?= $isSel ? '● Selected Plan' : '○ Choose Plan' ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid-2" style="max-width:960px; margin:0 auto; gap:28px;">
  <div class="card">
    <h2 class="mb-4">2. Event Date &amp; Logistics</h2>
    <p class="mb-20 text-muted text-sm">Specify schedule and attendance for <strong><?= e($venueData['name']) ?></strong>.</p>

    <form id="booking-step1-form" action="booking-catering-packages.php" method="GET">
      <input type="hidden" name="venue_id" value="<?= $venueId ?>">
      <input type="hidden" name="plan" id="input-plan" value="<?= $selectedPlan ?>">
      <input type="hidden" name="plan_rate" id="input-plan-rate" value="<?= $planPricing[$selectedPlan]['rate'] ?>">
      <input type="hidden" name="duration_hours" id="input-duration" value="<?= $planPricing[$selectedPlan]['duration'] ?>">

      <div class="form-group">
        <label class="form-label">Event Date *</label>
        <input type="date" name="date" id="event-date-input" class="form-control" min="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Time *</label>
          <input type="time" name="start_time" id="start-time-input" class="form-control" value="10:00" required>
        </div>
        <div class="form-group">
          <label class="form-label">End Time *</label>
          <input type="time" name="end_time" id="end-time-input" class="form-control" value="22:00" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Expected Guest Count *</label>
        <input type="number" name="guests" id="guest-count-input" class="form-control" placeholder="e.g. 100" min="10" max="<?= $maxCapacity ?>" required>
        <small class="form-hint">Maximum verified venue capacity is <?= number_format($maxCapacity) ?> attendees.</small>
      </div>

      <div class="form-group">
        <label class="form-label">Event Format</label>
        <select name="format" class="form-control">
          <option>Corporate Summit / Conference</option>
          <option>Gala Dinner &amp; Awards Ceremony</option>
          <option>Product Launch &amp; Reception</option>
          <option>Private Celebration / Wedding</option>
        </select>
      </div>

      <div id="availability-status" style="margin-top:12px;"></div>

      <div class="flex gap-12 mt-24">
        <button type="button" id="btn-check-availability" class="btn btn-outline" style="flex:1;">Check Availability</button>
        <button type="submit" class="btn btn-primary" style="flex:2;">Continue to Catering →</button>
      </div>
    </form>
  </div>

  <div class="card" style="background:var(--gray-50); position:sticky; top:90px;">
    <h3 class="mb-16">Booking Summary</h3>
    <div class="flex gap-12 mb-16">
      <div style="width:70px; height:70px; border-radius:var(--radius-sm); background:url('<?= e($summaryImg) ?>') center/cover;"></div>
      <div>
        <div class="font-bold text-sm"><?= e($venueData['name']) ?></div>
        <div class="text-xs text-muted">📍 <?= e($venueData['district']) ?></div>
        <div class="text-xs font-semibold text-primary mt-4">$<?= number_format($baseRate, 2) ?> base / day</div>
      </div>
    </div>
    <div style="border-top:1px solid var(--gray-200); padding-top:14px;">
      <div class="flex-between text-sm mb-8">
        <span class="text-muted">Selected Tier</span>
        <strong id="summary-tier-name"><?= e($planPricing[$selectedPlan]['name']) ?></strong>
      </div>
      <div class="flex-between text-sm mb-8">
        <span class="text-muted">Rental Duration</span>
        <span class="font-semibold" id="summary-duration"><?= e($planPricing[$selectedPlan]['hours']) ?></span>
      </div>
      <div class="flex-between text-sm mb-8">
        <span class="text-muted">Venue Cost</span>
        <strong class="text-primary font-bold" id="summary-cost">$<?= number_format($planPricing[$selectedPlan]['rate'], 2) ?></strong>
      </div>
      <div class="flex-between text-sm mb-8">
        <span class="text-muted">Max Capacity</span>
        <span class="font-semibold"><?= number_format($maxCapacity) ?> Guests</span>
      </div>
    </div>
    <div class="mt-16 pt-12" style="border-top:1px dashed var(--gray-300);">
      <div class="text-xs text-muted">Next step: Browse and select gourmet catering packages or singular dining add-ons.</div>
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
// Store venue_id from URL for booking flow
var vpVenueId = new URLSearchParams(window.location.search).get('venue_id') || '<?= $venueId ?>';
sessionStorage.setItem('vp_venue_id', vpVenueId);

document.addEventListener('DOMContentLoaded', function() {
  // Plan card selection handler
  document.querySelectorAll('.plan-card').forEach(function(card) {
    card.addEventListener('click', function() {
      document.querySelectorAll('.plan-card').forEach(function(c) {
        c.style.border = '2px solid var(--gray-200)';
        c.style.background = '#fff';
        var label = c.querySelector('.mt-12');
        if (label) { label.textContent = '○ Choose Plan'; label.style.color = 'var(--gray-400)'; }
      });
      card.style.border = '2px solid var(--primary)';
      card.style.background = '#eff6ff';
      var label = card.querySelector('.mt-12');
      if (label) { label.textContent = '● Selected Plan'; label.style.color = 'var(--primary)'; }

      var planKey = card.dataset.plan;
      var planRate = parseFloat(card.dataset.rate);
      var planDuration = parseInt(card.dataset.duration);
      var planName = card.dataset.name;

      document.getElementById('input-plan').value = planKey;
      document.getElementById('input-plan-rate').value = planRate;
      document.getElementById('input-duration').value = planDuration;

      document.getElementById('summary-tier-name').textContent = planName;
      document.getElementById('summary-duration').textContent = planDuration + ' Hours';
      document.getElementById('summary-cost').textContent = '$' + planRate.toLocaleString('en-US', {minimumFractionDigits: 2});

      sessionStorage.setItem('vp_venue_plan', planKey);
      sessionStorage.setItem('vp_venue_cost', planRate);
    });
  });

  // Availability checking
  var checkBtn = document.getElementById('btn-check-availability');
  var dateInput = document.getElementById('event-date-input');
  var startTimeInput = document.getElementById('start-time-input');
  var endTimeInput = document.getElementById('end-time-input');
  var statusBox = document.getElementById('availability-status');

  if (checkBtn) {
    checkBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      var dateVal = dateInput ? dateInput.value : '';
      if (!dateVal) {
        statusBox.innerHTML = '<div style="background:#fee2e2; border:1px solid #f87171; border-radius:6px; padding:10px 14px; color:#991b1b; font-size:0.85rem; font-weight:600;">⚠️ Please select an event date first.</div>';
        dateInput.focus();
        return;
      }
      checkBtn.disabled = true;
      checkBtn.textContent = 'Checking...';
      try {
        var start = startTimeInput ? startTimeInput.value + ':00' : '10:00:00';
        var end = endTimeInput ? endTimeInput.value + ':00' : '22:00:00';
        var res = await fetch('../api/bookings.php?action=check_availability&venue_id=' + vpVenueId + '&date=' + dateVal + '&start_time=' + start + '&end_time=' + end);
        var d = await res.json();
        if (d.data && d.data.available) {
          statusBox.innerHTML = '<div style="background:#dcfce7; border:1px solid #86efac; border-radius:6px; padding:10px 14px; color:#166534; font-size:0.85rem; font-weight:600;">✓ ' + (d.message || 'Date and time slot is available for booking!') + '</div>';
          sessionStorage.setItem('vp_event_date', dateVal);
          sessionStorage.setItem('vp_start_time', start);
          sessionStorage.setItem('vp_end_time', end);
        } else {
          statusBox.innerHTML = '<div style="background:#fef3c7; border:1px solid #f59e0b; border-radius:6px; padding:10px 14px; color:#92400e; font-size:0.85rem; font-weight:600;">⚠️ ' + (d.message || 'Time-slot conflict detected for this date.') + '</div>';
        }
      } catch(err) {
        statusBox.innerHTML = '<div style="background:#fee2e2; border:1px solid #f87171; border-radius:6px; padding:10px 14px; color:#991b1b; font-size:0.85rem;">Server connection error.</div>';
      } finally {
        checkBtn.disabled = false;
        checkBtn.textContent = 'Check Availability';
      }
    });
  }

  // Form submission: save to sessionStorage and forward
  var form = document.getElementById('booking-step1-form');
  if (form) {
    form.addEventListener('submit', function(e) {
      if (dateInput && dateInput.value) {
        sessionStorage.setItem('vp_event_date', dateInput.value);
        sessionStorage.setItem('vp_guest_count', document.getElementById('guest-count-input').value);
        sessionStorage.setItem('vp_start_time', startTimeInput.value);
        sessionStorage.setItem('vp_end_time', endTimeInput.value);
      }
    });
  }
});
</script>
</body>
</html>