// Search overlay: search products and site pages

(function () {
    'use strict';

    // DOM elements
    const searchOverlay = document.getElementById('searchOverlay');
    const searchToggles = document.querySelectorAll('#searchToggle, .nav-icon[aria-label="Search"]');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if (!searchOverlay || !searchInput) return;

    // Available products for instant search
    let products = [];
    let searchRequest = null;
    // Site pages
    const pages = [
        { name: 'Home', link: '/' },
        { name: 'Shop — All Products', link: '/shop' },
        { name: 'Best Seller — Collections', link: '/#collections' },
        { name: 'Our Story', link: '/#story' },
        { name: 'Contact', link: '/#contact' },
    ];

    // Open and close search modal
    function openSearch() {
        searchOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        // Auto-focus input after animation
        setTimeout(() => searchInput.focus(), 100);
    }

    function closeSearch() {
        searchOverlay.classList.remove('open');
        document.body.style.overflow = '';
        searchRequest?.abort();
        searchRequest = null;
        clearTimeout(debounceTimer);
        searchInput.value = '';
        searchResults.innerHTML = '<p class="search-hint">Type to search fragrances...</p>';
    }

    if (searchToggles) {
        searchToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                openSearch();
            });
        });
    }

    if (searchClose) searchClose.addEventListener('click', closeSearch);

    // Close on clicking overlay background (not the modal)
    searchOverlay.addEventListener('click', (e) => {
        if (e.target === searchOverlay) closeSearch();
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchOverlay.classList.contains('open')) {
            closeSearch();
        }
    });

    // Keyboard shortcut: Ctrl+K or Cmd+K
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchOverlay.classList.contains('open')) {
                closeSearch();
            } else {
                openSearch();
            }
        }
    });

    // Filter and display search results
    async function performSearch(query) {
        const q = query.toLowerCase().trim();

        if (!q) {
            searchRequest?.abort();
            searchResults.innerHTML = '<p class="search-hint">Type to search fragrances...</p>';
            return;
        }

        searchRequest?.abort();
        const controller = new AbortController();
        searchRequest = controller;
        searchResults.textContent = 'Searching…';
        try {
            const response = await fetch('/api/products/search?q=' + encodeURIComponent(q), { signal: controller.signal });
            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            if (searchRequest !== controller) return;
            products = data.products;
        } catch (error) {
            if (error.name !== 'AbortError') searchResults.textContent = 'Search is unavailable. Please try again.';
            return;
        }

        // Filter products
        const matchedProducts = products; // The server searches both name and brand.

        // Filter pages
        const matchedPages = pages.filter(p =>
            p.name.toLowerCase().includes(q)
        );

        // No results
        if (matchedProducts.length === 0 && matchedPages.length === 0) {
            searchResults.innerHTML = `
                <p class="search-no-results">No results for "<strong>${escapeHTML(query)}</strong>"</p>
            `;
            return;
        }

        let html = '';

        // Products section
        if (matchedProducts.length > 0) {
            html += '<p class="search-category">Products</p>';
            matchedProducts.forEach(p => {
                html += `
                    <a href="${escapeHTML(p.link)}" class="search-result" data-product="${escapeHTML(p.name)}">
                        <div class="search-result__thumb">
                            <img src="${escapeHTML(p.image)}" alt="${escapeHTML(p.name)}">
                        </div>
                        <div class="search-result__info">
                            <h4 class="search-result__name">${highlightMatch(p.name, q)}</h4>
                            <span class="search-result__price">${escapeHTML(p.price)}</span>
                        </div>
                        <span class="search-result__action">View</span>
                    </a>
                `;
            });
        }

        // Pages section
        if (matchedPages.length > 0) {
            html += '<p class="search-category">Pages</p>';
            matchedPages.forEach(p => {
                html += `
                    <a href="${p.link}" class="search-result">
                        <div class="search-result__thumb" style="display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="#8A8A8A" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                                <polyline points="13 2 13 9 20 9"/>
                            </svg>
                        </div>
                        <div class="search-result__info">
                            <h4 class="search-result__name">${highlightMatch(p.name, q)}</h4>
                        </div>
                        <span class="search-result__action">Go</span>
                    </a>
                `;
            });
        }

        searchResults.innerHTML = html;


    }

    // Helper functions
    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function highlightMatch(text, query) {
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return String(text).split(regex).map((part, index) => index % 2
            ? '<span style="color:#c4a0bd;font-weight:600;">' + escapeHTML(part) + '</span>'
            : escapeHTML(part)).join('');
    }

    // Live search as user types (with small debounce)
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(searchInput.value);
        }, 150);
    });

    // Immediate search on Enter
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(searchInput.value);
        }
    });

})();
