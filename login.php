<?php
require_once __DIR__ . '/database/helpers.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$site = ['name' => 'Maison Ungod'];
$redirect = $_GET['redirect'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site['name'] ?> — Sign In</title>
    <link rel="stylesheet" href="css/style.css?v=20">
    <link rel="stylesheet" href="css/animations.css?v=1">
    <link rel="stylesheet" href="css/pages/auth.css?v=2">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-header">
                <a href="index.php" class="brand-link">— <?= strtoupper($site['name']) ?> —</a>
                <h1>Sign In</h1>
                <p class="auth-subtitle">Please enter your details to sign in.</p>
            </div>
            
            <form class="account-form" id="pageLoginForm" novalidate>
                <?php if (!empty($redirect)): ?>
                    <input type="hidden" name="redirect" id="redirectUrl" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                
                <div class="account-msg" id="pageLoginMsg" style="display: none;"></div>
                
                <div class="account-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required autocomplete="email">
                </div>
                
                <div class="account-field">
                    <label for="password">Password</label>
                    <div class="auth-password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="auth-password-toggle" id="togglePassword" aria-label="Toggle password visibility">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="account-submit" id="pageLoginSubmit">Sign In</button>
            </form>
            
            <div class="auth-links">
                Don't have an account? <a href="register.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">Register</a>
            </div>

            <div class="auth-back">
                <a href="index.php">&larr; Back to Store</a>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/login.js?v=2"></script>
</body>
</html>
