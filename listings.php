<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

// Optional filters (used by frontend JS)
$filters = [
  'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : null,
  'q' => isset($_GET['q']) ? (string)$_GET['q'] : null,
  'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : null,
  'condition' => isset($_GET['condition']) ? (string)$_GET['condition'] : null,
  'sort' => isset($_GET['sort']) ? (string)$_GET['sort'] : 'newest',
];

$items = list_listings($filters);

// Shape it like the old frontend expected
$mapped = array_map(static function(array $p): array {
  return [
    'id' => (int)$p['id'],
    'title' => (string)$p['title'],
    'price' => (float)$p['price'],
    'seller' => (string)$p['seller_email'],
    'status' => (string)$p['status'],
    'category' => (string)$p['category_name'],
    'condition' => (string)$p['item_condition'],
    'img' => (string)($p['image_path'] ?: 'img/placeholder.png'),
    'description' => (string)($p['description'] ?? ''),
  ];
}, $items);

echo json_encode(['listings' => $mapped]);

