<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Profile API: strictly self-service for currently authenticated user
$currentUser = requireLogin();
$userId = (int)$currentUser['id'];
$db = getDBConnection();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

switch ($action) {
    case 'get':
        $stmt = $db->prepare("SELECT id, name, email, phone, role, avatar_text, avatar_bg, status, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        if (!$profile) {
            jsonResponse(false, 'User record not found.', null, 404);
        }

        // Additional stats depending on role
        $extra = [];
        if ($profile['role'] === 'customer') {
            $stmtBk = $db->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_amount), 0) AS total_spent FROM bookings WHERE customer_id = ?");
            $stmtBk->execute([$userId]);
            $extra = $stmtBk->fetch() ?: [];
        }

        jsonResponse(true, 'Profile retrieved', [
            'profile' => $profile,
            'extra'   => $extra
        ]);
        break;

    case 'update':
        $input = !empty($_POST) ? $_POST : getJsonInput();

        $name = sanitize($input['name'] ?? '');
        $email = strtolower(sanitize($input['email'] ?? ''));
        $phone = sanitize($input['phone'] ?? '');
        $avatarText = strtoupper(sanitize($input['avatar_text'] ?? ''));
        $avatarBg = sanitize($input['avatar_bg'] ?? '');

        // Password change fields (optional)
        $currentPassword = $input['current_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';
        $confirmPassword = $input['confirm_password'] ?? '';

        if (empty($name)) {
            jsonResponse(false, 'Full name cannot be empty.', null, 400);
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'A valid email address is required.', null, 400);
        }

        // Verify email uniqueness against other users
        $stmtEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmtEmail->execute([$email, $userId]);
        if ($stmtEmail->fetch()) {
            jsonResponse(false, 'This email address is already in use by another account.', null, 409);
        }

        // If avatar text not provided, generate from initials
        if (empty($avatarText)) {
            $avatarText = strtoupper(substr($name, 0, 2));
        } else {
            $avatarText = substr($avatarText, 0, 4);
        }

        if (empty($avatarBg) || !preg_match('/^#[a-f0-9]{6}$/i', $avatarBg)) {
            $avatarBg = $currentUser['avatar_bg'] ?? '#2563eb';
        }

        // Fetch current user row for verification
        $stmtCurr = $db->prepare("SELECT id, password_hash FROM users WHERE id = ? LIMIT 1");
        $stmtCurr->execute([$userId]);
        $currUserRow = $stmtCurr->fetch();
        if (!$currUserRow) {
            jsonResponse(false, 'Your user profile could not be found. Please log in again.', null, 404);
        }

        $updatePassword = false;
        $newPasswordHash = null;

        if (!empty($newPassword) || !empty($currentPassword)) {
            if (empty($currentPassword)) {
                jsonResponse(false, 'Please provide your current password to authorize password change.', null, 400);
            }
            if (!$currUserRow || !password_verify($currentPassword, $currUserRow['password_hash'])) {
                jsonResponse(false, 'Current password verification failed. Please check your password.', null, 403);
            }
            if (strlen($newPassword) < 6) {
                jsonResponse(false, 'New password must be at least 6 characters.', null, 400);
            }
            if ($newPassword !== $confirmPassword) {
                jsonResponse(false, 'New password and confirmation do not match.', null, 400);
            }

            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $updatePassword = true;
        }

        $db->beginTransaction();
        try {
            if ($updatePassword) {
                $stmtUp = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar_text = ?, avatar_bg = ?, password_hash = ? WHERE id = ?");
                $stmtUp->execute([$name, $email, $phone, $avatarText, $avatarBg, $newPasswordHash, $userId]);
            } else {
                $stmtUp = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, avatar_text = ?, avatar_bg = ? WHERE id = ?");
                $stmtUp->execute([$name, $email, $phone, $avatarText, $avatarBg, $userId]);
            }

            $db->commit();

            // Synchronize active session variables immediately
            $_SESSION['user_name']   = $name;
            $_SESSION['user_email']  = $email;
            $_SESSION['avatar_text'] = $avatarText;
            $_SESSION['avatar_bg']   = $avatarBg;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            jsonResponse(true, 'Profile updated successfully!', [
                'user' => [
                    'id'          => $userId,
                    'name'        => $name,
                    'email'       => $email,
                    'phone'       => $phone,
                    'avatar_text' => $avatarText,
                    'avatar_bg'   => $avatarBg,
                    'role'        => $currentUser['role']
                ]
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Profile update failure: " . $e->getMessage());
            jsonResponse(false, 'Failed to update profile: ' . $e->getMessage(), null, 500);
        }
        break;

    default:
        jsonResponse(false, 'Unknown profile action.', null, 400);
        break;
}
