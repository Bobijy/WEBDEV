<?php
require_once __DIR__ . '/../database/db.php';
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN brand VARCHAR(255) NULL AFTER category;");
    echo "Successfully added brand column.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
