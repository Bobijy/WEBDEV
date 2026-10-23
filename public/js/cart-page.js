// Cart page script: handles quantity changes and item removal on cart.php

// Update item quantity or remove item via API
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
        window.MaisonUngod.showAlert('Connection error. Please try again.', 'Connection Error');
    }
}

// Quantity adjust buttons (+ / -)
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        updateCart(btn.dataset.id, btn.dataset.qty, 'update');
    });
});

// Remove item buttons
document.querySelectorAll('.cart-item-remove').forEach(btn => {
    btn.addEventListener('click', async () => {
        const confirmed = await window.MaisonUngod.showConfirm('Are you sure you want to remove this item from your cart?', 'Remove Item', {
            icon: 'danger',
            isDanger: true,
            confirmText: 'Remove',
            cancelText: 'Cancel'
        });
        if (confirmed) {
            updateCart(btn.dataset.id, 0, 'remove');
        }
    });
});

