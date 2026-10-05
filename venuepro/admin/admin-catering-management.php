<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
$stmtC = $db->prepare(
    "SELECT u.*, cp.business_name, cp.specialization, cp.approval_status,
     COUNT(DISTINCT co.id) AS active_orders,
     COUNT(DISTINCT pkg.id) AS package_count
     FROM users u
     JOIN caterer_profiles cp ON u.id = cp.user_id
     LEFT JOIN caterer_orders co ON u.id = co.caterer_id AND co.preparation_status != 'Delivered'
     LEFT JOIN catering_packages pkg ON u.id = pkg.caterer_id
     WHERE u.role = 'caterer' AND u.status = 'active' AND cp.approval_status != 'rejected'
     GROUP BY u.id ORDER BY cp.business_name"
);
$stmtC->execute();
$caterers = $stmtC->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Catering Oversight</title>
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
        <a href="venue-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item active">
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'admin')) ?></div>
            </div>
          </a>
        </div>
      </header>
      <main class="page-body">
<div class="flex-between mb-24">
  <div>
    <h1>Catering Management Oversight</h1>
    <p>Certified culinary partners, quality assurance scores, and active kitchen queues.</p>
  </div>
  <a href="admin-add-caterer.php" class="btn btn-primary">+ Add New Caterer</a>
</div>

<?php
$approvedCaterers = [];
$pendingCaterers = [];
foreach ($caterers as $c) {
    if ($c['approval_status'] === 'approved') {
        $approvedCaterers[] = $c;
    } else {
        $pendingCaterers[] = $c;
    }
}
?>

<div>
  <div class="card">
    <div class="flex-between mb-16">
      <h3>Active Certified Partners</h3>
      <span class="pill pill-confirmed"><?= count($approvedCaterers) ?> PARTNERS</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>KITCHEN</th><th>SPECIALTY</th><th>ORDERS</th><th>STATUS</th><th>ACTIONS</th></tr></thead>
        <tbody>
          <?php if (empty($approvedCaterers)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--gray-400);">No approved caterers found.</td></tr>
          <?php else: foreach ($approvedCaterers as $c): ?>
          <tr id="caterer-row-<?= (int)$c['id'] ?>">
            <td><strong><?= e($c['business_name'] ?: $c['name']) ?></strong></td>
            <td><?= e($c['specialization'] ?? 'Fine Dining') ?></td>
            <td><?= (int)$c['active_orders'] ?></td>
            <td><span class="pill pill-confirmed">VERIFIED</span></td>
            <td>
              <div class="flex gap-8">
                <a href="admin-edit-caterer.php?id=<?= (int)$c['id'] ?>" class="btn btn-outline btn-sm" style="padding:4px 10px; font-size:0.78rem;">Edit</a>
                <button onclick="deleteCaterer(<?= (int)$c['id'] ?>, '<?= e(addslashes($c['business_name'] ?: $c['name'])) ?>')" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;cursor:pointer;border-radius:6px;padding:4px 10px;font-size:0.78rem;">Remove</button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- <div class="card">
    <div class="flex-between mb-16">
      <h3>Pending Applications</h3>
      <span class="pill pill-pending"><?= count($pendingCaterers) ?> PENDING</span>
    </div>
    <?php if (empty($pendingCaterers)): ?>
    <div style="text-align:center;padding:24px;color:var(--gray-400);">No pending applications.</div>
    <?php else: foreach ($pendingCaterers as $c): ?>
    <div class="card mb-12" style="background:var(--gray-50);">
      <div class="flex-between mb-4">
        <span class="font-bold"><?= e($c['business_name'] ?: $c['name']) ?></span>
        <a href="admin-review-caterer.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Review App</a>
      </div>
      <div class="text-xs text-muted"><?= e($c['specialization'] ?? '') ?> • <?= e($c['kitchen_address'] ?? 'Kitchen Facility') ?></div>
    </div>
    <?php endforeach; endif; ?>
  </div> -->
</div>
</main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
<script>
async function deleteCaterer(id, name) {
  if (!confirm('Remove caterer "' + name + '" from the platform? This will update the database.')) return;
  try {
    var res = await fetch('../api/caterers.php?action=delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ caterer_id: id })
    });
    var d = await res.json();
    if (d.success) {
      var row = document.getElementById('caterer-row-' + id);
      if (row) { row.style.opacity = '0'; row.style.transition = 'opacity .3s'; setTimeout(function() { row.remove(); }, 320); }
      var toast = document.createElement('div');
      toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#166534;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,.2);';
      toast.textContent = '✓ ' + name + ' removed.';
      document.body.appendChild(toast);
      setTimeout(function() { toast.remove(); }, 3000);
    } else {
      alert(d.message || 'Failed to remove caterer.');
    }
  } catch(e) {
    alert('Connection error.');
  }
}
</script>
</body>
</html>
