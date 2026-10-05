<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
// Financial metrics
$stmtRev  = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$totalRev = (float)$stmtRev->fetchColumn();

$stmtBk   = $db->query("SELECT COUNT(*) FROM bookings");
$totalBk  = (int)$stmtBk->fetchColumn();

$stmtAvg  = $db->query("SELECT COALESCE(AVG(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$avgBkVal = (float)$stmtAvg->fetchColumn();

$stmtMax  = $db->query("SELECT COALESCE(MAX(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$maxBooking = (float)$stmtMax->fetchColumn();

// Confirmed and pending breakdown
$stmtConfRev = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status IN ('confirmed','completed')");
$confirmedRev = (float)$stmtConfRev->fetchColumn();

$stmtPendRev = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status = 'pending'");
$pendingRev = (float)$stmtPendRev->fetchColumn();

// Top venues by revenue and bookings (excluding cancelled)
$stmtVTop = $db->query(
    "SELECT v.name, COUNT(b.id) AS bk_count, COALESCE(SUM(b.total_amount),0) AS revenue
     FROM venues v
     LEFT JOIN bookings b ON v.id = b.venue_id AND b.booking_status != 'cancelled'
     GROUP BY v.id
     ORDER BY revenue DESC, bk_count DESC
     LIMIT 5"
);
$topVenues = $stmtVTop->fetchAll();
$maxBkCount = !empty($topVenues) ? max(1, ...array_column($topVenues, 'bk_count')) : 1;

// Revenue streams breakdown
$revBreakdown = $db->query("
    SELECT
      COALESCE(SUM(venue_cost), 0) AS total_venue_cost,
      COALESCE(SUM(package_cost), 0) AS total_caterer_cost,
      COALESCE(SUM(staffing_cost), 0) AS total_staff_cost,
      COALESCE(SUM(service_fee + tax_vat), 0) AS total_fees
    FROM bookings
    WHERE booking_status != 'cancelled'
")->fetch();

// Monthly transaction volume
$stmtMonthly = $db->query(
    "SELECT DATE_FORMAT(event_date, '%b %Y') AS month_label,
            DATE_FORMAT(event_date, '%b') AS short_month,
            DATE_FORMAT(event_date, '%Y-%m') AS ym,
            COUNT(*) AS total_count,
            COALESCE(SUM(total_amount), 0) AS volume
     FROM bookings
     WHERE booking_status != 'cancelled'
     GROUP BY ym
     ORDER BY ym ASC"
);
$monthlyTxns = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

// Full detailed transactions
$stmtTxn = $db->query(
    "SELECT b.*, u.name AS client_name, u.email AS client_email, u.phone AS client_phone, v.name AS venue_name
     FROM bookings b
     JOIN users u ON b.customer_id = u.id
     JOIN venues v ON b.venue_id = v.id
     ORDER BY b.created_at DESC LIMIT 50"
);
$transactions = $stmtTxn->fetchAll();

// Booking summary breakdown
$bkSummary = $db->query("
    SELECT
      COUNT(*) AS total,
      COALESCE(SUM(booking_status IN ('confirmed','completed')), 0) AS confirmed,
      COALESCE(SUM(booking_status = 'pending'), 0) AS pending,
      COALESCE(SUM(booking_status IN ('cancelled','rejected')), 0) AS cancelled
    FROM bookings
")->fetch();
$bkTotal     = (int)($bkSummary['total']     ?? 0);
$bkConfirmed = (int)($bkSummary['confirmed'] ?? 0);
$bkPending   = (int)($bkSummary['pending']   ?? 0);
$bkCancelled = (int)($bkSummary['cancelled'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Reports & Analytics</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
.report-tab { display:none; }
.tab-btn { border:none; background:transparent; cursor:pointer; }

/* CSS-only chart */
.chart-wrap { display:flex; align-items:flex-end; gap:6px; height:160px; padding:0 4px; }
.chart-bar-col { display:flex; flex-direction:column; align-items:center; gap:4px; flex:1; }
.chart-bar { width:100%; border-radius:4px 4px 0 0; background:var(--primary); opacity:.45; min-width:28px; }
.chart-bar.peak { opacity:1; }
.chart-label { font-size:.65rem; color:var(--gray-500); text-align:center; }
.chart-val { font-size:.6rem; color:var(--gray-400); }

.filter-bar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.filter-bar select, .filter-bar input[type=date] { padding:7px 12px; border:1.5px solid var(--gray-300); border-radius:var(--radius-sm); font-size:.8rem; background:#fff; }
.filter-bar label { font-size:.8rem; font-weight:600; color:var(--gray-600); }
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
        <div class="nav-label">Governance</div>
        <a href="admin-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Staff Directory
        </a>
        <a href="admin-user-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> User Governance
        </a>
        <a href="admin-reports.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../logout.php" class="nav-item" style="color:var(--gray-400);">
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
          <input type="text" placeholder="e.g. Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
        <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge"><?= $bkPending ?></span>
            🔔
          </a>
          <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="View & Edit My Profile">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'admin')) ?></div>
            </div>
          </a>
        </div>
      </header>
      <main class="page-body">

<div class="flex-between mb-24">
  <div>
    <h1>Reports &amp; Analytics</h1>
    <p>Full financial and operational overview across all VenuePro properties.</p>
  </div>
  <a href="admin-export-revenue.php" class="btn btn-primary">📥 Export Report</a>
</div>

<!-- Summary Cards -->
<div class="stats-grid mb-24">
  <div class="stat-card green">
    <div class="stat-label">Total Gross Revenue</div>
    <div class="stat-value">$<?= number_format($totalRev, 2) ?></div>
    <span class="stat-badge positive"><?= $totalRev > 0 ? '$' . number_format($confirmedRev, 2) . ' confirmed (' . round(($confirmedRev / $totalRev) * 100) . '%)' : 'No revenue recorded' ?></span>
    <div class="stat-icon" style="color:#059669;background:#d1fae5;">💵</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Transactions</div>
    <div class="stat-value"><?= $totalBk ?></div>
    <span class="stat-badge <?= $bkPending > 0 ? 'warning' : 'neutral' ?>"><?= $bkConfirmed ?> Confirmed · <?= $bkPending ?> Pending</span>
    <div class="stat-icon">📋</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Avg. Transaction Value</div>
    <div class="stat-value"><?= $totalBk > 0 ? "$" . number_format($avgBkVal, 2) : "$0.00" ?></div>
    <span class="stat-badge positive"><?= $maxBooking > 0 ? 'Peak Booking: $' . number_format($maxBooking, 2) : 'No bookings recorded' ?></span>
    <div class="stat-icon">📈</div>
  </div>
</div>

<!-- Revenue Streams Breakdown -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Revenue by Service Stream</div>
    <span class="text-xs text-muted">Direct allocation from confirmed and active event bookings</span>
  </div>
  <div class="grid-4 gap-16">
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); border:1px solid var(--gray-200);">
      <div class="text-xs text-muted font-bold mb-4">VENUE HIRE</div>
      <div style="font-size:1.35rem; font-weight:800; color:var(--navy-900);">$<?= number_format((float)$revBreakdown['total_venue_cost'], 2) ?></div>
      <div class="text-xs text-muted mt-4"><?= $totalRev > 0 ? round(($revBreakdown['total_venue_cost'] / $totalRev) * 100) : 0 ?>% of total revenue</div>
    </div>
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); border:1px solid var(--gray-200);">
      <div class="text-xs text-muted font-bold mb-4">CATERING PACKAGES</div>
      <div style="font-size:1.35rem; font-weight:800; color:#059669;">$<?= number_format((float)$revBreakdown['total_caterer_cost'], 2) ?></div>
      <div class="text-xs text-muted mt-4"><?= $totalRev > 0 ? round(($revBreakdown['total_caterer_cost'] / $totalRev) * 100) : 0 ?>% of total revenue</div>
    </div>
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); border:1px solid var(--gray-200);">
      <div class="text-xs text-muted font-bold mb-4">STAFFING &amp; CREW</div>
      <div style="font-size:1.35rem; font-weight:800; color:#2563eb;">$<?= number_format((float)$revBreakdown['total_staff_cost'], 2) ?></div>
      <div class="text-xs text-muted mt-4"><?= $totalRev > 0 ? round(($revBreakdown['total_staff_cost'] / $totalRev) * 100) : 0 ?>% of total revenue</div>
    </div>
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); border:1px solid var(--gray-200);">
      <div class="text-xs text-muted font-bold mb-4">FEES &amp; TAXES</div>
      <div style="font-size:1.35rem; font-weight:800; color:#d97706;">$<?= number_format((float)$revBreakdown['total_fees'], 2) ?></div>
      <div class="text-xs text-muted mt-4"><?= $totalRev > 0 ? round(($revBreakdown['total_fees'] / $totalRev) * 100) : 0 ?>% of total revenue</div>
    </div>
  </div>
</div>

<!-- Monthly Transaction Chart -->
<div class="card mb-24">
  <div class="card-header" style="flex-wrap:wrap; gap:12px;">
    <div>
      <div class="card-title">Monthly Transaction Volume</div>
      <span class="text-xs text-muted" id="chartFilterSubtitle">Showing interactive transaction volume breakdown</span>
    </div>
    <div style="display:flex; align-items:center; gap:8px;">
      <label class="text-xs text-muted font-semibold" for="txnPeriodFilter">Select Period:</label>
      <select id="txnPeriodFilter" class="form-control" style="width:auto; padding:6px 12px; font-size:0.85rem;" onchange="updateTransactionGraph(this.value)">
        <option value="6m" selected>Last 6 Months</option>
        <option value="12m">Last 12 Months</option>
        <option value="2026">Year 2026</option>
        <option value="all">All Time</option>
      </select>
    </div>
  </div>
  <div id="txnChartWrap" class="chart-wrap" style="margin-top:20px; min-height:180px; align-items:flex-end;">
    <!-- Rendered dynamically by updateTransactionGraph -->
  </div>
  <div id="txnChartSummary" style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:12px; border-top:1px solid var(--gray-200); font-size:0.85rem; color:var(--gray-600);">
  </div>
</div>

<!-- Transaction Table -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Transaction Register</div>
    <a href="admin-export-revenue.php" class="btn btn-outline btn-sm">Generate &amp; Export</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>BOOKING / TXN ID</th>
          <th>EVENT DATE</th>
          <th>CLIENT</th>
          <th>VENUE</th>
          <th>EVENT NAME</th>
          <th>AMOUNT</th>
          <th>STATUS</th>
          <th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
        <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--gray-400);">No transactions found in records.</td></tr>
        <?php else: foreach ($transactions as $tx):
          $txnCode = 'TXN-' . str_pad($tx['id'], 4, '0', STR_PAD_LEFT);
          $bkCode  = $tx['booking_code'] ?: ('#BK-' . $tx['id']);
          $txDate  = date('M j, Y', strtotime($tx['event_date'] ?: $tx['created_at']));
          $isPaid  = in_array($tx['booking_status'], ['confirmed', 'completed']) || ($tx['payment_status'] ?? '') === 'paid';
          $statusTxt = strtoupper($tx['booking_status']);
          $statusCls = ($tx['booking_status'] === 'confirmed' || $tx['booking_status'] === 'completed') ? 'confirmed' : ($tx['booking_status'] === 'cancelled' || $tx['booking_status'] === 'rejected' ? 'cancelled' : 'pending');
          $modalData = [
            'id' => $txnCode,
            'booking_code' => $bkCode,
            'date' => $txDate,
            'client' => $tx['client_name'],
            'email' => $tx['client_email'] ?? '',
            'phone' => $tx['client_phone'] ?? 'N/A',
            'venue' => $tx['venue_name'],
            'event' => $tx['event_name'],
            'amount' => '$' . number_format((float)$tx['total_amount'], 2),
            'method' => !empty($tx['payment_card_last4']) ? ('Credit Card (•••• ' . $tx['payment_card_last4'] . ')') : 'Electronic Payment',
            'ref' => $bkCode,
            'status' => $statusTxt,
            'status_cls' => $statusCls,
            'booking_id' => (int)$tx['id'],
            'guests' => (int)$tx['guest_count']
          ];
        ?>
        <tr>
          <td class="font-bold text-primary"><?= e($bkCode) ?></td>
          <td><?= $txDate ?></td>
          <td><?= e($tx['client_name']) ?></td>
          <td><?= e($tx['venue_name']) ?></td>
          <td><span class="pill pill-in-progress" style="font-size:0.75rem;"><?= e($tx['event_name']) ?></span></td>
          <td class="font-bold">$<?= number_format((float)$tx['total_amount'], 2) ?></td>
          <td><span class="pill pill-<?= $statusCls ?>"><?= $statusTxt ?></span></td>
          <td><button class="btn btn-ghost btn-sm" onclick='showTransactionModal(<?= htmlspecialchars(json_encode($modalData), ENT_QUOTES, 'UTF-8') ?>)'>View</button></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="flex-between" style="padding:12px 0 0; font-size:.8rem; color:var(--gray-500);">
    <span>Showing <?= count($transactions) ?> recorded transactions</span>
  </div>
