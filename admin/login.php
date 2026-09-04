<?php
require_once __DIR__ . '/includes/header.php';

// Redirect to dashboard if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header("Location: index.php");
    exit;
}
?>
<div style="width: 100vw; height: 100vh; display: flex; align-items: center; justify-content: center; background: #0A0A0A;">
    <div style="background: #171717; padding: 40px; border: 1px solid #2E2E2E; border-radius: 8px; width: 100%; max-width: 400px; text-align: center;">
        <h2 style="font-family: var(--font-heading); color: var(--accent); margin-bottom: 20px;">Admin Login</h2>
        <form id="adminLoginForm">
            <div id="loginMsg" style="display:none; margin-bottom:15px; font-size:0.85rem; padding:10px; border-radius:4px;"></div>
            <div class="form-group" style="text-align: left;">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group" style="text-align: left; position: relative;">
                <label>Password</label>
                <input type="password" name="password" id="adminPassword" class="form-control" required>
                <button type="button" id="togglePassword" style="position:absolute; right:10px; top:32px; background:none; border:none; color:#8A8A8A; cursor:pointer;">Show</button>
            </div>
            <button type="submit" class="btn" id="loginSubmitBtn" style="width: 100%; margin-top: 10px;">Log In</button>
        </form>
        <div style="margin-top:20px; font-size:0.85rem;">
            <a href="../index.php" style="color: #8A8A8A; text-decoration:none;">&larr; Back to Store</a>
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('adminPassword');
        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = 'Hide';
        } else {
            input.type = 'password';
            this.textContent = 'Show';
        }
    });

    document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const msg = document.getElementById('loginMsg');
        const btn = document.getElementById('loginSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Authenticating...';

        const formData = new FormData(e.target);
        formData.append('action', 'login');

        try {
            const res = await fetch('../api/auth.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success && data.user && data.user.role === 'admin') {
                window.location.href = 'index.php';
            } else if (data.success) {
                // Logged in successfully but not an admin
                await fetch('../api/auth.php?action=logout'); // Log them out immediately
                msg.style.display = 'block';
                msg.style.background = 'rgba(231, 76, 60, 0.2)';
                msg.style.color = '#e74c3c';
                msg.textContent = 'Access Denied: You do not have administrator privileges.';
            } else {
                msg.style.display = 'block';
                msg.style.background = 'rgba(231, 76, 60, 0.2)';
                msg.style.color = '#e74c3c';
                msg.textContent = data.message;
            }
        } catch (err) {
            msg.style.display = 'block';
            msg.textContent = 'Connection error.';
        }
        
        btn.disabled = false;
        btn.textContent = 'Log In';
    });
</script>
</body>
</html>
