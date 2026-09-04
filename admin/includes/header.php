<?php
session_start();
// Protect routes: Redirect to admin login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header("Location: login.php");
        exit;
    }
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison Ungod — Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<?php if ($current_page !== 'login.php'): ?>
    <aside class="admin-sidebar">
        <div class="sidebar-brand">Maison Ungod</div>
        <nav class="sidebar-nav">
            <a href="index.php" <?= $current_page === 'index.php' ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="products.php" <?= $current_page === 'products.php' ? 'class="active"' : '' ?>>Products</a>
            <a href="orders.php" <?= $current_page === 'orders.php' ? 'class="active"' : '' ?>>Orders</a>
            <a href="users.php" <?= $current_page === 'users.php' ? 'class="active"' : '' ?>>Users</a>
        </nav>
        <nav class="sidebar-nav sidebar-bottom">
            <a href="../index.php">View Store</a>
            <a href="#" id="adminLogoutBtn">Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h2 id="pageTitle">Admin Panel</h2>
        </header>
        <div class="admin-content">
<?php endif; ?>
