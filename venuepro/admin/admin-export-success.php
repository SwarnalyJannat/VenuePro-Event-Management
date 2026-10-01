<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Report Generated</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card" style="text-align:center; max-width:500px; padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#eff6ff; color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px;">📥</div>
    <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:8px;">Report Generated!</h1>
    <p class="text-muted mb-24">The enterprise revenue breakdown for Jan 2026 – Sep 2026 has been generated.</p>
    <div class="flex gap-12">
      <a href="admin-reports.php" class="btn btn-outline" style="flex:1;">Back to Reports</a>
      <a href="#" class="btn btn-primary" style="flex:1;">Download PDF</a>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>