// Account modal script: handles modal toggle, sign-in, and registration via API

(function () {
    'use strict';

    // DOM elements
    const accountOverlay = document.getElementById('accountOverlay');
    const accountModal = document.getElementById('accountModal');
    const accountClose = document.getElementById('accountClose');
    const accountToggles = document.querySelectorAll('#accountToggle');
    
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const showRegisterBtn = document.getElementById('showRegister');
    const showLoginBtn = document.getElementById('showLogin');
    
    // Dashboard DOM
    const profileView = document.getElementById('profileView');
    const updateProfileForm = document.getElementById('updateProfileForm');
    const logoutBtn = document.getElementById('logoutBtn');
    const ordersContainer = document.getElementById('ordersContainer');
    const authHeader = document.querySelector('.auth-header');
    
    // Inputs
    const fName = document.getElementById('updateName');
    const fEmail = document.getElementById('updateEmail');
    const fPhone = document.getElementById('updatePhone');
    const fAddress = document.getElementById('updateAddress');
    const fMsg = document.getElementById('updateMsg');

    // Open and close account modal
    async function openAccount() {
        if (!accountModal) return;
        
        try {
            const res = await fetch('api/auth.php?action=status');
            const data = await res.json();
            
            if (data.loggedIn) {
                window.location.href = 'account.php';
            } else {
                accountModal.classList.add('open');
                accountOverlay.classList.add('open');
                document.body.style.overflow = 'hidden';
                if (authHeader) authHeader.style.display = 'block';
                if (loginForm) loginForm.style.display = 'block';
                if (registerForm) registerForm.style.display = 'none';
                if (profileView) profileView.style.display = 'none';
            }
        } catch (err) {
            console.error('Failed to fetch auth status', err);
        }
    }

    function closeAccount() {
        if (!accountModal) return;
        accountModal.classList.remove('open');
        accountOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    accountToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            openAccount();
        });
    });

    if (accountClose) accountClose.addEventListener('click', closeAccount);
    if (accountOverlay) accountOverlay.addEventListener('click', closeAccount);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && accountModal && accountModal.classList.contains('open')) {
            closeAccount();
        }
    });

    // Switch between sign-in and registration forms
    if (showRegisterBtn) {
        showRegisterBtn.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
            if (authHeader) {
                const h2 = authHeader.querySelector('h2');
                if (h2) h2.textContent = 'Create Account';
            }
        });
    }
    if (showLoginBtn) {
        showLoginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
            if (authHeader) {
                const h2 = authHeader.querySelector('h2');
                if (h2) h2.textContent = 'Sign In';
            }
        });
    }

    // Handle sign-in form submission
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('loginSubmit');
            const msg = document.getElementById('loginMsg');
            
            window.MaisonUngod.clearFieldErrors(loginForm);
            
            btn.disabled = true;
            btn.textContent = 'SIGNING IN...';
            
            const formData = new FormData(loginForm);
            formData.append('action', 'login');
            formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
            
            try {
                const response = await fetch('api/auth.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'account.php';
                } else {
                    msg.className = 'account-msg error';
                    msg.textContent = data.message;
                    msg.style.display = 'block';
                    if (data.errors) {
                        window.MaisonUngod.displayFieldErrors(loginForm, data.errors);
                    }
                }
            } catch (err) {
                msg.className = 'account-msg error';
                msg.textContent = 'Connection error.';
                msg.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'SIGN IN';
        });
    }

    // Handle registration form submission
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('registerSubmit');
            const msg = document.getElementById('registerMsg');
            
            window.MaisonUngod.clearFieldErrors(registerForm);
            
            btn.disabled = true;
            btn.textContent = 'CREATING...';
            
            const formData = new FormData(registerForm);
            formData.append('action', 'register');
            formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
            
            try {
                const response = await fetch('api/auth.php', { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'account.php';
                } else {
                    msg.className = 'account-msg error';
                    msg.textContent = data.message;
                    msg.style.display = 'block';
                    if (data.errors) {
                        window.MaisonUngod.displayFieldErrors(registerForm, data.errors);
                    }
                }
            } catch (err) {
                msg.className = 'account-msg error';
                msg.textContent = 'Connection error.';
                msg.style.display = 'block';
            }
            btn.disabled = false;
            btn.textContent = 'CREATE ACCOUNT';
        });
    }

    // Check for login query parameter in URL (e.g. ?login=1)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login') === '1') {
        openAccount();
        // Remove parameter from URL without reloading
        window.history.replaceState({}, document.title, window.location.pathname);
    }

})();
