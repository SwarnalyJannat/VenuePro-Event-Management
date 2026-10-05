<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<?php
$stmtN = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 40");
$stmtN->execute([$currentUser['id']]);
$notifRows  = $stmtN->fetchAll();
$unreadCount = 0;
foreach ($notifRows as $n) { if (!$n['is_read']) $unreadCount++; }
// Icon map by type
$typeIcons  = ['booking'=>'📅','payment'=>'💳','message'=>'💬','system'=>'🔔','caterer'=>'🍽️'];
$typeColors = ['booking'=>'#16a34a','payment'=>'#2563eb','message'=>'#7c3aed','system'=>'#f59e0b','caterer'=>'#059669'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Notification Center</title>
  <link rel="stylesheet" href="../css/style.css">
  
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Sidebar -->
    
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
            <header class="topbar">
        <label for="sidebar-toggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </label>
        <div class="topbar-search">
          <span class="topbar-search-icon">🔍</span>
          <input type="text" placeholder="Search event venues, bookings, menus...">
        </div>
        <div class="topbar-actions">
          <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge" id="notif-badge"><?= $unreadCount > 0 ? $unreadCount : '' ?></span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <a href="customer-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        
<div class="flex-between mb-24">
  <div>
    <h1>Notification Center</h1>
    <p>Real-time booking confirmations, invoice receipts, and schedule reminders.</p>
  </div>
  <button class="btn btn-ghost btn-sm">Mark All as Read</button>
</div>

<div class="card">
<?php if (empty($notifRows)): ?>
  <div style="text-align:center;padding:48px;color:var(--gray-400);">
    <div style="font-size:3rem;margin-bottom:12px;">🔔</div>
    <p>You're all caught up! No notifications.</p>
  </div>
<?php else: foreach ($notifRows as $notif):
  $icon  = $typeIcons[$notif['type']] ?? '🔔';
  $color = $typeColors[$notif['type']] ?? '#6b7280';
  $isUnread = !$notif['is_read'];
  $timeAgo = '';
  $diff = time() - strtotime($notif['created_at']);
  if ($diff < 3600) $timeAgo = floor($diff/60).' mins ago';
  elseif ($diff < 86400) $timeAgo = floor($diff/3600).' hours ago';
  else $timeAgo = date('M j, Y', strtotime($notif['created_at']));
  $link = !empty($notif['link_url']) ? $notif['link_url'] : '#';
?>
  <div class="notif-item <?= $isUnread ? 'unread' : '' ?>" data-notif-id="<?= $notif['id'] ?>">
    <?php if ($isUnread): ?><div class="notif-dot"></div><?php else: ?><div style="width:8px;"></div><?php endif; ?>
    <div class="notif-avatar" style="background:<?= $color ?>;"><?= $icon ?></div>
    <div style="flex:1;">
      <div class="flex-between mb-4">
        <div class="font-bold text-sm"><?= e($notif['title']) ?></div>
        <span class="text-xs text-muted"><?= $timeAgo ?></span>
      </div>
      <p class="text-sm"><?= e($notif['message']) ?></p>
      <?php if ($link !== '#'): ?>
      <a href="<?= e($link) ?>" class="text-xs text-primary" style="text-decoration:underline;">View details →</a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; endif; ?>
</div>

      </main>

                        <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
        <div class="footer-links">
          <a href="../privacy-policy.php">Privacy Policy</a>
          <a href="../terms-of-service.php">Terms of Service</a>
          <a href="../contact-support.php">Contact Support</a>
        </div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>
<script>
// Mark all as read
document.querySelector('.btn-ghost.btn-sm') && document.querySelector('.btn-ghost.btn-sm').addEventListener('click', async function(e) {
  e.preventDefault();
  var res = await fetch('../api/notifications.php?action=mark_all_read');
  if ((await res.json()).success) {
    document.querySelectorAll('.notif-item.unread').forEach(function(el){ el.classList.remove('unread'); });
    document.querySelectorAll('.notif-dot').forEach(function(d){ d.style.visibility='hidden'; });
    var badge = document.getElementById('notif-badge'); if(badge) badge.textContent='';
  }
});
// Mark individual as read on click
document.querySelectorAll('.notif-item[data-notif-id]').forEach(function(item) {
  item.addEventListener('click', function() {
    var id = this.dataset.notifId;
    if (this.classList.contains('unread')) {
      fetch('../api/notifications.php?action=mark_read&id=' + id);
      this.classList.remove('unread');
      var dot = this.querySelector('.notif-dot'); if(dot) dot.style.visibility='hidden';
    }
  });
});
</script>
</body>
</html>