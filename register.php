 __DIR__ . '/database/helpers.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: account.php");
    exit;
}

$site = ['name' => 'Maison Ungod'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="csrf-token" content="<?= CSRF::generate() ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $site['name'] ?> — Register</title>
    <link rel="stylesheet" href="css/style.css?v=3">
    <link rel="stylesheet" href="css/animations.css?v=1">
    <link rel="stylesheet" href="css/pages/auth.css?v=1">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-header">
                <a href="index.php">— <?= strtoupper($site['name']) ?> —</a>
                <h1>Create Account</h1>
            </div>
            
            <form class="account-form" id="pageRegisterForm">
                <div class="account-msg" id="pageRegisterMsg"></div>
                <div class="account-field">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="John Doe" required>
                </div>
                <div class="account-field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="account-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                <div class="account-field">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="account-submit" id="pageRegisterSubmit">Register</button>
            </form>
            
            <div class="auth-links">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/register.js?v=2"></script>
</body>
</html>
