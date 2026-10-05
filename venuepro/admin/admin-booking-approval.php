<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('admin', 'admin-login.php');
$db = getDBConnection();
?>
<?php
$bookingId = (int)($_GET['booking_id'] ?? ($_GET['id'] ?? 0));
$bookingDetail = null;
if ($bookingId > 0) {
    $stmtBD = $db->prepare("SELECT b.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, v.name AS venue_name, cp.title AS package_title FROM bookings b JOIN users u ON b.customer_id=u.id JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id WHERE b.id=? LIMIT 1");
    $stmtBD->execute([$bookingId]);
    $bookingDetail = $stmtBD->fetch();
}
if (!$bookingDetail) {
    $stmtBD = $db->query("SELECT b.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone, v.name AS venue_name, cp.title AS package_title FROM bookings b JOIN users u ON b.customer_id=u.id JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id ORDER BY (b.booking_status='pending') DESC, b.created_at DESC LIMIT 1");
    $bookingDetail = $stmtBD ? $stmtBD->fetch() : null;
    $bookingId = $bookingDetail ? (int)$bookingDetail['id'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Admin – Approval Review</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div class="sidebar-logo-text">VenuePro</div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Governance</div>
        <a href="admin-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="admin-pending-bookings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> Bookings Approvals
        </a>
        <a href="venue-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> Venue Catalog
        </a>
        <a href="admin-catering-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg> Catering Oversight
        </a>
        <a href="admin-staff-management.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> Staff Directory
        </a>
        <a href="admin-reports.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reports &amp; Analytics
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

    <div class="main-content">
      <header class="topbar">
        <label for="sidebar-toggle" class="sidebar-toggle-btn" title="Toggle Sidebar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </label>
        <div class="topbar-search">
          <span class="topbar-search-icon">🔍</span>
          <input type="text" placeholder="Search bookings, venues, staff, caterers...">
        </div>
        <div class="topbar-actions">
          <a href="notification-center.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">8</span>
            🔔
          </a>
          <a href="admin-profile.php" class="topbar-user" style="text-decoration:none; cursor:pointer;" title="Edit My Profile">
            <div class="user-avatar" style="background:#0f172a;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'Administrator') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Admin')) ?></div>
            </div>
          </a>
        </div>
      </header>

      <main class="page-body">
        <?php if (!$bookingDetail): ?>
        <div class="card" style="text-align:center; padding:60px 24px; color:var(--gray-400);">
          <div style="font-size:3rem; margin-bottom:12px;">📋</div>
          <h2>No Booking Found</h2>
          <p>The requested booking could not be located.</p>
          <a href="admin-pending-bookings.php" class="btn btn-primary mt-16">Return to Pending Bookings</a>
        </div>
        <?php else:
          $bCode = e($bookingDetail['booking_code']);
          $st = strtolower($bookingDetail['booking_status']);
        ?>
        <div class="breadcrumb">
          <a href="admin-pending-bookings.php">Bookings</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current"><?= $bCode ?> Review</span>
        </div>

        <div class="flex-between mb-24">
          <div>
            <h1>Booking Approval Review – <?= $bCode ?></h1>
            <p>Submitted by <?= e($bookingDetail['customer_name']) ?> on <?= date('M j, Y', strtotime($bookingDetail['created_at'])) ?></p>
          </div>
          <span class="pill pill-<?= $st ?>" style="font-size:0.85rem; padding:6px 14px;">STATUS: <?= strtoupper($st) ?></span>
        </div>

        <div class="grid-2" style="grid-template-columns: 2fr 1.2fr; gap:24px;">
          <div>
            <div class="card mb-24">
              <h3 class="mb-16">Reservation Specifications</h3>
              <div class="grid-2 gap-16 mb-16">
                <div>
                  <div class="text-xs text-muted">Selected Venue</div>
                  <div class="font-bold text-base"><?= e($bookingDetail['venue_name']) ?></div>
                </div>
                <div>
                  <div class="text-xs text-muted">Event Schedule</div>
                  <div class="font-bold text-base"><?= date('F j, Y', strtotime($bookingDetail['event_date'])) ?> · <?= substr($bookingDetail['start_time'], 0, 5) ?> - <?= substr($bookingDetail['end_time'], 0, 5) ?></div>
                </div>
                <div>
                  <div class="text-xs text-muted">Attendance Count</div>
                  <div class="font-bold text-base"><?= number_format($bookingDetail['guest_count']) ?> Attendees</div>
                </div>
                <div>
                  <div class="text-xs text-muted">Catering Selection</div>
                  <div class="font-bold text-base"><?= e($bookingDetail['package_title'] ?? 'Direct Venue Hire / None') ?></div>
                </div>
              </div>
              <div style="border-top:1px solid var(--gray-200); padding-top:12px;" class="flex-between">
                <span class="font-bold">Total Estimated Booking Value</span>
                <span class="font-bold text-primary" style="font-size:1.3rem;">$<?= number_format($bookingDetail['total_amount'], 2) ?></span>
              </div>
            </div>

            <div class="card">
              <h3 class="mb-12">Client Profile</h3>
              <div class="text-sm mb-4"><span class="text-muted">Client Name:</span> <strong><?= e($bookingDetail['customer_name']) ?></strong></div>
              <div class="text-sm mb-4"><span class="text-muted">Contact:</span> <?= e($bookingDetail['customer_email']) ?> · <?= e($bookingDetail['customer_phone'] ?? 'N/A') ?></div>
              <div class="text-sm"><span class="text-muted">Event Name:</span> <?= e($bookingDetail['event_name']) ?></div>
            </div>
          </div>

          <div class="card" style="background:var(--gray-50);">
            <h3 class="mb-16">Approval Decision</h3>
            <div id="decision-alert" style="display:none; margin-bottom:12px; padding:10px 14px; border-radius:6px; font-size:0.85rem; font-weight:600;"></div>

            <div class="form-group mb-16">
              <label class="form-label">Internal Compliance Notes (Optional)</label>
              <textarea id="admin-notes" class="form-control" rows="3" placeholder="Add approval remarks or requirements..."></textarea>
            </div>

            <button type="button" id="btn-approve" class="btn btn-primary btn-full mb-12" style="background:#16a34a; border-color:#16a34a; font-weight:700;">✓ Approve &amp; Confirm Booking</button>
            <button type="button" id="btn-reject" class="btn btn-danger btn-outline btn-full font-bold">✗ Reject Reservation</button>
          </div>
        </div>
        <?php endif; ?>
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Administration. SOC-2 Certified.</div>
      </footer>
    </div>
  </div>
<script src="../js/app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var bId = <?= (int)$bookingId ?>;
  var bCode = '<?= $bookingDetail ? e($bookingDetail['booking_code']) : '' ?>';
  var approveBtn = document.getElementById('btn-approve');
  var rejectBtn = document.getElementById('btn-reject');
  var notesInput = document.getElementById('admin-notes');
  var alertBox = document.getElementById('decision-alert');

  if (approveBtn) {
    approveBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      if (!confirm('Approve and confirm booking ' + bCode + '?')) return;
      approveBtn.disabled = true;
      approveBtn.textContent = 'Processing Approval...';
      try {
        var res = await fetch('../api/bookings.php?action=update_status', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            booking_id: bId,
            status: 'confirmed',
            notes: notesInput ? notesInput.value.trim() : ''
          })
        });
        var data = await res.json();
        if (data.success) {
          window.location.href = 'admin-approval-success.php?booking_id=' + bId;
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (data.message || 'Error updating status');
          approveBtn.disabled = false;
          approveBtn.textContent = '✓ Approve & Confirm Booking';
        }
      } catch(err) {
        alert('Connection error');
        approveBtn.disabled = false;
        approveBtn.textContent = '✓ Approve & Confirm Booking';
      }
    });
  }

  if (rejectBtn) {
    rejectBtn.addEventListener('click', async function(e) {
      e.preventDefault();
      var reason = prompt('Please enter rejection reason:', 'Scheduling or capacity conflict') || 'Scheduling conflict';
      if (!reason) return;
      rejectBtn.disabled = true;
      rejectBtn.textContent = 'Rejecting...';
      try {
        var res = await fetch('../api/bookings.php?action=update_status', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            booking_id: bId,
            status: 'rejected',
            notes: reason
          })
        });
        var data = await res.json();
        if (data.success) {
          window.location.href = 'booking-rejected.php?booking_id=' + bId;
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#991b1b';
          alertBox.textContent = '⚠️ ' + (data.message || 'Error rejecting booking');
          rejectBtn.disabled = false;
          rejectBtn.textContent = '✗ Reject Reservation';
        }
      } catch(err) {
        alert('Connection error');
        rejectBtn.disabled = false;
        rejectBtn.textContent = '✗ Reject Reservation';
      }
    });
  }
});
</script>

</body>
</html>