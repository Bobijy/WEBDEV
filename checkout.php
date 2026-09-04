<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Fetch user data for pre-filling the delivery form
$user = db_fetch($pdo,
    'SELECT full_name, email, phone, address FROM users WHERE id = :id',
    [':id' => (int) $_SESSION['user_id']]
);

// Fetch saved addresses
$user_addresses = db_fetch_all($pdo, 
    'SELECT * FROM user_addresses WHERE user_id = :uid ORDER BY is_default DESC, id DESC', 
    [':uid' => (int) $_SESSION['user_id']]
);

$name_parts = explode(' ', $user['full_name'] ?? '');
$first_name = $name_parts[0] ?? '';
$last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : '';

// Fetch cart items for display in the sidebar
$items = [];
$total = 0;
$cartItems = db_fetch_all($pdo, '
    SELECT ci.quantity, p.name, p.price, p.image
    FROM   cart_items ci
    JOIN   carts c      ON ci.cart_id   = c.id
    JOIN   products p   ON ci.product_id = p.id
    WHERE  c.user_id = :uid
', [':uid' => (int) $_SESSION['user_id']]);
foreach ($cartItems as $row) {
    $items[] = $row;
    $total += ($row['price'] * $row['quantity']);
}

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

// Calculate taxes and shipping dynamically
$shipping = 50.00;
$tax_rate = 0.12; // 12%
$taxes = $total * $tax_rate;
$grand_total = $total + $shipping; // Tax is typically included in total based on screenshot "Including P106.61 in taxes"

$site = ['name' => 'Maison Ungod'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> — Checkout</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/pages/checkout.css?v=2">

</head>
<body>

<div class="checkout-layout">
    
    <!-- Left Main Content -->
    <div class="main-content">
        <a href="index.php" class="header-logo"><?= htmlspecialchars($site['name']) ?></a>
        
        <form id="checkoutForm">
            <div class="alert" id="checkoutMsg"></div>
            
            <div class="contact-info">
                <div>
                    <h2>Contact</h2>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div class="contact-badge">
                    <?= strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) ?>
                </div>
            </div>
            
            <label class="checkbox-wrapper">
                <input type="checkbox" checked>
                <span>Email me with news and offers</span>
            </label>

            <h2>Delivery</h2>
            
            <?php if (!empty($user_addresses)): ?>
                <div class="saved-addresses" style="margin-bottom: 20px;">
                    <?php foreach ($user_addresses as $index => $addr): ?>
                        <label class="payment-option <?= $index === 0 ? 'active' : '' ?>" style="display:flex; align-items:flex-start; margin-bottom:10px;">
                            <div class="payment-label-wrap" style="align-items:flex-start; margin-top: 4px;">
                                <input type="radio" name="selected_address_id" value="<?= $addr['id'] ?>" <?= $index === 0 ? 'checked' : '' ?> onclick="toggleAddressMode(false, this)">
                                <div>
                                    <strong style="font-size:0.85rem;"><?= htmlspecialchars($addr['full_name']) ?></strong><br>
                                    <span style="font-size:0.8rem; color:var(--text-light);"><?= htmlspecialchars($addr['phone']) ?></span><br>
                                    <span style="font-size:0.8rem; color:var(--text-light);"><?= htmlspecialchars($addr['address_line']) ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    <label class="payment-option" style="display:flex; align-items:center;">
                        <div class="payment-label-wrap">
                            <input type="radio" name="selected_address_id" value="new" onclick="toggleAddressMode(true, this)">
                            <span>Use a different address</span>
                        </div>
                    </label>
                </div>
            <?php else: ?>
                <input type="hidden" name="selected_address_id" value="new">
            <?php endif; ?>

            <div id="newAddressForm" style="<?= !empty($user_addresses) ? 'display:none;' : '' ?>">
                <div class="floating-input">
                    <select id="country" name="country">
                        <option value="Philippines">Philippines</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                    </select>
                    <label for="country">Country/Region</label>
                </div>
                
                <div class="form-row">
                    <div class="form-col floating-input">
                        <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($first_name) ?>" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                        <label for="firstName">First name</label>
                    </div>
                    <div class="form-col floating-input">
                        <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($last_name) ?>" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                        <label for="lastName">Last name</label>
                    </div>
                </div>
                
                <div class="floating-input">
                    <input type="text" id="addressLine1" name="addressLine1" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                    <label for="addressLine1">"Complete Address" to avoid shipping delay.</label>
                </div>
                
                <div class="floating-input">
                    <input type="text" id="barangay" name="barangay" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                    <label for="barangay">Barangay</label>
                </div>
                
                <div class="form-row">
                    <div class="form-col floating-input">
                        <input type="text" id="postalCode" name="postalCode" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                        <label for="postalCode">Postal code</label>
                    </div>
                    <div class="form-col floating-input">
                        <input type="text" id="city" name="city" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                        <label for="city">City</label>
                    </div>
                </div>
                
                <div class="floating-input">
                    <select id="region" name="region">
                        <option value="Metro Manila">Metro Manila</option>
                        <option value="Cebu">Cebu</option>
                        <option value="Davao">Davao</option>
                    </select>
                    <label for="region">Region</label>
                </div>
                
                <div class="floating-input">
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder=" " <?= empty($user_addresses) ? 'required' : '' ?>>
                    <label for="phone">Phone</label>
                </div>
            </div>
            
            <label class="checkbox-wrapper">
                <input type="checkbox">
                <span>Text me with news and offers</span>
            </label>
            
            <h2>Payment</h2>
            <p style="color: var(--text-light); font-size: 0.8rem; margin-bottom: 16px;">All transactions are secure and encrypted.</p>
            
            <div class="payment-box">
                <!-- Option 1 -->
                <label class="payment-option active" id="label-cc">
                    <div class="payment-label-wrap">
                        <input type="radio" name="payment_method" value="Credit Card" checked onclick="togglePayment(this, 'panel-cc')">
                        <span>Credit card</span>
                    </div>
                    <div class="payment-icons">
                        <span style="background: #1434CB; color: white; padding: 2px 6px; border-radius: 2px; font-size: 10px; font-weight: bold;">VISA</span>
                        <span style="background: #FF5F00; color: white; padding: 2px 6px; border-radius: 2px; font-size: 10px; font-weight: bold;">MC</span>
                    </div>
                </label>
                <div class="payment-panel active" id="panel-cc">
                    <div class="floating-input">
                        <input type="text" placeholder=" " id="cc_number">
                        <label for="cc_number">Card number</label>
                    </div>
                    <div class="form-row">
                        <div class="form-col floating-input">
                            <input type="text" placeholder=" " id="cc_exp">
                            <label for="cc_exp">Expiration date (MM / YY)</label>
                        </div>
                        <div class="form-col floating-input">
                            <input type="text" placeholder=" " id="cc_sec">
                            <label for="cc_sec">Security code</label>
                        </div>
                    </div>
                    <div class="floating-input" style="margin-bottom:0;">
                        <input type="text" placeholder=" " id="cc_name">
                        <label for="cc_name">Name on card</label>
                    </div>
                </div>
                
                <!-- Option 2 -->
                <label class="payment-option" id="label-paymongo">
                    <div class="payment-label-wrap">
                        <input type="radio" name="payment_method" value="PayMongo" onclick="togglePayment(this, 'panel-paymongo')">
                        <span>Secure Payments via PayMongo</span>
                    </div>
                </label>
                <div class="payment-panel" id="panel-paymongo">
                    <p style="font-size: 0.8rem; color: var(--text-light); text-align: center; padding: 20px 0;">After clicking "Pay now", you will be redirected to PayMongo to complete your purchase securely.</p>
                </div>
                
                <!-- Option 3 -->
                <label class="payment-option" id="label-cod">
                    <div class="payment-label-wrap">
                        <input type="radio" name="payment_method" value="Cash On Delivery" onclick="togglePayment(this, 'panel-cod')">
                        <span>Cash On Delivery Nationwide (COD)</span>
                    </div>
                </label>
                <div class="payment-panel" id="panel-cod">
                    <p style="font-size: 0.8rem; color: var(--text-light); text-align: center; padding: 20px 0;">Pay with cash upon delivery.</p>
                </div>
            </div>

            <!-- We generate the aggregate hidden address field to satisfy the legacy API -->
            <input type="hidden" name="address" id="finalAddress">
            
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px;">
                <a href="shop.php?cart=open" style="color: var(--brand-color); text-decoration: none; font-weight: 500; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H6M12 5l-7 7 7 7"/></svg>
                    Return to cart
                </a>
                <button type="submit" class="btn-pay" id="checkoutSubmit" style="margin-top: 0; width: auto; padding: 18px 40px;">Pay now</button>
            </div>
            
            <div class="footer-links">
                <a href="#">Refund policy</a>
                <a href="#">Privacy policy</a>
                <a href="#">Terms of service</a>
                <a href="#">Contact</a>
            </div>
        </form>
    </div>
    
    <!-- Right Sidebar Content -->
    <div class="sidebar">
        <?php foreach ($items as $item): ?>
            <div class="cart-item">
                <div class="item-thumb-wrapper">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <span class="item-badge"><?= $item['quantity'] ?></span>
                </div>
                <div class="item-info">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <p>100ml</p>
                </div>
                <div class="item-price">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
            </div>
        <?php endforeach; ?>
        
        <div class="discount-row">
            <div class="floating-input">
                <input type="text" id="discount" placeholder=" ">
                <label for="discount">Discount code</label>
            </div>
            <button class="btn-apply" disabled>Apply</button>
        </div>
        
        <div class="totals-row">
            <span>Subtotal</span>
            <span class="val">₱<?= number_format($total, 2) ?></span>
        </div>
        <div class="totals-row">
            <span>Shipping</span>
            <span class="val">₱<?= number_format($shipping, 2) ?></span>
        </div>
        <div class="totals-row grand-total">
            <span>Total</span>
            <span class="val"><span class="currency">PHP</span>₱<?= number_format($grand_total, 2) ?></span>
        </div>
        <p class="tax-note">Including ₱<?= number_format($taxes, 2) ?> in taxes</p>
    </div>
</div>

<script src="js/checkout.js?v=3"></script>
</body>
</html>
