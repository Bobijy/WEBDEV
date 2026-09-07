<?php
session_start();
require_once __DIR__ . '/../../database/helpers.php';

// Protect routes: Redirect to admin login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header("Location: login.php");
        exit;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['first_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison Ungod — Admin Dashboard</title>
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin.css?v=2">
</head>
<body>
<?php if ($current_page !== 'login.php'): ?>
<div class="app-wrapper">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <img src="../assets/logo/logo.png" width="28" height="28" alt="Logo" style="object-fit: contain;">
            <div>
                Maison Ungod
                <span class="sidebar-brand-subtitle">Admin Panel</span>
            </div>
        </div>
        
        <div class="sidebar-heading">Menu</div>
        <nav class="sidebar-nav">
            <a href="index.php" <?= $current_page === 'index.php' ? 'class="active"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="products.php" <?= $current_page === 'products.php' ? 'class="active"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Products
            </a>
            <a href="orders.php" <?= $current_page === 'orders.php' ? 'class="active"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Orders
            </a>
            <a href="users.php" <?= $current_page === 'users.php' ? 'class="active"' : '' ?>>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Users
            </a>
        </nav>

        <div class="sidebar-heading" style="margin-top:30px;">Store</div>
        <nav class="sidebar-nav sidebar-bottom">
            <a href="../index.php">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                View Store
            </a>
        </nav>

        <div class="sidebar-user" id="adminLogoutBtn">
            <img src="../assets/user-placeholder.jpg" alt="Avatar" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=18181F&color=7C3AED'">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($admin_name) ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color:var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        
        <!-- TOP NAV -->
        <nav class="top-nav">
            <div class="top-nav-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="top-nav-title" id="pageTitle">Admin Panel</div>
            </div>
            
            <div class="top-nav-right" style="position:relative; display: flex; align-items: center; gap: 24px;">
                <!-- Links removed as per user request -->
            </div>
        </nav>

        <div class="admin-content">
<?php endif; ?>
