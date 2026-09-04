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
