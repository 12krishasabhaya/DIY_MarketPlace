<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow for authenticated users (avoid email enumeration for guests)
$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$email = trim((string)($_GET['email'] ?? ''));
if ($email === '') {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$id = $stmt->fetchColumn();

echo json_encode(['user_id' => $id ? (int)$id : null]);

