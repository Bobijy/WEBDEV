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

// Verify order ownership and fetch details
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

$site = ['name' => 'Maison Ungod'];
$order_date = date('M j, Y', strtotime($order['created_at']));
$shipping_fee = isset($order['shipping_fee']) ? (float) $order['shipping_fee'] : 0.00;
$total_amount = (float) $order['total_amount'];
$subtotal = $total_amount - $shipping_fee;

$formatted_subtotal = number_format($subtotal, 2);
$formatted_shipping = number_format($shipping_fee, 2);
$formatted_total = number_format($total_amount, 2);

// Calculate 12% tax
$tax_rate = 0.12;
$tax_amount = $subtotal * $tax_rate;
$formatted_tax = number_format($tax_amount, 2);

$is_canceled = (strtolower($order['status']) === 'canceled');
$is_card = ($order['payment_method'] === 'Card');
$card_brand = !empty($order['card_brand']) ? $order['card_brand'] : 'Card';
$card_last4 = !empty($order['card_last4']) ? $order['card_last4'] : '••••';
$card_holder = !empty($order['card_name']) ? $order['card_name'] : 'Cardholder';
$txn_id = !empty($order['transaction_id']) ? $order['transaction_id'] : ('TXN-' . date('Ymd', strtotime($order['created_at'])) . '-' . strtoupper(substr(md5($order['id']), 0, 6)));
$order_number = !empty($order['order_number']) ? $order['order_number'] : ('ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT));
$order_datetime = date('M j, Y h:i A', strtotime($order['created_at']));
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
    <link rel="stylesheet" href="css/components/receipt-modal.css?v=1">
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    <h1>Order <?= $order_id ?></h1>
                </a>
                <div class="order-date">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Confirmed <?= $order_date ?>
                </div>
            </div>
            <div>
                <button type="button" class="btn-receipt-view-orders" onclick="openReceiptModal()" style="background:#1c1c1e; color:#F5F5F5; border:1px solid rgba(255,255,255,0.18); padding:9px 18px; border-radius:8px; cursor:pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    View Official Receipt
                </button>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card">
                <div class="status-icon">
                    <?php if ($is_canceled): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
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
                                <img src="<?= htmlspecialchars($item['image']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>">
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
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
                            <span
                                style="font-family: var(--font-heading); font-size: 1.4rem; color: var(--brand-light);">₱<?= $formatted_total ?></span>
                        </div>
                    </div>

                    <a href="shop.php" class="btn-buy-again-large">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        Buy again
                    </a>
                </div>

                <!-- Order Details -->
                <div class="side-card">
                    <h2 class="side-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                        Order Details
                    </h2>

                    <div class="info-list">
                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Contact</div>
                                <div class="info-value"><?= htmlspecialchars($order['email']) ?></div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Method</div>
                                <div class="info-value">Shipping Fee Nationwide</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect>
                                    <line x1="2" y1="10" x2="22" y2="10"></line>
                                </svg>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Payment</div>
                                <div class="info-value">
                                    <p><?= htmlspecialchars($order['payment_method'], ENT_QUOTES, 'UTF-8') ?><?= !empty($order['card_last4']) ? ' (' . htmlspecialchars($card_brand) . ' •••• ' . htmlspecialchars($order['card_last4']) . ')' : '' ?></p>
                                    <p style="font-size: 0.8rem;">₱<?= $formatted_total ?> PHP · <?= $order_date ?></p>
                                    <button type="button" class="btn-view-receipt-order" onclick="openReceiptModal()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        View Official Receipt
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Official E-Receipt Modal -->
    <div class="receipt-modal-overlay" id="receiptModalOverlay" aria-modal="true" role="dialog">
        <div class="receipt-modal-container">
            <div class="receipt-card">
                <button type="button" class="receipt-close-btn" onclick="closeReceiptModal()" aria-label="Close receipt">&times;</button>
                
                <div class="receipt-header">
                    <div class="receipt-brand-logo"><?= htmlspecialchars($site['name']) ?></div>
                    <div class="receipt-brand-sub">Haute Parfumerie &middot; Official Payment Receipt</div>
                    <div class="receipt-title-wrap">
                        <span class="receipt-badge-approved">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?= $is_card ? 'Card Payment Authorized &amp; Approved' : 'Payment Recorded' ?>
                        </span>
                    </div>
                </div>

                <div class="receipt-info-grid">
                    <div class="receipt-info-cell">
                        <span class="receipt-info-label">Order Number</span>
                        <span class="receipt-info-value"><?= htmlspecialchars($order_number) ?></span>
                    </div>
                    <div class="receipt-info-cell">
                        <span class="receipt-info-label">Transaction Reference</span>
                        <span class="receipt-info-value"><?= htmlspecialchars($txn_id) ?></span>
                    </div>
                    <div class="receipt-info-cell">
                        <span class="receipt-info-label">Date &amp; Time</span>
                        <span class="receipt-info-value"><?= htmlspecialchars($order_datetime) ?></span>
                    </div>
                    <div class="receipt-info-cell">
                        <span class="receipt-info-label">Payment Method</span>
                        <span class="receipt-info-value receipt-card-tag">
                            <?php if ($is_card): ?>
                                <span class="receipt-card-brand-badge <?= (stripos($card_brand, 'master') !== false || stripos($card_brand, 'mc') !== false) ? 'mc' : '' ?>">
                                    <?= strtoupper(htmlspecialchars($card_brand)) ?>
                                </span>
                                <span><?= htmlspecialchars($card_brand) ?> ending in &bull;&bull;&bull;&bull; <?= htmlspecialchars($card_last4) ?></span>
                            <?php else: ?>
                                <span><?= htmlspecialchars($order['payment_method']) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="receipt-two-col">
                    <div class="receipt-col-block">
                        <div class="receipt-col-title"><?= $is_card ? 'Billed To (Cardholder)' : 'Customer Info' ?></div>
                        <div class="receipt-col-text" style="font-weight:600;"><?= htmlspecialchars($card_holder) ?></div>
                        <div class="receipt-col-text" style="color:#6B7280; font-size:0.78rem;"><?= htmlspecialchars($order['email'] ?? '') ?></div>
                    </div>
                    <div class="receipt-col-block">
                        <div class="receipt-col-title">Delivery Address</div>
                        <div class="receipt-col-text"><?= htmlspecialchars($order['shipping_address']) ?></div>
                    </div>
                </div>

                <div class="receipt-table-wrapper">
                    <table class="receipt-table">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                    <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                    <td class="text-right">&#8369;<?= number_format($item['price'], 2) ?></td>
                                    <td class="text-right" style="font-weight:600;">&#8369;<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="receipt-totals-wrap">
                    <div class="receipt-totals-box">
                        <div class="receipt-total-row">
                            <span>Subtotal</span>
                            <span>&#8369;<?= $formatted_subtotal ?></span>
                        </div>
                        <div class="receipt-total-row">
                            <span>Shipping Fee</span>
                            <span><?= $shipping_fee > 0 ? '&#8369;' . $formatted_shipping : 'Free' ?></span>
                        </div>
                        <div class="receipt-total-row">
                            <span>Estimated VAT (12% incl.)</span>
                            <span>&#8369;<?= $formatted_tax ?></span>
                        </div>
                        <div class="receipt-total-row grand-total">
                            <span>Total Paid</span>
                            <span class="val">&#8369;<?= $formatted_total ?></span>
                        </div>
                    </div>
                </div>

                <div class="receipt-footer-wrap">
                    <p class="receipt-footer-text">
                        Thank you for choosing <?= htmlspecialchars($site['name']) ?>. This document serves as your official electronic receipt and proof of payment.
                    </p>
                    <div class="receipt-security-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        256-Bit Encrypted Electronic Transaction &middot; Verified &amp; Logged
                    </div>
                </div>

                <div class="receipt-actions" style="justify-content: center;">
                    <button type="button" class="btn-receipt-continue" onclick="closeReceiptModal()">
                        Close Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openReceiptModal() {
            const overlay = document.getElementById('receiptModalOverlay');
            if (overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeReceiptModal() {
            const overlay = document.getElementById('receiptModalOverlay');
            if (overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeReceiptModal();
            }
        });

        // Auto open if URL has receipt=1
        if (window.location.search.indexOf('receipt=1') !== -1) {
            openReceiptModal();
        }
    </script>
</body>

</html>