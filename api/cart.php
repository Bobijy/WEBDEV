<?php
/**
 * Maison Ungod — Cart API
 *
 * Actions : fetch, add, update, remove
 * Driver  : PDO with named parameters
 * Security: session guard + input validation on all mutable actions
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Require a valid session — cart actions are never public
require_auth();

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Cart Retrieval / Creation ─────────────────────────────────────────────────
/**
 * Return the cart ID for the given user.
 * Creates a new cart record if one does not exist.
 *
 * @param  PDO $pdo
 * @param  int $userId
 * @return int Cart ID
 */
function get_or_create_cart(PDO $pdo, int $userId): int {
    $cart = db_fetch($pdo, 'SELECT id FROM carts WHERE user_id = :uid', [':uid' => $userId]);

    if ($cart) {
        return (int) $cart['id'];
    }

    // No cart yet — create one
    db_execute($pdo, 'INSERT INTO carts (user_id) VALUES (:uid)', [':uid' => $userId]);
    return (int) $pdo->lastInsertId();
}

$cartId = get_or_create_cart($pdo, $userId);

// ── Action Router ─────────────────────────────────────────────────────────────
switch ($action) {

    // ═══════════════════════
    // FETCH — return all items in the cart
    // ═══════════════════════
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

        // Compute total server-side — never trust the client's total
        $total = array_reduce($items, fn($carry, $row) => $carry + ($row['price'] * $row['quantity']), 0.0);

        json_response(true, '', ['items' => $items, 'total' => $total]);
        break;

    // ═══════════════════════
    // ADD — add a product to the cart (or increase quantity)
    // ═══════════════════════
    case 'add':
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty       = (int) ($_POST['quantity']   ?? 1);

        // Validate product ID
        if (!validate_id($productId)) {
            json_response(false, 'Invalid product.');
        }

        // Validate quantity (must be at least 1)
        if (!validate_quantity($qty)) {
            json_response(false, 'Quantity must be at least 1.');
        }

        // Verify the product actually exists and is Active
        $product = db_fetch(
            $pdo,
            'SELECT id FROM products WHERE id = :id AND status = :status',
            [':id' => $productId, ':status' => 'Active']
        );
        if (!$product) {
            json_response(false, 'Product not found or unavailable.');
        }

        // Insert or increment quantity if already in cart
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

    // ═══════════════════════
    // UPDATE — change quantity of a cart item
    // ═══════════════════════
    case 'update':
        $itemId = (int) ($_POST['item_id']  ?? 0);
        $qty    = (int) ($_POST['quantity'] ?? 0);

        // Validate item ID
        if (!validate_id($itemId)) {
            json_response(false, 'Invalid cart item.');
        }

        try {
            if ($qty <= 0) {
                // Quantity zero or below — remove the item entirely
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

    // ═══════════════════════
    // REMOVE — delete a cart item
    // ═══════════════════════
    case 'remove':
        $itemId = (int) ($_POST['item_id'] ?? 0);

        // Validate item ID
        if (!validate_id($itemId)) {
            json_response(false, 'Invalid cart item.');
        }

        try {
            // cart_id guard ensures users can only remove their own items
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

    // ═══════════════════════
    // DEFAULT
    // ═══════════════════════
    default:
        json_response(false, 'Invalid action.');
        break;
}
