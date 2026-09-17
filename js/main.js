/*============================================================
   PORTFOLIO V3 - MAIN JAVASCRIPT
   Interactive features and animations
============================================================*/

document.addEventListener('DOMContentLoaded', () => {
    initThemeSystem();
    initNavigation();
    initProgressBar();
    initPortfolioFilter();
    initSkillsTabs();
    initContactForm();
    initScrollAnimations();
    initSmoothScroll();
    initParallaxBanner();
});

// ================== NAVIGATION ==================
function initNavigation() {
    const navbar = document.getElementById('navbar');
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');

    // Scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile menu toggle
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        // Close menu on link click
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    // Active link on scroll
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-links a');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').slice(1) === current) {
                link.classList.add('active');
            }
        });
    });
}

// ================== PROGRESS BAR ==================
function initProgressBar() {
    const progressBar = document.getElementById('progressBar');

    window.addEventListener('scroll', () => {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        progressBar.style.width = scrollPercent + '%';
    });
}

// ================== PORTFOLIO FILTER ==================
function initPortfolioFilter() {
    // 1. Work V2 Filters (index.html)
    const workV2FilterBtns = document.querySelectorAll('.work-v2__filter-btn');
    const workV2Cards = document.querySelectorAll('.work-v2__card');

    if (workV2FilterBtns.length > 0 && workV2Cards.length > 0) {
        workV2FilterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;

                // Update active button
                workV2FilterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Filter cards with smooth fade/scale
                workV2Cards.forEach(card => {
                    const category = card.dataset.category;
                    const matches = filter === 'all' || category === filter;

                    if (matches) {
                        card.classList.remove('work-v2__card--hidden');
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.96)';
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                card.style.transition = 'opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                                card.style.opacity = '1';
                                card.style.transform = 'scale(1)';
                                setTimeout(() => {
                                    card.style.opacity = '';
                                    card.style.transform = '';
                                    card.style.transition = '';
                                }, 350);
                            });
                        });
                    } else {
                        card.style.transition = 'opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1), transform 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.96)';
                        setTimeout(() => {
                            card.classList.add('work-v2__card--hidden');
                            card.style.opacity = '';
                            card.style.transform = '';
                            card.style.transition = '';
                        }, 250);
                    }
                });
            });
        });
    }

    // 2. Legacy Portfolio Filters (e.g. projects.html)
    const filterBtns = document.querySelectorAll('.filter-btn');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    if (filterBtns.length > 0 && portfolioItems.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;

                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Filter items
                portfolioItems.forEach(item => {
                    const category = item.dataset.category;

                    if (filter === 'all' || category === filter) {
                        item.style.display = 'block';
                        setTimeout(() => {
                            item.style.opacity = '1';
                            item.style.transform = 'scale(1)';
                        }, 50);
                    } else {
                        item.style.opacity = '0';
                        item.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });
    }
}

// ================== SKILLS TABS ==================
function initSkillsTabs() {
    const skillTabs = document.querySelectorAll('.skill-tab');
    const skillCategories = document.querySelectorAll('.skill-category');

    skillTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;

            // Update active tab
            skillTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // Show matching category
            skillCategories.forEach(category => {
                category.classList.remove('active');
                if (category.dataset.category === tabName) {
                    category.classList.add('active');
                    // Animate progress bars
                    animateProgressBars(category);
                }
            });
        });
    });

    // Animate first category on load
    const firstCategory = document.querySelector('.skill-category.active');
    if (firstCategory) {
        animateProgressBars(firstCategory);
    }
}

function animateProgressBars(category) {
    const bars = category.querySelectorAll('.skill-progress');
    bars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 100);
    });
}

// ================== CONTACT FORM ==================
function initContactForm() {
    const form = document.getElementById('contactForm');
    const successMsg = document.getElementById('formSuccess');
    const errorBanner = document.getElementById('formError');
    const csrfInput = document.getElementById('csrfToken');
    const submitBtn = document.getElementById('contactSubmitBtn') || (form ? form.querySelector('button[type="submit"]') : null);

    if (!form) return;

    // Fetch CSRF token asynchronously
    fetch('contact.php?action=csrf')
        .then(res => res.json())
        .then(data => {
            if (data && data.csrf_token && csrfInput) {
                csrfInput.value = data.csrf_token;
            }
        })
        .catch(() => {});

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (errorBanner) {
            errorBanner.classList.remove('show');
            errorBanner.textContent = '';
        }

        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const message = document.getElementById('message');

        let isValid = true;

        // Validate name
        if (!name || !name.value.trim()) {
            if (name) showError(name);
            isValid = false;
        } else {
            clearError(name);
        }

        // Validate email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email || !emailRegex.test(email.value.trim())) {
            if (email) showError(email);
            isValid = false;
        } else {
            clearError(email);
        }

        // Validate message
        if (!message || !message.value.trim()) {
            if (message) showError(message);
            isValid = false;
        } else {
            clearError(message);
        }

        if (isValid && submitBtn) {
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = `<span class="loading-spinner"></span> Sending...`;
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            const formData = new FormData(form);

            try {
                const response = await fetch('contact.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    form.style.display = 'none';
                    if (errorBanner) errorBanner.style.display = 'none';
                    successMsg.classList.add('show');
                    const successText = document.getElementById('formSuccessMessage');
                    if (successText && data.message) {
                        successText.textContent = data.message;
                    }
                } else {
                    if (errorBanner) {
                        errorBanner.textContent = data.message || 'Something went wrong. Please check your details and try again.';
                        errorBanner.classList.add('show');
                    }
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                }
            } catch (error) {
                // Network failure fallback: open email client
                const subjectVal = (document.getElementById('subject') && document.getElementById('subject').value.trim()) || 'Project Inquiry';
                const subject = encodeURIComponent(`Portfolio Contact: ${name.value.trim()} - ${subjectVal}`);
                const body = encodeURIComponent(
                    `Name: ${name.value.trim()}\nEmail: ${email.value.trim()}\n\nMessage:\n${message.value.trim()}`
                );
                const mailtoUrl = `mailto:contact@knitin525.in?subject=${subject}&body=${body}`;

                window.location.href = mailtoUrl;

                form.style.display = 'none';
                if (errorBanner) errorBanner.style.display = 'none';
                successMsg.classList.add('show');
                const successText = document.getElementById('formSuccessMessage');
                if (successText) {
                    successText.textContent = 'Your default email client has opened to send your inquiry directly to Nitin Kumar.';
                }
            }
        }
    });

    // Clear errors on input
    form.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('input', () => clearError(input));
    });
}

