<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'my_assignments';
$db = getDBConnection();

switch ($action) {
    case 'my_assignments':
        $user = requireRole(['staff', 'admin']);
        $sql = "SELECT sa.*, b.venue_id, b.booking_code, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, v.name AS venue_name, v.district AS venue_district, u.name AS customer_name, u.phone AS customer_phone 
                FROM staff_assignments sa 
                JOIN bookings b ON sa.booking_id = b.id 
                JOIN venues v ON b.venue_id = v.id 
                JOIN users u ON b.customer_id = u.id 
                WHERE b.booking_status IN ('confirmed', 'completed')";
        $params = [];

        if ($user['role'] === 'staff') {
            $stmtProf = $db->prepare("SELECT assigned_venues FROM staff_profiles WHERE user_id = ? LIMIT 1");
            $stmtProf->execute([$user['id']]);
            $prof = $stmtProf->fetch();
            $assignedVenueIds = [];
            if (!empty($prof['assigned_venues'])) {
                foreach (explode(',', $prof['assigned_venues']) as $vId) {
                    $vId = (int)trim($vId);
                    if ($vId > 0) $assignedVenueIds[] = $vId;
                }
                $assignedVenueIds = array_values(array_unique($assignedVenueIds));
            }

            if (empty($assignedVenueIds)) {
                jsonResponse(true, 'Staff assignments retrieved (none assigned)', ['assignments' => []]);
                break;
            }

            $inPlaceholders = implode(',', array_fill(0, count($assignedVenueIds), '?'));
            $sql .= " AND b.venue_id IN ($inPlaceholders)";
            $params = $assignedVenueIds;
        }

        $sql .= " ORDER BY b.event_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        foreach ($assignments as &$asgn) {
            $asgn['checklist'] = !empty($asgn['checklist_json']) ? json_decode($asgn['checklist_json'], true) : [];
        }

        jsonResponse(true, 'Staff assignments retrieved', ['assignments' => $assignments]);
        break;

    case 'update_checklist':
        $user = requireRole(['staff', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $checklist = isset($input['checklist']) ? (is_string($input['checklist']) ? $input['checklist'] : json_encode($input['checklist'])) : null;
        $status = sanitize($input['setup_status'] ?? 'in_progress');
        $completionPct = isset($input['completion_pct']) ? min(100, max(0, (int)$input['completion_pct'])) : null;

        if ($assignmentId <= 0) {
            jsonResponse(false, 'Invalid assignment ID', null, 400);
        }

        if ($user['role'] === 'staff') {
            $stmtChk = $db->prepare("SELECT b.venue_id FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id WHERE sa.id = ? LIMIT 1");
            $stmtChk->execute([$assignmentId]);
            $venueId = (int)$stmtChk->fetchColumn();

            $stmtProf = $db->prepare("SELECT assigned_venues FROM staff_profiles WHERE user_id = ? LIMIT 1");
            $stmtProf->execute([$user['id']]);
            $prof = $stmtProf->fetch();
            $allowedVenueIds = [];
            if (!empty($prof['assigned_venues'])) {
                foreach (explode(',', $prof['assigned_venues']) as $vId) {
                    $vId = (int)trim($vId);
                    if ($vId > 0) $allowedVenueIds[] = $vId;
                }
            }
            if (!in_array($venueId, $allowedVenueIds)) {
                jsonResponse(false, 'Unauthorized: This event is not at one of your assigned operational venues.', null, 403);
            }
        }

        if ($completionPct !== null) {
            $stmt = $db->prepare("UPDATE staff_assignments SET checklist_json = COALESCE(?, checklist_json), setup_status = ?, completion_pct = ?, completed_at = IF(? = 100, NOW(), completed_at) WHERE id = ?");
            $stmt->execute([$checklist, $status, $completionPct, $completionPct, $assignmentId]);
        } else {
            $stmt = $db->prepare("UPDATE staff_assignments SET checklist_json = COALESCE(?, checklist_json), setup_status = ? WHERE id = ?");
            $stmt->execute([$checklist, $status, $assignmentId]);
        }

        if ($completionPct === 100 || $status === 'completed') {
            try {
                $stmtBk = $db->prepare("SELECT booking_id FROM staff_assignments WHERE id = ? LIMIT 1");
                $stmtBk->execute([$assignmentId]);
                $bId = $stmtBk->fetchColumn();
                if ($bId) {
                    $db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE id = ? AND booking_status IN ('confirmed','pending')")->execute([$bId]);
                }
            } catch (Exception $e) {}
        }

        jsonResponse(true, 'Setup checklist updated successfully!');
        break;

    case 'create':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $name = sanitize($input['name'] ?? '');
        $email = strtolower(sanitize($input['email'] ?? ''));
        $phone = sanitize($input['phone'] ?? '');
        $department = sanitize($input['department'] ?? 'Event Operations');
        $password = $input['password'] ?? 'password123';
        $staffCode = sanitize($input['staff_code'] ?? ($input['staff_id'] ?? 'STF-' . rand(1000, 9999)));
        $venueInput = $input['assigned_venues'] ?? [];
        if (is_array($venueInput)) {
            $assignedVenuesStr = implode(',', array_map('intval', $venueInput));
        } else {
            $assignedVenuesStr = sanitize((string)$venueInput);
        }

        if (empty($name) || empty($email)) {
            jsonResponse(false, 'Full legal name and corporate email are required.', null, 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Please provide a valid email address.', null, 400);
        }
        if (strlen($password) < 6) {
            jsonResponse(false, 'Password must be at least 6 characters.', null, 400);
        }

        // Check if user already exists
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        if ($stmtCheck->fetch()) {
            jsonResponse(false, 'An account with this email address already exists.', null, 409);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $avatarText = strtoupper(substr($name, 0, 2));
        $avatarColors = ['#2563eb', '#059669', '#0284c7', '#4f46e5', '#7c3aed', '#0d9488'];
        $avatarBg = $avatarColors[array_rand($avatarColors)];

        $db->beginTransaction();
        try {
            $stmtUser = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone, avatar_text, avatar_bg, status) VALUES (?, ?, ?, 'staff', ?, ?, ?, 'active')");
            $stmtUser->execute([$name, $email, $passwordHash, $phone, $avatarText, $avatarBg]);
            $newUserId = (int)$db->lastInsertId();

            $stmtProf = $db->prepare("INSERT INTO staff_profiles (user_id, staff_code, department, assigned_venues, active_status) VALUES (?, ?, ?, ?, 'active')");
            $stmtProf->execute([$newUserId, $staffCode, $department, $assignedVenuesStr]);

            $db->commit();
            jsonResponse(true, 'Staff member added successfully!', [
                'user_id' => $newUserId,
                'staff_code' => $staffCode
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(false, 'Failed to add staff member: ' . $e->getMessage(), null, 500);
        }
        break;

    case 'update':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $staffId = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($staffId <= 0) {
            jsonResponse(false, 'Valid staff ID is required', null, 400);
        }

        $name = sanitize($input['name'] ?? '');
        $email = sanitize($input['email'] ?? '');
        $phone = sanitize($input['phone'] ?? '');
        $staffCode = sanitize($input['staff_code'] ?? '');
        $department = sanitize($input['department'] ?? 'Event Operations');
        $assignedVenues = sanitize($input['assigned_venues'] ?? '');
        $status = sanitize($input['status'] ?? 'active');

        if (empty($name) || empty($email)) {
            jsonResponse(false, 'Name and Email are required', null, 400);
        }

        // Check if email belongs to another user
        $stmtC = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmtC->execute([$email, $staffId]);
        if ($stmtC->fetch()) {
            jsonResponse(false, 'This email is already in use by another account', null, 409);
        }

        $stmtUser = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ? AND role = 'staff'");
        $stmtUser->execute([$name, $email, $phone, $status, $staffId]);

        $activeStatus = ($status === 'active') ? 'active' : 'inactive';
        $stmtProf = $db->prepare("UPDATE staff_profiles SET staff_code = ?, department = ?, assigned_venues = ?, active_status = ? WHERE user_id = ?");
        $stmtProf->execute([$staffCode, $department, $assignedVenues, $activeStatus, $staffId]);

        jsonResponse(true, 'Staff member details updated successfully!');
        break;

    case 'delete':
    requireRole('admin');

    $input = !empty($_POST) ? $_POST : getJsonInput();
    $staffId = (int)($input['id'] ?? ($_GET['id'] ?? 0));

    if ($staffId <= 0) {
        jsonResponse(false, 'Valid staff ID is required', null, 400);
    }

    try {
        $db->beginTransaction();

        // Make sure the ID belongs to a staff member
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'staff' LIMIT 1");
        $stmt->execute([$staffId]);

        if (!$stmt->fetch()) {
            $db->rollBack();
            jsonResponse(false, 'Staff member not found.', null, 404);
        }

        // Delete staff assignments first
        $stmt = $db->prepare("DELETE FROM staff_assignments WHERE staff_id = ?");
        $stmt->execute([$staffId]);

        // Delete staff profile
        $stmt = $db->prepare("DELETE FROM staff_profiles WHERE user_id = ?");
        $stmt->execute([$staffId]);

        // Finally delete the staff account
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$staffId]);

        $db->commit();

        jsonResponse(true, 'Staff member removed successfully.');

    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        jsonResponse(false, 'Unable to remove staff member from the database.', null, 500);
    }

    break;

    case 'assign_venues':
        requireRole('admin');
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $userId = (int)($input['user_id'] ?? 0);
        $venueIds = isset($input['venue_ids']) && is_array($input['venue_ids']) ? $input['venue_ids'] : [];
        if ($userId <= 0) {
            jsonResponse(false, 'Valid user ID is required', null, 400);
        }
        // Store venue IDs as comma-separated string in assigned_venues column
        $venueStr = implode(',', array_map('intval', $venueIds));
        $db->prepare("UPDATE staff_profiles SET assigned_venues = ? WHERE user_id = ?")->execute([$venueStr, $userId]);
        jsonResponse(true, 'Venue assignments updated successfully.');
        break;

    case 'complete_setup':
        requireRole(['staff', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $checklist = isset($input['checklist']) && is_array($input['checklist']) ? json_encode($input['checklist']) : null;
        if ($assignmentId <= 0) {
            jsonResponse(false, 'Invalid assignment ID', null, 400);
        }
        $stmt = $db->prepare("UPDATE staff_assignments SET checklist_json = COALESCE(?, checklist_json), setup_status = 'completed' WHERE id = ?");
        $stmt->execute([$checklist, $assignmentId]);
        jsonResponse(true, 'Setup marked complete!');
        break;

    case 'submit_report':
        $user = requireRole(['staff', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $completionPct = 100;
        $venueName = sanitize($input['venue_name'] ?? '');
        $staffName = sanitize($input['staff_name'] ?? '');

        if ($assignmentId <= 0) {
            jsonResponse(false, 'Invalid assignment ID', null, 400);
        }

        if ($user['role'] === 'staff') {
            $stmtChk = $db->prepare("SELECT b.venue_id FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id WHERE sa.id = ? LIMIT 1");
            $stmtChk->execute([$assignmentId]);
            $venueId = (int)$stmtChk->fetchColumn();

            $stmtProf = $db->prepare("SELECT assigned_venues FROM staff_profiles WHERE user_id = ? LIMIT 1");
            $stmtProf->execute([$user['id']]);
            $prof = $stmtProf->fetch();
            $allowedVenueIds = [];
            if (!empty($prof['assigned_venues'])) {
                foreach (explode(',', $prof['assigned_venues']) as $vId) {
                    $vId = (int)trim($vId);
                    if ($vId > 0) $allowedVenueIds[] = $vId;
                }
            }
            if (!in_array($venueId, $allowedVenueIds)) {
                jsonResponse(false, 'Unauthorized: This event is not at one of your assigned operational venues.', null, 403);
            }
        }
        
        $checklistArr = isset($input['checklist']) && is_array($input['checklist']) ? $input['checklist'] : [];
        if (empty($checklistArr)) {
            $checklistArr = [];
            for ($ti = 0; $ti < 10; $ti++) {
                $checklistArr['task-' . $ti] = true;
            }
        } else {
            // Confirming completion confirms all checklist items
            foreach ($checklistArr as $k => $v) {
                $checklistArr[$k] = true;
            }
        }
        $checklist = json_encode($checklistArr);

        $stmt = $db->prepare(
            "UPDATE staff_assignments SET
             setup_status = 'completed',
             completion_pct = 100,
             reported_venue = ?,
             reported_staff = ?,
             checklist_json = ?,
             completed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$venueName, $staffName, $checklist, $assignmentId]);

        // Sync with bookings and notify customer
        try {
            $stmtBk = $db->prepare("SELECT sa.booking_id, b.customer_id, b.event_name, v.name AS venue_name FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id JOIN venues v ON b.venue_id = v.id WHERE sa.id = ? LIMIT 1");
            $stmtBk->execute([$assignmentId]);
            $bkInfo = $stmtBk->fetch();

            if ($bkInfo) {
                $stmtUpdBk = $db->prepare("UPDATE bookings SET booking_status = 'completed' WHERE id = ?");
                $stmtUpdBk->execute([$bkInfo['booking_id']]);

                $stmtNotif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link_url, is_read) VALUES (?, ?, ?, 'booking', ?, 0)");
                $stmtNotif->execute([
                    $bkInfo['customer_id'],
                    'Setup Verified: ' . ($venueName ?: $bkInfo['venue_name']),
                    'Event setup checklists and venue staging are 100% verified by venue staff for ' . $bkInfo['event_name'] . '.',
                    'customer/customer-live-progress.php?booking_id=' . $bkInfo['booking_id']
                ]);
            }
        } catch (Exception $e) {
            // Non-critical notification logging
        }

        jsonResponse(true, 'Report submitted successfully!', [
            'assignment_id'  => $assignmentId,
            'completion_pct' => 100,
            'setup_status'   => 'completed'
        ]);
        break;

    default:
        jsonResponse(false, 'Invalid staff action', null, 400);
        break;
}
