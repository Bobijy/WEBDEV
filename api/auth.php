<?php
/**
 * Maison Ungod — Authentication API
 *
 * Actions : register, login, logout, status, update_profile
 * Driver  : PDO with named parameters
 * Security: server-side validation + sanitization on all user input
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// ── Action Router ─────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ═══════════════════════
    // REGISTER
    // ═══════════════════════
    case 'register':
        // 1. Read raw inputs
        $name            = sanitize_raw($_POST['name']             ?? '');
        $email           = sanitize_raw($_POST['email']            ?? '');
        $password        = $_POST['password']                       ?? '';
        $confirmPassword = $_POST['confirm_password']               ?? '';

        // 2. Required field check
        if ($name === '' || $email === '' || $password === '') {
            json_response(false, 'All fields are required.');
        }

        // 3. Name validation — length and character rules
        if (!validate_name($name)) {
            json_response(false, 'Name must be 2–100 characters and contain only letters, spaces, hyphens, or apostrophes.');
        }

        // 4. Email validation
        if (!validate_email($email)) {
            json_response(false, 'Please enter a valid email address.');
        }

        // 5. Password length
        if (!validate_password($password)) {
            json_response(false, 'Password must be at least 6 characters.');
        }

        // 6. Server-side confirm password check (never trust client only)
        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            json_response(false, 'Passwords do not match.');
        }

        // 7. Sanitize name for storage (encode HTML entities)
        $safeName = sanitize_string($name);

        // 8. Check for duplicate email
        $existing = db_fetch($pdo, 'SELECT id FROM users WHERE email = :email', [':email' => $email]);
        if ($existing) {
            json_response(false, 'An account with this email already exists.');
        }

        // 9. Hash password and insert
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password) VALUES (:name, :email, :password)');
            $stmt->execute([
                ':name'     => $safeName,
                ':email'    => $email,
                ':password' => $hashedPassword,
            ]);
            $newId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Register failed: ' . $e->getMessage());
            json_response(false, 'Registration failed. Please try again.');
        }

        // 10. Auto-login after registration
        $_SESSION['user_id']    = $newId;
        $_SESSION['user_name']  = $safeName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'customer';

        json_response(true, 'Account created successfully!', [
            'user' => ['name' => $safeName, 'email' => $email],
        ]);
        break;

    // ═══════════════════════
    // LOGIN
    // ═══════════════════════
    case 'login':
        $email    = sanitize_raw($_POST['email']    ?? '');
        $password = $_POST['password']               ?? '';

        // Validate required fields
        if ($email === '' || $password === '') {
            json_response(false, 'Email and password are required.');
        }

        // Validate email format before hitting the DB
        if (!validate_email($email)) {
            json_response(false, 'Please enter a valid email address.');
        }

        // Lookup user by email
        $user = db_fetch(
            $pdo,
            'SELECT id, full_name, email, password, role FROM users WHERE email = :email',
            [':email' => $email]
        );

        if (!$user) {
            json_response(false, 'No account found with this email.');
        }

        // Verify password against stored hash
        if (!password_verify($password, $user['password'])) {
            json_response(false, 'Incorrect password.');
        }

        // Set session
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        json_response(true, 'Welcome back, ' . htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') . '!', [
            'user' => [
                'name'  => $user['full_name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
        break;

    // ═══════════════════════
    // LOGOUT
    // ═══════════════════════
    case 'logout':
        // Destroy session data completely
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        json_response(true, 'Logged out successfully.');
        break;

    // ═══════════════════════
    // STATUS (check if logged in)
    // ═══════════════════════
    case 'status':
        if (!isset($_SESSION['user_id'])) {
            json_response(false, '', ['loggedIn' => false]);
        }

        $userId = (int) $_SESSION['user_id'];

        $user = db_fetch(
            $pdo,
            'SELECT id, full_name, email, phone, address, role FROM users WHERE id = :id',
            [':id' => $userId]
        );

        $orders = db_fetch_all(
            $pdo,
            'SELECT id, total_amount, status, created_at FROM orders WHERE user_id = :id ORDER BY created_at DESC',
            [':id' => $userId]
        );

        echo json_encode([
            'loggedIn' => true,
            'user'     => [
                'id'      => $user['id'],
                'name'    => $user['full_name'],
                'email'   => $user['email'],
                'phone'   => $user['phone'],
                'address' => $user['address'],
                'role'    => $user['role'],
            ],
            'orders' => $orders,
        ]);
        break;

    // ═══════════════════════
    // UPDATE PROFILE
    // ═══════════════════════
    case 'update_profile':
        require_auth();

        $phone   = sanitize_raw($_POST['phone']   ?? '');
        $address = sanitize_raw($_POST['address'] ?? '');

        // Phone — required and must match valid format
        if ($phone !== '' && !validate_phone($phone)) {
            json_response(false, 'Please enter a valid phone number (e.g., 09XX-XXX-XXXX).');
        }

        // Address — max length guard
        if (mb_strlen($address) > 500) {
            json_response(false, 'Address is too long (maximum 500 characters).');
        }

        try {
            db_execute($pdo,
                'UPDATE users SET phone = :phone, address = :address WHERE id = :id',
                [
                    ':phone'   => $phone   ?: null,
                    ':address' => $address ?: null,
                    ':id'      => (int) $_SESSION['user_id'],
                ]
            );
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Profile update failed: ' . $e->getMessage());
            json_response(false, 'Failed to update profile. Please try again.');
        }

        json_response(true, 'Profile updated successfully.');
        break;

    // ═══════════════════════
    // DEFAULT
    // ═══════════════════════
    default:
        json_response(false, 'Invalid action.');
        break;
}
