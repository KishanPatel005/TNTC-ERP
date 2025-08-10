<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function auth_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

function auth_require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST[CSRF_TOKEN_KEY] ?? '';
        if (!$token || !hash_equals($_SESSION[CSRF_TOKEN_KEY] ?? '', $token)) {
            http_response_code(400);
            die('Invalid CSRF token');
        }
    }
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function has_role(string $role): bool {
    $u = current_user();
    if (!$u) return false;
    return strtolower($u['role_name'] ?? '') === strtolower($role);
}

function require_role(array $roles): void {
    $u = current_user();
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    $role = strtolower($u['role_name'] ?? '');
    $roles = array_map('strtolower', $roles);
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        die('Access denied');
    }
}

function attempt_login(string $email, string $password): bool {
    $stmt = db_query(
        'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.status = "active" LIMIT 1',
        's', [ $email ]
    );
    $user = db_fetch_one($stmt);
    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => (int)$user['role_id'],
            'role_name' => $user['role_name'],
        ];
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
