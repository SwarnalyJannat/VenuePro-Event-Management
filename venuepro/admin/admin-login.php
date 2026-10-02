<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'admin-dashboard.php';
    if ($u['role'] === 'admin') {
        header("Location: $dest");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Administrator Sign In</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <div style="font-size:1.5rem; font-weight:700;">VenuePro Administration</div>
    <div style="font-size:0.85rem; color:var(--danger); font-weight:600;">RESTRICTED ACCESS • AUTHORIZED PERSONNEL ONLY</div>
  </div>

  <div class="auth-card">
    <h2 style="font-size:1.4rem; margin-bottom:4px;">Administrator Sign In</h2>
    <p style="margin-bottom:24px; font-size:0.875rem;">Enter credentials with multi-factor privilege access.</p>

    <form action="admin-dashboard.php">
      <div class="form-group">
        <label class="form-label">Admin SSO / Email</label>
        <input type="email" name="email" class="form-control" placeholder="admin@venuepro.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Security Key / Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full mb-16" style="background:#0f172a; border-color:#0f172a;">Authenticate Admin Console →</button>
    </form>
    <div class="text-xs text-center text-muted">All session activities are audited and logged according to SOC-2 standards.</div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>