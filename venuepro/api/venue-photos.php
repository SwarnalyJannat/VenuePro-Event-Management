<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';
$db = getDBConnection();

switch ($action) {

    // GET: list photos for a venue (public)
    case 'list':
        $venueId = (int)($_GET['venue_id'] ?? 0);
        if ($venueId <= 0) {
            jsonResponse(false, 'venue_id required', null, 400);
        }
        $stmt = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$venueId]);
        jsonResponse(true, 'Photos fetched', ['photos' => $stmt->fetchAll()]);
        break;

    // POST: add a photo (admin only)
    case 'add':
        requireRole(['admin'], 'admin-login.php');
        $input = getJsonInput();
        $venueId  = (int)($input['venue_id'] ?? 0);
        $photoUrl = sanitize($input['photo_url'] ?? '');
        $caption  = sanitize($input['caption'] ?? '');
        $userId   = (int)($_SESSION['user_id'] ?? 0);

        if ($venueId <= 0 || empty($photoUrl)) {
            jsonResponse(false, 'venue_id and photo_url are required', null, 400);
        }

        // Max sort_order + 1
        $stmtMax = $db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM venue_photos WHERE venue_id = ?");
        $stmtMax->execute([$venueId]);
        $sortOrder = (int)$stmtMax->fetchColumn();

        $stmt = $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$venueId, $photoUrl, $caption, $sortOrder, $userId]);
        $newId = (int)$db->lastInsertId();

        jsonResponse(true, 'Photo added', ['id' => $newId, 'sort_order' => $sortOrder], 201);
        break;

    // POST: delete a photo (admin only)
    case 'delete':
        requireRole(['admin'], 'admin-login.php');
        $input = getJsonInput();
        $photoId = (int)($input['id'] ?? 0);
        if ($photoId <= 0) {
            jsonResponse(false, 'Photo ID required', null, 400);
        }
        $stmt = $db->prepare("DELETE FROM venue_photos WHERE id = ?");
        $stmt->execute([$photoId]);
        jsonResponse(true, 'Photo removed');
        break;

    default:
        jsonResponse(false, 'Invalid action', null, 400);
}
