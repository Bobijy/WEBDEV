/**
 * register.js — Standalone Registration Page Logic
 * Handles the register form on register.php with client-side
 * confirm-password check and AJAX submission to api/auth.php.
 */

document.getElementById('pageRegisterForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const btn  = document.getElementById('pageRegisterSubmit');
    const msg  = document.getElementById('pageRegisterMsg');
    const form = e.target;

    // Client-side confirm password check (server also validates this)
    const pwd        = document.getElementById('password').value;
    const confirmPwd = document.getElementById('confirm_password').value;

    if (pwd !== confirmPwd) {
        msg.className = 'account-msg error';
        msg.textContent = 'Passwords do not match.';
        msg.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'CREATING...';

    const formData = new FormData(form);
    formData.append('action', 'register');

    try {
        const response = await fetch('api/auth.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            msg.className = 'account-msg success';
            msg.textContent = 'Account created! Redirecting...';
            msg.style.display = 'block';
            window.location.href = 'account.php';
        } else {
            msg.className = 'account-msg error';
            msg.textContent = data.message;
            msg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'REGISTER';
        }
    } catch (err) {
        msg.className = 'account-msg error';
        msg.textContent = 'Connection error.';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'REGISTER';
    }
});
