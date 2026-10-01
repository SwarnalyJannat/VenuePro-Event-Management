<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'get';
$db = getDBConnection();

switch ($action) {
    case 'get':
        $key = sanitize($_GET['key'] ?? 'privacy_policy');
        $stmt = $db->prepare("SELECT * FROM site_policies WHERE policy_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $policy = $stmt->fetch();

        if (!$policy) {
            jsonResponse(false, 'Policy not found', null, 404);
        }

        jsonResponse(true, 'Policy retrieved', ['policy' => $policy]);
        break;

    case 'update':
        requireRole('admin');
        $user = getCurrentUser();
        $input = !empty($_POST) ? $_POST : getJsonInput();

        $key = sanitize($input['key'] ?? '');
        $title = sanitize($input['title'] ?? '');
        $content = $input['content'] ?? '';

        if (empty($key) || empty($content)) {
            jsonResponse(false, 'Policy key and content are required', null, 400);
        }

        $stmt = $db->prepare("UPDATE site_policies SET title = ?, content = ?, updated_by = ?, updated_at = NOW() WHERE policy_key = ?");
        $stmt->execute([$title, $content, $user['id'], $key]);

        jsonResponse(true, 'Legal policy document updated successfully!');
        break;

    default:
        jsonResponse(false, 'Invalid policy action', null, 400);
        break;
}
