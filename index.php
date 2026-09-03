<?php

$site = [
    'name' => 'Maison Ungod',
    'tagline' => 'Find Your Signature Scent',
    'description' => 'Maison Ungod',
    'year' => date('Y'),
];

// Navigation Links 
$navLinks = [
    ['label' => 'Home'],
    ['label' => 'Shop'],
    ['label' => 'Best Seller'],
    ['label' => 'Our Story'],
    ['label' => 'Contact'],
];

// Product Categories 
$categories = [
    [
        'title' => 'Pour Homme',
        'subtitle' => 'Fragrance For Him',
        'image' => 'assets/images/pour-homme.png',
        'price' => '$145.00',
        'link' => 'javascript:void(0)',
    ],
    [
        'title' => 'Pour Femme',
        'subtitle' => 'Fragrance For Her',
        'image' => 'assets/images/pour-femme.png',
        'price' => '$145.00',
        'link' => 'javascript:void(0)',
    ],
];

// Collections 
$collections = [
    ['image' => 'assets/images/collection-1.png', 'label' => 'Fragrance 1', 'price' => '$145.00'],
    ['image' => 'assets/images/collection-2.png', 'label' => 'Fragrance 2', 'price' => '$145.00'],
    ['image' => 'assets/images/collection-3.png', 'label' => 'Fragrance 3', 'price' => '$145.00'],
    ['image' => 'assets/images/collection-4.png', 'label' => 'Fragrance 4', 'price' => '$165.00'],
];

// Features 
$features = [
    [
        'title' => 'Rare Ingredients',
        'text' => 'We source only the finest, most exclusive raw materials. Every botanical and resin is carefully selected to ensure unparalleled quality and a truly distinctive scent profile.',
        'image' => 'assets/images/rare-ingredients.jpg',
        'reverse' => false,
    ],
    [
        'title' => 'Masterful Craftsmanship',
        'text' => 'Perfumery excellence reimagined. Each Maison Ungod fragrance is meticulously blended and aged to perfection, allowing the unique character to develop over time.',
        'image' => 'assets/images/craftsmanship.jpg',
        'reverse' => true,
    ],
];

// SVG Icons   
$icons = [
    'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
    'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.733 16h4.267l-11.733 -16zM4 20l6.768 -6.768M13.232 10.768L20 4"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'bag' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
];

$socials = [
    ['icon' => 'instagram'],
    ['icon' => 'facebook'],
    ['icon' => 'x'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $site['description'] ?>">
    <title>Maison Ungod</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <!-- Top Row: Logo | Brand | Icons -->
            <div class="nav-top">
                <a class="nav-logo">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?> Logo">
                </a>
                <span class="nav-brand">—<?= strtoupper($site['name']) ?>—</span>
                <div class="nav-icons">
                    <a class="nav-icon" aria-label="Search"><?= $icons['search'] ?></a>
                    <a class="nav-icon" aria-label="Account">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </a>
                    <a class="nav-icon" aria-label="Cart"><?= $icons['bag'] ?></a>
                </div>
            </div>
            <!-- Bottom Row: Centered Nav Links -->
            <div class="nav-bottom" id="navLinks">
                <?php foreach ($navLinks as $link): ?>
                    <a><?= $link['label'] ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <!-- HERO -->
    <section class="hero" id="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <h1><?= $site['tagline'] ?></h1>
                <a class="btn-shop">Shop Now</a>
            </div>
            <div class="hero-image">
                <img src="assets/images/hero-product.png" alt="<?= $site['name'] ?> Signature Fragrance">
            </div>
        </div>
    </section>

    <!--CATEGORIES — Pour Homme / Pour Femme-->
    <section class="categories" id="categories">
        <div class="container categories-grid">
            <?php foreach ($categories as $i => $cat): ?>
                <div class="cat-card reveal reveal-d<?= $i + 1 ?>">
                    <h3 class="cat-title"><?= $cat['title'] ?></h3>
                    <p class="cat-subtitle"><?= $cat['subtitle'] ?></p>
                    <img class="cat-img" src="<?= $cat['image'] ?>" alt="<?= $cat['title'] ?>">
                    <p class="cat-price"><?= $cat['price'] ?></p>
                    <a href="<?= $cat['link'] ?>" class="cat-link">Shop Link</Link></a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- THE COLLECTIONS-->
    <section class="collections" id="collections">
        <div class="container">
            <div class="section-title reveal">
                <h2>The Collections</h2>
            </div>
        </div>
        <div class="coll-slider-wrap">
            <!-- Left Arrow -->
            <button class="coll-arrow coll-arrow--left" id="collPrev" aria-label="Previous">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>
            <!-- Right Arrow -->
            <button class="coll-arrow coll-arrow--right" id="collNext" aria-label="Next">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </button>
            <!-- Slider Track -->
            <div class="coll-track" id="collTrack">
                <?php foreach ($collections as $i => $col): ?>
                    <div class="coll-card reveal reveal-d<?= ($i % 3) + 1 ?>">
                        <h3 class="coll-card__title"><?= $col['label'] ?></h3>
                        <div class="coll-card__img-wrap">
                            <img src="<?= $col['image'] ?>" alt="<?= $col['label'] ?>">
                        </div>
                        <div class="coll-card__footer">
                            <span class="coll-card__price"><?= $col['price'] ?></span>
                            <button class="coll-card__bag" aria-label="Add to bag">
                                <?= $icons['bag'] ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- THE STORY OF THE MAISON-->
    <section class="story" id="story">
        <div class="container story-grid">
            <div class="story-img-wrap reveal">
                <img src="assets/images/story-perfume.png" alt="Maison Ungod Heritage">
            </div>
            <div class="story-content reveal reveal-d2">
                <h2>The Story of<br>the Maison</h2>
                <div class="story-bar"></div>
                <p>
                    At Maison Ungod, we believe a fragrance is more than a scent — it is an art of expression. The
                    Maison represents an unwavering dedication to the timeless art of perfumery, merging age-old methods
                    and modern sophistication.
                </p>
                <p>
                    Crafted from rare ingredients and the most exquisite raw materials, each collection is designed to
                    be a timeless, lingering legacy wherever you go.
                </p>
            </div>
        </div>
    </section>

    <!--FEATURES-->
    <section class="features" id="features">
        <div class="container">
            <?php foreach ($features as $feat): ?>
                <div class="feat-row <?= $feat['reverse'] ? 'feat-row--reverse' : '' ?> reveal">
                    <div class="feat-text">
                        <h3><?= $feat['title'] ?></h3>
                        <p><?= $feat['text'] ?></p>
                    </div>
                    <div class="feat-img">
                        <img src="<?= $feat['image'] ?>" alt="<?= $feat['title'] ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!--FOOTER -->
    <footer class="site-footer" id="contact">
        <div class="container">
            <div class="footer-top">
                <div class="footer-brand">
                    <img src="assets/logo/logo.png" alt="<?= $site['name'] ?>">
                    <div class="footer-social">
                        <?php foreach ($socials as $s): ?>
                            <a><?= $icons[$s['icon']] ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Menu</h4>
                    <ul>
                        <li><a>Home</a></li>
                        <li><a>Collections</a></li>
                        <li><a>Our Story</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Legal Page</h4>
                    <ul>
                        <li><a>Privacy Policy</a></li>
                        <li><a>Terms of Service</a></li>
                        <li><a>Cookie Policy</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="mailto:bobjoshuaungod08@gmail.com">bobjoshuaungod08@gmail.com</a></li>
                        <li><a href="tel:+639812106561">09812106561</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= $site['year'] ?> <?= $site['name'] ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>


</body>

</html>