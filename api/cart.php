<?php
// Cart API: handles fetch, add, update, and remove actions

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

// Cart actions require a logged-in user
require_auth();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get the user's cart ID, or create one if it does not exist
function get_or_create_cart(PDO $pdo, int $userId): int {
    $cart = db_fetch($pdo, 'SELECT id FROM carts WHERE user_id = :uid', [':uid' => $userId]);

    if ($cart) {
        return (int) $cart['id'];
    }

    // No cart yet — create a new one
    db_execute($pdo, 'INSERT INTO carts (user_id) VALUES (:uid)', [':uid' => $userId]);
    return (int) $pdo->lastInsertId();
}

$cartId = get_or_create_cart($pdo, $userId);

// Handle cart actions
switch ($action) {

    // Return all items in the user's cart
    case 'fetch':
        $items = db_fetch_all($pdo, '
            SELECT ci.id        AS item_id,
                   ci.quantity,
                   p.name,
                   p.price,
                   p.image
            FROM   cart_items ci
            JOIN   products p ON ci.product_id = p.id
            WHERE  ci.cart_id = :cart_id
        ', [':cart_id' => $cartId]);

        // Calculate total price server-side
        $total = array_reduce($items, fn($carry, $row) => $carry + ($row['price'] * $row['quantity']), 0.0);

        json_response(true, '', ['items' => $items, 'total' => $total]);
        break;

    // Add a product to the cart (or increment quantity if already added)
    case 'add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty       = (int) ($_POST['quantity']   ?? 1);

        // Validate product ID and quantity
        if (!validate_id($productId)) {
            json_response(false, 'Invalid product.');
        }

        if (!validate_quantity($qty)) {
            json_response(false, 'Quantity must be at least 1.');
        }

        // Check if the product exists and is active
        $product = db_fetch(
            $pdo,
            'SELECT id FROM products WHERE id = :id AND status = :status',
            [':id' => $productId, ':status' => 'Active']
        );
        if (!$product) {
            json_response(false, 'Product not found or unavailable.');
        }

        // Check if item is already in the cart
        $existing = db_fetch(
            $pdo,
            'SELECT id, quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id',
            [':cart_id' => $cartId, ':product_id' => $productId]
        );

        try {
            if ($existing) {
                $newQty = $existing['quantity'] + $qty;
                db_execute($pdo,
                    'UPDATE cart_items SET quantity = :qty WHERE id = :id',
                    [':qty' => $newQty, ':id' => (int) $existing['id']]
                );
            } else {
                db_execute($pdo,
                    'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :qty)',
                    [':cart_id' => $cartId, ':product_id' => $productId, ':qty' => $qty]
                );
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Cart add failed: ' . $e->getMessage());
            json_response(false, 'Failed to add item. Please try again.');
        }

        json_response(true, 'Item added to cart.');
        break;

    // Update item quantity
    case 'update':
        $itemId = (int) ($_POST['item_id']  ?? 0);
        $qty    = (int) ($_POST['quantity'] ?? 0);

        if (!validate_id($itemId)) {
            json_response(false, 'Invalid cart item.');
        }

        try {
            if ($qty <= 0) {
                // If quantity is 0 or less, remove the item
                db_execute($pdo,
                    'DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id',
                    [':id' => $itemId, ':cart_id' => $cartId]
                );
            } else {
                db_execute($pdo,
                    'UPDATE cart_items SET quantity = :qty WHERE id = :id AND cart_id = :cart_id',
                    [':qty' => $qty, ':id' => $itemId, ':cart_id' => $cartId]
                );
            }
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Cart update failed: ' . $e->getMessage());
            json_response(false, 'Failed to update item. Please try again.');
        }

        json_response(true, 'Cart updated.');
        break;

    // Remove an item from the cart
    case 'remove':
        $itemId = (int) ($_POST['item_id'] ?? 0);

        if (!validate_id($itemId)) {
            json_response(false, 'Invalid cart item.');
        }

        try {
            // Only remove if the item belongs to the user's cart
            db_execute($pdo,
                'DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id',
                [':id' => $itemId, ':cart_id' => $cartId]
            );
        } catch (PDOException $e) {
            error_log('[Maison Ungod] Cart remove failed: ' . $e->getMessage());
            json_response(false, 'Failed to remove item. Please try again.');
        }

        json_response(true, 'Item removed from cart.');
        break;

    default:
        json_response(false, 'Invalid action.');
        break;
}

