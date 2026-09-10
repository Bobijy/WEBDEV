<?php
// Checkout API: processes order placement, saves order items, and empties cart

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

// User must be logged in to checkout
require_auth();

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

// Read input values
$selectedAddressId = sanitize_raw($_POST['selected_address_id'] ?? 'new');
$address       = sanitize_raw($_POST['address']        ?? '');
$phone         = sanitize_raw($_POST['phone']          ?? '');
$paymentMethod = sanitize_raw($_POST['payment_method'] ?? '');

// If user selected a saved address, retrieve it from the database
if ($selectedAddressId !== 'new') {
    $savedAddr = db_fetch($pdo, 'SELECT * FROM user_addresses WHERE id = :id AND user_id = :uid', [
        ':id' => (int) $selectedAddressId,
        ':uid' => $userId
    ]);
    if ($savedAddr) {
        $address = $savedAddr['address_line'] . (!empty($savedAddr['postal_code']) ? ', ' . $savedAddr['postal_code'] : '');
        $phone = $savedAddr['phone'];
    } else {
        json_response(false, 'Selected address not found.');
    }
}

// Allowed payment methods
const ALLOWED_PAYMENT_METHODS = ['Card', 'Cash On Delivery'];

// Validate address
if ($address === '') {
    json_response(false, 'Shipping address is required.');
}
if (mb_strlen($address) < 10 && $selectedAddressId === 'new') {
    json_response(false, 'Please enter a complete shipping address (at least 10 characters).');
}
if (mb_strlen($address) > 500) {
    json_response(false, 'Shipping address is too long (maximum 500 characters).');
}

// Validate phone number
if ($phone === '') {
    json_response(false, 'Phone number is required.');
}
if (!validate_phone($phone)) {
    json_response(false, 'Please enter a valid phone number (e.g., 09XX-XXX-XXXX or +63 9XX).');
}

// Validate payment method
if (!validate_in_list($paymentMethod, ALLOWED_PAYMENT_METHODS)) {
    json_response(false, 'Invalid payment method selected.');
}

if ($paymentMethod === 'Card') {
    $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cardExp    = trim($_POST['card_exp'] ?? '');
    $cardSec    = trim($_POST['card_sec'] ?? '');
    $cardName   = trim($_POST['card_name'] ?? '');

    $cardErrors = [];

    if ($cardNumber === '') {
        $cardErrors['card_number'] = 'Card number is required.';
    } elseif (strlen($cardNumber) < 15 || strlen($cardNumber) > 16) {
        $cardErrors['card_number'] = 'Please enter a valid 15 or 16-digit card number.';
    }

    if ($cardExp === '') {
        $cardErrors['card_exp'] = 'Expiration date is required.';
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $cardExp, $m)) {
        $cardErrors['card_exp'] = 'Please enter a valid expiration date (MM / YY).';
    } else {
        $expMonth  = (int) $m[1];
        $expYear   = 2000 + (int) $m[2];
        $currYear  = (int) date('Y');
        $currMonth = (int) date('n');

        if ($expYear < $currYear || ($expYear === $currYear && $expMonth < $currMonth)) {
            $cardErrors['card_exp'] = 'Card has expired.';
        }
    }

    if ($cardSec === '') {
        $cardErrors['card_sec'] = 'Security code is required.';
    } elseif (!preg_match('/^\d{3,4}$/', $cardSec)) {
        $cardErrors['card_sec'] = 'Security code must be 3 or 4 digits.';
    }

    if ($cardName === '') {
        $cardErrors['card_name'] = 'Name on card is required.';
    } elseif (mb_strlen($cardName) < 2) {
        $cardErrors['card_name'] = 'Please enter the full name on the card.';
    }

    if (!empty($cardErrors)) {
        json_response(false, 'Please fill in all required card details.', ['errors' => $cardErrors]);
    }
}

// Fetch the user's cart
$cart = db_fetch($pdo, 'SELECT id FROM carts WHERE user_id = :uid', [':uid' => $userId]);

if (!$cart) {
    json_response(false, 'Cart not found. Please add items and try again.');
}
$cartId = (int) $cart['id'];

