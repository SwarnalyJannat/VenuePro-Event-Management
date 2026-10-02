<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    if ($u['role'] === 'admin') {
        header("Location: admin-dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Admin Sign Up</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Provision System Administrator</h1>
    <p>Master security token required to register root administrative privileges.</p>
  </div>

  <div class="auth-card">
    <div id="signupAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>
    <form id="register-form">
      <input type="hidden" name="role" value="admin">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. System Administrator" required>
      </div>
      <div class="form-group">
        <label class="form-label">Corporate Email</label>
        <input type="email" name="email" class="form-control" placeholder="e.g. admin@enterprise.internal" required>
      </div>
      <div class="form-group">
        <label class="form-label">Enterprise Security Master Token</label>
        <div class="password-wrap">
          <input type="password" name="access_code" class="form-control" placeholder="e.g. VENUEPRO2026" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Set Passphrase</label>
        <div class="password-wrap">
          <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="background:#0f172a; border-color:#0f172a;">Provision Account →</button>
    </form>
  </div>

  <div class="auth-footer" style="margin-top:16px; text-align:center; font-size:0.875rem; color:var(--gray-500);">
    Already registered? <a href="admin-login.php" style="font-weight:600;">Sign in here</a>
  </div>

<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('register-form');
  var alertBox = document.getElementById('signupAlert');
  if (!form) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    alertBox.style.display = 'none';
    var btn = form.querySelector('[type="submit"]');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Provisioning...'; }

    var payload = {
      name:        (form.querySelector('[name="name"]') || {}).value || '',
      email:       (form.querySelector('[name="email"]') || {}).value || '',
      password:    (form.querySelector('[name="password"]') || {}).value || '',
      access_code: (form.querySelector('[name="access_code"]') || {}).value || '',
      role:        'admin'
    };

    try {
      var res = await fetch('../api/auth.php?action=register', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });
      var d = await res.json();
      if (d.success) {
        alertBox.style.display = 'block';
        alertBox.style.background = '#dcfce7';
        alertBox.style.color = '#166534';
        alertBox.textContent = '✓ Admin account provisioned! Redirecting to dashboard...';
        setTimeout(function() {
          window.location.href = d.data && d.data.redirect ? d.data.redirect : 'admin-dashboard.php';
        }, 800);
      } else {
        alertBox.style.display = 'block';
        alertBox.style.background = '#fee2e2';
        alertBox.style.color = '#991b1b';
        alertBox.textContent = '⚠️ ' + (d.message || 'Registration failed');
        if (btn) { btn.disabled = false; btn.textContent = origText; }
      }
    } catch(err) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Connection error. Please try again.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
    }
  });
});
</script>
</body>
</html>