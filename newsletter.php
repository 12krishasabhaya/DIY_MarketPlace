<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

csrf_verify();

$email = trim((string)($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_email']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscriptions (email) VALUES (?)');
    $stmt->execute([$email]);
    echo json_encode(['ok' => true, 'status' => 'subscribed']);
} catch (PDOException $e) {
    // Duplicate email or table missing
    if ($e->getCode() === '23000') {
        echo json_encode(['ok' => true, 'status' => 'already_subscribed']);
        exit;
    }
    if ($e->getCode() === '42S02') {
        http_response_code(500);
        echo json_encode(['error' => 'table_missing', 'message' => 'Import sql/schema.sql to create newsletter_subscriptions']);
        exit;
    }
    throw $e;
}

