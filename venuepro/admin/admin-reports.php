<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
$stmtRev  = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$totalRev = (float)$stmtRev->fetchColumn();
$stmtBk   = $db->query("SELECT COUNT(*) FROM bookings");
$totalBk  = (int)$stmtBk->fetchColumn();
$stmtAvg  = $db->query("SELECT COALESCE(AVG(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$avgBkVal = (float)$stmtAvg->fetchColumn();
$stmtVTop = $db->query("SELECT v.name, COUNT(b.id) AS bk_count, COALESCE(SUM(b.total_amount),0) AS revenue
    FROM venues v LEFT JOIN bookings b ON v.id = b.venue_id GROUP BY v.id ORDER BY revenue DESC LIMIT 5");
$topVenues = $stmtVTop->fetchAll();
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
        <a href="admin-reports.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
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
          <input type="text" placeholder="Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
                  <a href="admin-policy-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Legal &amp; Policies
        </a>
        <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
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
    <div class="stat-label">Total Gross Revenue (YTD)</div>
    <div class="stat-value"><?= "$" . number_format($totalRev/1000,1) . "k" ?></div>
    <span class="stat-badge positive">+18.2% vs last year</span>
    <div class="stat-icon" style="color:#059669;background:#d1fae5;">💵</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Transactions</div>
    <div class="stat-value"><?= $totalBk ?></div>
    <span class="stat-badge neutral">YTD</span>
    <div class="stat-icon">📋</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Avg. Transaction Value</div>
    <div class="stat-value"><?= "$" . number_format($avgBkVal,0) ?></div>
    <span class="stat-badge positive">+5.4%</span>
    <div class="stat-icon">📈</div>
  </div>
</div>

<!-- Monthly Transaction Chart -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Monthly Transaction Volume</div>
    <span class="stat-badge neutral">January – September 2026</span>
  </div>
  <div class="chart-wrap" style="margin-top:16px;">
    <div class="chart-bar-col"><div class="chart-bar" style="height:38%;"></div><div class="chart-val">$21k</div><div class="chart-label">JAN</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:52%;"></div><div class="chart-val">$29k</div><div class="chart-label">FEB</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:45%;"></div><div class="chart-val">$25k</div><div class="chart-label">MAR</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:68%;"></div><div class="chart-val">$38k</div><div class="chart-label">APR</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:80%;"></div><div class="chart-val">$45k</div><div class="chart-label">MAY</div></div>
    <div class="chart-bar-col"><div class="chart-bar peak" style="height:100%;"></div><div class="chart-val">$56k</div><div class="chart-label">JUN</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:72%;"></div><div class="chart-val">$40k</div><div class="chart-label">JUL</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:88%;"></div><div class="chart-val">$49k</div><div class="chart-label">AUG</div></div>
    <div class="chart-bar-col"><div class="chart-bar" style="height:62%;"></div><div class="chart-val">$35k</div><div class="chart-label">SEP</div></div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card mb-16" style="padding:16px 20px;">
  <div class="filter-bar">
    <label>Filter by:</label>
    <select>
      <option>All Months</option>
      <option>January 2026</option>
      <option>February 2026</option>
      <option>March 2026</option>
      <option>April 2026</option>
      <option>May 2026</option>
      <option>June 2026</option>
      <option>July 2026</option>
      <option>August 2026</option>
      <option selected>September 2026</option>
    </select>
    <label>— or date range —</label>
    <label>From: <input type="date" value="2026-01-01"></label>
    <label>To: <input type="date" value="2026-09-07"></label>
    <select>
      <option>All Types</option>
      <option>Bookings</option>
      <option>Catering Orders</option>
      <option>Venue Hire</option>
    </select>
    <button class="btn btn-primary btn-sm">Apply Filters</button>
    <button class="btn btn-ghost btn-sm">Reset</button>
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
          <th>TXN ID</th>
          <th>DATE</th>
          <th>CLIENT</th>
          <th>VENUE</th>
          <th>TYPE</th>
          <th>AMOUNT</th>
          <th>STATUS</th>
          <th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="font-bold text-primary">#TXN-0091</td>
          <td>Sep 06, 2026</td>
          <td>Jane Doe Events</td>
          <td>Grand Ballroom</td>
          <td><span class="pill pill-in-progress">Booking</span></td>
          <td class="font-bold">$4,890.00</td>
          <td><span class="pill pill-confirmed">PAID</span></td>
          <td><button class="btn btn-ghost btn-sm" onclick="showTransactionModal('TXN-0091', 'Sep 06, 2026', 'Jane Doe Events', 'Grand Ballroom', 'Booking Reservation', '$4,890.00', 'Visa •••• 4242', 'ch_3M4o9281a9', 'PAID')">View</button></td>
        </tr>
        <tr>
          <td class="font-bold text-primary">#TXN-0090</td>
          <td>Sep 05, 2026</td>
          <td>TechCorp Global</td>
          <td>Sky Terrace</td>
          <td><span class="pill pill-in-progress">Booking</span></td>
          <td class="font-bold">$8,400.00</td>
          <td><span class="pill pill-pending">PENDING</span></td>
          <td><a href="admin-booking-approval.php" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
        <tr>
          <td class="font-bold text-primary">#TXN-0089</td>
          <td>Sep 04, 2026</td>
          <td>Luxe Media Inc.</td>
          <td>Riverside Suite</td>
          <td><span class="pill pill-in-progress">Booking</span></td>
          <td class="font-bold">$3,450.00</td>
          <td><span class="pill pill-confirmed">PAID</span></td>
          <td><button class="btn btn-ghost btn-sm" onclick="showTransactionModal('TXN-0089', 'Sep 04, 2026', 'Luxe Media Inc.', 'Riverside Suite', 'Executive Booking', '$3,450.00', 'Mastercard •••• 8812', 'ch_9Kp2841v9', 'PAID')">View</button></td>
        </tr>
        <tr>
          <td class="font-bold text-primary">#TXN-0088</td>
          <td>Sep 03, 2026</td>
          <td>Artisan Catering</td>
          <td>Grand Ballroom</td>
          <td><span class="pill" style="background:#fef9c3;color:#854d0e;">Catering</span></td>
          <td class="font-bold">$1,850.00</td>
          <td><span class="pill pill-confirmed">PAID</span></td>
          <td><button class="btn btn-ghost btn-sm" onclick="showTransactionModal('TXN-0088', 'Sep 03, 2026', 'Artisan Catering', 'Grand Ballroom', 'Catering Service Package', '$1,850.00', 'Direct ACH Transfer', 'ach_41872911b', 'PAID')">View</button></td>
        </tr>
        <tr>
          <td class="font-bold text-primary">#TXN-0087</td>
          <td>Sep 02, 2026</td>
          <td>Vanguard Financial</td>
          <td>Grand Ballroom</td>
          <td><span class="pill pill-in-progress">Booking</span></td>
          <td class="font-bold">$12,800.00</td>
          <td><span class="pill pill-pending">PENDING</span></td>
          <td><a href="admin-booking-approval.php" class="btn btn-ghost btn-sm">View</a></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="flex-between" style="padding:12px 0 0; font-size:.8rem; color:var(--gray-500);">
    <span>Showing 5 of 347 transactions</span>
    <div class="flex gap-8">
      <a href="#" class="btn btn-ghost btn-sm">‹ Prev</a>
      <a href="#" class="btn btn-primary btn-sm">1</a>
      <a href="#" class="btn btn-ghost btn-sm">2</a>
      <a href="#" class="btn btn-ghost btn-sm">3</a>
      <a href="#" class="btn btn-ghost btn-sm">Next ›</a>
    </div>
  </div>
</div>

<!-- Staff & Caterer Quick Links -->
<div class="grid-2 gap-20 mb-24">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Staff Performance Overview</div>
      <a href="admin-staff-management.php" class="btn btn-outline btn-sm">Manage Staff</a>
    </div>
    <table>
      <thead><tr><th>STAFF</th><th>ROLE</th><th>EVENTS</th><th>ACTION</th></tr></thead>
      <tbody>
        <tr>
          <td class="font-semibold">Sarah Jenkins</td>
          <td>Lead Coordinator</td>
          <td>14 events</td>
          <td><a href="admin-staff-management.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <tr>
          <td class="font-semibold">David Chen</td>
          <td>AV Engineer</td>
          <td>11 events</td>
          <td><a href="admin-staff-management.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <tr>
          <td class="font-semibold">Rachel Miller</td>
          <td>Logistics Coord.</td>
          <td>9 events</td>
          <td><a href="admin-staff-management.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
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
        <tr>
          <td class="font-semibold">Artisan Catering</td>
          <td>42</td>
          <td class="font-bold text-success">$77,700</td>
          <td><a href="admin-review-caterer.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <tr>
          <td class="font-semibold">Epicurean Events</td>
          <td>28</td>
          <td class="font-bold text-success">$51,800</td>
          <td><a href="admin-review-caterer.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
        <tr>
          <td class="font-semibold">Boutique Bites</td>
          <td>18</td>
          <td class="font-bold text-success">$17,100</td>
          <td><a href="admin-review-caterer.php" class="btn btn-ghost btn-sm">View Details</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Bookings Summary -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Generated Bookings Summary</div>
    <a href="admin-pending-bookings.php" class="btn btn-outline btn-sm">View All Bookings</a>
  </div>
  <div class="grid-4 gap-16 mb-16">
    <div style="padding:16px; background:var(--gray-50); border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:var(--primary);">347</div>
      <div class="text-xs text-muted">Total Generated</div>
    </div>
    <div style="padding:16px; background:#dcfce7; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#16a34a;">289</div>
      <div class="text-xs text-muted">Confirmed</div>
    </div>
    <div style="padding:16px; background:#fef9c3; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#d97706;">38</div>
      <div class="text-xs text-muted">Pending Approval</div>
    </div>
    <div style="padding:16px; background:#fee2e2; border-radius:var(--radius-sm); text-align:center;">
      <div style="font-size:1.5rem; font-weight:800; color:#dc2626;">20</div>
      <div class="text-xs text-muted">Rejected / Cancelled</div>
    </div>
  </div>
  <!-- Top venues bar visual -->
  <div style="margin-top:8px;">
    <div class="flex-between text-sm mb-4"><span class="font-semibold">Grand Emerald Ballroom</span><span>142 bookings — $134,800</span></div>
    <div class="progress-bar mb-12"><div class="progress-fill" style="width:82%;"></div></div>
    <div class="flex-between text-sm mb-4"><span class="font-semibold">Skyline Vista Lounge</span><span>98 bookings — $68,200</span></div>
    <div class="progress-bar mb-12"><div class="progress-fill" style="width:56%;"></div></div>
    <div class="flex-between text-sm mb-4"><span class="font-semibold">Crystal Tech Pavilion</span><span>64 bookings — $32,500</span></div>
    <div class="progress-bar"><div class="progress-fill" style="width:37%;"></div></div>
  </div>
</div>

      </main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
        <div class="footer-links">
          <a href="admin-policy-management.php">✎ Policy &amp; Legal Editor</a>
        </div>
      </footer>
    </div>
  </div>

  <!-- Transaction Details Modal -->
  <div id="txn-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div class="card" style="max-width:560px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:var(--shadow-lg); margin:0;">
      <div class="flex-between mb-16" style="border-bottom:1px solid var(--gray-200); padding-bottom:12px;">
        <div class="flex gap-8" style="align-items:center;">
          <h3 style="margin:0;">Transaction Details</h3>
          <span id="txn-modal-id" class="font-bold text-primary" style="font-size:1.05rem;">#TXN-0091</span>
        </div>
        <button type="button" onclick="closeTxnModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:var(--gray-500); line-height:1;">&times;</button>
      </div>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; background:var(--gray-50); padding:12px 16px; border-radius:var(--radius-sm);">
        <div>
          <div class="text-xs text-muted font-bold">PAYMENT STATUS</div>
          <span class="pill pill-confirmed" id="txn-modal-status" style="margin-top:4px; display:inline-block;">PAID IN FULL ✓</span>
        </div>
        <div style="text-align:right;">
          <div class="text-xs text-muted font-bold">TOTAL AMOUNT</div>
          <div id="txn-modal-amount" style="font-size:1.4rem; font-weight:800; color:var(--navy-900);">$4,890.00</div>
        </div>
      </div>

      <div class="grid-2 gap-16 mb-20">
        <div>
          <div class="text-xs text-muted font-bold mb-4">CLIENT / CUSTOMER</div>
          <div id="txn-modal-client" class="font-bold">Jane Doe Events</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">TRANSACTION DATE</div>
          <div id="txn-modal-date" class="font-medium">Sep 06, 2026</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">VENUE / SERVICE</div>
          <div id="txn-modal-venue" class="font-medium">Grand Ballroom</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">TRANSACTION TYPE</div>
          <div id="txn-modal-type" class="font-medium">Booking Reservation</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">PAYMENT METHOD</div>
          <div id="txn-modal-method" class="font-medium">Visa •••• 4242</div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">GATEWAY REFERENCE</div>
          <div id="txn-modal-ref" class="font-mono text-xs text-muted">ch_3M4o9281a9</div>
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
          <span class="text-success font-semibold">Cleared &amp; Disbursed</span>
        </div>
        <div class="flex-between text-sm">
          <span>Merchant Account</span>
          <span class="font-mono text-xs">VenuePro Operating LLC (**892)</span>
        </div>
      </div>

      <div class="flex gap-12">
        <button type="button" class="btn btn-ghost" style="flex:1;" onclick="closeTxnModal()">Close</button>
        <a href="../customer/client-invoice.php" class="btn btn-outline" style="flex:1; text-align:center; justify-content:center;">View Official Tax Invoice</a>
        <button type="button" class="btn btn-primary" style="flex:1;" onclick="window.print()">Print Receipt</button>
      </div>
    </div>
  </div>

  <script>
    function showTransactionModal(id, date, client, venue, type, amount, method, ref, status) {
      document.getElementById('txn-modal-id').innerText = '#' + id;
      document.getElementById('txn-modal-date').innerText = date;
      document.getElementById('txn-modal-client').innerText = client;
      document.getElementById('txn-modal-venue').innerText = venue;
      document.getElementById('txn-modal-type').innerText = type;
      document.getElementById('txn-modal-amount').innerText = amount;
      document.getElementById('txn-modal-method').innerText = method;
      document.getElementById('txn-modal-ref').innerText = ref;
      document.getElementById('txn-modal-status').innerText = status + ' IN FULL ✓';
      document.getElementById('txn-modal').style.display = 'flex';
    }

    function closeTxnModal() {
      document.getElementById('txn-modal').style.display = 'none';
    }
  </script>
<script src="../js/app.js"></script>
</body>
</html>