<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Application Under Review</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card" style="text-align:center; max-width:520px; padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#fef3c7; color:#d97706; display:flex; align-items:center; justify-content:center; font-size:32px; margin:0 auto 20px;">⏳</div>
    <h2 class="mb-8">Application Under Review</h2>
    <p class="mb-24">Thank you for applying to become a certified VenuePro catering partner. Our compliance and quality assurance team is reviewing your culinary credentials and commercial kitchen license.</p>

    <div class="card mb-24" style="background:var(--gray-50); text-align:left;">
      <div class="flex-between mb-12">
        <span class="text-sm font-semibold">Application Stage</span>
        <span class="pill pill-pending">In Review</span>
      </div>
      <div class="progress-bar mb-8"><div class="progress-fill orange" style="width:65%;"></div></div>
      <small class="text-muted">Estimated verification: 24-48 business hours.</small>
    </div>

    <div class="flex gap-12">
      <a href="../index.php" class="btn btn-ghost" style="flex:1;">Return Home</a>
      <a href="caterer-dashboard.php" class="btn btn-outline" style="flex:1;">Preview Portal</a>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>