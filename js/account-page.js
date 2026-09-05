/**
 * account-page.js — Account Dashboard Logic
 * Handles tab switching, profile edit modal, and profile update AJAX.
 * Loaded only by account.php.
 */

// ── Tab Switcher ─────────────────────────────────────────────────────────────
/**
 * Switch between the Profile and Orders tabs.
 * Called from onclick="" attributes on the sidebar nav items.
 *
 * @param {string} tabId  'profile' or 'orders'
 */
function switchTab(tabId) {
    // Update Nav
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    document.getElementById('nav-' + tabId).classList.add('active');

    // Update Content
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
}

// ── Check URL hash on load to auto-switch tabs ────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash === '#orders') {
        switchTab('orders');
    } else if (hash === '#addresses') {
        switchTab('addresses');
    }
});

// ── Logout ───────────────────────────────────────────────────────────────────
const logoutBtn = document.getElementById('logoutBtnNav') || document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        await fetch('api/auth.php?action=logout');
        window.location.href = 'index.php?login=1';
    });
}

// ── Profile Update Form Submit ────────────────────────────────────────────────
const inlineProfileForm = document.getElementById('inlineProfileForm');
const editMsg           = document.getElementById('editMsg');
const inlineModalSubmit = document.getElementById('inlineModalSubmit');

if (inlineProfileForm) {
    inlineProfileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        inlineModalSubmit.disabled = true;
        inlineModalSubmit.textContent = 'Saving...';

        window.MaisonUngod.clearFieldErrors(inlineProfileForm);

        const formData = new FormData(inlineProfileForm);
        formData.append('action', 'update_profile');
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());

        try {
            const response = await fetch('api/auth.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                editMsg.className = 'edit-msg success';
                editMsg.textContent = 'Profile updated successfully!';
                editMsg.style.display = 'block';

                // Reload to show updated data
                setTimeout(() => window.location.reload(), 1000);
            } else {
                editMsg.className = 'edit-msg error';
                editMsg.textContent = data.message || 'Error updating profile.';
                editMsg.style.display = 'block';
                if (data.errors) {
                    window.MaisonUngod.displayFieldErrors(inlineProfileForm, data.errors);
                }
            }
        } catch (err) {
            editMsg.className = 'edit-msg error';
            editMsg.textContent = 'Connection error.';
            editMsg.style.display = 'block';
        }

        inlineModalSubmit.disabled = false;
        inlineModalSubmit.textContent = 'Save';
    });
}

// ── Addresses Logic ────────────────────────────────────────────────────────
const addressModalOverlay = document.getElementById('addressModalOverlay');
const addressForm = document.getElementById('addressForm');
const addrMsg = document.getElementById('addrMsg');

function openAddressModal(addr = null) {
    document.getElementById('addrMsg').style.display = 'none';
    
    if (addr) {
        document.getElementById('addressModalTitle').textContent = 'Edit Address';
        document.getElementById('addr_action').value = 'edit';
        document.getElementById('addr_id').value = addr.id;
        document.getElementById('addr_full_name').value = addr.full_name;
        document.getElementById('addr_phone').value = addr.phone;
        document.getElementById('addr_line').value = addr.address_line;
        document.getElementById('addr_is_default').checked = addr.is_default == 1;
        document.getElementById('addr_is_default').disabled = addr.is_default == 1; // prevent unchecking if already default
    } else {
        document.getElementById('addressModalTitle').textContent = 'New Address';
        document.getElementById('addr_action').value = 'add';
        addressForm.reset();
        document.getElementById('addr_id').value = '';
        document.getElementById('addr_is_default').disabled = false;
    }
    
    addressModalOverlay.classList.add('open');
}

function closeAddressModal() {
    addressModalOverlay.classList.remove('open');
}

