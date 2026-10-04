<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();
?>
<?php
$catUser = $currentUser;
$orderId = (int)($_GET['order_id'] ?? 0);
$orderDetail = null; $singularItems = [];

// Fetch all pending/in-progress orders for this caterer (for selector)
$stmtOrds = $db->prepare(
    "SELECT co.*, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.total_amount,
     v.name AS venue_name, cp.title AS package_title, cp.price_per_event AS package_cost,
     u.name AS customer_name, u.email AS customer_email
     FROM caterer_orders co
     JOIN bookings b ON co.booking_id = b.id
     JOIN venues v ON b.venue_id = v.id
     LEFT JOIN catering_packages cp ON b.package_id = cp.id
     JOIN users u ON b.customer_id = u.id
     WHERE co.caterer_id = ?
     ORDER BY b.event_date ASC"
);
$stmtOrds->execute([$catUser['id']]);
$allOrders = $stmtOrds->fetchAll();

if ($orderId > 0) {
    foreach ($allOrders as $o) {
        if ((int)$o['id'] === $orderId) { $orderDetail = $o; break; }
    }
}
if (!$orderDetail && !empty($allOrders)) {
    $orderDetail = $allOrders[0];
    $orderId = (int)$orderDetail['id'];
}
$jsOrderStore = [];
foreach ($allOrders as $ord) {
    $code = $ord['order_code'];
    $stmtItems = $db->prepare("SELECT * FROM booking_singular_items WHERE booking_id = ?");
    $stmtItems->execute([$ord['booking_id']]);
    $bItems = $stmtItems->fetchAll();

    $singItems = [];
    foreach ($bItems as $bi) {
        $singItems[] = [
            'id' => (int)$bi['id'],
            'name' => $bi['item_name'],
            'emoji' => $bi['emoji'] ?? '🍽️',
            'diet' => $bi['dietary_tag'] ?? '',
            'qty' => (int)$bi['quantity'],
            'unitPrice' => (float)$bi['unit_price'],
            'note' => $bi['notes'] ?? '',
            'status' => $bi['item_status'] ?? 'Pending'
        ];
    }

    $jsOrderStore[$code] = [
        'id' => '#' . $code,
        'orderDbId' => (int)$ord['id'],
        'bookingId' => (int)$ord['booking_id'],
        'event' => $ord['event_name'],
        'venue' => $ord['venue_name'],
        'date' => date('M j, Y', strtotime($ord['event_date'])),
        'time' => substr($ord['start_time'],0,5) . ' – ' . substr($ord['end_time'],0,5),
        'guests' => $ord['guest_count'] . ' Guests',
        'package' => $ord['package_title'] ?? 'Custom Package',
        'coversText' => ($ord['package_title'] ?? 'Custom Package') . ' — ' . $ord['covers_count'] . ' covers',
        'status' => $ord['preparation_status'] ?? 'Preparing',
        'total' => 'Total: $' . number_format($ord['total_amount'], 2),
        'packageDecision' => $ord['package_decision'] ?? 'Pending',
        'packagePrice' => '$' . number_format($ord['package_cost'] ?? 1200, 2),
        'packageDesc' => 'Package dining service and presentation for ' . $ord['guest_count'] . ' guests.',
        'singularItems' => $singItems
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Caterer – Order Details &amp; Item Approvals</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .order-select-card {
      border: 2px solid var(--gray-200);
      background: #fff;
      border-radius: var(--radius);
      padding: 14px 18px;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: left;
    }
    .order-select-card:hover {
      border-color: #059669;
      box-shadow: var(--shadow-sm);
    }
    .order-select-card.active {
      border-color: #059669;
      background: #f0fdf4;
    }
    .order-header-card {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #fff;
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 16px;
    }
    .order-header-id {
      font-size: 1.8rem;
      font-weight: 800;
      color: #6ee7b7;
      margin-bottom: 8px;
    }
    .order-header-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 0.85rem;
      color: #cbd5e1;
    }
    .order-meta-item strong {
      color: #fff;
    }
    .diet-tag {
      display: inline-block;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      margin-top: 3px;
    }
    .diet-gf { background: #eff6ff; color: #2563eb; }
    .diet-veg { background: #dcfce7; color: #16a34a; }

    /* Decision Cards & Action Buttons */
    .decision-card {
      border-radius: var(--radius);
      border: 1.5px solid var(--gray-200);
      background: #fff;
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .decision-card.accepted {
      border-color: #86efac;
      background: #f0fdf4;
    }
    .decision-card.rejected {
      border-color: #fca5a5;
      background: #fef2f2;
    }

    /* Singular Item Table Rows */
    .si-row {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 14px 18px;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm);
      background: #fff;
      margin-bottom: 12px;
      transition: all 0.2s ease;
    }
    .si-row:hover {
      border-color: #cbd5e1;
    }
    .si-row.accepted {
      border-color: #86efac;
      background: #f0fdf4;
    }
    .si-row.rejected {
      border-color: #fca5a5;
      background: #fef2f2;
      opacity: 0.85;
    }
    .si-emoji {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: #f8fafc;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      flex-shrink: 0;
      border: 1px solid var(--gray-200);
    }
    .si-info {
      flex: 1;
    }
    .si-name {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--gray-900);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .si-meta {
      font-size: 0.78rem;
      color: var(--gray-500);
      margin-top: 3px;
    }
    .si-price-col {
      text-align: right;
      padding-right: 12px;
      flex-shrink: 0;
    }
    .si-total {
      font-weight: 800;
      font-size: 1.05rem;
      color: var(--primary);
    }
    .si-unit {
      font-size: 0.75rem;
      color: var(--gray-500);
    }
    .si-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }

    .btn-accept {
      background: #10b981;
      color: #fff;
      border: none;
      font-weight: 700;
      padding: 7px 14px;
      border-radius: 6px;
      font-size: 0.8rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.15s;
    }
    .btn-accept:hover {
      background: #059669;
    }
    .btn-accept.active {
      background: #059669;
      box-shadow: 0 0 0 2px #6ee7b7;
    }

    .btn-reject {
      background: #fff;
      color: #dc2626;
      border: 1.5px solid #fca5a5;
      font-weight: 700;
      padding: 7px 14px;
      border-radius: 6px;
      font-size: 0.8rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.15s;
    }
    .btn-reject:hover {
      background: #fee2e2;
    }
    .btn-reject.active {
      background: #dc2626;
      color: #fff;
      border-color: #dc2626;
    }

    .btn-batch {
      font-size: 0.78rem;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    <!-- Caterer Sidebar -->
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub" style="color:#10b981;">Kitchen Operations</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          Package Library
        </a>
        <a href="caterer-menu-items.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
          Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          Create Package
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Log out
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="caterer-dashboard.php">Kitchen Overview</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Order Inspection &amp; Item Approvals</span>
        </div>

        <div class="flex-between mb-20">
          <div>
            <h1 style="font-size:1.85rem; font-weight:800; margin-bottom:4px;">Order Approvals &amp; Specifications</h1>
            <!-- <p>Review incoming customer orders. Accept or reject catering packages and singular add-on items individually.</p> -->
          </div>
          <a href="caterer-dashboard.php" class="btn btn-ghost btn-sm">← Back to Overview</a>
        </div>

        <!-- Order Number List Selector -->
        <div class="card mb-24" style="padding:18px;">
          <div class="text-xs font-bold text-muted mb-12" style="letter-spacing:0.05em; text-transform:uppercase;">
            Active Orders (Click any order to load specifications and accept/reject requests):
          </div>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
            <?php if (empty($allOrders)): ?>
            <div style="grid-column:1/-1; padding:20px; text-align:center; color:var(--gray-400);">No orders assigned to your kitchen at this time.</div>
            <?php else: foreach ($allOrders as $idx => $ord): 
              $code = $ord['order_code'];
              $isFirst = $idx === 0;
            ?>
            <div id="ocard-<?= e($code) ?>" class="order-select-card <?= $isFirst ? 'active' : '' ?>" onclick="loadOrder('<?= e($code) ?>')">
              <div class="flex-between mb-2">
                <span class="font-bold text-primary" style="font-size:1.05rem;">#<?= e($code) ?></span>
                <span id="olist-badge-<?= e($code) ?>" class="pill pill-<?= $ord['package_decision']==='Accepted' ? 'confirmed' : 'cancelled' ?>" style="font-size:0.65rem;">
                  <?= $ord['package_decision']==='Accepted' ? 'Accepted' : 'Rejected' ?>
                </span>
              </div>
              <div class="font-semibold text-xs" style="color:var(--navy-900);"><?= e($ord['event_name']) ?></div>
              <div class="text-xs text-muted"><?= e($ord['venue_name']) ?> • <?= (int)$ord['guest_count'] ?> Guests</div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Order Header Card -->
        <div class="order-header-card">
          <div>
            <div id="order-header-id" class="order-header-id">#ORD-2045</div>
            <div id="order-header-meta" class="order-header-meta">
              <div class="order-meta-item">🎉 <strong id="meta-event">Annual Gala Night</strong></div>
              <div class="order-meta-item">🏛️ <strong id="meta-venue">Grand Ballroom, Floor 3</strong></div>
              <div class="order-meta-item">📅 <strong id="meta-date">Sep 14, 2026</strong></div>
              <div class="order-meta-item">⏰ <strong id="meta-time">7:00 PM – 11:00 PM</strong></div>
              <div class="order-meta-item">👥 <strong id="meta-guests">320 Guests</strong></div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
            <span id="order-status-badge" class="pill pill-in-progress" style="font-size:0.85rem; padding:6px 14px;">Preparing</span>
            <span class="text-xs" style="color:#94a3b8;">Package: <strong id="meta-package" style="color:#fff;">Gold Package</strong></span>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- 1. PACKAGE ACCEPT / REJECT DECISION CARD                       -->
        <!-- ============================================================== -->
        <div class="decision-card" id="pkg-decision-card">
          <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
            <div class="flex-center gap-8">
              <span style="font-size:1.4rem;">📦</span>
              <div>
                <h3 style="font-size:1.15rem; font-weight:800; margin:0;" id="pkg-card-title">Catering Package Order: Gold Package</h3>
                <div class="text-xs text-muted" id="pkg-card-subtitle">Covers 320 guests · Base package order submitted by customer</div>
              </div>
            </div>
            <span id="pkg-status-badge" class="pill pill-inquiry" style="font-size:0.8rem; padding:4px 12px; font-weight:700;">PENDING REVIEW</span>
          </div>

          <div style="display:grid; grid-template-columns: 1fr auto; gap:20px; align-items:center;">
            <div>
              <div class="text-sm text-muted mb-6" id="pkg-card-desc">
                Includes full multi-course plated dining service, staff coordination, and banquet equipment setup.
              </div>
              <div class="text-xs text-muted">
                Package Value: <strong class="text-primary font-bold" id="pkg-card-price" style="font-size:1rem;">$13,260.00</strong>
              </div>
            </div>

            <!-- Package Decision Buttons -->
            <div class="flex gap-10" id="pkg-btn-group">
              <button type="button" class="btn-accept" id="btn-pkg-accept" onclick="decidePackage('Accepted')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Accept Package
              </button>
              <button type="button" class="btn-reject" id="btn-pkg-reject" onclick="decidePackage('Rejected')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Reject Package
              </button>
            </div>
          </div>

          <!-- Alert / Feedback Banner -->
          <div id="pkg-feedback-box" style="margin-top:14px; display:none; padding:10px 14px; border-radius:var(--radius-sm); font-size:0.85rem;"></div>

          <!-- Optional Rejection Reason Box -->
          <div id="pkg-reject-box" style="margin-top:12px; display:none;">
            <label class="form-label text-xs">Reason for rejection (shared with customer &amp; event coordinator):</label>
            <div class="flex gap-8 mt-4">
              <input type="text" class="form-control text-sm" id="pkg-reject-reason" placeholder="e.g., Kitchen capacity full for this date / ingredients unavailable...">
              <button type="button" class="btn btn-outline btn-sm font-bold" onclick="savePkgRejectReason()">Save Note</button>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- 2. CUSTOMER SINGULAR ADD-ON ITEMS (ACCEPT / REJECT PER ITEM)   -->
        <!-- ============================================================== -->
        <div class="card mb-24">
          <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:14px;">
            <div>
              <div class="flex-center gap-8">
                <span style="font-size:1.4rem;">🍽️</span>
                <h3 style="font-size:1.15rem; font-weight:800; margin:0;">Customer Add-On Singular Items</h3>
                <span class="pill pill-inquiry" id="si-count-badge" style="font-size:0.75rem;">4 Items</span>
              </div>
              <div class="text-xs text-muted mt-4">
                The customer ordered these specific singular items alongside the main package. Accept or reject each item according to inventory.
              </div>
            </div>

            <!-- Batch Actions -->
            <div class="flex gap-8">
              <button type="button" class="btn-batch btn-accept" onclick="decideAllSingularItems('Accepted')">
                ✓ Accept All
              </button>
              <button type="button" class="btn-batch btn-reject" onclick="decideAllSingularItems('Rejected')">
                ✕ Reject All
              </button>
            </div>
          </div>

          <!-- Singular Items List Container -->
          <div id="singular-items-container">
            <!-- Dynamically populated per order -->
          </div>

          <!-- Singular Items Financial Summary -->
          <div style="background:#f8fafc; border:1px solid var(--gray-200); border-radius:var(--radius-sm); padding:14px 18px; margin-top:16px;">
            <div class="flex-between text-sm mb-4">
              <span class="text-muted">Total Add-Ons Requested:</span>
              <strong id="si-total-requested">$755.00</strong>
            </div>
            <div class="flex-between text-sm mb-4" style="color:#166534;">
              <span>Accepted Add-Ons Revenue:</span>
              <strong id="si-total-accepted">$210.00</strong>
            </div>
            <div class="flex-between text-sm" style="color:#991b1b;">
              <span>Rejected Add-Ons Excluded:</span>
              <strong id="si-total-rejected">$0.00</strong>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- 3. DYNAMICALLY CALCULATED TOTAL ORDER REVENUE CARD              -->
        <!-- ============================================================== -->
        <div class="card mb-24" style="background:#ffffff; border:1.5px solid var(--gray-200); border-radius:var(--radius); padding:24px;">
          <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
            <div class="flex-center gap-8">
              <span style="font-size:1.4rem;">💰</span>
              <div>
                <h3 style="font-size:1.15rem; font-weight:800; margin:0;">Dynamically Calculated Total Revenue</h3>
                <div class="text-xs text-muted">Comprehensive revenue summary: Package base price plus singular add-on menu items</div>
              </div>
            </div>
            <span class="pill pill-confirmed" style="font-size:0.8rem; font-weight:700;">NET REVENUE</span>
          </div>

          <div class="grid-3 gap-16">
            <div class="card" style="background:#f8fafc; padding:16px; border:1px solid var(--gray-200);">
              <div class="text-xs text-muted font-bold mb-4" style="text-transform:uppercase;">1. Catering Package Price</div>
              <div class="font-bold text-primary" style="font-size:1.35rem;" id="rev-package-price">$0.00</div>
              <div class="text-xs text-muted mt-4" id="rev-package-name">Base menu service</div>
            </div>
            <div class="card" style="background:#f8fafc; padding:16px; border:1px solid var(--gray-200);">
              <div class="text-xs text-muted font-bold mb-4" style="text-transform:uppercase;">2. Singular Add-on Items</div>
              <div class="font-bold" style="font-size:1.35rem; color:#059669;" id="rev-singular-price">$0.00</div>
              <div class="text-xs text-muted mt-4" id="rev-singular-count">0 items selected</div>
            </div>
            <div class="card" style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color:#fff; padding:16px; border:none;">
              <div class="text-xs font-bold mb-4" style="text-transform:uppercase; color:#94a3b8;">Total Calculated Revenue</div>
              <div class="font-bold text-success" style="font-size:1.5rem; color:#6ee7b7;" id="rev-total-price">$0.00</div>
              <div class="text-xs mt-4" style="color:#cbd5e1;">Sum of package + singular items</div>
            </div>
          </div>
        </div>

      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>

  <script>
    // Master data store populated dynamically from MySQL database
    const orderStore = <?= json_encode($jsOrderStore, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let activeOrderKey = Object.keys(orderStore)[0] || "";

    // -------------------------------------------------------------
    // LOAD ORDER FUNCTION
    // -------------------------------------------------------------
    function loadOrder(key) {
      activeOrderKey = key;
      const order = orderStore[key];
      if (!order) return;

      // Update card active states in selector
      Object.keys(orderStore).forEach(k => {
        const c = document.getElementById('ocard-' + k);
        if (c) {
          if (k === key) c.classList.add('active');
          else c.classList.remove('active');
        }
      });

      // Update Order Header
      document.getElementById('order-header-id').innerText = order.id;
      document.getElementById('meta-event').innerText = order.event;
      document.getElementById('meta-venue').innerText = order.venue;
      document.getElementById('meta-date').innerText = order.date;
      document.getElementById('meta-time').innerText = order.time;
      document.getElementById('meta-guests').innerText = order.guests;
      document.getElementById('meta-package').innerText = order.package;
      const pcb = document.getElementById('package-covers-badge');
      if (pcb) pcb.innerText = order.coversText;
      const otd = document.getElementById('order-total-display');
      if (otd) otd.innerText = order.total;

      const badge = document.getElementById('order-status-badge');
      badge.innerText = order.status;
      badge.className = order.status === 'Delivering' ? 'pill pill-confirmed' : 'pill pill-in-progress';

      // 1. Render Package Decision Card
      renderPackageDecision();

      // 2. Render Singular Items
      renderSingularItems();

      // 3. Update Dynamically Calculated Total Revenue
      updateTotalRevenue();
    }

    // -------------------------------------------------------------
    // PACKAGE DECISION LOGIC
    // -------------------------------------------------------------
    function renderPackageDecision() {
      const order = orderStore[activeOrderKey];
      const card = document.getElementById('pkg-decision-card');
      const title = document.getElementById('pkg-card-title');
      const subtitle = document.getElementById('pkg-card-subtitle');
      const desc = document.getElementById('pkg-card-desc');
      const price = document.getElementById('pkg-card-price');
      const statusBadge = document.getElementById('pkg-status-badge');
      const btnAccept = document.getElementById('btn-pkg-accept');
      const btnReject = document.getElementById('btn-pkg-reject');
      const feedback = document.getElementById('pkg-feedback-box');
      const rejectBox = document.getElementById('pkg-reject-box');

      title.textContent = `Catering Package Order: ${order.package}`;
      subtitle.textContent = `Covers ${order.guests} · Ordered by customer for ${order.event}`;
      desc.textContent = order.packageDesc || 'Full multi-course plated dining service with banquet setup.';
      price.textContent = order.packagePrice || order.total;

      card.classList.remove('accepted', 'rejected');
      btnAccept.classList.remove('active');
      btnReject.classList.remove('active');
      rejectBox.style.display = 'none';

      if (order.packageDecision === 'Accepted') {
        card.classList.add('accepted');
        statusBadge.className = 'pill pill-confirmed';
        statusBadge.textContent = 'PACKAGE ACCEPTED';
        btnAccept.classList.add('active');
        feedback.style.display = 'block';
        feedback.style.background = '#dcfce7';
        feedback.style.border = '1px solid #86efac';
        feedback.style.color = '#166534';
        feedback.innerHTML = '✓ <strong>Package Accepted!</strong> Kitchen preparation and scheduling are confirmed for this event.';
      } else if (order.packageDecision === 'Rejected') {
        card.classList.add('rejected');
        statusBadge.className = 'pill pill-canceled';
        statusBadge.textContent = 'PACKAGE REJECTED';
        btnReject.classList.add('active');
        rejectBox.style.display = 'block';
        feedback.style.display = 'block';
        feedback.style.background = '#fee2e2';
        feedback.style.border = '1px solid #fca5a5';
        feedback.style.color = '#991b1b';
        feedback.innerHTML = '✕ <strong>Package Rejected.</strong> Customer and Venue Staff have been notified to arrange alternatives.';
      } else {
        statusBadge.className = 'pill pill-inquiry';
        statusBadge.textContent = 'PENDING REVIEW';
        feedback.style.display = 'none';
      }

      // Update selector card badge
      updateSelectorBadge(activeOrderKey);
    }

    async function decidePackage(decision) {
      const order = orderStore[activeOrderKey];
      if (!order) return;
      const orderId = order.orderDbId;
      try {
        const res = await fetch('../api/caterer-orders.php?action=decide_package', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ order_id: orderId, decision: decision, reason: '' })
        });
        const d = await res.json();
        if (d.success) {
          order.packageDecision = decision;
          renderPackageDecision();
        } else {
          alert(d.message || 'Could not save decision.');
        }
      } catch(e) { alert('Connection error. Please try again.'); }
    }

    async function savePkgRejectReason() {
      const order = orderStore[activeOrderKey];
      if (!order) return;
      const reason = document.getElementById('pkg-reject-reason') ? document.getElementById('pkg-reject-reason').value : '';
      try {
        const res = await fetch('../api/caterer-orders.php?action=decide_package', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ order_id: order.orderDbId, decision: 'Rejected', reason: reason })
        });
        const d = await res.json();
        if (d.success) {
          const fb = document.getElementById('pkg-feedback-box');
          if (fb) { fb.innerHTML = '✕ <strong>Rejection reason saved.</strong>'; }
        } else { alert(d.message || 'Could not save rejection reason.'); }
      } catch(e) { alert('Connection error.'); }
    }

    // -------------------------------------------------------------
    // SINGULAR ITEMS DECISION LOGIC
    // -------------------------------------------------------------
    function renderSingularItems() {
      const order = orderStore[activeOrderKey];
      const container = document.getElementById('singular-items-container');
      const countBadge = document.getElementById('si-count-badge');
      const items = order.singularItems || [];

      countBadge.textContent = `${items.length} Items Requested`;

      if (items.length === 0) {
        container.innerHTML = '<div class="text-sm text-muted" style="padding:16px; text-align:center;">No singular add-on items requested for this order.</div>';
        updateSingularFinancials();
        return;
      }

      let html = '';
      items.forEach((item, idx) => {
        const itemSubtotal = (item.qty * item.unitPrice).toFixed(2);
        const statusClass = item.status === 'Accepted' ? 'accepted' : (item.status === 'Rejected' ? 'rejected' : '');
        const pillClass = item.status === 'Accepted' ? 'pill pill-confirmed' : (item.status === 'Rejected' ? 'pill pill-canceled' : 'pill pill-inquiry');
        const pillText = item.status === 'Accepted' ? 'ACCEPTED' : (item.status === 'Rejected' ? 'REJECTED' : 'PENDING');
        const dietHtml = item.diet ? `<span class="diet-tag ${item.diet === 'GF' ? 'diet-gf' : 'diet-veg'}">${item.diet}</span>` : '';

        html += `
          <div class="si-row ${statusClass}" id="si-row-${idx}">
            <div class="si-emoji">${item.emoji || '🍽️'}</div>
            <div class="si-info">
              <div class="si-name">
                <span>${item.name}</span>
                ${dietHtml}
              </div>
              <div class="si-meta">
                Qty: <strong>${item.qty}</strong> · Unit Price: <strong>$${item.unitPrice.toFixed(2)}</strong>
                ${item.note ? ` · <span style="font-style:italic;">Note: "${item.note}"</span>` : ''}
              </div>
            </div>
            <div class="si-price-col">
              <div class="si-total">$${itemSubtotal}</div>
              <div class="si-unit">$${item.unitPrice.toFixed(2)}/unit</div>
            </div>
            <div class="si-actions">
              <span class="${pillClass}" style="font-size:0.7rem; padding:4px 8px;">${pillText}</span>
              <button type="button" class="btn-accept ${item.status === 'Accepted' ? 'active' : ''}" 
                title="Accept this item" onclick="decideSingularItem(${idx}, 'Accepted')">
                ✓ Accept
              </button>
              <button type="button" class="btn-reject ${item.status === 'Rejected' ? 'active' : ''}" 
                title="Reject this item" onclick="decideSingularItem(${idx}, 'Rejected')">
                ✕ Reject
              </button>
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
      updateSingularFinancials();
    }

    async function decideSingularItem(idx, decision) {
      const order = orderStore[activeOrderKey];
      if (!order || !order.singularItems || !order.singularItems[idx]) return;
      const item = order.singularItems[idx];
      const itemId = item.id;
      try {
        const res = await fetch('../api/caterer-orders.php?action=decide_singular_item', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ item_id: itemId, decision: decision })
        });
        const d = await res.json();
        if (d.success) {
          item.status = decision;
          renderSingularItems();
          updateSelectorBadge(activeOrderKey);
        } else {
          alert(d.message || 'Could not save item decision.');
        }
      } catch(e) { alert('Connection error. Please try again.'); }
    }

    async function decideAllSingularItems(decision) {
      const order = orderStore[activeOrderKey];
      if (!order) return;
      const bookingId = order.bookingId;
      try {
        const res = await fetch('../api/caterer-orders.php?action=decide_all_singular_items', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ booking_id: bookingId, decision: decision })
        });
        const d = await res.json();
        if (d.success) {
          if (order.singularItems) {
            order.singularItems.forEach(function(item) { item.status = decision; });
          }
          renderSingularItems();
          updateSelectorBadge(activeOrderKey);
        } else {
          alert(d.message || 'Could not update all items.');
        }
      } catch(e) { alert('Connection error. Please try again.'); }
    }

    function updateSingularFinancials() {
      const order = orderStore[activeOrderKey];
      const items = order.singularItems || [];

      let requested = 0;
      let accepted = 0;
      let rejected = 0;

      items.forEach(item => {
        const sub = item.qty * item.unitPrice;
        requested += sub;
        if (item.status === 'Accepted') accepted += sub;
        else if (item.status === 'Rejected') rejected += sub;
      });

      document.getElementById('si-total-requested').textContent = '$' + requested.toFixed(2);
      document.getElementById('si-total-accepted').textContent = '$' + accepted.toFixed(2);
      document.getElementById('si-total-rejected').textContent = '$' + rejected.toFixed(2);

      updateTotalRevenue();
    }

    function updateTotalRevenue() {
      const order = orderStore[activeOrderKey];
      if (!order) return;

      let pkgPrice = 0;
      if (order.packagePrice) {
        pkgPrice = parseFloat(String(order.packagePrice).replace(/[^0-9.-]+/g, '')) || 0;
      }

      let singTotal = 0;
      let count = 0;
      (order.singularItems || []).forEach(item => {
        if (item.status !== 'Rejected') {
          singTotal += (item.qty * item.unitPrice);
          count++;
        }
      });

      let totalRev = pkgPrice + singTotal;

      const pkgEl = document.getElementById('rev-package-price');
      const pkgNameEl = document.getElementById('rev-package-name');
      const singEl = document.getElementById('rev-singular-price');
      const singCountEl = document.getElementById('rev-singular-count');
      const totalEl = document.getElementById('rev-total-price');

      if (pkgEl) pkgEl.textContent = '$' + pkgPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (pkgNameEl) pkgNameEl.textContent = (order.package || 'Catering Package') + ' Base';
      if (singEl) singEl.textContent = '$' + singTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (singCountEl) singCountEl.textContent = count + ' add-on item(s) included';
      if (totalEl) totalEl.textContent = '$' + totalRev.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateSelectorBadge(key) {
      const badge = document.getElementById('olist-badge-' + key);
      const order = orderStore[key];
      if (!badge || !order) return;

      if (order.packageDecision === 'Accepted') {
        badge.className = 'pill pill-confirmed';
        badge.textContent = 'Accepted';
      } else {
        badge.className = 'pill pill-canceled';
        badge.textContent = 'Rejected';
      }
    }

    function updateOrderStatus(newStatus) {
      if (orderStore[activeOrderKey]) {
        orderStore[activeOrderKey].status = newStatus;
        const badge = document.getElementById('order-status-badge');
        badge.innerText = newStatus;
        badge.className = newStatus === 'Delivering' ? 'pill pill-confirmed' : 'pill pill-in-progress';
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const firstKey = Object.keys(orderStore)[0];
      if (firstKey) loadOrder(firstKey);
    });
  </script>
<script src="../js/app.js"></script>
</body>
</html>
