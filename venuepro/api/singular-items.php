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
        $sql = "SELECT * FROM singular_menu_items WHERE status = 'active'";
        $params = [];

        if (!empty($category) && $category !== 'All') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY category ASC, name ASC";
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
        $unitLabel = sanitize($input['unit_label'] ?? 'piece');
        $dietaryTags = isset($input['dietary_tags']) && is_array($input['dietary_tags']) ? json_encode($input['dietary_tags']) : '[]';

        if (empty($name) || $price <= 0) {
            jsonResponse(false, 'Please provide an item name and valid price.', null, 400);
        }

        $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . rand(100, 999);

        $stmt = $db->prepare("INSERT INTO singular_menu_items (caterer_id, name, item_key, category, emoji, price, unit_label, quantity_available, min_order_qty, description, dietary_tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$user['id'], $name, $itemKey, $category, $emoji, $price, $unitLabel, $qty, $minQty, $description, $dietaryTags]);

        jsonResponse(true, 'Singular menu item added successfully!', ['item_id' => $db->lastInsertId()], 201);
        break;

    case 'update':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? 0);

        if ($id <= 0) {
            jsonResponse(false, 'Invalid item ID', null, 400);
        }

        $name = sanitize($input['name'] ?? '');
        $category = sanitize($input['category'] ?? 'Food');
        $price = (float)($input['price'] ?? 0);
        $qty = (int)($input['quantity_available'] ?? 50);
        $minQty = (int)($input['min_order_qty'] ?? 1);
        $description = sanitize($input['description'] ?? '');

        $stmt = $db->prepare("UPDATE singular_menu_items SET name = ?, category = ?, price = ?, quantity_available = ?, min_order_qty = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $category, $price, $qty, $minQty, $description, $id]);

        jsonResponse(true, 'Singular menu item updated successfully!');
        break;

    case 'delete':
        requireRole(['caterer', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

        if ($id <= 0) {
            jsonResponse(false, 'Invalid item ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE singular_menu_items SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);

        jsonResponse(true, 'Singular menu item deleted successfully.');
        break;

    default:
        jsonResponse(false, 'Invalid singular items action', null, 400);
        break;
}
