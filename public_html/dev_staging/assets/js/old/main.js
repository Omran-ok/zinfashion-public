/**
 * ZIN Fashion - Main JavaScript (Cleaned Version)
 * Location: /public_html/dev_staging/assets/js/main.js
 * Removed: Unused functions, non-existent element handlers
 */

// ===========================
// DOM Ready
// ===========================

document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeLanguage();
    initializeNavigation();
    initializeSearch();
    initializeProductFilters();
    initializeNewsletter();
    initializeCart();
    initializeWishlist();
    initializeHeroSlider();
});

// ===========================
// Theme Management
// ===========================

function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update cookie for PHP
    document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
}

// ===========================
// Language Management
// ===========================

function initializeLanguage() {
    const savedLang = localStorage.getItem('lang') || 'de';
    updateLanguage(savedLang);
}

function changeLanguage(lang) {
    localStorage.setItem('lang', lang);
    document.cookie = `lang=${lang};path=/;max-age=31536000`;
    
    // Update text content
    updateLanguage(lang);
    
    // Update HTML attributes
    document.documentElement.setAttribute('lang', lang);
    document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
}

function updateLanguage(lang) {
    if (typeof translations === 'undefined') return;
    
    const elements = document.querySelectorAll('[data-i18n]');
    elements.forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });
    
    // Update placeholders
    const placeholders = document.querySelectorAll('[data-i18n-placeholder]');
    placeholders.forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (translations[lang] && translations[lang][key]) {
            element.placeholder = translations[lang][key];
        }
    });
}

// ===========================
// Navigation
// ===========================

function initializeNavigation() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const nav = document.querySelector('.nav');
    const navOverlay = document.querySelector('.nav-overlay') || createNavOverlay();
    
    if (mobileToggle && nav) {
        mobileToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            navOverlay.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        });
        
        navOverlay.addEventListener('click', function() {
            nav.classList.remove('active');
            navOverlay.classList.remove('active');
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

function createNavOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'nav-overlay';
    document.body.appendChild(overlay);
    return overlay;
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
        
        // Live search with debounce
        searchInput.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query.length > 2) {
                performSearch(query);
            }
        }, 300));
    }
}

async function performSearch(query) {
    try {
        const response = await fetch(`/api.php?action=search&q=${encodeURIComponent(query)}`);
        if (response.ok) {
            const results = await response.json();
            // Display search results (implement based on your UI)
            console.log('Search results:', results);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

// ===========================
// Hero Slider
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
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    // Auto-play
    setInterval(nextSlide, 5000);
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentSlide = index;
            showSlide(currentSlide);
        });
    });
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
// Cart Functions
// ===========================

function initializeCart() {
    // Load cart on page load
    loadCart();
    
    // Initialize cart icon click
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
        cartIcon.addEventListener('click', toggleCart);
    }
}

async function loadCart() {
    try {
        const response = await fetch('/api.php?action=cart');
        if (response.ok) {
            const cartData = await response.json();
            updateCartUI(cartData);
        }
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

function updateCartUI(cartData) {
    // Update cart count
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const totalItems = cartData.items ? cartData.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

function toggleCart() {
    const cartModal = document.querySelector('.cart-modal');
    const cartOverlay = document.querySelector('.cart-overlay');
    
    if (cartModal && cartOverlay) {
        const isActive = cartModal.classList.contains('active');
        
        cartModal.classList.toggle('active');
        cartOverlay.classList.toggle('active');
        document.body.style.overflow = isActive ? '' : 'hidden';
        
        if (!isActive) {
            loadCart(); // Refresh cart when opening
        }
    }
}

// ===========================
// Wishlist
// ===========================

function initializeWishlist() {
    const wishlistButtons = document.querySelectorAll('.wishlist-btn');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            toggleWishlist(productId, this);
        });
    });
}

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
            showNotification('Wishlist updated', 'success');
        }
    } catch (error) {
        console.error('Wishlist error:', error);
        showNotification('Error updating wishlist', 'error');
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
    const notification = document.createElement('div');
    notification.className = `notification ${type} show`;
    notification.innerHTML = `
        ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : ''}
        ${type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : ''}
        ${type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' : ''}
        ${type === 'info' ? '<i class="fas fa-info-circle"></i>' : ''}
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ===========================
// AJAX Helper
// ===========================

async function ajaxRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            ...options,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('AJAX request failed:', error);
        throw error;
    }
}

// ===========================
// Form Validation
// ===========================

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
        
        if (input.type === 'email' && !validateEmail(input.value)) {
            input.classList.add('error');
            isValid = false;
        }
    });
    
    return isValid;
}

// ===========================
// Export for use in other scripts
// ===========================

window.ZINFashion = {
    toggleTheme,
    changeLanguage,
    toggleCart,
    toggleWishlist,
    showNotification,
    ajaxRequest,
    validateForm,
    debounce
};
