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

$stmtAssign = $db->prepare(
    "SELECT sa.*, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count,
     v.name AS venue_name, u.name AS customer_name
     FROM staff_assignments sa
     JOIN bookings b ON sa.booking_id = b.id
     JOIN venues v ON b.venue_id = v.id
     JOIN users u ON b.customer_id = u.id
     WHERE sa.staff_id = ?
     ORDER BY b.event_date ASC LIMIT 10"
);
$stmtAssign->execute([$staffUser['id']]);
$assignments = $stmtAssign->fetchAll();

$todayAssignments = array_filter($assignments, function($a) use ($today) {
    return $a['event_date'] === $today;
});

$stmtPending = $db->prepare("SELECT COUNT(*) FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id WHERE sa.staff_id = ? AND sa.setup_status = 'pending' AND b.event_date >= ?");
$stmtPending->execute([$staffUser['id'], $today]);
$pendingTasks = (int)$stmtPending->fetchColumn();
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
            <span class="badge">4</span>
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
    <p>Daily shift assignments, event schedules, and interactive setup checklists.</p>
  </div>
  <!-- <span class="stat-badge positive" style="font-size:.8rem; padding:6px 14px;">📅 Sep 07, 2026 — Shift Active</span> -->
</div>

<div class="stats-grid mb-24">
  <div class="stat-card"><div class="stat-label">Assigned Events Today</div><div class="stat-value"><?= count($todayAssignments) ?></div></div>
  <div class="stat-card orange"><div class="stat-label">Open Tasks</div><div class="stat-value"><?= $pendingTasks ?></div></div>
  <div class="stat-card green"><div class="stat-label">Completed This Week</div><div class="stat-value">12</div></div>
  <div class="stat-card"><div class="stat-label">Hours Logged</div><div class="stat-value">28h</div></div>
</div>

<!-- Event Schedule Table with selectable rows -->
<div class="card mb-24">
  <div class="card-header">
    <div class="card-title">Today's Event Schedule</div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>EVENT NAME</th>
          <th>VENUE</th>
          <th>CALL TIME</th>
          <th>STATUS</th>
          <th>QUICK ACTION</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
        <tr><td colspan="5" style="text-align:center; padding:24px; color:var(--gray-400);">No assigned event shifts at this time.</td></tr>
        <?php else: foreach ($assignments as $a): 
          $callTime = substr($a['start_time'], 0, 5);
          $stClass  = ($a['setup_status'] === 'completed') ? 'confirmed' : (($a['setup_status'] === 'in_progress') ? 'in-progress' : 'pending');
          $stLabel  = ($a['setup_status'] === 'completed') ? 'READY' : (($a['setup_status'] === 'in_progress') ? 'IN SETUP' : 'UPCOMING');
        ?>
        <tr>
          <td>
            <div class="font-semibold"><?= e($a['event_name']) ?></div>
            <div class="text-xs text-muted">Client: <?= e($a['customer_name']) ?> · <?= date('M j, Y', strtotime($a['event_date'])) ?></div>
          </td>
          <td><?= e($a['venue_name']) ?></td>
          <td class="font-semibold"><?= $callTime ?></td>
          <td><span class="pill pill-<?= $stClass ?>"><?= $stLabel ?></span></td>
          <td><a href="staff-event-setup.php?booking_id=<?= $a['booking_id'] ?>" class="btn btn-outline btn-sm">Open Full Setup</a></td>
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