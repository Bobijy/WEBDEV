<?php
// Address API: manages user delivery addresses (add, edit, delete, set default)

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Unauthorized');
}

$user_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
    case 'edit':
        $id           = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $label        = sanitize_raw($_POST['label'] ?? 'Home');
        $full_name    = sanitize_raw($_POST['full_name'] ?? '');
        $phone        = sanitize_raw($_POST['phone'] ?? '');
        $address_line = sanitize_raw($_POST['address_line'] ?? '');
        $postal_code  = sanitize_raw($_POST['postal_code'] ?? '');
        $is_default   = isset($_POST['is_default']) ? 1 : 0;

        if ($full_name === '' || $phone === '' || $address_line === '') {
            json_response(false, 'Please fill in all required fields.');
        }

        try {
            $pdo->beginTransaction();

            // If this is the first address, force it to be default
            $count = db_fetch($pdo, 'SELECT COUNT(*) as c FROM user_addresses WHERE user_id = :uid', [':uid' => $user_id]);
            if ($count['c'] == 0) {
                $is_default = 1;
            }

            $display_addr = $address_line . ($postal_code !== '' ? ', ' . $postal_code : '');

            // If setting as default, clear others
            if ($is_default) {
                db_execute($pdo, 'UPDATE user_addresses SET is_default = 0 WHERE user_id = :uid', [':uid' => $user_id]);
                // Sync to users table
                db_execute($pdo, 'UPDATE users SET address = :addr, phone = :phone WHERE id = :uid', [
                    ':addr'  => $display_addr,
                    ':phone' => $phone,
                    ':uid'   => $user_id
                ]);
            }

            if ($action === 'add') {
                db_execute($pdo, 
                    'INSERT INTO user_addresses (user_id, label, full_name, phone, address_line, postal_code, is_default) VALUES (:uid, :label, :name, :phone, :addr, :postal, :def)',
                    [
                        ':uid'    => $user_id,
                        ':label'  => $label,
                        ':name'   => $full_name,
                        ':phone'  => $phone,
                        ':addr'   => $address_line,
                        ':postal' => $postal_code !== '' ? $postal_code : null,
                        ':def'    => $is_default
                    ]
                );
            } else {
                // Ensure the address belongs to the user
                db_execute($pdo,
                    'UPDATE user_addresses SET label = :label, full_name = :name, phone = :phone, address_line = :addr, postal_code = :postal, is_default = :def WHERE id = :id AND user_id = :uid',
                    [
                        ':label'  => $label,
                        ':name'   => $full_name,
                        ':phone'  => $phone,
                        ':addr'   => $address_line,
                        ':postal' => $postal_code !== '' ? $postal_code : null,
                        ':def'    => $is_default,
                        ':id'     => $id,
                        ':uid'    => $user_id
                    ]
                );
            }

            $pdo->commit();
            json_response(true, 'Address saved successfully.');
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(false, 'Failed to save address.');
        }
        break;

    case 'delete':
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            // Check if it's default
            $addr = db_fetch($pdo, 'SELECT is_default FROM user_addresses WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $user_id]);
            if ($addr) {
                db_execute($pdo, 'DELETE FROM user_addresses WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $user_id]);
                
                // If default was deleted, make the newest one default
                if ($addr['is_default']) {
                    $next = db_fetch($pdo, 'SELECT id, address_line, postal_code, phone FROM user_addresses WHERE user_id = :uid ORDER BY id DESC LIMIT 1', [':uid' => $user_id]);
                    if ($next) {
                        db_execute($pdo, 'UPDATE user_addresses SET is_default = 1 WHERE id = :id', [':id' => $next['id']]);
                        $next_addr = $next['address_line'] . (!empty($next['postal_code']) ? ', ' . $next['postal_code'] : '');
                        db_execute($pdo, 'UPDATE users SET address = :addr, phone = :phone WHERE id = :uid', [
                            ':addr'  => $next_addr,
                            ':phone' => $next['phone'],
                            ':uid'   => $user_id
                        ]);
                    } else {
                        // No addresses left
                        db_execute($pdo, 'UPDATE users SET address = NULL WHERE id = :uid', [':uid' => $user_id]);
                    }
                }
                json_response(true, 'Address deleted.');
            } else {
                json_response(false, 'Address not found.');
            }
        } catch (Exception $e) {
            json_response(false, 'Failed to delete address.');
        }
        break;

    case 'set_default':
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        try {
            $pdo->beginTransaction();
            $addr = db_fetch($pdo, 'SELECT * FROM user_addresses WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $user_id]);
            if ($addr) {
                db_execute($pdo, 'UPDATE user_addresses SET is_default = 0 WHERE user_id = :uid', [':uid' => $user_id]);
                db_execute($pdo, 'UPDATE user_addresses SET is_default = 1 WHERE id = :id', [':id' => $id]);
                
                $full_addr = $addr['address_line'] . (!empty($addr['postal_code']) ? ', ' . $addr['postal_code'] : '');
                db_execute($pdo, 'UPDATE users SET address = :addr, phone = :phone WHERE id = :uid', [
                    ':addr'  => $full_addr,
                    ':phone' => $addr['phone'],
                    ':uid'   => $user_id
                ]);
                $pdo->commit();
                json_response(true, 'Default address updated.');
            } else {
                $pdo->rollBack();
                json_response(false, 'Address not found.');
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(false, 'Failed to update default address.');
        }
        break;

    default:
        json_response(false, 'Invalid action.');
}
