/**
 * FILE: /assets/js/components/footer.js
 * Footer component functionality
 */

window.ZIN = window.ZIN || {};

ZIN.footer = {
    elements: {},
    backToTopVisible: false,
    
    /**
     * Initialize footer component
     */
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.initBackToTop();
        this.initNewsletter();
        ZIN.utils.debug('Footer component initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.elements = {
            footer: document.querySelector('.footer'),
            newsletterForm: document.getElementById('newsletterForm'),
            newsletterInput: document.querySelector('.newsletter-input'),
            newsletterBtn: document.querySelector('.newsletter-btn'),
            backToTop: document.querySelector('.back-to-top'),
            socialLinks: document.querySelectorAll('.social-link'),
            footerLinks: document.querySelectorAll('.footer-links a')
        };
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Newsletter form
        if (this.elements.newsletterForm) {
            this.elements.newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleNewsletterSubmit();
            });
        }
        
        // Back to top button
        if (this.elements.backToTop) {
            this.elements.backToTop.addEventListener('click', (e) => {
                e.preventDefault();
                this.scrollToTop();
            });
        }
        
        // Social links tracking
        this.elements.socialLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const platform = this.getSocialPlatform(link);
                this.trackSocialClick(platform);
            });
        });
        
        // Footer links
        this.elements.footerLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Add smooth scroll for anchor links
                if (link.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(link.getAttribute('href'));
                    if (target) {
                        ZIN.utils.scrollTo(target, 100);
                    }
                }
            });
        });
    },
    
    /**
     * Initialize newsletter functionality
     */
    initNewsletter: function() {
        // Validate email on input
        if (this.elements.newsletterInput) {
            this.elements.newsletterInput.addEventListener('input', () => {
                this.validateNewsletterInput();
            });
            
            // Clear validation on focus
            this.elements.newsletterInput.addEventListener('focus', () => {
                this.clearNewsletterValidation();
            });
        }
    },
    
    /**
     * Handle newsletter form submission
     */
    handleNewsletterSubmit: async function() {
        const email = this.elements.newsletterInput.value.trim();
        
        // Validate email
        if (!this.validateEmail(email)) {
            this.showNewsletterError('Please enter a valid email address');
            return;
        }
        
        // Show loading state
        ZIN.utils.addLoading(this.elements.newsletterBtn);
        this.elements.newsletterBtn.textContent = 'Subscribing...';
        
        // Submit to API
        const result = await ZIN.api.subscribeNewsletter(email);
        
        // Remove loading state
        ZIN.utils.removeLoading(this.elements.newsletterBtn);
        this.elements.newsletterBtn.textContent = 'Subscribe';
        
        if (result.success) {
            // Success
            this.showNewsletterSuccess();
            this.elements.newsletterInput.value = '';
            ZIN.notifications.success(ZIN.config.newsletter.successMessage);
            
            // Track event
            this.trackNewsletterSignup(email);
        } else {
            // Error
            this.showNewsletterError(result.error || ZIN.config.newsletter.errorMessage);
            ZIN.notifications.error(ZIN.config.newsletter.errorMessage);
        }
    },
    
    /**
     * Validate email format
     */
    validateEmail: function(email) {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return pattern.test(email);
    },
    
    /**
     * Validate newsletter input
     */
    validateNewsletterInput: function() {
        const email = this.elements.newsletterInput.value.trim();
        
        if (email.length > 0 && !this.validateEmail(email)) {
            this.elements.newsletterInput.classList.add('error');
            return false;
        }
        
        this.elements.newsletterInput.classList.remove('error');
        return true;
    },
    
    /**
     * Clear newsletter validation
     */
    clearNewsletterValidation: function() {
        this.elements.newsletterInput.classList.remove('error', 'success');
        const message = this.elements.newsletterForm.querySelector('.newsletter-message');
        if (message) {
            message.remove();
        }
    },
    
    /**
     * Show newsletter success message
     */
    showNewsletterSuccess: function() {
        this.elements.newsletterInput.classList.remove('error');
        this.elements.newsletterInput.classList.add('success');
        
        // Add success message
        const existingMessage = this.elements.newsletterForm.querySelector('.newsletter-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const message = document.createElement('div');
        message.className = 'newsletter-message success';
        message.innerHTML = '<i class="fas fa-check-circle"></i> Successfully subscribed!';
        this.elements.newsletterForm.appendChild(message);
        
        // Remove message after 5 seconds
        setTimeout(() => {
            message.remove();
            this.elements.newsletterInput.classList.remove('success');
        }, 5000);
    },
    
    /**
     * Show newsletter error message
     */
    showNewsletterError: function(error) {
        this.elements.newsletterInput.classList.add('error');
        this.elements.newsletterInput.classList.remove('success');
        
        // Add error message
        const existingMessage = this.elements.newsletterForm.querySelector('.newsletter-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        const message = document.createElement('div');
        message.className = 'newsletter-message error';
        message.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error}`;
        this.elements.newsletterForm.appendChild(message);
        
        // Remove message after 5 seconds
        setTimeout(() => {
            message.remove();
        }, 5000);
    },
    
    /**
     * Initialize back to top button
     */
    initBackToTop: function() {
        // Create button if it doesn't exist
        if (!this.elements.backToTop) {
            this.createBackToTopButton();
        }
        
        // Show/hide on scroll
        window.addEventListener('scroll', ZIN.utils.throttle(() => {
            this.toggleBackToTop();
        }, 200));
        
        // Initial check
        this.toggleBackToTop();
    },
    
    /**
     * Create back to top button
     */
    createBackToTopButton: function() {
        const button = document.createElement('button');
        button.className = 'back-to-top';
        button.innerHTML = '<i class="fas fa-arrow-up"></i>';
        button.setAttribute('aria-label', 'Back to top');
        button.style.display = 'none';
        
        document.body.appendChild(button);
        this.elements.backToTop = button;
        
        // Bind click event
        button.addEventListener('click', (e) => {
            e.preventDefault();
            this.scrollToTop();
        });
    },
    
    /**
     * Toggle back to top button visibility
     */
    toggleBackToTop: function() {
        if (!this.elements.backToTop) return;
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 500) {
            if (!this.backToTopVisible) {
                this.elements.backToTop.classList.add('show');
                this.backToTopVisible = true;
            }
        } else {
            if (this.backToTopVisible) {
                this.elements.backToTop.classList.remove('show');
                this.backToTopVisible = false;
            }
        }
    },
    
    /**
     * Scroll to top of page
     */
    scrollToTop: function() {
        ZIN.utils.scrollTo('body', 0, 500);
    },
    
    /**
     * Get social platform from link
     */
    getSocialPlatform: function(link) {
        const href = link.getAttribute('href') || '';
        
        if (href.includes('facebook')) return 'facebook';
        if (href.includes('instagram')) return 'instagram';
        if (href.includes('twitter')) return 'twitter';
        if (href.includes('tiktok')) return 'tiktok';
        if (href.includes('youtube')) return 'youtube';
        if (href.includes('linkedin')) return 'linkedin';
        
        return 'unknown';
    },
    
    /**
     * Track social link click
     */
    trackSocialClick: function(platform) {
        ZIN.utils.debug('Social link clicked:', platform);
        
        // Google Analytics tracking if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'social_click', {
                'social_platform': platform,
                'location': 'footer'
            });
        }
        
        // Facebook Pixel tracking if available
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Contact', {
                content_name: 'Social Link',
                content_category: platform
            });
        }
    },
    
    /**
     * Track newsletter signup
     */
    trackNewsletterSignup: function(email) {
        ZIN.utils.debug('Newsletter signup:', email);
        
        // Google Analytics tracking if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'sign_up', {
                'method': 'newsletter'
            });
        }
        
        // Facebook Pixel tracking if available
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Lead', {
                content_name: 'Newsletter',
                content_category: 'Email Signup'
            });
        }
    },
    
    /**
     * Update copyright year
     */
    updateCopyrightYear: function() {
        const yearElement = document.querySelector('.copyright-year');
        if (yearElement) {
            yearElement.textContent = new Date().getFullYear();
        }
    }
};
