<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? 'list');
$listingId = (int)($_GET['listing_id'] ?? ($_POST['listing_id'] ?? 0));

if ($listingId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

if ($action === 'summary') {
    echo json_encode(reviews_summary($listingId));
    exit;
}

if ($action === 'list') {
    $items = reviews_list($listingId, 50);
    echo json_encode(['reviews' => $items]);
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
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = (string)($_POST['comment'] ?? '');
    try {
        $id = review_create($listingId, (int)$user['id'], $rating, $comment);
        echo json_encode(['ok' => true, 'id' => $id]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);

