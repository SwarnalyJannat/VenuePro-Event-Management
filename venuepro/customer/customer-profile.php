<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$postAlertMsg = '';
$postAlertSuccess = false;

// Handle direct server-side POST submission (ensures 100% persistence even if JS is bypassed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    $name = sanitize($_POST['name'] ?? '');
    $email = strtolower(sanitize($_POST['email'] ?? ''));
    $phone = sanitize($_POST['phone'] ?? '');
    $avatarText = strtoupper(sanitize($_POST['avatar_text'] ?? ''));
    $avatarBg = sanitize($_POST['avatar_bg'] ?? '');
    $currPw = $_POST['current_password'] ?? '';
    $newPw = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';
    $userId = (int)$currentUser['id'];

    if (empty($name)) {
        $postAlertMsg = 'Full name cannot be empty.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $postAlertMsg = 'A valid email address is required.';
    } else {
        $stmtEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmtEmail->execute([$email, $userId]);
        if ($stmtEmail->fetch()) {
            $postAlertMsg = 'This email address is already in use by another account.';
        } else {
            if (empty($avatarText)) {
                $avatarText = strtoupper(substr($name, 0, 2));
            } else {
                $avatarText = substr($avatarText, 0, 4);
            }
            if (empty($avatarBg) || !preg_match('/^#[a-f0-9]{6}$/i', $avatarBg)) {
                $avatarBg = $currentUser['avatar_bg'] ?? '#2563eb';
            }

            $updatePassword = false;
            $newPasswordHash = null;
            if (!empty($newPw) || !empty($currPw)) {
                $stmtCurr = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
                $stmtCurr->execute([$userId]);
                $userHash = $stmtCurr->fetchColumn();
                if (empty($currPw)) {
                    $postAlertMsg = 'Please provide your current password to authorize password change.';
                } elseif (!password_verify($currPw, $userHash)) {
                    $postAlertMsg = 'Current password verification failed. Please check your password.';
                } elseif (strlen($newPw) < 6) {
                    $postAlertMsg = 'New password must be at least 6 characters.';
                } elseif ($newPw !== $confirmPw) {
                    $postAlertMsg = 'New password and confirmation do not match.';
                } else {
                    $newPasswordHash = password_hash($newPw, PASSWORD_BCRYPT);
                    $updatePassword = true;
                }
            }

            if (empty($postAlertMsg)) {
                if ($updatePassword) {
                    $stmtUp = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar_text = ?, avatar_bg = ?, password_hash = ? WHERE id = ?");
                    $stmtUp->execute([$name, $email, $phone, $avatarText, $avatarBg, $newPasswordHash, $userId]);
                } else {
                    $stmtUp = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar_text = ?, avatar_bg = ? WHERE id = ?");
                    $stmtUp->execute([$name, $email, $phone, $avatarText, $avatarBg, $userId]);
                }

                $_SESSION['user_name']   = $name;
                $_SESSION['user_email']  = $email;
                $_SESSION['avatar_text'] = $avatarText;
                $_SESSION['avatar_bg']   = $avatarBg;

                $postAlertMsg = 'Your profile has been successfully updated!';
                $postAlertSuccess = true;
            }
        }
    }
}

$stmtCust = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmtCust->execute([$currentUser['id']]);
$customerData = $stmtCust->fetch() ?: $currentUser;

// Booking summary stats for this customer
$stmtBk = $db->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_amount), 0) AS total_spent FROM bookings WHERE customer_id = ?");
$stmtBk->execute([$currentUser['id']]);
$stats = $stmtBk->fetch() ?: ['total_bookings' => 0, 'total_spent' => 0];

