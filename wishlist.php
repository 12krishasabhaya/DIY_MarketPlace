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

if ($action === 'list') {
    $ids = wishlist_ids((int)$user['id']);
    echo json_encode(['ids' => $ids]);
    exit;
}

if ($action === 'toggle') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }
    csrf_verify();
    $listingId = (int)($_POST['listing_id'] ?? 0);
    if ($listingId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    $active = wishlist_toggle((int)$user['id'], $listingId);
    echo json_encode(['active' => $active]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);