if (addressForm) {
    addressForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('addrSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        window.MaisonUngod.clearFieldErrors(addressForm);
        const formData = new FormData(addressForm);
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
        
        try {
            const response = await fetch('api/address.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                addrMsg.className = 'edit-msg success';
                addrMsg.textContent = data.message;
                addrMsg.style.display = 'block';
                setTimeout(() => {
                    window.location.href = window.location.pathname + '#addresses';
                    window.location.reload();
                }, 1000);
            } else {
                addrMsg.className = 'edit-msg error';
                addrMsg.textContent = data.message || 'Error saving address.';
                addrMsg.style.display = 'block';
                if (data.errors) {
                    window.MaisonUngod.displayFieldErrors(addressForm, data.errors);
                }
                btn.disabled = false;
                btn.textContent = 'Save';
            }
        } catch (err) {
            addrMsg.className = 'edit-msg error';
            addrMsg.textContent = 'Connection error.';
            addrMsg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Save';
        }
    });
}

async function deleteAddress(id) {
    if (!confirm('Are you sure you want to delete this address?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
    
    try {
        const response = await fetch('api/address.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            window.location.href = window.location.pathname + '#addresses';
            window.location.reload();
        } else {
            alert(data.message || 'Error deleting address');
        }
    } catch (err) {
        alert('Connection error');
    }
}

async function setDefaultAddress(id) {
    const formData = new FormData();
    formData.append('action', 'set_default');
    formData.append('id', id);
    formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
    
    try {
        const response = await fetch('api/address.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            window.location.href = window.location.pathname + '#addresses';
            window.location.reload();
        } else {
            alert(data.message || 'Error setting default');
        }
    } catch (err) {
        alert('Connection error');
    }
}

// ── Change Field Logic (Email / Phone) ──────────────────────────────────────
const changeFieldModalOverlay = document.getElementById('changeFieldModalOverlay');
const changeFieldForm = document.getElementById('changeFieldForm');
const changeFieldMsg = document.getElementById('changeFieldMsg');
const changeFieldValue = document.getElementById('changeFieldValue');
const changeFieldLabel = document.getElementById('changeFieldLabel');
const changeFieldTitle = document.getElementById('changeFieldModalTitle');

function openChangeFieldModal(field, currentValue) {
    changeFieldMsg.style.display = 'none';
    changeFieldValue.value = currentValue;
    changeFieldValue.name = field;
    
    if (field === 'email') {
        changeFieldTitle.textContent = currentValue ? 'Change Email' : 'Add Email';
        changeFieldLabel.textContent = 'New Email Address';
        changeFieldValue.type = 'email';
    } else if (field === 'phone') {
        changeFieldTitle.textContent = currentValue ? 'Change Phone' : 'Add Phone';
        changeFieldLabel.textContent = 'New Phone Number';
        changeFieldValue.type = 'text';
    }
    
    changeFieldModalOverlay.classList.add('open');
    changeFieldValue.focus();
}

function closeChangeFieldModal() {
    changeFieldModalOverlay.classList.remove('open');
}

if (changeFieldForm) {
    changeFieldForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('changeFieldSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        window.MaisonUngod.clearFieldErrors(changeFieldForm);
        const formData = new FormData(changeFieldForm);
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
        
        try {
            const response = await fetch('api/auth.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                changeFieldMsg.className = 'edit-msg success';
                changeFieldMsg.textContent = data.message;
                changeFieldMsg.style.display = 'block';
                setTimeout(() => window.location.reload(), 1000);
            } else {
                changeFieldMsg.className = 'edit-msg error';
                changeFieldMsg.textContent = data.message || 'Error updating field.';
                changeFieldMsg.style.display = 'block';
                if (data.errors) {
                    window.MaisonUngod.displayFieldErrors(changeFieldForm, data.errors);
                }
                btn.disabled = false;
                btn.textContent = 'Save';
            }
        } catch (err) {
            changeFieldMsg.className = 'edit-msg error';
            changeFieldMsg.textContent = 'Connection error.';
            changeFieldMsg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Save';
        }
    });
}

// ── Change Password Logic ───────────────────────────────────────────────────
const passwordModalOverlay = document.getElementById('passwordModalOverlay');
const passwordForm = document.getElementById('passwordForm');
const passwordMsg = document.getElementById('passwordMsg');

function openPasswordModal() {
    passwordMsg.style.display = 'none';
    passwordForm.reset();
    passwordModalOverlay.classList.add('open');
    document.getElementById('current_password').focus();
}

function closePasswordModal() {
    passwordModalOverlay.classList.remove('open');
}

if (passwordForm) {
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const newPass = document.getElementById('new_password').value;
        const confirmPass = document.getElementById('confirm_password').value;
        
        window.MaisonUngod.clearFieldErrors(passwordForm);
        
        if (newPass !== confirmPass) {
            passwordMsg.className = 'edit-msg error';
            passwordMsg.textContent = 'New passwords do not match.';
            passwordMsg.style.display = 'block';
            window.MaisonUngod.displayFieldErrors(passwordForm, { confirm_password: 'New passwords do not match.' });
            return;
        }

        const btn = document.getElementById('passwordSubmitBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        const formData = new FormData(passwordForm);
        formData.append('action', 'change_password');
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());
        
        try {
            const response = await fetch('api/auth.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                passwordMsg.className = 'edit-msg success';
                passwordMsg.textContent = data.message;
                passwordMsg.style.display = 'block';
                setTimeout(() => closePasswordModal(), 1500);
            } else {
                passwordMsg.className = 'edit-msg error';
                passwordMsg.textContent = data.message || 'Error updating password.';
                passwordMsg.style.display = 'block';
                if (data.errors) {
                    window.MaisonUngod.displayFieldErrors(passwordForm, data.errors);
                }
                btn.disabled = false;
                btn.textContent = 'Save Password';
            }
        } catch (err) {
            passwordMsg.className = 'edit-msg error';
            passwordMsg.textContent = 'Connection error.';
            passwordMsg.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Save Password';
        }
    });
}

