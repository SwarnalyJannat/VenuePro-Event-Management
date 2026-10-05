<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
// --- Live admin dashboard stats ---
$todayDate    = date('Y-m-d');

// Today's bookings
$stmtToday    = $db->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?");
$stmtToday->execute([$todayDate]);
$todayBookings = (int)$stmtToday->fetchColumn();

// Last 7 days bookings
$stmtThisWeek = $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$thisWeekBookings = (int)$stmtThisWeek->fetchColumn();

// Pending bookings
$stmtPending  = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status = 'pending'");
$pendingCount = (int)$stmtPending->fetchColumn();

// Gross revenue & confirmed revenue
$stmtRev      = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status != 'cancelled'");
$revMTD       = (float)$stmtRev->fetchColumn();

$stmtConf     = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE booking_status IN ('confirmed','completed')");
$confirmedRev = (float)$stmtConf->fetchColumn();
$confPct      = $revMTD > 0 ? round(($confirmedRev / $revMTD) * 100) : 0;

// Active events today
$stmtActive   = $db->prepare("SELECT COUNT(*) FROM bookings WHERE event_date = ? AND booking_status IN ('confirmed','pending')");
$stmtActive->execute([$todayDate]);
$activeEvents = (int)$stmtActive->fetchColumn();

// Upcoming events in next 30 days
$stmtUpcoming = $db->query("SELECT COUNT(*) FROM bookings WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND booking_status IN ('confirmed','pending')");
$upcoming30d  = (int)$stmtUpcoming->fetchColumn();

// Total active properties
$totalVenues  = (int)$db->query("SELECT COUNT(*) FROM venues WHERE status = 'active'")->fetchColumn();

// Recent bookings
$stmtRecent   = $db->query("SELECT b.*, u.name AS customer_name, v.name AS venue_name FROM bookings b JOIN users u ON b.customer_id=u.id JOIN venues v ON b.venue_id=v.id ORDER BY b.created_at DESC LIMIT 8");
$recentBookings = $stmtRecent->fetchAll();

// --- Dynamic Venue Occupancy (bookings per venue / 30-day window) ---
$stmtOcc = $db->query(
    "SELECT v.id, v.name,
     COUNT(b.id) AS booking_count,
     v.capacity
     FROM venues v
     LEFT JOIN bookings b ON v.id = b.venue_id
       AND b.booking_status IN ('confirmed','pending')
       AND b.event_date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE() + INTERVAL 30 DAY
     WHERE v.status = 'active'
     GROUP BY v.id ORDER BY booking_count DESC, v.name ASC LIMIT 6"
);
$venueOccupancy = $stmtOcc->fetchAll();

// --- Dynamic 6-Month Revenue Trend (Jul - Dec 2026) ---
$currentYear = (int)date('Y');
$monthsToDisplay = [];
for ($m = 7; $m <= 12; $m++) {
    $ym = sprintf('%04d-%02d', $currentYear, $m);
    $monthsToDisplay[$ym] = [
        'ym' => $ym,
        'label' => date('M', strtotime("$ym-01")),
        'full_label' => date('F Y', strtotime("$ym-01")),
        'revenue' => 0.0,
        'count' => 0
    ];
}

