<?php
/**
 * Maison Ungod — Dedicated Logout API
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// If called via standard browser link/redirect instead of AJAX fetch:
if (isset($_GET['redirect'])) {
    $target = $_GET['redirect'] === 'login' ? '../login.php' : '../index.php';
    header("Location: $target");
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../database/helpers.php';
json_response(true, 'Logged out successfully.');
