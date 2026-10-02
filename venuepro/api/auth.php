<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = getDBConnection();

switch ($action) {
    case 'login':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $email = strtolower(sanitize($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $expectedRole = strtolower(sanitize($input['role'] ?? ''));

        if (empty($email) || empty($password)) {
            jsonResponse(false, 'Please provide both email and password.', null, 400);
        }

        $stmt = $db->prepare("
            SELECT u.* FROM users u 
            LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
            WHERE (LOWER(u.email) = ? OR LOWER(sp.staff_code) = ?) 
              AND u.status = 'active' 
            LIMIT 1
        ");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(false, 'Invalid credentials. Please verify your email and password.', null, 401);
        }

        // If specific role requested for role login cards
        if (!empty($expectedRole) && strtolower($user['role']) !== $expectedRole) {
            jsonResponse(false, "This portal is reserved for " . ucfirst($expectedRole) . " accounts. Your account is registered as " . ucfirst($user['role']) . ".", null, 403);
        }

        loginUser($user);

        // Redirect targets
        $roleRoutes = [
            'customer' => '../customer/customer-dashboard.php',
            'caterer'  => '../caterer/caterer-dashboard.php',
            'staff'    => '../staff/staff-dashboard.php',
            'admin'    => '../admin/admin-dashboard.php'
        ];
        $redirect = $roleRoutes[strtolower($user['role'])] ?? '../index.php';

        jsonResponse(true, 'Sign in successful!', [
            'user'     => getCurrentUser(),
            'redirect' => $redirect
        ]);
        break;

    case 'register':
        $input = !empty($_POST) ? $_POST : getJsonInput();
        $name = sanitize($input['name'] ?? ($input['fullname'] ?? ''));
        $email = strtolower(sanitize($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        $role = strtolower(sanitize($input['role'] ?? 'customer'));
        $phone = sanitize($input['phone'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            jsonResponse(false, 'All required fields must be completed.', null, 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Please provide a valid email address.', null, 400);
        }

        if (strlen($password) < 6) {
            jsonResponse(false, 'Password must be at least 6 characters.', null, 400);
        }

        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'An account with this email address already exists.', null, 409);
        }

        // Validate admin access code if admin role
        if ($role === 'admin') {
            $accessCode = sanitize($input['access_code'] ?? ($input['admin_code'] ?? ''));
            if ($accessCode !== 'VENUEPRO2026') {
                jsonResponse(false, 'Invalid Administrator Access Code.', null, 403);
            }
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $avatarText = strtoupper(substr($name, 0, 2));
        $avatarColors = ['#2563eb', '#059669', '#0284c7', '#4f46e5', '#7c3aed', '#0d9488'];
        $avatarBg = $avatarColors[array_rand($avatarColors)];

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, role, phone, avatar_text, avatar_bg, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $email, $passwordHash, $role, $phone, $avatarText, $avatarBg]);
            $userId = (int)$db->lastInsertId();

            // Handle Caterer profile
            if ($role === 'caterer') {
                $bizName = sanitize($input['business_name'] ?? "$name Catering Co.");
                $kitchenAddr = sanitize($input['kitchen_address'] ?? "Kitchen Facility, Metropolis");
                $specialty = sanitize($input['specialization'] ?? "Fine Dining & Events");
                $stmtCaterer = $db->prepare("INSERT INTO caterer_profiles (user_id, business_name, owner_name, kitchen_address, specialization, approval_status) VALUES (?, ?, ?, ?, ?, 'approved')");
                $stmtCaterer->execute([$userId, $bizName, $name, $kitchenAddr, $specialty]);
            }

            // Handle Staff profile
            if ($role === 'staff') {
                $staffCode = sanitize($input['staff_id'] ?? 'STF-' . rand(1000, 9999));
                $dept = sanitize($input['department'] ?? 'Event Operations');
                $stmtStaff = $db->prepare("INSERT INTO staff_profiles (user_id, staff_code, department, active_status) VALUES (?, ?, ?, 'active')");
                $stmtStaff->execute([$userId, $staffCode, $dept]);
            }

            $db->commit();

            // Auto-login
            $newUser = [
                'id'          => $userId,
                'name'        => $name,
                'email'       => $email,
                'role'        => $role,
                'avatar_text' => $avatarText,
                'avatar_bg'   => $avatarBg
            ];
            loginUser($newUser);

            $roleRoutes = [
                'customer' => '../customer/customer-dashboard.php',
                'caterer'  => '../caterer/caterer-dashboard.php',
                'staff'    => '../staff/staff-dashboard.php',
                'admin'    => '../admin/admin-dashboard.php'
            ];
            $redirect = $roleRoutes[$role] ?? '../registration-successful.php';

            jsonResponse(true, 'Registration successful! Welcome to VenuePro.', [
                'user'     => getCurrentUser(),
                'redirect' => $redirect
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Registration error: " . $e->getMessage());
            jsonResponse(false, 'Registration failed due to a server error. Please try again.', null, 500);
        }
        break;

    case 'logout':
        logoutUser();
        jsonResponse(true, 'Successfully logged out.', ['redirect' => '../login-role.php']);
        break;

    case 'me':
        if (!isLoggedIn()) {
            jsonResponse(false, 'Not authenticated', null, 401);
        }
        jsonResponse(true, 'Authenticated', ['user' => getCurrentUser()]);
        break;

    case 'session':
        jsonResponse(true, 'Session status', [
            'logged_in' => isLoggedIn(),
            'user'      => isLoggedIn() ? getCurrentUser() : null
        ]);
        break;

    default:
        jsonResponse(false, 'Unknown auth action requested.', null, 400);
        break;
}
