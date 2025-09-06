/**
 * ZIN Fashion - Enhanced Main JavaScript
 * Location: /public_html/dev_staging/assets/js/main.js
 * 
 * Complete production-ready JavaScript with all features
 */

// Global state management
const AppState = {
    cart: {
        items: [],
        count: 0,
        subtotal: 0,
        shipping: 0,
        total: 0
    },
    wishlist: [],
    user: {
        isLoggedIn: false,
        id: null,
        name: null
    },
    filters: {
        category: null,
        sizes: [],
        colors: [],
        minPrice: 0,
        maxPrice: 9999,
        sort: 'newest'
    }
};

/**
 * Initialize Application
 */
function initializeApp() {
    // Load config from window object (set by PHP)
    if (window.siteConfig) {
        AppState.user.isLoggedIn = window.siteConfig.isLoggedIn;
    }
    
    // Initialize all components
    initializeTheme();
    initializeLanguage();
    initializeNavigation();
    initializeSearch();
    initializeCart();
    initializeWishlist();
    initializeNewsletter();
    initializeCookieConsent();
    initializeBackToTop();
    initializeAccessibility();
    initializeLazyLoading();
    
    // Load initial data
    loadCartData();
    loadWishlistData();
    
    // Initialize page-specific features
    initializePageFeatures();
}

/**
 * Theme Management
 */
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'dark';
    
    // Apply current theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(currentTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const newTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            setCookie('theme', newTheme, 365);
            updateThemeIcon(newTheme);
            
            // Dispatch theme change event
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: newTheme }));
        });
    }
}

function updateThemeIcon(theme) {
    const darkIcon = document.querySelector('[data-theme-icon="dark"]');
    const lightIcon = document.querySelector('[data-theme-icon="light"]');
    
    if (darkIcon && lightIcon) {
        if (theme === 'dark') {
            darkIcon.style.display = 'block';
            lightIcon.style.display = 'none';
        } else {
            darkIcon.style.display = 'none';
            lightIcon.style.display = 'block';
        }
    }
}

/**
 * Language Management
 */
function initializeLanguage() {
    const langButtons = document.querySelectorAll('.lang-btn');
    const currentLang = localStorage.getItem('lang') || 'de';
    
    // Update all translations on load
    updateTranslations(currentLang);
    
    langButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const newLang = this.dataset.lang;
            
            // Update active state
            document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Save preference
            localStorage.setItem('lang', newLang);
            setCookie('lang', newLang, 365);
            
            // Update translations
            updateTranslations(newLang);
            
            // Update HTML attributes
            document.documentElement.lang = newLang;
            document.documentElement.dir = newLang === 'ar' ? 'rtl' : 'ltr';
            
            // Dispatch language change event
            window.dispatchEvent(new CustomEvent('languageChanged', { detail: newLang }));
        });
    });
}

function updateTranslations(lang) {
    if (typeof translations === 'undefined' || !translations[lang]) return;
    
    // Update text content
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });
    
    // Update placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (translations[lang][key]) {
            element.placeholder = translations[lang][key];
        }
    });
    
    // Update aria-labels
    document.querySelectorAll('[data-i18n-aria]').forEach(element => {
        const key = element.getAttribute('data-i18n-aria');
        if (translations[lang][key]) {
            element.setAttribute('aria-label', translations[lang][key]);
        }
    });
    
    // Update titles
    document.querySelectorAll('[data-i18n-title]').forEach(element => {
        const key = element.getAttribute('data-i18n-title');
        if (translations[lang][key]) {
            element.title = translations[lang][key];
        }
    });
}

/**
 * Navigation Management
 */
function initializeNavigation() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const nav = document.querySelector('.nav');
    const navOverlay = document.getElementById('navOverlay');
    const dropdownItems = document.querySelectorAll('.nav-item.has-dropdown');
    
    // Mobile menu toggle
    if (mobileToggle && nav) {
        mobileToggle.addEventListener('click', function() {
            const isOpen = nav.classList.contains('active');
            
            if (isOpen) {
                closeNav();
            } else {
                openNav();
            }
        });
    }
    
    // Nav overlay click
    if (navOverlay) {
        navOverlay.addEventListener('click', closeNav);
    }
    
    // Dropdown menus (desktop)
    dropdownItems.forEach(item => {
        let hoverTimeout;
        
        item.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            this.classList.add('open');
        });
        
        item.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                this.classList.remove('open');
            }, 300);
        });
        
        // Mobile dropdown toggle
        const link = item.querySelector('.nav-link');
        if (link) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 767) {
                    e.preventDefault();
                    item.classList.toggle('open');
                }
            });
        }
    });
    
    // Sticky header on scroll
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.header');
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
            
            // Hide/show on scroll direction
            if (currentScroll > lastScroll && currentScroll > 300) {
                header.classList.add('hidden');
            } else {
                header.classList.remove('hidden');
            }
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
}

