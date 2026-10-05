<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/venue_db.php';
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

        try {
            $venueDb = getVenueDBConnection($venueId);
            $stmt = $venueDb->prepare("SELECT id, venue_id, file_name, caption, is_cover, sort_order FROM photos ORDER BY sort_order ASC, id ASC");
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $photos = [];
            foreach ($rows as $r) {
                $photos[] = [
                    'id'         => (int)$r['id'],
                    'venue_id'   => $venueId,
                    'photo_url'  => "api/venue-photo.php?venue_id={$venueId}&id={$r['id']}",
                    'caption'    => $r['caption'],
                    'is_cover'   => (int)$r['is_cover'],
                    'sort_order' => (int)$r['sort_order']
                ];
            }
        } catch (Exception $e) {
            $stmt = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
            $stmt->execute([$venueId]);
            $photos = $stmt->fetchAll();
        }

        jsonResponse(true, 'Photos fetched', ['photos' => $photos]);
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

        // Add to dedicated venue DB if physical file exists or seed
        $newId = 0;
        $sortOrder = 0;
        try {
            $venueDb = getVenueDBConnection($venueId);
            $stmtMax = $venueDb->prepare("SELECT COALESCE(MAX(sort_order),0)+1 FROM photos");
            $stmtMax->execute();
            $sortOrder = (int)$stmtMax->fetchColumn();

            // Check if local file exists to load as blob
            $localPath = __DIR__ . '/../' . ltrim($photoUrl, '/.');
            $blob = '';
            $fName = basename($photoUrl);
            $mime = 'image/jpeg';
            if (file_exists($localPath) && is_file($localPath)) {
                $blob = file_get_contents($localPath);
                $mime = mime_content_type($localPath) ?: 'image/jpeg';
            } else {
                $defaultPath = __DIR__ . '/../assets/venues pic/images.jpg';
                if (file_exists($defaultPath)) {
                    $blob = file_get_contents($defaultPath);
                }
            }

            $stmtIns = $venueDb->prepare("INSERT INTO photos (venue_id, file_name, mime_type, file_size, photo_data, caption, is_cover, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)");
            $stmtIns->execute([$venueId, $fName, $mime, strlen($blob), $blob, $caption, $sortOrder, $userId]);
            $newId = (int)$venueDb->lastInsertId();
            $streamUrl = "api/venue-photo.php?venue_id={$venueId}&id={$newId}";
        } catch (Exception $e) {
            $streamUrl = $photoUrl;
        }

        // Mirror into main DB venue_photos
        $stmt = $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$venueId, $streamUrl, $caption, $sortOrder, $userId]);
        $mainDbId = (int)$db->lastInsertId();

        jsonResponse(true, 'Photo added', ['id' => $newId ?: $mainDbId, 'photo_url' => $streamUrl, 'sort_order' => $sortOrder], 201);
        break;

    // POST: delete a photo (admin only)
    case 'delete':
        requireRole(['admin'], 'admin-login.php');
        $input = getJsonInput();
        $photoId = (int)($input['id'] ?? ($_POST['id'] ?? 0));
        if ($photoId <= 0) {
            jsonResponse(false, 'Photo ID required', null, 400);
        }

        // Get info from main DB
        $stmtF = $db->prepare("SELECT venue_id, photo_url FROM venue_photos WHERE id = ? OR photo_url LIKE ? LIMIT 1");
        $stmtF->execute([$photoId, "%id={$photoId}%"]);
        $pRow = $stmtF->fetch();

        if ($pRow) {
            $vId = (int)$pRow['venue_id'];
            try {
                $venueDb = getVenueDBConnection($vId);
                $venueDb->prepare("DELETE FROM photos WHERE id = ?")->execute([$photoId]);
            } catch (Exception $e) {}
        }

        $stmt = $db->prepare("DELETE FROM venue_photos WHERE id = ? OR photo_url LIKE ?");
        $stmt->execute([$photoId, "%id={$photoId}%"]);
        jsonResponse(true, 'Photo removed');
        break;

    default:
        jsonResponse(false, 'Invalid action', null, 400);
}
