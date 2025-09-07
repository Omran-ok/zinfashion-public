/**
 * FILE: /assets/js/components/newsletter.js
 * Newsletter subscription functionality
 */

window.ZIN = window.ZIN || {};

ZIN.newsletter = {
    forms: [],
    isSubscribed: false,
    
    /**
     * Initialize newsletter
     */
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.checkSubscriptionStatus();
        ZIN.utils.debug('Newsletter component initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.forms = document.querySelectorAll('.newsletter-form, [data-newsletter-form]');
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        this.forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit(form);
            });
            
            // Email validation on blur
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput) {
                emailInput.addEventListener('blur', () => {
                    this.validateEmail(emailInput);
                });
            }
        });
    },
    
    /**
     * Check subscription status
     */
    checkSubscriptionStatus: function() {
        // Check if user is already subscribed
        const subscribed = ZIN.storage ? ZIN.storage.get('newsletter_subscribed') : 
                          localStorage.getItem('newsletter_subscribed');
        if (subscribed) {
            this.isSubscribed = true;
            this.updateForms();
        }
    },
    
    /**
     * Handle form submission
     */
    handleSubmit: async function(form) {
        const emailInput = form.querySelector('input[type="email"]');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!emailInput) return;
        
        const email = emailInput.value.trim();
        
        // Validate email
        if (!this.isValidEmail(email)) {
            this.showError(form, 'Bitte geben Sie eine gültige E-Mail-Adresse ein');
            return;
        }
        
        // Check if already subscribed
        if (this.isSubscribed) {
            this.showError(form, 'Sie sind bereits für unseren Newsletter angemeldet');
            return;
        }
        
        try {
            // Show loading state
            if (submitBtn) {
                if (ZIN.utils && ZIN.utils.addLoading) {
                    ZIN.utils.addLoading(submitBtn);
                } else {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('loading');
                }
                submitBtn.textContent = 'Wird angemeldet...';
            }
            
            // Subscribe via API
            let response;
            if (ZIN.api && ZIN.api.newsletter) {
                response = await ZIN.api.newsletter.subscribe(email);
            } else {
                // Fallback to direct fetch
                const formData = new FormData();
                formData.append('email', email);
                formData.append('action', 'newsletter');
                
                const res = await fetch('/api.php', {
                    method: 'POST',
                    body: formData
                });
                response = await res.json();
            }
            
            if (response.success) {
                this.handleSuccess(form, email);
            } else {
                this.showError(form, response.message || 'Anmeldung fehlgeschlagen');
            }
            
        } catch (error) {
            console.error('Newsletter subscription failed:', error);
            this.showError(form, 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        } finally {
            if (submitBtn) {
                if (ZIN.utils && ZIN.utils.removeLoading) {
                    ZIN.utils.removeLoading(submitBtn);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                }
                submitBtn.textContent = 'Anmelden';
            }
        }
    },
    
    /**
     * Handle successful subscription
     */
    handleSuccess: function(form, email) {
        // Mark as subscribed
        this.isSubscribed = true;
        
        // Save to storage
        if (ZIN.storage) {
            ZIN.storage.set('newsletter_subscribed', true);
            ZIN.storage.set('newsletter_email', email);
        } else {
            localStorage.setItem('newsletter_subscribed', 'true');
            localStorage.setItem('newsletter_email', email);
        }
        
        // Show success message
        this.showSuccess(form, 'Vielen Dank für Ihre Anmeldung!');
        
        // Show discount code if applicable
        this.showDiscountCode(form);
        
        // Clear form
        form.reset();
        
        // Update all forms
        this.updateForms();
        
        // Track event
        this.trackSubscription(email);
        
        // Show notification if available
        if (ZIN.notification) {
            ZIN.notification.show('Newsletter-Anmeldung erfolgreich! Prüfen Sie Ihre E-Mails für den Rabattcode.', 'success');
        } else if (ZIN.notifications) {
            ZIN.notifications.success('Newsletter-Anmeldung erfolgreich! Prüfen Sie Ihre E-Mails für den Rabattcode.');
        }
    },
    
    /**
     * Show discount code
     */
    showDiscountCode: function(form) {
        const discountCode = 'WILLKOMMEN10'; // 10% discount for new subscribers
        
        const codeDisplay = document.createElement('div');
        codeDisplay.className = 'newsletter-discount';
        codeDisplay.innerHTML = `
            <div class="discount-code-box">
                <h4>Ihr Willkommensrabatt</h4>
                <p>Verwenden Sie den Code für 10% Rabatt auf Ihre erste Bestellung:</p>
                <div class="discount-code">
                    <span class="code-text">${discountCode}</span>
                    <button class="code-copy" onclick="ZIN.newsletter.copyCode('${discountCode}')">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
                <small>Der Code wurde auch an Ihre E-Mail-Adresse gesendet</small>
            </div>
        `;
        
        // Insert after form
        form.parentNode.insertBefore(codeDisplay, form.nextSibling);
        
        // Auto-hide after 30 seconds
        setTimeout(() => {
            codeDisplay.style.opacity = '0';
            setTimeout(() => codeDisplay.remove(), 500);
        }, 30000);
    },
    
    /**
     * Copy discount code
     */
    copyCode: function(code) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(() => {
                if (ZIN.notification) {
                    ZIN.notification.show('Rabattcode kopiert!', 'success');
                } else if (ZIN.notifications) {
                    ZIN.notifications.success('Rabattcode kopiert!');
                } else {
                    alert('Rabattcode kopiert!');
                }
            }).catch(() => {
                this.fallbackCopyToClipboard(code);
            });
        } else {
            this.fallbackCopyToClipboard(code);
        }
    },
    
    /**
     * Fallback copy to clipboard
     */
    fallbackCopyToClipboard: function(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = '0';
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            if (ZIN.notifications) {
                ZIN.notifications.success('Rabattcode kopiert!');
            }
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        
        document.body.removeChild(textArea);
    },
    
    /**
     * Update forms for subscribed users
     */
    updateForms: function() {
        if (!this.isSubscribed) return;
        
        const savedEmail = ZIN.storage ? ZIN.storage.get('newsletter_email') : 
                          localStorage.getItem('newsletter_email');
        
        this.forms.forEach(form => {
            const emailInput = form.querySelector('input[type="email"]');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            if (emailInput) {
                emailInput.value = savedEmail || '';
                emailInput.disabled = true;
            }
            
            if (submitBtn) {
                submitBtn.textContent = 'Bereits angemeldet';
                submitBtn.disabled = true;
            }
            
            // Add unsubscribe link
            if (!form.querySelector('.newsletter-unsubscribe')) {
                const unsubLink = document.createElement('a');
                unsubLink.className = 'newsletter-unsubscribe';
                unsubLink.href = '#';
                unsubLink.textContent = 'Newsletter abbestellen';
                unsubLink.onclick = (e) => {
                    e.preventDefault();
                    this.unsubscribe();
                };
                form.appendChild(unsubLink);
            }
        });
    },
    
    /**
     * Unsubscribe from newsletter
     */
    unsubscribe: async function() {
        if (!confirm('Möchten Sie sich wirklich vom Newsletter abmelden?')) {
            return;
        }
        
        const email = ZIN.storage ? ZIN.storage.get('newsletter_email') : 
                     localStorage.getItem('newsletter_email');
        
        if (!email) return;
        
        try {
            // Clear local storage
            if (ZIN.storage) {
                ZIN.storage.remove('newsletter_subscribed');
                ZIN.storage.remove('newsletter_email');
            } else {
                localStorage.removeItem('newsletter_subscribed');
                localStorage.removeItem('newsletter_email');
            }
            
            this.isSubscribed = false;
            
            // Reset forms
            this.forms.forEach(form => {
                form.reset();
                const emailInput = form.querySelector('input[type="email"]');
                const submitBtn = form.querySelector('button[type="submit"]');
                
                if (emailInput) emailInput.disabled = false;
                if (submitBtn) {
                    submitBtn.textContent = 'Anmelden';
                    submitBtn.disabled = false;
                }
                
                const unsubLink = form.querySelector('.newsletter-unsubscribe');
                if (unsubLink) unsubLink.remove();
            });
            
            if (ZIN.notifications) {
                ZIN.notifications.success('Sie wurden erfolgreich abgemeldet');
            }
            
        } catch (error) {
            console.error('Unsubscribe failed:', error);
            if (ZIN.notifications) {
                ZIN.notifications.error('Fehler beim Abmelden');
            }
        }
    },
    
    /**
     * Validate email address
     */
    validateEmail: function(input) {
        const email = input.value.trim();
        
        if (!email) {
            this.clearValidation(input);
            return false;
        }
        
        if (this.isValidEmail(email)) {
            this.showValidation(input, true);
            return true;
        } else {
            this.showValidation(input, false, 'Ungültige E-Mail-Adresse');
            return false;
        }
    },
    
    /**
     * Check if email is valid
     */
    isValidEmail: function(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    /**
     * Show validation state
     */
    showValidation: function(input, isValid, message = '') {
        const container = input.parentElement;
        
        // Remove existing validation
        this.clearValidation(input);
        
        // Add validation class
        input.classList.add(isValid ? 'is-valid' : 'is-invalid');
        
        // Add feedback message
        if (message) {
            const feedback = document.createElement('div');
            feedback.className = isValid ? 'valid-feedback' : 'invalid-feedback';
            feedback.textContent = message;
            container.appendChild(feedback);
        }
    },
    
    /**
     * Clear validation state
     */
    clearValidation: function(input) {
        input.classList.remove('is-valid', 'is-invalid');
        
        const container = input.parentElement;
        const feedback = container.querySelector('.valid-feedback, .invalid-feedback');
        if (feedback) feedback.remove();
    },
    
    /**
     * Show success message
     */
    showSuccess: function(form, message) {
        this.clearMessages(form);
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-success newsletter-alert';
        alert.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        
        form.parentNode.insertBefore(alert, form);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    },
    
    /**
     * Show error message
     */
    showError: function(form, message) {
        this.clearMessages(form);
        
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger newsletter-alert';
        alert.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        
        form.parentNode.insertBefore(alert, form);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    },
    
    /**
     * Clear messages
     */
    clearMessages: function(form) {
        const alerts = form.parentNode.querySelectorAll('.newsletter-alert');
        alerts.forEach(alert => alert.remove());
    },
    
    /**
     * Track subscription event
     */
    trackSubscription: function(email) {
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'newsletter_subscribe', {
                'event_category': 'engagement',
                'event_label': email
            });
        }
        
        // Track with Facebook Pixel if available
        if (typeof fbq !== 'undefined') {
            fbq('track', 'Subscribe', {
                value: 10.00,
                currency: 'EUR'
            });
        }
        
        // Custom tracking event
        document.dispatchEvent(new CustomEvent('newsletter:subscribed', {
            detail: { email: email }
        }));
    }
};
