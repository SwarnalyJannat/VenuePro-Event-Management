<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $category = sanitize($_GET['category'] ?? '');
        $catererId = (int)($_GET['caterer_id'] ?? 0);
        $packageId = (int)($_GET['package_id'] ?? 0);

        if ($packageId > 0 && $catererId <= 0) {
            $stmtCat = $db->prepare("SELECT caterer_id FROM catering_packages WHERE id = ? LIMIT 1");
            $stmtCat->execute([$packageId]);
            $catererId = (int)$stmtCat->fetchColumn();
        }

        $sql = "SELECT s.*, cp.business_name, u.name as caterer_name 
                FROM singular_menu_items s 
                LEFT JOIN users u ON s.caterer_id = u.id 
                LEFT JOIN caterer_profiles cp ON u.id = cp.user_id 
                WHERE s.status = 'active'";
        $params = [];

        if ($catererId > 0) {
            $sql .= " AND s.caterer_id = ?";
            $params[] = $catererId;
        }

        if (!empty($category) && $category !== 'All') {
            $sql .= " AND s.category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY s.category ASC, s.name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        foreach ($items as &$it) {
            $it['dietary_list'] = !empty($it['dietary_tags']) ? json_decode($it['dietary_tags'], true) : [];
        }

        jsonResponse(true, 'Singular menu items retrieved', ['items' => $items]);
        break;

    case 'create':
        requireRole(['caterer', 'admin']);
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();

        $name = sanitize($input['name'] ?? '');
        $category = sanitize($input['category'] ?? 'Food');
        $price = (float)($input['price'] ?? 0);
        $qty = (int)($input['quantity_available'] ?? 50);
        $minQty = (int)($input['min_order_qty'] ?? 1);
        $description = sanitize($input['description'] ?? '');
        $emoji = sanitize($input['emoji'] ?? '🍽️');
        $imageUrl = sanitize($input['image_url'] ?? '');
        $unitLabel = sanitize($input['unit_label'] ?? 'piece');
        $dietaryTags = isset($input['dietary_tags']) && is_array($input['dietary_tags']) ? json_encode($input['dietary_tags']) : '[]';

        if (empty($name) || $price <= 0) {
            jsonResponse(false, 'Please provide an item name and valid price.', null, 400);
        }

        $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . rand(100, 999);

        $stmt = $db->prepare("INSERT INTO singular_menu_items (caterer_id, name, item_key, category, emoji, image_url, price, unit_label, quantity_available, min_order_qty, description, dietary_tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user['id'], $name, $itemKey, $category, $emoji, $imageUrl, $price, $unitLabel, $qty, $minQty, $description, $dietaryTags]);

        jsonResponse(true, 'Singular menu item added successfully!', ['item_id' => $db->lastInsertId()], 201);
        break;

    case 'update':
        requireRole(['caterer', 'admin']);
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(false, 'Invalid item ID', null, 400);
        }

        if ($user['role'] === 'caterer') {
            $stmtCheck = $db->prepare("SELECT caterer_id FROM singular_menu_items WHERE id = ? LIMIT 1");
            $stmtCheck->execute([$id]);
            $ownerId = (int)$stmtCheck->fetchColumn();
            if ($ownerId !== (int)$user['id']) {
                jsonResponse(false, 'Unauthorized. You do not own this menu item.', null, 403);
            }
        }

        $name = sanitize($input['name'] ?? '');
        $category = sanitize($input['category'] ?? 'Food');
        $price = (float)($input['price'] ?? 0);
        $qty = (int)($input['quantity_available'] ?? 50);
        $minQty = (int)($input['min_order_qty'] ?? 1);
        $description = sanitize($input['description'] ?? '');
        $unitLabel = sanitize($input['unit_label'] ?? 'piece');
        $imageUrl = sanitize($input['image_url'] ?? '');
        $emoji = sanitize($input['emoji'] ?? '🍽️');
        $dietaryTags = isset($input['dietary_tags']) && is_array($input['dietary_tags']) ? json_encode($input['dietary_tags']) : '[]';

        $stmt = $db->prepare("UPDATE singular_menu_items SET name = ?, category = ?, price = ?, quantity_available = ?, min_order_qty = ?, unit_label = ?, description = ?, image_url = ?, emoji = ?, dietary_tags = ? WHERE id = ?");
        $stmt->execute([$name, $category, $price, $qty, $minQty, $unitLabel, $description, $imageUrl, $emoji, $dietaryTags, $id]);

        jsonResponse(true, 'Singular menu item updated successfully!');
        break;

    case 'delete':
        requireRole(['caterer', 'admin']);
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            jsonResponse(false, 'Invalid item ID', null, 400);
        }

        if ($user['role'] === 'caterer') {
            $stmtCheck = $db->prepare("SELECT caterer_id FROM singular_menu_items WHERE id = ? LIMIT 1");
            $stmtCheck->execute([$id]);
            $ownerId = (int)$stmtCheck->fetchColumn();
            if ($ownerId !== (int)$user['id']) {
                jsonResponse(false, 'Unauthorized. You do not own this menu item.', null, 403);
            }
        }

        $stmt = $db->prepare("UPDATE singular_menu_items SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(true, 'Singular menu item deleted successfully.');
        break;

    default:
        jsonResponse(false, 'Invalid singular items action', null, 400);
        break;
}
