<?php
/**
 * Maison Ungod — Admin API
 *
 * Protected endpoint for the Admin Dashboard.
 * Driver  : PDO with named parameters
 * Security: admin role guard + full product/order/user validation
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require admin role — will 403 and exit otherwise
require_admin();

// ── Input Source ─────────────────────────────────────────────────────────────
// POST for form submissions, raw JSON body for PUT/DELETE from JS fetch()
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$input = [];
if ($method === 'PUT' || $method === 'DELETE') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $action = $input['action'] ?? $action;
}

// ── Allowed Whitelists ────────────────────────────────────────────────────────
const ALLOWED_CATEGORIES = ['Pour Homme', 'Pour Femme', 'Unisex', 'Uncategorized'];
const ALLOWED_STATUSES   = ['Active', 'Draft'];
const ALLOWED_ORDER_STATUSES = ['Pending', 'Approved', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
const ALLOWED_ROLES      = ['admin', 'customer'];

// ── Helper: Extract Product Fields ───────────────────────────────────────────
/**
 * Read and validate product fields from POST or JSON body.
 * Returns an array of clean values, or calls json_response() on failure.
 */
function extract_product_fields(array $post, array $json): array {
    $name     = sanitize_raw($post['name']        ?? $json['name']        ?? '');
    $desc     = sanitize_raw($post['description'] ?? $json['description'] ?? '');
    $price    = $post['price']                     ?? $json['price']       ?? '';
    $stock    = $post['stock']                     ?? $json['stock']       ?? '';
    $category = sanitize_raw($post['category']    ?? $json['category']    ?? 'Uncategorized');
    $status   = sanitize_raw($post['status']      ?? $json['status']      ?? 'Active');

    // ── Validation ───────────────────────────────────────────────────────────

    if ($name === '') {
        json_response(false, 'Product name is required.');
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
        json_response(false, 'Product name must be between 2 and 255 characters.');
    }

    if (!validate_price($price)) {
        json_response(false, 'Price must be a positive number greater than zero.');
    }

    if (!validate_stock($stock)) {
        json_response(false, 'Stock must be a non-negative whole number.');
    }

    if (!validate_in_list($category, ALLOWED_CATEGORIES)) {
        json_response(false, 'Invalid category selected.');
    }

    if (!validate_in_list($status, ALLOWED_STATUSES)) {
        json_response(false, 'Invalid status selected.');
    }

    return [
        'name'        => $name,
        'description' => $desc,
        'price'       => (float) $price,
        'stock'       => (int) $stock,
        'category'    => $category,
        'status'      => $status,
    ];
}

