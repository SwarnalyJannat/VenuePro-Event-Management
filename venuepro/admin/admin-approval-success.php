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
  <title>VenuePro Admin – Booking Approved</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-card" style="text-align:center; max-width:500px; padding:48px 36px;">
    <div style="width:72px; height:72px; border-radius:50%; background:#dcfce7; color:#16a34a; display:flex; align-items:center; justify-content:center; font-size:36px; margin:0 auto 20px;">✓</div>
    <h1 style="font-size:1.8rem; font-weight:800; margin-bottom:8px;">Booking Approved!</h1>
    <p class="text-muted mb-24">#BK-9021 has been marked as confirmed. Notification emails and client invoice receipts have been dispatched.</p>
    <a href="admin-pending-bookings.php" class="btn btn-primary btn-full">Return to Pending List →</a>
  </div>
<script src="../js/app.js"></script>
</body>
</html>