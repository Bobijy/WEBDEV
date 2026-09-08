// Checkout page script: handles address selection, payment method toggle, and order submission

// Switch active payment option and show corresponding details
function togglePayment(radio, panelId) {
    // Reset all option labels
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
    // Hide all panels
    document.querySelectorAll('.payment-panel').forEach(el => el.classList.remove('active'));

    // Activate selected option and panel
    radio.closest('.payment-option').classList.add('active');
    document.getElementById(panelId).classList.add('active');
}

// Switch between saved addresses and new address input form
function toggleAddressMode(isNew, radioEl) {
    // Highlight selected address option
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

// Handle checkout form submission
document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const selectedAddressId = form.selected_address_id ? form.selected_address_id.value : 'new';

    const msg = document.getElementById('checkoutMsg');
    
    if (selectedAddressId === '') {
        msg.className = 'alert error';
        msg.textContent = 'Please select a shipping address.';
        msg.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

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

    if (window.MaisonUngod && typeof window.MaisonUngod.clearFieldErrors === 'function') {
        window.MaisonUngod.clearFieldErrors(form);
    }

    btn.disabled = true;
    btn.textContent = 'Processing...';

    const formData = new FormData(form);
    
    if (window.MaisonUngod && typeof window.MaisonUngod.getCSRFToken === 'function') {
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
    }

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
            
            if (data.errors && window.MaisonUngod && typeof window.MaisonUngod.displayFieldErrors === 'function') {
                window.MaisonUngod.displayFieldErrors(form, data.errors);
            }
            
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
