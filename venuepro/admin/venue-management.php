<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
$stmtV = $db->prepare("SELECT v.*, COUNT(DISTINCT b.id) AS booking_count,
    COALESCE(SUM(CASE WHEN b.booking_status IN ('confirmed','pending') THEN 1 ELSE 0 END),0) AS active_bookings,
    (SELECT COUNT(*) FROM venue_photos vp WHERE vp.venue_id = v.id) AS photo_count
    FROM venues v
    LEFT JOIN bookings b ON v.id = b.venue_id
    GROUP BY v.id ORDER BY v.id");
$stmtV->execute();
$venues = $stmtV->fetchAll();
$totalVenues = count($venues);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Venue Management</title>
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
        <a href="admin-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Staff Directory
        </a>
        <a href="admin-reports.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
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
          <input type="text" placeholder="Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
        <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Admin')) ?></div>
            </div>
          </a>
        </div>
      </header>
      <main class="page-body">
<div class="flex-between mb-24">
  <div>
    <h1>Venue Management Dashboard</h1>
    <p>Manage all enterprise properties, pricing rules, and booking availability.</p>
  </div>
  <a href="add-new-venue.php" class="btn btn-primary">+ Add New Venue</a>
</div>

<?php
$stmtActB = $db->query("SELECT COUNT(*) FROM bookings WHERE booking_status IN ('confirmed','pending')");
$totalActiveBookings = (int)$stmtActB->fetchColumn();
$stmtGross = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status != 'cancelled'");
$totalGross = (float)$stmtGross->fetchColumn();
?>
<!-- 
<div class="stats-grid mb-24">
  <div class="stat-card">
    <div class="stat-label">Total Properties</div>
    <div class="stat-value"><?= $totalVenues ?></div>
    <span class="stat-badge positive">All verified</span>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Bookings</div>
    <div class="stat-value"><?= $totalActiveBookings ?></div>
    <span class="stat-badge positive">Current volume</span>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Average Occupancy</div>
    <div class="stat-value">74.5%</div>
    <span class="stat-badge positive">+6.2% vs last month</span>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Volume</div>
    <div class="stat-value">$<?= number_format($totalGross, 0) ?></div>
    <span class="stat-badge neutral">All-time</span>
  </div>
</div> -->

<div class="grid-2 gap-20">
  <?php if (empty($venues)): ?>
    <div class="card" style="grid-column: 1 / -1; text-align:center; padding:48px; color:var(--gray-400);">
      No venues found in inventory.
    </div>
  <?php else: foreach ($venues as $v):
    $occ = min(98, max(20, (int)($v['booking_count'] * 18 + 35)));
    $coverImg = !empty($v['image_url']) ? $v['image_url'] : 'assets/venues pic/images.jpg';
    $displayCover = str_starts_with($coverImg, 'http') ? $coverImg : ('../' . preg_replace('/^(\.\.\/)+/', '', $coverImg));
  ?>
  <div class="card venue-card-item">
    <div style="height:140px; border-radius:6px; overflow:hidden; margin-bottom:14px; background:#0f172a; position:relative;">
      <img src="<?= e($displayCover) ?>" alt="<?= e($v['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
      <span class="pill pill-<?= $v['status'] === 'active' ? 'confirmed' : 'pending' ?>" style="position:absolute; top:8px; left:8px;">
        <?= strtoupper($v['status']) ?> VENUE
      </span>
      <span style="position:absolute; bottom:8px; right:8px; background:rgba(0,0,0,0.7); color:#fff; font-size:0.75rem; font-weight:700; padding:3px 8px; border-radius:4px;">
        📷 <?= (int)($v['photo_count'] ?? 0) ?> Photos
      </span>
    </div>
    <div class="flex-between mb-12">
      <div>
        <h3 class="venue-card-title"><?= e($v['name']) ?></h3>
        <div class="text-xs text-muted"><?= e($v['district'] ?? '') ?> • Max Capacity: <?= (int)$v['capacity'] ?> Guests</div>
      </div>
      <div class="text-right">
        <div class="font-bold text-primary">$<?= number_format((float)$v['base_rate'], 2) ?> / day</div>
        <div class="text-xs text-muted">Bookings: <?= (int)$v['booking_count'] ?></div>
      </div>
    </div>
    <!-- <div class="progress-bar mb-16"><div class="progress-fill" style="width:<?= $occ ?>%;"></div></div> -->
    <div style="display:flex; flex-direction:column; gap:6px;">
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
        <a href="admin-edit-venue.php?id=<?= $v['id'] ?>" class="btn btn-outline btn-sm" style="justify-content:center; text-align:center;">Edit Specs &amp; Cover</a>
        <a href="admin-edit-venue.php?id=<?= $v['id'] ?>#gallery-management" class="btn btn-primary btn-sm" style="justify-content:center; text-align:center;">📸 Manage Photos</a>
      </div>
      <button onclick="deleteVenue(<?= (int)$v['id'] ?>, this)" class="btn btn-sm btn-full" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;cursor:pointer;border-radius:6px;padding:6px 12px;font-size:0.8rem;width:100%;">🗑 Remove Venue</button>
    </div>
  </div>
  <?php endforeach; endif; ?>
</div>
</main>
      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
<script>
async function deleteVenue(id, btn) {
  if (!confirm('Remove this venue from the catalog? It will be set to inactive.')) return;
  btn.disabled = true; btn.textContent = 'Removing...';
  try {
    var res = await fetch('../api/venues.php?action=delete', {
      method: 'POST', headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: id })
    });
    var d = await res.json();
    if (d.success) {
      var card = btn.closest('.venue-card-item, .card');
      if (card) { card.style.opacity='0'; card.style.transition='opacity .3s'; setTimeout(function(){ card.remove(); }, 320); }
    } else {
      alert(d.message || 'Failed to remove venue.');
      btn.disabled = false; btn.textContent = '🗑 Remove Venue';
    }
  } catch(e) {
    alert('Connection error.');
    btn.disabled = false; btn.textContent = '🗑 Remove Venue';
  }
}
</script>
</body>
</html>
