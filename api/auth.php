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
        
        $userId = (int) $_SESSION['user_id'];
        $currentUser = db_fetch($pdo, 'SELECT * FROM users WHERE id = :id', [':id' => $userId]);

        $name      = isset($_POST['name']) ? sanitize_raw($_POST['name']) : $currentUser['full_name'];
        $email     = isset($_POST['email']) ? sanitize_raw($_POST['email']) : $currentUser['email'];
        $phone     = isset($_POST['phone']) ? sanitize_raw($_POST['phone']) : $currentUser['phone'];
        $address   = isset($_POST['address']) ? sanitize_raw($_POST['address']) : $currentUser['address'];
        $gender    = isset($_POST['gender']) ? sanitize_raw($_POST['gender']) : $currentUser['gender'];
        
        $dob_date  = sanitize_raw($_POST['dob_date']  ?? '');
        $dob_month = sanitize_raw($_POST['dob_month'] ?? '');
        $dob_year  = sanitize_raw($_POST['dob_year']  ?? '');
        $dob = $currentUser['dob'];
        if ($dob_date && $dob_month && $dob_year) {
            $dob = sprintf('%04d-%02d-%02d', $dob_year, $dob_month, $dob_date);
        }

        // Validation
        if ($name === '' || $email === '') {
            json_response(false, 'Name and email are required.');
        }
        if (!validate_name($name)) {
            json_response(false, 'Name must be 2–100 characters and contain only letters, spaces, hyphens, or apostrophes.');
        }
        if (!validate_email($email)) {
            json_response(false, 'Please enter a valid email address.');
        }
        if ($phone !== '' && $phone !== null && !validate_phone($phone)) {
            json_response(false, 'Please enter a valid phone number (e.g., 09XX-XXX-XXXX).');
        }
        if ($address !== null && mb_strlen($address) > 500) {
            json_response(false, 'Address is too long (maximum 500 characters).');
        }

        $safeName = sanitize_string($name);

        try {
            db_execute($pdo,
                'UPDATE users SET full_name = :name, email = :email, phone = :phone, address = :address, gender = :gender, dob = :dob WHERE id = :id',
                [
                    ':name'    => $safeName,
                    ':email'   => $email,
                    ':phone'   => $phone   ?: null,
                    ':address' => $address ?: null,
                    ':gender'  => $gender  ?: null,
                    ':dob'     => $dob     ?: null,
                    ':id'      => (int) $_SESSION['user_id'],
                ]
            );
            
            // Update session if needed
            $_SESSION['user_name']  = $safeName;
            $_SESSION['user_email'] = $email;
            
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Profile update failed: ' . $e->getMessage());
            json_response(false, 'Failed to update profile. Please try again.');
        }

        json_response(true, 'Profile updated successfully.');
        break;

    // ═══════════════════════
    // CHANGE PASSWORD
    // ═══════════════════════
    case 'change_password':
        require_auth();

        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            json_response(false, 'All fields are required.');
        }

        if ($new_password !== $confirm_password) {
            json_response(false, 'New passwords do not match.');
        }

        if (strlen($new_password) < 6) {
            json_response(false, 'New password must be at least 6 characters.');
        }

        $userId = (int) $_SESSION['user_id'];
        $user = db_fetch($pdo, 'SELECT password FROM users WHERE id = :id', [':id' => $userId]);

        if (!$user || !password_verify($current_password, $user['password'])) {
            json_response(false, 'Incorrect current password.');
        }

        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

        try {
            db_execute($pdo, 'UPDATE users SET password = :password WHERE id = :id', [
                ':password' => $hashedPassword,
                ':id'       => $userId,
            ]);
            json_response(true, 'Password changed successfully.');
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Password change failed: ' . $e->getMessage());
            json_response(false, 'Failed to change password. Please try again.');
        }
        break;

    // ═══════════════════════
    // DEFAULT
    // ═══════════════════════
    default:
        json_response(false, 'Invalid action.');
        break;
}