function showError(input) {
    input.style.borderColor = '#ef4444';
}

function clearError(input) {
    input.style.borderColor = '';
}

// ================== SCROLL ANIMATIONS ==================
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observe elements
    const animateElements = document.querySelectorAll(
        '.service-card, .portfolio-item, .testimonial-card, .skill-item, .contact-info'
    );

    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });

    // Trigger initial check
    setTimeout(() => {
        animateElements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight - 100) {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }
        });
    }, 100);

    // About V2 section reveal
    const aboutSection = document.querySelector('.about-v2');
    if (aboutSection) {
        const aboutObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    aboutObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -80px 0px' });
        aboutObserver.observe(aboutSection);
    }

    // Work V2 section reveal
    const workSection = document.querySelector('.work-v2');
    if (workSection) {
        const workObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    workObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
        workObserver.observe(workSection);
    }
}

// ================== SMOOTH SCROLL ==================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;

            e.preventDefault();
            const target = document.querySelector(href);

            if (target) {
                const navHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// ================== LAZY LOADING FOR IMAGES ==================
document.addEventListener('DOMContentLoaded', () => {
    const images = document.querySelectorAll('img');

    images.forEach(img => {
        img.addEventListener('load', () => {
            img.style.opacity = '1';
        });

        if (img.complete) {
            img.style.opacity = '1';
        }
    });
});

// ================== THEME SYSTEM ==================
function initThemeSystem() {
    const THEME_STORAGE_KEY = 'portfolio-theme';
    const themeToggles = document.querySelectorAll('.theme-toggle-btn');
    const metaThemeColor = document.getElementById('metaThemeColor');

    function getPreferredTheme() {
        const saved = localStorage.getItem(THEME_STORAGE_KEY);
        if (saved === 'dark' || saved === 'light') {
            return saved;
        }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function applyTheme(theme, save = false) {
        document.documentElement.setAttribute('data-theme', theme);
        if (save) {
            try {
                localStorage.setItem(THEME_STORAGE_KEY, theme);
            } catch (e) {}
        }

        // Update mobile theme-color meta tag
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#0B0D10' : '#fafbfc');
        }

        // Update all toggle buttons' aria-label and tooltips
        themeToggles.forEach(btn => {
            const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        });
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next, true);
    }

    // Attach click listeners to all theme toggle buttons
    themeToggles.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleTheme();
        });
    });

    // Listen to system preference changes if user hasn't explicitly set a preference
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(THEME_STORAGE_KEY)) {
                applyTheme(e.matches ? 'dark' : 'light', false);
            }
        });
    }

    // Ensure state synchronization on load
    const currentTheme = document.documentElement.getAttribute('data-theme') || getPreferredTheme();
    applyTheme(currentTheme, false);
}

// ================== PARALLAX SHOWCASE BANNER ==================
function initParallaxBanner() {
    const banner = document.getElementById('showcaseBanner');
    const parallaxMedia = document.getElementById('parallaxMedia');
    if (!banner || !parallaxMedia) return;

    // Respect reduced motion preferences
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    let isVisible = false;
    let ticking = false;

    // Use IntersectionObserver so calculations only run when section is in/near viewport
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            isVisible = entry.isIntersecting;
            if (isVisible) {
                requestUpdate();
            }
        });
    }, {
        rootMargin: '120px 0px 120px 0px'
    });

    observer.observe(banner);

    function updateParallax() {
        ticking = false;
        if (!isVisible) return;

        const rect = banner.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Progress from 0 (entering bottom of screen) to 1 (leaving top of screen)
        const totalDistance = windowHeight + rect.height;
        const currentDistance = windowHeight - rect.top;
        const progress = Math.max(0, Math.min(1, currentDistance / totalDistance));

        // Dynamic travel based on banner height, safely bounded within the 20% bleed
        const maxOffset = Math.min(rect.height * 0.16, 130);
        const offsetY = (progress - 0.5) * maxOffset * 2;

        parallaxMedia.style.transform = `translate3d(0, ${offsetY.toFixed(1)}px, 0)`;
    }

    function requestUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate, { passive: true });

    // Initial positioning
    requestUpdate();
}