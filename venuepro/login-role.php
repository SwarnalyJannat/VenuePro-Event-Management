<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'index.php';
    if ($u['role'] === '') {
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
  <title>VenuePro – Sign in Role Selection</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Sign in to VenuePro</h1>
    <p>Select your profile role to access your dedicated workspace.</p>
  </div>

  <div class="role-grid">
    <a href="customer/customer-login.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">🛍️</div>
      <div class="role-name" style="color:var(--gray-900);">Customer</div>
      <div class="role-desc">Looking for top-tier catering services and venues for your next event.</div>
    </a>
    <a href="admin/admin-login.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">🛡️</div>
      <div class="role-name" style="color:var(--gray-900);">Admin</div>
      <div class="role-desc">Full control over platform operations, users, and venue management.</div>
    </a>
    <a href="staff/staff-login.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">👥</div>
      <div class="role-name" style="color:var(--gray-900);">Staff</div>
      <div class="role-desc">Manage day-to-day operations, guest lists, and event coordination.</div>
    </a>
    <a href="caterer/caterer-login.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">🍴</div>
      <div class="role-name" style="color:var(--gray-900);">Caterer</div>
      <div class="role-desc">Manage menus, bookings, and kitchen operations for your venue.</div>
    </a>
  </div>

  <div class="auth-footer">
    Don't have an account yet? <a href="signup-role.php" style="font-weight:600;">Choose a role to register</a>
  </div>
<script src="js/app.js"></script>
</body>
</html>