function openNav() {
    const nav = document.querySelector('.nav');
    const navOverlay = document.getElementById('navOverlay');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    
    nav.classList.add('active');
    navOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    mobileToggle.setAttribute('aria-expanded', 'true');
}

function closeNav() {
    const nav = document.querySelector('.nav');
    const navOverlay = document.getElementById('navOverlay');
    const mobileToggle = document.getElementById('mobileMenuToggle');
    
    nav.classList.remove('active');
    navOverlay.classList.remove('active');
    document.body.style.overflow = '';
    mobileToggle.setAttribute('aria-expanded', 'false');
}

/**
 * Search Functionality
 */
function initializeSearch() {
    const searchForm = document.querySelector('.search-bar');
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(searchInput.value);
        });
    }
    
    // Mobile search toggle
    const mobileSearchToggle = document.getElementById('mobileSearchToggle');
    const searchBar = document.querySelector('.search-bar');
    
    if (mobileSearchToggle && searchBar) {
        mobileSearchToggle.addEventListener('click', function() {
            searchBar.classList.toggle('active');
            if (searchBar.classList.contains('active')) {
                searchInput.focus();
            }
        });
    }
    
    // Search suggestions (autocomplete)
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (this.value.length >= 2) {
                    fetchSearchSuggestions(this.value);
                }
            }, 300);
        });
    }
}

function performSearch(query) {
    if (!query.trim()) return;
    
    // Save search history
    saveSearchHistory(query);
    
    // Redirect to search results page
    window.location.href = `${window.siteConfig.siteUrl}/search?q=${encodeURIComponent(query)}`;
}

async function fetchSearchSuggestions(query) {
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=search-suggestions&q=${encodeURIComponent(query)}`);
        const suggestions = await response.json();
        
        displaySearchSuggestions(suggestions);
    } catch (error) {
        console.error('Error fetching search suggestions:', error);
    }
}

function displaySearchSuggestions(suggestions) {
    // Implementation for displaying search suggestions dropdown
    // This would create and position a dropdown below the search input
}

function saveSearchHistory(query) {
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
    
    // Remove if already exists
    history = history.filter(item => item !== query);
    
    // Add to beginning
    history.unshift(query);
    
    // Keep only last 10 searches
    history = history.slice(0, 10);
    
    localStorage.setItem('searchHistory', JSON.stringify(history));
}

/**
 * Cart Management
 */
function initializeCart() {
    const cartIcon = document.getElementById('cartIcon');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartModal = document.getElementById('cartModal');
    
    if (cartIcon) {
        cartIcon.addEventListener('click', openCart);
    }
    
    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }
    
    // Close cart on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cartModal && cartModal.classList.contains('active')) {
            closeCart();
        }
    });
}

async function loadCartData() {
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=get-cart`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            AppState.cart = data.cart;
            updateCartUI();
        }
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

function updateCartUI() {
    // Update cart count badge
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = AppState.cart.count;
        cartCount.style.display = AppState.cart.count > 0 ? 'flex' : 'none';
    }
    
    // Update cart modal content if open
    const cartModal = document.getElementById('cartModal');
    if (cartModal && cartModal.classList.contains('active')) {
        renderCart();
    }
}

function openCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        renderCart();
        cartModal.classList.add('active');
        cartOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus management for accessibility
        cartModal.focus();
    }
}

function closeCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        cartModal.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
        
        // Return focus to cart icon
        document.getElementById('cartIcon').focus();
    }
}

