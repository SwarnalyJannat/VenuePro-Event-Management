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
  <title>VenuePro – Admin Provisioning</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Provision System Administrator</h1>
    <p>Master security token required to register root administrative privileges.</p>
  </div>

  <div class="auth-card">
    <form action="../registration-successful.php">
      <div class="form-group">
        <label class="form-label">Enterprise Security Master Token</label>
        <input type="password" class="form-control" placeholder="••••••••••••••••" required>
      </div>
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" placeholder="Alex Sterling" required>
      </div>
      <div class="form-group">
        <label class="form-label">Corporate Email</label>
        <input type="email" class="form-control" placeholder="alex.sterling@venuepro.internal" required>
      </div>
      <div class="form-group">
        <label class="form-label">Set Passphrase</label>
        <input type="password" class="form-control" placeholder="••••••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="background:#0f172a; border-color:#0f172a;">Provision Account →</button>
    </form>
  </div>
<script src="../js/app.js"></script>
</body>
</html>