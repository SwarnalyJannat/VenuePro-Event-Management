<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$db = getDBConnection();
// Ensure session has caterer user
$userStmt = $db->query("SELECT * FROM users WHERE role = 'caterer' LIMIT 1");
$caterer = $userStmt->fetch();
if (!$caterer) {
    die("No caterer found\n");
}
$_SESSION['user'] = $caterer;

$testPackage = [
    'title' => 'Test Tasting Menu',
    'tier' => 'Platinum',
    'price_per_event' => 1500,
    'price_per_guest' => 35,
    'min_guests' => 20,
    'max_capacity' => 100,
    'description' => 'A custom dynamic tasting package',
    'cuisine_type' => 'French Fusion',
    'service_style' => 'Plated Dinner',
    'features' => ['Tartare', 'Foie Gras', 'Sorbet', 'Wagyu Beef', 'Souffle'],
    'courses' => [
        ['title' => 'Course 1', 'items' => ['Tartare', 'Foie Gras']],
        ['title' => 'Course 2', 'items' => ['Sorbet']],
        ['title' => 'Course 3', 'items' => ['Wagyu Beef']],
        ['title' => 'Course 4', 'items' => ['Souffle']]
    ]
];

// Simulate API call logic
$stmt = $db->prepare("INSERT INTO catering_packages (caterer_id, title, tier, price_per_event, price_per_guest, min_guests, max_capacity, description, cuisine_type, service_style, features, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
$stmt->execute([
    $caterer['id'],
    $testPackage['title'],
    $testPackage['tier'],
    $testPackage['price_per_event'],
    $testPackage['price_per_guest'],
    $testPackage['min_guests'],
    $testPackage['max_capacity'],
    $testPackage['description'],
    $testPackage['cuisine_type'],
    $testPackage['service_style'],
    json_encode($testPackage['features'])
]);
$packageId = (int)$db->lastInsertId();

$stmtItem = $db->prepare("INSERT INTO package_menu_items (package_id, course, item_name, unit_value) VALUES (?, ?, ?, ?)");
foreach ($testPackage['courses'] as $course) {
    $cTitle = !empty($course['title']) ? sanitize($course['title']) : 'Course 1';
    if (!empty($course['items']) && is_array($course['items'])) {
        foreach ($course['items'] as $item) {
            $itemName = trim(sanitize($item));
            if ($itemName !== '') {
                $stmtItem->execute([$packageId, $cTitle, $itemName, 15.00]);
            }
        }
    }
}

// Verify items inserted
$verify = $db->prepare("SELECT course, item_name FROM package_menu_items WHERE package_id = ?");
$verify->execute([$packageId]);
$rows = $verify->fetchAll(PDO::FETCH_ASSOC);

echo "Inserted Package ID: $packageId\n";
echo "Items inserted:\n";
foreach ($rows as $r) {
    echo "- [{$r['course']}] {$r['item_name']}\n";
}

// Clean up test package
$db->exec("DELETE FROM catering_packages WHERE id = $packageId");
echo "Cleaned up test package\n";
