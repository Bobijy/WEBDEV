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

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/helpers.php';

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
$order_date      = date('M j, Y', strtotime($order['created_at']));
$shipping_fee    = isset($order['shipping_fee']) ? (float)$order['shipping_fee'] : 0.00;
$total_amount    = (float)$order['total_amount'];
$subtotal        = $total_amount - $shipping_fee;

$formatted_subtotal = number_format($subtotal, 2);
$formatted_shipping = number_format($shipping_fee, 2);
$formatted_total    = number_format($total_amount, 2);

// Compute included tax (12%)
$tax_rate        = 0.12;
$tax_amount      = $subtotal * $tax_rate;
$formatted_tax   = number_format($tax_amount, 2);

$is_canceled = (strtolower($order['status']) === 'canceled');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> — Order <?= $order_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/pages/order.css?v=4">
</head>
<body>

<div class="container">
    
    <!-- Top Nav with Logo -->
    <div class="top-nav">
        <a href="index.php">
            <img src="assets/logo/logo.png" alt="<?= $site['name'] ?>">
        </a>
    </div>

    <!-- Header -->
    <div class="order-header">
        <div>
            <a href="account.php#orders" class="order-header-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                <h1>Order <?= $order_id ?></h1>
            </a>
            <div class="order-date">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Confirmed <?= $order_date ?>
            </div>
        </div>
    </div>
    
    <!-- Status Cards -->
    <div class="status-cards">
        <div class="status-card">
            <div class="status-icon">
                <?php if ($is_canceled): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php endif; ?>
            </div>
            <div class="status-info">
                <h3>Payment <?= $is_canceled ? 'cancelled' : 'processed' ?></h3>
                <?php if ($is_canceled): ?>
                    <p>Your payment was processed securely.</p>
                <?php else: ?>
                    <p>Your payment was processed securely.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="status-card">
            <div class="status-icon">
                <?php if ($is_canceled): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                <?php endif; ?>
            </div>
            <div class="status-info">
                <h3><?= ucfirst(htmlspecialchars($order['status'])) ?></h3>
                <p>Your order has been <?= strtolower(htmlspecialchars($order['status'])) ?></p>
                <div class="status-pill"><?= $order_date ?></div>
            </div>
        </div>
    </div>
    
    <!-- Main Layout Grid -->
    <div class="order-layout">
        
        <!-- Left Column: Items Box -->
        <div class="order-box">
            <h2 class="order-box-title">Items (<?= count($items) ?>)</h2>
            
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
                        <div class="item-subtitle">Maison Collection</div>
                    </div>
                    <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                </div>
            <?php endforeach; ?>
            
            <div class="box-divider"></div>
            
            <div class="summary-line">
                <span>Subtotal</span>
                <span>₱<?= $formatted_subtotal ?></span>
            </div>
            <div class="summary-line">
                <span>Shipping</span>
                <span><?= $shipping_fee > 0 ? '₱' . $formatted_shipping : 'Free' ?></span>
            </div>
            
            <div class="summary-line total-box">
                <div class="total-box-left">
                    <span>Total</span>
                    <span class="tax-note">Including ₱<?= $formatted_tax ?> in taxes</span>
                </div>
                <div>
                    <span class="currency">PHP</span> 
                    <span class="total-price">₱<?= $formatted_total ?></span>
                </div>
            </div>
        </div>

        <!-- Right Column: Info Cards -->
        <div class="side-panel">
            
            <!-- Order Summary -->
            <div class="side-card">
                <h2 class="side-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Order Summary
                </h2>
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span>₱<?= $formatted_subtotal ?></span>
                </div>
                <div class="summary-line">
                    <span>Shipping</span>
                    <span>₱<?= $formatted_shipping ?></span>
                </div>
                
                <div class="summary-line side-total">
                    <span>Total</span>
                    <div>
                        <span class="currency">PHP</span> 
                        <span style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--brand-light);">₱<?= $formatted_total ?></span>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-light); text-align: left;">Including ₱<?= $formatted_tax ?> in taxes</div>
                
                <a href="shop.php" class="btn-buy-again-large">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    Buy again
                </a>
            </div>

            <!-- Order Details -->
            <div class="side-card">
                <h2 class="side-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    Order Details
                </h2>
                
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Contact</div>
                            <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        </div>
                        <div class="info-content">
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
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Method</div>
                            <div class="info-value">Shipping Fee Nationwide</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Payment</div>
                            <div class="info-value">
                                <p><?= htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p style="font-size: 0.8rem;">₱<?= $formatted_total ?> PHP · <?= $order_date ?></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
    </div>
</div>

</body>
</html>
