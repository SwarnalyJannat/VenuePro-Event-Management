<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'customer-dashboard.php';
    if ($u['role'] === 'customer') {
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

    <form action="../registration-successful.php">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" class="form-control" placeholder="Jane Doe" required>
      </div>

      <div class="form-group">
        <label class="form-label">Work Email address</label>
        <input type="email" class="form-control" placeholder="jane@company.com" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <div class="form-group flex-center gap-8">
        <input type="checkbox" id="terms" required>
        <label for="terms" style="font-size:0.8rem; color:var(--gray-600); cursor:pointer;">I agree to the <a href="../terms-of-service.php">Terms of Service</a> & <a href="../privacy-policy.php">Privacy Policy</a></label>
      </div>

      <button type="submit" class="btn btn-primary btn-full mb-16">Create Account →</button>
    </form>
  </div>

  <div class="auth-footer">
    Already registered? <a href="customer-login.php" style="font-weight:600;">Sign in here</a>
  </div>
<script src="../js/app.js"></script>
</body>
</html>