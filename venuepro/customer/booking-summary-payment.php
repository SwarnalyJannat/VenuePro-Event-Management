<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('customer', 'customer-login.php');
$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro – Booking Step 3: Payment &amp; Singular Add-On Items</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Add-On Items Selector */
    .addon-card {
      border: 1.5px solid var(--gray-200);
      background: #fff;
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .addon-item {
      display: flex; align-items: center; gap: 14px;
      padding: 12px 16px; border: 1.5px solid var(--gray-200);
      border-radius: var(--radius-sm); background: #fff;
      transition: all 0.2s; margin-bottom: 10px;
    }
    .addon-item:hover { border-color: var(--primary); background: #f8fafc; }
    .addon-item.selected { border-color: var(--primary); background: #eff6ff; }
    .addon-img {
      width: 52px; height: 52px; border-radius: 8px;
      background: #f1f5f9; display: flex; align-items: center;
      justify-content: center; font-size: 1.8rem; flex-shrink: 0;
    }
    .addon-info { flex: 1; }
    .addon-name { font-weight: 700; font-size: 0.875rem; color: var(--gray-900); }
    .addon-desc { font-size: 0.75rem; color: var(--gray-500); margin-top: 2px; }
    .addon-price { font-weight: 800; font-size: 0.9rem; color: var(--primary); white-space: nowrap; }
    .addon-qty {
      display: flex; align-items: center; gap: 6px; flex-shrink: 0;
    }
    .qty-btn {
      width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--gray-300);
      background: #fff; font-size: 1rem; font-weight: 700; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: all 0.15s; line-height: 1;
    }
    .qty-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .qty-input {
      width: 44px; text-align: center; border: 1.5px solid var(--gray-200);
      border-radius: 6px; padding: 4px; font-weight: 700;
      font-size: 0.85rem; background: #fff;
    }

    .selected-addons-list { display: flex; flex-direction: column; gap: 6px; }
    .selected-addon-row {
      display: flex; justify-content: space-between; font-size: 0.82rem; color: var(--gray-700);
    }

    .total-box {
      background: #eff6ff; padding: 16px 18px; border-radius: var(--radius-sm);
      margin-top: 16px;
    }
    .decision-radio-group {
      display: flex; gap: 12px; margin-bottom: 16px;
    }
    .decision-radio-label {
      flex: 1; padding: 12px 14px; border: 1.5px solid var(--gray-300);
      border-radius: var(--radius-sm); cursor: pointer; text-align: center;
      font-weight: 600; font-size: 0.85rem; transition: all 0.15s;
    }
    .decision-radio-label.active {
      border-color: var(--primary); background: #eff6ff; color: var(--primary);
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Customer Sidebar -->
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
          <div class="sidebar-logo-sub">Customer Portal</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Switch Role / Logout
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
          <input type="text" placeholder="Search event venues, bookings, menus...">
        </div>
        <div class="topbar-actions">
          <a href="customer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">3</span>
            🔔
          </a>
          <a href="customer-chat.php" class="topbar-icon-btn" title="Contact Venue Staff">
            💬
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#2563eb;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'Customer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <!-- Booking Steps -->
        <div class="steps">
          <div class="step done"><div class="step-circle">✓</div><div class="step-label">Venue</div></div>
          <div class="step-line done"></div>
          <div class="step done"><div class="step-circle">✓</div><div class="step-label">Logistics &amp; Package</div></div>
          <div class="step-line done"></div>
          <div class="step active"><div class="step-circle">3</div><div class="step-label">Payment &amp; Add-Ons</div></div>
        </div>

        <div style="max-width:1200px; margin:0 auto; display:grid; grid-template-columns:1.2fr 1fr; gap:28px; align-items:start;">

          <!-- LEFT COLUMN: Singular Items Add-On Card FIRST, then Payment -->
          <div style="display:flex; flex-direction:column; gap:24px;">

            <!-- ===== ADD-ON SINGULAR ITEMS CARD (Beside the package) ===== -->
            <div class="addon-card">
              <div class="flex-between mb-12">
                <div class="flex-center gap-8">
                  <div style="width:38px; height:38px; border-radius:50%; background:#eff6ff; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">🍽️</div>
                  <div>
                    <h2 style="font-size:1.25rem; font-weight:800; margin:0;">Singular Add-On Items</h2>
                    <p class="text-xs text-muted" style="margin:2px 0 0;">Would you like to add any singular items beside your main package?</p>
                  </div>
                </div>
                <span class="stat-badge positive" style="font-size:0.75rem;">From Caterer</span>
              </div>

              <div class="decision-radio-group">
                <label class="decision-radio-label active" id="tab-addons-yes" onclick="toggleAddonChoice(true)">
                  ✓ Yes, add singular items
                </label>
                <label class="decision-radio-label" id="tab-addons-no" onclick="toggleAddonChoice(false)">
                  ✕ No, package only
                </label>
              </div>

              <div id="addon-selection-body">
                <div class="text-xs text-muted mb-16">
                  Select any singular items you need and adjust quantities. Your caterer will receive these along with your package order:
                </div>

                <!-- Item 1: Shrimp -->
                <div class="addon-item" id="addon-shrimp">
                  <div class="addon-img">🍤</div>
                  <div class="addon-info">
                    <div class="addon-name">Grilled Tiger Shrimp</div>
                    <div class="addon-desc">Garlic butter &amp; lemon zest · Gluten-Free · Freshly grilled</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('shrimp', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-shrimp" value="0" min="0" max="50" data-price="12.50" data-name="Grilled Tiger Shrimp" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('shrimp', 1)">+</button>
                  </div>
                  <div class="addon-price">$12.50/pc</div>
                </div>

                <!-- Item 2: Coke -->
                <div class="addon-item" id="addon-coke">
                  <div class="addon-img">🥤</div>
                  <div class="addon-info">
                    <div class="addon-name">Premium Coca-Cola (Glass Bottle)</div>
                    <div class="addon-desc">Chilled 330ml · Served on ice with fresh lime</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('coke', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-coke" value="0" min="0" max="120" data-price="3.50" data-name="Coca-Cola (Glass)" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('coke', 1)">+</button>
                  </div>
                  <div class="addon-price">$3.50/btl</div>
                </div>

                <!-- Item 3: Yogurt Parfait -->
                <div class="addon-item" id="addon-yogurt">
                  <div class="addon-img">🍶</div>
                  <div class="addon-info">
                    <div class="addon-name">Greek Yogurt Parfait</div>
                    <div class="addon-desc">Granola &amp; seasonal berries · Vegetarian · GF</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('yogurt', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-yogurt" value="0" min="0" max="30" data-price="7.00" data-name="Greek Yogurt Parfait" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('yogurt', 1)">+</button>
                  </div>
                  <div class="addon-price">$7.00/cup</div>
                </div>

                <!-- Item 4: Dessert - Vanilla Panna Cotta -->
                <div class="addon-item" id="addon-panna">
                  <div class="addon-img">🍮</div>
                  <div class="addon-info">
                    <div class="addon-name">Vanilla Panna Cotta Dessert</div>
                    <div class="addon-desc">Mixed berry coulis &amp; mint garnish · Italian artisan classic</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('panna', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-panna" value="0" min="0" max="40" data-price="9.50" data-name="Vanilla Panna Cotta" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('panna', 1)">+</button>
                  </div>
                  <div class="addon-price">$9.50/srv</div>
                </div>

                <!-- Item 5: Sparkling Water -->
                <div class="addon-item" id="addon-water">
                  <div class="addon-img">💧</div>
                  <div class="addon-info">
                    <div class="addon-name">San Pellegrino Sparkling Water</div>
                    <div class="addon-desc">500ml premium Italian mineral water · Table service</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('water', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-water" value="0" min="0" max="30" data-price="4.50" data-name="San Pellegrino Sparkling" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('water', 1)">+</button>
                  </div>
                  <div class="addon-price">$4.50/btl</div>
                </div>

                <!-- Item 6: Truffle Bruschetta -->
                <div class="addon-item" id="addon-bruschetta">
                  <div class="addon-img">🥖</div>
                  <div class="addon-info">
                    <div class="addon-name">Truffle Bruschetta</div>
                    <div class="addon-desc">White truffle oil, sun-dried tomatoes · Vegetarian</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('bruschetta', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-bruschetta" value="0" min="0" max="30" data-price="6.00" data-name="Truffle Bruschetta" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('bruschetta', 1)">+</button>
                  </div>
                  <div class="addon-price">$6.00/pc</div>
                </div>

                <!-- Item 7: Cheese Board -->
                <div class="addon-item" id="addon-cheese">
                  <div class="addon-img">🧀</div>
                  <div class="addon-info">
                    <div class="addon-name">Artisan Cheese Board</div>
                    <div class="addon-desc">Brie, Gouda, Manchego · Serves 4 · Vegetarian</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('cheese', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-cheese" value="0" min="0" max="15" data-price="18.00" data-name="Artisan Cheese Board" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('cheese', 1)">+</button>
                  </div>
                  <div class="addon-price">$18.00/brd</div>
                </div>

                <!-- Item 8: French Macarons Dessert -->
                <div class="addon-item" id="addon-macarons">
                  <div class="addon-img">🫐</div>
                  <div class="addon-info">
                    <div class="addon-name">French Macarons Box/6 (Dessert)</div>
                    <div class="addon-desc">Pistachio, raspberry, caramel, vanilla · GF &amp; Vegetarian</div>
                  </div>
                  <div class="addon-qty">
                    <button class="qty-btn" type="button" onclick="changeQty('macarons', -1)">−</button>
                    <input class="qty-input" type="number" id="qty-macarons" value="0" min="0" max="30" data-price="14.00" data-name="French Macarons (Box/6)" oninput="recalcTotal()">
                    <button class="qty-btn" type="button" onclick="changeQty('macarons', 1)">+</button>
                  </div>
                  <div class="addon-price">$14.00/box</div>
                </div>

                <!-- Add-On Subtotal Preview -->
                <div id="addon-subtotal-box" style="display:none; margin-top:16px; padding:14px 16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:var(--radius-sm);">
                  <div class="font-bold text-sm mb-8" style="color:#166534;">🛒 Your Selected Add-On Items:</div>
                  <div id="addon-lines" class="selected-addons-list"></div>
                  <div class="flex-between pt-8" style="border-top:1px dashed #86efac; margin-top:8px;">
                    <span class="font-bold text-sm" style="color:#166534;">Add-Ons Subtotal</span>
                    <span class="font-bold text-sm" style="color:#166534;" id="addon-subtotal-val">$0.00</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Payment Method Card -->
            <div class="card">
              <div class="flex-between mb-4">
                <h2>Payment Method</h2>
                <span class="stat-badge positive">🛡️ SECURE SSL</span>
              </div>
              <p class="mb-24">Finalize your booking and catering transaction securely.</p>

              <form action="booking-success.php" id="payment-form">
                <div class="form-group">
                  <label class="form-label">Cardholder Name</label>
                  <input type="text" class="form-control" value="Mahmud" placeholder="e.g. Mahmud" required>
                </div>

                <div class="form-group">
                  <label class="form-label">Card Number</label>
                  <div class="input-wrap">
                    <input type="text" class="form-control" value="•••• •••• •••• 4242" placeholder="0000 0000 0000 0000" required>
                    <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:20px;">💳</span>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Expiration Date</label>
                    <input type="text" class="form-control" value="12/28" placeholder="MM / YY" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">CVC</label>
                    <input type="password" class="form-control" value="•••" placeholder="•••" required>
                  </div>
                </div>

                <div class="form-group flex-center gap-8 mb-24">
                  <input type="checkbox" id="savecard" checked>
                  <label for="savecard" class="text-xs text-muted">Save card details for faster checkout. Encrypted according to PCI DSS standards.</label>
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg mb-16">Pay Now →</button>
                <div class="flex-center justify-center gap-8 text-xs text-muted">
                  <span>🔒 End-to-end encryption</span>
                  <span>•</span>
                  <span>Verified by Stripe</span>
                </div>
              </form>
            </div>

          </div>

          <!-- RIGHT COLUMN: Booking Summary (Updates dynamically) -->
          <div class="card" style="position:sticky; top:80px;">
            <h3 class="mb-8">Booking Summary</h3>
            <div class="text-xs text-muted mb-16">📅 December 14, 2026 • 18:00 - 22:00</div>

            <!-- Venue -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold">The Glass House – Grand Hall</div>
                  <div class="text-xs text-muted">Main Space + Terrace</div>
                </div>
                <div class="font-semibold">$2,400.00</div>
              </div>
            </div>

            <!-- Catering Package (Main Package) -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold" style="color:var(--primary);">Catering: Platinum Package</div>
                  <div class="text-xs text-muted">150 Guests • 3-Course Dinner • Open Bar (4h)</div>
                </div>
                <div class="font-semibold">$1,850.00</div>
              </div>
            </div>

            <!-- Professional Staffing -->
            <div style="border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="flex-between text-sm mb-4">
                <div>
                  <div class="font-bold">Professional Staffing</div>
                  <div class="text-xs text-muted">4 Waitstaff • 2 Bartenders • 1 Coordinator</div>
                </div>
                <div class="font-semibold">$640.00</div>
              </div>
            </div>

            <!-- Add-On Singular Items (Dynamically displayed) -->
            <div id="summary-addon-section" style="display:none; border-bottom:1px solid var(--gray-100); padding-bottom:14px; margin-bottom:14px;">
              <div class="font-bold text-sm mb-8" style="color:#059669;">Selected Singular Add-On Items</div>
              <div id="summary-addon-lines" style="display:flex; flex-direction:column; gap:4px;"></div>
              <div class="flex-between text-sm mt-8">
                <span class="text-muted font-semibold">Add-Ons Subtotal</span>
                <span class="font-bold" id="summary-addon-total" style="color:#059669;">$0.00</span>
              </div>
            </div>

            <!-- Subtotal & Calculations -->
            <div style="border-bottom:1px solid var(--gray-200); padding-bottom:14px; margin-bottom:16px;">
              <div class="flex-between text-sm mb-4">
                <span class="text-muted">Package &amp; Venue Base</span>
                <span>$4,890.00</span>
              </div>
              <div class="flex-between text-sm mb-4" id="summary-row-addons" style="display:none;">
                <span class="text-muted">Singular Items</span>
                <span id="summary-row-addons-val" style="color:#059669; font-weight:700;">$0.00</span>
              </div>
              <div class="flex-between text-sm mb-4">
                <span class="text-muted">Service Fee (10%)</span>
                <span id="fee-display">$489.00</span>
              </div>
              <div class="flex-between text-sm">
                <span class="text-muted">VAT (8%)</span>
                <span id="vat-display">$430.32</span>
              </div>
            </div>

            <!-- TOTAL AMOUNT -->
            <div class="total-box">
              <div class="flex-between">
                <div>
                  <div class="text-xs text-muted font-bold">TOTAL PAYMENT</div>
                  <div class="text-xs text-primary">USD Currency • dynamically summed</div>
                </div>
                <div style="font-size:1.85rem; font-weight:900; color:var(--primary);" id="grand-total">$5,809.32</div>
              </div>
            </div>

            <div class="card mt-16" style="background:#fefce8; border:1px solid #fef08a; padding:12px; margin-top:16px;">
              <div class="text-xs" style="color:#854d0e;">ℹ️ Free cancellation until 48 hours before the event. Refund processed in 5-10 business days.</div>
            </div>
          </div>

        </div><!-- /grid -->
      </main>

      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
        <div class="footer-links">
          <a href="../privacy-policy.php">Privacy Policy</a>
          <a href="../terms-of-service.php">Terms of Service</a>
          <a href="../contact-support.php">Contact Support</a>
        </div>
      </footer>
    </div>
  </div>

  <script>
    const BASE = 4890;      // Venue ($2,400) + Package ($1,850) + Staffing ($640)
    const SERVICE_RATE = 0.10;
    const VAT_RATE = 0.08;

    const ITEMS = [
      { id: 'shrimp',     price: 12.50 },
      { id: 'coke',       price: 3.50  },
      { id: 'yogurt',     price: 7.00  },
      { id: 'panna',      price: 9.50  },
      { id: 'water',      price: 4.50  },
      { id: 'bruschetta', price: 6.00  },
      { id: 'cheese',     price: 18.00 },
      { id: 'macarons',   price: 14.00 }
    ];

    function toggleAddonChoice(enable) {
      const yesBtn = document.getElementById('tab-addons-yes');
      const noBtn = document.getElementById('tab-addons-no');
      const body = document.getElementById('addon-selection-body');

      if (enable) {
        yesBtn.classList.add('active');
        noBtn.classList.remove('active');
        body.style.display = 'block';
      } else {
        noBtn.classList.add('active');
        yesBtn.classList.remove('active');
        body.style.display = 'none';
        // Zero all quantities
        ITEMS.forEach(item => {
          const el = document.getElementById('qty-' + item.id);
          if (el) el.value = 0;
        });
        recalcTotal();
      }
    }

    function changeQty(id, delta) {
      const el = document.getElementById('qty-' + id);
      const cur = parseInt(el.value) || 0;
      const max = parseInt(el.max) || 999;
      el.value = Math.max(0, Math.min(cur + delta, max));
      recalcTotal();
    }

    function recalcTotal() {
      let addonTotal = 0;
      let addonLines = [];
      let summaryLines = [];

      ITEMS.forEach(function(item) {
        const el = document.getElementById('qty-' + item.id);
        if (!el) return;
        const qty = parseInt(el.value) || 0;
        if (qty > 0) {
          const sub = qty * item.price;
          addonTotal += sub;
          const name = el.getAttribute('data-name');
          addonLines.push('<div class="selected-addon-row"><span>' + name + ' × ' + qty + '</span><span>$' + sub.toFixed(2) + '</span></div>');
          summaryLines.push('<div class="flex-between text-xs text-muted"><span>' + name + ' × ' + qty + '</span><span>$' + sub.toFixed(2) + '</span></div>');
          // Highlight selected item card
          const card = document.getElementById('addon-' + item.id);
          if (card) card.classList.add('selected');
        } else {
          const card = document.getElementById('addon-' + item.id);
          if (card) card.classList.remove('selected');
        }
      });

      // Update add-on subtotal preview box inside the card
      const addonBox = document.getElementById('addon-subtotal-box');
      const addonLinesEl = document.getElementById('addon-lines');
      const addonSubVal = document.getElementById('addon-subtotal-val');
      addonBox.style.display = addonTotal > 0 ? 'block' : 'none';
      addonLinesEl.innerHTML = addonLines.join('');
      addonSubVal.textContent = '$' + addonTotal.toFixed(2);

      // Update right-hand summary panel
      const summarySection = document.getElementById('summary-addon-section');
      const summaryAddonLines = document.getElementById('summary-addon-lines');
      const summaryAddonTotal = document.getElementById('summary-addon-total');
      const summaryRowAddons = document.getElementById('summary-row-addons');
      const summaryRowAddonsVal = document.getElementById('summary-row-addons-val');
      summarySection.style.display = addonTotal > 0 ? 'block' : 'none';
      summaryRowAddons.style.display = addonTotal > 0 ? 'flex' : 'none';
      summaryAddonLines.innerHTML = summaryLines.join('');
      summaryAddonTotal.textContent = '$' + addonTotal.toFixed(2);
      summaryRowAddonsVal.textContent = '$' + addonTotal.toFixed(2);

      // Recalculate dynamic grand total
      const subtotal = BASE + addonTotal;
      const fee = subtotal * SERVICE_RATE;
      const vat = subtotal * VAT_RATE;
      const grand = subtotal + fee + vat;

      document.getElementById('fee-display').textContent = '$' + fee.toFixed(2);
      document.getElementById('vat-display').textContent = '$' + vat.toFixed(2);
      document.getElementById('grand-total').textContent = '$' + grand.toFixed(2);
    }
  </script>
<script src="../js/app.js"></script>
<script>
// Read booking params from sessionStorage (set during venue/date selection flow)
var vpVenueId   = sessionStorage.getItem('vp_venue_id')   || '1';
var vpPackageId = sessionStorage.getItem('vp_package_id') || '';
var vpEventDate = sessionStorage.getItem('vp_event_date') || '';

document.addEventListener('DOMContentLoaded', function() {
  // Inject hidden fields into payment form
  var pf = document.getElementById('payment-form');
  if (pf) {
    function addHidden(name, val) {
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = name; h.value = val;
      pf.appendChild(h);
    }
    addHidden('venue_id',   vpVenueId);
    addHidden('package_id', vpPackageId);
    addHidden('event_date', vpEventDate);
    addHidden('start_time', '18:00:00');
    addHidden('end_time',   '22:00:00');
    addHidden('guest_count','100');
    addHidden('event_name', 'Event at Venue #' + vpVenueId);
    addHidden('duration_hours','4');
  }

  // Clear session on successful booking
  window.addEventListener('vpBookingComplete', function() {
    sessionStorage.removeItem('vp_venue_id');
    sessionStorage.removeItem('vp_package_id');
    sessionStorage.removeItem('vp_event_date');
  });
});
</script>
</body>
</html>