$stmtNotif = $db->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND booking_status = 'confirmed'");
$stmtNotif->execute([$currentUser['id']]);
$notifCount = (int)$stmtNotif->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Customer Profile Settings</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .color-option {
      width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
      border: 3px solid transparent; transition: transform 0.2s, border-color 0.2s;
    }
    .color-option:hover { transform: scale(1.1); }
    .color-option.selected { border-color: #0f172a; box-shadow: 0 0 0 2px #fff inset; }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div class="sidebar-logo-text">VenuePro</div>
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
          <input type="text" placeholder="e.g. Search event venues, bookings, menus...">
        </div>
        <div class="topbar-actions">
          <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge"><?= $notifCount ?></span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <a href="customer-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="View & Edit My Profile">
            <div id="topbar-avatar" class="user-avatar" style="background:<?= e($customerData['avatar_bg'] ?: '#2563eb') ?>;"><?= e($customerData['avatar_text'] ?: 'CU') ?></div>
            <div class="user-info">
              <div id="topbar-name" class="user-name"><?= e($customerData['name']) ?></div>
              <div class="user-role">Customer</div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb">
          <a href="customer-dashboard.php">Home</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">My Profile Settings</span>
        </div>

        <div class="flex-between mb-24">
          <div>
            <h1>Customer Profile Settings</h1>
            <p>Update your personal contact details, preferences, and account security.</p>
          </div>
          <span class="pill pill-confirmed" style="font-size:0.8rem; padding:6px 14px;">VERIFIED CLIENT</span>
        </div>

        <?php if (!empty($postAlertMsg)): ?>
        <div style="padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:0.9rem; background:<?= $postAlertSuccess ? '#dcfce7' : '#fee2e2' ?>; border:1px solid <?= $postAlertSuccess ? '#86efac' : '#f87171' ?>; color:<?= $postAlertSuccess ? '#166534' : '#991b1b' ?>;">
          <?= $postAlertSuccess ? '✓ ' : '⚠️ ' ?><?= e($postAlertMsg) ?>
        </div>
        <?php endif; ?>
        <div id="profileAlertTop" style="display:none; padding:12px 16px; border-radius:6px; margin-bottom:20px; font-size:0.9rem;"></div>

        <div class="grid-2 gap-24" style="grid-template-columns: 1fr 2fr; align-items:start;">
          <!-- Left Column: Avatar & Summary -->
          <div class="card" style="text-align:center;">
            <div style="margin:16px auto 20px;">
              <div id="avatarPreview" style="width:96px; height:96px; border-radius:50%; background:<?= e($customerData['avatar_bg'] ?: '#2563eb') ?>; color:#fff; font-size:2.2rem; font-weight:800; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 4px 14px rgba(0,0,0,0.15);">
                <?= e($customerData['avatar_text'] ?: 'CU') ?>
              </div>
            </div>
            <h3 id="displayProfileName" style="margin:0 0 4px;"><?= e($customerData['name']) ?></h3>
            <p id="displayProfileEmail" class="text-sm text-muted" style="margin-bottom:16px;"><?= e($customerData['email']) ?></p>
            <div style="border-top:1px solid var(--gray-200); padding-top:16px; text-align:left; font-size:0.85rem;">
              <div class="flex-between mb-8">
                <span class="text-muted">Total Reservations:</span>
                <span class="font-bold text-primary"><?= (int)$stats['total_bookings'] ?> event<?= (int)$stats['total_bookings'] === 1 ? '' : 's' ?></span>
              </div>
              <div class="flex-between mb-8">
                <span class="text-muted">Total Invested:</span>
                <span class="font-bold text-success">$<?= number_format((float)$stats['total_spent'], 2) ?></span>
              </div>
              <div class="flex-between">
                <span class="text-muted">Member Since:</span>
                <span><?= date('M j, Y', strtotime($customerData['created_at'])) ?></span>
              </div>
            </div>
          </div>

          <!-- Right Column: Profile Edit Form -->
          <div class="card">
            <h2 class="mb-4" style="font-size:1.25rem;">Personal Information</h2>
            <p class="text-sm text-muted mb-20">Only you can update your personal contact details and password.</p>

            <form id="customerProfileForm" method="POST" action="customer-profile.php">
              <input type="hidden" name="action" value="save_profile">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Full Name *</label>
                  <input type="text" name="name" id="inputName" class="form-control" value="<?= e($customerData['name']) ?>" placeholder="e.g. Mahmud Hassan" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Email Address *</label>
                  <input type="email" name="email" id="inputEmail" class="form-control" value="<?= e($customerData['email']) ?>" placeholder="e.g. mahmud@example.com" required>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Phone Number</label>
                  <input type="tel" name="phone" id="inputPhone" class="form-control" value="<?= e($customerData['phone'] ?? '') ?>" placeholder="e.g. +1 (555) 012-3456">
                </div>
                <div class="form-group">
                  <label class="form-label">Avatar Initials</label>
                  <input type="text" name="avatar_text" id="inputAvatarText" maxlength="3" class="form-control" value="<?= e($customerData['avatar_text'] ?: 'CU') ?>" placeholder="e.g. MH">
                </div>
              </div>

              <div class="form-group mb-24">
                <label class="form-label">Avatar Badge Color</label>
                <div class="flex gap-10" style="align-items:center; margin-top:8px;" id="avatarColorPicker">
                  <?php
                  $presetColors = ['#2563eb', '#059669', '#7c3aed', '#0284c7', '#d97706', '#0f172a'];
                  $currentBg = $customerData['avatar_bg'] ?: '#2563eb';
                  foreach ($presetColors as $c):
                    $isSel = (strtolower($currentBg) === strtolower($c));
                  ?>
                  <div class="color-option <?= $isSel ? 'selected' : '' ?>" style="background:<?= $c ?>;" data-color="<?= $c ?>" onclick="selectAvatarColor('<?= $c ?>')"></div>
                  <?php endforeach; ?>
                  <input type="hidden" name="avatar_bg" id="inputAvatarBg" value="<?= e($currentBg) ?>">
                </div>
              </div>

              <!-- Security / Password Change -->
              <div style="border-top:1px solid var(--gray-200); padding-top:20px; margin-top:20px;">
                <h3 style="font-size:1.1rem; margin-bottom:4px;">Change Password</h3>
                <p class="text-sm text-muted mb-16">Leave password fields blank if you do not want to change your current password.</p>

                <div class="form-group">
                  <label class="form-label">Current Password</label>
                  <input type="password" name="current_password" id="inputCurrPw" class="form-control" placeholder="e.g. •••••••• (required to authorize new password)">
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" id="inputNewPw" class="form-control" placeholder="e.g. •••••••• (min. 6 characters)">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="inputConfirmPw" class="form-control" placeholder="e.g. •••••••• (re-enter new password)">
                  </div>
                </div>
              </div>

              <div id="profileAlertBottom" style="display:none; padding:12px 16px; border-radius:6px; margin-top:16px; font-size:0.9rem;"></div>

              <div class="flex gap-12 mt-24">
                <a href="customer-dashboard.php" class="btn btn-ghost" style="flex:1;">Discard Changes</a>
                <button type="submit" id="btnSaveProfile" class="btn btn-primary" style="flex:2;">Save Profile Changes →</button>
              </div>
            </form>
          </div>
        </div>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise. Client Portal.</div>
      </footer>
    </div>
  </div>

  <script>
    function selectAvatarColor(color) {
      document.getElementById('inputAvatarBg').value = color;
      document.getElementById('avatarPreview').style.background = color;
      var options = document.querySelectorAll('.color-option');
      options.forEach(function(opt) {
        if (opt.getAttribute('data-color') === color) {
          opt.classList.add('selected');
        } else {
          opt.classList.remove('selected');
        }
      });
    }

    document.getElementById('inputAvatarText').addEventListener('input', function(e) {
      var val = (e.target.value || 'CU').toUpperCase();
      document.getElementById('avatarPreview').textContent = val;
    });

    document.getElementById('inputName').addEventListener('input', function(e) {
      var val = e.target.value.trim();
      if (val) {
        document.getElementById('displayProfileName').textContent = val;
      }
    });

    document.getElementById('customerProfileForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      var btn = document.getElementById('btnSaveProfile');
      var alertTop = document.getElementById('profileAlertTop');
      var alertBottom = document.getElementById('profileAlertBottom');

      function showAlert(msg, isSuccess) {
        [alertTop, alertBottom].forEach(function(box) {
          box.style.display = 'block';
          box.style.background = isSuccess ? '#dcfce7' : '#fee2e2';
          box.style.border = isSuccess ? '1px solid #86efac' : '1px solid #f87171';
          box.style.color = isSuccess ? '#166534' : '#991b1b';
          box.innerHTML = (isSuccess ? '✓ ' : '⚠️ ') + msg;
        });
        if (!isSuccess) {
          alertBottom.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }

      var name = document.getElementById('inputName').value.trim();
      var email = document.getElementById('inputEmail').value.trim();
      var phone = document.getElementById('inputPhone').value.trim();
      var avatarText = document.getElementById('inputAvatarText').value.trim();
      var avatarBg = document.getElementById('inputAvatarBg').value.trim();
      var currPw = document.getElementById('inputCurrPw').value;
      var newPw = document.getElementById('inputNewPw').value;
      var confirmPw = document.getElementById('inputConfirmPw').value;

      if (!name || !email) {
        showAlert('Full name and email address cannot be empty.', false);
        return;
      }

      if (newPw || currPw) {
        if (!currPw) {
          showAlert('Please provide your current password to set a new password.', false);
          return;
        }
        if (newPw.length < 6) {
          showAlert('New password must be at least 6 characters.', false);
          return;
        }
        if (newPw !== confirmPw) {
          showAlert('New password and password confirmation do not match.', false);
          return;
        }
      }

      var payload = {
        name: name,
        email: email,
        phone: phone,
        avatar_text: avatarText,
        avatar_bg: avatarBg,
        current_password: currPw,
        new_password: newPw,
        confirm_password: confirmPw
      };

      var origText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Saving Profile Changes...';

      try {
        var res = await fetch('../api/profile.php?action=update', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          credentials: 'include',
          body: JSON.stringify(payload)
        });
        var data = await res.json();
        if (data.success) {
          showAlert('Your profile has been successfully updated!', true);
          if (data.data && data.data.user) {
            var u = data.data.user;
            var topAvatar = document.getElementById('topbar-avatar');
            var topName = document.getElementById('topbar-name');
            var dispName = document.getElementById('displayProfileName');
            var dispEmail = document.getElementById('displayProfileEmail');
            var avPreview = document.getElementById('avatarPreview');

            if (topAvatar) {
              topAvatar.textContent = u.avatar_text;
              topAvatar.style.background = u.avatar_bg;
            }
            if (topName) {
              topName.textContent = u.name;
            }
            if (dispName) {
              dispName.textContent = u.name;
            }
            if (dispEmail) {
              dispEmail.textContent = u.email;
            }
            if (avPreview) {
              avPreview.textContent = u.avatar_text;
              avPreview.style.background = u.avatar_bg;
            }
          }
          document.getElementById('inputCurrPw').value = '';
          document.getElementById('inputNewPw').value = '';
          document.getElementById('inputConfirmPw').value = '';
        } else {
          showAlert(data.message || 'Failed to update profile. Please verify your inputs.', false);
        }
      } catch (err) {
        console.error(err);
        showAlert('Network communication error while updating profile. Please try again.', false);
      } finally {
        btn.disabled = false;
        btn.textContent = origText;
      }
    });
  </script>
</body>
</html>
