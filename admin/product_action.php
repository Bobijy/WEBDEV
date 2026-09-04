<?php
session_start();

// Redirect to admin login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/product_validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add' || $action === 'update') {
    $validation = validate_product_form($_POST);
    $errors = $validation['errors'];
    $data   = $validation['data'];

    $is_update = ($action === 'update');
    $id = 0;

    if ($is_update) {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!validate_id($id)) {
            $errors['id'] = 'Invalid product ID.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        
        $redirect = 'product_form.php' . ($is_update ? '?id=' . $id : '');
        header("Location: $redirect");
        exit;
    }

    // Default image
    $image = 'assets/images/default.png';

    try {
        if ($is_update) {
            $stmt = $pdo->prepare('
                UPDATE products
                SET    name = :name,
                       description = :description,
                       price = :price,
                       stock = :stock,
                       category = :category,
                       status = :status
                WHERE  id = :id
            ');
            $stmt->execute([
                ':name'        => $data['name'],
                ':description' => $data['description'],
                ':price'       => $data['price'],
                ':stock'       => $data['stock'],
                ':category'    => $data['category'],
                ':status'      => $data['status'],
                ':id'          => $id,
            ]);
            $_SESSION['msg'] = 'Product updated successfully.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO products (name, description, price, stock, category, status, image)
                VALUES (:name, :description, :price, :stock, :category, :status, :image)
            ');
            $stmt->execute([
                ':name'        => $data['name'],
                ':description' => $data['description'],
                ':price'       => $data['price'],
                ':stock'       => $data['stock'],
                ':category'    => $data['category'],
                ':status'      => $data['status'],
                ':image'       => $image,
            ]);
            $_SESSION['msg'] = 'Product added successfully.';
        }
    } catch (PDOException $e) {
        error_log('[Maison Ungod] Product action failed: ' . $e->getMessage());
        $_SESSION['error'] = 'Database error. Please try again.';
        $_SESSION['form_data'] = $_POST;
        
        $redirect = 'product_form.php' . ($is_update ? '?id=' . $id : '');
        header("Location: $redirect");
        exit;
    }

    header("Location: products.php");
    exit;

} elseif ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (validate_id($id)) {
        try {
            $affected = db_execute($pdo, 'DELETE FROM products WHERE id = :id', [':id' => $id]);
            if ($affected > 0) {
                $_SESSION['msg'] = 'Product deleted successfully.';
            } else {
                $_SESSION['error'] = 'Product not found.';
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Delete product failed: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete product. Please try again.';
        }
    } else {
        $_SESSION['error'] = 'Invalid product ID.';
    }

    header("Location: products.php");
    exit;
}

// Invalid action
header("Location: products.php");
exit;
