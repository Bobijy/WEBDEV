/**
 * checkout.js — Checkout Page Logic
 * Handles payment method toggle and checkout form AJAX submission.
 */

/**
 * Toggle the active payment option and show the corresponding panel.
 * Called via onclick attribute on each payment radio input.
 *
 * @param {HTMLInputElement} radio   The radio input that was selected
 * @param {string}           panelId The ID of the panel to reveal
 */
function togglePayment(radio, panelId) {
    // Reset all option labels
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
    // Hide all panels
    document.querySelectorAll('.payment-panel').forEach(el => el.classList.remove('active'));

    // Activate selected option + panel
    radio.closest('.payment-option').classList.add('active');
    document.getElementById(panelId).classList.add('active');
}

/**
 * Toggle between saved addresses and the new address form.
 */
function toggleAddressMode(isNew, radioEl) {
    // Highlight the selected option container
    if (radioEl) {
        document.querySelectorAll('.saved-addresses .payment-option').forEach(el => el.classList.remove('active'));
        radioEl.closest('.payment-option').classList.add('active');
    }

    const newForm = document.getElementById('newAddressForm');
    const inputs = newForm.querySelectorAll('input[type="text"]');
    
    if (isNew) {
        newForm.style.display = 'block';
        inputs.forEach(input => input.required = true);
    } else {
        newForm.style.display = 'none';
        inputs.forEach(input => input.required = false);
    }
}

// ── Checkout Form Submit ──────────────────────────────────────────────────────
document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const selectedAddressId = form.selected_address_id ? form.selected_address_id.value : 'new';

    // If "new" is selected, aggregate the individual address fields
    if (selectedAddressId === 'new') {
        const addressParts = [
            form.addressLine1.value,
            form.barangay.value,
            form.city.value,
            form.region.value,
            form.postalCode.value,
            form.country.value,
        ];
        document.getElementById('finalAddress').value = addressParts
            .filter(p => p.trim() !== '')
            .join(', ');
    } else {
        // We will just let the backend fetch the address string from the DB using selected_address_id
        document.getElementById('finalAddress').value = 'saved_address'; 
    }

    const btn = document.getElementById('checkoutSubmit');
    const msg = document.getElementById('checkoutMsg');

    btn.disabled = true;
    btn.textContent = 'Processing...';

    const formData = new FormData(form);

    try {
        const response = await fetch('api/checkout.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            msg.className = 'alert success';
            msg.textContent = 'Order placed successfully! Redirecting...';
            msg.style.display = 'block';

            setTimeout(() => {
                window.location.href = 'account.php#orders';
            }, 1500);
        } else {
            msg.className = 'alert error';
            msg.textContent = data.message;
            msg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Pay now';
        }
    } catch (err) {
        msg.className = 'alert error';
        msg.textContent = 'Connection error.';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Pay now';
    }
});