// ── Action Router ─────────────────────────────────────────────────────────────
switch ($action) {

    // ─── DASHBOARD STATS ─────────────────────────────────────────────────────
    case 'dashboard_stats':
        try {
            // Products summary
            $productRow = db_fetch($pdo, 'SELECT COUNT(*) AS c, COALESCE(SUM(stock), 0) AS s FROM products');

            // Orders grouped by status
            $orderRows = db_fetch_all($pdo, 'SELECT status, COUNT(*) AS c FROM orders GROUP BY status');
            $orderCounts = ['Pending' => 0, 'Approved' => 0, 'Completed' => 0];
            foreach ($orderRows as $row) {
                if (array_key_exists($row['status'], $orderCounts)) {
                    $orderCounts[$row['status']] = (int) $row['c'];
                }
            }

            // Customer count
            $customerRow = db_fetch($pdo, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer'");

            // 5 most recent orders
            $recentOrders = db_fetch_all($pdo, '
                SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name
                FROM   orders o
                JOIN   users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
                LIMIT 5
            ');

            json_response(true, '', [
                'stats' => [
                    'total_products'   => (int) $productRow['c'],
                    'total_stock'      => (int) $productRow['s'],
                    'pending_orders'   => $orderCounts['Pending'],
                    'approved_orders'  => $orderCounts['Approved'],
                    'completed_orders' => $orderCounts['Completed'],
                    'total_customers'  => (int) $customerRow['c'],
                ],
                'recent_orders' => $recentOrders,
            ]);
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Dashboard stats failed: ' . $e->getMessage());
            json_response(false, 'Failed to load dashboard stats.');
        }
        break;

    // ─── GET ALL PRODUCTS ─────────────────────────────────────────────────────
    case 'get_products':
        $products = db_fetch_all($pdo, 'SELECT * FROM products ORDER BY id DESC');
        json_response(true, '', ['products' => $products]);
        break;

    // ─── ADD PRODUCT ──────────────────────────────────────────────────────────
    case 'add_product':
        $fields = extract_product_fields($_POST, $input);
        // Default image (no file upload in this system)
        $image = 'assets/images/default.png';

        try {
            $stmt = $pdo->prepare('
                INSERT INTO products (name, description, price, stock, category, status, image)
                VALUES (:name, :description, :price, :stock, :category, :status, :image)
            ');
            $stmt->execute([
                ':name'        => $fields['name'],
                ':description' => $fields['description'],
                ':price'       => $fields['price'],
                ':stock'       => $fields['stock'],
                ':category'    => $fields['category'],
                ':status'      => $fields['status'],
                ':image'       => $image,
            ]);
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Add product failed: ' . $e->getMessage());
            json_response(false, 'Failed to add product. Please try again.');
        }

        json_response(true, 'Product added successfully.');
        break;

    // ─── UPDATE PRODUCT ───────────────────────────────────────────────────────
    case 'update_product':
        $id = (int) ($_POST['id'] ?? $input['id'] ?? 0);

        if (!validate_id($id)) {
            json_response(false, 'Invalid product ID.');
        }

        $fields = extract_product_fields($_POST, $input);

        // Confirm the product actually exists
        $existing = db_fetch($pdo, 'SELECT id FROM products WHERE id = :id', [':id' => $id]);
        if (!$existing) {
            json_response(false, 'Product not found.');
        }

        try {
            db_execute($pdo, '
                UPDATE products
                SET    name = :name,
                       description = :description,
                       price = :price,
                       stock = :stock,
                       category = :category,
                       status = :status
                WHERE  id = :id
            ', [
                ':name'        => $fields['name'],
                ':description' => $fields['description'],
                ':price'       => $fields['price'],
                ':stock'       => $fields['stock'],
                ':category'    => $fields['category'],
                ':status'      => $fields['status'],
                ':id'          => $id,
            ]);
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Update product failed: ' . $e->getMessage());
            json_response(false, 'Failed to update product. Please try again.');
        }

        json_response(true, 'Product updated successfully.');
        break;

    // ─── DELETE PRODUCT ───────────────────────────────────────────────────────
    case 'delete_product':
        $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);

        if (!validate_id($id)) {
            json_response(false, 'Invalid product ID.');
        }

        try {
            $affected = db_execute($pdo,
                'DELETE FROM products WHERE id = :id',
                [':id' => $id]
            );
            if ($affected === 0) {
                json_response(false, 'Product not found.');
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Delete product failed: ' . $e->getMessage());
            json_response(false, 'Failed to delete product. Please try again.');
        }

        json_response(true, 'Product deleted successfully.');
        break;

    // ─── GET ALL ORDERS ───────────────────────────────────────────────────────
    case 'get_orders':
        $orders = db_fetch_all($pdo, '
            SELECT o.*, u.full_name AS customer_name, u.email
            FROM   orders o
            JOIN   users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ');
        json_response(true, '', ['orders' => $orders]);
        break;

    // ─── GET SINGLE ORDER ─────────────────────────────────────────────────────
    case 'get_order':
        $id = (int) ($_GET['id'] ?? 0);

        if (!validate_id($id)) {
            json_response(false, 'Invalid order ID.');
        }

        $order = db_fetch($pdo, '
            SELECT o.*, u.full_name AS customer_name, u.email, u.phone
            FROM   orders o
            JOIN   users u ON o.user_id = u.id
            WHERE  o.id = :id
        ', [':id' => $id]);

        if (!$order) {
            json_response(false, 'Order not found.');
        }

        $items = db_fetch_all($pdo, '
            SELECT oi.*, p.name, p.image
            FROM   order_items oi
            JOIN   products p ON oi.product_id = p.id
            WHERE  oi.order_id = :order_id
        ', [':order_id' => $id]);

        json_response(true, '', ['order' => $order, 'items' => $items]);
        break;

    // ─── UPDATE ORDER STATUS ──────────────────────────────────────────────────
    case 'update_order_status':
        $id     = (int) ($input['id']     ?? $_POST['id']     ?? 0);
        $status = sanitize_raw($input['status'] ?? $_POST['status'] ?? '');

        if (!validate_id($id)) {
            json_response(false, 'Invalid order ID.');
        }

        if (!validate_in_list($status, ALLOWED_ORDER_STATUSES)) {
            json_response(false, 'Invalid order status.');
        }

        try {
            $affected = db_execute($pdo,
                'UPDATE orders SET status = :status WHERE id = :id',
                [':status' => $status, ':id' => $id]
            );
            if ($affected === 0) {
                json_response(false, 'Order not found.');
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Update order status failed: ' . $e->getMessage());
            json_response(false, 'Failed to update order status. Please try again.');
        }

        json_response(true, 'Status updated to ' . $status . '.');
        break;

    // ─── GET ALL USERS ────────────────────────────────────────────────────────
    case 'get_users':
        $users = db_fetch_all($pdo,
            'SELECT id, full_name, email, phone, role, created_at FROM users ORDER BY created_at DESC'
        );
        json_response(true, '', ['users' => $users]);
        break;

    // ─── UPDATE USER ROLE ─────────────────────────────────────────────────────
    case 'update_user_role':
        $id   = (int) ($input['id']   ?? $_POST['id']   ?? 0);
        $role = sanitize_raw($input['role'] ?? $_POST['role'] ?? '');

        if (!validate_id($id)) {
            json_response(false, 'Invalid user ID.');
        }

        // Admins cannot demote themselves via this endpoint
        if ($id === (int) $_SESSION['user_id']) {
            json_response(false, 'You cannot change your own role.');
        }

        if (!validate_in_list($role, ALLOWED_ROLES)) {
            json_response(false, 'Invalid role. Must be "admin" or "customer".');
        }

        try {
            $affected = db_execute($pdo,
                'UPDATE users SET role = :role WHERE id = :id',
                [':role' => $role, ':id' => $id]
            );
            if ($affected === 0) {
                json_response(false, 'User not found.');
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Update user role failed: ' . $e->getMessage());
            json_response(false, 'Failed to update user role. Please try again.');
        }

        json_response(true, 'User role updated to ' . $role . '.');
        break;

    // ─── DEFAULT ──────────────────────────────────────────────────────────────
    default:
        json_response(false, 'Invalid action.');
        break;
}
