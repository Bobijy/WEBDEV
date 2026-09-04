(function () {
    'use strict';

    // ── DOM References ──
    const cartOverlay = document.getElementById('cartOverlay');
    const cartPanel = document.getElementById('cartPanel');
    const cartClose = document.getElementById('cartClose');
    const cartToggles = document.querySelectorAll('#cartToggle');
    
    const cartItemsDiv = document.getElementById('cartItems');
    const cartEmptyP = document.getElementById('cartEmpty');
    const cartTotalSpan = document.getElementById('cartTotal');
    const cartCheckoutBtn = document.getElementById('cartCheckout');
    const cartBadge = document.getElementById('cartBadge');

    // ── Open / Close ──
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

    // ── Fetch Cart via API ──
    async function fetchCart() {
        try {
            const res = await fetch('api/cart.php?action=fetch');
            const data = await res.json();
            
            if (data.success) {
                renderCart(data.items, data.total);
            } else {
                if (data.message === 'Unauthorized') {
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

    // ── Render Cart UI ──
    function renderCart(items, total) {
        if (items.length === 0) {
            cartEmptyP.textContent = 'Your bag is empty.';
            cartEmptyP.style.display = 'block';
            cartItemsDiv.innerHTML = '';
            cartItemsDiv.appendChild(cartEmptyP);
            cartCheckoutBtn.style.display = 'none';
            if (cartBadge) cartBadge.textContent = '0';
            cartTotalSpan.textContent = '$0.00';
            return;
        }

        cartEmptyP.style.display = 'none';
        cartItemsDiv.innerHTML = '';
        let count = 0;

        items.forEach(item => {
            count += item.quantity;
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `
                <div class="cart-item__thumb">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="cart-item__info">
                    <h4 class="cart-item__name">${item.name}</h4>
                    <p class="cart-item__unit-price">$${parseFloat(item.price).toFixed(2)}</p>
                    <div class="cart-item__controls">
                        <button class="cart-qty-btn minus" data-id="${item.item_id}" data-qty="${item.quantity - 1}">-</button>
                        <span class="cart-qty-val">${item.quantity}</span>
                        <button class="cart-qty-btn plus" data-id="${item.item_id}" data-qty="${item.quantity + 1}">+</button>
                    </div>
                </div>
                <button class="cart-item__delete" data-id="${item.item_id}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            `;
            cartItemsDiv.appendChild(row);
        });

        cartTotalSpan.textContent = '$' + parseFloat(total).toFixed(2);
        cartCheckoutBtn.style.display = 'block';
        if (cartBadge) cartBadge.textContent = count;

        // Re-bind listeners for qty & remove inside the modal
        cartItemsDiv.querySelectorAll('.cart-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                updateCartItem(btn.dataset.id, btn.dataset.qty);
            });
        });
        
        cartItemsDiv.querySelectorAll('.cart-item__delete').forEach(btn => {
            btn.addEventListener('click', () => {
                if(confirm('Remove this item?')) {
                    updateCartItem(btn.dataset.id, 0);
                }
            });
        });
    }

    // ── Update Item API ──
    async function updateCartItem(id, qty) {
        const formData = new FormData();
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
            alert('Connection error');
        }
    }

    // ── Handle Add to Bag Buttons globally ──
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
                    if (data.message === 'Unauthorized') {
                        // Open account login modal instead of redirecting
                        if (document.getElementById('accountModal')) {
                            document.getElementById('accountModal').classList.add('open');
                            document.getElementById('accountOverlay').classList.add('open');
                        } else {
                            window.location.href = 'login.php';
                        }
                    } else {
                        alert(data.message);
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
