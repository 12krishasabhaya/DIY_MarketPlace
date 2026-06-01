<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json; charset=utf-8');

$cats = list_categories();
echo json_encode(['categories' => $cats]);