// Fetch cart items and verify cart is not empty
$items = db_fetch_all($pdo, '
    SELECT ci.product_id,
           ci.quantity,
           p.name,
           p.price,
           p.stock
    FROM   cart_items ci
    JOIN   products p ON ci.product_id = p.id
    WHERE  ci.cart_id = :cart_id
', [':cart_id' => $cartId]);

if (empty($items)) {
    json_response(false, 'Your cart is empty.');
}

// Calculate subtotal using database prices
$subtotal = array_reduce(
    $items,
    fn($carry, $row) => $carry + ((float)$row['price'] * (int)$row['quantity']),
    0.0
);

$shippingFee = 50.00;
$totalAmount = $subtotal + $shippingFee;

// Prepare Card & Transaction metadata
$cardLast4     = null;
$cardBrand     = null;
$cardHolder    = null;
$transactionId = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

if ($paymentMethod === 'Card') {
    $cardLast4 = substr($cardNumber, -4);
    if (str_starts_with($cardNumber, '4')) {
        $cardBrand = 'Visa';
    } elseif (str_starts_with($cardNumber, '5') || str_starts_with($cardNumber, '2')) {
        $cardBrand = 'Mastercard';
    } elseif (str_starts_with($cardNumber, '3')) {
        $cardBrand = 'American Express';
    } else {
        $cardBrand = 'Card';
    }
    $cardHolder = $cardName;
}

// Fetch user info for receipt
$userInfo = db_fetch($pdo, 'SELECT email, full_name FROM users WHERE id = :id', [':id' => $userId]);

// Create order transaction
try {
    $pdo->beginTransaction();

    // 1. Update user shipping info
    db_execute($pdo,
        'UPDATE users SET address = :address, phone = :phone WHERE id = :id',
        [':address' => $address, ':phone' => $phone, ':id' => $userId]
    );

    // 2. Insert new order record
    $orderNumber = 'ORD-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));
    
    $stmt = $pdo->prepare('
        INSERT INTO orders (order_number, user_id, total_amount, shipping_fee, shipping_address, payment_method, status, card_last4, card_brand, card_name, transaction_id)
        VALUES (:order_number, :user_id, :total_amount, :shipping_fee, :shipping_address, :payment_method, :status, :card_last4, :card_brand, :card_name, :transaction_id)
    ');
    $stmt->execute([
        ':order_number'     => $orderNumber,
        ':user_id'          => $userId,
        ':total_amount'     => $totalAmount,
        ':shipping_fee'     => $shippingFee,
        ':shipping_address' => $address,
        ':payment_method'   => $paymentMethod,
        ':status'           => 'Pending',
        ':card_last4'       => $cardLast4,
        ':card_brand'       => $cardBrand,
        ':card_name'        => $cardHolder,
        ':transaction_id'   => $transactionId,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    // 3. Insert order items
    $stmtItem  = $pdo->prepare('
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (:order_id, :product_id, :quantity, :price)
    ');

    $receiptItems = [];
    foreach ($items as $item) {
        $stmtItem->execute([
            ':order_id'  => $orderId,
            ':product_id'=> (int) $item['product_id'],
            ':quantity'  => (int) $item['quantity'],
            ':price'     => (float) $item['price'],
        ]);

        $receiptItems[] = [
            'name'     => $item['name'] ?? 'Perfume',
            'quantity' => (int) $item['quantity'],
            'price'    => (float) $item['price'],
            'total'    => (float) $item['price'] * (int) $item['quantity']
        ];
    }

    // 4. Empty the user's cart
    db_execute($pdo,
        'DELETE FROM cart_items WHERE cart_id = :cart_id',
        [':cart_id' => $cartId]
    );

    $pdo->commit();

    $receiptData = [
        'order_id'         => $orderId,
        'order_number'     => $orderNumber,
        'transaction_id'   => $transactionId,
        'payment_method'   => $paymentMethod,
        'card_brand'       => $cardBrand ?: 'Card',
        'card_last4'       => $cardLast4 ?: '••••',
        'card_name'        => $cardHolder ?: ($userInfo['full_name'] ?? 'Valued Customer'),
        'date'             => date('M j, Y h:i A'),
        'customer_name'    => $cardHolder ?: ($userInfo['full_name'] ?? 'Valued Customer'),
        'customer_email'   => $userInfo['email'] ?? '',
        'shipping_address' => $address,
        'phone'            => $phone,
        'items'            => $receiptItems,
        'subtotal'         => $subtotal,
        'shipping_fee'     => $shippingFee,
        'tax_amount'       => $subtotal * 0.12,
        'total_amount'     => $totalAmount,
    ];

    json_response(true, 'Order placed successfully!', [
        'order_id' => $orderId,
        'receipt'  => $receiptData
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[Maison Ungod] Checkout transaction failed: ' . $e->getMessage());
    json_response(false, 'Failed to place order. Please try again.');
}

