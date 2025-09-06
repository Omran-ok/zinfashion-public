/**
 * ZIN Fashion - Main JavaScript (Fixed)
 * Location: /public_html/dev_staging/assets/js/main.js
 * 
 * Complete working implementation with theme toggle
 */

// ===========================
// Theme Management System
// ===========================

/**
 * Initialize theme on page load
 */
(function initializeThemeImmediately() {
    // Get saved theme from localStorage or cookie
    const savedTheme = localStorage.getItem('theme') || 
                      getCookieValue('theme') || 
                      'dark';
    
    // Apply theme immediately to prevent flash
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update icon visibility
    updateThemeIconVisibility(savedTheme);
})();

/**
 * Global theme toggle function (called by onclick)
 */
function toggleTheme(button) {
    // Get current theme
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    // Add animation class if button provided
    if (button) {
        button.classList.add('switching');
        setTimeout(() => button.classList.remove('switching'), 500);
    }
    
    // Apply new theme
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // Save to both localStorage and cookie
    localStorage.setItem('theme', newTheme);
    setCookie('theme', newTheme, 365);
    
    // Update icon visibility
    updateThemeIconVisibility(newTheme);
    
    // Dispatch custom event
    window.dispatchEvent(new CustomEvent('themeChanged', { 
        detail: { oldTheme: currentTheme, newTheme: newTheme } 
    }));
}

/**
 * Update theme icon visibility
 */
function updateThemeIconVisibility(theme) {
    const darkIcon = document.querySelector('[data-theme-icon="dark"]');  // Moon icon
    const lightIcon = document.querySelector('[data-theme-icon="light"]'); // Sun icon
    
    if (darkIcon && lightIcon) {
        if (theme === 'dark') {
            // In dark mode, show moon icon
            darkIcon.style.display = 'block';
            lightIcon.style.display = 'none';
        } else {
            // In light mode, show sun icon
            darkIcon.style.display = 'none';
            lightIcon.style.display = 'block';
        }
    }
}

// ===========================
// Language Management
// ===========================

/**
 * Change language (called by dropdown)
 */
function changeLanguage(lang) {
    localStorage.setItem('lang', lang);
    setCookie('lang', lang, 365);
    location.reload();
}

/**
 * Initialize language system
 */
function initializeLanguage() {
    const langButtons = document.querySelectorAll('.lang-btn');
    const currentLang = localStorage.getItem('lang') || getCookieValue('lang') || 'de';
    
    // Set active language button
    langButtons.forEach(btn => {
        if (btn.dataset.lang === currentLang) {
            btn.classList.add('active');
        }
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const newLang = this.dataset.lang;
            changeLanguage(newLang);
        });
    });
    
    // Update translations if available
    if (typeof updateTranslations === 'function') {
        updateTranslations(currentLang);
    }
}

// ===========================
// Cookie Management
// ===========================

