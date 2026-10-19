(function () {
'use strict';
window.MaisonUngod = window.MaisonUngod || {};
let cancelActiveMessage = null;
// GLOBAL MODAL SYSTEM (Message, Alert, Confirm)
window.MaisonUngod.showModal = function (options) {
    options = options || {};
    if (cancelActiveMessage) cancelActiveMessage();
    return new Promise((resolve) => {
        const previousFocus = document.activeElement;
        const previousOverflow = document.body.style.overflow;
        const title = options.title || 'Notice';
        const message = options.message || '';
        const iconType = options.icon || 'info';
        const confirmText = options.confirmText || 'OK';
        const cancelText = options.cancelText || null;
        const isDanger = !!options.isDanger;

        let overlay = document.getElementById('globalMessageOverlay');
        let modal = document.getElementById('globalMessageModal');

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'globalMessageOverlay';
            overlay.className = 'message-overlay';
            document.body.appendChild(overlay);
        }

        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'globalMessageModal';
            modal.className = 'message-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'globalMessageTitle');
            modal.setAttribute('aria-describedby', 'globalMessageText');
            document.body.appendChild(modal);
        }

        // SVG Icons matching Maison Ungod luxury aesthetic
        let iconSvg = '';
        let iconClass = 'message-modal__icon';
        if (iconType === 'auth') {
            iconClass += ' message-modal__icon--auth';
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
        } else if (iconType === 'danger') {
            iconClass += ' message-modal__icon--danger';
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
        } else if (iconType === 'warning') {
            iconClass += ' message-modal__icon--warning';
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
        } else if (iconType === 'success') {
            iconClass += ' message-modal__icon--success';
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
        } else {
            iconClass += ' message-modal__icon--info';
            iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
        }

        const confirmBtnClass = isDanger
            ? 'message-modal__btn message-modal__btn--danger'
            : 'message-modal__btn message-modal__btn--primary';

        modal.innerHTML = `
            <button class="message-modal__close" id="globalMessageClose" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="message-modal__inner">
                <div class="${iconClass}">
                    ${iconSvg}
                </div>
                <h3 class="message-modal__title" id="globalMessageTitle"></h3>
                <p class="message-modal__text" id="globalMessageText"></p>
                <div class="message-modal__actions">
                    ${cancelText ? `<button type="button" class="message-modal__btn message-modal__btn--secondary" id="globalMessageCancel"></button>` : ''}
                    <button type="button" class="${confirmBtnClass}" id="globalMessageConfirm"></button>
                </div>
            </div>
        `;

        const btnConfirm = modal.querySelector('#globalMessageConfirm');
        const btnCancel = modal.querySelector('#globalMessageCancel');
        const btnClose = modal.querySelector('#globalMessageClose');

        modal.querySelector('#globalMessageTitle').textContent = title;
        modal.querySelector('#globalMessageText').textContent = message;
        btnConfirm.textContent = confirmText;
        if (btnCancel) btnCancel.textContent = cancelText;
        let isClosed = false;
        function cleanupAndClose() {
            if (isClosed) return;
            isClosed = true;
            modal.classList.remove('open');
            overlay.classList.remove('open');
            document.removeEventListener('keydown', handleKeydown);
            overlay.removeEventListener('click', handleOverlayClick);
            document.body.style.overflow = previousOverflow;
            modal.inert = true;
            modal.setAttribute('aria-hidden', 'true');
            cancelActiveMessage = null;
            previousFocus?.focus();
        }

        function handleConfirm() {
            if (isClosed) return;
            cleanupAndClose();
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
            resolve(true);
        }

        function handleCancel() {
            if (isClosed) return;
            cleanupAndClose();
            if (typeof options.onCancel === 'function') {
                options.onCancel();
            }
            resolve(false);
        }

        function handleOverlayClick(e) {
            if (e.target === overlay) {
                handleCancel();
            }
        }

        function handleKeydown(e) {
            if (e.key === 'Escape') { e.preventDefault(); handleCancel(); }
            if (e.key === 'Tab') {
                const buttons = [...modal.querySelectorAll('button')];
                const first = buttons[0], last = buttons[buttons.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        }

        btnConfirm.addEventListener('click', handleConfirm);
        if (btnCancel) btnCancel.addEventListener('click', handleCancel);
        if (btnClose) btnClose.addEventListener('click', handleCancel);
        overlay.addEventListener('click', handleOverlayClick);
        document.addEventListener('keydown', handleKeydown);

        // Open modal
        document.body.style.overflow = 'hidden';
        overlay.classList.add('open');
        modal.classList.add('open');
        modal.inert = false;
        modal.setAttribute('aria-hidden', 'false');
        cancelActiveMessage = handleCancel;
        (btnCancel || btnConfirm).focus();
    });
};

window.MaisonUngod.showAlert = function (message, title) {
    const msgStr = String(message || '');
    const isAuth = msgStr.toLowerCase().includes('unauthorized') || msgStr.toLowerCase().includes('log in');

    return window.MaisonUngod.showModal({
        title: title || (isAuth ? 'Sign In Required' : 'Notice'),
        message: msgStr,
        icon: isAuth ? 'auth' : 'info',
        confirmText: isAuth ? 'Sign In' : 'OK',
        cancelText: isAuth ? 'Continue Browsing' : null,
        onConfirm: () => {
            if (isAuth) {
                window.location.href = '/login';
            }
        }
    });
};

window.MaisonUngod.showConfirm = function (message, title, options) {
    options = options || {};
    return window.MaisonUngod.showModal({
        title: title || 'Confirmation',
        message: String(message || ''),
        icon: options.icon || (options.isDanger ? 'danger' : 'warning'),
        confirmText: options.confirmText || 'Confirm',
        cancelText: options.cancelText || 'Cancel',
        isDanger: !!options.isDanger
    });
};


// Keep the original form submission (including validation, submitter and CSRF).
const pendingConfirmations = new WeakSet();
const approvedConfirmations = new WeakSet();
document.addEventListener('submit', async event => {
    const form = event.target;
    if (!form.matches('form[data-confirm]') || approvedConfirmations.has(form)) return;
    event.preventDefault();
    if (pendingConfirmations.has(form)) return;
    pendingConfirmations.add(form);
    try {
        const accepted = await window.MaisonUngod.showConfirm(form.dataset.confirm, form.dataset.confirmTitle, {
            isDanger: true, confirmText: form.dataset.confirmButton || 'Confirm', cancelText: 'Cancel'
        });
        if (accepted && form.isConnected) {
            approvedConfirmations.add(form);
            try { form.requestSubmit(event.submitter || undefined); }
            finally { approvedConfirmations.delete(form); }
        }
    } finally { pendingConfirmations.delete(form); }
});
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-confirm-form]');
    if (!trigger) return;
    event.preventDefault();
    document.getElementById(trigger.dataset.confirmForm)?.requestSubmit();
});

})();
