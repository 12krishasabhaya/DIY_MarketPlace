<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$action = (string)($_GET['action'] ?? '');

if ($action === 'create') {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'method_not_allowed']);
        exit;
    }
    csrf_verify();
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $reason = trim((string)($_POST['reason'] ?? ''));
    $details = isset($_POST['details']) ? trim((string)$_POST['details']) : null;
    if ($listingId <= 0 || $reason === '') {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }
    $user = auth_user();
    try {
        $rid = report_create($listingId, $reason, $details ?: null, $user ? (int)$user['id'] : null);
        echo json_encode(['ok' => true, 'id' => $rid]);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad_request']);

