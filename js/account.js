/**
 * account.js — Account helpers and fallback redirection
 */

(function () {
    'use strict';

    // Global helper to navigate to account/login
    function openAccount() {
        window.location.href = 'login.php';
    }

    function closeAccount() {
        // No-op kept for backwards compatibility
    }

    // Expose helpers globally
    window.MaisonUngod = window.MaisonUngod || {};
    window.MaisonUngod.openAccount = openAccount;
    window.MaisonUngod.closeAccount = closeAccount;

    // Support ?login=1 query redirect if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('login') === '1') {
        window.location.href = 'login.php';
    }
})();