function renderCart() {
    const cartModal = document.getElementById('cartModal');
    if (!cartModal) return;
    
    const lang = localStorage.getItem('lang') || 'de';
    const t = (key) => translations[lang][key] || key;
    
    if (AppState.cart.items && AppState.cart.items.length > 0) {
        cartModal.innerHTML = `
            <div class="cart-header">
                <h2 class="cart-title">${t('shopping-cart')} (${AppState.cart.count})</h2>
                <button class="cart-close" onclick="closeCart()" aria-label="${t('close')}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="cart-content">
                <div class="cart-items">
                    ${AppState.cart.items.map(item => renderCartItem(item)).join('')}
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="cart-summary">
                    <div class="cart-subtotal">
                        <span>${t('subtotal')}</span>
                        <span>€${AppState.cart.subtotal.toFixed(2)}</span>
                    </div>
                    <div class="cart-shipping">
                        <span>${t('shipping')}</span>
                        <span>${AppState.cart.shipping === 0 ? t('free') : '€' + AppState.cart.shipping.toFixed(2)}</span>
                    </div>
                    <div class="cart-total">
                        <span>${t('total')}</span>
                        <span>€${AppState.cart.total.toFixed(2)}</span>
                    </div>
                </div>
                
                <button class="btn btn-primary cart-checkout-btn" onclick="goToCheckout()">
                    ${t('proceed-to-checkout')}
                </button>
                
                <a href="${window.siteConfig.siteUrl}/cart" class="cart-view-link">
                    ${t('view-full-cart')}
                </a>
            </div>
        `;
    } else {
        cartModal.innerHTML = `
            <div class="cart-header">
                <h2 class="cart-title">${t('shopping-cart')}</h2>
                <button class="cart-close" onclick="closeCart()" aria-label="${t('close')}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="cart-content cart-empty-state">
                <i class="fas fa-shopping-bag cart-empty-icon"></i>
                <p class="cart-empty-text">${t('cart-empty')}</p>
                <button class="btn btn-primary" onclick="closeCart()">
                    ${t('continue-shopping')}
                </button>
            </div>
        `;
    }
}

function renderCartItem(item) {
    const lang = localStorage.getItem('lang') || 'de';
    const t = (key) => translations[lang][key] || key;
    
    return `
        <div class="cart-item" data-item-id="${item.id}">
            <div class="cart-item-image">
                <img src="${item.image}" alt="${item.name}" loading="lazy">
            </div>
            
            <div class="cart-item-details">
                <h4 class="cart-item-name">${item.name}</h4>
                
                <div class="cart-item-meta">
                    ${item.size ? `<span>${t('size')}: ${item.size}</span>` : ''}
                    ${item.color ? `<span>${t('color')}: ${item.color}</span>` : ''}
                </div>
                
                <div class="cart-item-price">
                    ${item.sale_price ? `
                        <span class="original-price">€${item.price.toFixed(2)}</span>
                        <span class="sale-price">€${item.sale_price.toFixed(2)}</span>
                    ` : `
                        <span>€${item.price.toFixed(2)}</span>
                    `}
                </div>
                
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" 
                           class="quantity-input" 
                           value="${item.quantity}" 
                           min="1" 
                           max="${item.stock}"
                           onchange="updateCartQuantity(${item.id}, this.value)">
                    <button class="quantity-btn" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            
            <button class="cart-item-remove" 
                    onclick="removeFromCart(${item.id})" 
                    aria-label="${t('remove')}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
}

async function updateCartQuantity(itemId, quantity) {
    if (quantity < 1) {
        removeFromCart(itemId);
        return;
    }
    
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=update-cart`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.siteConfig.csrfToken
            },
            body: JSON.stringify({ itemId, quantity }),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            AppState.cart = data.cart;
            updateCartUI();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        showNotification('Error updating cart', 'error');
    }
}

async function removeFromCart(itemId) {
    if (!confirm('Remove this item from cart?')) return;
    
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=remove-from-cart`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.siteConfig.csrfToken
            },
            body: JSON.stringify({ itemId }),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            AppState.cart = data.cart;
            updateCartUI();
            showNotification('Item removed from cart', 'success');
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        showNotification('Error removing item', 'error');
    }
}

function goToCheckout() {
    if (window.siteConfig.isLoggedIn) {
        window.location.href = `${window.siteConfig.siteUrl}/checkout`;
    } else {
        // Save cart and redirect to login
        sessionStorage.setItem('redirectAfterLogin', '/checkout');
        window.location.href = `${window.siteConfig.siteUrl}/login`;
    }
}

/**
 * Wishlist Management
 */
function initializeWishlist() {
    // Add event listeners to wishlist buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.wishlist-btn')) {
            const btn = e.target.closest('.wishlist-btn');
            const productId = btn.dataset.productId;
            toggleWishlist(productId, btn);
        }
    });
}

async function loadWishlistData() {
    if (!window.siteConfig.isLoggedIn) return;
    
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=get-wishlist`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            AppState.wishlist = data.wishlist;
            updateWishlistUI();
        }
    } catch (error) {
        console.error('Error loading wishlist:', error);
    }
}

