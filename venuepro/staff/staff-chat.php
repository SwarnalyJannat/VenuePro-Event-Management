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
  <title>VenuePro Staff – Customer Communication</title>
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
    .msg { max-width: 70%; padding: 12px 16px; border-radius: var(--radius); font-size: 0.875rem; line-height: 1.5; }
    .msg-in { align-self: flex-start; background: #fff; border: 1px solid var(--gray-200); color: var(--gray-800); }
    .msg-out { align-self: flex-end; background: var(--primary); color: #fff; }
    .chat-input-bar { padding: 14px 20px; border-top: 1px solid var(--gray-200); display: flex; gap: 12px; align-items: center; background: #fff; }
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
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'staff')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb mb-16">
          <a href="staff-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Customer Coordination Chat</span>
        </div>

<?php
$stmtCust = $db->query("SELECT DISTINCT u.id, u.name, u.email, u.avatar_text, u.avatar_bg, b.id as booking_id, b.booking_code, b.event_name, v.name as venue_name
FROM users u
LEFT JOIN bookings b ON b.customer_id = u.id
LEFT JOIN venues v ON b.venue_id = v.id
WHERE u.role = 'customer' AND u.status = 'active'
GROUP BY u.id
ORDER BY u.id ASC");
$customers = $stmtCust->fetchAll();

$activeCustomerId = (int)($_GET['customer_id'] ?? ($customers[0]['id'] ?? 1));
$activeCustomer = null;
foreach ($customers as $c) {
    if ((int)$c['id'] === $activeCustomerId) {
        $activeCustomer = $c;
        break;
    }
}
if (!$activeCustomer && !empty($customers)) {
    $activeCustomer = $customers[0];
    $activeCustomerId = (int)$activeCustomer['id'];
}

$stmtMsgs = $db->prepare("SELECT cm.*, u.name as sender_name, u.avatar_text FROM chat_messages cm JOIN users u ON cm.sender_id = u.id WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) ORDER BY cm.created_at ASC");
$stmtMsgs->execute([$currentUser['id'], $activeCustomerId, $activeCustomerId, $currentUser['id']]);
$chatMessages = $stmtMsgs->fetchAll();
?>
        <div class="card" style="padding:0; overflow:hidden;">
          <div class="chat-layout">
            <!-- Customer Conversations List -->
            <div class="chat-list">
              <div style="padding:14px 16px; border-bottom:1px solid var(--gray-200); background:var(--gray-50);">
                <div class="font-bold text-xs text-muted mb-6">EVENT CLIENTS (ASSIGNED)</div>
              </div>
              
              <?php if (empty($customers)): ?>
              <div style="padding:20px; text-align:center; color:var(--gray-400); font-size:0.85rem;">No clients registered.</div>
              <?php else: foreach ($customers as $cust): 
                $isActive = (int)$cust['id'] === $activeCustomerId;
                $evTitle = !empty($cust['venue_name']) ? $cust['venue_name'] : 'Client Account';
              ?>
              <a href="staff-chat.php?customer_id=<?= $cust['id'] ?>" class="chat-item <?= $isActive ? 'active' : '' ?>">
                <div class="user-avatar" style="background:<?= e($cust['avatar_bg'] ?? '#2563eb') ?>;"><?= e($cust['avatar_text'] ?? 'CU') ?></div>
                <div style="flex:1; overflow:hidden;">
                  <div class="flex-between">
                    <span class="font-bold text-sm"><?= e($cust['name']) ?></span>
                    <span class="text-xs text-muted"><?= $cust['booking_code'] ?? '' ?></span>
                  </div>
                  <div class="text-xs text-muted" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= e($evTitle) ?></div>
                </div>
              </a>
              <?php endforeach; endif; ?>
            </div>

            <!-- Chat Main -->
            <div class="chat-main">
              <div class="chat-header">
                <div class="flex-center gap-12">
                  <div class="user-avatar" style="background:<?= e($activeCustomer['avatar_bg'] ?? '#2563eb') ?>;"><?= e($activeCustomer['avatar_text'] ?? 'CU') ?></div>
                  <div>
                    <div class="font-bold"><?= e($activeCustomer['name'] ?? 'Client') ?> <span class="pill pill-confirmed" style="font-size:0.7rem; margin-left:6px;">Customer</span></div>
                    <div class="text-xs text-muted">Event: <?= e($activeCustomer['event_name'] ?? 'Active Event Reservation') ?> (<?= e($activeCustomer['booking_code'] ?? 'Booking Reference') ?>)</div>
                  </div>
                </div>
                <div class="flex gap-8">
                  <a href="staff-event-setup.php<?= !empty($activeCustomer['booking_id']) ? '?booking_id='.$activeCustomer['booking_id'] : '' ?>" class="btn btn-outline btn-sm">Inspect Setup Checklist</a>
                </div>
              </div>

              <div class="chat-messages" id="staff-chat-container">
                <div style="text-align:center; margin-bottom:8px;">
                  <span class="text-xs text-muted" style="background:#e2e8f0; padding:3px 12px; border-radius:12px;">🛡️ Direct Customer Channel: Venue Staff &amp; Client Coordination</span>
                </div>

                <?php if (empty($chatMessages)): ?>
                <div style="text-align:center; padding:40px; color:var(--gray-400);">
                  <div style="font-size:2.5rem; margin-bottom:8px;">💬</div>
                  <p class="text-sm">No messages yet with <?= e($activeCustomer['name'] ?? 'this client') ?>.<br>Send a greeting below!</p>
                </div>
                <?php else: foreach ($chatMessages as $msg): 
                  $isMe = (int)$msg['sender_id'] === (int)$currentUser['id'];
                  $timeStr = date('g:i A', strtotime($msg['created_at']));
                ?>
                <div class="msg <?= $isMe ? 'msg-out' : 'msg-in' ?>">
                  <?= nl2br(e($msg['message'])) ?>
                  <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;"><?= $timeStr ?></div>
                </div>
                <?php endforeach; endif; ?>
              </div>

              <div class="chat-input-bar">
                <input type="text" id="staff-chat-input" class="form-control" placeholder="Reply to customer <?= e($activeCustomer['name'] ?? 'Client') ?>..." style="flex:1;">
                <button type="button" id="staff-chat-send" class="btn btn-primary font-bold">Send Message →</button>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const input = document.getElementById('staff-chat-input');
  const btn = document.getElementById('staff-chat-send');
  const container = document.getElementById('staff-chat-container');

  async function sendMsg() {
    const text = input.value.trim();
    if (!text) return;
    
    const b = document.createElement('div');
    b.className = 'msg msg-out';
    b.innerHTML = text.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '<div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;">Just now</div>';
    container.appendChild(b);
    container.scrollTop = container.scrollHeight;
    input.value = '';

    try {
      await fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          receiver_id: <?= (int)$activeCustomerId ?>,
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
