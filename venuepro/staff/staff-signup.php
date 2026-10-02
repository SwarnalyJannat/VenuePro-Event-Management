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
$db = getDBConnection();
$venues = $db->query("SELECT id, name FROM venues WHERE status = 'active' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Staff Registration</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page">
  <div class="auth-logo">
    <img src="../assets/logo.png" alt="VenuePro" class="auth-logo-img">
    <h1 style="font-size:1.75rem;">Join VenuePro as an Operations Staff Member</h1>
    <p>Coordinate logistics, manage event setups, and ensure flawless venue execution.</p>
  </div>

  <div class="auth-card" style="max-width:580px;">
    <form id="register-form" action="staff-signup.php" method="POST">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Sarah Jenkins" required>
        </div>
        <div class="form-group">
          <label class="form-label">Contact Phone *</label>
          <input type="tel" name="phone" class="form-control" placeholder="e.g. +1 (555) 349-2091" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Work Email Address *</label>
          <input type="email" name="email" class="form-control" placeholder="e.g. sarah.staff@venuepro.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Staff Employee ID *</label>
          <input type="text" name="staff_id" class="form-control" placeholder="e.g. STF-1004" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department *</label>
          <select name="department" class="form-control" required>
            <option value="Event Operations">Event Operations &amp; Staging</option>
            <option value="Audio/Visual">Audio/Visual &amp; Acoustics Engineering</option>
            <option value="Guest Hospitality">Guest Hospitality &amp; VIP Concierge</option>
            <option value="Logistics & Safety">Facility Safety &amp; Logistics</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Operational Role / Title *</label>
          <select name="role_title" class="form-control" required>
            <option value="Lead Event Coordinator">Lead Event Coordinator</option>
            <option value="Senior AV Technician">Senior AV Technician</option>
            <option value="Stage & Lighting Specialist">Stage &amp; Lighting Specialist</option>
            <option value="Hospitality Operations Supervisor">Hospitality Operations Supervisor</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Primary Assigned Venue</label>
        <select name="assigned_venue_id" class="form-control">
          <option value="">All Properties (Floating Staff)</option>
          <?php foreach ($venues as $v): ?>
          <option value="<?= $v['id'] ?>"><?= e($v['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Create Password *</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full mb-8" style="background:#7c3aed; border-color:#7c3aed;">Complete Staff Registration →</button>
      <div class="text-xs text-center text-muted" style="margin-top:12px;">Staff credentials are authenticated against enterprise duty logs.</div>
    </form>
  </div>

  <div class="auth-footer" style="margin-top:16px; text-align:center; font-size:0.875rem;">
    Already have a staff account? <a href="staff-login.php" style="font-weight:600; color:#7c3aed;">Sign in to Staff Workspace</a>
  </div>
<script src="../js/app.js"></script>
</body>
</html>