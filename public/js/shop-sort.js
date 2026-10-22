// Sorting applies to the complete catalog through the existing shop route.
(function () {
    'use strict';
    const wrapper = document.getElementById('sortWrapper');
    const toggle = document.getElementById('sortToggle');
    const dropdown = document.getElementById('sortDropdown');
    if (!wrapper || !toggle || !dropdown) return;

    function close() {
        wrapper.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
    }
    toggle.addEventListener('click', () => {
        const open = wrapper.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', event => {
        if (!wrapper.contains(event.target)) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && wrapper.classList.contains('open')) {
            close();
            toggle.focus();
        }
    });
    dropdown.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', button.dataset.sort);
            url.searchParams.delete('page');
            window.location.assign(url.href);
        });
    });
})();

