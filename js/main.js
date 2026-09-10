// Main script: animations, scroll reveals, navbar effects, and slider interactions

(function () {
    'use strict';

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }

    // Hamburger menu toggle
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    if (hamburger && navLinks) {
        const toggleMenu = () => {
            const isActive = navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
            if (navbar) {
                navbar.classList.toggle('menu-active', isActive);
            }
            document.body.style.overflow = isActive ? 'hidden' : '';
        };

        hamburger.addEventListener('click', toggleMenu);

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navLinks.classList.contains('active')) {
                toggleMenu();
            }
        });

        // Close mobile nav on link click and update active link
        const pageNavLinks = navLinks.querySelectorAll('a:not(.mobile-nav-action)');
        pageNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navLinks.classList.contains('active')) {
                    toggleMenu();
                }
                pageNavLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        // Close mobile nav when clicking mobile action buttons
        const mobileActionLinks = navLinks.querySelectorAll('.mobile-nav-action');
        mobileActionLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navLinks.classList.contains('active')) {
                    toggleMenu();
                }
            });
        });

        // Ensure mobile menu closes if viewport expands to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
                if (navbar) navbar.classList.remove('menu-active');
                document.body.style.overflow = '';
            }
        });
    }

    const allNavLinks = navLinks ? navLinks.querySelectorAll('a') : [];

    // Scroll spy for pages with hash-based nav links
    const sections = document.querySelectorAll('section[id]');
    const hasHashLinks = [...allNavLinks].some(link => link.getAttribute('href').startsWith('#'));

    if (hasHashLinks && sections.length > 0) {
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const top = section.offsetTop - 150;
                if (window.scrollY >= top) {
                    current = '#' + section.id;
                }
            });
            allNavLinks.forEach(link => {
                link.classList.toggle('active', link.getAttribute('href') === current);
            });
        });
    }


    // SCROLL PROGRESS BAR

    const scrollProgress = document.querySelector('.scroll-progress');
    if (scrollProgress) {
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            scrollProgress.style.width = progress + '%';
        }, { passive: true });
    }

    // SCROLL-TO-TOP BUTTON

    const scrollTopBtn = document.querySelector('.scroll-to-top');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('visible', window.scrollY > 500);
        }, { passive: true });

        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ADVANCED SCROLL REVEAL

    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .reveal-blur, .section-divider, .story-bar');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));

    // Staggered entrance for collection cards
    const collCards = document.querySelectorAll('.coll-card');
    const cardWaveObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Stagger cards with progressive delay
                const cards = entry.target.parentElement.querySelectorAll('.coll-card');
                cards.forEach((card, i) => {
                    setTimeout(() => {
                        card.classList.add('visible');
                    }, i * 120);
                });
                // Unobserve all cards in this track
                cards.forEach(card => cardWaveObserver.unobserve(card));
            }
        });
    }, { threshold: 0.1 });

    collCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px) scale(0.95)';
        card.style.transition = 'opacity 0.7s cubic-bezier(0.16, 1, 0.3, 1), transform 0.7s cubic-bezier(0.16, 1, 0.3, 1)';
        cardWaveObserver.observe(card);
    });

    // Override for visible state
    const cardStyle = document.createElement('style');
    cardStyle.textContent = '.coll-card.visible { opacity: 1 !important; transform: translateY(0) scale(1) !important; }';
    document.head.appendChild(cardStyle);

    // PARALLAX ENGINE

    const parallaxElements = document.querySelectorAll('[data-parallax]');

    function updateParallax() {
        const scrollY = window.scrollY;

        parallaxElements.forEach(el => {
            const speed = parseFloat(el.dataset.parallax) || 0.3;
            const rect = el.getBoundingClientRect();
            const offsetTop = rect.top + scrollY;
            const relativeScroll = scrollY - offsetTop + window.innerHeight;

            if (rect.top < window.innerHeight && rect.bottom > 0) {
                const translateY = (relativeScroll * speed * -0.15).toFixed(2);
                el.style.transform = `translateY(${translateY}px)`;
            }
        });
    }

    if (parallaxElements.length > 0) {
        let parallaxTicking = false;
        window.addEventListener('scroll', () => {
            if (!parallaxTicking) {
                requestAnimationFrame(() => {
                    updateParallax();
                    parallaxTicking = false;
                });
                parallaxTicking = true;
            }
        }, { passive: true });
    }


    // MOUSE-FOLLOW GLOW (Hero)

    const heroSection = document.getElementById('hero');
    const mouseGlow = document.querySelector('.mouse-glow');

    if (heroSection && mouseGlow) {
        heroSection.addEventListener('mouseenter', () => {
            mouseGlow.classList.add('active');
        });

        heroSection.addEventListener('mouseleave', () => {
            mouseGlow.classList.remove('active');
        });

        heroSection.addEventListener('mousemove', (e) => {
            const rect = heroSection.getBoundingClientRect();
            const x = e.clientX - rect.left - 200;
            const y = e.clientY - rect.top - 200;
            mouseGlow.style.transform = `translate(${x}px, ${y}px)`;
        });
    }


    // CUSTOM CURSOR (Removed)


    // MAGNETIC NAV LINKS

    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (!isTouchDevice) {
        allNavLinks.forEach(link => {
            link.addEventListener('mousemove', (e) => {
                const rect = link.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;
                link.style.transform = `translate(${x * 0.2}px, ${y * 0.3}px)`;
            });

            link.addEventListener('mouseleave', () => {
                link.style.transform = 'translate(0, 0)';
            });
        });
    }

    // 3D TILT CARDS

    if (!isTouchDevice) {
        const tiltCards = document.querySelectorAll('.coll-card, .cat-card');

        tiltCards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = (e.clientX - rect.left) / rect.width;
                const y = (e.clientY - rect.top) / rect.height;
                const tiltX = (y - 0.5) * 8; // max 4 degrees
                const tiltY = (x - 0.5) * -8;

                card.style.transform = `perspective(800px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateY(-4px)`;
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(800px) rotateX(0) rotateY(0) translateY(0)';
            });
        });
    }


    // BUTTON RIPPLE EFFECT

    const rippleButtons = document.querySelectorAll('.btn-shop, .account-submit, .cart-checkout-btn');

    rippleButtons.forEach(btn => {
        btn.classList.add('ripple-btn');

        btn.addEventListener('click', function (e) {
            const rect = btn.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.className = 'ripple-effect';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';

            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // ELASTIC BAG BOUNCE

    document.addEventListener('click', (e) => {
        const bagBtn = e.target.closest('.coll-card__bag, .shop-card__bag');
        if (bagBtn) {
            bagBtn.classList.remove('bag-bounce');
            // Force reflow to restart animation
            void bagBtn.offsetWidth;
            bagBtn.classList.add('bag-bounce');
            setTimeout(() => bagBtn.classList.remove('bag-bounce'), 600);
        }
    });

    // SHIMMER EFFECT ON IMAGES

    const shimmerTargets = document.querySelectorAll('.coll-card__img-wrap, .cat-card, .shop-card__img-wrap, .feat-img');
    shimmerTargets.forEach(el => {
        el.classList.add('shimmer-wrap');
    });

    // COLLECTIONS SLIDER — Enhanced

    const collTrack = document.getElementById('collTrack');
    if (collTrack) {
        const collPrev = document.getElementById('collPrev');
        const collNext = document.getElementById('collNext');
        const sliderProgressBar = document.querySelector('.slider-progress__bar');
        const progressContainer = document.querySelector('.slider-progress');

        let autoPlayInterval = null;
        let isHovered = false;

        function checkScrollable() {
            // Check if there is actual content to scroll
            const isScrollable = Math.ceil(collTrack.scrollWidth) > Math.ceil(collTrack.clientWidth);

            if (!isScrollable) {
                if (collPrev) collPrev.style.display = 'none';
                if (collNext) collNext.style.display = 'none';
                if (progressContainer) progressContainer.style.display = 'none';
                if (autoPlayInterval) {
                    clearInterval(autoPlayInterval);
                    autoPlayInterval = null;
                }
                return false;
            } else {
                if (collPrev) collPrev.style.display = '';
                if (collNext) collNext.style.display = '';
                if (progressContainer) progressContainer.style.display = '';
                return true;
            }
        }

        function getScrollAmount() {
            const card = collTrack.querySelector('.coll-card');
            if (!card) return 320;
            const style = getComputedStyle(collTrack);
            const gap = parseFloat(style.gap) || 28;
            return card.offsetWidth + gap;
        }

        if (collPrev) {
            collPrev.addEventListener('click', () => {
                if (checkScrollable()) collTrack.scrollBy({ left: -getScrollAmount(), behavior: 'smooth' });
            });
        }

        if (collNext) {
            collNext.addEventListener('click', () => {
                if (checkScrollable()) collTrack.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
            });
        }

        // Auto-play slider with pause on hover
        function startAutoPlay() {
            if (!checkScrollable()) return;
            if (autoPlayInterval) clearInterval(autoPlayInterval);

            autoPlayInterval = setInterval(() => {
                if (!isHovered && checkScrollable()) {
                    const maxScroll = collTrack.scrollWidth - collTrack.clientWidth;

                    if (collTrack.scrollLeft >= maxScroll - 10) {
                        // Reset to beginning
                        collTrack.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        collTrack.scrollBy({ left: getScrollAmount(), behavior: 'smooth' });
                    }

                    // Reset progress bar animation
                    if (sliderProgressBar) {
                        sliderProgressBar.classList.remove('running');
                        sliderProgressBar.classList.add('reset');
                        void sliderProgressBar.offsetWidth;
                        sliderProgressBar.classList.remove('reset');
                        sliderProgressBar.classList.add('running');
                    }
                }
            }, 5000);

            // Start initial progress bar
            if (sliderProgressBar) {
                setTimeout(() => sliderProgressBar.classList.add('running'), 100);
            }
        }

        // Pause on hover
        const sliderWrap = collTrack.closest('.coll-slider-wrap');
        if (sliderWrap) {
            sliderWrap.addEventListener('mouseenter', () => {
                isHovered = true;
                if (sliderProgressBar && checkScrollable()) {
                    sliderProgressBar.classList.remove('running');
                    sliderProgressBar.classList.add('reset');
                }
            });

            sliderWrap.addEventListener('mouseleave', () => {
                isHovered = false;
                if (sliderProgressBar && checkScrollable()) {
                    void sliderProgressBar.offsetWidth;
                    sliderProgressBar.classList.remove('reset');
                    sliderProgressBar.classList.add('running');
                }
            });
        }

        // Pause on manual scroll/click
        [collPrev, collNext].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    if (sliderProgressBar && checkScrollable()) {
                        sliderProgressBar.classList.remove('running');
                        sliderProgressBar.classList.add('reset');
                        void sliderProgressBar.offsetWidth;
                        sliderProgressBar.classList.remove('reset');
                        sliderProgressBar.classList.add('running');
                    }
                });
            }
        });

        // Initialize state
        checkScrollable();

        // Use a short delay before autoplay to ensure styles are calculated
        setTimeout(() => {
            startAutoPlay();
        }, 500);

        // Re-evaluate on window resize
        window.addEventListener('resize', () => {
            checkScrollable();
            if (!checkScrollable() && autoPlayInterval) {
                clearInterval(autoPlayInterval);
                autoPlayInterval = null;
            }
        });
    }

    // CART BADGE PULSE ON UPDATE

    const cartBadge = document.getElementById('cartBadge');
    if (cartBadge) {
        const badgeObserver = new MutationObserver(() => {
            cartBadge.classList.remove('pulse');
            void cartBadge.offsetWidth;
            cartBadge.classList.add('pulse');
            setTimeout(() => cartBadge.classList.remove('pulse'), 400);
        });
        badgeObserver.observe(cartBadge, { childList: true, characterData: true, subtree: true });
    }



})();

