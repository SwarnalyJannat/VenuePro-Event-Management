<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';
$currentUser = requireRole('caterer', 'caterer-login.php');
$db = getDBConnection();

// Load items from DB for this caterer
$stmt = $db->prepare("SELECT * FROM singular_menu_items WHERE caterer_id = ? AND status = 'active' ORDER BY category ASC, name ASC");
$stmt->execute([$currentUser['id']]);
$menuItems = $stmt->fetchAll();

// Counts
$totalItems = count($menuItems);
$lowStock = 0;
foreach ($menuItems as $it) { if ($it['quantity_available'] <= 10) $lowStock++; }

// Category emoji map
function catEmoji($cat) {
    $map = ['Food'=>'🍽️','Drinks'=>'🥤','Dessert'=>'🍮','Appetizer'=>'🥗','Seafood'=>'🦐','Dairy'=>'🧀','Beverage'=>'🍹'];
    return $map[$cat] ?? '🍴';
}
function dietList($tags) {
    if (empty($tags)) return [];
    $d = json_decode($tags, true);
    return is_array($d) ? $d : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VenuePro Caterer – Singular Item Menu</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
    .item-card { border: 1.5px solid var(--gray-200); border-radius: var(--radius); background: #fff; overflow: hidden; transition: all 0.2s; display: flex; flex-direction: column; }
    .item-card:hover { border-color: #059669; box-shadow: 0 4px 16px rgba(5,150,105,0.12); transform: translateY(-2px); }
    .item-card-img { width: 100%; height: 140px; object-fit: cover; background: var(--gray-100); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
    .item-card-body { padding: 14px 16px; flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .item-card-actions { display: flex; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--gray-100); }
    .qty-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 12px; background: #dcfce7; color: #166534; }
    .qty-badge.low { background: #fef3c7; color: #92400e; }
    .qty-badge.out { background: #fee2e2; color: #991b1b; }
    .diet-tag { font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
    .diet-veg { background:#d1fae5; color:#065f46; }
    .diet-gf  { background:#ede9fe; color:#4c1d95; }
    .diet-vegan { background:#fef9c3; color:#92400e; }
    .diet-halal { background:#dbeafe; color:#1e40af; }
    /* Modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; }
    .modal-overlay.open { display:flex; }
    .modal-box { background:#fff; border-radius:var(--radius); padding:28px; width:100%; max-width:520px; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.25); animation:fadeIn .2s ease; max-height:90vh; overflow-y:auto; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(-12px)} to{opacity:1;transform:translateY(0)} }
    .modal-close { position:absolute; top:16px; right:16px; font-size:20px; font-weight:700; color:var(--gray-500); cursor:pointer; background:none; border:none; line-height:1; }
    .modal-close:hover { color:var(--gray-900); }
  </style>
</head>
<body>
  <input type="checkbox" id="sidebar-toggle">
  <div class="app-shell">
    <aside class="sidebar" id="main-sidebar">
      <div class="sidebar-logo">
        <img src="../assets/logo.png" alt="VenuePro" class="sidebar-logo-img">
        <div>
          <div class="sidebar-logo-text">VenuePro</div>
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-label">Culinary Dashboard</div>
        <a href="caterer-dashboard.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg> Kitchen Overview
        </a>
        <a href="caterer-order-details.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select an Order
        </a>
        <a href="caterer-food-packages.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Package Library
        </a>
        <a href="caterer-menu-items.php" class="nav-item active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/><path d="M9 12h6"/><path d="M9 16h4"/></svg>
          Singular Menu Items
        </a>
        <a href="caterer-create-package-1.php" class="nav-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Create Package
        </a>
      </nav>
      <div class="sidebar-footer">
        <a href="../venues.php" class="nav-item logout-link" style="color:var(--gray-400);">
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
          <input type="text" placeholder="Search menu items, prices, categories...">
        </div>
        <div class="topbar-actions">
          <a href="caterer-notifications.php" class="topbar-icon-btn" title="Notifications">
            <span class="badge">5</span>🔔
          </a>
          <div class="topbar-user">
            <div class="user-avatar" style="background:#059669;"><?= e($currentUser['avatar_text'] ?? 'U') ?></div>
            <div class="user-info">
              <div class="user-name"><?= e($currentUser['name'] ?? 'User') ?></div>
              <div class="user-role"><?= ucfirst(e($currentUser['role'] ?? 'caterer')) ?></div>
            </div>
          </div>
        </div>
      </header>

      <main class="page-body">
        <div class="breadcrumb mb-16">
          <a href="caterer-dashboard.php">Kitchen Overview</a>
          <span class="breadcrumb-sep">›</span>
          <span class="breadcrumb-current">Singular Menu Items</span>
        </div>

        <div class="flex-between mb-24">
          <div>
            <h1 style="font-size:1.85rem; font-weight:800; margin-bottom:4px;">Singular Menu Items</h1>
            <p>Manage add-on items available for customers to order alongside their catering packages.</p>
          </div>
          <button onclick="openAddModal()" class="btn btn-primary font-bold flex-center gap-8">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Item
          </button>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-24">
          <div class="stat-card"><div class="stat-label">Total Items</div><div class="stat-value"><?= $totalItems ?></div></div>
          <div class="stat-card orange"><div class="stat-label">Low Stock (&le;10)</div><div class="stat-value"><?= $lowStock ?></div></div>
          <div class="stat-card green"><div class="stat-label">Categories</div><div class="stat-value"><?= count(array_unique(array_column($menuItems, 'category'))) ?></div></div>
          <div class="stat-card"><div class="stat-label">My Caterer ID</div><div class="stat-value" style="font-size:1rem;">#<?= (int)$currentUser['id'] ?></div></div>
        </div>

        <!-- Global alert -->
        <div id="globalAlert" style="display:none; padding:12px; border-radius:6px; margin-bottom:16px; font-size:0.9rem;"></div>

        <!-- Item Grid -->
        <div class="item-grid" id="itemGrid">
          <?php if (empty($menuItems)): ?>
          <div style="grid-column:1/-1; text-align:center; padding:48px; color:var(--gray-400);">
            <div style="font-size:3rem; margin-bottom:12px;">🍽️</div>
            <div class="font-semibold" style="font-size:1.1rem;">No menu items yet</div>
            <div class="text-sm mt-4">Click "Add New Item" to create your first singular item.</div>
          </div>
          <?php else: ?>
          <?php foreach ($menuItems as $item):
            $diets = dietList($item['dietary_tags'] ?? '');
            $qty = (int)$item['quantity_available'];
            $qtyClass = $qty <= 0 ? 'out' : ($qty <= 10 ? 'low' : '');
            $qtyLabel = $qty <= 0 ? 'Out of stock' : $qty . ' left';
          ?>
          <div class="item-card" id="item-card-<?= (int)$item['id'] ?>">
            <div class="item-card-img" style="background:var(--gray-50);">
              <?php if (!empty($item['image_url'])): ?>
              <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
              <?= e($item['emoji'] ?? '🍴') ?>
              <?php endif; ?>
            </div>
            <div class="item-card-body">
              <div class="flex-between">
                <div class="font-bold" style="font-size:1.05rem;"><?= e($item['name']) ?></div>
                <span class="qty-badge <?= $qtyClass ?>"><?= $qtyLabel ?></span>
              </div>
              <div class="text-primary font-bold">$<?= number_format((float)$item['price'], 2) ?> / <?= e($item['unit_label'] ?? 'piece') ?></div>
              <div class="text-xs text-muted">Category: <?= e($item['category']) ?> • Min order: <?= (int)($item['min_order_qty'] ?? 1) ?></div>
              <?php if (!empty($item['description'])): ?>
              <div class="text-xs text-muted"><?= e($item['description']) ?></div>
              <?php endif; ?>
              <?php if (!empty($diets)): ?>
              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:4px;">
                <?php foreach ($diets as $d): ?>
                <span class="diet-tag diet-<?= strtolower(str_replace([' ','-'],['',''],$d)) ?>"><?= e($d) ?></span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <div class="item-card-actions">
              <button onclick='openEditModal(<?= json_encode($item) ?>)' class="btn btn-outline btn-sm" style="flex:1;">✎ Edit</button>
              <button onclick='confirmRemove(<?= (int)$item["id"] ?>, this)' class="btn btn-ghost btn-sm" style="color:#dc2626; flex:1;">✕ Remove</button>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </main>
      <footer class="page-footer">
        <div>© 2026 VenuePro Enterprise Event Management. All rights reserved.</div>
      </footer>
    </div>
  </div>

  <!-- ===== ADD ITEM MODAL ===== -->
  <div id="modal-add" class="modal-overlay">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
      <h2 style="font-size:1.25rem; font-weight:800; margin-bottom:4px;">Add Singular Menu Item</h2>
      <p class="text-xs text-muted mb-20">Item will appear to customers during checkout as an optional add-on.</p>
      <div id="addAlert" style="display:none; padding:10px; border-radius:6px; margin-bottom:12px; font-size:.85rem;"></div>
      <form id="addItemForm">
        <div class="form-group mb-14">
          <label class="form-label">Item Name *</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Grilled Tiger Shrimp">
        </div>
        <div class="form-row mb-14">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Price per Unit (USD) *</label>
            <input type="number" name="price" class="form-control" placeholder="e.g. 12.50" min="0" step="0.01">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Quantity Available</label>
            <input type="number" name="quantity_available" class="form-control" placeholder="e.g. 50" min="0">
          </div>
        </div>
        <div class="form-row mb-14">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Category</label>
            <select name="category" class="form-control">
              <option value="Food">Food</option>
              <option value="Drinks">Drinks</option>
              <option value="Dessert">Dessert</option>
              <option value="Appetizer">Appetizer</option>
              <option value="Seafood">Seafood</option>
              <option value="Dairy">Dairy</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Unit Label</label>
            <input type="text" name="unit_label" class="form-control" placeholder="e.g. piece, bottle, serving">
          </div>
        </div>
        <div class="form-group mb-14">
          <label class="form-label">Short Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="e.g. Freshly grilled with garlic butter and lemon zest..."></textarea>
        </div>
        <div class="form-group mb-14">
          <label class="form-label">Emoji Icon</label>
          <input type="text" name="emoji" class="form-control" placeholder="e.g. 🍤" maxlength="4">
        </div>
        <div class="form-group mb-14">
          <label class="form-label">Dietary Tags</label>
          <div class="flex gap-16 flex-wrap" style="margin-top:6px;">
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Vegetarian"> Vegetarian</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Gluten-Free"> Gluten-Free</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Vegan"> Vegan</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Halal"> Halal</label>
          </div>
        </div>
        <div class="flex gap-12 mt-16">
          <button type="button" onclick="closeModal('modal-add')" class="btn btn-outline" style="flex:1;">Cancel</button>
          <button type="submit" class="btn btn-primary font-bold" style="flex:1; background:#059669; border-color:#059669;">✓ Save Item</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ===== EDIT ITEM MODAL ===== -->
  <div id="modal-edit" class="modal-overlay">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('modal-edit')">✕</button>
      <h2 style="font-size:1.25rem; font-weight:800; margin-bottom:4px;">Edit Menu Item</h2>
      <p class="text-xs text-muted mb-20">Changes apply immediately to customer checkout after saving.</p>
      <div id="editAlert" style="display:none; padding:10px; border-radius:6px; margin-bottom:12px; font-size:.85rem;"></div>
      <form id="editItemForm">
        <input type="hidden" name="id" id="edit-id">
        <div class="form-group mb-14">
          <label class="form-label">Item Name *</label>
          <input type="text" name="name" id="edit-name" class="form-control" placeholder="e.g. Grilled Tiger Shrimp">
        </div>
        <div class="form-row mb-14">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Price per Unit (USD) *</label>
            <input type="number" name="price" id="edit-price" class="form-control" min="0" step="0.01">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Quantity Available</label>
            <input type="number" name="quantity_available" id="edit-qty" class="form-control" min="0">
          </div>
        </div>
        <div class="form-row mb-14">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Category</label>
            <select name="category" id="edit-category" class="form-control">
              <option value="Food">Food</option>
              <option value="Drinks">Drinks</option>
              <option value="Dessert">Dessert</option>
              <option value="Appetizer">Appetizer</option>
              <option value="Seafood">Seafood</option>
              <option value="Dairy">Dairy</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Unit Label</label>
            <input type="text" name="unit_label" id="edit-unit" class="form-control" placeholder="e.g. piece">
          </div>
        </div>
        <div class="form-group mb-14">
          <label class="form-label">Short Description</label>
          <textarea name="description" id="edit-description" class="form-control" rows="2" placeholder="e.g. Freshly prepared..."></textarea>
        </div>
        <div class="form-group mb-14">
          <label class="form-label">Dietary Tags</label>
          <div class="flex gap-16 flex-wrap" style="margin-top:6px;" id="edit-diet-tags">
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Vegetarian"> Vegetarian</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Gluten-Free"> Gluten-Free</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Vegan"> Vegan</label>
            <label class="flex-center gap-6 text-sm"><input type="checkbox" name="dietary_tags[]" value="Halal"> Halal</label>
          </div>
        </div>
        <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:var(--radius-sm); padding:10px 14px; margin-bottom:16px;">
          <div class="font-semibold text-xs" style="color:#dc2626; margin-bottom:6px;">⚠️ Danger Zone</div>
          <div class="flex-between">
            <div class="text-xs text-muted">Permanently remove this item from the menu.</div>
            <button type="button" id="editDeleteBtn" class="btn btn-sm" style="background:#dc2626; color:#fff; font-size:0.75rem; padding:4px 10px;">✕ Delete Item</button>
          </div>
        </div>
        <div class="flex gap-12">
          <button type="button" onclick="closeModal('modal-edit')" class="btn btn-outline" style="flex:1;">Cancel</button>
          <button type="submit" class="btn btn-primary font-bold" style="flex:1;">✓ Save Changes</button>
        </div>
      </form>
    </div>
  </div>

<script src="../js/app.js"></script>
<script>
function showGlobalAlert(msg, ok) {
  var a = document.getElementById('globalAlert');
  a.style.display = 'block';
  a.style.background = ok ? '#dcfce7' : '#fee2e2';
  a.style.color = ok ? '#166534' : '#991b1b';
  a.textContent = (ok ? '✓ ' : '⚠️ ') + msg;
  setTimeout(function() { a.style.display = 'none'; }, 4000);
}

function openAddModal() {
  document.getElementById('addItemForm').reset();
  document.getElementById('addAlert').style.display = 'none';
  document.getElementById('modal-add').classList.add('open');
}

function openEditModal(item) {
  document.getElementById('editAlert').style.display = 'none';
  document.getElementById('edit-id').value = item.id;
  document.getElementById('edit-name').value = item.name || '';
  document.getElementById('edit-price').value = item.price || '';
  document.getElementById('edit-qty').value = item.quantity_available || '';
  document.getElementById('edit-category').value = item.category || 'Food';
  document.getElementById('edit-unit').value = item.unit_label || '';
  document.getElementById('edit-description').value = item.description || '';

  // Dietary tags
  var dietaryList = [];
  try { dietaryList = JSON.parse(item.dietary_tags || '[]'); } catch(e) {}
  document.querySelectorAll('#edit-diet-tags input[type=checkbox]').forEach(function(cb) {
    cb.checked = dietaryList.indexOf(cb.value) !== -1;
  });

  // Delete button in edit modal
  document.getElementById('editDeleteBtn').onclick = function() {
    closeModal('modal-edit');
    deleteItem(item.id);
  };

  document.getElementById('modal-edit').classList.add('open');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

// Close modals when clicking backdrop
document.querySelectorAll('.modal-overlay').forEach(function(m) {
  m.addEventListener('click', function(e) {
    if (e.target === m) closeModal(m.id);
  });
});

// ADD item form submit
document.getElementById('addItemForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var form = this;
  var alertBox = document.getElementById('addAlert');
  alertBox.style.display = 'none';
  var btn = form.querySelector('[type="submit"]');
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Saving...';

  var diets = [];
  form.querySelectorAll('[name="dietary_tags[]"]:checked').forEach(function(cb) { diets.push(cb.value); });

  var payload = {
    name:               (form.querySelector('[name="name"]') || {}).value || '',
    price:              parseFloat((form.querySelector('[name="price"]') || {}).value) || 0,
    quantity_available: parseInt((form.querySelector('[name="quantity_available"]') || {}).value) || 50,
    category:           (form.querySelector('[name="category"]') || {}).value || 'Food',
    unit_label:         (form.querySelector('[name="unit_label"]') || {}).value || 'piece',
    description:        (form.querySelector('[name="description"]') || {}).value || '',
    emoji:              (form.querySelector('[name="emoji"]') || {}).value || '🍴',
    dietary_tags:       diets
  };

  if (!payload.name || payload.price <= 0) {
    alertBox.style.display = 'block'; alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
    alertBox.textContent = '⚠️ Name and price are required.';
    btn.disabled = false; btn.textContent = origText; return;
  }

  try {
    var res = await fetch('../api/singular-items.php?action=create', {
      method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
    });
    var d = await res.json();
    if (d.success) {
      closeModal('modal-add');
      showGlobalAlert('Item added successfully!', true);
      setTimeout(function() { location.reload(); }, 800);
    } else {
      alertBox.style.display = 'block'; alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ ' + (d.message || 'Failed to add item');
      btn.disabled = false; btn.textContent = origText;
    }
  } catch(err) {
    alertBox.style.display = 'block'; alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
    alertBox.textContent = '⚠️ Connection error.';
    btn.disabled = false; btn.textContent = origText;
  }
});

// EDIT form submit
document.getElementById('editItemForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var form = this;
  var alertBox = document.getElementById('editAlert');
  alertBox.style.display = 'none';
  var btn = form.querySelector('[type="submit"]');
  var origText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Saving...';

  var diets = [];
  form.querySelectorAll('[name="dietary_tags[]"]:checked').forEach(function(cb) { diets.push(cb.value); });

  var payload = {
    id:                 parseInt(document.getElementById('edit-id').value),
    name:               document.getElementById('edit-name').value || '',
    price:              parseFloat(document.getElementById('edit-price').value) || 0,
    quantity_available: parseInt(document.getElementById('edit-qty').value) || 0,
    category:           document.getElementById('edit-category').value || 'Food',
    unit_label:         document.getElementById('edit-unit').value || 'piece',
    description:        document.getElementById('edit-description').value || '',
    dietary_tags:       diets
  };

  try {
    var res = await fetch('../api/singular-items.php?action=update', {
      method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
    });
    var d = await res.json();
    if (d.success) {
      closeModal('modal-edit');
      showGlobalAlert('Item updated successfully!', true);
      setTimeout(function() { location.reload(); }, 800);
    } else {
      alertBox.style.display = 'block'; alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
      alertBox.textContent = '⚠️ ' + (d.message || 'Update failed');
      btn.disabled = false; btn.textContent = origText;
    }
  } catch(err) {
    alertBox.style.display = 'block'; alertBox.style.background = '#fee2e2'; alertBox.style.color = '#991b1b';
    alertBox.textContent = '⚠️ Connection error.';
    btn.disabled = false; btn.textContent = origText;
  }
});

// REMOVE / DELETE item
function confirmRemove(id, btn) {
  if (!confirm('Remove this item from your menu? This cannot be undone.')) return;
  deleteItem(id, btn);
}

async function deleteItem(id, btn) {
  try {
    var res = await fetch('../api/singular-items.php?action=delete', {
      method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id: id })
    });
    var d = await res.json();
    if (d.success) {
      var card = document.getElementById('item-card-' + id);
      if (card) { card.style.opacity = '0'; card.style.transform = 'scale(0.95)'; card.style.transition = 'all .3s'; setTimeout(function() { card.remove(); }, 300); }
      showGlobalAlert('Item removed successfully.', true);
    } else {
      showGlobalAlert(d.message || 'Failed to remove item.', false);
    }
  } catch(err) {
    showGlobalAlert('Connection error.', false);
  }
}
</script>
</body>
</html>
