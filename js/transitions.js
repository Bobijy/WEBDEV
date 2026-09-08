// Page Transitions: smooth page transitions and scroll position restoration

(function () {
    'use strict';
    return; // Disabled by user request

    // Create transition overlay element
    const overlay = document.createElement('div');
    overlay.className = 'page-transition';
    overlay.innerHTML = '<div class="page-transition__bar"></div>';
    document.body.appendChild(overlay);

    // Save scroll position before leaving
    function saveScrollPosition() {
        sessionStorage.setItem('mu_scroll_' + window.location.pathname, window.scrollY);
    }

    // Restore scroll position on load
    function restoreScrollPosition() {
        const saved = sessionStorage.getItem('mu_scroll_' + window.location.pathname);
        if (saved && !window.location.hash) {
            // Wait for page to render, then smooth-scroll
            requestAnimationFrame(() => {
                window.scrollTo({ top: parseInt(saved, 10), behavior: 'instant' });
            });
        }
    }

    // Intercept internal link clicks
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');

        // Skip non-navigating links
        if (!href ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            link.target === '_blank' ||
            link.hasAttribute('data-no-transition') ||
            e.ctrlKey || e.metaKey || e.shiftKey) {
            return;
        }

        // Skip external links
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return;

            // Skip transition to/from shop
            if (url.pathname.includes('shop.php') || window.location.pathname.includes('shop.php')) {
                return;
            }
        } catch (err) {
            return;
        }

        // Trigger transition
        e.preventDefault();
        saveScrollPosition();

        overlay.classList.add('active');

        setTimeout(() => {
            window.location.href = href;
        }, 400);
    });

    // Fade in on page load
    window.addEventListener('pageshow', function (e) {
        // Handle back/forward cache
        if (e.persisted) {
            overlay.classList.remove('active');
        }
        overlay.classList.remove('active');
        restoreScrollPosition();
    });

    // Ensure overlay is hidden on initial load
    window.addEventListener('DOMContentLoaded', function () {
        // Small delay to ensure page has rendered
        setTimeout(() => {
            overlay.classList.remove('active');
        }, 100);
    });
})();
