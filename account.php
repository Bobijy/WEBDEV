<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?login=1");
    exit;
}

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/database/helpers.php';

// Fetch user data
$user = db_fetch($pdo,
    'SELECT full_name, email, phone, address, gender, dob FROM users WHERE id = :id',
    [':id' => (int) $_SESSION['user_id']]
);

// Parse DOB if available
$dob_date = $dob_month = $dob_year = '';
if (!empty($user['dob'])) {
    $dob_parts = explode('-', $user['dob']);
    if (count($dob_parts) === 3) {
        $dob_year = $dob_parts[0];
        $dob_month = ltrim($dob_parts[1], '0'); // remove leading zero for match
        $dob_date = ltrim($dob_parts[2], '0');
    }
}


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

// Fetch user addresses
$addresses = db_fetch_all($pdo, '
    SELECT * FROM user_addresses 
    WHERE user_id = :uid 
    ORDER BY is_default DESC, id DESC
', [':uid' => (int) $_SESSION['user_id']]);

$site = [
    'name' => 'Maison Ungod',
    'tagline' => 'Find Your Signature Scent',
    'description' => 'Shop the exclusive Maison Ungod fragrance collection — luxury perfumes crafted with rare ingredients.',
    'year' => date('Y'),
];

// Navigation Links
$navLinks = [
    ['label' => 'Home', 'href' => 'index.php#hero', 'active' => false],
    ['label' => 'Shop', 'href' => 'shop.php', 'active' => false],
    ['label' => 'Best Seller', 'href' => 'index.php#collections', 'active' => false],
    ['label' => 'Our Story', 'href' => 'index.php#story', 'active' => false],
    ['label' => 'Contact', 'href' => '#', 'id' => 'contactToggle', 'active' => false],
];

// SVG Icons
$icons = [
    'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
    'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
    'x' => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.733 16h4.267l-11.733 -16zM4 20l6.768 -6.768M13.232 10.768L20 4"/></svg>',
    'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    'bag' => '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 00 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
];

// Social Links
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
    <title><?= htmlspecialchars($site['name']) ?> &mdash; My Account</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Shared styles for navbar + footer -->
    <link rel="stylesheet" href="css/style.css?v=11">
    <link rel="stylesheet" href="css/animations.css?v=2">
    
    <link rel="stylesheet" href="css/pages/account.css?v=8">
    
    <style>
        .account-page-wrapper {
            padding-top: 130px;
            padding-bottom: 80px;
            min-height: 80vh;
        }
    </style>
</head>
<body>

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
            </div>
        </div>
    </nav>

    <div class="account-page-wrapper">
<div class="account-layout">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-avatar">MU</div>
            <div class="sidebar-username"><?= htmlspecialchars($user['full_name'] ?? 'bobjoshua005') ?></div>
            <div class="sidebar-join-date">Joined May 2024</div>
        </div>
        <div class="sidebar-nav">
            <a class="nav-item active" onclick="switchTab('profile')" id="nav-profile">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                My Profile
            </a>
            <a class="nav-item" onclick="switchTab('orders')" id="nav-orders">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                Orders
            </a>

            <a class="nav-item" onclick="switchTab('addresses')" id="nav-addresses" style="cursor:pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                Addresses
            </a>
            <a class="nav-item" onclick="openPasswordModal()" style="cursor:pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Change Password
            </a>
            <a class="nav-item" id="logoutBtnNav" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="main-content">
        
        <!-- PROFILE TAB -->
        <div class="tab-content active" id="tab-profile">
            <div class="main-header">
                <h2>My Profile</h2>
                <p>Manage and protect your account</p>
            </div>
            
            <div class="divider"></div>
            
            <form id="inlineProfileForm" class="profile-form">
                <div class="edit-msg" id="editMsg"></div>
                
                <div class="form-row">
                    <div class="form-label">Username</div>
                    <div class="form-value">
                        <span class="form-text"><?= htmlspecialchars(explode('@', $user['email'])[0]) ?></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Name</div>
                    <div class="form-value">
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" placeholder="Enter your name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Email</div>
                    <div class="form-value">
                        <?php if(empty($user['email'])): ?>
                            <button type="button" class="text-link" onclick="openChangeFieldModal('email', '')">Add</button>
                        <?php else: ?>
                            <span class="form-text" style="color: var(--text-light);"><?= htmlspecialchars(substr($user['email'], 0, 2) . '***@' . (strpos($user['email'], '@') !== false ? explode('@', $user['email'])[1] : '')) ?></span>
                            <button type="button" class="text-link" onclick="openChangeFieldModal('email', '<?= htmlspecialchars($user['email']) ?>')">Change</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Phone Number</div>
                    <div class="form-value">
                        <?php if(empty($user['phone'])): ?>
                            <button type="button" class="text-link" onclick="openChangeFieldModal('phone', '')">Add</button>
                        <?php else: ?>
                            <span class="form-text" style="color: var(--text-light); display: inline-flex; align-items: center;">
                                <span style="transform: translateY(-1px); letter-spacing: 2px; margin-right: 2px;">**********</span>
                                <span><?= htmlspecialchars(substr($user['phone'], -2)) ?></span>
                            </span>
                            <button type="button" class="text-link" onclick="openChangeFieldModal('phone', '<?= htmlspecialchars($user['phone']) ?>')">Change</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Gender <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                    <div class="form-value">
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="gender" value="Male" <?= (isset($user['gender']) && $user['gender'] === 'Male') ? 'checked' : '' ?>> Male
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="gender" value="Female" <?= (isset($user['gender']) && $user['gender'] === 'Female') ? 'checked' : '' ?>> Female
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="gender" value="Other" <?= (isset($user['gender']) && $user['gender'] === 'Other') ? 'checked' : '' ?>> Other
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-label">Date of birth <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                    <div class="form-value">
                        <div class="dob-group">
                            <?php
                            $dob_date = '';
                            $dob_month = '';
                            $dob_year = '';
                            if (!empty($user['dob'])) {
                                $parts = explode('-', $user['dob']);
                                if (count($parts) === 3) {
                                    $dob_year = (int)$parts[0];
                                    $dob_month = (int)$parts[1];
                                    $dob_date = (int)$parts[2];
                                }
                            }
                            ?>
                            <div class="select-wrapper">
                                <select name="dob_date" class="form-select">
                                    <option value="">Date</option>
                                    <?php 
                                    for($i=1; $i<=31; $i++) {
                                        $sel = ($dob_date == $i) ? 'selected' : '';
                                        echo "<option value='$i' $sel>$i</option>"; 
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="select-wrapper">
                                <select name="dob_month" class="form-select">
                                    <option value="">Month</option>
                                    <?php 
                                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                    foreach($months as $index => $m) {
                                        $m_val = $index + 1;
                                        $sel = ($dob_month == $m_val) ? 'selected' : '';
                                        echo "<option value='$m_val' $sel>$m</option>"; 
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="select-wrapper">
                                <select name="dob_year" class="form-select">
                                    <option value="">Year</option>
                                    <?php 
                                    for($i=date('Y'); $i>=1900; $i--) {
                                        $sel = ($dob_year == $i) ? 'selected' : '';
                                        echo "<option value='$i' $sel>$i</option>"; 
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-save" id="inlineModalSubmit">Save</button>
            </form>
        </div>
        
        <!-- ORDERS TAB -->
        <div class="tab-content" id="tab-orders">
            <div class="main-header">
                <h2>My Orders</h2>
                <p>View and track your purchases</p>
            </div>
            
            <div class="divider"></div>
            
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
                            <div style="display:flex; gap:10px;">
                                <?php if ($order['status'] === 'Pending'): ?>
                                    <button class="order-card-btn" style="background:transparent; border:1px solid var(--border); color:var(--text-main);" onclick="event.preventDefault(); cancelOrder(<?= $order['id'] ?>);">Cancel</button>
                                <?php endif; ?>
                                <button class="order-card-btn" onclick="event.preventDefault(); window.location.href='shop.php';">Buy again</button>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- ADDRESSES TAB -->
        <div class="tab-content" id="tab-addresses">
            <div class="main-header" style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <h2>My Addresses</h2>
                    <p>Manage your shipping addresses</p>
                </div>
                <button class="btn-save" style="margin-top:0;" onclick="openAddressModal()">+ Add New Address</button>
            </div>
            
            <div class="divider"></div>
            
            <div id="addressList">
                <?php if (empty($addresses)): ?>
                    <p style="color: var(--text-light);">You haven't saved any addresses yet.</p>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-card <?= $addr['is_default'] ? 'default-address' : '' ?>">
                            <div class="address-card-main">
                                <div class="address-icon">
                                    <?php if ($addr['is_default']): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect><path d="M9 22v-4h6v4"></path><path d="M8 6h.01"></path><path d="M16 6h.01"></path><path d="M12 6h.01"></path><path d="M12 10h.01"></path><path d="M12 14h.01"></path><path d="M16 10h.01"></path><path d="M16 14h.01"></path><path d="M8 10h.01"></path><path d="M8 14h.01"></path></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="address-info">
                                    <div class="address-card-header">
                                        <h3><?= htmlspecialchars($addr['full_name']) ?></h3>
                                        <?php if ($addr['is_default']): ?>
                                            <span class="badge-default">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                                DEFAULT
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-card-body">
                                        <p>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                            <?= htmlspecialchars($addr['phone']) ?>
                                        </p>
                                        <p>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                            <?= htmlspecialchars($addr['address_line']) ?><?= !empty($addr['postal_code']) ? ', ' . htmlspecialchars($addr['postal_code']) : '' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="address-card-actions">
                                <div>
                                    <button class="text-link" onclick="openAddressModal(<?= htmlspecialchars(json_encode($addr)) ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                        Edit
                                    </button>
                                    <button class="text-link delete-btn" onclick="deleteAddress(<?= $addr['id'] ?>)">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        Delete
                                    </button>
                                </div>
                                <?php if (!$addr['is_default']): ?>
                                    <button class="btn-outline" onclick="setDefaultAddress(<?= $addr['id'] ?>)">Set as Default</button>
                                <?php else: ?>
                                    <button class="btn-outline is-default" disabled>
                                        <svg style="vertical-align: text-bottom; margin-right: 4px;" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        Default Address
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>
</div> <!-- Close account-layout -->

<!-- Change Field Modal (Email/Phone) -->
<div class="edit-modal-overlay" id="changeFieldModalOverlay">
    <div class="edit-modal" style="max-width: 400px;">
        <button class="edit-close" onclick="closeChangeFieldModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h2 id="changeFieldModalTitle">Change</h2>
        <form id="changeFieldForm">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="edit-msg" id="changeFieldMsg"></div>
            
            <div class="form-group" id="changeFieldContainer">
                <label id="changeFieldLabel" for="changeFieldValue">New Value</label>
                <input type="text" id="changeFieldValue" name="" required>
            </div>
            
            <div class="modal-actions" style="display:flex; justify-content:flex-end; gap:16px; margin-top:20px;">
                <button type="button" class="btn-small" style="background:transparent; border:none; color:var(--text-main);" onclick="closeChangeFieldModal()">Cancel</button>
                <button type="submit" class="btn-save" id="changeFieldSubmitBtn">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div class="edit-modal-overlay" id="passwordModalOverlay">
    <div class="edit-modal pw-modal" style="max-width: 460px; background: #121216; border: 1px solid rgba(113,65,107,0.3); border-radius: 12px; padding: 24px 30px;">
        <button class="edit-close" onclick="closePasswordModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h2 style="font-family: var(--font-body); font-weight: 600; font-size: 1.25rem; color: var(--brand-light); margin-bottom: 16px; text-transform: none; text-align: left;">Change Password</h2>
        
        <div class="pw-icon-wrapper" style="text-align: center; margin-bottom: 24px; position: relative;">
            <div style="width: 80px; height: 80px; border-radius: 50%; border: 1px solid rgba(113,65,107,0.2); display: inline-flex; justify-content: center; align-items: center; position: relative;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(113,65,107,0.4); display: flex; justify-content: center; align-items: center; color: var(--brand-light); box-shadow: 0 0 15px rgba(113,65,107,0.3);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C9.243 2 7 4.243 7 7v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7c0-2.757-2.243-5-5-5zM9 7c0-1.654 1.346-3 3-3s3 1.346 3 3v3H9V7zm4 10.723V19h-2v-1.277a1.993 1.993 0 0 1 .567-3.677A2.001 2.001 0 0 1 14 16a1.99 1.99 0 0 1-1 1.723z"/></svg>
                </div>
            </div>
        </div>

        <form id="passwordForm">
            <input type="hidden" name="action" value="change_password">
            
            <div class="edit-msg" id="passwordMsg"></div>
            
            <div class="pw-form-group">
                <label for="current_password">Current Password</label>
                <div class="pw-input-wrapper">
                    <svg class="pw-icon-left" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" id="current_password" name="current_password" required>
                    <button type="button" class="pw-toggle-btn" onclick="togglePw('current_password')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            
            <div class="pw-form-group">
                <label for="new_password">New Password</label>
                <div class="pw-input-wrapper">
                    <svg class="pw-icon-left" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" id="new_password" name="new_password" required minlength="6" oninput="checkPwStrength(this.value)">
                    <button type="button" class="pw-toggle-btn" onclick="togglePw('new_password')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>

            <div class="pw-form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div class="pw-input-wrapper">
                    <svg class="pw-icon-left" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    <button type="button" class="pw-toggle-btn" onclick="togglePw('confirm_password')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                </div>
            </div>
            
            <div class="pw-strength-container" style="display:flex; align-items:center; gap: 12px; margin-bottom: 24px; margin-top: 16px;">
                <div style="display:flex; align-items:center; gap:6px; color: #4CAF50; font-size: 0.8rem; font-weight: 600;" id="pwStrengthLabel">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                    <span id="pwStrengthText">Strong</span>
                </div>
                <div style="display:flex; gap: 4px; flex: 1;" id="pwStrengthBars">
                    <div class="pw-strength-bar" style="height: 4px; flex: 1; background: var(--brand-light); border-radius: 2px;"></div>
                    <div class="pw-strength-bar" style="height: 4px; flex: 1; background: var(--brand-light); border-radius: 2px;"></div>
                    <div class="pw-strength-bar" style="height: 4px; flex: 1; background: var(--brand-light); border-radius: 2px;"></div>
                    <div class="pw-strength-bar" style="height: 4px; flex: 1; background: var(--brand-light); border-radius: 2px;"></div>
                    <div class="pw-strength-bar" style="height: 4px; flex: 1; background: #2E2E2E; border-radius: 2px;"></div>
                </div>
            </div>
            
            <div class="modal-actions" style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="pw-btn-cancel" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="pw-btn-save" id="passwordSubmitBtn">Save Password</button>
            </div>
        </form>
        
        <script>
            function togglePw(id) {
                const input = document.getElementById(id);
                if(input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            }
            
            function checkPwStrength(val) {
                const bars = document.querySelectorAll('#pwStrengthBars .pw-strength-bar');
                const label = document.getElementById('pwStrengthLabel');
                const text = document.getElementById('pwStrengthText');
                
                let score = 0;
                if(val.length > 5) score++;
                if(val.length > 8) score++;
                if(/[A-Z]/.test(val)) score++;
                if(/[0-9]/.test(val)) score++;
                if(/[^A-Za-z0-9]/.test(val)) score++;
                
                const colors = ['#2E2E2E', '#F44336', '#FF9800', '#FFC107', 'var(--brand-light)', 'var(--brand-light)'];
                const labels = ['None', 'Weak', 'Fair', 'Good', 'Strong', 'Strong'];
                const labelColors = ['#2E2E2E', '#F44336', '#FF9800', '#FFC107', '#4CAF50', '#4CAF50'];
                
                bars.forEach((bar, idx) => {
                    bar.style.background = idx < score ? colors[score] : '#2E2E2E';
                });
                
                text.textContent = val.length === 0 ? 'None' : labels[score];
                label.style.color = val.length === 0 ? '#8A8A8A' : labelColors[score];
            }
        </script>
    </div>
</div>

<!-- Address Modal -->
<div class="edit-modal-overlay" id="addressModalOverlay">
    <div class="edit-modal">
        <button class="edit-close" onclick="closeAddressModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <h2 id="addressModalTitle">New Address</h2>
        <form id="addressForm">
            <input type="hidden" name="id" id="addr_id">
            <input type="hidden" name="action" id="addr_action" value="add">
            <div class="edit-msg" id="addrMsg"></div>
            
            <div class="form-group">
                <label for="addr_full_name">Full Name</label>
                <input type="text" id="addr_full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="addr_phone">Phone Number</label>
                <input type="text" id="addr_phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="addr_line">Address</label>
                <textarea id="addr_line" name="address_line" rows="3" required style="width: 100%; background: transparent; border: 1px solid var(--border-light); color: var(--text-main); padding: 10px 16px; border-radius: 4px; font-family: inherit; font-size: 0.95rem; resize: vertical;"></textarea>
            </div>
            <div class="form-group">
                <label for="addr_postal_code">Postal Code</label>
                <input type="text" id="addr_postal_code" name="postal_code" placeholder="e.g. 1000">
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="is_default" id="addr_is_default" value="1">
                    <span style="font-size:0.9rem; color:var(--text-light);">Set as default address</span>
                </label>
            </div>
            <div class="modal-actions" style="display:flex; justify-content:flex-end; gap:16px; margin-top:20px;">
                <button type="button" class="btn-small" style="background:transparent; border:none; color:var(--text-main);" onclick="closeAddressModal()">Cancel</button>
                <button type="submit" class="btn-save" id="addrSubmitBtn">Save Address</button>
            </div>
        </form>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="edit-modal-overlay" id="cancelOrderModalOverlay">
    <div class="edit-modal" style="max-width: 400px; text-align: center; padding: 40px 30px;">
        <button class="edit-close" onclick="closeCancelOrderModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        
        <div style="background: rgba(229, 57, 53, 0.1); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#e53935" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        
        <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Cancel Order?</h2>
        <p style="color: var(--text-light); margin-bottom: 25px; font-size: 0.95rem; line-height: 1.5;">Are you sure you want to cancel this order? This action cannot be undone and your items will be removed from your purchases.</p>
        
        <div class="edit-msg" id="cancelOrderMsg" style="text-align: left;"></div>
        
        <div class="modal-actions" style="display:flex; gap:16px; margin-top:10px;">
            <button type="button" class="btn-save" style="flex: 1; background: transparent; border: 1px solid var(--border); color: var(--text-main);" onclick="closeCancelOrderModal()">Keep Order</button>
            <button type="button" class="btn-save" style="flex: 1; background: #e53935; border: 1px solid #e53935;" id="confirmCancelOrderBtn">Yes, Cancel</button>
        </div>
    </div>
</div>

    </div> <!-- Close account-page-wrapper -->

    <!-- Footer -->
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


    <!-- Scroll-to-Top Button -->
    <button class="scroll-to-top" aria-label="Scroll to top">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    <script src="js/main.js?v=5"></script>
    <script src="js/cart.js?v=5"></script>
    <script src="js/search.js?v=4"></script>
    <script src="js/contact.js?v=3"></script>
    <script src="js/transitions.js?v=4"></script>
    <script src="js/account-page.js?v=8"></script>
</body>
</html>


