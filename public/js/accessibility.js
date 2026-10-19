// Focus ownership follows the existing modal open/close behavior.
(function () {
    'use strict';
    const dialogs = document.querySelectorAll('[role="dialog"][aria-modal="true"]');
    let current = null;
    let previousFocus = null;
    const focusable = dialog => [...dialog.querySelectorAll('a[href],button,input,select,textarea,[tabindex="0"]')]
        .filter(element => !element.disabled && !element.closest('[inert]') && element.getClientRects().length > 0);
    function update() {
        dialogs.forEach(dialog => {
            const visible = dialog.classList.contains('open') || dialog.classList.contains('active') || dialog.style.display === 'flex';
            dialog.inert = !visible;
            dialog.setAttribute('aria-hidden', String(!visible));
            if (visible && current !== dialog) {
                previousFocus = document.activeElement;
                current = dialog;
                (focusable(dialog)[0] || dialog).focus();
            } else if (!visible && current === dialog) {
                current = null;
                previousFocus?.focus();
            }
        });
    }
    const observer = new MutationObserver(update);
    dialogs.forEach(dialog => observer.observe(dialog, { attributes: true, attributeFilter: ['class', 'style'] }));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || !current) return;
        const elements = focusable(current);
        const first = elements[0];
        const last = elements[elements.length - 1];
        if (!first) { event.preventDefault(); return; }
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    update();
})();
