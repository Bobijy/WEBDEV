/**
 * cart-page.js — Cart Page Interaction
 * Handles quantity update and item removal on cart.php.
 * Calls api/cart.php and reloads the page to reflect changes.
 */

/**
 * Send a cart update or remove request to the API.
 *
 * @param {string|number} id     The cart item ID
 * @param {string|number} qty    The new quantity (ignored for 'remove')
 * @param {string}        action 'update' or 'remove'
 */
async function updateCart(id, qty, action) {
    const formData = new FormData();
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
    formData.append('action', action);
    formData.append('item_id', id);
    if (action === 'update') formData.append('quantity', qty);

    try {
        await fetch('api/cart.php', { method: 'POST', body: formData });
        window.location.reload(); // Refresh to show updated totals
    } catch (err) {
        alert('Connection error. Please try again.');
    }
}

// ── Quantity Buttons ──────────────────────────────────────────────────────────
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        updateCart(btn.dataset.id, btn.dataset.qty, 'update');
    });
});

// ── Remove Buttons ────────────────────────────────────────────────────────────
document.querySelectorAll('.cart-item-remove').forEach(btn => {
    btn.addEventListener('click', () => {
        if (confirm('Remove item from cart?')) {
            updateCart(btn.dataset.id, 0, 'remove');
        }
    });
});
