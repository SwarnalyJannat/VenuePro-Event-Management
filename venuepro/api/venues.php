<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/venue_db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$db = getDBConnection();

/**
 * Helper to upload image files to assets/venues pic/ (filesystem backup if needed)
 * Returns relative path 'assets/venues pic/{filename}' or null on failure/empty
 */
function handleVenueImageUpload($fileKey, $prefix = 'venue') {
    if (!isset($_FILES[$fileKey]) || empty($_FILES[$fileKey]['name'])) {
        return null;
    }
    $file = $_FILES[$fileKey];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        return null;
    }

    // MIME type check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!str_starts_with($mime, 'image/')) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/venues pic/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $cleanPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix);
    $filename = $cleanPrefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'assets/venues pic/' . $filename;
    }
    return null;
}

switch ($action) {
    case 'list':
        $search = sanitize($_GET['search'] ?? '');
        $type = sanitize($_GET['type'] ?? '');
        $minCapacity = (int)($_GET['min_capacity'] ?? 0);
        $maxPrice = (float)($_GET['max_price'] ?? 0);
        $district = sanitize($_GET['district'] ?? '');

        $sql = "SELECT v.*, (SELECT COUNT(*) FROM venue_photos vp WHERE vp.venue_id = v.id) AS photo_count FROM venues v WHERE v.status = 'active'";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (v.name LIKE ? OR v.district LIKE ? OR v.description LIKE ?)";
            $term = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term;
        }

        if (!empty($type) && $type !== 'All') {
            $sql .= " AND v.venue_type = ?";
            $params[] = $type;
        }

        if ($minCapacity > 0) {
            $sql .= " AND v.capacity >= ?";
            $params[] = $minCapacity;
        }

        if ($maxPrice > 0) {
            $sql .= " AND v.base_rate <= ?";
            $params[] = $maxPrice;
        }

        $sql .= " ORDER BY v.name ASC";
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

        // Load uploaded photos from venue's dedicated database
        try {
            $venueDb = getVenueDBConnection((int)$venue['id']);
            $stmtPh = $venueDb->prepare("SELECT id, venue_id, file_name, caption, is_cover, sort_order FROM photos ORDER BY sort_order ASC, id ASC");
            $stmtPh->execute();
            $rawPhotos = $stmtPh->fetchAll();
            $photos = [];
            foreach ($rawPhotos as $ph) {
                $photos[] = [
                    'id'         => (int)$ph['id'],
                    'venue_id'   => (int)$ph['venue_id'],
                    'photo_url'  => "api/venue-photo.php?venue_id={$venue['id']}&id={$ph['id']}",
                    'caption'    => $ph['caption'],
                    'is_cover'   => (int)$ph['is_cover'],
                    'sort_order' => (int)$ph['sort_order'],
                    'file_name'  => $ph['file_name']
                ];
            }
        } catch (Exception $e) {
            $stmtPh = $db->prepare("SELECT * FROM venue_photos WHERE venue_id = ? ORDER BY sort_order ASC, id ASC");
            $stmtPh->execute([$venue['id']]);
            $photos = $stmtPh->fetchAll();
        }

        jsonResponse(true, 'Venue found', ['venue' => $venue, 'photos' => $photos]);
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

        // Amenities parsing
        $amenities = '[]';
        if (isset($input['amenities'])) {
            if (is_array($input['amenities'])) {
                $amenities = json_encode(array_values($input['amenities']));
            } else if (is_string($input['amenities'])) {
                $amenities = $input['amenities'];
            }
        }

        if (empty($name) || empty($address)) {
            jsonResponse(false, 'Venue Name and Address are required', null, 400);
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . rand(100, 999);

        // Insert initial venue record
        $stmt = $db->prepare("INSERT INTO venues (name, slug, venue_type, address, district, capacity, base_rate, service_fee_pct, additional_hour_rate, description, amenities, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', 'active')");
        $stmt->execute([$name, $slug, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $amenities]);
        $newVenueId = (int)$db->lastInsertId();

        // Step 1: Provision dedicated MySQL database for this new venue
        $venueDb = getVenueDBConnection($newVenueId);

        $currentAdmin = getCurrentUser();
        $adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

        // Step 2: Handle primary cover image upload directly into venue database
        $uploadedFileKey = null;
        if (isset($_FILES['venue_image']) && $_FILES['venue_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileKey = 'venue_image';
        } else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileKey = 'image';
        }

        if ($uploadedFileKey) {
            $f = $_FILES[$uploadedFileKey];
            $blob = file_get_contents($f['tmp_name']);
            $fName = basename($f['name']);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fMime = finfo_file($finfo, $f['tmp_name']) ?: 'image/jpeg';
            finfo_close($finfo);
            $fSize = (int)$f['size'];

            $stmtInsPhoto = $venueDb->prepare("INSERT INTO photos (venue_id, file_name, mime_type, file_size, photo_data, caption, is_cover, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?)");
            $stmtInsPhoto->execute([$newVenueId, $fName, $fMime, $fSize, $blob, $name . ' — Primary Cover', $adminId]);
            $newPhotoId = (int)$venueDb->lastInsertId();

            $imageUrl = "api/venue-photo.php?venue_id={$newVenueId}&cover=1";
            $photoUrl = "api/venue-photo.php?venue_id={$newVenueId}&id={$newPhotoId}";

            // Mirror into main DB venue_photos for backward compatibility
            $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, 0, ?)")
               ->execute([$newVenueId, $photoUrl, $name . ' — Primary Space', $adminId]);
        } else {
            // Seed default photo binary into this venue's dedicated database
            $defaultFile = __DIR__ . '/../assets/venues pic/images.jpg';
            if (file_exists($defaultFile)) {
                $blob = file_get_contents($defaultFile);
                $stmtInsPhoto = $venueDb->prepare("INSERT INTO photos (venue_id, file_name, mime_type, file_size, photo_data, caption, is_cover, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?)");
                $stmtInsPhoto->execute([$newVenueId, 'images.jpg', 'image/jpeg', strlen($blob), $blob, $name . ' — Default Space', $adminId]);
                $newPhotoId = (int)$venueDb->lastInsertId();
                $photoUrl = "api/venue-photo.php?venue_id={$newVenueId}&id={$newPhotoId}";
                $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, 0, ?)")
                   ->execute([$newVenueId, $photoUrl, $name . ' — Default Space', $adminId]);
            }
            $imageUrl = "api/venue-photo.php?venue_id={$newVenueId}&cover=1";
        }

        // Set venue's primary image_url to the secured stream endpoint
        $db->prepare("UPDATE venues SET image_url = ? WHERE id = ?")->execute([$imageUrl, $newVenueId]);

        jsonResponse(true, 'Venue created successfully with dedicated database', ['venue_id' => $newVenueId, 'image_url' => $imageUrl], 201);
        break;

    case 'update':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(false, 'Missing valid venue ID', null, 400);
        }

        // Fetch existing venue record
        $stmtCurr = $db->prepare("SELECT * FROM venues WHERE id = ? LIMIT 1");
        $stmtCurr->execute([$id]);
        $currentVenue = $stmtCurr->fetch();
        if (!$currentVenue) {
            jsonResponse(false, 'Venue not found', null, 404);
        }

        $name = sanitize($input['name'] ?? $currentVenue['name']);
        $venueType = sanitize($input['venue_type'] ?? $currentVenue['venue_type']);
        $address = sanitize($input['address'] ?? $currentVenue['address']);
        $district = sanitize($input['district'] ?? $currentVenue['district']);
        $capacity = (int)($input['capacity'] ?? ($input['max_capacity'] ?? $currentVenue['capacity']));
        $baseRate = (float)($input['base_rate'] ?? $currentVenue['base_rate']);
        $serviceFee = (float)($input['service_fee_pct'] ?? $currentVenue['service_fee_pct']);
        $extraHour = (float)($input['additional_hour_rate'] ?? $currentVenue['additional_hour_rate']);
        $description = sanitize($input['description'] ?? $currentVenue['description']);
        $status = sanitize($input['status'] ?? $currentVenue['status']);

        // Amenities
        $amenities = null;
        if (isset($input['amenities'])) {
            if (is_array($input['amenities'])) {
                $amenities = json_encode(array_values($input['amenities']));
            } else if (is_string($input['amenities'])) {
                $amenities = $input['amenities'];
            }
        }

        if (empty($name) || empty($address)) {
            jsonResponse(false, 'Venue Name and Address are required', null, 400);
        }

        // Check if a new cover image file was uploaded
        $uploadedFileKey = null;
        if (isset($_FILES['venue_image']) && $_FILES['venue_image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileKey = 'venue_image';
        } else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadedFileKey = 'image';
        }

        $newImageUrl = null;
        if ($uploadedFileKey) {
            $venueDb = getVenueDBConnection($id);
            $f = $_FILES[$uploadedFileKey];
            $blob = file_get_contents($f['tmp_name']);
            $fName = basename($f['name']);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fMime = finfo_file($finfo, $f['tmp_name']) ?: 'image/jpeg';
            finfo_close($finfo);
            $fSize = (int)$f['size'];

            // Unset previous covers in dedicated DB
            $venueDb->exec("UPDATE photos SET is_cover = 0");

            $currentAdmin = getCurrentUser();
            $adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

            // Insert new cover into dedicated venue DB
            $stmtInsPhoto = $venueDb->prepare("INSERT INTO photos (venue_id, file_name, mime_type, file_size, photo_data, caption, is_cover, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, 1, 0, ?)");
            $stmtInsPhoto->execute([$id, $fName, $fMime, $fSize, $blob, $name . ' — Primary Cover Image', $adminId]);
            $newPhotoId = (int)$venueDb->lastInsertId();

            $newImageUrl = "api/venue-photo.php?venue_id={$id}&cover=1";
            $photoUrl = "api/venue-photo.php?venue_id={$id}&id={$newPhotoId}";

            // Mirror into main DB
            $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, 0, ?)")
               ->execute([$id, $photoUrl, $name . ' — Primary Cover Image', $adminId]);
        } else if (!empty($input['image_url'])) {
            $newImageUrl = sanitize($input['image_url']);
        }

        if ($newImageUrl !== null) {
            // Update image_url
            if ($amenities !== null) {
                $stmt = $db->prepare("UPDATE venues SET name=?, venue_type=?, address=?, district=?, capacity=?, base_rate=?, service_fee_pct=?, additional_hour_rate=?, description=?, amenities=?, image_url=?, status=? WHERE id=?");
                $stmt->execute([$name, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $amenities, $newImageUrl, $status, $id]);
            } else {
                $stmt = $db->prepare("UPDATE venues SET name=?, venue_type=?, address=?, district=?, capacity=?, base_rate=?, service_fee_pct=?, additional_hour_rate=?, description=?, image_url=?, status=? WHERE id=?");
                $stmt->execute([$name, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $newImageUrl, $status, $id]);
            }
            $finalImage = $newImageUrl;
        } else {
            // Keep existing image_url
            if ($amenities !== null) {
                $stmt = $db->prepare("UPDATE venues SET name=?, venue_type=?, address=?, district=?, capacity=?, base_rate=?, service_fee_pct=?, additional_hour_rate=?, description=?, amenities=?, status=? WHERE id=?");
                $stmt->execute([$name, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $amenities, $status, $id]);
            } else {
                $stmt = $db->prepare("UPDATE venues SET name=?, venue_type=?, address=?, district=?, capacity=?, base_rate=?, service_fee_pct=?, additional_hour_rate=?, description=?, status=? WHERE id=?");
                $stmt->execute([$name, $venueType, $address, $district, $capacity, $baseRate, $serviceFee, $extraHour, $description, $status, $id]);
            }
            $finalImage = $currentVenue['image_url'];
        }

        jsonResponse(true, 'Venue updated successfully', [
            'venue_id'  => $id,
            'image_url' => $finalImage
        ]);
        break;

    case 'delete':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            jsonResponse(false, 'Valid venue ID is required', null, 400);
        }
        $db->prepare("UPDATE venues SET status = 'inactive' WHERE id = ?")->execute([$id]);
        jsonResponse(true, 'Venue removed successfully.');
        break;

    /* ── VENUE PHOTOS MANAGEMENT ENDPOINTS (MULTI-DATABASE) ── */

    case 'list_photos':
        $venueId = (int)($_GET['venue_id'] ?? ($_POST['venue_id'] ?? 0));
        if ($venueId <= 0) {
            jsonResponse(false, 'Missing valid venue ID', null, 400);
        }

        $venueDb = getVenueDBConnection($venueId);
        $stmtPh = $venueDb->prepare("SELECT id, venue_id, file_name, mime_type, file_size, caption, is_cover, sort_order, uploaded_by, created_at FROM photos ORDER BY sort_order ASC, id ASC");
        $stmtPh->execute();
        $dbPhotos = $stmtPh->fetchAll();

        $photos = [];
        foreach ($dbPhotos as $ph) {
            $photos[] = [
                'id'         => (int)$ph['id'],
                'venue_id'   => $venueId,
                'photo_url'  => "api/venue-photo.php?venue_id={$venueId}&id={$ph['id']}",
                'caption'    => $ph['caption'],
                'is_cover'   => (int)$ph['is_cover'],
                'sort_order' => (int)$ph['sort_order'],
                'file_name'  => $ph['file_name'],
                'file_size'  => (int)$ph['file_size']
            ];
        }

        // Also fetch venue cover image and basic details
        $stmtV = $db->prepare("SELECT id, name, image_url FROM venues WHERE id = ? LIMIT 1");
        $stmtV->execute([$venueId]);
        $venue = $stmtV->fetch();

        jsonResponse(true, 'Photos retrieved', [
            'venue'  => $venue,
            'photos' => $photos
        ]);
        break;

    case 'upload_photo':
    case 'upload_photos':
        requireRole('admin');
        $venueId = (int)($_POST['venue_id'] ?? ($_GET['venue_id'] ?? 0));
        if ($venueId <= 0) {
            jsonResponse(false, 'Valid venue ID is required', null, 400);
        }

        // Verify venue exists
        $stmtV = $db->prepare("SELECT id, name, image_url FROM venues WHERE id = ? LIMIT 1");
        $stmtV->execute([$venueId]);
        $venueRow = $stmtV->fetch();
        if (!$venueRow) {
            jsonResponse(false, 'Venue not found', null, 404);
        }

        $caption = sanitize($_POST['caption'] ?? '');
        $currentAdmin = getCurrentUser();
        $adminId = $currentAdmin ? (int)$currentAdmin['id'] : null;

        // Collect uploaded files
        $filesToProcess = [];
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $filesToProcess[] = [
                        'name'     => $_FILES['photos']['name'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'size'     => $_FILES['photos']['size'][$i]
                    ];
                }
            }
        } else if (isset($_FILES['photos']) && $_FILES['photos']['error'] === UPLOAD_ERR_OK) {
            $filesToProcess[] = $_FILES['photos'];
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $filesToProcess[] = $_FILES['photo'];
        }

        if (empty($filesToProcess)) {
            jsonResponse(false, 'No valid image files provided for upload', null, 400);
        }

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $uploaded = [];
        $venueDb = getVenueDBConnection($venueId);

        $countExisting = (int)$venueDb->query("SELECT COUNT(*) FROM photos")->fetchColumn();

        foreach ($filesToProcess as $fileItem) {
            $ext = strtolower(pathinfo($fileItem['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) continue;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fileItem['tmp_name']);
            finfo_close($finfo);
            if (!str_starts_with($mime, 'image/')) continue;

            $blobData = file_get_contents($fileItem['tmp_name']);
            if ($blobData === false || strlen($blobData) === 0) continue;

            $cap = !empty($caption) ? $caption : pathinfo($fileItem['name'], PATHINFO_FILENAME);
            $isCover = ($countExisting === 0 && empty($uploaded)) ? 1 : 0;

            // Get next sort order
            $nextSort = (int)$venueDb->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM photos")->fetchColumn();

            // Insert into venue's dedicated database as LONGBLOB
            $stmtIns = $venueDb->prepare("INSERT INTO photos (venue_id, file_name, mime_type, file_size, photo_data, caption, is_cover, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$venueId, $fileItem['name'], $mime, strlen($blobData), $blobData, $cap, $isCover, $nextSort, $adminId]);
            $photoId = (int)$venueDb->lastInsertId();

            $photoUrl = "api/venue-photo.php?venue_id={$venueId}&id={$photoId}";

            // Mirror into main DB venue_photos for backward compatibility
            $stmtMirror = $db->prepare("INSERT INTO venue_photos (venue_id, photo_url, caption, sort_order, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmtMirror->execute([$venueId, $photoUrl, $cap, $nextSort, $adminId]);

            // If designated as cover or venue image empty, set stream endpoint as primary cover
            if ($isCover === 1 || empty($venueRow['image_url']) || str_contains($venueRow['image_url'], 'venue-default')) {
                $coverUrl = "api/venue-photo.php?venue_id={$venueId}&cover=1";
                $db->prepare("UPDATE venues SET image_url = ? WHERE id = ?")->execute([$coverUrl, $venueId]);
                $venueRow['image_url'] = $coverUrl;
            }

            $uploaded[] = [
                'id'        => $photoId,
                'venue_id'  => $venueId,
                'photo_url' => $photoUrl,
                'caption'   => $cap,
                'is_cover'  => $isCover
            ];
        }

        if (empty($uploaded)) {
            jsonResponse(false, 'Failed to store image files. Supported formats: JPG, PNG, WEBP, GIF.', null, 400);
        }

        jsonResponse(true, count($uploaded) . ' photo(s) secured in venue database successfully', ['photos' => $uploaded]);
        break;

    case 'update_photo':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $photoId = (int)($input['photo_id'] ?? ($input['id'] ?? 0));
        $venueId = (int)($input['venue_id'] ?? 0);
        if ($photoId <= 0) {
            jsonResponse(false, 'Missing valid photo ID', null, 400);
        }
        $caption = sanitize($input['caption'] ?? '');
        $sortOrder = (int)($input['sort_order'] ?? 0);

        // Find venueId if not explicitly provided
        if ($venueId <= 0) {
            $stmtVp = $db->prepare("SELECT venue_id FROM venue_photos WHERE id = ? OR photo_url LIKE ? LIMIT 1");
            $stmtVp->execute([$photoId, "%id={$photoId}%"]);
            $rowVp = $stmtVp->fetch();
            if ($rowVp) {
                $venueId = (int)$rowVp['venue_id'];
            }
        }

        if ($venueId > 0) {
            try {
                $venueDb = getVenueDBConnection($venueId);
                $stmtUp = $venueDb->prepare("UPDATE photos SET caption = ?, sort_order = ? WHERE id = ?");
                $stmtUp->execute([$caption, $sortOrder, $photoId]);
            } catch (Exception $e) {}
        }

        // Mirror update to main DB
        $stmtUpPh = $db->prepare("UPDATE venue_photos SET caption = ?, sort_order = ? WHERE id = ? OR photo_url LIKE ?");
        $stmtUpPh->execute([$caption, $sortOrder, $photoId, "%id={$photoId}%"]);

        jsonResponse(true, 'Photo caption updated successfully');
        break;

    case 'delete_photo':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $photoId = (int)($input['photo_id'] ?? ($input['id'] ?? ($_GET['photo_id'] ?? 0)));
        $venueId = (int)($input['venue_id'] ?? ($_GET['venue_id'] ?? 0));
        if ($photoId <= 0) {
            jsonResponse(false, 'Missing valid photo ID', null, 400);
        }

        // Find venueId if not explicitly provided
        if ($venueId <= 0) {
            $stmtVp = $db->prepare("SELECT venue_id FROM venue_photos WHERE id = ? OR photo_url LIKE ? LIMIT 1");
            $stmtVp->execute([$photoId, "%id={$photoId}%"]);
            $rowVp = $stmtVp->fetch();
            if ($rowVp) {
                $venueId = (int)$rowVp['venue_id'];
            }
        }

        if ($venueId > 0) {
            try {
                $venueDb = getVenueDBConnection($venueId);
                $isCover = (int)$venueDb->query("SELECT is_cover FROM photos WHERE id = {$photoId}")->fetchColumn();
                $venueDb->prepare("DELETE FROM photos WHERE id = ?")->execute([$photoId]);

                if ($isCover === 1) {
                    // Promote next photo as cover
                    $nextPhoto = $venueDb->query("SELECT id FROM photos ORDER BY sort_order ASC, id ASC LIMIT 1")->fetch();
                    if ($nextPhoto) {
                        $venueDb->prepare("UPDATE photos SET is_cover = 1 WHERE id = ?")->execute([$nextPhoto['id']]);
                    }
                }
            } catch (Exception $e) {}
        }

        // Delete from main DB
        $db->prepare("DELETE FROM venue_photos WHERE id = ? OR photo_url LIKE ?")->execute([$photoId, "%id={$photoId}%"]);

        jsonResponse(true, 'Photo removed successfully');
        break;

    case 'set_cover_photo':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $venueId = (int)($input['venue_id'] ?? 0);
        $photoId = (int)($input['photo_id'] ?? 0);

        if ($venueId <= 0 || $photoId <= 0) {
            jsonResponse(false, 'Valid venue ID and photo ID required', null, 400);
        }

        $venueDb = getVenueDBConnection($venueId);
        $venueDb->prepare("UPDATE photos SET is_cover = 0 WHERE venue_id = ?")->execute([$venueId]);
        $stmtUp = $venueDb->prepare("UPDATE photos SET is_cover = 1 WHERE id = ? AND venue_id = ?");
        $stmtUp->execute([$photoId, $venueId]);

        $coverUrl = "api/venue-photo.php?venue_id={$venueId}&cover=1";
        $db->prepare("UPDATE venues SET image_url = ? WHERE id = ?")->execute([$coverUrl, $venueId]);

        jsonResponse(true, 'Venue cover image updated successfully', [
            'image_url' => $coverUrl,
            'photo_url' => "api/venue-photo.php?venue_id={$venueId}&id={$photoId}"
        ]);
        break;

    default:
        jsonResponse(false, 'Invalid venue action', null, 400);
        break;
}
