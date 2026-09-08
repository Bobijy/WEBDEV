// Shop sort script: handles sorting products by price or name on shop.php

(function () {
    const sortWrapper  = document.getElementById('sortWrapper');
    const sortToggle   = document.getElementById('sortToggle');
    const sortDropdown = document.getElementById('sortDropdown');
    const productGrid  = document.querySelector('.product-grid');

    if (!sortWrapper || !productGrid) return;

    // Toggle dropdown open and closed
    sortToggle.addEventListener('click', () => {
        sortWrapper.classList.toggle('open');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!sortWrapper.contains(e.target)) {
            sortWrapper.classList.remove('open');
        }
    });

    // Extract price number from product card element
    function getPrice(card) {
        const priceEl = card.querySelector('.cat-price');
        return parseFloat(priceEl.textContent.replace(/[^0-9.]/g, '')) || 0;
    }

    // Extract product name from product card element
    function getName(card) {
        const nameEl = card.querySelector('.cat-title');
        return nameEl.textContent.trim();
    }

    // Preserve initial catalog order
    const originalOrder = [...productGrid.querySelectorAll('.cat-card')];

    // Handle sort selection
    sortDropdown.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
            const sortType = btn.dataset.sort;

            // Highlight selected sort option
            sortDropdown.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Close dropdown
            sortWrapper.classList.remove('open');

            // Sort product cards
            let cards = [...productGrid.querySelectorAll('.cat-card')];

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

            // Re-append cards in sorted order
            cards.forEach(card => productGrid.appendChild(card));
        });
    });
})();

