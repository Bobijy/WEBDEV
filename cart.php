<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
$site = ['name' => 'Maison Ungod', 'year' => date('Y')];

// Fetch cart items for server-side rendering (used for JS fallback / SEO)
$items = [];
$total = 0;
$cartRows = db_fetch_all($pdo, '
    SELECT ci.id AS item_id, ci.quantity, p.name, p.price, p.image
    FROM   cart_items ci
    JOIN   carts c    ON ci.cart_id   = c.id
    JOIN   products p ON ci.product_id = p.id
    WHERE  c.user_id = :uid
', [':uid' => (int) $_SESSION['user_id']]);
foreach ($cartRows as $row) {
    $items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site['name'] ?> — Cart</title>
    <link rel="stylesheet" href="css/style.css?v=3">
    <link rel="stylesheet" href="css/animations.css?v=1">
    <link rel="stylesheet" href="css/pages/cart.css?v=1">
</head>
<body>
    <div class="cart-page">
        <a href="shop.php" class="back-link">← Continue Shopping</a>
        <h1>Your Bag</h1>
        
        <?php if (empty($items)): ?>
            <div style="text-align:center; padding: 50px 0;">
                <p style="color: #8A8A8A; margin-bottom:20px;">Your bag is currently empty.</p>
                <a href="shop.php" class="cart-checkout-btn">Shop Now</a>
            </div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th style="text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="cart-item-product">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div>
                                        <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                        <div style="color: #8A8A8A; font-size: 0.9rem;">$<?= number_format($item['price'], 2) ?></div>
                                        <button class="cart-item-remove" data-id="<?= $item['item_id'] ?>">Remove</button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="cart-qty-ctrl">
                                    <button class="qty-btn minus" data-id="<?= $item['item_id'] ?>" data-qty="<?= $item['quantity'] - 1 ?>">-</button>
                                    <span><?= $item['quantity'] ?></span>
                                    <button class="qty-btn plus" data-id="<?= $item['item_id'] ?>" data-qty="<?= $item['quantity'] + 1 ?>">+</button>
                                </div>
                            </td>
                            <td style="text-align:right; font-size:1.1rem;">
                                $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-summary">
                <p>Subtotal: <strong>$<?= number_format($total, 2) ?></strong></p>
                <p style="font-size:0.8rem; color:#8A8A8A; margin-bottom:20px;">Taxes and shipping calculated at checkout</p>
                <a href="checkout.php" class="cart-checkout-btn">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/cart-page.js?v=1"></script>
</body>
</html>
