// Admin login script: handles password toggle and login submission on admin/login.php

// Show or hide password toggle
document.getElementById('togglePassword').addEventListener('click', function () {
    const input = document.getElementById('adminPassword');
    if (input.type === 'password') {
        input.type = 'text';
        this.textContent = 'Hide';
    } else {
        input.type = 'password';
        this.textContent = 'Show';
    }
});

// Handle admin login submission
document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const msg = document.getElementById('loginMsg');
    const btn = document.getElementById('loginSubmitBtn');

    btn.disabled = true;
    btn.textContent = 'Authenticating...';

    const formData = new FormData(e.target);
    formData.append('action', 'login');

    try {
        const res  = await fetch('../api/auth.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success && data.user && data.user.role === 'admin') {
            window.location.href = 'index.php';
        } else if (data.success) {
            // Logged in but not an admin — log out immediately
            await fetch('../api/auth.php?action=logout');
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
