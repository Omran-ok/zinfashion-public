/**
 * ZIN Fashion - Authentication JavaScript
 * Location: /public_html/dev_staging/assets/js/auth.js
 */

// ========================================
// Password Toggle
// ========================================
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// ========================================
// Password Strength Checker
// ========================================
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Check length
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Check for uppercase letters
    if (/[A-Z]/.test(password)) strength++;
    
    // Check for lowercase letters
    if (/[a-z]/.test(password)) strength++;
    
    // Check for numbers
    if (/[0-9]/.test(password)) strength++;
    
    // Check for special characters
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // Return strength level
    if (strength < 3) return 'weak';
    if (strength < 5) return 'medium';
    return 'strong';
}

// ========================================
// Initialize Password Strength Indicator
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordStrengthDiv = document.getElementById('passwordStrength');
    
    if (passwordInput && passwordStrengthDiv) {
        // Create strength bar
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength-bar';
        passwordStrengthDiv.appendChild(strengthBar);
        
        // Create strength text
        const strengthText = document.createElement('div');
        strengthText.className = 'password-strength-text';
        passwordStrengthDiv.appendChild(strengthText);
        
        // Update on input
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length > 0) {
                passwordStrengthDiv.classList.add('active');
                const strength = checkPasswordStrength(password);
                
                // Update bar
                strengthBar.className = `password-strength-bar ${strength}`;
                
                // Update text
                strengthText.className = `password-strength-text active ${strength}`;
                
                const strengthMessages = {
                    'weak': 'Weak password',
                    'medium': 'Medium strength',
                    'strong': 'Strong password'
                };
                
                strengthText.textContent = strengthMessages[strength];
            } else {
                passwordStrengthDiv.classList.remove('active');
                strengthText.classList.remove('active');
            }
        });
    }
    
    // Form validation
    initializeFormValidation();
});

// ========================================
// Form Validation
// ========================================
function initializeFormValidation() {
    const forms = document.querySelectorAll('.auth-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Remove previous error states
            form.querySelectorAll('.error').forEach(elem => {
                elem.classList.remove('error');
            });
            
            // Validate required fields
            form.querySelectorAll('[required]').forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                    
                    // Add error message if not exists
                    if (!input.nextElementSibling?.classList.contains('field-error')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.className = 'field-error';
                        errorMsg.textContent = 'This field is required';
                        input.parentNode.appendChild(errorMsg);
                    }
                }
            });
            
            // Validate email
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput && emailInput.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value)) {
                    emailInput.classList.add('error');
                    isValid = false;
                    
                    if (!emailInput.nextElementSibling?.classList.contains('field-error')) {
                        const errorMsg = document.createElement('span');
                        errorMsg.className = 'field-error';
                        errorMsg.textContent = 'Please enter a valid email address';
                        emailInput.parentNode.appendChild(errorMsg);
                    }
                }
            }
            
            // Validate password match (for registration)
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                confirmPassword.classList.add('error');
                isValid = false;
                
                if (!confirmPassword.parentNode.querySelector('.field-error')) {
                    const errorMsg = document.createElement('span');
                    errorMsg.className = 'field-error';
                    errorMsg.textContent = 'Passwords do not match';
                    confirmPassword.parentNode.appendChild(errorMsg);
                }
            }
            
            // Check terms acceptance
            const termsCheckbox = form.querySelector('#terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                termsCheckbox.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = form.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Remove error state on input
        form.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                const errorMsg = this.parentNode.querySelector('.field-error');
                if (errorMsg) {
                    errorMsg.remove();
                }
            });
        });
    });
}

// ========================================
// Account Menu Toggle (Mobile)
// ========================================
function toggleAccountMenu() {
    const sidebar = document.querySelector('.account-sidebar');
    if (sidebar) {
        sidebar.classList.toggle('mobile-open');
    }
}

// ========================================
// Address Form Handler
// ========================================
function initializeAddressForm() {
    const addressForms = document.querySelectorAll('.address-form');
    
    addressForms.forEach(form => {
        // Country selector
        const countrySelect = form.querySelector('#country');
        if (countrySelect) {
            countrySelect.addEventListener('change', function() {
                updateStateField(this.value);
            });
        }
        
        // Set as default checkbox
        const defaultCheckbox = form.querySelector('#is_default');
        if (defaultCheckbox) {
            defaultCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Uncheck other default addresses
                    document.querySelectorAll('.address-card').forEach(card => {
                        const otherCheckbox = card.querySelector('input[name="default_address"]');
                        if (otherCheckbox && otherCheckbox !== this) {
                            otherCheckbox.checked = false;
                        }
                    });
                }
            });
        }
    });
}

// ========================================
// Update State Field Based on Country
// ========================================
function updateStateField(country) {
    const stateField = document.getElementById('state');
    const stateLabel = stateField?.previousElementSibling;
    
    if (!stateField || !stateLabel) return;
    
    const germanStates = [
        'Baden-Württemberg', 'Bayern', 'Berlin', 'Brandenburg',
        'Bremen', 'Hamburg', 'Hessen', 'Mecklenburg-Vorpommern',
        'Niedersachsen', 'Nordrhein-Westfalen', 'Rheinland-Pfalz',
        'Saarland', 'Sachsen', 'Sachsen-Anhalt', 'Schleswig-Holstein', 'Thüringen'
    ];
    
    if (country === 'DE') {
        // Convert to select for German states
        const select = document.createElement('select');
        select.id = 'state';
        select.name = 'state';
        select.className = stateField.className;
        
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select state...';
        select.appendChild(defaultOption);
        
        germanStates.forEach(state => {
            const option = document.createElement('option');
            option.value = state;
            option.textContent = state;
            select.appendChild(option);
        });
        
        stateField.replaceWith(select);
        stateLabel.textContent = 'State *';
    } else {
        // Keep as text input for other countries
        if (stateField.tagName === 'SELECT') {
            const input = document.createElement('input');
            input.type = 'text';
            input.id = 'state';
            input.name = 'state';
            input.className = stateField.className;
            input.placeholder = 'State/Province/Region';
            
            stateField.replaceWith(input);
            stateLabel.textContent = 'State/Province';
        }
    }
}

// ========================================
// Delete Confirmation
// ========================================
function confirmDelete(itemType, itemId) {
    const message = `Are you sure you want to delete this ${itemType}? This action cannot be undone.`;
    
    if (confirm(message)) {
        // Submit delete form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="item_type" value="${itemType}">
            <input type="hidden" name="item_id" value="${itemId}">
            <input type="hidden" name="csrf_token" value="${window.csrfToken || ''}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// ========================================
// Initialize on DOM Ready
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize address forms
    initializeAddressForm();
    
    // Mobile account menu toggle
    const menuToggle = document.querySelector('.account-menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleAccountMenu);
    }
    
    // Add active class to current page in account menu
    const currentPath = window.location.pathname;
    document.querySelectorAll('.account-menu a').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});

// ========================================
// Export functions for global use
// ========================================
window.togglePassword = togglePassword;
window.confirmDelete = confirmDelete;
window.toggleAccountMenu = toggleAccountMenu;
