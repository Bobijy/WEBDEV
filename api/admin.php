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

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

// Require admin role — will 403 and exit otherwise
require_admin();

// ── Input Source ──
// POST for form submissions, raw JSON body for PUT/DELETE from JS fetch()
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$input = [];
if ($method === 'PUT' || $method === 'DELETE') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
    $action = $input['action'] ?? $action;
}

// ── Allowed Whitelists ──
const ALLOWED_CATEGORIES = ['Pour Homme', 'Pour Femme', 'Unisex', 'Uncategorized'];
const ALLOWED_STATUSES = ['Active', 'Draft'];
const ALLOWED_ORDER_STATUSES = ['Pending', 'Approved', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
const ALLOWED_ROLES = ['admin', 'customer'];

// ── Action Router ──
switch ($action) {

    // ─── PRODUCTS ───
    case 'get_products':
        try {
            $products = db_fetch_all($pdo, "SELECT * FROM products ORDER BY id DESC");
            json_response(true, '', ['products' => $products]);
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Failed to fetch products: ' . $e->getMessage());
            json_response(false, 'Failed to fetch products.');
        }
        break;

    case 'edit_product':
        if ($method !== 'POST') {
            json_response(false, 'Invalid method. Use POST for edit_product.');
        }

        // Validate CSRF
        if (!isset($_POST['csrf_token']) || !CSRF::verify($_POST['csrf_token'])) {
            json_response(false, 'Invalid security token. Please refresh the page.');
        }

        // Validate basic fields
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $status = trim($_POST['status'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!$id) {
            json_response(false, 'Invalid product ID.');
        }

        $errors = [];
        if (empty($name)) $errors['name'] = 'Product Name is required.';
        if (!in_array($category, ALLOWED_CATEGORIES)) $errors['category'] = 'Invalid category selected.';
        if ($price === false || $price < 0) $errors['price'] = 'Price must be a valid positive number.';
        if ($stock === false || $stock < 0) $errors['stock'] = 'Stock must be a valid positive integer.';
        if (!in_array($status, ALLOWED_STATUSES) && $status !== 'Hidden') $errors['status'] = 'Invalid status selected.';

        if (!empty($errors)) {
            json_response(false, 'Validation failed.', ['errors' => $errors]);
        }

        try {
            $pdo->beginTransaction();
            
            // Get current image to delete if a new one is uploaded
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentProduct) {
                $pdo->rollBack();
                json_response(false, 'Product not found.');
            }

            $currentImagePath = $currentProduct['image'];

            // Handle Image Upload
            $imagePath = $currentImagePath; // default to old image

            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['image']['tmp_name'];
                    $fileName = $_FILES['image']['name'];
                    $fileSize = $_FILES['image']['size'];
                    $fileType = mime_content_type($tmpName);

                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    $maxSize = 5 * 1024 * 1024; // 5MB

                    if (!in_array($fileType, $allowedTypes)) {
                        $pdo->rollBack();
                        json_response(false, 'Invalid image format. Only JPG, PNG, WEBP, and GIF are allowed.');
                    }
                    if ($fileSize > $maxSize) {
                        $pdo->rollBack();
                        json_response(false, 'Image size exceeds the 5MB limit.');
                    }

                    // Create unique filename
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid('prod_') . '.' . $ext;
                    $uploadDir = __DIR__ . '/../uploads/products/';
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $destination = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $destination)) {
                        $imagePath = 'uploads/products/' . $newFileName;
                        // Delete old image if exists and different
                        if ($currentImagePath && file_exists(__DIR__ . '/../' . $currentImagePath)) {
                            unlink(__DIR__ . '/../' . $currentImagePath);
                        }
                    } else {
                        $pdo->rollBack();
                        json_response(false, 'Failed to upload image. Please try again.');
                    }
                } else {
                    $pdo->rollBack();
                    json_response(false, 'Image upload error code: ' . $_FILES['image']['error']);
                }
            }

            // Update Database
            $sql = "UPDATE products 
                    SET name = :name, 
                        description = :description, 
                        category = :category, 
                        brand = :brand, 
                        price = :price, 
                        stock = :stock, 
                        status = :status, 
                        image = :image 
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':category' => $category,
                ':brand' => $brand,
                ':price' => $price,
                ':stock' => $stock,
                ':status' => $status,
                ':image' => $imagePath,
                ':id' => $id
            ]);

            $pdo->commit();
            json_response(true, 'Product updated successfully.');

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[Maison Ungod] Edit product failed: ' . $e->getMessage());
            json_response(false, 'Database error occurred while updating the product.');
        }
        break;

    // ─── DASHBOARD STATS ──
    case 'dashboard_stats':
        try {
            // Products summary
            $productRow = db_fetch($pdo, 'SELECT COUNT(*) AS c, COALESCE(SUM(stock), 0) AS s FROM products');

            // Orders grouped by status
            $orderRows = db_fetch_all($pdo, 'SELECT status, COUNT(*) AS c FROM orders GROUP BY status');
            $orderCounts = ['Pending' => 0, 'Approved' => 0, 'Processing' => 0, 'Shipped' => 0, 'Completed' => 0, 'Cancelled' => 0];
            foreach ($orderRows as $row) {
                if (array_key_exists($row['status'], $orderCounts)) {
                    $orderCounts[$row['status']] = (int) $row['c'];
                }
            }

            // Customer count
            $customerRow = db_fetch($pdo, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer'");

            // 10 most recent orders
            $recentOrders = db_fetch_all($pdo, '
                SELECT o.id, o.total_amount, o.status, o.created_at, o.payment_method, u.full_name, o.shipping_address, u.email,
                       (SELECT GROUP_CONCAT(CONCAT(p.name, " (x", oi.quantity, ")") SEPARATOR ", ") 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = o.id) AS products_list
                FROM   orders o
                JOIN   users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
                LIMIT 10
            ');

            // Sales data (last 7 days)
            $salesData = db_fetch_all($pdo, "
                SELECT DATE(created_at) as date, SUM(total_amount) as total
                FROM orders
                WHERE status NOT IN ('Cancelled', 'Pending') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");

            // Best Selling Products (Top 5)
            $bestSellers = db_fetch_all($pdo, "
                SELECT p.id, p.name, p.image, p.price, SUM(oi.quantity) as total_sold
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status NOT IN ('Cancelled', 'Pending')
                GROUP BY p.id
                ORDER BY total_sold DESC
                LIMIT 5
            ");

            // Notifications
            $lowStockProducts = db_fetch_all($pdo, "SELECT id, name, stock FROM products WHERE stock <= 10");
            $newOrdersToday = db_fetch_all($pdo, "SELECT id, created_at FROM orders WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC");

            $notifications = [];
            foreach ($lowStockProducts as $prod) {
                $notifications[] = [
                    'type' => 'low_stock',
                    'message' => 'Low stock for: ' . $prod['name'] . ' (' . $prod['stock'] . ' left)',
                    'time' => date('Y-m-d H:i:s')
                ];
            }
            foreach ($newOrdersToday as $ord) {
                $notifications[] = [
                    'type' => 'new_order',
                    'message' => 'New order received: #' . str_pad($ord['id'], 5, '0', STR_PAD_LEFT),
                    'time' => $ord['created_at']
                ];
            }


            json_response(true, '', [
                'stats' => [
                    'total_products' => (int) $productRow['c'],
                    'total_stock' => (int) $productRow['s'],
                    'pending_orders' => $orderCounts['Pending'],
                    'approved_orders' => $orderCounts['Approved'],
                    'processing_orders' => $orderCounts['Processing'],
                    'shipped_orders' => $orderCounts['Shipped'],
                    'completed_orders' => $orderCounts['Completed'],
                    'total_customers' => (int) $customerRow['c'],
                    'all_order_counts' => $orderCounts // raw counts for the doughnut chart
                ],
                'recent_orders' => $recentOrders,
                'sales_data' => $salesData,
                'best_sellers' => $bestSellers,
                'notifications' => $notifications
            ]);
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Dashboard stats failed: ' . $e->getMessage());
            json_response(false, 'Failed to load dashboard stats.');
        }
        break;
    // ─── SALES CHART ───
    case 'sales_chart':
        $range = $_GET['range'] ?? 'week';
        $interval = '6 DAY';
        if ($range === 'today')
            $interval = '0 DAY'; // Only today
        if ($range === 'month')
            $interval = '1 MONTH';
        if ($range === 'year')
            $interval = '1 YEAR';

        try {
            if ($range === 'today') {
                $query = "SELECT DATE_FORMAT(created_at, '%H:00') as label, SUM(total_amount) as total, COUNT(*) as orders
                          FROM orders WHERE status != 'Cancelled' AND DATE(created_at) = CURDATE()
                          GROUP BY HOUR(created_at) ORDER BY created_at ASC";
            } else {
                $query = "SELECT DATE(created_at) as label, SUM(total_amount) as total, COUNT(*) as orders
                          FROM orders WHERE status != 'Cancelled' AND created_at >= DATE_SUB(CURDATE(), INTERVAL $interval)
                          GROUP BY DATE(created_at) ORDER BY label ASC";
            }
            $data = db_fetch_all($pdo, $query);
            json_response(true, '', ['sales_data' => $data]);
        } catch (PDOException $e) {
            json_response(false, 'Failed to load sales chart.');
        }
        break;

    // ─── GLOBAL SEARCH ────
    case 'global_search':
        $q = sanitize_raw($_GET['q'] ?? '');
        if (strlen($q) < 2)
            json_response(true, '', ['results' => []]);

        $results = [];
        try {
            // Search Products
            $products = db_fetch_all($pdo, "SELECT id, name, price FROM products WHERE name LIKE :q LIMIT 3", [':q' => "%$q%"]);
            foreach ($products as $p)
                $results[] = ['type' => 'Product', 'id' => $p['id'], 'label' => $p['name'], 'url' => "products.php?id=" . $p['id']];

            // Search Orders
            $orders = db_fetch_all($pdo, "SELECT id, total_amount FROM orders WHERE id LIKE :q OR order_number LIKE :q LIMIT 3", [':q' => "%$q%"]);
            foreach ($orders as $o)
                $results[] = ['type' => 'Order', 'id' => $o['id'], 'label' => 'Order #' . str_pad($o['id'], 5, '0', STR_PAD_LEFT), 'url' => "orders.php?id=" . $o['id']];

            // Search Customers
            $users = db_fetch_all($pdo, "SELECT id, full_name FROM users WHERE full_name LIKE :q OR email LIKE :q LIMIT 3", [':q' => "%$q%"]);
            foreach ($users as $u)
                $results[] = ['type' => 'User', 'id' => $u['id'], 'label' => $u['full_name'], 'url' => "users.php?id=" . $u['id']];

            json_response(true, '', ['results' => $results]);
        } catch (PDOException $e) {
            json_response(false, 'Search failed.');
        }
        break;

    // ─── GET ALL ORDERS ───
    case 'get_orders':
        $statusFilter = $_GET['status'] ?? '';
        $sql = '
            SELECT o.*, u.full_name AS customer_name, u.email,
                   (SELECT GROUP_CONCAT(CONCAT(p.name, " (x", oi.quantity, ")") SEPARATOR ", ") 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = o.id) AS products_list
            FROM   orders o
            JOIN   users u ON o.user_id = u.id
        ';
        $params = [];
        
        if ($statusFilter && in_array($statusFilter, ALLOWED_ORDER_STATUSES)) {
            $sql .= ' WHERE o.status = :status ';
            $params[':status'] = $statusFilter;
        }
        
        $sql .= ' ORDER BY o.created_at DESC';
        
        $orders = db_fetch_all($pdo, $sql, $params);
        json_response(true, '', ['orders' => $orders]);
        break;

    // ─── GET SINGLE ORDER ───
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

    // ─── UPDATE ORDER STATUS ───
    case 'update_order_status':
        $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
        $status = sanitize_raw($input['status'] ?? $_POST['status'] ?? '');

        if (!validate_id($id)) {
            json_response(false, 'Invalid order ID.');
        }

        if (!validate_in_list($status, ALLOWED_ORDER_STATUSES)) {
            json_response(false, 'Invalid order status.');
        }

        try {
            // Get current order status
            $currentOrder = db_fetch($pdo, 'SELECT status FROM orders WHERE id = :id', [':id' => $id]);
            if (!$currentOrder) {
                json_response(false, 'Order not found.');
            }

            $pdo->beginTransaction();

            $affected = db_execute(
                $pdo,
                'UPDATE orders SET status = :status WHERE id = :id',
                [':status' => $status, ':id' => $id]
            );

            // Deduct stock if it was not Approved and now is Approved
            if ($currentOrder['status'] !== 'Approved' && $status === 'Approved') {
                $items = db_fetch_all($pdo, 'SELECT product_id, quantity FROM order_items WHERE order_id = :id', [':id' => $id]);
                $stmtStock = $pdo->prepare('UPDATE products SET stock = GREATEST(stock - :qty, 0) WHERE id = :id');
                foreach ($items as $item) {
                    $stmtStock->execute([
                        ':qty' => (int) $item['quantity'],
                        ':id'  => (int) $item['product_id'],
                    ]);
                }
            }

            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Maison Ungod] Update order status failed: ' . $e->getMessage());
            json_response(false, 'Failed to update order status. Please try again.');
        }


        json_response(true, 'Status updated to ' . $status . '.');
        break;

    // ─── GET ALL USERS ───
    case 'get_users':
        $users = db_fetch_all(
            $pdo,
            'SELECT id, full_name, email, phone, role, created_at FROM users ORDER BY created_at DESC'
        );
        json_response(true, '', ['users' => $users]);
        break;

    // ─── UPDATE USER ROLE ───
    case 'update_user_role':
        $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
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
            $affected = db_execute(
                $pdo,
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

    // ─── DEFAULT ─
    default:
        json_response(false, 'Invalid action.');
        break;
}
