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
    if (window.location.hash === '#orders') {
        switchTab('orders');
    }
});

// ── Logout ───────────────────────────────────────────────────────────────────
document.getElementById('logoutBtn').addEventListener('click', async () => {
    await fetch('api/auth.php?action=logout');
    window.location.href = 'index.php?login=1';
});

// ── Edit Profile Modal ────────────────────────────────────────────────────────
const editModalOverlay  = document.getElementById('editModalOverlay');
const editProfileBtn    = document.getElementById('editProfileBtn');
const editAddressBtn    = document.getElementById('editAddressBtn');
const editModalClose    = document.getElementById('editModalClose');
const editModalCancel   = document.getElementById('editModalCancel');
const editProfileForm   = document.getElementById('editProfileForm');
const editMsg           = document.getElementById('editMsg');
const editModalSubmit   = document.getElementById('editModalSubmit');

function openModal() {
    editModalOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    editModalOverlay.classList.remove('open');
    document.body.style.overflow = '';
    editMsg.style.display = 'none';
}

if (editProfileBtn)  editProfileBtn.addEventListener('click', openModal);
if (editAddressBtn)  editAddressBtn.addEventListener('click', openModal);
if (editModalClose)  editModalClose.addEventListener('click', closeModal);
if (editModalCancel) editModalCancel.addEventListener('click', closeModal);

editModalOverlay.addEventListener('click', (e) => {
    if (e.target === editModalOverlay) closeModal();
});

// ── Profile Update Form Submit ────────────────────────────────────────────────
if (editProfileForm) {
    editProfileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        editModalSubmit.disabled = true;
        editModalSubmit.textContent = 'SAVING...';

        const formData = new FormData(editProfileForm);
        formData.append('action', 'update_profile');

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
            }
        } catch (err) {
            editMsg.className = 'edit-msg error';
            editMsg.textContent = 'Connection error.';
            editMsg.style.display = 'block';
        }

        editModalSubmit.disabled = false;
        editModalSubmit.textContent = 'Save Changes';
    });
}
