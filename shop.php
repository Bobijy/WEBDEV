<?php
session_start();
require_once __DIR__ . '/database/helpers.php';
/**
 * Maison Ungod — Shop Page
 */

// Site Configuration
$site = [
    'name' => 'Maison Ungod',
    'tagline' => 'Find Your Signature Scent',
    'description' => 'Shop the exclusive Maison Ungod fragrance collection &mdash; luxury perfumes crafted with rare ingredients.',
    'year' => date('Y'),
];

// Navigation Links
$navLinks = [
    ['label' => 'Home', 'href' => 'index.php#hero', 'active' => false],
    ['label' => 'Shop', 'href' => 'shop.php', 'active' => true],
    ['label' => 'Best Seller', 'href' => 'index.php#collections', 'active' => false],
    ['label' => 'Our Story', 'href' => 'index.php#story', 'active' => false],
    ['label' => 'Contact', 'href' => '#', 'id' => 'contactToggle', 'active' => false],
];

// Active Products from Database
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/helpers.php';

$products = [];
$rows = db_fetch_all($pdo, "SELECT id, image, name, price FROM products WHERE status = 'Active' ORDER BY id ASC");
foreach ($rows as $row) {
    $products[] = [
        'id'    => $row['id'],
        'image' => $row['image'],
        'label' => $row['name'],
        'price' => '₱' . number_format($row['price'], 2),
    ];
}

// SVG Icons
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
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($site['description']) ?>">
    <title><?= htmlspecialchars($site['name']) ?> &mdash; Shop</title>

    <!-- Shared styles for navbar + footer -->
    <link rel="stylesheet" href="css/style.css?v=12">
    <link rel="stylesheet" href="css/animations.css?v=2">

    <link rel="stylesheet" href="css/pages/shop.css?v=1">
</head>

