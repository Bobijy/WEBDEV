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

// DSN & PDO Options 
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_NAME,
    DB_CHARSET
);

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Throw on DB errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Always return assoc arrays
    PDO::ATTR_EMULATE_PREPARES => false,                     // Use real prepared statements
];

// Connect
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