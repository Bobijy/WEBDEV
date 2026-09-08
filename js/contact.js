// Contact modal script: handles open/close and contact form submission

(function () {
    'use strict';

    // DOM elements
    const contactOverlay = document.getElementById('contactOverlay');
    const contactModal = document.getElementById('contactModal');
    const contactClose = document.getElementById('contactClose');
    const contactToggles = document.querySelectorAll('#contactToggle');
    
    const contactForm = document.getElementById('contactForm');
    const contactMsg = document.getElementById('contactMsg');
    const contactSubmit = document.getElementById('contactSubmit');

    // Open and close contact modal
    function openContact() {
        if (!contactModal) return;
        contactModal.classList.add('open');
        contactOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeContact() {
        if (!contactModal) return;
        contactModal.classList.remove('open');
        contactOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    contactToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            openContact();
        });
    });

    if (contactClose) contactClose.addEventListener('click', closeContact);
    if (contactOverlay) contactOverlay.addEventListener('click', closeContact);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && contactModal && contactModal.classList.contains('open')) {
            closeContact();
        }
    });

    // Handle contact form submission
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            contactSubmit.disabled = true;
            contactSubmit.textContent = 'SENDING...';
            
            // Simulate network request
            setTimeout(() => {
                contactForm.reset();
                contactMsg.className = 'account-msg success';
                contactMsg.textContent = 'Message sent successfully! We will get back to you soon.';
                contactMsg.style.display = 'block';
                
                contactSubmit.disabled = false;
                contactSubmit.textContent = 'SEND MESSAGE';
                
                // Hide success message after 5 seconds
                setTimeout(() => {
                    contactMsg.style.display = 'none';
                }, 5000);
            }, 800);
        });
    }
})();

