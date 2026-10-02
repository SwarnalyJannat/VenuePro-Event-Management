<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    if ($u['role'] === 'customer') {
        header("Location: customer-dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Customer Registration</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <div style="font-size:1.5rem; font-weight:700;">VenuePro</div>
    <div style="font-size:0.85rem; color:var(--gray-500);">Enterprise Event Management</div>
  </div>

  <div class="auth-card">
    <h2 style="font-size:1.4rem; margin-bottom:4px;">Create customer account</h2>
    <p style="margin-bottom:24px; font-size:0.875rem;">Start exploring and reserving verified venues.</p>

    <div id="registerAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

    <form id="register-form">
      <input type="hidden" name="role" value="customer">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" placeholder="e.g. Jane Doe" required>
      </div>

      <div class="form-group">
        <label class="form-label">Work Email address</label>
        <input type="email" name="email" class="form-control" placeholder="e.g. jane@company.com" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="password-wrap">
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrap">
            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
          </div>
        </div>
      </div>

      <div class="form-group flex-center gap-8">
        <input type="checkbox" id="terms" required>
        <label for="terms" style="font-size:0.8rem; color:var(--gray-600); cursor:pointer;">I agree to the <a href="../terms-of-service.php">Terms of Service</a> &amp; <a href="../privacy-policy.php">Privacy Policy</a></label>
      </div>

      <button type="submit" class="btn btn-primary btn-full mb-16">Create Account →</button>
    </form>
  </div>

  <div class="auth-footer">
    Already registered? <a href="customer-login.php" style="font-weight:600;">Sign in here</a>
  </div>

<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('register-form');
  var alertBox = document.getElementById('registerAlert');
  if (!form) return;

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    alertBox.style.display = 'none';
    var btn = form.querySelector('[type="submit"]');
    var origText = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = 'Creating account...'; }

    var pw  = (form.querySelector('[name="password"]') || {}).value || '';
    var cpw = (form.querySelector('[name="confirm_password"]') || {}).value || '';
    if (pw !== cpw) {
      alertBox.style.display = 'block';
      alertBox.style.background = '#fee2e2';
      alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ Passwords do not match.';
      if (btn) { btn.disabled = false; btn.textContent = origText; }
      return;
    }

    var payload = {
      name:     (form.querySelector('[name="name"]') || {}).value || '',
      email:    (form.querySelector('[name="email"]') || {}).value || '',
      password: pw,
      role:     'customer'
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
        alertBox.textContent = '✓ Account created! Redirecting to dashboard...';
        setTimeout(function() {
          window.location.href = d.data && d.data.redirect ? d.data.redirect : 'customer-dashboard.php';
        }, 700);
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