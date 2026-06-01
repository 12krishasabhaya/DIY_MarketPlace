<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? '');

if ($action === 'list') {
    $sellerId = (int)($_GET['seller_id'] ?? 0);
    if ($sellerId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    $reviews = seller_reviews_list($sellerId);
    echo json_encode(['reviews' => $reviews]);
    exit;
}

if ($action === 'create') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }
    
    $user = auth_user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    csrf_verify();
    
    $sellerId = (int)($_POST['seller_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $comment = (string)($_POST['comment'] ?? '');
    
    if ($sellerId <= 0 || $sellerId === (int)$user['id']) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_seller']);
        exit;
    }
    
    try {
        $id = seller_review_create($sellerId, (int)$user['id'], $rating, $comment);
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
