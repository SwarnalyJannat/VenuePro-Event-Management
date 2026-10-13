<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$venueId = (int)($_GET['venue_id'] ?? (session_id() ? ($_SESSION['vp_venue_id'] ?? 1) : 1));
$stmtV = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
$stmtV->execute([$venueId]);
$venue = $stmtV->fetch() ?: $db->query("SELECT * FROM venues ORDER BY id ASC LIMIT 1")->fetch();
$venueId = (int)$venue['id'];

$packageId = (int)($_GET['package_id'] ?? 0);
$package = null;
if ($packageId > 0) {
    $stmtP = $db->prepare("SELECT cp.*, u.name AS caterer_name, pr.business_name FROM catering_packages cp LEFT JOIN users u ON cp.caterer_id = u.id LEFT JOIN caterer_profiles pr ON u.id = pr.user_id WHERE cp.id = ? LIMIT 1");
    $stmtP->execute([$packageId]);
    $package = $stmtP->fetch();
}
$isCustom = isset($_GET['custom_package']) && $_GET['custom_package'] == '1';

// Determine assigned caterers for this venue
$venueName = $venue['name'] ?? '';
$venueIdStr = (string)$venueId;

$stmtAssigned = $db->prepare("
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
$stmtAssigned->execute([$venueName, $venueIdStr, $venueIdStr, $venueName, "%$venueName%"]);
$assignedCaterers = $stmtAssigned ? $stmtAssigned->fetchAll(PDO::FETCH_ASSOC) : [];
$assignedCatererIds = array_map('intval', array_column($assignedCaterers, 'id'));

// Determine caterer strictly (from chosen package, explicit parameter, or assigned caterers for this venue)
$catererId = (int)($_GET['caterer_id'] ?? ($package['caterer_id'] ?? 0));
if ($catererId > 0 && !in_array($catererId, $assignedCatererIds, true)) {
    // If passed caterer is not assigned to this venue, fallback to venue's assigned caterer
    $catererId = !empty($assignedCatererIds) ? $assignedCatererIds[0] : 0;
}
if ($catererId <= 0 && !empty($assignedCatererIds)) {
    $catererId = $assignedCatererIds[0];
}

$catererInfo = null;
$singularItems = [];
$catererBizTitle = 'No Catering Selected';

if ($catererId > 0) {
    $stmtCatInfo = $db->prepare("SELECT cp.*, u.name as caterer_name FROM caterer_profiles cp JOIN users u ON cp.user_id = u.id WHERE u.id = ? LIMIT 1");
    $stmtCatInfo->execute([$catererId]);
    $catererInfo = $stmtCatInfo->fetch();
    $catererBizTitle = $catererInfo['business_name'] ?? ($package['business_name'] ?? ($package['caterer_name'] ?? 'Partner Catering Kitchen'));

    // Dynamic singular menu items strictly scoped to this caterer
    $stmtSingular = $db->prepare("SELECT * FROM singular_menu_items WHERE caterer_id = ? AND status = 'active' ORDER BY category ASC, name ASC");
    $stmtSingular->execute([$catererId]);
    $singularItems = $stmtSingular->fetchAll(PDO::FETCH_ASSOC);
}

// Schedule parameters from query or defaults
$selectedDate = sanitize($_GET['date'] ?? ($_GET['event_date'] ?? ''));
$selectedStartTime = sanitize($_GET['start_time'] ?? '10:00:00');
$selectedEndTime   = sanitize($_GET['end_time'] ?? '22:00:00');
$selectedGuests    = (int)($_GET['guests'] ?? ($_GET['guest_count'] ?? 0));
if ($selectedGuests <= 0 && !empty($_SESSION['vp_guests'])) {
    $selectedGuests = (int)$_SESSION['vp_guests'];
}
$selectedDuration  = (int)($_GET['duration_hours'] ?? 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Booking Step 3: Payment &amp; Singular Add-On Items</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Add-On Items Selector */
    .addon-card {
      border: 1.5px solid var(--gray-200);
      background: #fff;
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .addon-item {
      display: flex; align-items: center; gap: 14px;
      padding: 12px 16px; border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm); background: #fff;
      transition: all 0.2s; margin-bottom: 10px;
    }
    .addon-item:hover { border-color: var(--primary); background: #f8fafc; }
    .addon-item.selected { border-color: var(--primary); background: #eff6ff; }
    .addon-img {
      width: 52px; height: 52px; border-radius: 8px;
      background: #f1f5f9; display: flex; align-items: center;
      justify-content: center; font-size: 1.8rem; flex-shrink: 0;
      overflow: hidden;
    }
    .addon-info { flex: 1; }
    .addon-name { font-weight: 700; font-size: 0.875rem; color: var(--gray-900); }
    .addon-desc { font-size: 0.75rem; color: var(--gray-500); margin-top: 2px; }
    .addon-price { font-weight: 800; font-size: 0.9rem; color: var(--primary); white-space: nowrap; }
    .addon-qty {
      display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    }
    .qty-btn {
      width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--gray-300);
      background: #fff; font-size: 1rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.15s; line-height: 1;
    }
    .qty-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .qty-input {
      width: 44px; text-align: center; border: 1.5px solid var(--gray-200);
      border-radius: 6px; padding: 4px; font-weight: 700;
      font-size: 0.85rem; background: #fff;
    }

    .selected-addons-list { display: flex; flex-direction: column; gap: 6px; }
    .selected-addon-row {
      display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--gray-700);
    }

    .total-box {
      background: #eff6ff; padding: 16px 18px; border-radius: var(--radius-sm);
      margin-top: 16px;
    }
    .decision-radio-group {
      display: flex; gap: 12px; margin-bottom: 16px;
    }
    .decision-radio-label {
      flex: 1; padding: 12px 14px; border: 1.5px solid var(--gray-300);
      border-radius: var(--radius-sm); cursor: pointer; text-align: center;
      font-weight: 600; font-size: 0.85rem; transition: all 0.15s;
    }
    .decision-radio-label.active {
      border-color: var(--primary); background: #eff6ff; color: var(--primary);
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Customer Sidebar -->
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub">Customer Portal</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Switch Role / Logout
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
        <!-- Booking Steps -->
        <div class="steps">
          <div class="step done"><div class="step-circle">✓</div><div class="step-label">Venue</div></div>
          <div class="step-line done"></div>
          <div class="step done"><div class="step-circle">✓</div><div class="step-label">Logistics &amp; Package</div></div>
          <div class="step-line done"></div>
          <div class="step active"><div class="step-circle">3</div><div class="step-label">Payment &amp; Add-Ons</div></div>
        </div>

        <div style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:1.2fr 1fr; gap:28px; align-items:start;">

          <!-- LEFT COLUMN: Singular Items Add-On Card FIRST, then Payment -->
          <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- ===== ADD-ON SINGULAR ITEMS CARD (Beside the package) ===== -->
            <?php if ($catererId > 0 && !empty($singularItems)): ?>
            <div class="addon-card">
              <div class="flex-between mb-12">
                <div class="flex-center gap-8">
                  <div style="width:38px; height:38px; border-radius:50%; background:#eff6ff; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">🍽️</div>
                  <div>
                    <h2 style="font-size:1.25rem; font-weight:800; margin:0;">Singular Add-On Items</h2>
                    <p class="text-xs text-muted" style="margin:2px 0 0;">Curated by <strong><?= e($catererBizTitle) ?></strong> for your event</p>
                  </div>
                </div>
                <span class="stat-badge positive" style="font-size:0.75rem;">👨‍🍳 <?= e($catererBizTitle) ?></span>
              </div>

              <div class="decision-radio-group">
                <label class="decision-radio-label active" id="tab-addons-yes" onclick="toggleAddonChoice(true)">
                  ✓ Yes, add singular items
                </label>
                <label class="decision-radio-label" id="tab-addons-no" onclick="toggleAddonChoice(false)">
                  ✕ No, package only
                </label>
              </div>

              <div id="addon-selection-body">
                <div class="text-xs text-muted mb-16">
                  Select any singular items you need and adjust quantities. Your caterer (<strong><?= e($catererBizTitle) ?></strong>) will receive these along with your package order:
                </div>

                <!-- Dynamic Singular Menu Add-Ons from Database -->
                <?php if (empty($singularItems)): ?>
                <div class="text-xs text-muted" style="padding:16px; text-align:center;">No singular add-on items currently offered by <?= e($catererBizTitle) ?>.</div>
                <?php else: foreach ($singularItems as $si):
                  $itemId = (int)$si['id'];
                  $price = (float)$si['price'];
                  $emoji = !empty($si['emoji']) ? $si['emoji'] : '🍽️';
                  $unitLabel = !empty($si['unit_label']) ? ('/' . $si['unit_label']) : '';
                  $maxQty = max(10, min(200, (int)($si['quantity_available'] ?: 50)));
                  $dietary = !empty($si['dietary_tags']) ? (' · ' . $si['dietary_tags']) : '';
                ?>
                <div class="addon-item" id="addon-<?= $itemId ?>">
                  <div class="addon-img">
                    <?php if (!empty($si['image_url'])): ?>
                    <img src="<?= e($si['image_url']) ?>" alt="<?= e($si['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                    <?= $emoji ?>
                    <?php endif; ?>
                  </div>
                  <div class="addon-info">
                    <div class="addon-name"><?= e($si['name']) ?></div>
                    <div class="addon-desc"><?= e($si['description'] ?? '') ?><?= e($dietary) ?></div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty(<?= $itemId ?>, -1)">−</button>
                    <input class="qty-input" type="number" id="qty-<?= $itemId ?>" value="0" min="0" max="<?= $maxQty ?>" data-item-id="<?= $itemId ?>" data-price="<?= number_format($price, 2, '.', '') ?>" data-name="<?= e($si['name']) ?>" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty(<?= $itemId ?>, 1)">+</button>
                  </div>
                  <div class="addon-price">$<?= number_format($price, 2) ?><?= $unitLabel ?></div>
                </div>
                <?php endforeach; endif; ?>

                <!-- Add-On Subtotal Preview -->
                <div id="addon-subtotal-box" style="display:none; margin-top:16px; padding:14px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:var(--radius-sm);">
                  <div class="font-bold text-sm mb-8" style="color:#166534;">🛒 Your Selected Add-On Items:</div>
                  <div id="addon-lines" class="selected-addons-list"></div>
                  <div class="flex-between pt-8" style="border-top:1px dashed #86efac; margin-top:8px;">
                    <span class="font-bold text-sm" style="color:#166534;">Add-Ons Subtotal</span>
                    <span class="font-bold text-sm" style="color:#166534;" id="addon-subtotal-val">$0.00</span>
                  </div>
                </div>
              </div>
            </div>
            <?php elseif ($catererId <= 0): ?>
            <div class="card" style="background:#f8fafc; border:1px dashed var(--gray-300); text-align:center; padding:20px;">
              <div style="font-size:1.8rem; margin-bottom:6px;">🏛️</div>
              <div class="font-bold text-sm">Venue-Only Booking (No In-House Catering)</div>
              <div class="text-xs text-muted mt-4">You are booking <strong><?= e($venue['name']) ?></strong> without in-house catering. You can coordinate your own culinary arrangements.</div>
            </div>
            <?php endif; ?>

            <!-- Payment Method Card -->
            <div class="card">
              <div class="flex-between mb-4">
                <h2>Payment Method</h2>
                <span class="stat-badge positive">🛡️ SECURE SSL</span>
              </div>
              <p class="mb-24">Finalize your booking and catering transaction securely.</p>

              <form action="booking-success.php" id="payment-form">
                <div class="form-group">
                  <label class="form-label">Cardholder Name</label>
                  <input type="text" class="form-control" value="<?= e($currentUser['name'] ?? '') ?>" placeholder="e.g. <?= e($currentUser['name'] ?? 'Alex Thompson') ?>" required>
                </div>

                <div class="form-group">
                  <label class="form-label">Card Number</label>
                  <div class="input-wrap">
                    <input type="text" class="form-control" value="•••• •••• •••• 4242" placeholder="0000 0000 0000 0000" required>
                    <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:20px;">💳</span>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Expiration Date</label>
                    <input type="text" class="form-control" value="12/28" placeholder="MM / YY" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">CVC</label>
                    <input type="password" class="form-control" value="•••" placeholder="•••" required>
                  </div>
                </div>

                <div class="form-group flex-center gap-8 mb-24">
                  <input type="checkbox" id="savecard" checked>
                  <label for="savecard" class="text-xs text-muted">Save card details for faster checkout. Encrypted according to PCI DSS standards.</label>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg mb-16">Pay Now →</button>
                <div class="flex-center justify-center gap-8 text-xs text-muted">
                  <span>🔒 End-to-end encryption</span>
                  <span>•</span>
                  <span>Verified by Stripe</span>
                </div>
              </form>
            </div>

          </div>

          <!-- RIGHT COLUMN: Booking Summary (Updates dynamically) -->
          <div class="card" style="position:sticky; top:80px;">
            <h3 class="mb-8">Booking Summary</h3>
            <div class="text-xs text-muted mb-16" id="summary-datetime-display">📅 <span id="summary-date-val"><?= !empty($selectedDate) ? date('F j, Y', strtotime($selectedDate)) : 'December 14, 2026' ?></span> • <span id="summary-time-val"><?= e($selectedStartTime) ?> - <?= e($selectedEndTime) ?></span></div>

            <!-- Venue -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold" id="summary-venue-name"><?= e($venue['name'] ?? 'The Glass House – Grand Hall') ?></div>
                  <div class="text-xs text-muted" id="summary-venue-type"><?= e($venue['venue_type'] ?? 'Ballroom') ?> · Max Capacity: <?= (int)($venue['capacity'] ?? 250) ?> Guests</div>
                  <div class="text-xs font-semibold" style="color:var(--primary); margin-top:2px;" id="summary-attendees-display">👥 <span id="summary-guests-count-val"><?= $selectedGuests ?: '100' ?></span> Attendees Expected</div>
                </div>
                <div class="font-semibold" id="summary-venue-rate">$<?= number_format((float)($venue['base_rate'] ?? 2400), 2) ?></div>
              </div>
            </div>

            <!-- Catering Package (Main Package or Custom Hybrid) -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold" style="color:var(--primary);" id="summary-pkg-title">Catering: <?= $package ? e($package['title']) : 'Custom Hybrid Package' ?></div>
                  <div class="text-xs text-muted" id="summary-pkg-desc"><?= $package ? ('Min ' . (int)$package['min_guests'] . ' Guests · Standard Tier') : 'Multi-tier selection across Gold, Platinum & Signature' ?></div>
                </div>
                <div class="font-semibold" id="summary-pkg-price">$<?= number_format((float)($package['price_per_event'] ?? 1850), 2) ?></div>
              </div>
              <div id="summary-custom-dishes-box" style="display:none; margin-top:8px; padding:10px 12px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <div class="font-bold text-xs" style="color:#166534; margin-bottom:4px;">🍽️ Custom Selected Dishes:</div>
                <div id="summary-custom-dishes-list" style="display:flex; flex-direction:column; gap:3px; font-size:0.75rem; color:#14532d;"></div>
              </div>
            </div>

            <!-- Professional Staffing -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold">Professional Staffing</div>
                  <div class="text-xs text-muted">4 Waitstaff • 2 Bartenders • 1 Coordinator</div>
                </div>
                <div class="font-semibold" id="summary-staffing-rate">$640.00</div>
              </div>
            </div>

            <!-- Add-On Singular Items (Dynamically displayed) -->
            <div id="summary-addon-section" style="display:none; border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="font-bold text-sm mb-8" style="color:#059669;">Selected Singular Add-On Items</div>
              <div id="summary-addon-lines" style="display:flex; flex-direction:column; gap:4px;"></div>
              <div class="flex-between text-sm mt-8">
                <span class="text-muted font-semibold">Add-Ons Subtotal</span>
                <span class="font-bold" id="summary-addon-total" style="color:#059669;">$0.00</span>
              </div>
            </div>

            <!-- Subtotal & Calculations -->
            <div style="border-bottom:1px solid var(--gray-200); padding-bottom:14px; margin-bottom:16px;">
              <div class="flex-between text-sm mb-4">
                <span class="text-muted">Package &amp; Venue Base</span>
                <span id="summary-base-display">$4,890.00</span>
              </div>
              <div class="flex-between text-sm mb-4" id="summary-row-addons" style="display:none;">
                <span class="text-muted">Singular Items</span>
                <span id="summary-row-addons-val" style="color:#059669; font-weight:700;">$0.00</span>
              </div>
              <div class="flex-between text-sm mb-4">
                <span class="text-muted">Service Fee (10%)</span>
                <span id="fee-display">$489.00</span>
              </div>
              <div class="flex-between text-sm">
                <span class="text-muted">VAT (8%)</span>
                <span id="vat-display">$430.32</span>
              </div>
            </div>

            <!-- TOTAL AMOUNT -->
            <div class="total-box">
              <div class="flex-between">
                <div>
                  <div class="text-xs text-muted font-bold">TOTAL PAYMENT</div>
                  <div class="text-xs text-primary">USD Currency • dynamically summed</div>
                </div>
                <div style="font-size:1.85rem; font-weight:900; color:var(--primary);" id="grand-total">$5,809.32</div>
              </div>
            </div>

            <div class="card mt-16" style="background:#fefce8; border:1px solid #fef08a; padding:12px; margin-top:16px;">
              <div class="text-xs" style="color:#854d0e;">ℹ️ Free cancellation until 48 hours before the event. Refund processed in 5-10 business days.</div>
            </div>
          </div>

        </div><!-- /grid -->
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

  <script>
    const VENUE_BASE = <?= (float)($venue['base_rate'] ?? 2400) ?>;
    let packageCost = <?= $package ? (float)$package['price_per_event'] : 1850 ?>;
    const STAFFING_COST = 640;
    const SERVICE_RATE = 0.10;
    const VAT_RATE = 0.08;

    const ITEMS = <?= json_encode(array_map(function($it) {
      return [
        'id'    => (int)$it['id'],
        'price' => (float)$it['price'],
        'name'  => $it['name']
      ];
    }, $singularItems)) ?>;

    // Sync booking date/time from sessionStorage if available
    const savedEventDate = sessionStorage.getItem('vp_event_date') || sessionStorage.getItem('vp_date');
    if (savedEventDate) {
      const parts = savedEventDate.split('-');
      if (parts.length === 3) {
        const dObj = new Date(parts[0], parts[1] - 1, parts[2]);
        const dVal = document.getElementById('summary-date-val');
        if (dVal && !isNaN(dObj.getTime())) {
          dVal.textContent = dObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
        }
      }
    }
    const savedStart = sessionStorage.getItem('vp_start_time');
    const savedEnd   = sessionStorage.getItem('vp_end_time');
    if (savedStart && savedEnd) {
      const tVal = document.getElementById('summary-time-val');
      if (tVal) tVal.textContent = savedStart.substring(0, 5) + ' - ' + savedEnd.substring(0, 5);
    }

    // Sync guest count and display from sessionStorage or initial value
    const savedGuests = sessionStorage.getItem('vp_guest_count') || sessionStorage.getItem('vp_guests') || '<?= $selectedGuests ?: 100 ?>';
    const guestCountEl = document.getElementById('summary-guests-count-val');
    if (guestCountEl) guestCountEl.textContent = savedGuests;

    // Check if custom package in sessionStorage
    const isCustomPkg = sessionStorage.getItem('vp_is_custom_package') === '1' || (new URLSearchParams(window.location.search).get('custom_package') === '1');
    if (isCustomPkg) {
      const customTotal = parseFloat(sessionStorage.getItem('vp_custom_total') || '0');
      if (customTotal > 0) packageCost = customTotal;

      const titleEl = document.getElementById('summary-pkg-title');
      const descEl = document.getElementById('summary-pkg-desc');
      const priceEl = document.getElementById('summary-pkg-price');
      const customBox = document.getElementById('summary-custom-dishes-box');
      const customList = document.getElementById('summary-custom-dishes-list');

      if (titleEl) titleEl.textContent = 'Catering: ' + (sessionStorage.getItem('vp_custom_title') || 'Custom Hybrid Package');
      if (descEl) descEl.textContent = savedGuests + ' Guests · Multi-Tier Dish Selection';
      if (priceEl) priceEl.textContent = '$' + packageCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      try {
        const dishes = JSON.parse(sessionStorage.getItem('vp_custom_dishes') || '[]');
        if (dishes.length > 0 && customBox && customList) {
          customBox.style.display = 'block';
          customList.innerHTML = dishes.map(d => `
            <div class="flex-between" style="border-bottom:1px dashed #bbf7d0; padding-bottom:3px; margin-bottom:3px;">
              <span>• <strong>${d.name}</strong> <small style="color:#059669; font-weight:700;">[From ${d.tier}]</small></span>
              <span style="font-weight:700;">$${parseFloat(d.price).toFixed(2)}/head</span>
            </div>
          `).join('');
        }
      } catch (e) {}
    }

    function toggleAddonChoice(enable) {
      const yesBtn = document.getElementById('tab-addons-yes');
      const noBtn = document.getElementById('tab-addons-no');
      const body = document.getElementById('addon-selection-body');

      if (enable) {
        yesBtn.classList.add('active');
        noBtn.classList.remove('active');
        body.style.display = 'block';
      } else {
        noBtn.classList.add('active');
        yesBtn.classList.remove('active');
        body.style.display = 'none';
        // Zero all quantities
        ITEMS.forEach(item => {
          const el = document.getElementById('qty-' + item.id);
          if (el) el.value = 0;
        });
        recalcTotal();
      }
    }

    function changeQty(id, delta) {
      const el = document.getElementById('qty-' + id);
      const cur = parseInt(el.value) || 0;
      const max = parseInt(el.max) || 999;
      el.value = Math.max(0, Math.min(cur + delta, max));
      recalcTotal();
    }

    function recalcTotal() {
      let addonTotal = 0;
      let addonLines = [];
      let summaryLines = [];

      ITEMS.forEach(function(item) {
        const el = document.getElementById('qty-' + item.id);
        if (!el) return;
        const qty = parseInt(el.value) || 0;
        if (qty > 0) {
          const sub = qty * item.price;
          addonTotal += sub;
          const name = el.getAttribute('data-name');
          addonLines.push('<div class="selected-addon-row"><span>' + name + ' × ' + qty + '</span><span>$' + sub.toFixed(2) + '</span></div>');
          summaryLines.push('<div class="flex-between text-xs text-muted"><span>' + name + ' × ' + qty + '</span><span>$' + sub.toFixed(2) + '</span></div>');
          // Highlight selected item card
          const card = document.getElementById('addon-' + item.id);
          if (card) card.classList.add('selected');
        } else {
          const card = document.getElementById('addon-' + item.id);
          if (card) card.classList.remove('selected');
        }
      });

      // Update add-on subtotal preview box inside the card
      const addonBox = document.getElementById('addon-subtotal-box');
      const addonLinesEl = document.getElementById('addon-lines');
      const addonSubVal = document.getElementById('addon-subtotal-val');
      addonBox.style.display = addonTotal > 0 ? 'block' : 'none';
      addonLinesEl.innerHTML = addonLines.join('');
      addonSubVal.textContent = '$' + addonTotal.toFixed(2);

      // Update right-hand summary panel
      const summarySection = document.getElementById('summary-addon-section');
      const summaryAddonLines = document.getElementById('summary-addon-lines');
      const summaryAddonTotal = document.getElementById('summary-addon-total');
      const summaryRowAddons = document.getElementById('summary-row-addons');
      const summaryRowAddonsVal = document.getElementById('summary-row-addons-val');
      summarySection.style.display = addonTotal > 0 ? 'block' : 'none';
      summaryRowAddons.style.display = addonTotal > 0 ? 'flex' : 'none';
      summaryAddonLines.innerHTML = summaryLines.join('');
      summaryAddonTotal.textContent = '$' + addonTotal.toFixed(2);
      summaryRowAddonsVal.textContent = '$' + addonTotal.toFixed(2);

      // Recalculate dynamic grand total
      const baseSubtotal = VENUE_BASE + packageCost + STAFFING_COST;
      const baseDisplay = document.getElementById('summary-base-display');
      if (baseDisplay) baseDisplay.textContent = '$' + baseSubtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

      const subtotal = baseSubtotal + addonTotal;
      const fee = subtotal * SERVICE_RATE;
      const vat = subtotal * VAT_RATE;
      const grand = subtotal + fee + vat;

      document.getElementById('fee-display').textContent = '$' + fee.toFixed(2);
      document.getElementById('vat-display').textContent = '$' + vat.toFixed(2);
      document.getElementById('grand-total').textContent = '$' + grand.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    document.addEventListener('DOMContentLoaded', recalcTotal);
  </script>
<script src="../js/app.js"></script>
<script>
// Read booking params from sessionStorage (set during venue/date selection flow)
var vpVenueId   = sessionStorage.getItem('vp_venue_id')   || '<?= $venueId ?>';
var vpPackageId = sessionStorage.getItem('vp_package_id') || '<?= $packageId ?>';
var vpEventDate = sessionStorage.getItem('vp_event_date') || sessionStorage.getItem('vp_date') || '<?= e($selectedDate) ?>';
var vpGuests    = sessionStorage.getItem('vp_guest_count') || sessionStorage.getItem('vp_guests') || '<?= $selectedGuests ?: 100 ?>';
var vpStart     = sessionStorage.getItem('vp_start_time') || '<?= e($selectedStartTime) ?>' || '10:00:00';
var vpEnd       = sessionStorage.getItem('vp_end_time')   || '<?= e($selectedEndTime) ?>'   || '22:00:00';
var vpDuration  = sessionStorage.getItem('vp_duration_hours') || '<?= $selectedDuration ?: 4 ?>';

document.addEventListener('DOMContentLoaded', function() {
  var gEl = document.getElementById('summary-guests-count-val');
  if (gEl && vpGuests) gEl.textContent = vpGuests;

  // Inject hidden fields into payment form
  var pf = document.getElementById('payment-form');
  if (pf) {
    function addHidden(name, val) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = name; h.value = val;
      pf.appendChild(h);
    }
    addHidden('venue_id',       vpVenueId);
    addHidden('package_id',     vpPackageId);
    addHidden('caterer_id',     '<?= $catererId ?>');
    addHidden('event_date',     vpEventDate);
    addHidden('start_time',     vpStart);
    addHidden('end_time',       vpEnd);
    addHidden('guest_count',    vpGuests);
    addHidden('guests',         vpGuests);
    addHidden('event_name',     'Event at ' + (document.getElementById('summary-venue-name')?.textContent || ('Venue #' + vpVenueId)));
    addHidden('duration_hours', vpDuration);
  }

  // Clear session on successful booking
  window.addEventListener('vpBookingComplete', function() {
    sessionStorage.removeItem('vp_venue_id');
    sessionStorage.removeItem('vp_package_id');
    sessionStorage.removeItem('vp_event_date');
    sessionStorage.removeItem('vp_date');
    sessionStorage.removeItem('vp_guests');
    sessionStorage.removeItem('vp_guest_count');
  });
});
</script>
</body>
</html>
