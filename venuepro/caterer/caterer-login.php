<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'caterer-dashboard.php';
    if ($u['role'] === 'caterer') {
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
  <title>VenuePro – Caterer Sign In</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <div style="font-size:1.5rem; font-weight:700;">VenuePro Catering</div>
    <div style="font-size:0.85rem; color:var(--gray-500);">Kitchen & Menu Management Portal</div>
  </div>

  <div class="auth-card">
    <h2 style="font-size:1.4rem; margin-bottom:4px;">Caterer Portal Sign In</h2>
    <p style="margin-bottom:24px; font-size:0.875rem;">Access your orders, menus, and fulfillment schedule.</p>

    <form action="caterer-dashboard.php">
      <div class="form-group">
        <label class="form-label">Culinary Partner Email</label>
        <input type="email" name="email" class="form-control" placeholder="caterer@venuepro.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full mb-16" style="background:#059669; border-color:#059669;">Open Kitchen Dashboard →</button>
    </form>
  </div>

  <div class="auth-footer">
    Want to offer catering on VenuePro? <a href="caterer-signup.php" style="font-weight:600;">Apply as a Caterer</a>
  </div>
<script src="../js/app.js"></script>
</body>
</html>