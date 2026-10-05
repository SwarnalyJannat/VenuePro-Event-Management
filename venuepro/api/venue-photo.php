<?php
/**
 * VenuePro Secure Photo Stream Controller
 * Streams photo binary data directly from each venue's dedicated MySQL database.
 */
require_once __DIR__ . '/../config/venue_db.php';

$venueId = (int)($_GET['venue_id'] ?? 0);
$photoId = (int)($_GET['photo_id'] ?? ($_GET['id'] ?? 0));
$isCover = isset($_GET['cover']) && (string)$_GET['cover'] === '1';

if ($venueId <= 0) {
    serveFallbackImage();
    exit;
}

try {
    $venueDb = getVenueDBConnection($venueId);

    if ($isCover) {
        $stmt = $venueDb->prepare("SELECT mime_type, file_name, file_size, photo_data FROM photos WHERE is_cover = 1 ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $photo = $stmt->fetch();
        if (!$photo) {
            // If no designated cover, get the first sorted photo
            $stmt = $venueDb->prepare("SELECT mime_type, file_name, file_size, photo_data FROM photos ORDER BY sort_order ASC, id ASC LIMIT 1");
            $stmt->execute();
            $photo = $stmt->fetch();
        }
    } elseif ($photoId > 0) {
        $stmt = $venueDb->prepare("SELECT mime_type, file_name, file_size, photo_data FROM photos WHERE id = ? LIMIT 1");
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch();
    } else {
        // Default to first photo
        $stmt = $venueDb->prepare("SELECT mime_type, file_name, file_size, photo_data FROM photos ORDER BY sort_order ASC, id ASC LIMIT 1");
        $stmt->execute();
        $photo = $stmt->fetch();
    }

    if ($photo && !empty($photo['photo_data'])) {
        $mime = !empty($photo['mime_type']) ? $photo['mime_type'] : 'image/jpeg';
        header("Content-Type: " . $mime);
        header("Cache-Control: public, max-age=86400");
        header("Content-Length: " . strlen($photo['photo_data']));
        echo $photo['photo_data'];
        exit;
    }
} catch (Exception $e) {
    // Graceful fallback if database connection or query encounters issue
}

serveFallbackImage();

function serveFallbackImage() {
    $fallbackFile = __DIR__ . '/../assets/venues pic/images.jpg';
    if (file_exists($fallbackFile)) {
        header("Content-Type: image/jpeg");
        header("Cache-Control: public, max-age=86400");
        readfile($fallbackFile);
        exit;
    }

    // Default SVG placeholder if file not present
    header("Content-Type: image/svg+xml");
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="sans-serif" font-size="24">Venue Photograph</text></svg>';
    exit;
}