<body>



    <!-- Scroll Progress Bar -->
    <div class="scroll-progress"></div>

    <!-- Custom Cursor -->


    <!-- Navigation Bar -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <!-- Top Row: Logo | Centered Brand | Icons -->
            <div class="nav-top">
                <a href="index.php" class="nav-logo" aria-label="<?= $site['name'] ?>">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?> Logo">
                </a>
                <span class="nav-brand">&mdash;&nbsp;<?= strtoupper($site['name']) ?>&nbsp;&mdash;</span>
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
                    <button class="hamburger" id="hamburger" aria-label="Menu">
                        <span></span><span></span><span></span>
                    </button>
                </div>
            </div>
            <!-- Bottom Row: Centered Nav Links -->
            <div class="nav-bottom" id="navLinks">
                <?php foreach ($navLinks as $link): ?>
                    <a href="<?= $link['href'] ?>" <?= isset($link['id']) ? 'id="'.$link['id'].'"' : '' ?> <?= $link['active'] ? ' class="active"' : '' ?>><?= $link['label'] ?></a>
                <?php endforeach; ?>

                <!-- Mobile Only Actions (Search, Account, Cart) -->
                <div class="mobile-nav-actions">
                    <a href="#" class="mobile-nav-action" id="mobileSearchToggle" aria-label="Search" onclick="document.getElementById('searchOverlay').classList.add('open'); document.body.style.overflow='hidden'; setTimeout(() => document.getElementById('searchInput').focus(), 100); return false;">
                        <?= $icons['search'] ?>
                        <span>Search</span>
                    </a>
                    <a href="#" class="mobile-nav-action" id="mobileAccountToggle" aria-label="Account" onclick="const at = document.getElementById('accountToggle'); if (at) at.click(); return false;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <span>Account</span>
                    </a>
                    <a href="#" class="mobile-nav-action" id="mobileCartToggle" aria-label="Cart" onclick="const ct = document.getElementById('cartToggle'); if (ct) ct.click(); return false;">
                        <?= $icons['bag'] ?>
                        <span>Bag</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Products Catalog Section -->
    <section class="shop-page" id="shop">
        <div class="shop-container">

            <!-- Heading -->
            <div class="shop-heading">
                <h1>Our Products</h1>
            </div>

            <!-- Toolbar: Item count + Sort -->
            <div class="shop-toolbar">
                <span><?= count($products) ?> Items</span>
                <div class="sort-wrapper" id="sortWrapper">
                    <span class="shop-sort" id="sortToggle">Sort
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6" />
                        </svg>
                    </span>
                    <div class="sort-dropdown" id="sortDropdown">
                        <button data-sort="default" class="active">Default</button>
                        <button data-sort="price-asc">Price: Low &rarr; High</button>
                        <button data-sort="price-desc">Price: High &rarr; Low</button>
                        <button data-sort="name-asc">Name: A &rarr; Z</button>
                        <button data-sort="name-desc">Name: Z &rarr; A</button>
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="product-grid">
                <?php foreach ($products as $i => $prod): ?>
                    <div class="cat-card reveal reveal-d<?= ($i % 3) + 1 ?>">
                        <h3 class="cat-title"><?= $prod['label'] ?></h3>
                        <p class="cat-subtitle">Maison Collection</p>
                        <img class="cat-img" src="<?= $prod['image'] ?>" alt="<?= $prod['label'] ?>">
                        <p class="cat-price"><?= $prod['price'] ?></p>
                        <button class="cat-link add-to-cart-btn" style="background:transparent; border-top:none; border-left:none; border-right:none; cursor:pointer;"
                                data-id="<?= $prod['id'] ?>"
                                data-name="<?= $prod['label'] ?>"
                                data-price="<?= $prod['price'] ?>"
                                data-image="<?= $prod['image'] ?>">
                            ADD TO BAG
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer" id="contact">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?>">
                    <div class="footer-social">
                        <?php foreach ($socials as $s): ?>
                            <a href="javascript:void(0)" aria-label="<?= $s['icon'] ?>"><?= $icons[$s['icon']] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Menu</h4>
                    <ul>
                        <li><a href="javascript:void(0)">Home</a></li>
                        <li><a href="javascript:void(0)">Collections</a></li>
                        <li><a href="javascript:void(0)">Our Story</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Legalities</h4>
                    <ul>
                        <li><a href="javascript:void(0)">Privacy Policy</a></li>
                        <li><a href="javascript:void(0)">Terms of Service</a></li>
                        <li><a href="javascript:void(0)">Cookie Policy</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="javascript:void(0)">Phone: (0912) 0858</a></li>
                        <li><a href="javascript:void(0)">Email: bobjoshuaungod26@gmail.com</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= $site['year'] ?> <?= $site['name'] ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Search Modal -->
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

    <!-- Shopping Bag Drawer -->
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
                <span style="color:#C2C2C2; font-size: 0.85rem; text-transform: none; letter-spacing: 0;">Subtotal</span>
                <span id="cartSubtotal" style="font-size: 0.95rem; color: #F5F5F5; font-family: var(--font-body);">₱0.00</span>
            </div>
            <div class="cart-total-row" style="margin-bottom: 20px;">
                <span style="color:#C2C2C2; font-size: 0.85rem; text-transform: none; letter-spacing: 0;">Shipping</span>
                <span style="font-size: 0.8rem; color: #C2C2C2; font-family: var(--font-body);">Calculated at checkout</span>
            </div>
            <div class="cart-total-box" style="background: rgba(113, 65, 107, 0.05); border: 1px solid rgba(113, 65, 107, 0.15); border-radius: 8px; padding: 20px; text-align: center; margin-bottom: 15px;">
                <span style="display:block; font-size: 0.75rem; letter-spacing: 3px; color: #8A8A8A; text-transform: uppercase; margin-bottom: 8px;">TOTAL</span>
                <div id="cartTotal" style="font-size: 1.8rem; color: var(--accent); font-family: var(--font-heading);">₱0.00</div>
            </div>
            <div style="text-align: center; color: #8A8A8A; font-size: 0.75rem; margin-bottom: 15px;">
                <svg style="vertical-align: middle; margin-right: 4px; margin-top:-2px;" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Secure checkout • 100% Authentic
            </div>
            <a href="checkout.php" class="cart-checkout-btn" id="cartCheckout" style="display:flex; justify-content:center; align-items:center; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                CHECK OUT
            </a>
            <div style="text-align: center; margin-top: 20px;">
                <a href="#" onclick="document.getElementById('cartClose').click(); return false;" style="color: #999; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Continue Shopping
                </a>
            </div>
        </div>
    </aside>

    <!-- Account Modal (Sign In / Register) -->
    <div class="account-overlay" id="accountOverlay"></div>
    <div class="account-modal" id="accountModal">
        <button class="account-modal__close" id="accountClose" aria-label="Close account">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        </button>
        
        <div class="account-modal__inner">
            <div class="auth-header">
                <h2>Sign In</h2>
            </div>
            
            <form class="account-form" id="loginForm">
                <div class="account-msg" id="loginMsg"></div>
                <div class="account-field">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="your@email.com" required>
                </div>
                <div class="account-field">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="account-submit" id="loginSubmit">Sign In</button>
                <div class="auth-links">
                    Don't have an account? <a href="#" id="showRegister">Register</a>
                </div>
            </form>

            <form class="account-form" id="registerForm" style="display:none;">
                <div class="account-msg" id="registerMsg"></div>
                <div class="account-field">
                    <label for="registerName">Full Name</label>
                    <input type="text" id="registerName" name="name" placeholder="John Doe" required>
                </div>
                <div class="account-field">
                    <label for="registerEmail">Email</label>
                    <input type="email" id="registerEmail" name="email" placeholder="your@email.com" required>
                </div>
                <div class="account-field">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" placeholder="Min. 6 characters" required>
                </div>
                <button type="submit" class="account-submit" id="registerSubmit">Create Account</button>
                <div class="auth-links">
                    Already have an account? <a href="#" id="showLogin">Sign In</a>
                </div>
            </form>

        </div>
    </div>

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

    <!-- Page Scripts -->
    <!-- Scroll-to-Top Button -->
    <button class="scroll-to-top" aria-label="Scroll to top">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    <script src="js/main.js?v=6"></script>
    <script src="js/cart.js?v=5"></script>
    <script src="js/search.js?v=4"></script>
    <script src="js/account.js?v=3"></script>
    <script src="js/contact.js?v=3"></script>
    <script src="js/transitions.js?v=4"></script>

    <script src="js/shop-sort.js?v=2"></script>

    <?php if (isset($_GET['cart']) && $_GET['cart'] === 'open'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cartToggle = document.getElementById('cartToggle');
            if (cartToggle) {
                // Ensure cart.js has had time to bind its listeners.
                setTimeout(() => cartToggle.click(), 150);
            }
        });
    </script>
    <?php endif; ?>

</body>

</html>
