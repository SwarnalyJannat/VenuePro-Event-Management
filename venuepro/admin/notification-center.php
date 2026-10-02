<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Notifications</title>
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
        <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'admin')) ?></div>
            </div>
          </div>
        </div>
      </header>
      <main class="page-body">
<?php
$stmtN = $db->prepare("SELECT * FROM notifications WHERE user_id = ? OR type IN ('booking','system') ORDER BY created_at DESC LIMIT 40");
$stmtN->execute([$currentUser['id']]);
$notifRows = $stmtN->fetchAll();
$unreadCount = 0;
foreach ($notifRows as $n) { if (!$n['is_read']) $unreadCount++; }
$typeIcons  = ['booking'=>'📋','payment'=>'💳','message'=>'💬','system'=>'🔔','caterer'=>'🍴'];
$typeColors = ['booking'=>'#2563eb','payment'=>'#2563eb','message'=>'#7c3aed','system'=>'#d97706','caterer'=>'#059669'];
?>
<div class="flex-between mb-24">
  <div>
    <h1>Administrative Notification Center</h1>
    <p>Incoming booking approvals, compliance alerts, and staff scheduling updates.</p>
  </div>
  <button class="btn btn-ghost btn-sm" id="admin-mark-all-read">Mark All as Read</button>
</div>

<div class="card">
  <?php if (empty($notifRows)): ?>
  <div style="text-align:center; padding:48px; color:var(--gray-400);">
    <div style="font-size:3rem; margin-bottom:12px;">🔔</div>
    <p>No administrative alerts at this time.</p>
  </div>
  <?php else: foreach ($notifRows as $notif): 
    $icon = $typeIcons[$notif['type']] ?? '🔔';
    $color = $typeColors[$notif['type']] ?? '#2563eb';
    $isUnread = !$notif['is_read'];
    $diff = time() - strtotime($notif['created_at']);
    if ($diff < 3600) $timeAgo = floor($diff/60) . ' mins ago';
    elseif ($diff < 86400) $timeAgo = floor($diff/3600) . ' hours ago';
    else $timeAgo = date('M j, Y', strtotime($notif['created_at']));
  ?>
  <div class="notif-item <?= $isUnread ? 'unread' : '' ?>" data-notif-id="<?= $notif['id'] ?>">
    <?php if ($isUnread): ?><div class="notif-dot"></div><?php else: ?><div style="width:8px;"></div><?php endif; ?>
    <div class="notif-avatar" style="background:<?= $color ?>;"><?= $icon ?></div>
    <div style="flex:1;">
      <div class="flex-between mb-4">
        <span class="font-bold text-sm"><?= e($notif['title']) ?></span>
        <span class="text-xs text-muted"><?= $timeAgo ?></span>
      </div>
      <p class="text-sm"><?= e($notif['message']) ?></p>
      <?php if (!empty($notif['link_url'])): ?>
      <!-- <div class="mt-8"><a href="<?= e($notif['link_url']) ?>" class="btn btn-outline btn-sm">Inspect Alert →</a></div> -->
      <?php endif; ?>
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
<script>
document.getElementById('admin-mark-all-read') && document.getElementById('admin-mark-all-read').addEventListener('click', async function(e) {
  e.preventDefault();
  const res = await fetch('../api/notifications.php?action=mark_all_read');
  const d = await res.json();
  if (d.success) {
    document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    document.querySelectorAll('.notif-dot').forEach(el => el.style.visibility = 'hidden');
  }
});
document.querySelectorAll('.notif-item[data-notif-id]').forEach(function(item) {
  item.addEventListener('click', function() {
    const id = this.dataset.notifId;
    if (this.classList.contains('unread')) {
      fetch('../api/notifications.php?action=mark_read&id=' + id);
      this.classList.remove('unread');
      const dot = this.querySelector('.notif-dot');
      if (dot) dot.style.visibility = 'hidden';
    }
  });
});
</script>
<script src="../js/app.js"></script>
</body>
</html>