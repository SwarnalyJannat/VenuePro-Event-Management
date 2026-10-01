<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('staff', 'staff-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Staff – Notifications</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .notif-item { display: flex; gap: 14px; padding: 16px 20px; border-bottom: 1px solid var(--gray-100); background: #fff; align-items: flex-start; }
    .notif-item.unread { background: #f0fdf4; border-left: 3px solid #16a34a; }
    .notif-icon-box { width: 38px; height: 38px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
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
        <a href="staff-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Staff Dashboard
        </a>
        <a href="staff-event-setup.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg> Event Setup Detail
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb mb-16">
          <a href="staff-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Staff Notifications</span>
        </div>

<?php
$stmtN = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 40");
$stmtN->execute([$currentUser['id']]);
$notifRows = $stmtN->fetchAll();
$unreadCount = 0;
foreach ($notifRows as $n) { if (!$n['is_read']) $unreadCount++; }
$typeIcons  = ['booking'=>'📋','payment'=>'💳','message'=>'💬','system'=>'⏱','caterer'=>'🍽️'];
$typeColors = ['booking'=>'#eff6ff; color:#2563eb','payment'=>'#eff6ff; color:#2563eb','message'=>'#f0fdf4; color:#16a34a','system'=>'#fef3c7; color:#d97706','caterer'=>'#f1f5f9; color:var(--gray-600)'];
?>
        <div class="flex-between mb-24">
          <div>
            <h1>Staff Notifications &amp; Task Alerts</h1>
            <p>Direct alerts for assigned event setups, customer chat inquiries, and schedule dispatches.</p>
          </div>
          <span class="text-xs text-muted"><?= $unreadCount ?> Unread Alert<?= $unreadCount == 1 ? '' : 's' ?></span>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
          <?php if (empty($notifRows)): ?>
          <div style="padding:48px; text-align:center; color:var(--gray-400);">
            <div style="font-size:3rem; margin-bottom:12px;">🔔</div>
            <p>No new notifications at this time.</p>
          </div>
          <?php else: foreach ($notifRows as $notif): 
            $icon = $typeIcons[$notif['type']] ?? '🔔';
            $colorStyle = $typeColors[$notif['type']] ?? '#eff6ff; color:#2563eb';
            $isUnread = !$notif['is_read'];
            $timeStr = date('M j, g:i A', strtotime($notif['created_at']));
          ?>
          <div class="notif-item <?= $isUnread ? 'unread' : '' ?>" data-notif-id="<?= $notif['id'] ?>">
            <div class="notif-icon-box" style="background:<?= $colorStyle ?>"><?= $icon ?></div>
            <div style="flex:1;">
              <div class="flex-between mb-4">
                <strong><?= e($notif['title']) ?></strong>
                <span class="text-xs text-muted"><?= $timeStr ?></span>
              </div>
              <p class="text-xs text-muted mb-8"><?= e($notif['message']) ?></p>
              <?php if (!empty($notif['link_url'])): ?>
              <a href="<?= e($notif['link_url']) ?>" class="btn btn-primary btn-sm" style="font-size:0.75rem; padding:4px 10px;">View Task →</a>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; endif; ?>
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
