// Cart slide-over panel: handles bag preview, quantity adjustments, and adding items

(function () {
    'use strict';

    // DOM elements
    const cartOverlay = document.getElementById('cartOverlay');
    const cartPanel = document.getElementById('cartPanel');
    const cartClose = document.getElementById('cartClose');
    const cartToggles = document.querySelectorAll('#cartToggle');
    
    const cartItemsDiv = document.getElementById('cartItems');
    const cartEmptyP = document.getElementById('cartEmpty');
    const cartTotalSpan = document.getElementById('cartTotal');
    const cartCheckoutBtn = document.getElementById('cartCheckout');
    const cartBadge = document.getElementById('cartBadge');

    // Open and close slide-over cart panel
    function openCart() {
        if (!cartPanel) return;
        cartPanel.classList.add('open');
        cartOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
        fetchCart(); // Load contents
    }

    function closeCart() {
        if (!cartPanel) return;
        cartPanel.classList.remove('open');
        cartOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    cartToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            openCart();
        });
    });

    if (cartClose) cartClose.addEventListener('click', closeCart);
    if (cartOverlay) cartOverlay.addEventListener('click', closeCart);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && cartPanel && cartPanel.classList.contains('open')) {
            closeCart();
        }
    });

    // Fetch cart contents from API
    async function fetchCart() {
        try {
            const res = await fetch('api/cart.php?action=fetch');
            const data = await res.json();
            
            if (data.success) {
                renderCart(data.items, data.total);
            } else {
                if (data.message && data.message.toLowerCase().includes('unauthorized')) {
                    cartEmptyP.textContent = 'Please sign in to view your bag.';
                    cartEmptyP.style.display = 'block';
                    cartItemsDiv.innerHTML = '';
                    cartItemsDiv.appendChild(cartEmptyP);
                    cartCheckoutBtn.style.display = 'none';
                    if (cartBadge) cartBadge.textContent = '0';
                }
            }
        } catch (err) {
            console.error('Failed to fetch cart:', err);
        }
    }

    // Render cart items in the slide-over panel
    function renderCart(items, total) {
        if (items.length === 0) {
            cartEmptyP.textContent = 'Your bag is empty.';
            cartEmptyP.style.display = 'block';
            cartItemsDiv.innerHTML = '';
            cartItemsDiv.appendChild(cartEmptyP);
            cartCheckoutBtn.style.display = 'none';
            if (cartBadge) cartBadge.textContent = '0';
            cartTotalSpan.textContent = '₱0.00';
            const cartSubtotalSpan = document.getElementById('cartSubtotal');
            if (cartSubtotalSpan) cartSubtotalSpan.textContent = '₱0.00';
            return;
        }

        cartEmptyP.style.display = 'none';
        cartItemsDiv.innerHTML = '';
        let count = 0;

        items.forEach(item => {
            count += item.quantity;
            const row = document.createElement('div');
            row.className = 'cart-item';
            // Added inline styles for the layout matching the new design
            row.innerHTML = `
                <div class="cart-item__thumb" style="width: 72px; height: 96px; border-radius: 6px; background: transparent; border: none; margin-right: 15px;">
                    <img src="${item.image}" alt="${item.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                </div>
                <div class="cart-item__info" style="justify-content: flex-start; padding-top: 2px;">
                    <h4 class="cart-item__name" style="font-family: var(--font-body); font-size: 1rem; color: #F5F5F5; font-weight: 500; margin-bottom: 2px;">${item.name}</h4>
                    <div style="font-family: var(--font-body); font-size: 0.8rem; color: #8A8A8A; margin-bottom: 8px;">Maison Collection</div>
                    <p class="cart-item__unit-price" style="font-family: var(--font-body); font-size: 0.95rem; font-weight: 600; color: var(--accent); margin-bottom: 12px;">₱${parseFloat(item.price).toFixed(2)}</p>
                    <div class="cart-item__controls" style="margin-top: 0; background: transparent; border: 1px solid rgba(255,255,255,0.08); border-radius: 4px; display: inline-flex;">
                        <button class="cart-qty-btn minus" data-id="${item.item_id}" data-qty="${item.quantity - 1}" style="width: 26px; height: 26px; font-size: 1rem;">-</button>
                        <span class="cart-qty-val" style="width: 28px; height: 26px; font-size: 0.85rem; border-left: 1px solid rgba(255,255,255,0.08); border-right: 1px solid rgba(255,255,255,0.08);">${item.quantity}</span>
                        <button class="cart-qty-btn plus" data-id="${item.item_id}" data-qty="${item.quantity + 1}" style="width: 26px; height: 26px; font-size: 1rem;">+</button>
                    </div>
                </div>
                <button class="cart-item__delete" data-id="${item.item_id}" style="position: absolute; top: 20px; right: 0; background: transparent; border: none; color: #8A8A8A; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            `;
            cartItemsDiv.appendChild(row);
        });

        cartCheckoutBtn.style.display = 'flex'; // Changed to flex for icon alignment
        if (cartBadge) cartBadge.textContent = count;
        cartTotalSpan.textContent = '₱' + parseFloat(total).toFixed(2);
        
        const cartSubtotalSpan = document.getElementById('cartSubtotal');
        if (cartSubtotalSpan) cartSubtotalSpan.textContent = '₱' + parseFloat(total).toFixed(2);

        // Re-bind listeners for qty & remove inside the modal
        cartItemsDiv.querySelectorAll('.cart-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                updateCartItem(btn.dataset.id, btn.dataset.qty);
            });
        });
        
        cartItemsDiv.querySelectorAll('.cart-item__delete').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await showCartConfirmModal('Remove this item?');
                if(confirmed) {
                    updateCartItem(btn.dataset.id, 0);
                }
            });
        });
    }

    // Confirmation modal for item removal
    function showCartConfirmModal(message) {
        if (window.MaisonUngod && typeof window.MaisonUngod.showConfirm === 'function') {
            return window.MaisonUngod.showConfirm(message || 'Are you sure you want to remove this item?', 'Remove Item', {
                icon: 'danger',
                isDanger: true,
                confirmText: 'Remove',
                cancelText: 'Cancel'
            });
        }
        return Promise.resolve(confirm(message || 'Remove item?'));
    }

    // Update item quantity or remove via API
    async function updateCartItem(id, qty) {
        const formData = new FormData();
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
        formData.append('item_id', id);
        if (qty > 0) {
            formData.append('action', 'update');
            formData.append('quantity', qty);
        } else {
            formData.append('action', 'remove');
        }
        
        try {
            await fetch('api/cart.php', { method: 'POST', body: formData });
            fetchCart(); // Refresh
        } catch (err) {
            window.MaisonUngod.showAlert('Connection error. Please try again.', 'Connection Error');
        }
    }

    // Add to bag button click handler
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '...';
            btn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
                formData.append('action', 'add');
                formData.append('product_id', id);

                const response = await fetch('api/cart.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    btn.innerHTML = '✓';
                    fetchCart(); // Update badge in background
                    openCart(); // Show the user what they added
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 1000);
                } else {
                    if (data.message && data.message.toLowerCase().includes('unauthorized')) {
                        window.MaisonUngod.showModal({
                            title: 'Sign In Required',
                            message: 'Unauthorized. Please log in to add items to your shopping bag.',
                            icon: 'auth',
                            confirmText: 'Sign In',
                            cancelText: 'Continue Browsing',
                            onConfirm: () => {
                                if (window.MaisonUngod && typeof window.MaisonUngod.openAccount === 'function') {
                                    window.MaisonUngod.openAccount();
                                } else if (document.getElementById('accountModal')) {
                                    document.getElementById('accountModal').classList.add('open');
                                    document.getElementById('accountOverlay').classList.add('open');
                                    document.body.style.overflow = 'hidden';
                                } else {
                                    window.location.href = 'index.php?login=1';
                                }
                            }
                        });
                    } else {
                        window.MaisonUngod.showAlert(data.message || 'An error occurred.');
                    }
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        });
    });

    // Check cart initially to set the badge icon count
    fetchCart();

})();
