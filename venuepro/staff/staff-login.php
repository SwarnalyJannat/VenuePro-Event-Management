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
  <title>VenuePro – Staff Sign In</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <div style="font-size:1.5rem; font-weight:700;">VenuePro Staff Portal</div>
    <div style="font-size:0.85rem; color:var(--gray-500);">Event Logistics & Coordination</div>
  </div>

  <div class="auth-card">
    <h2 style="font-size:1.4rem; margin-bottom:4px;">Staff Sign In</h2>
    <p style="margin-bottom:24px; font-size:0.875rem;">Access your daily assignments and setup schedules.</p>

    <form action="staff-dashboard.php">
      <div class="form-group">
        <label class="form-label">Staff Email or Employee ID</label>
        <input type="text" name="email" class="form-control" placeholder="staff@venuepro.com or Staff ID" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full mb-16" style="background:#7c3aed; border-color:#7c3aed;">Open Staff Workspace →</button>
    </form>
  </div>
  <!-- <div class="auth-footer">New staff member? <a href="staff-signup.php" style="font-weight:600;">Activate with Invite Code</a></div> -->
<script src="../js/app.js"></script>
</body>
</html>