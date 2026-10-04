<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
$currentUser = getCurrentUser();
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Registration Successful</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card" style="text-align:center; max-width:480px; padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px;">✓</div>
    <h2 class="mb-8">Registration Successful!</h2>
    <p class="mb-24">Your profile has been created. You can now sign in and access the full capabilities of the VenuePro enterprise suite.</p>
    <a href="venues.php" class="btn btn-primary btn-full">Proceed to Sign In →</a>
  </div>
<script src="js/app.js"></script>
</body>
</html>