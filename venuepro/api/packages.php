<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $tier = sanitize($_GET['tier'] ?? '');
        $sql = "SELECT p.*, u.name AS caterer_name, cp.business_name FROM catering_packages p LEFT JOIN users u ON p.caterer_id = u.id LEFT JOIN caterer_profiles cp ON u.id = cp.user_id WHERE p.status = 'published'";
        $params = [];

        if (!empty($tier) && $tier !== 'All') {
            $sql .= " AND p.tier = ?";
            $params[] = $tier;
        }

        $sql .= " ORDER BY p.price_per_event ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $packages = $stmt->fetchAll();

        foreach ($packages as &$pkg) {
            $pkg['features_list'] = !empty($pkg['features']) ? json_decode($pkg['features'], true) : [];
        }

        jsonResponse(true, 'Packages retrieved', ['packages' => $packages]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(false, 'Missing package ID', null, 400);
        }

        $stmt = $db->prepare("SELECT p.*, u.name AS caterer_name, cp.business_name FROM catering_packages p LEFT JOIN users u ON p.caterer_id = u.id LEFT JOIN caterer_profiles cp ON u.id = cp.user_id WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $package = $stmt->fetch();

        if (!$package) {
            jsonResponse(false, 'Package not found', null, 404);
        }

        $package['features_list'] = !empty($package['features']) ? json_decode($package['features'], true) : [];

        // Course items
        $stmtItems = $db->prepare("SELECT * FROM package_menu_items WHERE package_id = ? ORDER BY course ASC, id ASC");
        $stmtItems->execute([$id]);
        $package['menu_items'] = $stmtItems->fetchAll();

        jsonResponse(true, 'Package details retrieved', ['package' => $package]);
        break;

    case 'create':
        requireRole(['caterer', 'admin']);
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();

        $title = sanitize($input['title'] ?? '');
        $tier = sanitize($input['tier'] ?? 'Gold');
        $priceEvent = (float)($input['price_per_event'] ?? 1200.00);
        $priceGuest = (float)($input['price_per_guest'] ?? 24.00);
        $minGuests = (int)($input['min_guests'] ?? 30);
        $maxCapacity = (int)($input['max_capacity'] ?? 500);
        $description = sanitize($input['description'] ?? '');
        $cuisine = sanitize($input['cuisine_type'] ?? 'Fine Dining');
        $service = sanitize($input['service_style'] ?? 'Plated Dinner');
        $features = isset($input['features']) && is_array($input['features']) ? json_encode($input['features']) : '[]';

        if (empty($title) || empty($description)) {
            jsonResponse(false, 'Package Title and Description are required', null, 400);
        }

        $stmt = $db->prepare("INSERT INTO catering_packages (caterer_id, title, tier, price_per_event, price_per_guest, min_guests, max_capacity, description, cuisine_type, service_style, features, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$user['id'], $title, $tier, $priceEvent, $priceGuest, $minGuests, $maxCapacity, $description, $cuisine, $service, $features]);

        jsonResponse(true, 'Catering package created successfully', ['package_id' => $db->lastInsertId()], 201);
        break;

    case 'update':
        requireRole(['caterer', 'admin']);
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(false, 'Missing package ID', null, 400);
        }

        $stmtChk = $db->prepare("SELECT * FROM catering_packages WHERE id = ? LIMIT 1");
        $stmtChk->execute([$id]);
        $pkg = $stmtChk->fetch();
        if (!$pkg) {
            jsonResponse(false, 'Package not found', null, 404);
        }

        $title = sanitize($input['title'] ?? '');
        $tier = sanitize($input['tier'] ?? 'Gold');
        $priceEvent = (float)($input['price_per_event'] ?? 1200.00);
        $priceGuest = (float)($input['price_per_guest'] ?? 24.00);
        $minGuests = (int)($input['min_guests'] ?? 30);
        $maxCapacity = (int)($input['max_capacity'] ?? 500);
        $description = sanitize($input['description'] ?? '');
        $cuisine = sanitize($input['cuisine_type'] ?? 'Fine Dining');
        $service = sanitize($input['service_style'] ?? 'Plated Dinner');
        $status = sanitize($input['status'] ?? 'published');
        $features = isset($input['features']) && is_array($input['features']) ? json_encode($input['features']) : null;

        if (empty($title) || empty($description)) {
            jsonResponse(false, 'Package Title and Description are required', null, 400);
        }

        if ($features !== null) {
            $stmt = $db->prepare("UPDATE catering_packages SET title=?, tier=?, price_per_event=?, price_per_guest=?, min_guests=?, max_capacity=?, description=?, cuisine_type=?, service_style=?, features=?, status=? WHERE id=?");
            $stmt->execute([$title, $tier, $priceEvent, $priceGuest, $minGuests, $maxCapacity, $description, $cuisine, $service, $features, $status, $id]);
        } else {
            $stmt = $db->prepare("UPDATE catering_packages SET title=?, tier=?, price_per_event=?, price_per_guest=?, min_guests=?, max_capacity=?, description=?, cuisine_type=?, service_style=?, status=? WHERE id=?");
            $stmt->execute([$title, $tier, $priceEvent, $priceGuest, $minGuests, $maxCapacity, $description, $cuisine, $service, $status, $id]);
        }

        jsonResponse(true, 'Catering package updated successfully!');
        break;

    default:
        jsonResponse(false, 'Invalid package action', null, 400);
        break;
}
