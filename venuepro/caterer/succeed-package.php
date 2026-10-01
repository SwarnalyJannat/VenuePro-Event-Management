<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Package Published Successfully</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card" style="text-align:center; max-width:500px; padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px;">✓</div>
    <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:8px;">Package Published!</h1>
    <p class="text-muted mb-24"><strong>Grand Sovereign Banquet</strong> is now live in the VenuePro customer booking portal.</p>
    <div class="flex gap-12">
      <a href="package-details.php" class="btn btn-outline" style="flex:1;">View Details</a>
      <a href="caterer-food-packages.php" class="btn btn-primary" style="flex:1; background:#059669; border-color:#059669;">Return to Library</a>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>