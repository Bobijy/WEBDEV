<?php
require 'database/db.php';
$stmt = $pdo->query('DESCRIBE orders');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