$stmtTrend = $db->query("
    SELECT DATE_FORMAT(event_date, '%Y-%m') AS ym,
           COALESCE(SUM(total_amount), 0) AS rev,
           COUNT(*) AS cnt
    FROM bookings
    WHERE booking_status != 'cancelled'
    GROUP BY ym
");
while ($row = $stmtTrend->fetch()) {
    if (isset($monthsToDisplay[$row['ym']])) {
        $monthsToDisplay[$row['ym']]['revenue'] = (float)$row['rev'];
        $monthsToDisplay[$row['ym']]['count'] = (int)$row['cnt'];
    }
}
$maxTrendRev = max(1, ...array_column($monthsToDisplay, 'revenue'));
$peakTrendMonth = '';
foreach ($monthsToDisplay as $m) {
    if ($m['revenue'] >= $maxTrendRev && $m['revenue'] > 0) {
        $peakTrendMonth = $m['label'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
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
        <a href="admin-dashboard.php" class="nav-item active">
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
        <a href="admin-reports.php" class="nav-item">
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
            <span class="badge"><?= $pendingCount ?></span>
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
<div class="page-header">
  <h1 class="page-title">Dashboard Overview</h1>
  <p class="page-subtitle">Live metrics for <?= date('F j, Y') ?> across all <?= $totalVenues ?> active <?= $totalVenues === 1 ? 'property' : 'properties' ?>.</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-badge <?= $todayBookings > 0 ? 'positive' : ($thisWeekBookings > 0 ? 'neutral' : 'neutral') ?>">
      <?= $todayBookings > 0 ? "+$todayBookings today" : ($thisWeekBookings > 0 ? "$thisWeekBookings this week" : "Up to date") ?>
    </span>
    <div class="stat-label">Today's Bookings</div>
    <div class="stat-value"><?= $todayBookings ?></div>
    <div class="stat-icon">📅</div>
  </div>

  <div class="stat-card orange">
    <span class="stat-badge <?= $pendingCount > 0 ? 'warning' : 'positive' ?>">
      <?= $pendingCount > 0 ? "$pendingCount action required" : "All cleared ✓" ?>
    </span>
    <div class="stat-label">Pending Approvals</div>
    <div class="stat-value"><?= $pendingCount ?></div>
    <div class="stat-icon" style="color:#d97706; background:#fef3c7;">📋</div>
  </div>

  <div class="stat-card green">
    <span class="stat-badge positive"><?= $confPct ?>% confirmed</span>
    <div class="stat-label">Gross Revenue</div>
    <div class="stat-value" title="$<?= number_format($revMTD, 2) ?>"><?= $revMTD >= 1000 ? ("$" . number_format($revMTD / 1000, 1) . "k") : ("$" . number_format($revMTD, 2)) ?></div>
    <div class="stat-icon" style="color:#059669; background:#d1fae5;">💵</div>
  </div>

  <div class="stat-card">
    <span class="stat-badge <?= $activeEvents > 0 ? 'positive' : 'neutral' ?>">
      <?= $activeEvents > 0 ? 'In-Progress' : ($upcoming30d > 0 ? "$upcoming30d upcoming" : "None today") ?>
    </span>
    <div class="stat-label">Active Events Today</div>
    <div class="stat-value"><?= $activeEvents ?></div>
    <div class="stat-icon">🏛️</div>
  </div>
</div>

<div class="grid-2 mb-24" style="grid-template-columns: 2fr 1fr;">
  <!-- Revenue Growth Trend Chart — 100% Dynamic from DB -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Revenue Growth Trend</div>
        <span class="text-xs text-muted">Monthly confirmed &amp; projected revenue volume</span>
      </div>
      <span class="stat-badge neutral">Jul – Dec <?= date('Y') ?></span>
    </div>
    <div class="bar-chart" style="margin-top:20px;">
      <?php foreach ($monthsToDisplay as $m):
        $pct = $m['revenue'] > 0 ? max(8, round(($m['revenue'] / $maxTrendRev) * 100)) : 4;
        $isPeak = ($m['revenue'] > 0 && $m['revenue'] >= $maxTrendRev);
        $valDisplay = $m['revenue'] > 0 ? ('$' . ($m['revenue'] >= 1000 ? number_format($m['revenue'] / 1000, 1) . 'k' : round($m['revenue']))) : '$0';
      ?>
      <div class="bar-col">
        <span style="font-size:0.65rem; color:<?= $m['revenue'] > 0 ? 'var(--navy-900)' : 'var(--gray-400)' ?>; margin-bottom:4px; font-weight:600;"><?= $valDisplay ?></span>
        <div class="bar <?= $isPeak ? 'active' : '' ?>" style="height:<?= $pct ?>%;" title="<?= e($m['full_label']) ?>: $<?= number_format($m['revenue'], 2) ?> (<?= $m['count'] ?> booking<?= $m['count'] === 1 ? '' : 's' ?>)"></div>
        <span class="bar-label <?= $isPeak ? 'font-bold' : '' ?>" style="<?= $isPeak ? 'color:var(--primary);' : '' ?>"><?= strtoupper($m['label']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="flex-between text-xs text-muted" style="margin-top:16px; padding-top:12px; border-top:1px solid var(--gray-200);">
      <span>Total 6M Volume: <strong class="text-primary font-bold">$<?= number_format(array_sum(array_column($monthsToDisplay, 'revenue')), 2) ?></strong></span>
      <span>Peak: <strong><?= $peakTrendMonth ?: 'None' ?></strong></span>
    </div>
  </div>

  <!-- Venue Occupancy — Dynamic from DB -->
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Venue Occupancy</div>
        <span class="text-xs text-muted">Active bookings across properties</span>
      </div>
      <span class="stat-badge neutral">±30-Day Window</span>
    </div>
    <div style="display:flex; flex-direction:column; gap:16px; margin-top:8px;">
      <?php if (empty($venueOccupancy)): ?>
      <div style="text-align:center;padding:20px;color:var(--gray-400);">No venue data yet.</div>
      <?php else: foreach ($venueOccupancy as $vo):
        $occ = $vo['booking_count'] > 0
          ? min(100, max(1, (int)round(($vo['booking_count'] / 30) * 100)))
          : 0;
        $barWidth = $vo['booking_count'] > 0 ? max(8, $occ) : 0;
        $occColor = $occ >= 50 ? '#059669' : ($occ >= 20 ? '#2563eb' : ($occ > 0 ? '#3b82f6' : '#e2e8f0'));
      ?>
      <div>
        <div class="flex-between text-sm mb-4">
          <span class="font-semibold"><?= e($vo['name']) ?></span>
          <span class="font-bold text-xs" style="color:<?= $vo['booking_count'] > 0 ? 'var(--navy-900)' : 'var(--gray-400)' ?>;">
            <?= (int)$vo['booking_count'] ?> booking<?= (int)$vo['booking_count'] === 1 ? '' : 's' ?> (<?= $occ ?>%)
          </span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $barWidth ?>%; background:<?= $occColor ?>;"></div></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>


<!-- Recent Booking Requests Table -->
<div class="card">
  <div class="card-header">
    <div class="card-title">Recent Booking Requests</div>
    <a href="admin-pending-bookings.php" class="text-sm font-semibold">View All Requests</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>BOOKING ID</th>
          <th>CUSTOMER</th>
          <th>VENUE</th>
          <th>DATE</th>
          <th>EST. VALUE</th>
          <th>STATUS</th>
          <th>ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentBookings)): ?>
        <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--gray-400);">No booking requests found.</td></tr>
        <?php else: foreach ($recentBookings as $bk):
          $statusClass = $bk['booking_status'] === 'canceled' ? 'cancelled' : strtolower($bk['booking_status']);
          $eventDate   = date('M j, Y', strtotime($bk['event_date']));
          $initials    = strtoupper(substr($bk['customer_name'] ?? 'U', 0, 2));
          $bkCode      = $bk['booking_code'] ?: ('#BK-' . $bk['id']);
        ?>
        <tr>
          <td class="font-bold text-primary"><?= e($bkCode) ?></td>
          <td>
            <div class="flex-center gap-8">
              <div class="user-avatar" style="background:#3b82f6; width:28px; height:28px; font-size:0.7rem; color:#fff; display:inline-flex; align-items:center; justify-content:center; border-radius:50%;"><?= e($initials) ?></div>
              <span><?= e($bk['customer_name']) ?></span>
            </div>
          </td>
          <td><?= e($bk['venue_name']) ?></td>
          <td><?= $eventDate ?></td>
          <td class="font-semibold">$<?= number_format((float)$bk['total_amount'], 2) ?></td>
          <td><span class="pill pill-<?= $statusClass ?>"><?= strtoupper($statusClass) ?></span></td>
          <td><a href="admin-booking-approval.php?booking_id=<?= $bk['id'] ?>" class="btn btn-outline btn-sm">View Details</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>