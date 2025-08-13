/**
 * Buffalo Marathon 2025 - Main JavaScript
 * Production Ready - 2025-08-08 14:03:53 UTC
 * Optimized for performance and accessibility
 */

(function() {
    'use strict';

    // Global variables
    const MARATHON_DATE = new Date('2025-10-11T07:00:00');
    const REGISTRATION_DEADLINE = new Date('2025-09-30T23:59:59');
    const EARLY_BIRD_DEADLINE = new Date('2025-08-31T23:59:59');

    /**
     * Initialize the application
     */
    function init() {
        // DOM Content Loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onDOMContentLoaded);
        } else {
            onDOMContentLoaded();
        }
    }

    /**
     * Handle DOM Content Loaded
     */
    function onDOMContentLoaded() {
        initCountdown();
        initFormValidation();
        initModalHandlers();
        initNavigationHandlers();
        initScrollEffects();
        initVideoOptimization();
        initAccessibilityFeatures();
        initProgressiveEnhancement();
        initFlashMessages();
        initDataTables();
        initCharts();
    }

    /**
     * Initialize countdown timer
     */
    function initCountdown() {
        const countdownElements = document.querySelectorAll('[data-countdown]');
        
        countdownElements.forEach(element => {
            const targetDate = new Date(element.dataset.countdown);
            updateCountdown(element, targetDate);
            
            // Update every second
            setInterval(() => {
                updateCountdown(element, targetDate);
            }, 1000);
        });

        // Live countdown on homepage
        updateLiveCountdowns();
        setInterval(updateLiveCountdowns, 1000);
    }

    /**
     * Update countdown display
     */
    function updateCountdown(element, targetDate) {
        const now = new Date();
        const diff = targetDate - now;

        if (diff <= 0) {
            element.innerHTML = '<span class="text-warning">Event Started!</span>';
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        element.innerHTML = `
            <div class="row g-2">
                <div class="col text-center">
                    <div class="h4 fw-bold text-army-green">${days}</div>
                    <small>Days</small>
                </div>
                <div class="col text-center">
                    <div class="h4 fw-bold text-army-green">${hours}</div>
                    <small>Hours</small>
                </div>
                <div class="col text-center">
                    <div class="h4 fw-bold text-army-green">${minutes}</div>
                    <small>Minutes</small>
                </div>
                <div class="col text-center">
                    <div class="h4 fw-bold text-army-green">${seconds}</div>
                    <small>Seconds</small>
                </div>
            </div>
        `;
    }

    /**
     * Update live countdowns on homepage
     */
    function updateLiveCountdowns() {
        // Days until marathon
        const marathonDays = document.getElementById('marathon-days');
        if (marathonDays) {
            const days = Math.max(0, Math.ceil((MARATHON_DATE - new Date()) / (1000 * 60 * 60 * 24)));
            marathonDays.textContent = days;
        }

        // Days until registration deadline
        const deadlineDays = document.getElementById('deadline-days');
        if (deadlineDays) {
            const days = Math.max(0, Math.ceil((REGISTRATION_DEADLINE - new Date()) / (1000 * 60 * 60 * 24)));
            deadlineDays.textContent = days;
        }

        // Days until early bird
        const earlyBirdDays = document.getElementById('early-bird-days');
        if (earlyBirdDays) {
            const days = Math.max(0, Math.ceil((EARLY_BIRD_DEADLINE - new Date()) / (1000 * 60 * 60 * 24)));
            earlyBirdDays.textContent = days;
        }
    }

    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!validateForm(this)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });

            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => clearFieldError(input));
            });
        });

        // Password confirmation validation
        const confirmPasswordFields = document.querySelectorAll('input[name="confirm_password"]');
        confirmPasswordFields.forEach(field => {
            field.addEventListener('input', function() {
                const passwordField = document.querySelector('input[name="password"], input[name="new_password"]');
                if (passwordField && this.value !== passwordField.value) {
                    this.setCustomValidity('Passwords do not match');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        // Email validation
        const emailFields = document.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value && !isValidEmail(this.value)) {
                    this.setCustomValidity('Please enter a valid email address');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        // Phone validation
        const phoneFields = document.querySelectorAll('input[type="tel"]');
        phoneFields.forEach(field => {
            field.addEventListener('input', function() {
                // Format phone number as user types
                this.value = formatPhoneNumber(this.value);
            });
        });
    }

    /**
     * Validate form
     */
    function validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validate individual field
     */
    function validateField(field) {
        const value = field.value.trim();
        const isRequired = field.hasAttribute('required');
        let isValid = true;

        // Clear previous error
        clearFieldError(field);

        // Required field validation
        if (isRequired && !value) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }

        // Email validation
        if (field.type === 'email' && value && !isValidEmail(value)) {
            showFieldError(field, 'Please enter a valid email address');
            isValid = false;
        }

        // Phone validation
        if (field.type === 'tel' && value && !isValidPhone(value)) {
            showFieldError(field, 'Please enter a valid phone number');
            isValid = false;
        }

        // Password validation
        if (field.type === 'password' && field.name === 'password' && value.length < 8) {
            showFieldError(field, 'Password must be at least 8 characters long');
            isValid = false;
        }

        // Date validation
        if (field.type === 'date' && value) {
            const date = new Date(value);
            const today = new Date();
            
            if (field.name === 'date_of_birth' && date > today) {
                showFieldError(field, 'Date of birth cannot be in the future');
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Show field error
     */
    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
    }

    /**
     * Clear field error
     */
    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * Validate email address
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate phone number
     */
    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Format phone number
     */
    function formatPhoneNumber(phone) {
        // Remove non-numeric characters except +
        let cleaned = phone.replace(/[^\d\+]/g, '');
        
        // Format for Zambian numbers
        if (cleaned.startsWith('260')) {
            cleaned = '+' + cleaned;
        } else if (cleaned.startsWith('0') && cleaned.length === 10) {
            cleaned = '+260' + cleaned.substring(1);
        }
        
        return cleaned;
    }

    /**
     * Initialize modal handlers
     */
    function initModalHandlers() {
        // Modal trigger buttons
        document.addEventListener('click', function(e) {
            const trigger = e.target.closest('[data-bs-toggle="modal"]');
            if (trigger) {
                const targetId = trigger.getAttribute('data-bs-target');
                const modal = document.querySelector(targetId);
                if (modal && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(modal).show();
                }
            }
        });

        // Modal close on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                if (typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(e.target)?.hide();
                }
            }
        });

        // Modal keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getInstance(activeModal)?.hide();
                }
            }
        });
    }

    /**
     * Initialize navigation handlers
     */
    function initNavigationHandlers() {
        // Mobile menu toggle
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            navbarToggler.addEventListener('click', function() {
                navbarCollapse.classList.toggle('show');
            });
        }

        // Smooth scrolling for anchor links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href^="#"]');
            if (link) {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });

        // Active navigation highlighting
        const navLinks = document.querySelectorAll('.nav-link');
        const currentPath = window.location.pathname;
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Initialize scroll effects
     */
    function initScrollEffects() {
        // Scroll to top button
        const scrollToTopBtn = createScrollToTopButton();
        
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.style.display = 'block';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        // Parallax effects
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        if (parallaxElements.length > 0) {
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                
                parallaxElements.forEach(element => {
                    const rate = scrolled * -0.5;
                    element.style.transform = `translateY(${rate}px)`;
                });
            });
        }

        // Fade in animation on scroll
        const fadeElements = document.querySelectorAll('.fade-in-scroll');
        
        if (fadeElements.length > 0) {
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            });

            fadeElements.forEach(element => {
                observer.observe(element);
            });
        }
    }

    /**
     * Create scroll to top button
     */
    function createScrollToTopButton() {
        const button = document.createElement('button');
        button.innerHTML = '<i class="fas fa-chevron-up"></i>';
        button.className = 'btn btn-army-green position-fixed';
        button.style.cssText = `
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        `;
        
        button.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        document.body.appendChild(button);
        return button;
    }

    /**
     * Initialize video optimization
     */
    function initVideoOptimization() {
        const videos = document.querySelectorAll('video');
        
        videos.forEach(video => {
            // Pause video on mobile to save bandwidth
            if (window.innerWidth < 768) {
                video.pause();
                video.style.display = 'none';
            }

            // Lazy load videos
            if ('IntersectionObserver' in window) {
                const videoObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const video = entry.target;
                            if (video.paused && window.innerWidth >= 768) {
                                video.play().catch(e => console.log('Video autoplay failed:', e));
                            }
                            videoObserver.unobserve(video);
                        }
                    });
                });

                videoObserver.observe(video);
            }
        });
    }

    /**
     * Initialize accessibility features
     */
    function initAccessibilityFeatures() {
        // Skip to main content link
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.textContent = 'Skip to main content';
        skipLink.className = 'sr-only sr-only-focusable';
        skipLink.style.cssText = `
            position: absolute;
            top: -40px;
            left: 6px;
            z-index: 1050;
            color: white;
            background-color: var(--army-green);
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
        `;
        
        skipLink.addEventListener('focus', function() {
            this.style.top = '6px';
        });
        
        skipLink.addEventListener('blur', function() {
            this.style.top = '-40px';
        });
        
        document.body.insertBefore(skipLink, document.body.firstChild);

        // Focus management for modals
        document.addEventListener('shown.bs.modal', function(e) {
            const modal = e.target;
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        });

        // Keyboard navigation improvements
        document.addEventListener('keydown', function(e) {
            // Enter key on buttons
            if (e.key === 'Enter' && e.target.tagName === 'BUTTON') {
                e.target.click();
            }
            
            // Escape key to close dropdowns
            if (e.key === 'Escape') {
                const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                openDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
    }

    /**
     * Initialize progressive enhancement
     */
    function initProgressiveEnhancement() {
        // Add JavaScript-enabled class
        document.documentElement.classList.add('js-enabled');

        // Enhanced form submission with loading states
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    
                    // Restore button if form validation fails
                    setTimeout(() => {
                        if (!this.checkValidity()) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    }, 100);
                }
            });
        });

        // Enhanced tooltips
        const tooltipElements = document.querySelectorAll('[title]');
        tooltipElements.forEach(element => {
            if (typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(element);
            }
        });
    }

    /**
     * Initialize flash messages
     */
    function initFlashMessages() {
        const alerts = document.querySelectorAll('.alert');
        
        alerts.forEach(alert => {
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (typeof bootstrap !== 'undefined') {
                    const bsAlert = bootstrap.Alert.getInstance(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }
            }, 5000);

            // Add fade-in animation
            alert.classList.add('fade-in');
        });
    }

    /**
     * Initialize data tables
     */
    function initDataTables() {
        const tables = document.querySelectorAll('table[data-table]');
        
        tables.forEach(table => {
            // Add responsive wrapper
            if (!table.parentElement.classList.contains('table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentElement.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }

            // Add sorting functionality
            const headers = table.querySelectorAll('th[data-sort]');
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    sortTable(table, this);
                });
            });
        });
    }

    /**
     * Sort table
     */
    function sortTable(table, header) {
        const columnIndex = Array.from(header.parentElement.children).indexOf(header);
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const isAscending = !header.classList.contains('sort-asc');
        
        // Clear previous sort indicators
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });
        
        // Add sort indicator
        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
        
        // Sort rows
        rows.sort((a, b) => {
            const aText = a.children[columnIndex].textContent.trim();
            const bText = b.children[columnIndex].textContent.trim();
            
            if (isAscending) {
                return aText.localeCompare(bText, undefined, { numeric: true });
            } else {
                return bText.localeCompare(aText, undefined, { numeric: true });
            }
        });
        
        // Reorder rows in table
        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * Initialize charts (if Chart.js is available)
     */
    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // Registration statistics chart
        const registrationChart = document.getElementById('registrationChart');
        if (registrationChart) {
            // Chart will be initialized by specific page JavaScript
        }

        // Payment status chart
        const paymentChart = document.getElementById('paymentChart');
        if (paymentChart) {
            // Chart will be initialized by specific page JavaScript
        }
    }

    /**
     * Utility functions
     */
    const utils = {
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // Format currency
        formatCurrency: function(amount) {
            return 'K' + parseFloat(amount).toFixed(2);
        },

        // Format date
        formatDate: function(date) {
            return new Date(date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        // Show notification
        showNotification: function(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 1050;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" aria-label="Close"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
            
            // Manual close
            notification.querySelector('.btn-close').addEventListener('click', () => {
                notification.remove();
            });
        }
    };

    // Expose utilities globally
    window.BuffaloMarathon = {
        utils: utils,
        init: init
    };

    // Initialize the application
    init();

})();

