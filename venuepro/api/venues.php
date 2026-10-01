<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {
    case 'list':
        $search = sanitize($_GET['search'] ?? '');
        $type = sanitize($_GET['type'] ?? '');
        $minCapacity = (int)($_GET['min_capacity'] ?? 0);
        $maxPrice = (float)($_GET['max_price'] ?? 0);
        $district = sanitize($_GET['district'] ?? '');

        $sql = "SELECT * FROM venues WHERE status = 'active'";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR district LIKE ? OR description LIKE ?)";
            $term = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term;
        }

        if (!empty($type) && $type !== 'All') {
            $sql .= " AND venue_type = ?";
            $params[] = $type;
        }

        if ($minCapacity > 0) {
            $sql .= " AND capacity >= ?";
            $params[] = $minCapacity;
        }

        if ($maxPrice > 0) {
            $sql .= " AND base_rate <= ?";
            $params[] = $maxPrice;
        }

        $sql .= " ORDER BY rating DESC, name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $venues = $stmt->fetchAll();

        foreach ($venues as &$v) {
            $v['amenities_list'] = !empty($v['amenities']) ? json_decode($v['amenities'], true) : [];
        }

        jsonResponse(true, 'Venues retrieved', ['venues' => $venues]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $slug = sanitize($_GET['slug'] ?? '');

        if ($id > 0) {
            $stmt = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
        } else if (!empty($slug)) {
            $stmt = $db->prepare("SELECT * FROM venues WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
        } else {
            jsonResponse(false, 'Missing venue ID or slug', null, 400);
        }

        $venue = $stmt->fetch();
        if (!$venue) {
            jsonResponse(false, 'Venue not found', null, 404);
        }

        $venue['amenities_list'] = !empty($venue['amenities']) ? json_decode($venue['amenities'], true) : [];
        jsonResponse(true, 'Venue found', ['venue' => $venue]);
        break;

    case 'create':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $name = sanitize($input['name'] ?? '');
        $venueType = sanitize($input['venue_type'] ?? 'Ballroom');
        $address = sanitize($input['address'] ?? '');
        $district = sanitize($input['district'] ?? '');
        $capacity = (int)($input['capacity'] ?? 100);
        $baseRate = (float)($input['base_rate'] ?? 2000.00);
        $serviceFee = (float)($input['service_fee_pct'] ?? 10.00);
        $extraHour = (float)($input['additional_hour_rate'] ?? 300.00);
        $description = sanitize($input['description'] ?? '');
        $amenities = isset($input['amenities']) && is_array($input['amenities']) ? json_encode($input['amenities']) : '[]';

        if (empty($name) || empty($address)) {
            jsonResponse(false, 'Venue Name and Address are required', null, 400);
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . rand(100, 999);

        $stmt = $db->prepare("INSERT INTO venues (name, slug, venue_type, address, district, capacity, base_rate, service_fee_pct, additional_hour_rate, description, amenities, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$name, $slug, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $amenities]);

        jsonResponse(true, 'Venue created successfully', ['venue_id' => $db->lastInsertId()], 201);
        break;

    default:
        jsonResponse(false, 'Invalid venue action', null, 400);
        break;
}
