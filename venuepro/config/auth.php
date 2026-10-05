<?php
/**
 * VenuePro Authentication & Session Management
 */

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';

/**
 * Check if a user is authenticated.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently authenticated user's session record.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'          => $_SESSION['user_id'] ?? null,
        'name'        => $_SESSION['user_name'] ?? 'User',
        'email'       => $_SESSION['user_email'] ?? '',
        'role'        => $_SESSION['user_role'] ?? 'customer',
        'avatar_text' => $_SESSION['avatar_text'] ?? 'U',
        'avatar_bg'   => $_SESSION['avatar_bg'] ?? '#2563eb'
    ];
}

/**
 * Enforce authentication. Redirects or outputs JSON 401 if unauthenticated.
 */
function requireLogin(?string $redirectUrl = null): array {
    if (!isLoggedIn()) {
        $isApi = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false ||
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Authentication required.', 'redirect' => $redirectUrl ?: '../login-role.php']);
            exit;
        }
        $target = $redirectUrl ?: (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false ? '../admin/admin-login.php' :
                                  (strpos($_SERVER['REQUEST_URI'] ?? '', '/caterer/') !== false ? '../caterer/caterer-login.php' :
                                  (strpos($_SERVER['REQUEST_URI'] ?? '', '/staff/') !== false ? '../staff/staff-login.php' : '../customer/customer-login.php')));
        header("Location: $target");
        exit;
    }

    // Verify user exists and is active in database to ensure session validity and prevent stale profiles
    try {
        $db = getDBConnection();
        $stmtUser = $db->prepare("SELECT id, name, email, role, phone, avatar_text, avatar_bg, status FROM users WHERE id = ? LIMIT 1");
        $stmtUser->execute([(int)$_SESSION['user_id']]);
        $activeUser = $stmtUser->fetch();
        if (!$activeUser || $activeUser['status'] !== 'active') {
            logoutUser();
            $target = $redirectUrl ?: '../login-role.php';
            header("Location: $target");
            exit;
        }

        // Keep session data fresh in sync with database
        $_SESSION['user_name']   = $activeUser['name'];
        $_SESSION['user_email']  = $activeUser['email'];
        $_SESSION['user_role']   = strtolower($activeUser['role']);
        $_SESSION['avatar_text'] = $activeUser['avatar_text'] ?: strtoupper(substr($activeUser['name'], 0, 2));
        $_SESSION['avatar_bg']   = $activeUser['avatar_bg'] ?: '#2563eb';
    } catch (Exception $e) {
        // Fall back to session if DB lookup fails
    }

    return getCurrentUser();
}

/**
 * Enforce role-based access control.
 *
 * @param string|array $allowedRoles
 */
function requireRole($allowedRoles, ?string $redirectUrl = null): array {
    $user = requireLogin($redirectUrl);
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    if (!in_array(strtolower($user['role']), array_map('strtolower', $roles))) {
        $isApi = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false;
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied: insufficient permissions.']);
            exit;
        }
        // Redirect to their own dashboard
        $roleRoutes = [
            'customer' => '../customer/customer-dashboard.php',
            'caterer'  => '../caterer/caterer-dashboard.php',
            'staff'    => '../staff/staff-dashboard.php',
            'admin'    => '../admin/admin-dashboard.php'
        ];
        $dest = $roleRoutes[strtolower($user['role'])] ?? '../index.php';
        header("Location: $dest");
        exit;
    }
    return $user;
}

/**
 * Log in a user and regenerate session ID to prevent fixation.
 */
function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']     = (int)$user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = strtolower($user['role']);
    $_SESSION['avatar_text'] = $user['avatar_text'] ?: strtoupper(substr($user['name'], 0, 2));
    $_SESSION['avatar_bg']   = $user['avatar_bg'] ?: '#2563eb';
}

/**
 * Log out user and destroy session.
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * CSRF Token Generation & Validation
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
