<?php
declare(strict_types=1);

// Diagnostics: check if seeded users exist and password verification works.
// Open: http://localhost/marketplace/tools/diag-login.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db();
    echo "DB OK\n";
    echo "DB_NAME=" . DB_NAME . "\n";

    $emails = ['admin@university.edu', 'user@university.edu'];
    foreach ($emails as $email) {
        $stmt = $pdo->prepare('SELECT id, email, role, status, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            echo "\n$email: NOT FOUND in users table\n";
            continue;
        }
        echo "\n$email: FOUND (id={$row['id']}, role={$row['role']}, status={$row['status']})\n";
        $pw = $email === 'admin@university.edu' ? 'admin123' : 'user123';
        $ok = password_verify($pw, (string)$row['password_hash']);
        echo "password_verify(" . $pw . "): " . ($ok ? "OK" : "FAIL") . "\n";
        echo "hash_prefix=" . substr((string)$row['password_hash'], 0, 20) . "...\n";
    }

    echo "\nIf users are NOT FOUND, import sql/schema.sql and run tools/seed.php.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}