// ── Cancel Order Logic ──────────────────────────────────────────────────────
const cancelOrderModalOverlay = document.getElementById('cancelOrderModalOverlay');
const cancelOrderMsg = document.getElementById('cancelOrderMsg');
const confirmCancelOrderBtn = document.getElementById('confirmCancelOrderBtn');
let currentCancelOrderId = null;

function cancelOrder(orderId) {
    currentCancelOrderId = orderId;
    cancelOrderMsg.style.display = 'none';
    cancelOrderModalOverlay.classList.add('open');
}

function closeCancelOrderModal() {
    cancelOrderModalOverlay.classList.remove('open');
    currentCancelOrderId = null;
}

if (confirmCancelOrderBtn) {
    confirmCancelOrderBtn.addEventListener('click', async () => {
        if (!currentCancelOrderId) return;
        
        confirmCancelOrderBtn.disabled = true;
        confirmCancelOrderBtn.textContent = 'Cancelling...';
        
        const formData = new FormData();
        formData.append('action', 'cancel_order');
        formData.append('order_id', currentCancelOrderId);
        formData.append('csrf_token', window.MaisonUngod.getCSRFToken());

        try {
            const response = await fetch('api/orders.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                cancelOrderMsg.className = 'edit-msg success';
                cancelOrderMsg.textContent = data.message;
                cancelOrderMsg.style.display = 'block';
                setTimeout(() => window.location.reload(), 1500);
            } else {
                cancelOrderMsg.className = 'edit-msg error';
                cancelOrderMsg.textContent = data.message || 'Error cancelling order.';
                cancelOrderMsg.style.display = 'block';
                confirmCancelOrderBtn.disabled = false;
                confirmCancelOrderBtn.textContent = 'Yes, Cancel';
            }
        } catch (err) {
            cancelOrderMsg.className = 'edit-msg error';
            cancelOrderMsg.textContent = 'Connection error.';
            cancelOrderMsg.style.display = 'block';
            confirmCancelOrderBtn.disabled = false;
            confirmCancelOrderBtn.textContent = 'Yes, Cancel';
        }
    });
}
