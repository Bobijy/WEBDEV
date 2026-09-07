// Maison Ungod — Premium Animation Engine
// Advanced scroll reveals, parallax, cursor effects,
// 3D tilt, magnetic links, slider upgrades

(function () {
    'use strict';

    // NAVBAR — Scroll Effect

    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 50);
        });
    }


    // HAMBURGER TOGGLE

    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close mobile nav on link click + transfer active highlight
        const allNavLinks = navLinks.querySelectorAll('a');
        allNavLinks.forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
                allNavLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });
    }

    const allNavLinks = navLinks ? navLinks.querySelectorAll('a') : [];

    // ── Scroll Spy — only on pages with hash-based nav links ──
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

    // ── Collection Card Wave Entrance ──
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

        // ── Auto-play with pause on hover ──
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
