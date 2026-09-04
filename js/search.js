// ═══════════════════════════════════════
// Maison Ungod — Search System
// Searches through products and pages
// ═══════════════════════════════════════

(function () {
    'use strict';

    // ── DOM References ──
    const searchOverlay = document.getElementById('searchOverlay');
    const searchToggles = document.querySelectorAll('#searchToggle, .nav-icon[aria-label="Search"]');
    const searchClose = document.getElementById('searchClose');
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if (!searchOverlay || !searchInput) return;

    // ... (rest of the vars)

    // ── Product catalog (all available products) ──
    const products = [
        { name: 'Fragrance 1', price: '$145.00', image: 'assets/images/collection-1.png', link: 'shop.php' },
        { name: 'Fragrance 2', price: '$145.00', image: 'assets/images/collection-2.png', link: 'shop.php' },
        { name: 'Fragrance 3', price: '$145.00', image: 'assets/images/collection-3.png', link: 'shop.php' },
        { name: 'Fragrance 4', price: '$165.00', image: 'assets/images/collection-4.png', link: 'shop.php' },
    ];

    // ── Pages catalog ──
    const pages = [
        { name: 'Home', link: 'index.php' },
        { name: 'Shop — All Products', link: 'shop.php' },
        { name: 'Best Seller — Collections', link: 'index.php#collections' },
        { name: 'Our Story', link: 'index.php#story' },
        { name: 'Contact', link: 'index.php#contact' },
    ];

    // ═══════════════════════
    // Open / Close Search
    // ═══════════════════════
    function openSearch() {
        searchOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        // Auto-focus input after animation
        setTimeout(() => searchInput.focus(), 100);
    }

    function closeSearch() {
        searchOverlay.classList.remove('open');
        document.body.style.overflow = '';
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

    // ═══════════════════════
    // Keyboard shortcut: Ctrl+K or Cmd+K
    // ═══════════════════════
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

    // ═══════════════════════
    // Search Logic
    // ═══════════════════════
    function performSearch(query) {
        const q = query.toLowerCase().trim();

        if (!q) {
            searchResults.innerHTML = '<p class="search-hint">Type to search fragrances...</p>';
            return;
        }

        // Filter products
        const matchedProducts = products.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.price.toLowerCase().includes(q)
        );

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
                    <a href="${p.link}" class="search-result" data-product="${escapeHTML(p.name)}">
                        <div class="search-result__thumb">
                            <img src="${p.image}" alt="${escapeHTML(p.name)}">
                        </div>
                        <div class="search-result__info">
                            <h4 class="search-result__name">${highlightMatch(p.name, q)}</h4>
                            <span class="search-result__price">${p.price}</span>
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

        // Add to cart on product click (optional — adds then navigates)
        searchResults.querySelectorAll('.search-result[data-product]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const productName = el.dataset.product;
                const product = products.find(p => p.name === productName);
                if (product) {
                    // Add to cart via localStorage directly
                    let cart = JSON.parse(localStorage.getItem('maisonUngodCart')) || [];
                    const existing = cart.find(item => item.name === product.name);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        cart.push({ name: product.name, price: product.price, image: product.image, qty: 1 });
                    }
                    localStorage.setItem('maisonUngodCart', JSON.stringify(cart));
                }
                // Navigate to shop page
                window.location.href = product ? product.link : 'shop.php';
            });
        });
    }

    // ═══════════════════════
    // Helpers
    // ═══════════════════════
    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function highlightMatch(text, query) {
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span style="color:#71416B;font-weight:600;">$1</span>');
    }

    // ── Live search on input ──
    let debounceTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            performSearch(searchInput.value);
        }, 150);
    });

    // ── Search on Enter ──
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch(searchInput.value);
        }
    });

})();