</div>

<!-- Staff & Caterer Quick Links -->
<!-- <div class="grid-2 gap-20 mb-24">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Staff Performance Overview</div>
      <a href="admin-staff-management.php" class="btn btn-outline btn-sm">Manage Staff</a>
    </div>
    <table>
      <thead><tr><th>STAFF</th><th>ROLE</th><th>EVENTS</th><th>ACTION</th></tr></thead>
      <tbody>
        <?php if (empty($staffPerf)): ?>
        <tr><td colspan="4" style="text-align:center; padding:16px; color:var(--gray-400);">No staff records found.</td></tr>
        <?php else: foreach ($staffPerf as $sp): ?>
        <tr>
          <td class="font-semibold"><?= e($sp['name']) ?></td>
          <td><?= e($sp['department'] ?: 'Event Staff') ?></td>
          <td><?= (int)$sp['event_count'] ?> event<?= (int)$sp['event_count'] === 1 ? '' : 's' ?></td>
          <td><a href="admin-staff-management.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card">
    <div class="card-header">
      <div class="card-title">Caterer Revenue Contribution</div>
      <a href="admin-catering-management.php" class="btn btn-outline btn-sm">Manage Caterers</a>
    </div>
    <table>
      <thead><tr><th>CATERER</th><th>ORDERS</th><th>REVENUE</th><th>ACTION</th></tr></thead>
      <tbody>
        <?php if (empty($catererContrib)): ?>
        <tr><td colspan="4" style="text-align:center; padding:16px; color:var(--gray-400);">No caterer records found.</td></tr>
        <?php else: foreach ($catererContrib as $cc): ?>
        <tr>
          <td class="font-semibold"><?= e($cc['business_name'] ?: $cc['owner_name']) ?></td>
          <td><?= (int)$cc['order_count'] ?></td>
          <td class="font-bold text-success">$<?= number_format($cc['total_cat_rev'], 2) ?></td>
          <td><a href="admin-catering-management.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div> -->
