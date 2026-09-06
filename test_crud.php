<?php
require_once __DIR__ . '/database/db.php';
$stmt = $pdo->query('SELECT name FROM products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
