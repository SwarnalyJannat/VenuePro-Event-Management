<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'contacts';
$db = getDBConnection();
$currentUser = requireLogin();

switch ($action) {
    case 'contacts':
        // Rule: Customers can only contact assigned Venue Staff and vice versa!
        if ($currentUser['role'] === 'customer') {
            // Find staff assigned to customer's bookings
            $stmt = $db->prepare("SELECT DISTINCT u.id, u.name, u.role, u.avatar_text, u.avatar_bg, sa.role_title, v.name AS venue_name FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id JOIN users u ON sa.staff_id = u.id JOIN venues v ON b.venue_id = v.id WHERE b.customer_id = ?");
            $stmt->execute([$currentUser['id']]);
            $contacts = $stmt->fetchAll();

            // If no assignments yet, show default staff members
            if (empty($contacts)) {
                $stmtDef = $db->query("SELECT id, name, role, avatar_text, avatar_bg, 'Lead Coordinator' AS role_title, 'Grand Emerald' AS venue_name FROM users WHERE role = 'staff'");
                $contacts = $stmtDef->fetchAll();
            }
        } else if ($currentUser['role'] === 'staff') {
            // Find customers who booked events where this staff is assigned
            $stmt = $db->prepare("SELECT DISTINCT u.id, u.name, u.role, u.avatar_text, u.avatar_bg, b.event_name AS role_title, v.name AS venue_name FROM staff_assignments sa JOIN bookings b ON sa.booking_id = b.id JOIN users u ON b.customer_id = u.id JOIN venues v ON b.venue_id = v.id WHERE sa.staff_id = ?");
            $stmt->execute([$currentUser['id']]);
            $contacts = $stmt->fetchAll();

            if (empty($contacts)) {
                $stmtCust = $db->query("SELECT id, name, role, avatar_text, avatar_bg, 'Active Client' AS role_title, 'General Inquiries' AS venue_name FROM users WHERE role = 'customer'");
                $contacts = $stmtCust->fetchAll();
            }
        } else {
            // Admin can contact all
            $stmt = $db->query("SELECT id, name, role, avatar_text, avatar_bg, role AS role_title, '' AS venue_name FROM users WHERE id != " . (int)$currentUser['id']);
            $contacts = $stmt->fetchAll();
        }

        jsonResponse(true, 'Contacts retrieved', ['contacts' => $contacts]);
        break;

    case 'messages':
        $receiverId = (int)($_GET['receiver_id'] ?? 0);
        if ($receiverId <= 0) {
            jsonResponse(false, 'Receiver ID is required', null, 400);
        }

        // Fetch conversation history
        $stmt = $db->prepare("SELECT cm.*, sender.name AS sender_name, sender.avatar_text AS sender_avatar, receiver.name AS receiver_name FROM chat_messages cm JOIN users sender ON cm.sender_id = sender.id JOIN users receiver ON cm.receiver_id = receiver.id WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) ORDER BY cm.created_at ASC");
        $stmt->execute([$currentUser['id'], $receiverId, $receiverId, $currentUser['id']]);
        $messages = $stmt->fetchAll();

        // Mark incoming messages as read
        $stmtRead = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmtRead->execute([$receiverId, $currentUser['id']]);

        jsonResponse(true, 'Messages retrieved', ['messages' => $messages]);
        break;

    case 'send':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $receiverId = (int)($input['receiver_id'] ?? 0);
        $message = sanitize($input['message'] ?? '');

        if ($receiverId <= 0 || empty($message)) {
            jsonResponse(false, 'Receiver ID and message content are required', null, 400);
        }

        $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, is_read) VALUES (?, ?, ?, 0)");
        $stmt->execute([$currentUser['id'], $receiverId, $message]);
        $msgId = $db->lastInsertId();

        jsonResponse(true, 'Message sent successfully', [
            'message_id' => $msgId,
            'message'    => $message,
            'sender_id'  => $currentUser['id'],
            'created_at' => date('Y-m-d H:i:s')
        ], 201);
        break;

    default:
        jsonResponse(false, 'Invalid chat action', null, 400);
        break;
}
