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
  <title>VenuePro – Choose Your Role</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="assets/logo2.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:2rem; font-weight:800;">Choose your role to get started</h1>
    <p>Select the profile that best describes your needs to tailor your VenuePro experience.</p>
  </div>

  <div class="role-grid" style="max-width:520px; grid-template-columns:repeat(2, 1fr); margin:0 auto;">
    <a href="customer/customer-register.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">🛍️</div>
      <div class="role-name" style="color:var(--gray-900);">Customer</div>
      <div class="role-desc">Looking for top-tier catering services and venues for your next event.</div>
    </a>
    <a href="admin/admin-signup.php" class="role-card" style="text-decoration:none;">
      <div class="role-icon">🛡️</div>
      <div class="role-name" style="color:var(--gray-900);">Admin</div>
      <div class="role-desc">Full control over platform operations, users, and venue management.</div>
    </a>
  </div>

  <div class="auth-footer">
    Already have an account? <a href="login-role.php" style="font-weight:600;">Sign in here</a>
  </div>
<script src="js/app.js"></script>
</body>
</html>