<!-- Bookings Summary -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Generated Bookings Summary</div>
    <a href="admin-pending-bookings.php" class="btn btn-outline btn-sm">View All Bookings</a>
  </div>
  <div class="grid-4 gap-16 mb-16">
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:var(--primary);"><?= $bkTotal ?></div>
      <div class="text-xs text-muted">Total Generated</div>
    </div>
    <div style="padding:16px; background:#dcfce7; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#16a34a;"><?= $bkConfirmed ?></div>
      <div class="text-xs text-muted">Confirmed</div>
    </div>
    <div style="padding:16px; background:#fef9c3; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#d97706;"><?= $bkPending ?></div>
      <div class="text-xs text-muted">Pending Approval</div>
    </div>
    <div style="padding:16px; background:#fee2e2; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#dc2626;"><?= $bkCancelled ?></div>
      <div class="text-xs text-muted">Rejected / Cancelled</div>
    </div>
  </div>
  <!-- Top venues bar visual -->
  <div style="margin-top:8px;">
    <?php if (empty($topVenues)): ?>
    <div class="text-sm text-muted" style="text-align:center; padding:16px;">No venue booking data yet.</div>
    <?php else: foreach ($topVenues as $tv):
      $barPct = $maxBkCount > 0 ? round(($tv['bk_count'] / $maxBkCount) * 100) : 0;
    ?>
    <div class="flex-between text-sm mb-4">
      <span class="font-semibold"><?= e($tv['name']) ?></span>
      <span><?= (int)$tv['bk_count'] ?> bookings — $<?= number_format((float)$tv['revenue'], 0) ?></span>
    </div>
    <div class="progress-bar mb-12"><div class="progress-fill" style="width:<?= $barPct ?>%;"></div></div>
    <?php endforeach; endif; ?>
  </div>
