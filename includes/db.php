<?php
/**
 * Maison Ungod — Database Connection
 *
 * Driver : PDO (replaces the previous MySQLi connection)
 * Errors : Logged server-side via error_log() — never exposed to the client.
 * Usage  : All API files require_once this file to obtain $pdo.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'maison_ungod');
define('DB_CHARSET', 'utf8mb4');

// ── DSN & PDO Options ─────────────────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw on DB errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Always return assoc arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                     // Use real prepared statements
];

// ── Connect ───────────────────────────────────────────────────────────────────
try {
    // First connect without a database to allow CREATE DATABASE
    $bootstrapDsn = sprintf(
        'mysql:host=%s;charset=%s',
        DB_HOST,
        DB_CHARSET
    );
    $pdo = new PDO($bootstrapDsn, DB_USER, DB_PASS, $pdoOptions);

    // Create DB if it doesn't exist, then select it
    $pdo->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE ' . DB_NAME);

} catch (PDOException $e) {
    // Log the real error — never expose to client
    error_log('[Maison Ungod] Database connection failed: ' . $e->getMessage());

    // Return a safe generic error (API context: JSON; page context: HTML)
    $isApi = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')
          || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));

    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
    } else {
        echo '<p style="font-family:sans-serif;color:#c0392b;padding:20px;">A server error occurred. Please try again later.</p>';
    }
    exit;
}

// ── Schema Bootstrap ──────────────────────────────────────────────────────────
// Runs CREATE TABLE IF NOT EXISTS — safe to run on every request.
// These never modify existing tables or data.
try {
    // Users
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            full_name  VARCHAR(100)  NOT NULL,
            email      VARCHAR(255)  NOT NULL UNIQUE,
            password   VARCHAR(255)  NOT NULL,
            phone      VARCHAR(20)   DEFAULT NULL,
            address    TEXT          DEFAULT NULL,
            role       VARCHAR(20)   DEFAULT 'customer',
            created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Products
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(255)  NOT NULL,
            description TEXT          DEFAULT NULL,
            price       DECIMAL(10,2) NOT NULL,
            image       VARCHAR(255)  DEFAULT NULL,
            stock       INT           DEFAULT 0,
            category    VARCHAR(100)  DEFAULT 'Uncategorized',
            status      VARCHAR(20)   DEFAULT 'Active',
            created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Carts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS carts (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Cart Items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cart_items (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            cart_id    INT NOT NULL,
            product_id INT NOT NULL,
            quantity   INT NOT NULL DEFAULT 1,
            FOREIGN KEY (cart_id)    REFERENCES carts(id)    ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_cart_product (cart_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Orders
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            user_id          INT           NOT NULL,
            total_amount     DECIMAL(10,2) NOT NULL,
            shipping_address TEXT          NOT NULL,
            payment_method   VARCHAR(50)   NOT NULL,
            status           VARCHAR(50)   DEFAULT 'Pending',
            created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Order Items
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            order_id   INT           NOT NULL,
            product_id INT           NOT NULL,
            quantity   INT           NOT NULL,
            price      DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

} catch (PDOException $e) {
    error_log('[Maison Ungod] Schema bootstrap failed: ' . $e->getMessage());
    // Non-fatal for existing installations — tables already exist
}


