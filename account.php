<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?login=1");
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// Fetch user data
$user = db_fetch($pdo,
    'SELECT full_name, email, phone, address FROM users WHERE id = :id',
    [':id' => (int) $_SESSION['user_id']]
);

// Fetch orders with their first product image
$orders = db_fetch_all($pdo, '
    SELECT o.id, o.total_amount, o.status, o.created_at,
           (SELECT p.image
            FROM   order_items oi
            JOIN   products p ON oi.product_id = p.id
            WHERE  oi.order_id = o.id
            LIMIT 1) AS first_image
    FROM   orders o
    WHERE  o.user_id = :uid
    ORDER BY o.created_at DESC
', [':uid' => (int) $_SESSION['user_id']]);

$site = [
    'name' => 'Maison Ungod',
    'tagline' => 'Find Your Signature Scent',
    'description' => 'Shop the exclusive Maison Ungod fragrance collection Ã¢â‚¬â€ luxury perfumes crafted with rare ingredients.',
    'year' => date('Y'),
];

// Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ Navigation Links Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
$navLinks = [
    ['label' => 'Home', 'href' => 'index.php#hero', 'active' => false],
    ['label' => 'Shop', 'href' => 'shop.php', 'active' => false],
    ['label' => 'Best Seller', 'href' => 'index.php#collections', 'active' => false],
    ['label' => 'Our Story', 'href' => 'index.php#story', 'active' => false],
    ['label' => 'Contact', 'href' => '#', 'id' => 'contactToggle', 'active' => false],
];

// Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬ SVG Icons Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
$icons = [
    'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
    'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.733 16h4.267l-11.733 -16zM4 20l6.768 -6.768M13.232 10.768L20 4"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'bag' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
];

$socials = [
    ['icon' => 'instagram', 'url' => '#'],
    ['icon' => 'facebook', 'url' => '#'],
    ['icon' => 'x', 'url' => '#'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($site['name']) ?> Ã¢â‚¬â€ My Account</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Shared styles for navbar + footer -->
    <link rel="stylesheet" href="css/style.css?v=7">
    <link rel="stylesheet" href="css/animations.css?v=2">
    
    <link rel="stylesheet" href="css/pages/account.css?v=1">
    
    <style>
        .account-page-wrapper {
            padding-top: 130px;
            padding-bottom: 80px;
            min-height: 80vh;
        }
    </style>
</head>
<body>

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
     NAVBAR
     Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <!-- Top Row: Logo | Brand | Icons -->
            <div class="nav-top">
                <a href="index.php" class="nav-logo">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?> Logo">
                </a>
                <span class="nav-brand">&mdash;<?= strtoupper($site['name']) ?>&mdash;</span>
                <div class="nav-icons">
                    <a href="#" class="nav-icon" id="searchToggle" aria-label="Search" onclick="document.getElementById('searchOverlay').classList.add('open'); document.body.style.overflow='hidden'; setTimeout(() => document.getElementById('searchInput').focus(), 100); return false;"><?= $icons['search'] ?></a>
                    <a href="#" class="nav-icon" id="accountToggle" aria-label="Account">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </a>
                    <a href="#" class="nav-icon" id="cartToggle" aria-label="Cart"><?= $icons['bag'] ?></a>
                </div>
            </div>
            <!-- Bottom Row: Centered Nav Links -->
            <div class="nav-bottom" id="navLinks">
                <?php foreach ($navLinks as $link): ?>
                    <a href="<?= $link['href'] ?>" <?= isset($link['id']) ? 'id="'.$link['id'].'"' : '' ?> <?= $link['active'] ? ' class="active"' : '' ?>><?= $link['label'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <div class="account-page-wrapper">
<div class="account-layout" style="flex-direction: column; gap: 0;">
    <a href="index.php" class="back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Back to Store
    </a>
    
    <div style="display: flex; gap: 60px; width: 100%; flex-wrap: wrap;" class="account-inner-layout">
        <!-- Sidebar Navigation -->
    <div class="sidebar">
        <a class="nav-item" onclick="switchTab('orders')" id="nav-orders">Orders</a>
        <a class="nav-item active" onclick="switchTab('profile')" id="nav-profile">Profile</a>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- PROFILE TAB -->
        <div class="tab-content active" id="tab-profile">
            <div class="section-header">
                <h2><?= htmlspecialchars($user['full_name'] ?? 'Maison Ungod User') ?></h2>
                <button class="btn-small" id="editProfileBtn">Edit</button>
            </div>
            
            <div class="info-box">
                <div class="info-content" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="info-label">Email</span>
                    <span style="font-size: 0.95rem; letter-spacing: 0.2px;"><?= htmlspecialchars($user['email']) ?></span>
                </div>
            </div>
            
            <div class="section-header">
                <h2>Addresses</h2>
                <button class="btn-small" id="editAddressBtn">Add</button>
            </div>
            
            <div class="info-box clickable">
                <div class="info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="info-content">
                    <h4><?= htmlspecialchars($user['full_name'] ?? 'User') ?> <span style="font-weight: normal; color: var(--text-light); font-size: 0.9rem; margin-left: 8px;">Default</span></h4>
                    <p><?= htmlspecialchars($user['address'] ?? 'No address provided.') ?></p>
                </div>
                <div class="info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </div>
            
            <div class="section-header">
                <h2>Marketing preferences</h2>
            </div>
            
            <div class="info-box">
                <div class="info-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <div class="info-content" style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="info-label">Email</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
            
            <div class="actions-row">
                <button class="btn-outline" id="logoutBtn">Sign out</button>
                <a href="#" class="text-link">Sign out of all devices</a>
            </div>
        </div>
        
        <!-- ORDERS TAB -->
        <div class="tab-content" id="tab-orders">
            <?php if (empty($orders)): ?>
                <p style="color: var(--text-light);">You haven't placed any orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <a href="order.php?id=<?= $order['id'] ?>" class="order-card">
                        <div class="order-card-top">
                            <div class="order-card-thumb">
                                <?php if ($order['first_image']): ?>
                                    <img src="<?= htmlspecialchars($order['first_image']) ?>" alt="Product">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="order-card-bottom">
                            <div>
                                <div class="order-card-status"><?= htmlspecialchars($order['status']) ?></div>
                                <div class="order-card-meta">
                                    <?= $order['id'] ?> &middot; &#8369;<?= number_format($order['total_amount'], 2) ?> PHP
                                </div>
                            </div>
                            <button class="order-card-btn" onclick="event.preventDefault(); window.location.href='shop.php';">Buy again</button>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
    </div>
</div>
</div> <!-- Close account-layout -->

    </div> <!-- Close account-page-wrapper -->

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     FOOTER
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <footer class="site-footer" id="contact">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?>">
                    <div class="footer-social">
                        <?php foreach ($socials as $s): ?>
                            <a href="<?= $s['url'] ?>" aria-label="<?= $s['icon'] ?>"><?= $icons[$s['icon']] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#collections">Collections</a></li>
                        <li><a href="index.php#story">Our Story</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Legalities</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="#">Phone: (0912) 0858</a></li>
                        <li><a href="mailto:bobjoshuaungod26@gmail.com">Email: bobjoshuaungod26@gmail.com</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= $site['year'] ?> <?= $site['name'] ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SEARCH OVERLAY
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="search-overlay" id="searchOverlay">
        <div class="search-modal">
            <div class="search-modal__header">
                <div class="search-input-wrap">
                    <?= $icons['search'] ?>
                    <input type="text" id="searchInput" placeholder="Search products..." autocomplete="off">
                </div>
                <button class="search-modal__close" id="searchClose" aria-label="Close search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="search-results" id="searchResults">
                <p class="search-hint">Type to search fragrances...</p>
            </div>
        </div>
    </div>

    <!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CART MODAL
     â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <aside class="cart-panel" id="cartPanel">
        <div class="cart-panel__header">
            <h2>Your Bag <span class="cart-badge" id="cartBadge">0</span></h2>
            <button class="cart-panel__close" id="cartClose" aria-label="Close cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18" />
                    <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
            </button>
        </div>
        <div class="cart-panel__items" id="cartItems">
            <!-- Items injected by JS -->
            <p class="cart-empty" id="cartEmpty">Your bag is empty.</p>
        </div>
        <div class="cart-panel__footer" id="cartFooter">
            <div class="cart-total-row">
                <span>Subtotal</span>
                <span id="cartTotal">.00</span>
            </div>
            <p class="cart-tax-note">Shipping & taxes calculated at checkout</p>
            <a href="checkout.php" class="cart-checkout-btn" id="cartCheckout" style="text-align:center; display:block;">CHECK OUT</a>
        </div>
    </aside>

    <div class="contact-overlay" id="contactOverlay"></div>
    <div class="account-modal contact-modal" id="contactModal">
        <button class="account-modal__close" id="contactClose" aria-label="Close contact">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="account-modal__inner">
            <div class="auth-header">
                <h2>Contact Us</h2>
                <p>Send us a message and we will get back to you shortly.</p>
            </div>
            <form class="account-form" id="contactForm">
                <div class="account-msg" id="contactMsg"></div>
                <div class="account-field">
                    <label for="contactName">Name</label>
                    <input type="text" id="contactName" name="name" required>
                </div>
                <div class="account-field">
                    <label for="contactEmail">Email</label>
                    <input type="email" id="contactEmail" name="email" required>
                </div>
                <div class="account-field">
                    <label for="contactMessage">Message</label>
                    <textarea id="contactMessage" name="message" required style="width: 100%; background: #171717; border: 1px solid #2E2E2E; color: #F5F5F5; padding: 12px; border-radius: 4px; font-family: var(--font-body); resize: vertical; min-height: 100px;"></textarea>
                </div>
                <button type="submit" class="account-submit" id="contactSubmit">Send Message</button>
            </form>
        </div>
    </div>

<!-- Edit Profile Modal -->
<div class="edit-modal-overlay" id="editModalOverlay">
    <div class="edit-modal">
        <button class="edit-close" id="editModalClose">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h2>Edit Profile</h2>
        <form id="editProfileForm">
            <div class="edit-msg" id="editMsg"></div>
            <div class="form-group">
                <label for="editName">Full Name</label>
                <input type="text" id="editName" name="name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="editEmail">Email</label>
                <input type="email" id="editEmail" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="editPhone">Phone Number</label>
                <input type="text" id="editPhone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="editAddress">Shipping Address</label>
                <input type="text" id="editAddress" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-small" id="editModalCancel" style="border:none;">Cancel</button>
                <button type="submit" class="btn-outline" id="editModalSubmit">Save Changes</button>
            </div>
        </form>
    </div>
</div>

    <!-- Scroll-to-Top Button -->
    <button class="scroll-to-top" aria-label="Scroll to top">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    <script src="js/main.js?v=4"></script>
    <script src="js/cart.js?v=3"></script>
    <script src="js/search.js?v=4"></script>
    <script src="js/contact.js?v=3"></script>
    <script src="js/transitions.js?v=4"></script>
    <script src="js/account-page.js?v=2"></script>
</body>
</html>


