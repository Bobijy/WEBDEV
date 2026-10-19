/**
 * login.js — Standalone Login Page Logic
 * Handles sign in submission to api/auth.php, password toggle, and redirecting.
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('pageLoginForm');
    const submitBtn = document.getElementById('pageLoginSubmit');
    const msgBox    = document.getElementById('pageLoginMsg');
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput  = document.getElementById('password');

    // Password visibility toggle
    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = pwdInput.type === 'password';
            pwdInput.type = isPassword ? 'text' : 'password';

            toggleBtn.innerHTML = isPassword
                ? `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                     <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                     <line x1="1" y1="1" x2="23" y2="23"></line>
                   </svg>`
                : `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                     <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                     <circle cx="12" cy="12" r="3"></circle>
                   </svg>`;
            toggleBtn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    }

    if (!loginForm) return;

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (window.MaisonUngod && typeof window.MaisonUngod.clearFieldErrors === 'function') {
            window.MaisonUngod.clearFieldErrors(loginForm);
        }

        const email = document.getElementById('email').value.trim();
        const password = pwdInput.value;

        if (!email || !password) {
            msgBox.className = 'account-msg error';
            msgBox.textContent = 'Please fill in both email and password.';
            msgBox.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'SIGNING IN...';
        msgBox.style.display = 'none';

        const formData = new FormData(loginForm);
        formData.append('action', 'login');
        
        const csrfToken = window.MaisonUngod && typeof window.MaisonUngod.getCSRFToken === 'function'
            ? window.MaisonUngod.getCSRFToken()
            : (document.querySelector('meta[name="csrf-token"]')?.content || '');
            
        formData.append('csrf_token', csrfToken);

        try {
            const res = await fetch('api/login.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                msgBox.className = 'account-msg success';
                msgBox.textContent = 'Signed in successfully! Redirecting...';
                msgBox.style.display = 'block';

                const redirectParam = document.getElementById('redirectUrl')?.value;
                let destination = 'index.php';

                if (data.user && data.user.role === 'admin') {
                    destination = 'admin/index.php';
                } else if (redirectParam) {
                    destination = redirectParam;
                } else if (data.redirect) {
                    destination = data.redirect;
                }

                setTimeout(() => {
                    window.location.href = destination;
                }, 400);
            } else {
                msgBox.className = 'account-msg error';
                msgBox.textContent = data.message || 'Invalid email or password.';
                msgBox.style.display = 'block';

                if (data.errors && window.MaisonUngod && typeof window.MaisonUngod.displayFieldErrors === 'function') {
                    window.MaisonUngod.displayFieldErrors(loginForm, data.errors);
                }

                submitBtn.disabled = false;
                submitBtn.textContent = 'SIGN IN';
            }
        } catch (err) {
            console.error('Sign in error:', err);
            msgBox.className = 'account-msg error';
            msgBox.textContent = 'Connection error. Please try again.';
            msgBox.style.display = 'block';

            submitBtn.disabled = false;
            submitBtn.textContent = 'SIGN IN';
        }
    });
});
