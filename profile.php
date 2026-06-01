<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/csrf.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/db.php';

$user = auth_require_login();

if (!is_post()) {
    redirect('dashboard.php');
}

csrf_verify();
$pdo = db();

$name = trim((string)($_POST['name'] ?? ''));
if ($name !== '') {
    $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$name, (int)$user['id']]);
}

$current = (string)($_POST['current_password'] ?? '');
$new = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if ($new !== '' || $confirm !== '' || $current !== '') {
    if (strlen($new) < 6) {
        flash_set('error', 'New password must be at least 6 characters.');
        redirect('dashboard.php');
    }
    if ($new !== $confirm) {
        flash_set('error', 'New password and confirmation do not match.');
        redirect('dashboard.php');
    }
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$user['id']]);
    $hash = (string)($stmt->fetchColumn() ?? '');
    if ($hash === '' || !password_verify($current, $hash)) {
        flash_set('error', 'Current password is incorrect.');
        redirect('dashboard.php');
    }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    if ($newHash === false) {
        flash_set('error', 'Could not update password.');
        redirect('dashboard.php');
    }
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int)$user['id']]);
}

flash_set('success', 'Profile updated.');
redirect('dashboard.php');

