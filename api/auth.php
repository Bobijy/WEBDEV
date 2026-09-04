<?php
/**
 * Maison Ungod — Authentication API
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'register':
        $name            = sanitize_raw($_POST['name']             ?? '');
        $email           = sanitize_raw($_POST['email']            ?? '');
        $password        = $_POST['password']                       ?? '';
        $confirmPassword = $_POST['confirm_password']               ?? '';

        $errors = [];

        if ($name === '') $errors['name'] = 'Name is required.';
        elseif (!validate_name($name)) $errors['name'] = 'Name must be 2–100 characters and contain only letters, spaces, hyphens, or apostrophes.';
        
        if ($email === '') $errors['email'] = 'Email is required.';
        elseif (!validate_email($email)) $errors['email'] = 'Please enter a valid email address.';
        else {
            $existing = db_fetch($pdo, 'SELECT id FROM users WHERE email = :email', [':email' => $email]);
            if ($existing) $errors['email'] = 'An account with this email already exists.';
        }

        if ($password === '') $errors['password'] = 'Password is required.';
        elseif (!validate_password($password)) $errors['password'] = 'Password must be at least 6 characters.';

        if ($confirmPassword !== '' && $password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            json_response(false, 'Please fix the errors below.', ['errors' => $errors]);
        }

        $safeName = sanitize_string($name);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            db_execute($pdo, 'INSERT INTO users (full_name, email, password) VALUES (:name, :email, :password)', [
                ':name'     => $safeName,
                ':email'    => $email,
                ':password' => $hashedPassword,
            ]);
            $newId = (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Register failed: ' . $e->getMessage());
            json_response(false, 'Registration failed. Please try again.');
        }

        $_SESSION['user_id']    = $newId;
        $_SESSION['user_name']  = $safeName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = 'customer';

        json_response(true, 'Account created successfully!', [
            'user' => ['name' => $safeName, 'email' => $email],
        ]);
        break;

    case 'login':
        $email    = sanitize_raw($_POST['email']    ?? '');
        $password = $_POST['password']               ?? '';

        $errors = [];

        if ($email === '') $errors['email'] = 'Email is required.';
        elseif (!validate_email($email)) $errors['email'] = 'Please enter a valid email address.';

        if ($password === '') $errors['password'] = 'Password is required.';

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

    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        json_response(true, 'Logged out successfully.');
        break;

    case 'status':
        if (!isset($_SESSION['user_id'])) {
            json_response(false, '', ['loggedIn' => false]);
        }
        $userId = (int) $_SESSION['user_id'];
        $user = db_fetch($pdo, 'SELECT id, full_name, email, phone, address, role FROM users WHERE id = :id', [':id' => $userId]);
        
        // Use order_items joined, since order_number is not in the db bootstrap schema it's added during checkout
        $orders = db_fetch_all($pdo, 'SELECT id, total_amount, status, created_at FROM orders WHERE user_id = :id ORDER BY created_at DESC', [':id' => $userId]);
        
        echo json_encode([
            'loggedIn' => true,
            'user'     => $user,
            'orders'   => $orders,
        ]);
        break;

    case 'update_profile':
        require_auth();
        $userId = (int) $_SESSION['user_id'];
        $currentUser = db_fetch($pdo, 'SELECT * FROM users WHERE id = :id', [':id' => $userId]);

        $name      = sanitize_raw($_POST['name'] ?? $currentUser['full_name']);
        $email     = sanitize_raw($_POST['email'] ?? $currentUser['email']);
        $phone     = sanitize_raw($_POST['phone'] ?? $currentUser['phone'] ?? '');
        $address   = sanitize_raw($_POST['address'] ?? $currentUser['address'] ?? '');
        $gender    = sanitize_raw($_POST['gender'] ?? $currentUser['gender'] ?? '');
        
        $errors = [];

        $dob_year  = sanitize_raw($_POST['dob_year'] ?? '');
        $dob_month = sanitize_raw($_POST['dob_month'] ?? '');
        $dob_date  = sanitize_raw($_POST['dob_date'] ?? '');
        
        $dob = $currentUser['dob'];
        if ($dob_year !== '' && $dob_month !== '' && $dob_date !== '') {
            if (checkdate((int)$dob_month, (int)$dob_date, (int)$dob_year)) {
                $dob = sprintf('%04d-%02d-%02d', $dob_year, $dob_month, $dob_date);
            } else {
                $errors['dob_date'] = 'Please enter a valid date of birth.';
            }
        } elseif ($dob_year === '' && $dob_month === '' && $dob_date === '') {
            $dob = null;
        } else {
            $errors['dob_date'] = 'Please completely fill or clear your date of birth.';
        }

        if ($name === '') $errors['name'] = 'Name is required.';
        elseif (!validate_name($name)) $errors['name'] = 'Name must be 2–100 characters and contain only letters, spaces, hyphens, or apostrophes.';
        
        if ($email === '') $errors['email'] = 'Email is required.';
        elseif (!validate_email($email)) $errors['email'] = 'Please enter a valid email address.';
        
        if ($phone !== '' && !validate_phone($phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        
        if ($address !== '' && mb_strlen($address) > 500) {
            $errors['address'] = 'Address is too long (maximum 500 characters).';
        }

        if (!empty($errors)) {
            json_response(false, 'Please fix the errors below.', ['errors' => $errors]);
        }

        $safeName = sanitize_string($name);

        try {
            db_execute($pdo, 'UPDATE users SET full_name = :name, email = :email, phone = :phone, address = :address, gender = :gender, dob = :dob WHERE id = :id', [
                ':name'    => $safeName,
                ':email'   => $email,
                ':phone'   => $phone ?: null,
                ':address' => $address ?: null,
                ':gender'  => $gender ?: null,
                ':dob'     => $dob,
                ':id'      => $userId,
            ]);
            $_SESSION['user_name']  = $safeName;
            $_SESSION['user_email'] = $email;
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Profile update failed: ' . $e->getMessage());
            json_response(false, 'Failed to update profile. Please try again.');
        }

        json_response(true, 'Profile updated successfully.');
        break;

    case 'change_password':
        require_auth();

        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];

        if ($current_password === '') $errors['current_password'] = 'Current password is required.';
        if ($new_password === '') $errors['new_password'] = 'New password is required.';
        elseif (!validate_password($new_password)) $errors['new_password'] = 'New password must be at least 6 characters.';

        if ($confirm_password === '') $errors['confirm_password'] = 'Please confirm your new password.';
        elseif ($new_password !== $confirm_password) $errors['confirm_password'] = 'New passwords do not match.';

        $userId = (int) $_SESSION['user_id'];
        $user = db_fetch($pdo, 'SELECT password FROM users WHERE id = :id', [':id' => $userId]);

        if (!$user || !password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Incorrect current password.';
        }

        if (!empty($errors)) {
            json_response(false, 'Please fix the errors below.', ['errors' => $errors]);
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

    default:
        json_response(false, 'Invalid action.');
        break;
}
