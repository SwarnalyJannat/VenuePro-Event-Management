<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();
$user = requireLogin();

switch ($action) {
    case 'list':
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll();

        $unreadCount = 0;
        foreach ($notifications as $n) {
            if (!$n['is_read']) $unreadCount++;
        }

        jsonResponse(true, 'Notifications retrieved', [
            'notifications' => $notifications,
            'unread_count'  => $unreadCount
        ]);
        break;

    case 'mark_read':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user['id']]);
        }

        jsonResponse(true, 'Notification marked as read');
        break;

    case 'mark_all_read':
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        jsonResponse(true, 'All notifications marked as read');
        break;

    case 'unread_count':
        $stmtUC = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmtUC->execute([$user['id']]);
        $uc = (int)$stmtUC->fetchColumn();
        jsonResponse(true, 'Unread count', ['count' => $uc]);
        break;

    default:
        jsonResponse(false, 'Invalid notifications action', null, 400);
        break;
}
