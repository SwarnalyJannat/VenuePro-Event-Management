<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'staff-dashboard.php';
    if ($u['role'] === 'staff') {
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
  <title>VenuePro – Staff Account Activation</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Activate Staff Account</h1>
    <p>Enter the invitation code provided by your administrator.</p>
  </div>

  <div class="auth-card">
    <form action="../registration-successful.php">
      <div class="form-group">
        <label class="form-label">Staff Invitation Code</label>
        <input type="text" class="form-control" placeholder="e.g. STF-8821-XP" required>
      </div>
      <div class="form-group">
        <label class="form-label">Full Legal Name</label>
        <input type="text" class="form-control" placeholder="e.g. Jane Doe" required>
      </div>
      <div class="form-group">
        <label class="form-label">Assigned Work Email</label>
        <input type="email" class="form-control" placeholder="s.jenkins@venuepro.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Set Account Password</label>
        <input type="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="background:#7c3aed; border-color:#7c3aed;">Complete Activation →</button>
    </form>
  </div>
<script src="../js/app.js"></script>
</body>
</html>