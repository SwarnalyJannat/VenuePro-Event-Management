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
        <div class="breadcrumb mb-16">
          <a href="staff-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Customer Coordination Chat</span>
        </div>

<?php
$stmtCust = $db->query("
    SELECT u.id, u.name, u.email, u.avatar_text, u.avatar_bg, 
           b.id as booking_id, b.booking_code, b.event_name, v.name as venue_name,
           (SELECT message FROM chat_messages cm WHERE (cm.sender_id = u.id OR cm.receiver_id = u.id) ORDER BY cm.id DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM chat_messages cm WHERE (cm.sender_id = u.id OR cm.receiver_id = u.id) ORDER BY cm.id DESC LIMIT 1) AS last_message_time,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.sender_id = u.id AND cm.is_read = 0) AS unread_count
    FROM users u
    LEFT JOIN bookings b ON b.customer_id = u.id AND b.booking_status IN ('confirmed', 'completed')
    LEFT JOIN venues v ON b.venue_id = v.id
    WHERE u.role = 'customer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY unread_count DESC, last_message_time DESC, u.id ASC
");
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

$stmtMsgs = $db->prepare("
    SELECT cm.*, 
           sender.name AS sender_name, sender.avatar_text AS sender_avatar, sender.avatar_bg AS sender_avatar_bg, sender.role AS sender_role,
           receiver.name AS receiver_name, receiver.avatar_text AS receiver_avatar, receiver.avatar_bg AS receiver_avatar_bg, receiver.role AS receiver_role
    FROM chat_messages cm
    JOIN users sender ON cm.sender_id = sender.id
    JOIN users receiver ON cm.receiver_id = receiver.id
    WHERE (sender.role = 'staff' AND cm.receiver_id = ?)
       OR (cm.sender_id = ? AND receiver.role = 'staff')
    ORDER BY cm.created_at ASC
");
$stmtMsgs->execute([$activeCustomerId, $activeCustomerId]);
$chatMessages = $stmtMsgs->fetchAll();

// Mark messages as read
$db->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND is_read = 0")->execute([$activeCustomerId]);
?>
        <div class="card" style="padding:0; overflow:hidden;">
          <div class="chat-layout">
            <!-- Customer Conversations List -->
            <div class="chat-list">
              <div style="padding:14px 16px; border-bottom:1px solid var(--gray-200); background:var(--gray-50);" class="flex-between">
                <div class="font-bold text-xs text-muted">EVENT CLIENTS (ASSIGNED)</div>
                <span class="badge" style="background:var(--primary); color:#fff; font-size:10px;"><?= count($customers) ?> Clients</span>
              </div>
              
              <?php if (empty($customers)): ?>
              <div style="padding:20px; text-align:center; color:var(--gray-400); font-size:0.85rem;">No clients registered.</div>
              <?php else: foreach ($customers as $cust): 
                $isActive = (int)$cust['id'] === $activeCustomerId;
                $evTitle = !empty($cust['venue_name']) ? $cust['venue_name'] : 'Client Account';
                $snippet = !empty($cust['last_message']) ? $cust['last_message'] : 'No messages yet';
                $unreads = (int)($cust['unread_count'] ?? 0);
              ?>
              <a href="staff-chat.php?customer_id=<?= $cust['id'] ?>" class="chat-item <?= $isActive ? 'active' : '' ?>">
                <div class="user-avatar" style="background:<?= e($cust['avatar_bg'] ?? '#2563eb') ?>;"><?= e($cust['avatar_text'] ?? 'CU') ?></div>
                <div style="flex:1; overflow:hidden;">
                  <div class="flex-between">
                    <span class="font-bold text-sm" style="display:flex; align-items:center; gap:6px;">
                      <?= e($cust['name']) ?>
                      <?php if ($unreads > 0): ?>
                        <span style="background:#dc2626; color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:10px;"><?= $unreads ?> new</span>
                      <?php endif; ?>
                    </span>
                    <span class="text-xs text-muted"><?= $cust['booking_code'] ?? '' ?></span>
                  </div>
                  <div class="text-xs text-muted" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= e($evTitle) ?></div>
                  <div class="text-xs" style="white-space:nowrap; text-overflow:ellipsis; overflow:hidden; color:var(--gray-500);"><?= e($snippet) ?></div>
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
                  <span id="chat-status-indicator" class="text-xs" style="color:#059669; font-weight:600; display:flex; align-items:center; gap:4px;">● Live Channel Active</span>
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
                  $isStaff = ($msg['sender_role'] ?? '') === 'staff';
                  $timeStr = date('g:i A', strtotime($msg['created_at']));
                ?>
                  <?php if ($isMe): ?>
                    <div class="msg msg-out">
                      <div class="text-xs" style="font-weight:600; opacity:0.85; margin-bottom:2px;">You (Staff)</div>
                      <?= nl2br(e($msg['message'])) ?>
                      <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;"><?= $timeStr ?></div>
                    </div>
                  <?php elseif ($isStaff): ?>
                    <div class="msg msg-out" style="background:#6366f1;">
                      <div class="text-xs" style="font-weight:600; opacity:0.9; margin-bottom:2px;"><?= e($msg['sender_name']) ?> (Staff Team)</div>
                      <?= nl2br(e($msg['message'])) ?>
                      <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;"><?= $timeStr ?></div>
                    </div>
                  <?php else: ?>
                    <div class="msg msg-in">
                      <div class="text-xs" style="font-weight:600; color:var(--primary); margin-bottom:2px;"><?= e($msg['sender_name']) ?> (Client)</div>
                      <?= nl2br(e($msg['message'])) ?>
                      <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;"><?= $timeStr ?></div>
                    </div>
                  <?php endif; ?>
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
  const activeCustomerId = <?= (int)$activeCustomerId ?>;
  const myUserId = <?= (int)$currentUser['id'] ?>;
  const input = document.getElementById('staff-chat-input');
  const btn = document.getElementById('staff-chat-send');
  const container = document.getElementById('staff-chat-container');
  let lastMessageCount = <?= count($chatMessages) ?>;
  let isSending = false;

  // Auto-scroll to bottom
  if (container) {
    container.scrollTop = container.scrollHeight;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function renderMessages(msgs) {
    let html = `
      <div style="text-align:center; margin-bottom:8px;">
        <span class="text-xs text-muted" style="background:#e2e8f0; padding:3px 12px; border-radius:12px;">🛡️ Direct Customer Channel: Venue Staff &amp; Client Coordination</span>
      </div>
    `;

    if (!msgs || msgs.length === 0) {
      html += `
        <div style="text-align:center; padding:40px; color:var(--gray-400);">
          <div style="font-size:2.5rem; margin-bottom:8px;">💬</div>
          <p class="text-sm">No messages yet with this client.<br>Send a greeting below!</p>
        </div>
      `;
    } else {
      msgs.forEach(m => {
        const isMe = parseInt(m.sender_id) === myUserId;
        const isStaff = m.sender_role === 'staff';
        const timeStr = m.created_at ? new Date(m.created_at.replace(/-/g, '/')).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : 'Just now';
        const textSafe = escapeHtml(m.message).replace(/\n/g, '<br>');

        if (isMe) {
          html += `
            <div class="msg msg-out">
              <div class="text-xs" style="font-weight:600; opacity:0.85; margin-bottom:2px;">You (Staff)</div>
              ${textSafe}
              <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;">${timeStr}</div>
            </div>
          `;
        } else if (isStaff) {
          html += `
            <div class="msg msg-out" style="background:#6366f1;">
              <div class="text-xs" style="font-weight:600; opacity:0.9; margin-bottom:2px;">${escapeHtml(m.sender_name)} (Staff Team)</div>
              ${textSafe}
              <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;">${timeStr}</div>
            </div>
          `;
        } else {
          html += `
            <div class="msg msg-in">
              <div class="text-xs" style="font-weight:600; color:var(--primary); margin-bottom:2px;">${escapeHtml(m.sender_name)} (Client)</div>
              ${textSafe}
              <div class="text-xs" style="text-align:right; margin-top:4px; opacity:0.75;">${timeStr}</div>
            </div>
          `;
        }
      });
    }

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
  }

  async function fetchLatestMessages() {
    if (isSending || !activeCustomerId) return;
    try {
      const res = await fetch(`../api/chat.php?action=messages&receiver_id=${activeCustomerId}`);
      const data = await res.json();
      if (data && data.success && Array.isArray(data.data.messages)) {
        const msgs = data.data.messages;
        if (msgs.length !== lastMessageCount) {
          lastMessageCount = msgs.length;
          renderMessages(msgs);
        }
      }
    } catch(err) {
      console.warn('Chat poll warning:', err);
    }
  }

  async function sendMsg() {
    const text = input.value.trim();
    if (!text || isSending) return;
    
    isSending = true;
    btn.disabled = true;
    const origText = btn.innerHTML;
    btn.innerHTML = 'Sending...';

    try {
      const res = await fetch('../api/chat.php?action=send', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          receiver_id: activeCustomerId,
          message: text
        })
      });
      const d = await res.json();
      if (d && d.success) {
        input.value = '';
        await fetchLatestMessages();
      } else {
        alert('⚠️ ' + (d.message || 'Failed to send message'));
      }
    } catch(e) {
      console.error(e);
      alert('⚠️ Connection error while sending message.');
    } finally {
      isSending = false;
      btn.disabled = false;
      btn.innerHTML = origText;
      input.focus();
    }
  }

  btn && btn.addEventListener('click', sendMsg);
  input && input.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); sendMsg(); }
  });

  // Polling every 3 seconds for live replies
  setInterval(fetchLatestMessages, 3000);
});
</script>
<script src="../js/app.js"></script>
</body>
</html>
