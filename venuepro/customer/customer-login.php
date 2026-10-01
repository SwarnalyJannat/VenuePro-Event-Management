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
  <title>VenuePro – Customer Sign In</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <div style="font-size:1.5rem; font-weight:700;">VenuePro</div>
    <div style="font-size:0.85rem; color:var(--gray-500);">Enterprise Event Management</div>
  </div>

  <div class="auth-card">
    <h2 style="font-size:1.4rem; margin-bottom:4px;">Sign in to your account</h2>
    <p style="margin-bottom:24px; font-size:0.875rem;">Welcome back! Please enter your details.</p>

    <form action="customer-dashboard.php">
      <div class="form-group">
        <label class="form-label">Email address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input type="email" class="form-control with-icon" value="mahmud@enterprise.com" placeholder="name@company.com" required>
        </div>
      </div>

      <div class="form-group">
        <div class="flex-between mb-4">
          <label class="form-label" style="margin-bottom:0;">Password</label>
          <a href="#" class="text-sm">Forgot Password?</a>
        </div>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" class="form-control with-icon" value="••••••••••••" placeholder="Enter password" required>
        </div>
      </div>

      <div class="form-group flex-center gap-8">
        <input type="checkbox" id="remember" checked accent-color="var(--primary)">
        <label for="remember" style="font-size:0.85rem; color:var(--gray-600); cursor:pointer;">Remember me for 30 days</label>
      </div>

      <button type="submit" class="btn btn-primary btn-full mb-16">Sign In →</button>

      <div class="divider">OR CONTINUE WITH</div>

      <button type="button" class="btn-google">
        <svg width="18" height="18" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.34 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.99 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.34 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/></svg>
        Continue with Google
      </button>
    </form>
  </div>

  <div class="auth-footer">
    Don't have an account? <a href="customer-register.php" style="font-weight:600;">Register for VenuePro</a>
  </div>
<script src="../js/app.js"></script>
</body>
</html>