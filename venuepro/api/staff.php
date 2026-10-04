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
        $sql = "SELECT sa.*, b.booking_code, b.event_name, b.event_date, b.start_time, b.end_time, b.guest_count, v.name AS venue_name, v.district AS venue_district, u.name AS customer_name, u.phone AS customer_phone FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id JOIN venues v ON b.venue_id = v.id JOIN users u ON b.customer_id = u.id";
        $params = [];

        if ($user['role'] === 'staff') {
            $sql .= " WHERE sa.staff_id = ?";
            $params[] = $user['id'];
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
        requireRole(['staff', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $checklist = isset($input['checklist']) && is_array($input['checklist']) ? json_encode($input['checklist']) : null;
        $status = sanitize($input['setup_status'] ?? 'in_progress');

        if ($assignmentId <= 0) {
            jsonResponse(false, 'Invalid assignment ID', null, 400);
        }

        $stmt = $db->prepare("UPDATE staff_assignments SET checklist_json = COALESCE(?, checklist_json), setup_status = ? WHERE id = ?");
        $stmt->execute([$checklist, $status, $assignmentId]);

        jsonResponse(true, 'Setup checklist updated successfully!');
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
        $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'staff'")->execute([$staffId]);
        $db->prepare("UPDATE staff_profiles SET active_status = 'inactive' WHERE user_id = ?")->execute([$staffId]);
        jsonResponse(true, 'Staff member removed successfully.');
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
        requireRole(['staff', 'admin']);
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $assignmentId = (int)($input['assignment_id'] ?? 0);
        $completionPct = min(100, max(0, (int)($input['completion_pct'] ?? 0)));
        $venueName = sanitize($input['venue_name'] ?? '');
        $staffName = sanitize($input['staff_name'] ?? '');
        $checklist = isset($input['checklist']) ? json_encode($input['checklist']) : null;

        if ($assignmentId <= 0) {
            jsonResponse(false, 'Invalid assignment ID', null, 400);
        }

        $stmt = $db->prepare(
            "UPDATE staff_assignments SET
             setup_status = 'completed',
             completion_pct = ?,
             reported_venue = ?,
             reported_staff = ?,
             checklist_json = COALESCE(?, checklist_json),
             completed_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$completionPct, $venueName, $staffName, $checklist, $assignmentId]);
        jsonResponse(true, 'Report submitted successfully!', ['completion_pct' => $completionPct]);
        break;

    default:
        jsonResponse(false, 'Invalid staff action', null, 400);
        break;
}
