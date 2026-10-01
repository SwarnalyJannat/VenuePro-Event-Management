<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Venue Staff Coordination Chat</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .chat-layout { display: grid; grid-template-columns: 320px 1fr; height: 640px; }
    .chat-list { border-right: 1px solid var(--gray-200); overflow-y: auto; background: #fff; }
    .chat-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--gray-100); cursor: pointer; text-decoration: none; color: inherit; transition: background 0.15s; }
    .chat-item:hover { background: var(--gray-50); }
    .chat-item.active { background: #eff6ff; border-left: 3px solid var(--primary); }
    .chat-main { display: flex; flex-direction: column; background: #fff; }
    .chat-header { padding: 16px 24px; border-bottom: 1px solid var(--gray-200); display: flex; align-items: center; justify-content: space-between; }
    .chat-messages { flex: 1; padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; background: #f8fafc; }
    .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: var(--radius); font-size: 0.875rem; line-height: 1.5; }
    .msg-in { align-self: flex-start; background: #fff; border: 1px solid var(--gray-200); color: var(--gray-800); }
    .msg-out { align-self: flex-end; background: var(--primary); color: #fff; }
    .chat-input-bar { padding: 14px 20px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; align-items: center; background: #fff; }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Customer Sidebar -->
    
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
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
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
            <span class="badge">3</span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb mb-16">
          <a href="customer-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Venue Staff Chat</span>
        </div>

        <div class="card" style="padding:0; overflow:hidden;">
          <div class="chat-layout">
<?php
$stmtStaff = $db->query("SELECT u.id, u.name, u.email, u.avatar_text, u.avatar_bg, sp.department, sp.staff_code FROM users u JOIN staff_profiles sp ON u.id = sp.user_id WHERE u.role = 'staff' AND u.status = 'active' ORDER BY u.id ASC");
$staffMembers = $stmtStaff->fetchAll();

$activeStaffId = (int)($_GET['staff_id'] ?? ($staffMembers[0]['id'] ?? 3));
$activeStaff = null;
foreach ($staffMembers as $sm) {
    if ((int)$sm['id'] === $activeStaffId) {
        $activeStaff = $sm;
        break;
    }
}
if (!$activeStaff && !empty($staffMembers)) {
    $activeStaff = $staffMembers[0];
    $activeStaffId = (int)$activeStaff['id'];
}

$stmtMsgs = $db->prepare("SELECT cm.*, u.name as sender_name, u.avatar_text FROM chat_messages cm JOIN users u ON cm.sender_id = u.id WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) ORDER BY cm.created_at ASC");
$stmtMsgs->execute([$currentUser['id'], $activeStaffId, $activeStaffId, $currentUser['id']]);
$chatMessages = $stmtMsgs->fetchAll();
?>
            <!-- Staff Contacts List (Venue Staff ONLY) -->
            <div class="chat-list">
              <div style="padding:14px 16px; border-bottom:1px solid var(--gray-200); background:var(--gray-50);">
                <div class="font-bold text-xs text-muted mb-6">ASSIGNED VENUE STAFF</div>
              </div>

              <?php if (empty($staffMembers)): ?>
              <div style="padding:20px; text-align:center; color:var(--gray-400); font-size:0.85rem;">No staff coordinators online.</div>
              <?php else: foreach ($staffMembers as $sm): 
                $isActive = (int)$sm['id'] === $activeStaffId;
              ?>
              <a href="customer-chat.php?staff_id=<?= $sm['id'] ?>" class="chat-item <?= $isActive ? 'active' : '' ?>">
                <div class="user-avatar" style="background:<?= e($sm['avatar_bg'] ?? '#7c3aed') ?>;"><?= e($sm['avatar_text'] ?? 'ST') ?></div>
                <div style="flex:1; overflow:hidden;">
                  <div class="flex-between">
                    <span class="font-bold text-sm"><?= e($sm['name']) ?></span>
                    <span class="text-xs text-muted"><?= e($sm['staff_code']) ?></span>
                  </div>
                  <div class="text-xs text-muted" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= e($sm['department']) ?></div>
                </div>
              </a>
              <?php endforeach; endif; ?>
            </div>

            <!-- Active Chat View -->
            <div class="chat-main">
              <div class="chat-header">
                <div class="flex-center gap-12">
                  <div class="user-avatar" style="background:<?= e($activeStaff['avatar_bg'] ?? '#7c3aed') ?>;"><?= e($activeStaff['avatar_text'] ?? 'ST') ?></div>
                  <div>
                    <div class="font-bold text-sm"><?= e($activeStaff['name'] ?? 'Venue Staff') ?> <span class="pill pill-confirmed" style="font-size:0.7rem; margin-left:6px;">Staff</span></div>
                    <div class="text-xs text-success">● <?= e($activeStaff['department'] ?? 'Event Operations') ?> (Direct Channel)</div>
                  </div>
                </div>
                <div class="flex gap-8">
                  <a href="customer-live-progress.php" class="btn btn-outline btn-sm">⚡ Check Live Progress</a>
                </div>
              </div>

              <div class="chat-messages" id="customer-chat-container">
                <div style="text-align:center; margin-bottom:8px;">
                  <span class="text-xs text-muted" style="background:#e2e8f0; padding:3px 14px; border-radius:12px;">🛡️ Dedicated Venue Staff Channel: Direct communication for logistics, hall staging &amp; A/V setups</span>
                </div>

                <?php if (empty($chatMessages)): ?>
                <div style="text-align:center; padding:40px; color:var(--gray-400);">
                  <div style="font-size:2.5rem; margin-bottom:8px;">💬</div>
                  <p class="text-sm">No messages yet with <?= e($activeStaff['name'] ?? 'this coordinator') ?>.<br>Send a message below to begin coordination!</p>
                </div>
                <?php else: foreach ($chatMessages as $msg): 
                  $isMe = (int)$msg['sender_id'] === (int)$currentUser['id'];
                  $timeStr = date('g:i A', strtotime($msg['created_at']));
                ?>
                <div class="msg-bubble <?= $isMe ? 'msg-out' : 'msg-in' ?>">
                  <?= nl2br(e($msg['message'])) ?>
                  <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;"><?= $timeStr ?></div>
                </div>
                <?php endforeach; endif; ?>
              </div>

              <div class="chat-input-bar">
                <input type="text" id="chat-input-field" class="form-control" placeholder="Type your message to venue coordinator <?= e($activeStaff['name'] ?? 'Staff') ?>..." style="flex:1;">
                <button type="button" id="chat-send-action" class="btn btn-primary font-bold">Send Message →</button>
              </div>
            </div>
          </div>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  const input = document.getElementById('chat-input-field');
  const btn = document.getElementById('chat-send-action');
  const container = document.getElementById('customer-chat-container');

  async function sendMsg() {
    const text = input.value.trim();
    if (!text) return;
    
    const b = document.createElement('div');
    b.className = 'msg-bubble msg-out';
    b.innerHTML = text.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '<div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;">Just now</div>';
    container.appendChild(b);
    container.scrollTop = container.scrollHeight;
    input.value = '';

    try {
      await fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          receiver_id: <?= (int)$activeStaffId ?>,
          message: text
        })
      });
    } catch(e) {
      console.error(e);
    }
  }

  btn && btn.addEventListener('click', sendMsg);
  input && input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); sendMsg(); }
  });
});
</script>
<script src="../js/app.js"></script>
</body>
</html>
