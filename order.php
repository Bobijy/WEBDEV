<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: account.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$order_id = (int) $_GET['id'];

// Fetch the order — only if it belongs to the logged-in user (ownership guard)
$order = db_fetch($pdo, '
    SELECT o.*, u.email
    FROM   orders o
    JOIN   users u ON o.user_id = u.id
    WHERE  o.id = :id AND o.user_id = :user_id
', [':id' => $order_id, ':user_id' => (int) $_SESSION['user_id']]);

if (!$order) {
    header("Location: account.php");
    exit;
}

// Fetch order items
$items = db_fetch_all($pdo, '
    SELECT oi.*, p.name, p.image
    FROM   order_items oi
    JOIN   products p ON oi.product_id = p.id
    WHERE  oi.order_id = :order_id
', [':order_id' => $order_id]);

$site            = ['name' => 'Maison Ungod'];
$order_date      = date('M j', strtotime($order['created_at']));
$formatted_total = number_format($order['total_amount'], 2);

// Compute included tax (12%) consistent with checkout.php
$tax_rate        = 0.12;
$tax_amount      = $order['total_amount'] * ($tax_rate / (1 + $tax_rate)); // extract tax from VAT-inclusive total
$formatted_tax   = number_format($tax_amount, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> — Order <?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/pages/order.css?v=1">
</head>
<body>

<div class="container">
    
    <!-- Header -->
    <div class="order-header">
        <div>
            <a href="account.php#orders" class="order-header-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <h1>Order <?= $order_id ?></h1>
            </a>
            <div class="order-date">Confirmed <?= $order_date ?></div>
        </div>
        <a href="shop.php" class="btn-buy-again">Buy again</a>
    </div>
    
    <!-- Status Banners -->
    <div class="alert-banner">
        <h3>Payment <?= strtolower(htmlspecialchars($order['status'])) ?></h3>
        <?php if (strtolower($order['status']) === 'canceled'): ?>
            <p>You were not charged.</p>
        <?php else: ?>
            <p>Your payment was processed securely.</p>
        <?php endif; ?>
    </div>
    
    <div class="alert-banner">
        <h3>
            <?php if (strtolower($order['status']) === 'canceled'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <?php endif; ?>
            <?= htmlspecialchars($order['status']) ?>
        </h3>
        <p>Your order has been <?= strtolower(htmlspecialchars($order['status'])) ?></p>
        <p style="margin-top: 4px;"><?= $order_date ?></p>
    </div>
    
    <!-- Order Items Box -->
    <div class="order-box">
        <?php foreach ($items as $item): ?>
            <div class="order-item">
                <div class="item-thumb-wrapper">
                    <div class="item-thumb">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="item-qty"><?= $item['quantity'] ?></div>
                </div>
                <div class="item-details">
                    <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                </div>
                <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
            </div>
        <?php endforeach; ?>
        
        <div class="summary-line" style="margin-top: 32px;">
            <span>Subtotal</span>
            <span>₱<?= $formatted_total ?></span>
        </div>
        <div class="summary-line">
            <span>Shipping</span>
            <span>Free</span>
        </div>
        
        <div class="summary-line total">
            <span>Total</span>
            <div><span class="currency">PHP</span> <span class="total-price">₱<?= $formatted_total ?></span></div>
        </div>
        <p class="tax-note">Including ₱<?= $formatted_tax ?> in taxes</p>
    </div>
    
    <!-- Information Grid -->
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Contact</div>
            <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Ship to</div>
            <div class="info-value">
                <?php 
                    $address_lines = explode(',', $order['shipping_address']);
                    foreach ($address_lines as $line) {
                        echo '<p>' . htmlspecialchars(trim($line)) . '</p>';
                    }
                ?>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">Method</div>
            <div class="info-value">Shipping Fee Nationwide</div>
        </div>
        <div class="info-row">
            <div class="info-label">Payment</div>
            <div class="info-value">
                <p><?= htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') ?></p>
                <p style="color: var(--text-light); font-size: 0.9rem;">₱<?= $formatted_total ?> PHP · <?= $order_date ?></p>
            </div>
        </div>
    </div>
    
</div>

</body>
</html>
