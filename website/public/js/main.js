/**
 * Phuket Yacht & Tours - Main JavaScript
 * Optimized for performance
 */

(function() {
    'use strict';

    // ==================== Mobile Menu ====================
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!mobileMenu.contains(e.target) && !menuToggle.contains(e.target)) {
                mobileMenu.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    }

    // ==================== Back to Top ====================
    const backToTop = document.getElementById('back-to-top');

    if (backToTop) {
        const toggleBackToTop = () => {
            if (window.scrollY > 500) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        };

        window.addEventListener('scroll', toggleBackToTop, { passive: true });

        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ==================== Telegram Popup ====================
    const telegramFab = document.querySelector('.telegram-fab');
    const telegramPopup = document.querySelector('.telegram-popup');
    const popupClose = document.querySelector('.popup-close');

    if (telegramFab && telegramPopup) {
        telegramFab.addEventListener('click', () => {
            telegramPopup.classList.toggle('open');
        });

        if (popupClose) {
            popupClose.addEventListener('click', () => {
                telegramPopup.classList.remove('open');
            });
        }

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!telegramPopup.contains(e.target) && !telegramFab.contains(e.target)) {
                telegramPopup.classList.remove('open');
            }
        });
    }

    // ==================== Lazy Loading Images ====================
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');

        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        }, {
            rootMargin: '100px'
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img.lazy').forEach(img => {
            img.src = img.dataset.src;
        });
    }

    // ==================== Smooth Scroll for Anchor Links ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // ==================== Form Validation ====================
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            // Email validation
            const emailField = form.querySelector('input[type="email"]');
            if (emailField && emailField.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('error');
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    });

    // ==================== Date Input Min Date ====================
    document.querySelectorAll('input[type="date"]').forEach(input => {
        if (!input.min) {
            input.min = new Date().toISOString().split('T')[0];
        }
    });

    // ==================== Number Formatting ====================
    window.formatPrice = function(amount, currency = 'THB') {
        const symbols = { THB: '฿', USD: '$', EUR: '€', RUB: '₽' };
        const symbol = symbols[currency] || currency;
        return symbol + amount.toLocaleString();
    };

    // ==================== Analytics Helper ====================
    window.trackEvent = function(category, action, label) {
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: category,
                event_label: label
            });
        }
    };

    // Track outbound links
    document.querySelectorAll('a[target="_blank"]').forEach(link => {
        link.addEventListener('click', () => {
            trackEvent('Outbound Link', 'click', link.href);
        });
    });

    // ==================== Newsletter Form ====================
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = this.querySelector('input[type="email"]').value;
            const button = this.querySelector('button');
            const originalText = button.textContent;

            button.textContent = '...';
            button.disabled = true;

            try {
                const response = await fetch('/api/newsletter/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });

                if (response.ok) {
                    button.textContent = '✓';
                    this.querySelector('input').value = '';
                    trackEvent('Newsletter', 'subscribe', email);
                } else {
                    button.textContent = 'Error';
                }
            } catch (error) {
                button.textContent = 'Error';
            }

            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        });
    }

    // ==================== Header Scroll Effect ====================
    const header = document.querySelector('.header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    }, { passive: true });

    // ==================== Performance: Defer Non-Critical JS ====================
    // Load additional scripts after page load
    window.addEventListener('load', () => {
        // Load Google Analytics if configured
        if (window.GA_TRACKING_ID) {
            const script = document.createElement('script');
            script.async = true;
            script.src = `https://www.googletagmanager.com/gtag/js?id=${window.GA_TRACKING_ID}`;
            document.head.appendChild(script);
        }
    });

})();
