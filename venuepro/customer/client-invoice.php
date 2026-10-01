<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();

$bookingId = (int)($_GET['booking_id'] ?? 0);
$booking = null; $invoice = null; $singularItems = [];

if ($bookingId > 0) {
    $stmtB = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, cp.title AS package_title FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id WHERE b.id=? AND b.customer_id=? LIMIT 1");
    $stmtB->execute([$bookingId, $currentUser['id']]);
    $booking = $stmtB->fetch();
    if ($booking) {
        $stmtI = $db->prepare("SELECT * FROM invoices WHERE booking_id=? LIMIT 1");
        $stmtI->execute([$bookingId]);
        $invoice = $stmtI->fetch();
        $stmtSi = $db->prepare("SELECT * FROM booking_singular_items WHERE booking_id=?");
        $stmtSi->execute([$bookingId]);
        $singularItems = $stmtSi->fetchAll();
    }
}
// Fallback to latest invoice for this customer
if (!$booking) {
    $stmtLast = $db->prepare("SELECT b.*, v.name AS venue_name, v.address AS venue_address, cp.title AS package_title FROM bookings b JOIN venues v ON b.venue_id=v.id LEFT JOIN catering_packages cp ON b.package_id=cp.id WHERE b.customer_id=? ORDER BY b.created_at DESC LIMIT 1");
    $stmtLast->execute([$currentUser['id']]);
    $booking = $stmtLast->fetch();
    if ($booking) {
        $stmtI = $db->prepare("SELECT * FROM invoices WHERE booking_id=? LIMIT 1");
        $stmtI->execute([$booking['id']]);
        $invoice = $stmtI->fetch();
        $stmtSi = $db->prepare("SELECT * FROM booking_singular_items WHERE booking_id=?");
        $stmtSi->execute([$booking['id']]);
        $singularItems = $stmtSi->fetchAll();
    }
}
$invoiceCode  = $invoice ? $invoice['invoice_code'] : 'INV-' . date('Y') . '-0000';
$issueDate    = $invoice ? date('F j, Y', strtotime($invoice['issue_date'])) : date('F j, Y');
$dueDate      = $invoice ? date('F j, Y', strtotime($invoice['due_date'])) : date('F j, Y');
$subtotal     = $booking ? (float)$booking['subtotal'] : 0;
$serviceFee   = $booking ? (float)$booking['service_fee'] : 0;
$vatTax       = $booking ? (float)$booking['vat_tax'] : 0;
$totalAmount  = $booking ? (float)$booking['total_amount'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Official Tax Invoice <?= e($invoiceCode) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { background: #e2e8f0; padding: 40px 20px; }
    .invoice-card {
      max-width: 800px; margin: 0 auto; background: #fff;
      border-radius: var(--radius); box-shadow: var(--shadow-lg); overflow: hidden;
    }
  </style>
</head>
<body>
  <div class="invoice-card">
    <div class="invoice-header">
      <div>
        <div style="font-size:1.8rem; font-weight:800;">VenuePro Enterprise</div>
        <div style="font-size:0.8rem; opacity:0.8;">Commercial Venue & Logistics Orchestration</div>
        <div style="font-size:0.75rem; margin-top:8px; opacity:0.7;">VAT Reg: US-98420-VN • info@venuepro.com</div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:1.6rem; font-weight:900; letter-spacing:0.05em;">TAX INVOICE</div>
        <div style="font-size:0.9rem; font-weight:600; color:#60a5fa;">#<?= e($invoiceCode) ?></div>
        <div style="font-size:0.75rem; opacity:0.8; margin-top:4px;">Date: <?= e($issueDate) ?></div>
      </div>
    </div>

    <div class="invoice-section">
      <div class="grid-2 mb-24">
        <div>
          <div class="text-xs text-muted font-bold mb-4">BILLED TO</div>
          <div class="font-bold"><?= e($currentUser['name']) ?></div>
          <div class="text-xs text-muted"><?= e($currentUser['email']) ?></div>
          <div class="text-xs text-muted"><?= e($currentUser['phone'] ?? 'Client ID: #' . $currentUser['id']) ?></div>
        </div>
        <div>
          <div class="text-xs text-muted font-bold mb-4">EVENT RESERVATION</div>
          <div class="font-bold"><?= $booking ? e($booking['venue_name']) : 'Venue Reservation' ?></div>
          <div class="text-xs text-muted">Date of Execution: <?= $booking ? date('F j, Y', strtotime($booking['event_date'])) : 'N/A' ?></div>
          <div class="text-xs text-muted">Time: <?= $booking ? substr($booking['start_time'],0,5) . ' – ' . substr($booking['end_time'],0,5) : 'N/A' ?></div>
          <div class="text-xs text-muted">Ref: Booking <strong><?= $booking ? e($booking['booking_code']) : '#BK-XXXX' ?></strong></div>
        </div>
      </div>

      <table class="invoice-table mb-24">
        <thead>
          <tr>
            <th>ITEM DESCRIPTION</th>
            <th>QTY</th>
            <th>UNIT PRICE</th>
            <th>TOTAL (USD)</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($booking): ?>
          <tr>
            <td>
              <div class="font-bold"><?= e($booking['venue_name']) ?> – <?= (int)($booking['duration_hours'] ?? 4) ?>h Session</div>
              <div class="text-xs text-muted"><?= e($booking['venue_address'] ?? 'Full facility access and amenities') ?></div>
            </td>
            <td>1</td>
            <td>$<?= number_format($booking['venue_cost'], 2) ?></td>
            <td class="font-bold">$<?= number_format($booking['venue_cost'], 2) ?></td>
          </tr>
          <?php if (!empty($booking['package_title']) && $booking['package_cost'] > 0): ?>
          <tr>
            <td>
              <div class="font-bold">Catering: <?= e($booking['package_title']) ?></div>
              <div class="text-xs text-muted"><?= (int)$booking['guest_count'] ?> Guests · Curated Culinary Tier</div>
            </td>
            <td><?= (int)$booking['guest_count'] ?></td>
            <td>$<?= number_format($booking['package_cost'] / max(1, $booking['guest_count']), 2) ?></td>
            <td class="font-bold">$<?= number_format($booking['package_cost'], 2) ?></td>
          </tr>
          <?php endif; ?>
          <?php if (!empty($booking['staffing_cost']) && $booking['staffing_cost'] > 0): ?>
          <tr>
            <td>
              <div class="font-bold">Professional Event Logistics Staff</div>
              <div class="text-xs text-muted">Lead Event Coordinator, Waitstaff & Setup Crew</div>
            </td>
            <td>1</td>
            <td>$<?= number_format($booking['staffing_cost'], 2) ?></td>
            <td class="font-bold">$<?= number_format($booking['staffing_cost'], 2) ?></td>
          </tr>
          <?php endif; ?>
          <?php foreach ($singularItems as $si): ?>
          <tr>
            <td>
              <div class="font-bold"><?= e($si['emoji'] ?? '🍽️') ?> <?= e($si['item_name']) ?></div>
              <div class="text-xs text-muted"><?= !empty($si['notes']) ? e($si['notes']) : 'À-la-carte culinary add-on' ?></div>
            </td>
            <td><?= (int)$si['quantity'] ?></td>
            <td>$<?= number_format($si['unit_price'], 2) ?></td>
            <td class="font-bold">$<?= number_format($si['subtotal'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr style="border-top:1px solid var(--gray-200);">
            <td colspan="3" style="text-align:right; font-weight:600;">Subtotal:</td>
            <td class="font-semibold">$<?= number_format($subtotal, 2) ?></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:right; font-size:0.85rem; color:var(--gray-500);">Service Fee (10%):</td>
            <td style="font-size:0.85rem; color:var(--gray-500);">$<?= number_format($serviceFee, 2) ?></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:right; font-size:0.85rem; color:var(--gray-500);">Taxes & VAT (8%):</td>
            <td style="font-size:0.85rem; color:var(--gray-500);">$<?= number_format($vatTax, 2) ?></td>
          </tr>
          <tr class="invoice-total-row">
            <td colspan="3" style="text-align:right; padding-right:16px;">TOTAL AMOUNT PAID:</td>
            <td>$<?= number_format($totalAmount, 2) ?></td>
          </tr>
          <?php else: ?>
          <tr><td colspan="4" style="text-align:center; padding:24px; color:var(--gray-400);">No invoice records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="card mb-24" style="background:var(--gray-50); padding:16px;">
        <div class="text-xs text-muted">Payment status: <strong class="text-success">PAID IN FULL via <?= e(!empty($booking['payment_card_last4']) ? 'Card ending in ' . $booking['payment_card_last4'] : 'Online Card Authorization') ?></strong> on <?= e($issueDate) ?>. Transaction authorized by VenuePro Gateway.</div>
      </div>

      <div class="flex-between">
        <a href="customer-dashboard.php" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print / Save as PDF</button>
      </div>
    </div>
  </div>
<script src="../js/app.js"></script>
</body>
</html>