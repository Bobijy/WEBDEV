// Checkout page script: handles address selection, payment method toggle, card validation, and order submission

// Switch active payment option and show corresponding details
function togglePayment(radio, panelId) {
    // Reset all option labels
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
    // Hide all panels
    document.querySelectorAll('.payment-panel').forEach(el => el.classList.remove('active'));

    // Activate selected option and panel
    radio.closest('.payment-option').classList.add('active');
    const panel = document.getElementById(panelId);
    if (panel) panel.classList.add('active');

    // If switching away from Card, clear card validation errors
    if (radio.value !== 'Card') {
        const ccPanel = document.getElementById('panel-cc');
        if (ccPanel) {
            ccPanel.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
            ccPanel.querySelectorAll('.field-error').forEach(el => el.remove());
        }
    }
}

// Switch between saved addresses and new address input form
function toggleAddressMode(isNew, radioEl) {
    // Highlight selected address option
    if (radioEl) {
        document.querySelectorAll('.saved-addresses .payment-option').forEach(el => el.classList.remove('active'));
        radioEl.closest('.payment-option').classList.add('active');
    }

    const newForm = document.getElementById('newAddressForm');
    if (!newForm) return;
    const inputs = newForm.querySelectorAll('input[type="text"]');
    
    if (isNew) {
        newForm.style.display = 'block';
        inputs.forEach(input => input.required = true);
    } else {
        newForm.style.display = 'none';
        inputs.forEach(input => input.required = false);
    }
}

// Card input auto-formatting and validation setup
document.addEventListener('DOMContentLoaded', () => {
    const ccNumber = document.getElementById('cc_number');
    const ccExp    = document.getElementById('cc_exp');
    const ccSec    = document.getElementById('cc_sec');
    const ccName   = document.getElementById('cc_name');

    // Auto-format Card Number: 4 digits blocks (xxxx xxxx xxxx xxxx)
    if (ccNumber) {
        ccNumber.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 16);
            let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
            e.target.value = formatted;
            clearSingleError(e.target);
        });
    }

    // Auto-format Expiry Date: MM / YY
    if (ccExp) {
        ccExp.addEventListener('input', (e) => {
            let val = e.target.value.replace(/\D/g, '').substring(0, 4);
            if (val.length >= 3) {
                e.target.value = val.substring(0, 2) + ' / ' + val.substring(2, 4);
            } else {
                e.target.value = val;
            }
            clearSingleError(e.target);
        });

        ccExp.addEventListener('keydown', (e) => {
            // If pressing backspace at "MM / ", delete the slash too
            if (e.key === 'Backspace' && e.target.value.length === 5) {
                e.target.value = e.target.value.substring(0, 2);
            }
        });
    }

    // Auto-format CVV: numeric only up to 4 digits
    if (ccSec) {
        ccSec.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
            clearSingleError(e.target);
        });
    }

    if (ccName) {
        ccName.addEventListener('input', (e) => {
            clearSingleError(e.target);
        });
    }

    function clearSingleError(input) {
        input.classList.remove('has-error');
        const errSpan = input.parentNode.querySelector('.field-error');
        if (errSpan) errSpan.remove();
    }
});

// Handle checkout form submission
document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const form = e.target;
    const selectedAddressId = form.selected_address_id ? form.selected_address_id.value : 'new';
    const msg = document.getElementById('checkoutMsg');
    
    if (window.MaisonUngod && typeof window.MaisonUngod.clearFieldErrors === 'function') {
        window.MaisonUngod.clearFieldErrors(form);
    }
    msg.style.display = 'none';

    if (selectedAddressId === '') {
        msg.className = 'alert error';
        msg.textContent = 'Please select a shipping address.';
        msg.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // Check payment method & validate card fields if Card is selected
    const paymentMethodEl = form.querySelector('input[name="payment_method"]:checked');
    const paymentMethod = paymentMethodEl ? paymentMethodEl.value : 'Card';

    if (paymentMethod === 'Card') {
        const ccNumberInput = document.getElementById('cc_number');
        const ccExpInput    = document.getElementById('cc_exp');
        const ccSecInput    = document.getElementById('cc_sec');
        const ccNameInput   = document.getElementById('cc_name');

        const rawNumber = ccNumberInput ? ccNumberInput.value.replace(/\D/g, '') : '';
        const rawExp    = ccExpInput ? ccExpInput.value.trim() : '';
        const rawSec    = ccSecInput ? ccSecInput.value.trim() : '';
        const rawName   = ccNameInput ? ccNameInput.value.trim() : '';

        const cardErrors = {};

        if (!rawNumber) {
            cardErrors.card_number = 'Card number is required.';
        } else if (rawNumber.length < 15 || rawNumber.length > 16) {
            cardErrors.card_number = 'Please enter a valid 15 or 16-digit card number.';
        }

        if (!rawExp) {
            cardErrors.card_exp = 'Expiration date is required.';
        } else {
            const expMatch = rawExp.match(/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/);
            if (!expMatch) {
                cardErrors.card_exp = 'Format must be MM / YY.';
            } else {
                const expMonth = parseInt(expMatch[1], 10);
                const expYear  = 2000 + parseInt(expMatch[2], 10);
                const now = new Date();
                const currentYear = now.getFullYear();
                const currentMonth = now.getMonth() + 1;

                if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                    cardErrors.card_exp = 'Card has expired.';
                }
            }
        }

        if (!rawSec) {
            cardErrors.card_sec = 'Security code is required.';
        } else if (!/^\d{3,4}$/.test(rawSec)) {
            cardErrors.card_sec = 'Security code must be 3 or 4 digits.';
        }

        if (!rawName) {
            cardErrors.card_name = 'Name on card is required.';
        } else if (rawName.length < 2) {
            cardErrors.card_name = 'Please enter the full name on the card.';
        }

        if (Object.keys(cardErrors).length > 0) {
            msg.className = 'alert error';
            msg.textContent = 'Please fill out all required card payment details.';
            msg.style.display = 'block';

            if (window.MaisonUngod && typeof window.MaisonUngod.displayFieldErrors === 'function') {
                window.MaisonUngod.displayFieldErrors(form, cardErrors);
            }

            // Smooth scroll to the first invalid card field
            const firstErrorEl = form.querySelector('#panel-cc .has-error');
            if (firstErrorEl) {
                firstErrorEl.focus();
                firstErrorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
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
        document.getElementById('finalAddress').value = 'saved_address'; 
    }

    const btn = document.getElementById('checkoutSubmit');
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
            msg.textContent = data.message || 'An error occurred while processing checkout.';
            msg.style.display = 'block';
            
            if (data.errors && window.MaisonUngod && typeof window.MaisonUngod.displayFieldErrors === 'function') {
                window.MaisonUngod.displayFieldErrors(form, data.errors);
            }
            
            btn.disabled = false;
            btn.textContent = 'Pay now';
        }
    } catch (err) {
        console.error('Checkout error:', err);
        msg.className = 'alert error';
        msg.textContent = 'Connection error. Please try again.';
        msg.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Pay now';
    }
});