function updateWishlistUI() {
    const wishlistCount = document.querySelector('.wishlist-count');
    if (wishlistCount) {
        wishlistCount.textContent = AppState.wishlist.length;
        wishlistCount.style.display = AppState.wishlist.length > 0 ? 'flex' : 'none';
    }
    
    // Update wishlist buttons
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = parseInt(btn.dataset.productId);
        if (AppState.wishlist.includes(productId)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

async function toggleWishlist(productId, button) {
    if (!window.siteConfig.isLoggedIn) {
        sessionStorage.setItem('redirectAfterLogin', window.location.pathname);
        window.location.href = `${window.siteConfig.siteUrl}/login`;
        return;
    }
    
    const isInWishlist = button.classList.contains('active');
    
    try {
        const response = await fetch(`${window.siteConfig.siteUrl}/api.php?action=toggle-wishlist`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.siteConfig.csrfToken
            },
            body: JSON.stringify({ productId }),
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (isInWishlist) {
                AppState.wishlist = AppState.wishlist.filter(id => id !== parseInt(productId));
                button.classList.remove('active');
                showNotification('Removed from wishlist', 'success');
            } else {
                AppState.wishlist.push(parseInt(productId));
                button.classList.add('active');
                showNotification('Added to wishlist', 'success');
            }
            updateWishlistUI();
        }
    } catch (error) {
        console.error('Error toggling wishlist:', error);
        showNotification('Error updating wishlist', 'error');
    }
}

/**
 * Newsletter Management
 */
function initializeNewsletter() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Successfully subscribed to newsletter!', 'success');
                    this.reset();
                } else {
                    showNotification(data.message || 'Subscription failed', 'error');
                }
            } catch (error) {
                console.error('Newsletter subscription error:', error);
                showNotification('Error subscribing to newsletter', 'error');
            }
        });
    }
}

/**
 * Cookie Consent
 */
function initializeCookieConsent() {
    const cookieBanner = document.getElementById('cookieBanner');
    
    if (!cookieBanner) return;
    
    // Check if consent already given
    if (getCookie('cookie_consent')) {
        cookieBanner.style.display = 'none';
    }
}

function acceptCookies() {
    setCookie('cookie_consent', 'accepted', 365);
    document.getElementById('cookieBanner').style.display = 'none';
    
    // Initialize analytics and other cookies
    initializeAnalytics();
}

function declineCookies() {
    setCookie('cookie_consent', 'declined', 365);
    document.getElementById('cookieBanner').style.display = 'none';
}

/**
 * Back to Top Button
 */
function initializeBackToTop() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (!backToTopBtn) return;
    
    // Show/hide based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    // Smooth scroll to top
    backToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Accessibility Features
 */
function initializeAccessibility() {
    // Skip links
    const skipLinks = document.querySelectorAll('.skip-link');
    skipLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.tabIndex = -1;
                target.focus();
            }
        });
    });
    
    // Keyboard navigation for dropdowns
    const dropdowns = document.querySelectorAll('.has-dropdown');
    dropdowns.forEach(dropdown => {
        const link = dropdown.querySelector('.nav-link');
        if (link) {
            link.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    dropdown.classList.toggle('open');
                }
            });
        }
    });
    
    // Focus trap for modals
    const modals = document.querySelectorAll('.modal, .cart-modal');
    modals.forEach(modal => {
        trapFocus(modal);
    });
}

/**
 * Lazy Loading
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img.lazy').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        const lazyImages = document.querySelectorAll('img.lazy');
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

/**
 * Page-specific Features
 */
function initializePageFeatures() {
    // Product pages
    if (document.querySelector('.product-detail')) {
        initializeProductDetail();
    }
    
    // Shop/Category pages
    if (document.querySelector('.shop-page')) {
        initializeShopFilters();
    }
    
    // Home page
    if (document.querySelector('.hero-slider')) {
        initializeHeroSlider();
    }
    
    // Account pages
    if (document.querySelector('.account-page')) {
        initializeAccountFeatures();
    }
}

/**
 * Utility Functions
 */
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Lax`;
}

function deleteCookie(name) {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
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

function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
        'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
    );
    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];
    
    element.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey) { // Shift + Tab
                if (document.activeElement === firstFocusableElement) {
                    lastFocusableElement.focus();
                    e.preventDefault();
                }
            } else { // Tab
                if (document.activeElement === lastFocusableElement) {
                    firstFocusableElement.focus();
                    e.preventDefault();
                }
            }
        }
        
        if (e.key === 'Escape') {
            element.classList.remove('active');
        }
    });
}

function formatPrice(price, currency = '€') {
    return `${currency}${parseFloat(price).toFixed(2)}`;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

/**
 * Analytics
 */
function initializeAnalytics() {
    // Initialize Google Analytics, Matomo, or other analytics
    if (window.siteConfig && window.siteConfig.analyticsId) {
        // Analytics implementation
    }
}

/**
 * Export functions for global use
 */
window.ZINFashion = {
    init: initializeApp,
    cart: {
        open: openCart,
        close: closeCart,
        add: addToCart,
        update: updateCartQuantity,
        remove: removeFromCart
    },
    wishlist: {
        toggle: toggleWishlist
    },
    utils: {
        showNotification,
        formatPrice,
        validateEmail,
        validatePhone
    }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}