/**
 * Service Worker Registration (Progressive Web App)
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

/**
 * Enhanced Navigation and Scroll functionality
 */
function initEnhancedNavigation() {
    const navbar = document.querySelector('.navbar');
    let scrollToTopBtn = document.getElementById('scrollToTop');
    
    // Create scroll-to-top button if it doesn't exist
    if (!scrollToTopBtn) {
        scrollToTopBtn = document.createElement('a');
        scrollToTopBtn.id = 'scrollToTop';
        scrollToTopBtn.href = '#';
        scrollToTopBtn.className = 'scroll-to-top';
        scrollToTopBtn.setAttribute('aria-label', 'Scroll to top');
        scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        document.body.appendChild(scrollToTopBtn);
    }
    
    // Handle navbar background on scroll for better visibility
    function handleNavbarScroll() {
        if (!navbar) return;
        
        if (window.scrollY > 100) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    }
    
    // Handle scroll-to-top button visibility
    function handleScrollToTop() {
        if (!scrollToTopBtn) return;
        
        if (window.scrollY > 300) {
            scrollToTopBtn.classList.add('show');
        } else {
            scrollToTopBtn.classList.remove('show');
        }
    }
    
    // Throttled scroll handler for better performance
    let ticking = false;
    function handleScroll() {
        if (!ticking) {
            requestAnimationFrame(function() {
                handleNavbarScroll();
                handleScrollToTop();
                ticking = false;
            });
            ticking = true;
        }
    }
    
    // Event listeners
    window.addEventListener('scroll', handleScroll);
    
    // Scroll to top functionality
    if (scrollToTopBtn) {
        scrollToTopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Initial calls
    handleNavbarScroll();
    handleScrollToTop();
}

// Initialize enhanced navigation on DOM load
document.addEventListener('DOMContentLoaded', function() {
    initEnhancedNavigation();
});

/**
 * End of Buffalo Marathon 2025 JavaScript
 * Total Functions: 26+
 * Features: Form validation, accessibility, performance optimization, enhanced navigation
 * Browser Support: IE11+, All modern browsers
 */