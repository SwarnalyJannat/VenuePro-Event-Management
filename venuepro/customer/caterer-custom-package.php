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
  <title>VenuePro – Custom Catering Package Builder</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    /* Custom Package Builder Styles */
    .builder-section {
      background: #fff; border: 1px solid var(--gray-200);
      border-radius: var(--radius); padding: 24px 28px; margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
    }
    .builder-section-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1.5px solid var(--gray-100);
    }
    .builder-section-title {
      font-size: 1.15rem; font-weight: 700; color: var(--gray-900);
      display: flex; align-items: center; gap: 10px;
    }
    .builder-section-badge {
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.05em; padding: 3px 8px; border-radius: 20px;
    }

    /* Cuisine Cards Grid */
    .cuisine-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;
    }
    .cuisine-radio { display: none; }
    .cuisine-card {
      border: 2px solid var(--gray-200); border-radius: var(--radius-sm);
      padding: 16px; cursor: pointer; transition: all 0.2s ease;
      display: flex; flex-direction: column; justify-content: space-between;
      background: #fff;
    }
    .cuisine-card:hover {
      border-color: var(--primary); background: #f8fafc; transform: translateY(-2px);
    }
    .cuisine-radio:checked + .cuisine-card {
      border-color: var(--primary); background: #eff6ff; box-shadow: 0 0 0 1px var(--primary);
    }
    .cuisine-icon { font-size: 1.8rem; margin-bottom: 8px; }
    .cuisine-name { font-weight: 700; font-size: 0.95rem; color: var(--gray-900); margin-bottom: 4px; }
    .cuisine-desc { font-size: 0.75rem; color: var(--gray-500); line-height: 1.4; margin-bottom: 8px; }
    .cuisine-price { font-size: 0.8rem; font-weight: 700; color: var(--primary); }

    /* Course Items List with Checkboxes */
    .course-item {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding: 12px 14px; border: 1.5px solid var(--gray-200); border-radius: var(--radius-sm);
      margin-bottom: 8px; background: #fff; cursor: pointer; transition: all 0.15s ease;
    }
    .course-item:hover { border-color: var(--primary); background: #f8fafc; }
    .course-check { display: none; }
    .course-check:checked + .course-item {
      background: #f0fdf4; border-color: #86efac;
    }
    .cb-box {
      width: 20px; height: 20px; border-radius: 4px; flex-shrink: 0;
      border: 2px solid var(--gray-300); background: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem; font-weight: 700; transition: all 0.15s ease;
    }
    .course-check:checked + .course-item .cb-box {
      background: #16a34a; border-color: #16a34a; color: #fff;
    }
    .course-check:checked + .course-item .cb-box::after { content: '✓'; }
    .course-info { flex: 1; }
    .course-title { font-weight: 600; font-size: 0.88rem; color: var(--gray-800); }
    .course-desc { font-size: 0.72rem; color: var(--gray-500); }
    .course-price-tag {
      font-size: 0.8rem; font-weight: 700; color: #059669; flex-shrink: 0;
    }

    /* Stepper & Headcount Adjuster */
    .headcount-wrap {
      display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    }
    .headcount-stepper {
      display: flex; align-items: center; border: 2px solid var(--gray-300);
      border-radius: var(--radius-sm); overflow: hidden; background: #fff;
    }
    .stepper-btn {
      width: 44px; height: 44px; border: none; background: var(--gray-100);
      font-size: 1.2rem; font-weight: 700; color: var(--gray-700);
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.15s;
    }
    .stepper-btn:hover { background: var(--gray-200); }
    .stepper-val {
      width: 80px; height: 44px; border: none; text-align: center;
      font-size: 1.25rem; font-weight: 800; color: var(--primary);
    }
    .headcount-presets {
      display: flex; gap: 8px; flex-wrap: wrap;
    }
    .preset-chip {
      padding: 8px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
      border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-700);
      cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .preset-chip:hover { border-color: var(--primary); color: var(--primary); }
    .preset-chip.active { background: var(--primary); color: #fff; border-color: var(--primary); }

    /* Sticky Sidebar Summary */
    .sticky-summary {
      position: sticky; top: 24px; background: #fff;
      border: 2px solid var(--primary); border-radius: var(--radius);
      padding: 24px; box-shadow: var(--shadow-md);
    }
    .summary-metric {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.85rem; margin-bottom: 10px; color: var(--gray-700);
    }
    .summary-total {
      display: flex; justify-content: space-between; align-items: center;
      padding-top: 14px; margin-top: 14px; border-top: 2px solid var(--gray-200);
    }
    .service-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.75rem; font-weight: 600; padding: 4px 10px;
      border-radius: 12px; background: #eff6ff; color: var(--primary);
    }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <!-- Sidebar -->
    
    
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
          <div class="sidebar-logo-text">VenuePro</div>
        <div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Main Menu</div>
        <a href="customer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Dashboard
        </a>
        <a href="venue-listings.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> Bookings &amp; Venues
        </a>
        <a href="customer-live-progress.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Live Progress
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../login-role.php" class="nav-item" style="color:var(--gray-400);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Log out
        </a>
      </div>
    </aside>

    <!-- Main Content -->
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
        <!-- Breadcrumb -->
        <div class="breadcrumb">
          <a href="customer-dashboard.php">Dashboard</a>
          <span class="breadcrumb-sep">›</span>
          <a href="browse-caterers.php">Catering Partners</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Custom Catering Package Builder</span>
        </div>

        <!-- Page Header -->
        <div class="flex-between mb-24">
          <div>
            <h1 style="font-size:2rem; font-weight:800; margin-bottom:4px;">Custom Catering Package Builder</h1>
            <p>Customize every element of your dining experience: headcount, cuisine, multi-course dishes, service style &amp; dietary specs.</p>
          </div>
          <div class="flex gap-8">
            <a href="booking-catering-packages.php" class="btn btn-ghost btn-sm">← Fixed Tiers</a>
            
          </div>
        </div>

        <!-- Form & Sticky Layout -->
        <div class="grid-2" style="grid-template-columns: 2.3fr 1.1fr; gap: 28px; align-items: start;">

          <!-- Left Column: Builder Form -->
          <div>

            <!-- Section 2: Cuisine Specialization -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>🍳</span> 1. Food Cuisine &amp; Culinary Style
                </div>
                <span class="builder-section-badge" style="background:#fefce8; color:#a16207;">Choose Primary Cuisine</span>
              </div>

              <div class="cuisine-grid">
                <!-- Cuisine 1 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio" checked>
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🥩</div>
                    <div class="cuisine-name">Contemporary Fine Dining</div>
                    <div class="cuisine-desc">Seasonal farm-to-table proteins, reduction jus, and molecular garnish presentation.</div>
                    <div class="cuisine-price">$55.00 / head</div>
                  </div>
                </label>

                <!-- Cuisine 2 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio">
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🌿</div>
                    <div class="cuisine-name">Mediterranean &amp; Aegean</div>
                    <div class="cuisine-desc">Cold-pressed olive oil, chargrilled seafood, heirloom vegetables &amp; fresh herbs.</div>
                    <div class="cuisine-price">$48.00 / head</div>
                  </div>
                </label>

                <!-- Cuisine 3 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio">
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🍣</div>
                    <div class="cuisine-name">Modern Asian Fusion</div>
                    <div class="cuisine-desc">Sashimi-grade seafood, robata grill, dim sum artisan stations, and wagyu bites.</div>
                    <div class="cuisine-price">$62.00 / head</div>
                  </div>
                </label>

                <!-- Cuisine 4 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio">
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🍝</div>
                    <div class="cuisine-name">Artisanal Northern Italian</div>
                    <div class="cuisine-desc">Handcrafted agnolotti, truffled risotto wheels, osso buco, and burrata crudos.</div>
                    <div class="cuisine-price">$52.00 / head</div>
                  </div>
                </label>

                <!-- Cuisine 5 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio">
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🍖</div>
                    <div class="cuisine-name">Gourmet Smokehouse BBQ</div>
                    <div class="cuisine-desc">Oak-smoked black Angus brisket, bourbon glaze ribs, and artisanal savory sides.</div>
                    <div class="cuisine-price">$45.00 / head</div>
                  </div>
                </label>

                <!-- Cuisine 6 -->
                <label>
                  <input type="radio" name="cuisine" class="cuisine-radio">
                  <div class="cuisine-card">
                    <div class="cuisine-icon">🥗</div>
                    <div class="cuisine-name">100% Plant-Based Haute</div>
                    <div class="cuisine-desc">Innovative vegan gastronomy, king oyster steaks, cashew ricotta, and raw desserts.</div>
                    <div class="cuisine-price">$48.00 / head</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Section 3: Dining Format & Service Style -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>🍽️</span> 2. Dining Format &amp; Service Style
                </div>
                <span class="builder-section-badge" style="background:#f0fdf4; color:#15803d;">Floor Execution</span>
              </div>

              <div class="grid-3 gap-12 mb-12">
                <label class="card flex gap-10" style="padding:14px; cursor:pointer; border:1.5px solid var(--primary); background:#eff6ff;">
                  <input type="radio" name="service_style" checked style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Synchronized Plated</div>
                    <div class="text-xs text-muted">White-glove simultaneous table service per course.</div>
                  </div>
                </label>

                <label class="card flex gap-10" style="padding:14px; cursor:pointer; border:1.5px solid var(--gray-200);">
                  <input type="radio" name="service_style" style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Interactive Action Stations</div>
                    <div class="text-xs text-muted">Chefs prepare custom dishes live at culinary bars.</div>
                  </div>
                </label>

                <label class="card flex gap-10" style="padding:14px; cursor:pointer; border:1.5px solid var(--gray-200);">
                  <input type="radio" name="service_style" style="accent-color:var(--primary);">
                  <div>
                    <div class="font-bold text-sm">Family-Style Feast</div>
                    <div class="text-xs text-muted">Lavish shared elevated platters at each table.</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Section 4: Interactive Course Builder -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>📜</span> 3. Custom Menu Course Builder
                </div>
                <span class="builder-section-badge" style="background:#faf5ff; color:#7e22ce;">Select Desired Dishes</span>
              </div>

              <!-- Course A: Canapés -->
              <div class="mb-20">
                <div class="flex-between mb-8">
                  <span class="text-xs font-bold text-primary" style="text-transform:uppercase; letter-spacing:0.06em;">Course 1: Canapés &amp; Passed Hors d'oeuvres (Cocktail Hour)</span>
                  <span class="text-xs text-muted">Select 2 to 4 items</span>
                </div>
                <input type="checkbox" id="c1" class="course-check" checked>
                <label for="c1" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Maine Lobster Roll Bites</div>
                    <div class="course-desc">Butter-poached lobster, tarragon aioli on toasted brioche rounds</div>
                  </div>
                  <span class="course-price-tag">+$6.00 / head</span>
                </label>

                <input type="checkbox" id="c2" class="course-check" checked>
                <label for="c2" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Truffled Wild Forest Mushroom Tartlets</div>
                    <div class="course-desc">Roasted chanterelles, black truffle cream, aged Gruyère crumble (V)</div>
                  </div>
                  <span class="course-price-tag">+$4.00 / head</span>
                </label>

                <input type="checkbox" id="c3" class="course-check" checked>
                <label for="c3" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Wagyu Beef Carpaccio Crostini</div>
                    <div class="course-desc">Thin-sliced A5 Wagyu, caper emulsion, shaved black garlic &amp; parmesan</div>
                  </div>
                  <span class="course-price-tag">+$7.00 / head</span>
                </label>

                <input type="checkbox" id="c4" class="course-check">
                <label for="c4" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Yellowfin Tuna Tartare in Sesame Cones</div>
                    <div class="course-desc">Yuzu ponzu marinade, avocado mousse, pickled ginger pearls</div>
                  </div>
                  <span class="course-price-tag">+$6.00 / head</span>
                </label>
              </div>

              <!-- Course B: Starters -->
              <div class="mb-20">
                <div class="flex-between mb-8">
                  <span class="text-xs font-bold text-primary" style="text-transform:uppercase; letter-spacing:0.06em;">Course 2: First Course &amp; Starters</span>
                  <span class="text-xs text-muted">Select 1 option</span>
                </div>
                <input type="checkbox" id="s1" class="course-check" checked>
                <label for="s1" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Burrata di Puglia with Roasted Heirloom Figs</div>
                    <div class="course-desc">25-year Modena balsamic glaze, baby arugula, smoked sea salt (V, GF)</div>
                  </div>
                  <span class="course-price-tag">+$7.00 / head</span>
                </label>

                <input type="checkbox" id="s2" class="course-check">
                <label for="s2" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Velvety Maine Lobster Bisque</div>
                    <div class="course-desc">Hennessy cognac splash, crème fraîche swirl, chive oil pipette</div>
                  </div>
                  <span class="course-price-tag">+$8.00 / head</span>
                </label>
              </div>

              <!-- Course C: Mains -->
              <div class="mb-20">
                <div class="flex-between mb-8">
                  <span class="text-xs font-bold text-primary" style="text-transform:uppercase; letter-spacing:0.06em;">Course 3: Main Entrée (Choice of Two for Guests)</span>
                  <span class="text-xs text-muted">Select 2 options</span>
                </div>
                <input type="checkbox" id="m1" class="course-check" checked>
                <label for="m1" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Prime Dry-Aged Angus Filet Mignon</div>
                    <div class="course-desc">Cabernet reduction demi-glace, bone marrow butter, roasted baby shallots</div>
                  </div>
                  <span class="course-price-tag">+$18.00 / head</span>
                </label>

                <input type="checkbox" id="m2" class="course-check" checked>
                <label for="m2" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Pan-Seared Chilean Sea Bass</div>
                    <div class="course-desc">Saffron champagne emulsion, braised fennel bulb, micro celery (GF)</div>
                  </div>
                  <span class="course-price-tag">+$16.00 / head</span>
                </label>

                <input type="checkbox" id="m3" class="course-check">
                <label for="m3" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Herb-Crusted Colorado Rack of Lamb</div>
                    <div class="course-desc">Rosemary mint jus, roasted garlic purée, confit cherry tomatoes</div>
                  </div>
                  <span class="course-price-tag">+$19.00 / head</span>
                </label>

                <input type="checkbox" id="m4" class="course-check" checked>
                <label for="m4" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Roasted King Oyster Mushroom Steaks (Vegetarian / Vegan Choice)</div>
                    <div class="course-desc">Chimichurri sauce, sweet corn purée, crispy shallot rings (Vegan, GF)</div>
                  </div>
                  <span class="course-price-tag">+$10.00 / head</span>
                </label>
              </div>

              <!-- Course D: Desserts -->
              <div class="mb-20">
                <div class="flex-between mb-8">
                  <span class="text-xs font-bold text-primary" style="text-transform:uppercase; letter-spacing:0.06em;">Course 4: Plated Dessert &amp; Confections</span>
                  <span class="text-xs text-muted">Select 1 option</span>
                </div>
                <input type="checkbox" id="d1" class="course-check" checked>
                <label for="d1" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Warm Valrhona Dark Chocolate Molten Lava Cake</div>
                    <div class="course-desc">Madagascar vanilla bean bean gelato, raspberry coulis pipette</div>
                  </div>
                  <span class="course-price-tag">+$7.00 / head</span>
                </label>

                <input type="checkbox" id="d2" class="course-check">
                <label for="d2" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Deconstructed Passionfruit &amp; Mango Tart</div>
                    <div class="course-desc">Torched Italian meringue, coconut crumble, edible gold leaf</div>
                  </div>
                  <span class="course-price-tag">+$6.00 / head</span>
                </label>
              </div>

              <!-- Course E: Bar & Beverage Service -->
              <div>
                <div class="flex-between mb-8">
                  <span class="text-xs font-bold text-primary" style="text-transform:uppercase; letter-spacing:0.06em;">Course 5: Bar &amp; Beverage Package</span>
                  <span class="text-xs text-muted">Optional addition</span>
                </div>
                <input type="checkbox" id="b1" class="course-check" checked>
                <label for="b1" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Premium 4-Hour Open Bar Service</div>
                    <div class="course-desc">Top-shelf spirits, sommelier-selected reds &amp; whites, craft drafts, sparkling wine</div>
                  </div>
                  <span class="course-price-tag">+$32.00 / head</span>
                </label>

                <input type="checkbox" id="b2" class="course-check">
                <label for="b2" class="course-item">
                  <span class="cb-box"></span>
                  <div class="course-info">
                    <div class="course-title">Artisanal Mocktail &amp; Cold-Pressed Juice Bar</div>
                    <div class="course-desc">Handcrafted alcohol-free botanicals, sparkling sodas, and organic infusions</div>
                  </div>
                  <span class="course-price-tag">+$12.00 / head</span>
                </label>
              </div>
            </div>

            <!-- Section 5: Dietary Preferences & Certified Prep -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>🥗</span> 4. Dietary Requirements &amp; Certifications
                </div>
                <span class="builder-section-badge" style="background:#fee2e2; color:#b91c1c;">Special Precautions</span>
              </div>

              <div class="grid-2 gap-12 mb-16">
                <label class="card flex-center gap-8 text-sm" style="padding:10px; cursor:pointer;">
                  <input type="checkbox" checked accent-color="#2563eb">
                  <span>Halal-Certified Protein Prep (12 guests)</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px; cursor:pointer;">
                  <input type="checkbox" checked accent-color="#2563eb">
                  <span>Vegan / Plant-Based Course (8 guests)</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px; cursor:pointer;">
                  <input type="checkbox" checked accent-color="#2563eb">
                  <span>Strict Gluten-Free / Celiac Safe (4 guests)</span>
                </label>
                <label class="card flex-center gap-8 text-sm" style="padding:10px; cursor:pointer;">
                  <input type="checkbox" checked accent-color="#2563eb">
                  <span>Nut-Free Kitchen Surface Sanitization</span>
                </label>
              </div>

              <div class="form-group">
                <label class="form-label">Special Kitchen Notes / Chef Instructions</label>
                <textarea class="form-control" rows="2" placeholder="e.g. 2 guests have severe shellfish allergies. Please serve bridal party canapés in VIP green room prior to ballroom doors opening."></textarea>
              </div>
            </div>

            <!-- Section 6: Staffing & Equipment Upgrades -->
            <div class="builder-section">
              <div class="builder-section-header">
                <div class="builder-section-title">
                  <span>👔</span> 5. On-Site Staffing &amp; Equipment Add-ons
                </div>
                <span class="builder-section-badge" style="background:var(--gray-100); color:var(--gray-700);">Optional Upgrades</span>
              </div>

              <div class="grid-2 gap-12">
                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" checked accent-color="#2563eb">
                    <span>Executive Chef on-site live plating</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$350</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" checked accent-color="#2563eb">
                    <span>Dedicated Master Sommelier</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$250</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" checked accent-color="#2563eb">
                    <span>Uniformed Banquet Servers (4 staff, 4h)</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$540</span>
                </label>

                <label class="card flex-between" style="padding:12px 14px; cursor:pointer;">
                  <div class="flex-center gap-8 text-sm">
                    <input type="checkbox" checked accent-color="#2563eb">
                    <span>Gold Cutlery &amp; Luxury Linen Rental</span>
                  </div>
                  <span class="font-bold text-sm text-primary">+$640</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Right Column: Sticky Estimate & Submit -->
          <div>
            <div class="sticky-summary">
              <div class="flex-between mb-16">
                <div>
                  <h3 style="font-size:1.25rem; font-weight:800; margin:4px 0 0;">Live Package Estimate</h3>
                </div>
                <span class="pill pill-in-progress" style="font-size:0.65rem;">DRAFT</span>
              </div>

              <div class="card mb-16" style="background:var(--gray-50); border:1px solid var(--gray-200); padding:12px;">
                <div class="text-xs text-muted">Culinary Partner</div>
                <div class="font-bold text-sm">Artisan Catering Co.</div>
                <div class="text-xs text-primary">Chef Alex Rivera</div>
                <div class="text-xs text-muted mt-4">Venue: Grand Emerald Ballroom · Nov 20, 2026</div>
              </div>

              <!-- Cost breakdown -->
              <div class="summary-metric">
                <span>Headcount (Attendees)</span>
                <strong>150 Guests</strong>
              </div>
              <div class="summary-metric">
                <span>Base Contemporary Cuisine</span>
                <span>$55.00 × 150 = $8,250.00</span>
              </div>
              <div class="summary-metric">
                <span>Selected Canapés (3 items)</span>
                <span>$17.00 × 150 = $2,550.00</span>
              </div>
              <div class="summary-metric">
                <span>Starter: Burrata di Puglia</span>
                <span>$7.00 × 150 = $1,050.00</span>
              </div>
              <div class="summary-metric">
                <span>Main: Filet Mignon &amp; Sea Bass</span>
                <span>$18.00 × 150 = $2,700.00</span>
              </div>
              <div class="summary-metric">
                <span>Dessert: Valrhona Fondant</span>
                <span>$7.00 × 150 = $1,050.00</span>
              </div>
              <div class="summary-metric">
                <span>Bar: 4-Hour Open Bar</span>
                <span>$32.00 × 150 = $4,800.00</span>
              </div>
              <div class="summary-metric">
                <span>Dietary Prep Accommodations</span>
                <span class="text-success font-semibold">Included ($0.00)</span>
              </div>
              <div class="summary-metric">
                <span>Chef &amp; Sommelier Staffing</span>
                <span>$1,780.00</span>
              </div>

              <div class="summary-metric" style="border-top:1px dashed var(--gray-300); padding-top:10px; margin-top:10px;">
                <span>Subtotal</span>
                <strong>$22,180.00</strong>
              </div>

              <div class="summary-total">
                <div>
                  <div style="font-size:0.75rem; color:var(--gray-500); font-weight:700; text-transform:uppercase;">TOTAL ESTIMATE</div>
                  <div style="font-size:1.6rem; font-weight:800; color:var(--primary);">$22,180.00</div>
                </div>
                <div style="text-align:right;">
                  <div class="text-xs text-muted">Per Head Rate</div>
                  <div class="font-bold text-success" style="font-size:1.05rem;">$147.87 / guest</div>
                </div>
              </div>

              <!-- Action Button -->
              <div style="margin-top:20px;">
                <a href="booking-summary-payment.php" class="btn btn-primary btn-full" style="font-weight:700; padding:12px;">
                  Proceed to Pay
                </a>
              </div>
            </div>
          </div>

        </div>

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
<script src="../js/app.js"></script>
</body>
</html>