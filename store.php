<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function list_categories(): array {
    $pdo = db();
    return $pdo->query('SELECT id, name, icon FROM categories ORDER BY id ASC')->fetchAll();
}

// --- Wishlist ---
function wishlist_ids(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT listing_id FROM wishlist_items WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function wishlist_toggle(int $userId, int $listingId): bool {
    $pdo = db();
    $chk = $pdo->prepare('SELECT id FROM wishlist_items WHERE user_id = ? AND listing_id = ? LIMIT 1');
    $chk->execute([$userId, $listingId]);
    $id = $chk->fetchColumn();
    if ($id) {
        $pdo->prepare('DELETE FROM wishlist_items WHERE id = ?')->execute([(int)$id]);
        return false;
    }
    $pdo->prepare('INSERT INTO wishlist_items (user_id, listing_id) VALUES (?, ?)')->execute([$userId, $listingId]);
    return true;
}

function wishlist_items(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('
      SELECT
        l.id, l.title, l.price, l.item_condition, l.image_path,
        c.name AS category_name
      FROM wishlist_items w
      JOIN listings l ON l.id = w.listing_id
      JOIN categories c ON c.id = l.category_id
      WHERE w.user_id = ?
      ORDER BY w.id DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// --- Reports ---
function report_create(int $listingId, string $reason, ?string $details, ?int $reportedByUserId): int {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO reports (listing_id, reason, details, reported_by_user_id) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $reason, $details, $reportedByUserId]);
    return (int)$pdo->lastInsertId();
}

function reports_list(): array {
    $pdo = db();
    return $pdo->query('
      SELECT
        r.id, r.reason, r.details, r.created_at,
        l.id AS listing_id, l.title AS listing_title,
        u.email AS reported_by_email
      FROM reports r
      JOIN listings l ON l.id = r.listing_id
      LEFT JOIN users u ON u.id = r.reported_by_user_id
      ORDER BY r.id DESC
    ')->fetchAll();
}

function report_dismiss(int $reportId): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM reports WHERE id = ?')->execute([$reportId]);
}

// --- Messages (Chat) ---
function message_send(int $fromUserId, int $toUserId, ?int $listingId, string $body): int {
    $pdo = db();
    $body = trim($body);
    if ($body === '') throw new RuntimeException('Message cannot be empty');
    $stmt = $pdo->prepare('INSERT INTO messages (listing_id, from_user_id, to_user_id, body) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $fromUserId, $toUserId, $body]);
    return (int)$pdo->lastInsertId();
}

function messages_between(int $userA, int $userB, ?int $listingId = null, int $limit = 50): array {
    $pdo = db();
    $limit = max(1, min(200, $limit));
    $params = [$userA, $userB, $userB, $userA];
    $listingWhere = '';
    if ($listingId !== null) {
        $listingWhere = ' AND (m.listing_id = ?)';
        $params[] = $listingId;
    } else {
        $listingWhere = ' AND (m.listing_id IS NULL)';
    }
    $stmt = $pdo->prepare('
      SELECT m.id, m.listing_id, m.from_user_id, m.to_user_id, m.body, m.created_at
      FROM messages m
      WHERE ((m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?))
      ' . $listingWhere . '
      ORDER BY m.id DESC
      LIMIT ' . $limit . '
    ');
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    return array_reverse($rows);
}

function messages_thread(int $myId, int $otherUserId, ?int $listingId): array {
    $pdo = db();
    
    // Mark messages as read since we are viewing the thread
    $updateStmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE to_user_id = ? AND from_user_id = ? AND is_read = 0' . ($listingId ? ' AND listing_id = ?' : ''));
    if ($listingId) $updateStmt->execute([$myId, $otherUserId, $listingId]);
    else $updateStmt->execute([$myId, $otherUserId]);

    $params = [$myId, $otherUserId, $otherUserId, $myId];
    $listingWhere = '';
    if ($listingId !== null) {
        $listingWhere = ' AND (m.listing_id = ?)';
        $params[] = $listingId;
    } else {
        $listingWhere = ' AND (m.listing_id IS NULL)';
    }

    $query = '
      SELECT m.*, u.name AS from_name 
      FROM messages m
      JOIN users u ON u.id = m.from_user_id
      WHERE ((m.from_user_id = ? AND m.to_user_id = ?) OR (m.from_user_id = ? AND m.to_user_id = ?))
      ' . $listingWhere . '
      ORDER BY m.id ASC
    ';
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function messages_inbox(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('
      SELECT 
        m.id, 
        m.body, 
        m.created_at, 
        m.from_user_id, 
        m.to_user_id, 
        m.listing_id,
        m.is_read,
        u.id AS other_user_id,
        u.name AS other_user_name,
        u.email AS other_user_email,
        l.title AS listing_title,
        l.image_path AS listing_image
      FROM messages m
      JOIN (
        SELECT 
          MAX(id) AS max_id,
          CASE WHEN from_user_id = ? THEN to_user_id ELSE from_user_id END AS other_user_id,
          listing_id
        FROM messages
        WHERE from_user_id = ? OR to_user_id = ?
        GROUP BY other_user_id, listing_id
      ) latest ON m.id = latest.max_id
      JOIN users u ON u.id = CASE WHEN m.from_user_id = ? THEN m.to_user_id ELSE m.from_user_id END
      LEFT JOIN listings l ON l.id = m.listing_id
      ORDER BY m.id DESC
    ');
    $stmt->execute([$userId, $userId, $userId, $userId]);
    return $stmt->fetchAll();
}

function count_unread_messages(int $userId): int {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE to_user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// --- Reviews ---
function review_create(int $listingId, int $userId, int $rating, string $comment): int {
    $pdo = db();
    $rating = max(1, min(5, $rating));
    $comment = trim($comment);
    if ($comment === '') throw new RuntimeException('Review comment cannot be empty');
    $stmt = $pdo->prepare('INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $userId, $rating, $comment]);
    return (int)$pdo->lastInsertId();
}

function reviews_list(int $listingId, int $limit = 50): array {
    $pdo = db();
    $limit = max(1, min(200, $limit));
    $stmt = $pdo->prepare('
      SELECT r.id, r.rating, r.comment, r.created_at, u.name AS user_name
      FROM reviews r
      JOIN users u ON u.id = r.user_id
      WHERE r.listing_id = ?
      ORDER BY r.id DESC
      LIMIT ' . $limit . '
    ');
    $stmt->execute([$listingId]);
    return $stmt->fetchAll();
}

function reviews_list_all(): array {
    $pdo = db();
    return $pdo->query('
      SELECT r.id, r.rating, r.comment, r.created_at, u.name AS user_name, u.email AS user_email, l.id AS listing_id, l.title AS listing_title
      FROM reviews r
      JOIN users u ON u.id = r.user_id
      JOIN listings l ON l.id = r.listing_id
      ORDER BY r.id DESC
    ')->fetchAll();
}

function reviews_summary(int $listingId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(AVG(rating), 0) AS avg_rating FROM reviews WHERE listing_id = ?');
    $stmt->execute([$listingId]);
    $row = $stmt->fetch() ?: ['cnt' => 0, 'avg_rating' => 0];
    return ['count' => (int)$row['cnt'], 'avg' => (float)$row['avg_rating']];
}

// --- Seller Reviews ---
function seller_review_create(int $sellerId, int $reviewerId, int $rating, string $comment): int {
    $pdo = db();
    $rating = max(1, min(5, $rating));
    $comment = trim($comment);
    if ($comment === '') throw new RuntimeException('Review comment cannot be empty');
    $stmt = $pdo->prepare('INSERT INTO seller_reviews (seller_id, reviewer_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)');
    $stmt->execute([$sellerId, $reviewerId, $rating, $comment]);
    return (int)$pdo->lastInsertId();
}

function seller_reviews_list(int $sellerId, int $limit = 50): array {
    $pdo = db();
    $limit = max(1, min(200, $limit));
    $stmt = $pdo->prepare('
      SELECT r.id, r.rating, r.comment, r.created_at, u.name AS reviewer_name, u.email as reviewer_email
      FROM seller_reviews r
      JOIN users u ON u.id = r.reviewer_id
      WHERE r.seller_id = ?
      ORDER BY r.id DESC
      LIMIT ' . $limit . '
    ');
    $stmt->execute([$sellerId]);
    return $stmt->fetchAll();
}

function seller_reviews_summary(int $sellerId): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(AVG(rating), 0) AS avg_rating FROM seller_reviews WHERE seller_id = ?');
    $stmt->execute([$sellerId]);
    $row = $stmt->fetch() ?: ['cnt' => 0, 'avg_rating' => 0];
    return ['count' => (int)$row['cnt'], 'avg' => (float)$row['avg_rating']];
}

function get_category_by_name(string $name): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, icon FROM categories WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function list_listings(array $filters = []): array {
    $pdo = db();

    $where = ['l.status = ?'];
    $params = ['active'];

    if (!empty($filters['category_id'])) {
        $where[] = 'l.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['seller_id'])) {
        $where[] = 'l.seller_user_id = ?';
        $params[] = (int)$filters['seller_id'];
    }
    if (!empty($filters['q'])) {
        $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
        $q = '%' . $filters['q'] . '%';
        $params[] = $q;
        $params[] = $q;
    }
    if (!empty($filters['max_price'])) {
        $where[] = 'l.price <= ?';
        $params[] = (float)$filters['max_price'];
    }
    if (!empty($filters['condition'])) {
        $where[] = 'l.item_condition = ?';
        $params[] = (string)$filters['condition'];
    }

    $orderBy = 'l.id DESC';
    if (($filters['sort'] ?? '') === 'low-high') $orderBy = 'l.price ASC';
    if (($filters['sort'] ?? '') === 'high-low') $orderBy = 'l.price DESC';

    $sql = '
      SELECT
        l.id, l.title, l.description, l.price, l.item_condition, l.location, l.image_path, l.status,
        c.name AS category_name,
        u.email AS seller_email
      FROM listings l
      JOIN categories c ON c.id = l.category_id
      JOIN users u ON u.id = l.seller_user_id
      WHERE ' . implode(' AND ', $where) . '
      ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_listing(int $id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('
      SELECT
        l.*,
        c.name AS category_name,
        c.icon AS category_icon,
        u.name AS seller_name,
        u.email AS seller_email
      FROM listings l
      JOIN categories c ON c.id = l.category_id
      JOIN users u ON u.id = l.seller_user_id
      WHERE l.id = ?
      LIMIT 1
    ');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_listing(int $sellerUserId, array $data): int {
    $pdo = db();
    $stmt = $pdo->prepare('
      INSERT INTO listings
        (seller_user_id, category_id, title, description, price, item_condition, location, status, image_path, image_gallery)
      VALUES
        (?,?,?,?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        $sellerUserId,
        (int)$data['category_id'],
        (string)$data['title'],
        (string)($data['description'] ?? ''),
        (float)$data['price'],
        (string)$data['item_condition'],
        (string)($data['location'] ?? ''),
        'active',
        $data['image_path'] ?? null,
        $data['image_gallery'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

// MERGED CART TABLE helpers (table name: cart)
function get_or_create_cart_id(int $userId): int {
    return $userId;
}

function cart_add_item(int $userId, int $listingId, int $qty): void {
    $pdo = db();
    $stmt = $pdo->prepare('
      INSERT INTO cart (user_id, listing_id)
      VALUES (?, ?)
      ON DUPLICATE KEY UPDATE listing_id = VALUES(listing_id)
    ');
    $stmt->execute([$userId, $listingId]);
}

function cart_set_quantity(int $userId, int $listingId, int $qty): void {
    $pdo = db();
    if ($qty <= 0) {
        $del = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND listing_id = ?');
        $del->execute([$userId, $listingId]);
        return;
    }
    cart_add_item($userId, $listingId, 1);
}

function cart_get(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('
      SELECT
        c.listing_id,
        l.title,
        l.price,
        l.image_path,
        l.item_condition,
        cat.name AS category_name
      FROM cart c
      JOIN listings l ON l.id = c.listing_id
      JOIN categories cat ON cat.id = l.category_id
      WHERE c.user_id = ?
      ORDER BY c.id DESC
    ');
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $total = 0.0;
    foreach ($items as $it) {
        $total += (float)$it['price'];
    }

    return ['items' => $items, 'total' => $total];
}

function cart_clear(int $userId): void {
    $pdo = db();
    $pdo->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$userId]);
}

function orders_list_for_user(int $userId): array {
    $pdo = db();
    $stmt = $pdo->prepare('
      SELECT o.order_ref AS id,
             SUM(o.price_at_purchase) AS total_amount,
             MIN(o.status) AS status,
             MIN(o.created_at) AS created_at,
             GROUP_CONCAT(l.title SEPARATOR \', \') AS product_titles,
             MIN(l.image_path) AS image_path
      FROM `order` o
      JOIN listings l ON l.id = o.listing_id
      WHERE o.buyer_user_id = ?
      GROUP BY o.order_ref
      ORDER BY MIN(o.id) DESC
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function orders_list_all(): array {
    $pdo = db();
    return $pdo->query('
      SELECT o.order_ref AS id,
             SUM(o.price_at_purchase) AS total_amount,
             MIN(o.status) AS status,
             MIN(o.created_at) AS created_at,
             u.email AS buyer_email,
             GROUP_CONCAT(l.title SEPARATOR \', \') AS product_titles,
             MIN(l.image_path) AS image_path
      FROM `order` o
      JOIN users u ON u.id = o.buyer_user_id
      JOIN listings l ON l.id = o.listing_id
      GROUP BY o.order_ref, u.email
      ORDER BY MIN(o.id) DESC
    ')->fetchAll();
}

function order_update_status(string $orderRef, string $status): void {
    $pdo = db();
    $valid = ['pending', 'confirmed', 'cancelled'];
    if (!in_array($status, $valid, true)) return;
    $pdo->prepare('UPDATE `order` SET status = ? WHERE order_ref = ?')->execute([$status, $orderRef]);
}

function create_order_from_cart(int $userId, ?string $shippingNote = null): int {
    $pdo = db();
    $cart = cart_get($userId);
    $items = $cart['items'];
    if (count($items) === 0) {
        throw new RuntimeException('Cart is empty');
    }

    $pdo->beginTransaction();
    try {
        $orderRef = bin2hex(random_bytes(8));
        $itemStmt = $pdo->prepare('INSERT INTO `order` (order_ref, buyer_user_id, listing_id, price_at_purchase, status, shipping_note) VALUES (?,?,?,?,?,?)');
        foreach ($items as $it) {
            $itemStmt->execute([$orderRef, $userId, (int)$it['listing_id'], (float)$it['price'], 'pending', $shippingNote]);
        }

        cart_clear($userId);
        $pdo->commit();
        return (int)($pdo->query('SELECT MAX(id) FROM `order`')->fetchColumn() ?? 0);
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

