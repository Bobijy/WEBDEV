/**
 * shop-sort.js — Shop Page Sort Dropdown
 * Handles the sort toggle and product card reordering on shop.php
 */
(function () {
    const sortWrapper  = document.getElementById('sortWrapper');
    const sortToggle   = document.getElementById('sortToggle');
    const sortDropdown = document.getElementById('sortDropdown');
    const productGrid  = document.querySelector('.product-grid');

    if (!sortWrapper || !productGrid) return;

    // ── Toggle dropdown ──
    sortToggle.addEventListener('click', () => {
        sortWrapper.classList.toggle('open');
    });

    // ── Close when clicking outside ──
    document.addEventListener('click', (e) => {
        if (!sortWrapper.contains(e.target)) {
            sortWrapper.classList.remove('open');
        }
    });

    // ── Parse price from card ──
    function getPrice(card) {
        const priceEl = card.querySelector('.cat-price');
        return parseFloat(priceEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    }

    // ── Get name from card ──
    function getName(card) {
        const nameEl = card.querySelector('.cat-title');
        return nameEl.textContent.trim();
    }

    // ── Save original order ──
    const originalOrder = [...productGrid.querySelectorAll('.cat-card')];

    // ── Sort handler ──
    sortDropdown.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
            const sortType = btn.dataset.sort;

            // Update active state
            sortDropdown.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Close dropdown
            sortWrapper.classList.remove('open');

            // Get current cards
            let cards = [...productGrid.querySelectorAll('.cat-card')];

            // Sort
            switch (sortType) {
                case 'price-asc':
                    cards.sort((a, b) => getPrice(a) - getPrice(b));
                    break;
                case 'price-desc':
                    cards.sort((a, b) => getPrice(b) - getPrice(a));
                    break;
                case 'name-asc':
                    cards.sort((a, b) => getName(a).localeCompare(getName(b)));
                    break;
                case 'name-desc':
                    cards.sort((a, b) => getName(b).localeCompare(getName(a)));
                    break;
                default:
                    cards = [...originalOrder];
            }

            // Rearrange DOM
            cards.forEach(card => productGrid.appendChild(card));
        });
    });
})();
