<?php
/**
 * Maison Ungod — Dedicated Login API
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
} else {
    json_response(false, 'Invalid request method.');
}

$email    = sanitize_raw($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];

if ($email === '') {
    $errors['email'] = 'Email is required.';
} elseif (!validate_email($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if ($password === '') {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    json_response(false, 'Please fix the errors below.', ['errors' => $errors]);
}

$user = db_fetch($pdo, 'SELECT id, full_name, email, password, role FROM users WHERE email = :email', [':email' => $email]);

if (!$user) {
    json_response(false, 'No account found with this email.', ['errors' => ['email' => 'No account found with this email.']]);
}

if (!password_verify($password, $user['password'])) {
    json_response(false, 'Incorrect password.', ['errors' => ['password' => 'Incorrect password.']]);
}

$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['full_name'];
$_SESSION['first_name'] = explode(' ', trim($user['full_name']))[0];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $user['role'];

$redirect = ($user['role'] === 'admin') ? 'admin/index.php' : 'index.php';
if ($user['role'] !== 'admin' && !empty($_POST['redirect'])) {
    $candidate = trim($_POST['redirect']);
    // Allow only relative paths or known internal pages to prevent open redirects
    if (!preg_match('#^(https?:)?//#i', $candidate) && !str_starts_with($candidate, '\\')) {
        $redirect = $candidate;
    }
}

json_response(true, 'Welcome back, ' . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . '!', [
    'user' => [
        'name'  => $user['full_name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ],
    'redirect' => $redirect,
]);
