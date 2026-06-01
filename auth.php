<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function auth_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Harden session cookies (best effort across PHP versions)
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $params = session_get_cookie_params();
        $cookie = [
            'lifetime' => 0,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookie);
        } else {
            session_set_cookie_params(
                $cookie['lifetime'],
                ($cookie['path'] . '; samesite=' . $cookie['samesite']),
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }
        session_start();
    }
}

function auth_user(): ?array {
    auth_start();
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return null;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$userId]);
    $user = $stmt->fetch();
    if (!$user) return null;
    if (($user['status'] ?? '') !== 'active') return null;
    return $user;
}

function auth_require_login(): array {
    $user = auth_user();
    if (!$user) {
        flash_set('error', 'Please login to continue.');
        redirect('login.php');
    }
    return $user;
}

function auth_require_admin(): array {
    $user = auth_require_login();
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        die('Forbidden');
    }
    return $user;
}

function auth_login(string $email, string $password): bool {
    auth_start();
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (($row['status'] ?? '') !== 'active') return false;

    $hash = (string)$row['password_hash'];
    if (!password_verify($password, $hash)) {
        return false;
    }

    $_SESSION['user_id'] = (int)$row['id'];
    return true;
}

function auth_logout(): void {
    auth_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

