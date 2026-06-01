<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';

header('Content-Type: application/json; charset=utf-8');

$user = auth_user();
$action = $_GET['action'] ?? '';

if ($action === 'count') {
    if (!$user) {
        echo json_encode(['count' => 0]);
        exit;
    }
    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT COUNT(*) AS qty
        FROM cart
        WHERE user_id = ?
    ');
    $stmt->execute([(int)$user['id']]);
    $qty = (int)($stmt->fetchColumn() ?? 0);
    echo json_encode(['count' => $qty]);
    exit;
}

if ($action === 'add') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    csrf_verify();
    require_once __DIR__ . '/../lib/store.php';
    $listingId = (int)($_POST['listing_id'] ?? 0);
    if ($listingId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    cart_add_item((int)$user['id'], $listingId, 1);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);

