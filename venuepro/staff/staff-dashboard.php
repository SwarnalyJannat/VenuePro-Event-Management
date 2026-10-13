<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('staff', 'staff-login.php');
$db = getDBConnection();
?>
<?php
$staffUser = $currentUser;
$today = date('Y-m-d');
$staffId = (int)$staffUser['id'];

// Retrieve staff profile and admin-assigned venues
$stmtProf = $db->prepare("SELECT sp.assigned_venues, sp.staff_code, sp.department FROM staff_profiles sp WHERE sp.user_id = ? LIMIT 1");
$stmtProf->execute([$staffId]);
$staffProfile = $stmtProf->fetch();

$assignedVenueIds = [];
if (!empty($staffProfile['assigned_venues'])) {
    foreach (explode(',', $staffProfile['assigned_venues']) as $vId) {
        $vId = (int)trim($vId);
        if ($vId > 0) {
            $assignedVenueIds[] = $vId;
        }
    }
    $assignedVenueIds = array_values(array_unique($assignedVenueIds));
}

$assignedVenues = [];
$assignments = [];
$completedCount = 0;
$pendingTasks = 0;
$totalEvents = 0;
$readinessPct = 100;

if (!empty($assignedVenueIds)) {
    $inPlaceholders = implode(',', array_fill(0, count($assignedVenueIds), '?'));

    // Fetch assigned venues metadata
    $stmtVenues = $db->prepare("SELECT id, name, address, capacity, district FROM venues WHERE id IN ($inPlaceholders) AND status = 'active' ORDER BY name ASC");
    $stmtVenues->execute($assignedVenueIds);
    $assignedVenues = $stmtVenues->fetchAll(PDO::FETCH_ASSOC);

    // Fetch active assignments/events strictly for assigned venues
    $sqlAssign = "
        SELECT 
            b.id AS booking_id, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, b.booking_status,
            v.id AS venue_id, v.name AS venue_name,
            u.name AS customer_name,
            sa.id AS id, sa.staff_id, sa.setup_status, sa.completion_pct, sa.checklist_json
        FROM bookings b
        JOIN venues v ON b.venue_id = v.id
        JOIN users u ON b.customer_id = u.id
        LEFT JOIN staff_assignments sa ON sa.booking_id = b.id
        WHERE b.venue_id IN ($inPlaceholders)
          AND b.booking_status IN ('confirmed', 'completed')
        ORDER BY b.event_date ASC, b.id ASC LIMIT 20
    ";
    $stmtAssign = $db->prepare($sqlAssign);
    $stmtAssign->execute($assignedVenueIds);
    $assignments = $stmtAssign->fetchAll(PDO::FETCH_ASSOC);

    // Auto-create assignment record if a confirmed booking doesn't have one yet
    foreach ($assignments as &$a) {
        if (empty($a['id'])) {
            $shiftTime = (!empty($a['start_time']) ? substr($a['start_time'], 0, 5) : '10:00') . ' - ' . (!empty($a['end_time']) ? substr($a['end_time'], 0, 5) : '18:00');
            $ins = $db->prepare("INSERT INTO staff_assignments (booking_id, staff_id, shift_time, setup_status, completion_pct, checklist_json) VALUES (?, ?, ?, 'pending', 0, '{}')");
            $ins->execute([$a['booking_id'], $staffId, $shiftTime]);
            $a['id'] = (int)$db->lastInsertId();
            $a['staff_id'] = $staffId;
            $a['setup_status'] = 'pending';
            $a['completion_pct'] = 0;
            $a['checklist_json'] = '{}';
        }
    }
    unset($a);

    // Strict scoped stats for assigned venues
    $sqlCompleted = "
        SELECT COUNT(*) 
        FROM staff_assignments sa 
        JOIN bookings b ON sa.booking_id = b.id 
        WHERE b.venue_id IN ($inPlaceholders)
          AND b.booking_status IN ('confirmed', 'completed')
          AND (sa.setup_status = 'completed' OR sa.completion_pct >= 100)
    ";
    $stmtCompleted = $db->prepare($sqlCompleted);
    $stmtCompleted->execute($assignedVenueIds);
    $completedCount = (int)$stmtCompleted->fetchColumn();

    $sqlPending = "
        SELECT COUNT(*) 
        FROM staff_assignments sa 
        JOIN bookings b ON sa.booking_id = b.id 
        WHERE b.venue_id IN ($inPlaceholders)
          AND b.booking_status IN ('confirmed', 'completed')
          AND (sa.setup_status != 'completed' AND COALESCE(sa.completion_pct, 0) < 100)
    ";
    $stmtPending = $db->prepare($sqlPending);
    $stmtPending->execute($assignedVenueIds);
    $pendingTasks = (int)$stmtPending->fetchColumn();

    $totalEvents = count($assignments);
    $readinessPct = ($totalEvents > 0 && ($completedCount + $pendingTasks) > 0)
        ? (int)round(($completedCount / ($completedCount + $pendingTasks)) * 100)
        : 100;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Staff – Staff Dashboard</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
/* CSS-only "select event from table" using radio buttons */
.event-row input[type=radio] { display:none; }
.event-row label {
  display: grid;
  grid-template-columns: 28px 1fr 1fr 80px 120px 140px;
  align-items: center; gap: 0 12px;
  padding: 12px 14px; cursor: pointer;
  border-bottom: 1px solid var(--gray-100);
  transition: background .15s;
}
.event-row label:hover { background: var(--gray-50); }
.event-row input[type=radio]:checked ~ label {
  background: #eff6ff; border-left: 3px solid var(--primary);
}
.event-row .radio-dot {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid var(--gray-400);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.event-row input[type=radio]:checked ~ label .radio-dot {
  border-color: var(--primary); background: var(--primary);
}
.event-row input[type=radio]:checked ~ label .radio-dot::after {
  content: ''; width: 6px; height: 6px; border-radius: 50%; background: #fff;
}
/* Task checklist CSS toggle */
.task-item { display: flex; align-items: center; gap: 0; }
.task-item input[type=checkbox] { display:none; }
.task-item label {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; cursor: pointer; flex: 1;
  border-radius: var(--radius-sm); border: 1.5px solid var(--gray-200);
  margin-bottom: 8px; background: #fff; font-size: .875rem;
  transition: all .15s;
}
.task-item label:hover { border-color: var(--primary); }
.task-item input[type=checkbox]:checked ~ label {
  background: #f0fdf4; border-color: #86efac; color: var(--gray-500);
  text-decoration: line-through;
}
.task-check-box {
  width: 20px; height: 20px; border-radius: 4px;
  border: 2px solid var(--gray-300); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem; transition: all .15s;
}
.task-item input[type=checkbox]:checked ~ label .task-check-box {
  background: #16a34a; border-color: #16a34a; color: #fff;
  content: '✓';
}
.task-item input[type=checkbox]:checked ~ label .task-check-box::after { content: '✓'; }
.task-badge { margin-left: auto; }
.priority-high { color: #dc2626; font-size: .7rem; font-weight: 700; }
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
        <div class="nav-label">Coordination</div>
        <a href="staff-dashboard.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Staff Dashboard
        </a>
        <a href="staff-event-setup.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg> Event Setup Detail
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
          <input type="text" placeholder="Search assigned tasks, venues, clients...">
        </div>
        <div class="topbar-actions">
          <a href="staff-notifications.php" class="topbar-icon-btn" title="Notifications">
            <?= renderNotifBadge() ?>
            🔔
          </a>
          <a href="staff-chat.php" class="topbar-icon-btn" title="Customer Chat">
            💬
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#7c3aed;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'staff')) ?></div>
            </div>
          </div>
        </div>
      </header>
      <main class="page-body">

<div class="flex-between mb-24">
  <div>
    <h1>Staff Coordination Hub</h1>
    <p>Daily shift assignments, event schedules, and interactive setup checklists for your assigned properties.</p>
  </div>
</div>

<!-- Admin-Assigned Operational Venues Info Strip -->
<div class="card mb-24" style="padding: 16px 20px; background: linear-gradient(to right, #f8fafc, #eff6ff); border: 1.5px solid #bfdbfe;">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
    <div style="display:flex; align-items:center; gap:12px;">
      <span style="font-size:1.6rem;">🏛️</span>
      <div>
        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:700; color:var(--primary);">
          Admin-Assigned Operational Properties (<?= count($assignedVenues) ?>)
        </div>
        <div style="font-size:0.875rem; color:var(--navy-900); font-weight:600; margin-top:2px;">
          <?php if (empty($assignedVenues)): ?>
            <span style="color:#dc2626; font-weight:500;">No operational properties assigned by administrator yet.</span>
          <?php else: ?>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:4px;">
              <?php foreach ($assignedVenues as $av): ?>
                <span class="badge" style="background:#fff; border:1px solid #93c5fd; color:#1e40af; font-size:0.8rem; padding:4px 10px; border-radius:14px; font-weight:600;">
                  📍 <?= e($av['name']) ?><?= !empty($av['district']) ? ' ('.e($av['district']).')' : '' ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div>
      <a href="staff-event-setup.php" class="btn btn-primary btn-sm">Open Venue Setup Tasklists →</a>
    </div>
  </div>
</div>

<?php if (empty($assignedVenueIds)): ?>
<div class="card mb-24" style="background:#fffbeb; border:1.5px solid #fde68a; padding:18px 24px; border-radius:var(--radius);">
  <div style="display:flex; align-items:center; gap:12px;">
    <span style="font-size:1.8rem;">⚠️</span>
    <div>
      <div style="font-weight:700; color:#b45309; font-size:1rem;">No Operational Venues Assigned</div>
      <p style="margin:4px 0 0; color:#92400e; font-size:0.875rem;">
        An administrator has not assigned any operational venues to your staff profile yet. Your dashboard and tasklists are strictly scoped to your assigned properties. Please contact an admin to assign venues to your account in the Staff Directory.
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="stats-grid mb-24">
  <div class="stat-card"><div class="stat-label">Assigned Events</div><div class="stat-value"><?= $totalEvents ?></div></div>
  <div class="stat-card orange"><div class="stat-label">Open Tasks</div><div class="stat-value"><?= $pendingTasks ?></div></div>
  <div class="stat-card green"><div class="stat-label">Completed Setups</div><div class="stat-value"><?= $completedCount ?></div></div>
  <div class="stat-card"><div class="stat-label">Readiness Rate</div><div class="stat-value"><?= $readinessPct ?>%</div></div>
</div>

<!-- Event Schedule Table with selectable rows -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Assigned Event Schedule &amp; Setup Status</div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>EVENT NAME</th>
          <th>VENUE</th>
          <th>CALL TIME</th>
          <th>SETUP PROGRESS</th>
          <th>STATUS</th>
          <th>QUICK ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
        <tr>
          <td colspan="6" style="text-align:center; padding:32px 20px; color:var(--gray-500);">
            <div style="font-size:1.6rem; margin-bottom:6px;">📋</div>
            <?php if (empty($assignedVenueIds)): ?>
              <strong>No operational venues assigned to your staff profile.</strong>
              <div class="text-xs text-muted" style="margin-top:4px;">Contact an administrator to assign venues in the Staff Directory.</div>
            <?php else: ?>
              <strong>No active event shifts scheduled at your assigned properties at this time.</strong>
              <div class="text-xs text-muted" style="margin-top:4px;">As soon as reservations are confirmed by administrators for your assigned venues, they will appear here.</div>
            <?php endif; ?>
          </td>
        </tr>
        <?php else: foreach ($assignments as $a): 
          $callTime = substr($a['start_time'], 0, 5);
          $isDone = ($a['setup_status'] === 'completed' || (int)($a['completion_pct'] ?? 0) >= 100);
          $pct = $isDone ? 100 : (int)($a['completion_pct'] ?? 0);
          $stClass  = $isDone ? 'confirmed' : (($a['setup_status'] === 'in_progress') ? 'in-progress' : 'pending');
          $stLabel  = $isDone ? 'READY' : (($a['setup_status'] === 'in_progress') ? 'IN SETUP' : 'UPCOMING');
        ?>
        <tr>
          <td>
            <div class="font-semibold"><?= e($a['event_name']) ?></div>
            <div class="text-xs text-muted">Client: <?= e($a['customer_name']) ?> · <?= date('M j, Y', strtotime($a['event_date'])) ?> · <strong style="color:var(--navy-900);"><?= number_format((int)($a['guest_count'] ?? 0)) ?></strong> Guests</div>
          </td>
          <td><?= e($a['venue_name']) ?></td>
          <td class="font-semibold"><?= $callTime ?></td>
          <td style="min-width:140px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <div style="flex:1; height:8px; background:var(--gray-200); border-radius:4px; overflow:hidden;">
                <div style="width:<?= $pct ?>%; height:100%; background:<?= $isDone ? '#16a34a' : 'var(--primary)' ?>; transition:width .3s;"></div>
              </div>
              <span style="font-size:0.8rem; font-weight:700; color:<?= $isDone ? '#16a34a' : 'inherit' ?>;"><?= $pct ?>%</span>
            </div>
          </td>
          <td><span class="pill pill-<?= $stClass ?>"><?= $stLabel ?></span></td>
          <td><a href="staff-event-setup.php?booking_id=<?= $a['booking_id'] ?>" class="btn btn-outline btn-sm"><?= $isDone ? 'View Report' : 'Open Setup' ?></a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

      </main>
                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>