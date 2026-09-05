<?php
/**
 * Maison Ungod — Checkout API
 *
 * Accepts the final order submission from the checkout form.
 * Driver  : PDO with named parameters
 * Security: session guard, payment method whitelist, phone/address validation
 */

// session_start() must be first — before any output or require
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

// Require a valid session
require_auth();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

// ── Read & Sanitize Inputs ────────────────────────────────────────────────────
$selectedAddressId = sanitize_raw($_POST['selected_address_id'] ?? 'new');
$address       = sanitize_raw($_POST['address']        ?? '');
$phone         = sanitize_raw($_POST['phone']          ?? '');
$paymentMethod = sanitize_raw($_POST['payment_method'] ?? '');

if ($selectedAddressId !== 'new') {
    $savedAddr = db_fetch($pdo, 'SELECT * FROM user_addresses WHERE id = :id AND user_id = :uid', [
        ':id' => (int) $selectedAddressId,
        ':uid' => $userId
    ]);
    if ($savedAddr) {
        $address = $savedAddr['address_line'];
        $phone = $savedAddr['phone'];
    } else {
        json_response(false, 'Selected address not found.');
    }
}

// ── Allowed Payment Methods (whitelist) ──────────────────────────────────────
const ALLOWED_PAYMENT_METHODS = ['Card', 'Cash On Delivery'];

// ── Validation ────────────────────────────────────────────────────────────────

// Address — required, min 10 chars, max 500 chars
if ($address === '') {
    json_response(false, 'Shipping address is required.');
}
if (mb_strlen($address) < 10 && $selectedAddressId === 'new') {
    json_response(false, 'Please enter a complete shipping address (at least 10 characters).');
}
if (mb_strlen($address) > 500) {
    json_response(false, 'Shipping address is too long (maximum 500 characters).');
}

// Phone — required and must match valid format
if ($phone === '') {
    json_response(false, 'Phone number is required.');
}
if (!validate_phone($phone)) {
    json_response(false, 'Please enter a valid phone number (e.g., 09XX-XXX-XXXX or +63 9XX).');
}

// Payment method — must be one of the approved options
if (!validate_in_list($paymentMethod, ALLOWED_PAYMENT_METHODS)) {
    json_response(false, 'Invalid payment method selected.');
}

// ── Fetch Cart ────────────────────────────────────────────────────────────────
$cart = db_fetch($pdo, 'SELECT id FROM carts WHERE user_id = :uid', [':uid' => $userId]);

if (!$cart) {
    json_response(false, 'Cart not found. Please add items and try again.');
}
$cartId = (int) $cart['id'];

// ── Fetch Cart Items + Compute Total Server-Side ──────────────────────────────
// Prices are read from the database — never trusted from POST data.
$items = db_fetch_all($pdo, '
    SELECT ci.product_id,
           ci.quantity,
           p.price,
           p.stock
    FROM   cart_items ci
    JOIN   products p ON ci.product_id = p.id
    WHERE  ci.cart_id = :cart_id
', [':cart_id' => $cartId]);

if (empty($items)) {
    json_response(false, 'Your cart is empty.');
}

// Calculate total entirely from DB prices
$subtotal = array_reduce(
    $items,
    fn($carry, $row) => $carry + ((float)$row['price'] * (int)$row['quantity']),
    0.0
);

$shippingFee = 50.00;
$totalAmount = $subtotal + $shippingFee;

// ── Transaction: Create Order + Order Items + Deduct Stock + Clear Cart ───────
try {
    $pdo->beginTransaction();

    // 1. Save/update the user's shipping details for future orders
    db_execute($pdo,
        'UPDATE users SET address = :address, phone = :phone WHERE id = :id',
        [':address' => $address, ':phone' => $phone, ':id' => $userId]
    );

    // 2. Create the order record
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
    
    $stmt = $pdo->prepare('
        INSERT INTO orders (order_number, user_id, total_amount, shipping_fee, shipping_address, payment_method, status)
        VALUES (:order_number, :user_id, :total_amount, :shipping_fee, :shipping_address, :payment_method, :status)
    ');
    $stmt->execute([
        ':order_number'     => $orderNumber,
        ':user_id'          => $userId,
        ':total_amount'     => $totalAmount,
        ':shipping_fee'     => $shippingFee,
        ':shipping_address' => $address,
        ':payment_method'   => $paymentMethod,
        ':status'           => 'Pending',
    ]);
    $orderId = (int) $pdo->lastInsertId();

    // 3. Insert order items and deduct stock for each product
    $stmtItem  = $pdo->prepare('
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ');
    $stmtStock = $pdo->prepare('
        UPDATE products
        SET    stock = GREATEST(stock - :qty, 0)
        WHERE  id = :id
    ');

    foreach ($items as $item) {
        $stmtItem->execute([
            ':order_id'  => $orderId,
            ':product_id'=> (int) $item['product_id'],
            ':quantity'  => (int) $item['quantity'],
            ':price'     => (float) $item['price'],
        ]);

        $stmtStock->execute([
            ':qty' => (int) $item['quantity'],
            ':id'  => (int) $item['product_id'],
        ]);
    }

    // 4. Clear the cart after successful order
    db_execute($pdo,
        'DELETE FROM cart_items WHERE cart_id = :cart_id',
        [':cart_id' => $cartId]
    );

    $pdo->commit();

    json_response(true, 'Order placed successfully!', ['order_id' => $orderId]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[Maison Ungod] Checkout transaction failed: ' . $e->getMessage());
    json_response(false, 'Failed to place order. Please try again.');
}