</div>

      </main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>

  <!-- Transaction Details Modal -->
  <div id="txn-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="card" style="max-width:560px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); margin:0;">
      <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
        <div class="flex gap-8" style="align-items:center;">
          <h3 style="margin:0;">Transaction Details</h3>
          <span id="txn-modal-id" class="font-bold text-primary" style="font-size:1.05rem;"></span>
        </div>
        <button type="button" onclick="closeTxnModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-500); line-height:1;">&times;</button>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:var(--gray-50); padding:12px 16px; border-radius:var(--radius-sm);">
        <div>
          <div class="text-xs text-muted font-bold">PAYMENT STATUS</div>
          <span class="pill" id="txn-modal-status" style="margin-top:4px; display:inline-block;">PENDING</span>
        </div>
        <div style="text-align:right;">
          <div class="text-xs text-muted font-bold">TOTAL AMOUNT</div>
          <div id="txn-modal-amount" style="font-size:1.4rem; font-weight:800; color:var(--navy-900);">$0.00</div>
        </div>
      </div>

      <div class="grid-2 gap-16 mb-20">
        <div>
          <div class="text-xs text-muted font-bold mb-4">CLIENT / CUSTOMER</div>
          <div id="txn-modal-client" class="font-bold">-</div>
          <div id="txn-modal-email" class="text-xs text-muted"></div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">TRANSACTION DATE</div>
          <div id="txn-modal-date" class="font-medium">-</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">VENUE / SERVICE</div>
          <div id="txn-modal-venue" class="font-medium">-</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">EVENT NAME &amp; GUESTS</div>
          <div id="txn-modal-event" class="font-medium">-</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">PAYMENT METHOD</div>
          <div id="txn-modal-method" class="font-medium">-</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">BOOKING REFERENCE</div>
          <div id="txn-modal-ref" class="font-mono text-xs text-muted">-</div>
        </div>
      </div>

      <div class="card mb-20" style="background:#f8fafc; border:1px solid var(--gray-200); padding:12px 16px;">
        <div class="text-xs text-muted font-bold mb-8">SETTLEMENT DETAILS</div>
        <div class="flex-between text-sm mb-4">
          <span>Processor Network</span>
          <span class="font-semibold">Stripe Enterprise Merchant</span>
        </div>
        <div class="flex-between text-sm mb-4">
          <span>Settlement Status</span>
          <span id="txn-modal-settlement" class="text-success font-semibold">Cleared &amp; Disbursed</span>
        </div>
        <div class="flex-between text-sm">
          <span>Merchant Ref</span>
          <span id="txn-modal-merchant-ref" class="font-mono text-xs">-</span>
        </div>
      </div>

      <div class="flex gap-12">
        <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeTxnModal()">Close</button>
        <a id="txn-modal-invoice-btn" href="admin-pending-bookings.php" class="btn btn-outline" style="flex:1; text-align:center; justify-content:center;">Review Booking</a>
        <button type="button" class="btn btn-primary" style="flex:1;" onclick="window.print()">Print Receipt</button>
      </div>
    </div>
  </div>

  <script>
    function showTransactionModal(data) {
      if (!data) return;
      document.getElementById('txn-modal-id').innerText = data.booking_code || ('#' + (data.id || ''));
      document.getElementById('txn-modal-date').innerText = data.date || 'N/A';
      document.getElementById('txn-modal-client').innerText = data.client || 'N/A';
      const emailEl = document.getElementById('txn-modal-email');
      if (emailEl) emailEl.innerText = data.email ? ('e.g. ' + data.email) : '';
      document.getElementById('txn-modal-venue').innerText = data.venue || 'N/A';
      
      const eventEl = document.getElementById('txn-modal-event');
      if (eventEl) {
        let evt = data.event || 'Booking Reservation';
        if (data.guests) evt += ' (' + data.guests + ' guests)';
        eventEl.innerText = evt;
      }
      
      document.getElementById('txn-modal-amount').innerText = data.amount || '$0.00';
      document.getElementById('txn-modal-method').innerText = data.method || 'Electronic Payment';
      document.getElementById('txn-modal-ref').innerText = data.ref || (data.booking_code || 'N/A');
      
      const merchantRefEl = document.getElementById('txn-modal-merchant-ref');
      if (merchantRefEl) merchantRefEl.innerText = data.booking_code || ('TXN-' + data.id);

      const statusEl = document.getElementById('txn-modal-status');
      if (statusEl) {
        statusEl.className = 'pill pill-' + (data.status_cls || 'pending');
        statusEl.innerText = (data.status || 'PENDING') + (data.status_cls === 'confirmed' ? ' ✓' : '');
      }

      const settEl = document.getElementById('txn-modal-settlement');
      if (settEl) {
        if (data.status_cls === 'confirmed') {
          settEl.className = 'text-success font-semibold';
          settEl.innerText = 'Cleared & Disbursed';
        } else if (data.status_cls === 'cancelled') {
          settEl.className = 'text-danger font-semibold';
          settEl.innerText = 'Cancelled / Voided';
        } else {
          settEl.className = 'text-warning font-semibold';
          settEl.innerText = 'Pending Settlement';
        }
      }

      const invoiceBtn = document.getElementById('txn-modal-invoice-btn');
      if (invoiceBtn && data.booking_id) {
        invoiceBtn.href = 'admin-booking-approval.php?id=' + encodeURIComponent(data.booking_id);
      }

      document.getElementById('txn-modal').style.display = 'flex';
    }

    function closeTxnModal() {
      document.getElementById('txn-modal').style.display = 'none';
    }

    const rawMonthlyData = <?= json_encode($monthlyTxns) ?>;

    function updateTransactionGraph(period) {
      const wrap = document.getElementById('txnChartWrap');
      const summary = document.getElementById('txnChartSummary');
      const subtitle = document.getElementById('chartFilterSubtitle');
      if (!wrap) return;

      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      const dataMap = {};
      if (Array.isArray(rawMonthlyData)) {
        rawMonthlyData.forEach(row => {
          if (row.ym) {
            dataMap[row.ym] = {
              label: row.short_month || row.month_label,
              ym: row.ym,
              volume: parseFloat(row.volume) || 0,
              count: parseInt(row.total_count, 10) || 0
            };
          }
        });
      }

      let filtered = [];
      if (period === '6m') {
        filtered = [7, 8, 9, 10, 11, 12].map(m => {
          const ym = '2026-' + String(m).padStart(2, '0');
          return dataMap[ym] || { label: monthNames[m - 1], ym: ym, volume: 0, count: 0 };
        });
        if (subtitle) subtitle.textContent = 'Showing 6-Month Period (July 2026 – December 2026)';
      } else if (period === '12m') {
        filtered = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map(m => {
          const ym = '2026-' + String(m).padStart(2, '0');
          return dataMap[ym] || { label: monthNames[m - 1], ym: ym, volume: 0, count: 0 };
        });
        if (subtitle) subtitle.textContent = 'Showing Full 12 Months (January – December 2026)';
      } else if (period === '2026') {
        filtered = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12].map(m => {
          const ym = '2026-' + String(m).padStart(2, '0');
          return dataMap[ym] || { label: monthNames[m - 1], ym: ym, volume: 0, count: 0 };
        });
        if (subtitle) subtitle.textContent = 'Year 2026 Database Volume (January – December 2026)';
      } else {
        const dbEntries = Object.values(dataMap);
        if (dbEntries.length > 0) {
          filtered = dbEntries.sort((a, b) => a.ym.localeCompare(b.ym));
        } else {
          filtered = [{ label: 'No Data', ym: '', volume: 0, count: 0 }];
        }
        if (subtitle) subtitle.textContent = 'All Historical & Confirmed Database Transactions';
      }

      let maxVol = Math.max(...filtered.map(d => d.volume), 0);
      let totalVol = filtered.reduce((acc, d) => acc + d.volume, 0);
      let totalCount = filtered.reduce((acc, d) => acc + d.count, 0);
      let peakItem = filtered.reduce((max, d) => (d.volume > (max ? max.volume : 0)) ? d : max, null);

      wrap.innerHTML = '';
      filtered.forEach(d => {
        let pct = maxVol > 0 ? Math.round((d.volume / maxVol) * 100) : 0;
        let isPeak = (peakItem && d.volume === peakItem.volume && peakItem.volume > 0);
        let col = document.createElement('div');
        col.className = 'chart-bar-col';
        col.style.flex = '1';
        col.style.display = 'flex';
        col.style.flexDirection = 'column';
        col.style.alignItems = 'center';
        col.style.height = '100%';
        col.style.justifyContent = 'flex-end';

        let val = document.createElement('div');
        val.className = 'chart-val';
        val.style.fontSize = '0.72rem';
        val.style.fontWeight = '600';
        val.style.marginBottom = '6px';
        if (d.volume > 0) {
          val.textContent = '$' + (d.volume >= 1000 ? (d.volume / 1000).toFixed(1) + 'k' : Math.round(d.volume));
        } else {
          val.textContent = '$0';
          val.style.color = 'var(--gray-300)';
        }

        let bar = document.createElement('div');
        bar.className = 'chart-bar' + (isPeak ? ' peak' : '');
        bar.style.height = d.volume > 0 ? Math.max(pct, 8) + '%' : '4px';
        bar.style.width = '100%';
        bar.style.maxWidth = '38px';
        bar.style.borderRadius = '4px 4px 0 0';
        bar.style.background = isPeak ? 'var(--primary)' : (d.volume > 0 ? '#93c5fd' : '#e2e8f0');
        bar.style.transition = 'height 0.4s ease';
        bar.title = d.label + (d.ym ? ' (' + d.ym + ')' : '') + ': $' + d.volume.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (' + d.count + ' transaction' + (d.count === 1 ? '' : 's') + ')';

        let lbl = document.createElement('div');
        lbl.className = 'chart-label';
        lbl.style.fontSize = '0.72rem';
        lbl.style.color = 'var(--gray-500)';
        lbl.style.marginTop = '6px';
        lbl.style.textTransform = 'uppercase';
        lbl.textContent = d.label;

        col.appendChild(val);
        col.appendChild(bar);
        col.appendChild(lbl);
        wrap.appendChild(col);
      });

      if (summary) {
        if (totalVol > 0 && peakItem) {
          summary.innerHTML = '<div>Total Volume: <strong style="color:var(--primary); font-size:1rem;">$' + totalVol.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong> across <strong>' + totalCount + '</strong> transaction' + (totalCount === 1 ? '' : 's') + '</div>' +
                               '<div>Period Peak: <strong>' + peakItem.label + ' ($' + peakItem.volume.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')</strong></div>';
        } else {
          summary.innerHTML = '<div>Total Volume: <strong style="color:var(--primary); font-size:1rem;">$0.00</strong> across <strong>0</strong> transactions</div>' +
                               '<div>Period Peak: <strong style="color:var(--gray-400);">None ($0.00)</strong></div>';
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      updateTransactionGraph('6m');
    });
  </script>
<script src="../js/app.js"></script>
</body>
</html>