// GLOBAL HELPERS: CSRF & Field Errors

window.MaisonUngod = window.MaisonUngod || {};

window.MaisonUngod.getCSRFToken = function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
};

window.MaisonUngod.displayFieldErrors = function (form, errors) {
    // Clear old errors
    form.querySelectorAll('.field-error').forEach(el => el.remove());
    form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));

    if (!errors) return;

    for (const [field, msg] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('has-error');
            const errSpan = document.createElement('span');
            errSpan.className = 'field-error';
            errSpan.style.color = '#e74c3c';
            errSpan.style.fontSize = '0.8rem';
            errSpan.style.display = 'block';
            errSpan.style.marginTop = '4px';
            errSpan.textContent = msg;
            input.parentNode.appendChild(errSpan);
        }
    }
};

window.MaisonUngod.clearFieldErrors = function (form) {
    form.querySelectorAll('.field-error').forEach(el => el.remove());
    form.querySelectorAll('.has-error').forEach(el => el.classList.remove('has-error'));
};

// Make footer links non-clickable while preserving hover effects
document.addEventListener('click', (e) => {
    const footerLink = e.target.closest('.site-footer a');
    if (footerLink) {
        e.preventDefault();
        e.stopPropagation();
    }
});

// GLOBAL MODAL SYSTEM (Message, Alert, Confirm)
window.MaisonUngod.showModal = function (options) {
    options = options || {};
    return new Promise((resolve) => {
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
                <h3 class="message-modal__title">${title}</h3>
                <p class="message-modal__text">${message}</p>
                <div class="message-modal__actions">
                    ${cancelText ? `<button type="button" class="message-modal__btn message-modal__btn--secondary" id="globalMessageCancel">${cancelText}</button>` : ''}
                    <button type="button" class="${confirmBtnClass}" id="globalMessageConfirm">${confirmText}</button>
                </div>
            </div>
        `;

        const btnConfirm = modal.querySelector('#globalMessageConfirm');
        const btnCancel = modal.querySelector('#globalMessageCancel');
        const btnClose = modal.querySelector('#globalMessageClose');

        let isClosed = false;
        function cleanupAndClose() {
            if (isClosed) return;
            isClosed = true;
            modal.classList.remove('open');
            overlay.classList.remove('open');
            document.removeEventListener('keydown', handleKeydown);
            overlay.removeEventListener('click', handleOverlayClick);
            document.body.style.overflow = '';
        }

        function handleConfirm() {
            cleanupAndClose();
            if (typeof options.onConfirm === 'function') {
                options.onConfirm();
            }
            resolve(true);
        }

        function handleCancel() {
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
            if (e.key === 'Escape') {
                handleCancel();
            } else if (e.key === 'Enter' && (!btnCancel || document.activeElement !== btnCancel)) {
                handleConfirm();
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
        setTimeout(() => btnConfirm.focus(), 50);
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
                window.location.href = 'login.php';
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

// Automatically route native browser alert to the luxury modal
window.alert = function (msg) {
    window.MaisonUngod.showAlert(msg);
};


