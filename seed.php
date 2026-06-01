<?php
declare(strict_types=1);

// One-time seeder for Campus Market (demo data).
// Run after importing sql/schema.sql:
//   http://localhost/marketplace/tools/seed.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/db.php';

function upsertCategory(PDO $pdo, string $name, string $icon): int {
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare('UPDATE categories SET icon = ? WHERE id = ?');
        $upd->execute([$icon, (int)$id]);
        return (int)$id;
    }
    $ins = $pdo->prepare('INSERT INTO categories (name, icon) VALUES (?, ?)');
    $ins->execute([$name, $icon]);
    return (int)$pdo->lastInsertId();
}

function upsertUser(PDO $pdo, string $name, string $email, string $password, string $role): int {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
        throw new RuntimeException('password_hash failed');
    }
    if ($id) {
        $upd = $pdo->prepare('UPDATE users SET name = ?, password_hash = ?, role = ?, status = ? WHERE id = ?');
        $upd->execute([$name, $hash, $role, 'active', (int)$id]);
        return (int)$id;
    }
    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$name, $email, $hash, $role, 'active']);
    return (int)$pdo->lastInsertId();
}

function upsertListing(PDO $pdo, array $listing): int {
    $stmt = $pdo->prepare('SELECT id FROM listings WHERE title = ? AND seller_user_id = ? LIMIT 1');
    $stmt->execute([$listing['title'], $listing['seller_user_id']]);
    $id = $stmt->fetchColumn();
    if ($id) {
        $upd = $pdo->prepare('UPDATE listings SET category_id=?, description=?, price=?, item_condition=?, location=?, status=?, image_path=? WHERE id=?');
        $upd->execute([
            $listing['category_id'],
            $listing['description'],
            $listing['price'],
            $listing['item_condition'],
            $listing['location'],
            $listing['status'],
            $listing['image_path'],
            (int)$id
        ]);
        return (int)$id;
    }
    $ins = $pdo->prepare('INSERT INTO listings (seller_user_id, category_id, title, description, price, item_condition, location, status, image_path) VALUES (?,?,?,?,?,?,?,?,?)');
    $ins->execute([
        $listing['seller_user_id'],
        $listing['category_id'],
        $listing['title'],
        $listing['description'],
        $listing['price'],
        $listing['item_condition'],
        $listing['location'],
        $listing['status'],
        $listing['image_path']
    ]);
    return (int)$pdo->lastInsertId();
}

function upsertWishlist(PDO $pdo, int $userId, int $listingId): void {
    $stmt = $pdo->prepare('INSERT INTO wishlist_items (user_id, listing_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE listing_id=VALUES(listing_id)');
    $stmt->execute([$userId, $listingId]);
}

function insertMessage(PDO $pdo, int $fromUserId, int $toUserId, ?int $listingId, string $body): void {
    $stmt = $pdo->prepare('INSERT INTO messages (listing_id, from_user_id, to_user_id, body) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $fromUserId, $toUserId, $body]);
}

function insertReport(PDO $pdo, int $listingId, string $reason, ?string $details, ?int $reportedByUserId): void {
    $stmt = $pdo->prepare('INSERT INTO reports (listing_id, reason, details, reported_by_user_id) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $reason, $details, $reportedByUserId]);
}

function insertReview(PDO $pdo, int $listingId, int $userId, int $rating, string $comment): void {
    $stmt = $pdo->prepare('INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?,?,?,?)');
    $stmt->execute([$listingId, $userId, $rating, $comment]);
}

function insertSellerReview(PDO $pdo, int $sellerId, int $reviewerId, int $rating, string $comment): void {
    $stmt = $pdo->prepare('INSERT INTO seller_reviews (seller_id, reviewer_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)');
    $stmt->execute([$sellerId, $reviewerId, $rating, $comment]);
}

function upsertCart(PDO $pdo, int $userId, int $listingId): void {
    $stmt = $pdo->prepare('INSERT INTO cart (user_id, listing_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE listing_id=VALUES(listing_id)');
    $stmt->execute([$userId, $listingId]);
}

function insertOrderItem(PDO $pdo, string $orderRef, int $buyerUserId, int $listingId, float $price, string $status, ?string $shippingNote): void {
    $stmt = $pdo->prepare('INSERT INTO `order` (order_ref, buyer_user_id, listing_id, price_at_purchase, status, shipping_note) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$orderRef, $buyerUserId, $listingId, $price, $status, $shippingNote]);
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    $catElectronics = upsertCategory($pdo, 'Electronics', 'fas fa-microchip');
    $catLab = upsertCategory($pdo, 'Lab Material', 'fas fa-flask');
    $catDorm = upsertCategory($pdo, 'Dorm Essentials', 'fas fa-bed');
    $catNotes = upsertCategory($pdo, 'Notes', 'fas fa-book');

    $adminId = upsertUser($pdo, 'Admin', 'admin@university.edu', 'admin123', 'admin');
    $studentId = upsertUser($pdo, 'Student User', 'user@university.edu', 'user123', 'student');

    $listingArduino = upsertListing($pdo, [
        'seller_user_id' => $adminId,
        'category_id' => $catElectronics,
        'title' => 'Arduino Uno Rev3',
        'description' => 'Perfect for DIY electronics projects and robotics. Includes USB cable and basic documentation.',
        'price' => 750.00,
        'item_condition' => 'Used (Good)',
        'location' => 'Engineering Block',
        'status' => 'active',
        'image_path' => 'img/arduino.avif',
    ]);

    $listingMicroscope = upsertListing($pdo, [
        'seller_user_id' => $adminId,
        'category_id' => $catLab,
        'title' => 'Compound Microscope',
        'description' => 'Ideal for biology and chemistry lab work. Features adjustable magnification and LED illumination.',
        'price' => 1200.00,
        'item_condition' => 'Fair',
        'location' => 'Science Lab',
        'status' => 'active',
        'image_path' => 'img/lab.avif',
    ]);

    // Seed wishlist, chat, report, reviews for UI testing
    upsertWishlist($pdo, $studentId, $listingArduino);
    upsertCart($pdo, $studentId, $listingMicroscope);

    insertMessage($pdo, $studentId, $adminId, $listingArduino, 'Hi! Is this Arduino still available?');
    insertMessage($pdo, $adminId, $studentId, $listingArduino, 'Yes, it is available. You can place the order anytime.');

    // Seed Reports
    insertReport($pdo, $listingMicroscope, 'Price seems incorrect', 'Please verify item condition/price.', $studentId);
    insertReport($pdo, $listingArduino, 'Suspicious listing', 'Seller is not responding properly.', null);

    // Seed Reviews
    insertReview($pdo, $listingArduino, $studentId, 5, 'Great seller and item matched the description.');
    insertReview($pdo, $listingMicroscope, $studentId, 4, 'Good condition, but delivery was a bit late.');

    // Seed Seller Reviews
    insertSellerReview($pdo, $adminId, $studentId, 5, 'Reliable seller, very quick responses and the item was exactly as described.');

    // Seed one sample order (merged `order` table, grouped by order_ref)
    $orderRef = bin2hex(random_bytes(8));
    insertOrderItem($pdo, $orderRef, $studentId, $listingArduino, 750.00, 'pending', 'Deliver near library');

    $pdo->commit();

    echo "Seed completed.\n";
    echo "Demo credentials:\n";
    echo "  Admin: admin@university.edu / admin123\n";
    echo "  Student: user@university.edu / user123\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Seed failed: " . $e->getMessage() . "\n";
}