function getCookieValue(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Lax`;
}

function acceptCookies() {
    setCookie('cookie_consent', 'accepted', 365);
    const banner = document.getElementById('cookieBanner');
    if (banner) banner.style.display = 'none';
}

function declineCookies() {
    setCookie('cookie_consent', 'declined', 365);
    const banner = document.getElementById('cookieBanner');
    if (banner) banner.style.display = 'none';
}

// ===========================
// Cart Management
// ===========================

function openCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        cartModal.classList.add('active');
        cartOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Load cart items if function exists
        if (typeof loadCart === 'function') {
            loadCart();
        }
    }
}

function closeCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        cartModal.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ===========================
// Navigation
// ===========================

function initializeNavigation() {
    // Mobile menu toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const nav = document.querySelector('.nav');
    
    if (mobileToggle && nav) {
        // Create overlay if it doesn't exist
        let overlay = document.querySelector('.nav-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'nav-overlay';
            document.body.appendChild(overlay);
        }
        
        mobileToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        });
        
        overlay.addEventListener('click', function() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Dropdown menus
    const dropdowns = document.querySelectorAll('.nav-item.has-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('mouseenter', function() {
            this.querySelector('.mega-menu')?.classList.add('active');
        });
        dropdown.addEventListener('mouseleave', function() {
            this.querySelector('.mega-menu')?.classList.remove('active');
        });
    });
}

// ===========================
// Search Functionality
// ===========================

function initializeSearch() {
    const searchForm = document.querySelector('.search-bar');
    const searchInput = document.querySelector('.search-input');
    
    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', function(e) {
            if (!searchInput.value.trim()) {
                e.preventDefault();
                searchInput.focus();
            }
        });
        
        // Search suggestions (if implemented)
        searchInput.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length > 2) {
                // Load search suggestions
                if (typeof loadSearchSuggestions === 'function') {
                    loadSearchSuggestions(query);
                }
            }
        }, 300));
    }
}

// ===========================
// Newsletter
// ===========================

function initializeNewsletter() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const csrfToken = this.querySelector('input[name="csrf_token"]').value;
            
            try {
                const response = await fetch('/api.php?action=newsletter', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}&csrf_token=${csrfToken}`
                });
                
                if (response.ok) {
                    showNotification('Newsletter subscription successful!', 'success');
                    this.reset();
                } else {
                    showNotification('Subscription failed. Please try again.', 'error');
                }
            } catch (error) {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    }
}

// ===========================
// Utility Functions
// ===========================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// ===========================
// Hero Slider (Homepage)
// ===========================

function initializeHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }
    
    // Make functions global for onclick handlers
    window.changeSlide = function(direction) {
        currentSlide = (currentSlide + direction + slides.length) % slides.length;
        showSlide(currentSlide);
    };
    
    window.currentSlide = function(index) {
        currentSlide = index - 1;
        showSlide(currentSlide);
    };
    
    // Auto-play
    setInterval(() => {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }, 5000);
}

// ===========================
// Product Filters
// ===========================

function initializeProductFilters() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const filter = this.dataset.filter;
            
            // Update active tab
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter products
            const products = document.querySelectorAll('.product-card');
            products.forEach(product => {
                if (filter === 'all' || product.dataset.category === filter) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        });
    });
}

// ===========================
// Wishlist
// ===========================

async function toggleWishlist(productId, button) {
    try {
        const response = await fetch('/api.php?action=wishlist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ product_id: productId })
        });
        
        if (response.ok) {
            button.classList.toggle('active');
            const icon = button.querySelector('i');
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
        }
    } catch (error) {
        console.error('Wishlist error:', error);
    }
}

// ===========================
// Back to Top
// ===========================

function initializeBackToTop() {
    // Create button if it doesn't exist
    let backToTop = document.getElementById('backToTop');
    if (!backToTop) {
        backToTop = document.createElement('button');
        backToTop.id = 'backToTop';
        backToTop.className = 'back-to-top';
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTop.style.display = 'none';
        document.body.appendChild(backToTop);
    }
    
    // Show/hide based on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
    
    // Scroll to top on click
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ===========================
// Initialize Everything
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    // Core functionality
    initializeLanguage();
    initializeNavigation();
    initializeSearch();
    initializeNewsletter();
    initializeBackToTop();
    
    // Page-specific features
    initializeHeroSlider();
    initializeProductFilters();
    
    // Check cookie consent
    if (!getCookieValue('cookie_consent')) {
        const cookieBanner = document.getElementById('cookieBanner');
        if (cookieBanner) {
            cookieBanner.style.display = 'block';
        }
    }
    
    // Initialize cart if cart.js is loaded
    if (typeof initializeCart === 'function') {
        initializeCart();
    }
    
    // Log successful initialization
    console.log('ZIN Fashion initialized successfully');
});

// ===========================
// Admin Panel Functions
// ===========================

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    }
}
