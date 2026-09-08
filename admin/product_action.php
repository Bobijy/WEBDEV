<?php
// Admin product actions: processes create, update, and delete form requests

session_start();

// Redirect to admin login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';
require_once __DIR__ . '/../database/product_validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: products.php");
    exit;
}

require_csrf();

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

    // Image handling
    $imagePath = 'uploads/products/prod_6a9f9995d87df.png'; // default fallback
    
    // If it's an update, preserve the existing image first
    if ($is_update) {
        $existingProduct = db_fetch($pdo, 'SELECT image FROM products WHERE id = :id', [':id' => $id]);
        if ($existingProduct) {
            $imagePath = $existingProduct['image'];
        }
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        $file_size = $_FILES['image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['image'] = 'Invalid image type. Only JPG, PNG, WEBP, and GIF are allowed.';
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
            $errors['image'] = 'Image size must be less than 5MB.';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            $upload_dir = __DIR__ . '/../uploads/products/';
            
            // Create dir if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $imagePath = 'uploads/products/' . $filename;
            } else {
                $errors['image'] = 'Failed to save uploaded image.';
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        
        $redirect = 'product_form.php' . ($is_update ? '?id=' . $id : '');
        header("Location: $redirect");
        exit;
    }

    try {
        if ($is_update) {
            $stmt = $pdo->prepare('
                UPDATE products
                SET    name = :name,
                       description = :description,
                       price = :price,
                       stock = :stock,
                       category = :category,
                       status = :status,
                       image = :image
                WHERE  id = :id
            ');
            $stmt->execute([
                ':name'        => $data['name'],
                ':description' => $data['description'],
                ':price'       => $data['price'],
                ':stock'       => $data['stock'],
                ':category'    => $data['category'],
                ':status'      => $data['status'],
                ':image'       => $imagePath,
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
                ':image'       => $imagePath,
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
