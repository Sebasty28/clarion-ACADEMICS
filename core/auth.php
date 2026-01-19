<?php
// core/auth.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';


function auth_user(): ?array {
    start_session();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return auth_user() !== null;
}

function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please login to continue.');
        redirect('/auth/login.php');
    }
}

function require_role(array $roles): void {
    $isAdminRole = in_array('SUPER_ADMIN', $roles, true);
    
    if (!is_logged_in()) {
        set_flash('error', 'Please login to continue.');
        if ($isAdminRole) {
            redirect('/auth/admin_login.php');
        } else {
            redirect('/auth/login.php');
        }
    }
    
    $user = auth_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo "403 Forbidden - Access denied.";
        exit;
    }
}

function login_user(array $userRow): void {
    start_session();
    session_regenerate_id(true);

    // Only store what you need in session
    $_SESSION['user'] = [
        'id'         => (int)$userRow['id'],
        'role'       => $userRow['role'],
        'first_name' => $userRow['first_name'],
        'last_name'  => $userRow['last_name'],
        'email'      => $userRow['email'],
    ];
}

function logout_user(): void {
    start_session();
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

function role_home(string $role): string {
    return match ($role) {
        'SUPER_ADMIN' => '/admin/super_dashboard.php',
        'EDUCATOR'    => '/educator/dashboard.php',
        default       => '/student/feed.php',
    };
}

function full_name(array $u): string {
    return trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
}
