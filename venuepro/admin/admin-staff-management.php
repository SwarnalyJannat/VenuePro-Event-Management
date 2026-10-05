<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
$stmtS = $db->prepare(
    "SELECT u.*, sp.staff_code, sp.department, sp.active_status, sp.assigned_venues,
     COUNT(sa.id) AS assigned_events
     FROM users u
     LEFT JOIN staff_profiles sp ON u.id = sp.user_id
     LEFT JOIN staff_assignments sa ON u.id = sa.staff_id
     WHERE u.role = 'staff' AND u.status != 'inactive'
     GROUP BY u.id ORDER BY u.name"
);
$stmtS->execute();
$staffMembers = $stmtS->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>VenuePro Admin – Staff Directory</title>
    <link rel="stylesheet" href="../css/style.css" />
  </head>
  <body>
    <input type="checkbox" id="sidebar-toggle" />
    <div class="app-shell">
      <aside class="sidebar" id="main-sidebar">
        <div class="sidebar-logo">
          <img
            src="../assets/logo.png"
            alt="VenuePro"
            class="sidebar-logo-img"
          />
          <div class="sidebar-logo-text">VenuePro</div>
          <div></div>
        </div>
        <nav class="sidebar-nav">
          <div class="nav-label">Governance</div>
          <a href="admin-dashboard.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="3" width="7" height="7" />
              <rect x="14" y="3" width="7" height="7" />
              <rect x="14" y="14" width="7" height="7" />
              <rect x="3" y="14" width="7" height="7" />
            </svg>
            Dashboard
          </a>
          <a href="admin-pending-bookings.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="4" width="18" height="18" rx="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            Bookings Approvals
          </a>
          <a href="venue-management.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            </svg>
            Venue Catalog
          </a>
          <a href="admin-catering-management.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M18 8h1a4 4 0 0 1 0 8h-1" />
              <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z" />
              <line x1="6" y1="1" x2="6" y2="4" />
              <line x1="10" y1="1" x2="10" y2="4" />
              <line x1="14" y1="1" x2="14" y2="4" />
            </svg>
            Catering Oversight
          </a>
          <a href="admin-staff-management.php" class="nav-item active">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            Staff Directory
          </a>
          <a href="admin-reports.php" class="nav-item">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <line x1="18" y1="20" x2="18" y2="10" />
              <line x1="12" y1="20" x2="12" y2="4" />
              <line x1="6" y1="20" x2="6" y2="14" />
            </svg>
            Reports &amp; Analytics
          </a>
        </nav>
        <div class="sidebar-footer">
          <a
            href="../venues.php"
            class="nav-item"
            style="color: var(--gray-400)"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            Log out
          </a>
        </div>
      </aside>

      <div class="main-content">
        <header class="topbar">
          <label
            for="sidebar-toggle"
            class="sidebar-toggle-btn"
            title="Toggle Sidebar"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </label>
          <div class="topbar-search">
            <span class="topbar-search-icon">🔍</span>
            <input
              type="text"
              placeholder="Search bookings, venues, staff, caterers..."
            />
          </div>
          <div class="topbar-actions">
            <a
              href="notification-center.php"
              class="topbar-icon-btn"
              title="Notifications"
            >
              <span class="badge">8</span>
              🔔
            </a>
            <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
              <div class="user-avatar" style="background: #0f172a"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
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
              <h1>Staff Directory & Management</h1>
              <!-- <p>
                Active coordinators, AV technicians, and on-site logistics crew.
              </p> -->
            </div>
            <a href="admin-add-staff.php" class="btn btn-primary"
              >+ Add New Staff Member</a
            >
          </div>

          <div class="card">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>MEMBER</th>
                    <th>ASSIGNED VENUES</th>
                    <th>EMAIL</th>
                    <th>ACTIONS</th>
                  </tr>
                </thead>
                <tbody>
<?php
// Build venue ID→name lookup
$venueMap = [];
foreach ($db->query("SELECT id, name FROM venues ORDER BY name") as $vr) {
    $venueMap[(int)$vr['id']] = $vr['name'];
}
?>
<?php if (empty($staffMembers)): ?>
<tr><td colspan="4" style="text-align:center;padding:32px;color:var(--gray-400);">No staff members found.</td></tr>
<?php else: foreach ($staffMembers as $s):
  // Resolve assigned venue IDs to names
  $venueNames = [];
  foreach (explode(',', $s['assigned_venues'] ?? '') as $vid) {
      $vid = (int)trim($vid);
      if ($vid > 0 && isset($venueMap[$vid])) {
          $venueNames[] = $venueMap[$vid];
      }
  }
?>
<tr id="staff-row-<?= (int)$s['id'] ?>">
  <td>
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;"><?= e(strtoupper(substr($s['name'],0,2))) ?></div>
      <div>
        <div class="font-semibold"><?= e($s['name']) ?></div>
        <div style="font-size:0.78rem;color:var(--gray-500);"><?= e($s['staff_code'] ?? 'N/A') ?> · <?= e($s['department'] ?? 'Operations') ?></div>
      </div>
    </div>
  </td>
  <td>
    <?php if (empty($venueNames)): ?>
    <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
    <?php else: foreach ($venueNames as $vn): ?>
    <span style="display:inline-block;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;border-radius:4px;padding:2px 7px;font-size:0.74rem;font-weight:600;margin:2px;"><?= e($vn) ?></span>
    <?php endforeach; endif; ?>
  </td>
  <td><?= e($s['email']) ?></td>
  <td>
    <a href="admin-edit-staff.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm" style="padding:4px 10px;font-size:0.78rem;">Edit</a>
    <button onclick="deleteStaff(<?= (int)$s['id'] ?>, '<?= e(addslashes($s['name'])) ?>')" class="btn btn-sm" style="padding:4px 10px;font-size:0.78rem;background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;cursor:pointer;border-radius:6px;margin-left:4px;">Remove</button>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody>


              </table>
            </div>
          </div>
        </main>
              <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration.</div>
      </footer>
      </div>
    </div>

  <script src="../js/app.js"></script>
<script>
async function deleteStaff(id, name) {
  if (!confirm('Remove staff member "' + name + '"? This cannot be undone.')) return;
  var row = document.getElementById('staff-row-' + id);
  try {
    var res = await fetch('../api/staff.php?action=delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ id: id })
    });
    var d = await res.json();
    if (d.success) {
      if (row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity .3s';
        setTimeout(function() { row.remove(); }, 320);
        
      }
      var toast = document.createElement('div');
      toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#166534;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;z-index:10000;box-shadow:0 4px 12px rgba(0,0,0,.2);';
      toast.textContent = '✓ ' + name + ' removed successfully.';
      document.body.appendChild(toast);
      setTimeout(function() { toast.remove(); }, 3000);
    } else {
      alert(d.message || 'Failed to remove staff member.');
    }
  } catch(e) {
    alert('Connection error. Please try again.');
  }
}
</script>
</body>
</html>
