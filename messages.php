<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$action = (string)($_GET['action'] ?? '');

if ($action === 'inbox') {
    $pdo = db();
    $pdo->prepare('UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND is_read = 0')->execute([(int)$user['id']]);
    
    $inbox = messages_inbox((int)$user['id']);
    echo json_encode(['inbox' => $inbox]);
    exit;
}

if ($action === 'thread') {
    $otherUserId = (int)($_GET['other_user_id'] ?? 0);
    $listingId = isset($_GET['listing_id']) && $_GET['listing_id'] !== '' ? (int)$_GET['listing_id'] : null;
    if ($otherUserId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    $msgs = messages_between((int)$user['id'], $otherUserId, $listingId, 50);
    echo json_encode(['messages' => $msgs]);
    exit;
}

if ($action === 'send') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }
    csrf_verify();
    $toUserId = (int)($_POST['to_user_id'] ?? 0);
    $listingId = isset($_POST['listing_id']) && $_POST['listing_id'] !== '' ? (int)$_POST['listing_id'] : null;
    $body = (string)($_POST['body'] ?? '');
    if ($toUserId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    try {
        $id = message_send((int)$user['id'], $toUserId, $listingId, $body);
        echo json_encode(['ok' => true, 'id' => $id]);
        exit;
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);

