<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
if (isLoggedIn()) {
    $u = getCurrentUser();
    $dest = 'caterer-dashboard.php';
    if ($u['role'] === 'caterer') {
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
  <title>VenuePro – Caterer Partner Application</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Join VenuePro as a Catering Partner</h1>
    <p>Deliver exceptional dining experiences for premium enterprise events.</p>
  </div>

  <div class="auth-card" style="max-width:560px;">
    <form action="caterer-pending.php">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Catering Business Name</label>
          <input type="text" class="form-control" placeholder="Epicurean Events Co." required>
        </div>
        <div class="form-group">
          <label class="form-label">Primary Contact Person</label>
          <input type="text" class="form-control" placeholder="Chef Marcus Vance" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Contact Email</label>
          <input type="email" class="form-control" placeholder="marcus@epicurean.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="tel" class="form-control" placeholder="+1 (555) 349-2091" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Kitchen / Commercial Prep Facility Address</label>
        <input type="text" class="form-control" placeholder="Suite 400, 782 Culinary Way, Downtown" required>
      </div>

      <div class="form-group">
        <label class="form-label">Culinary Specialization</label>
        <select class="form-control">
          <option>Contemporary Fine Dining & Plated Service</option>
          <option>High-End Buffet & Interactive Food Stations</option>
          <option>Artisanal Canapés & Cocktail Reception</option>
          <option>Global Fusion & Dietary Specialized Menus</option>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Create Password</label>
          <input type="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full mb-8" style="background:#059669; border-color:#059669;">Submit Caterer Application →</button>
      <div class="form-hint text-center">Applications are vetted by our compliance team within 2 business days.</div>
    </form>
  </div>
<script src="../js/app.js"></script>
</body>
</html>