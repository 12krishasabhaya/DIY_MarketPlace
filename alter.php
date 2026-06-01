<?php
require 'lib/db.php';
try {
    db()->exec('ALTER TABLE listings ADD COLUMN image_gallery TEXT NULL');
    echo 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'success (already exists)';
    } else {
        echo 'error: ' . $e->getMessage();
    }
}
