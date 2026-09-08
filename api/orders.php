<?php
// Orders API: handles customer order actions like cancellation

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';
require_auth();

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

switch ($action) {
    case 'cancel_order':
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            json_response(false, 'Invalid order ID.');
        }

        // Fetch order to verify ownership and status
        $order = db_fetch($pdo, 'SELECT status FROM orders WHERE id = :id AND user_id = :uid', [
            ':id'  => $orderId,
            ':uid' => $userId
        ]);

        if (!$order) {
            json_response(false, 'Order not found or unauthorized.');
        }

        if ($order['status'] !== 'Pending') {
            json_response(false, 'Only pending orders can be cancelled.');
        }

        try {
            $pdo->beginTransaction();

            // 1. Update order status
            db_execute($pdo, "UPDATE orders SET status = 'Cancelled' WHERE id = :id", [
                ':id' => $orderId
            ]);

            // 2. Fetch items to restock
            $items = db_fetch_all($pdo, 'SELECT product_id, quantity FROM order_items WHERE order_id = :oid', [
                ':oid' => $orderId
            ]);

            // 3. Restock products
            $stmtStock = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
            foreach ($items as $item) {
                $stmtStock->execute([
                    ':qty' => (int) $item['quantity'],
                    ':id'  => (int) $item['product_id']
                ]);
            }

            $pdo->commit();
            json_response(true, 'Order cancelled successfully.');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[Maison Ungod] Failed to cancel order: ' . $e->getMessage());
            json_response(false, 'Failed to cancel order.');
        }
        break;

    default:
        json_response(false, 'Invalid action.');
}
