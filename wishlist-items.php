<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$items = wishlist_items((int)$user['id']);
$mapped = array_map(static function(array $it): array {
    return [
        'id' => (int)$it['id'],
        'title' => (string)$it['title'],
        'price' => (float)$it['price'],
        'img' => (string)($it['image_path'] ?: 'img/placeholder.png'),
        'condition' => (string)$it['item_condition'],
        'category' => (string)$it['category_name'],
    ];
}, $items);

echo json_encode(['items' => $mapped]);

