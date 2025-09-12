/**
 * ZIN Fashion - Main JavaScript
 * Location: /public_html/dev_staging/assets/js/main.js
 * Updated: Fixed Quick View Modal positioning and structure
 */

// ========================================
// DOM Ready
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeLanguageSelector();
    initializeHeaderScroll();
    initializeMobileMenu();
    initializeSearch();
    initializeNewsletterForm();
    initializeBackToTop();
    initializeCookieNotice();
    initializeCartSidebar();
    ensureLogoColors();
    
    // Initialize shopping cart from cart.js
    if (typeof ShoppingCart !== 'undefined' && !window.cart) {
        window.cart = new ShoppingCart();
    }
});

// ========================================
// Header Scroll Behavior
// ========================================
function initializeHeaderScroll() {
    const headerWrapper = document.getElementById('headerWrapper');
    let lastScrollY = window.scrollY;
    let scrollThreshold = 50;
    
    if (!headerWrapper) return;
    
    handleHeaderScroll();
    
    let scrollTimer;
    window.addEventListener('scroll', function() {
        if (scrollTimer) {
            window.cancelAnimationFrame(scrollTimer);
        }
        
        scrollTimer = window.requestAnimationFrame(function() {
            handleHeaderScroll();
        });
    });
    
    function handleHeaderScroll() {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > scrollThreshold) {
            headerWrapper.classList.add('scrolled');
        } else {
            headerWrapper.classList.remove('scrolled');
        }
        
        lastScrollY = currentScrollY;
    }
}

// ========================================
// Ensure Logo Colors
// ========================================
function ensureLogoColors() {
    const logos = document.querySelectorAll('.logo-img, .footer-logo img, .mobile-logo, img[alt="ZIN Fashion"]');
    
    logos.forEach(logo => {
        logo.style.filter = 'none';
        logo.style.webkitFilter = 'none';
        logo.style.opacity = '1';
    });
    
    const observer = new MutationObserver(function(mutations) {
        logos.forEach(logo => {
            if (logo.style.filter !== 'none') {
                logo.style.filter = 'none';
                logo.style.webkitFilter = 'none';
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
}

// ========================================
// Theme Management
// ========================================
function initializeTheme() {
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    
    const savedTheme = localStorage.getItem('theme') || 'dark';
    body.className = `theme-${savedTheme}`;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = body.classList.contains('theme-light') ? 'light' : 'dark';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            body.className = `theme-${newTheme}`;
            localStorage.setItem('theme', newTheme);
            
            body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        });
    }
}

// ========================================
// Language Selector
// ========================================
function initializeLanguageSelector() {
    const langToggle = document.getElementById('langToggle');
    const langDropdown = document.getElementById('langDropdown');
    
    if (!langToggle || !langDropdown) return;
    
    langToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isActive = langDropdown.classList.contains('active');
        
        if (!isActive) {
            langDropdown.classList.add('active');
            langDropdown.style.cssText = `
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                z-index: 999999 !important;
                position: absolute !important;
                top: 100% !important;
                right: 0 !important;
                margin-top: 5px !important;
                pointer-events: auto !important;
                transform: translateY(0) !important;
            `;
        } else {
            langDropdown.classList.remove('active');
            langDropdown.style.cssText = '';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.language-selector')) {
            langDropdown.classList.remove('active');
            langDropdown.style.cssText = '';
        }
    });
}

// ========================================
// Mobile Menu
// ========================================
function initializeMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    
    let overlay = document.querySelector('.mobile-menu-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-menu-overlay';
        document.body.appendChild(overlay);
    }
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            this.classList.add('active');
        });
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }
        overlay.addEventListener('click', closeMobileMenu);
        
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            mobileMenuToggle.classList.remove('active');
        }
        
        const mobileNavToggles = document.querySelectorAll('.mobile-nav-toggle');
        mobileNavToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const parent = this.closest('.mobile-nav-item');
                parent.classList.toggle('active');
            });
        });
    }
}

// ========================================
// Search Functionality
// ========================================
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    let searchTimeout;
    
    if (searchInput && searchSuggestions) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    fetchSearchSuggestions(query);
                }, 300);
            } else {
                searchSuggestions.innerHTML = '';
                searchSuggestions.style.display = 'none';
            }
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.header-search')) {
                searchSuggestions.style.display = 'none';
            }
        });
    }
}

async function fetchSearchSuggestions(query) {
    try {
        const response = await fetch(`/api.php?action=search&q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.results && data.results.length > 0) {
            displaySearchSuggestions(data.results);
        } else {
            const searchSuggestions = document.getElementById('searchSuggestions');
            searchSuggestions.innerHTML = '<div class="no-results">No results found</div>';
            searchSuggestions.style.display = 'block';
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function displaySearchSuggestions(results) {
    const searchSuggestions = document.getElementById('searchSuggestions');
    let html = '';
    
    results.slice(0, 5).forEach(product => {
        html += `
            <a href="/product/${product.product_slug}" class="search-suggestion-item">
                <img src="${product.image_url || '/assets/images/placeholder.jpg'}" alt="${product.product_name}">
                <div class="suggestion-info">
                    <div class="suggestion-name">${product.product_name}</div>
                    <div class="suggestion-price">€${product.price}</div>
                </div>
            </a>
        `;
    });
    
    searchSuggestions.innerHTML = html;
    searchSuggestions.style.display = 'block';
}

// ========================================
// Newsletter Form
// ========================================
function initializeNewsletterForm() {
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterMessage = document.getElementById('newsletterMessage');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.email.value;
            const button = this.querySelector('button');
            const originalText = button.textContent;
            
            button.textContent = 'Subscribing...';
            button.disabled = true;
            
            try {
                const response = await fetch('/api.php?action=newsletter', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    newsletterMessage.className = 'newsletter-message success';
                    newsletterMessage.textContent = 'Thank you for subscribing!';
                    newsletterForm.reset();
                } else {
                    newsletterMessage.className = 'newsletter-message error';
                    newsletterMessage.textContent = data.message || 'Subscription failed. Please try again.';
                }
            } catch (error) {
                newsletterMessage.className = 'newsletter-message error';
                newsletterMessage.textContent = 'An error occurred. Please try again.';
            } finally {
                button.textContent = originalText;
                button.disabled = false;
            }
        });
    }
}

// ========================================
// Back to Top Button
// ========================================
function initializeBackToTop() {
    let backToTop = document.getElementById('backToTop');
    
    if (!backToTop) {
        backToTop = document.createElement('button');
        backToTop.id = 'backToTop';
        backToTop.className = 'back-to-top';
        backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
        backToTop.setAttribute('aria-label', 'Back to top');
        document.body.appendChild(backToTop);
    }
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ========================================
// Cookie Notice
// ========================================
function initializeCookieNotice() {
    let cookieNotice = document.getElementById('cookieNotice');
    
    if (!cookieNotice) {
        cookieNotice = document.createElement('div');
        cookieNotice.id = 'cookieNotice';
        cookieNotice.className = 'cookie-notice';
        cookieNotice.innerHTML = `
            <div class="cookie-content">
                <p>We use cookies to enhance your shopping experience. By continuing to browse, you agree to our use of cookies.</p>
                <div class="cookie-actions">
                    <button id="cookieAccept" class="btn btn-primary btn-small">Accept</button>
                    <button id="cookieDecline" class="btn btn-outline btn-small">Decline</button>
                </div>
            </div>
        `;
        document.body.appendChild(cookieNotice);
    }
    
    const cookieAccept = document.getElementById('cookieAccept');
    const cookieDecline = document.getElementById('cookieDecline');
    
    const cookieChoice = localStorage.getItem('cookieConsent');
    
    if (!cookieChoice) {
        setTimeout(() => {
            cookieNotice.classList.add('show');
        }, 2000);
    }
    
    if (cookieAccept) {
        cookieAccept.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            cookieNotice.classList.remove('show');
            initializeAnalytics();
        });
    }
    
    if (cookieDecline) {
        cookieDecline.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'declined');
            cookieNotice.classList.remove('show');
        });
    }
}

function initializeAnalytics() {
    console.log('Analytics initialized');
}

// ========================================
// Cart Sidebar (Just handles open/close)
// ========================================
function initializeCartSidebar() {
    const cartTrigger = document.getElementById('cartTrigger');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartClose = document.getElementById('cartClose');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartTrigger && cartSidebar) {
        cartTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.cart) {
                window.cart.openCartSidebar();
            }
        });
        
        if (cartClose) {
            cartClose.addEventListener('click', function() {
                if (window.cart) {
                    window.cart.closeCartSidebar();
                }
            });
        }
        
        if (cartOverlay) {
            cartOverlay.addEventListener('click', function() {
                if (window.cart) {
                    window.cart.closeCartSidebar();
                }
            });
        }
    }
}

// ========================================
// Wishlist Operations
// ========================================
async function addToWishlist(productId) {
    try {
        const response = await fetch('/api.php?action=wishlist', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Added to wishlist!', 'success');
            updateWishlistCount();
            
            const wishlistBtn = document.querySelector(`.add-to-wishlist[data-product-id="${productId}"]`);
            if (wishlistBtn) {
                wishlistBtn.classList.add('active');
                wishlistBtn.querySelector('i').classList.remove('far');
                wishlistBtn.querySelector('i').classList.add('fas');
            }
        } else {
            showNotification(data.message || 'Failed to add to wishlist', 'error');
        }
    } catch (error) {
        console.error('Error adding to wishlist:', error);
    }
}

async function removeFromWishlist(productId) {
    try {
        const response = await fetch(`/api.php?action=wishlist&product_id=${productId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Removed from wishlist', 'info');
            updateWishlistCount();
            
            const wishlistBtn = document.querySelector(`.add-to-wishlist[data-product-id="${productId}"]`);
            if (wishlistBtn) {
                wishlistBtn.classList.remove('active');
                wishlistBtn.querySelector('i').classList.add('far');
                wishlistBtn.querySelector('i').classList.remove('fas');
            }
        }
    } catch (error) {
        console.error('Error removing from wishlist:', error);
    }
}

async function updateWishlistCount() {
    try {
        const response = await fetch('/api.php?action=getWishlistCount');
        const data = await response.json();
        
        const wishlistCount = document.getElementById('wishlistCount');
        if (wishlistCount) {
            if (data.count > 0) {
                wishlistCount.textContent = data.count;
                wishlistCount.style.display = 'flex';
            } else {
                wishlistCount.style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Error updating wishlist count:', error);
    }
}

// ========================================
// Product Actions
// ========================================
document.addEventListener('click', function(e) {
    // Add to cart button
    if (e.target.closest('.btn-add-to-cart')) {
        e.preventDefault();
        const button = e.target.closest('.btn-add-to-cart');
        const productId = button.dataset.productId;
        if (window.cart) {
            window.cart.addItem(productId);
        }
    }
    
    // Add to wishlist button
    if (e.target.closest('.add-to-wishlist')) {
        e.preventDefault();
        const button = e.target.closest('.add-to-wishlist');
        const productId = button.dataset.productId;
        
        if (button.classList.contains('active')) {
            removeFromWishlist(productId);
        } else {
            addToWishlist(productId);
        }
    }
    
    // Quick view button
    if (e.target.closest('.quick-view')) {
        e.preventDefault();
        const button = e.target.closest('.quick-view');
        const productId = button.dataset.productId;
        openQuickView(productId);
    }
});

// ========================================
// Quick View Modal - FIXED VERSION
// ========================================
async function openQuickView(productId) {
    try {
        // Fetch product data
        const response = await fetch(`/api.php?action=product&id=${productId}`);
        const product = await response.json();
        
        if (product) {
            showQuickViewModal(product);
        }
    } catch (error) {
        console.error('Error loading product:', error);
        showNotification('Failed to load product details', 'error');
    }
}

function showQuickViewModal(product) {
    // Remove any existing modal first
    const existingModal = document.getElementById('quickViewModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Get current language and translations
    const currentLang = document.documentElement.lang || 'de';
    const translations = window.translations || {};
    
    // Helper function to get translated text
    const t = (key, fallback) => {
        return translations[key] || fallback;
    };
    
    // Get translated product name based on language
    const productName = currentLang === 'en' && product.product_name_en ? product.product_name_en :
                       currentLang === 'ar' && product.product_name_ar ? product.product_name_ar :
                       product.product_name;
    
    // Get translated category name based on language
    const categoryName = currentLang === 'en' && product.category_name_en ? product.category_name_en :
                        currentLang === 'ar' && product.category_name_ar ? product.category_name_ar :
                        product.category_name;
    
    // Get translated description based on language
    const description = currentLang === 'en' && product.description_en ? product.description_en :
                       currentLang === 'ar' && product.description_ar ? product.description_ar :
                       product.description || t('no_description', 'No description available.');
    
    // Stock status translations
    const inStockText = t('in_stock', 'In Stock');
    const outOfStockText = t('out_of_stock', 'Out of Stock');
    const addToCartText = t('add_to_cart', 'Add to Cart');
    const viewDetailsText = t('view_details', 'View Details');
    const closeText = t('close', 'Close');
    
    // Create modal HTML with proper structure and translations
    const modalHtml = `
        <div class="modal" id="quickViewModal">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <button class="modal-close" aria-label="${closeText}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="quick-view-content">
                    <div class="quick-view-images">
                        <img src="${product.image_url || '/assets/images/placeholder.jpg'}" alt="${productName}">
                    </div>
                    <div class="quick-view-info">
                        ${categoryName ? `<div class="quick-view-category">${categoryName}</div>` : ''}
                        <h2>${productName}</h2>
                        <div class="quick-view-price">
                            ${product.sale_price ? `
                                <span class="price-old">€${formatPrice(product.base_price)}</span>
                                <span class="price-current">€${formatPrice(product.sale_price)}</span>
                            ` : `
                                <span class="price-current">€${formatPrice(product.base_price)}</span>
                            `}
                        </div>
                        ${product.stock_status !== undefined ? `
                            <div class="quick-view-stock">
                                <i class="fas fa-${product.in_stock ? 'check-circle' : 'times-circle'}"></i>
                                <span class="${product.in_stock ? 'stock-available' : 'stock-unavailable'}">
                                    ${product.in_stock ? inStockText : outOfStockText}
                                </span>
                            </div>
                        ` : ''}
                        <div class="quick-view-description">
                            ${description}
                        </div>
                        <div class="quick-view-actions">
                            <button class="btn btn-primary" ${!product.in_stock ? 'disabled' : ''} data-product-id="${product.product_id}">
                                <i class="fas fa-shopping-cart"></i> ${addToCartText}
                            </button>
                            <a href="/product/${product.product_slug}" class="btn btn-outline">
                                ${viewDetailsText}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Insert modal at the end of body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Get the newly created modal
    const modal = document.getElementById('quickViewModal');
    const modalClose = modal.querySelector('.modal-close');
    const modalOverlay = modal.querySelector('.modal-overlay');
    const addToCartBtn = modal.querySelector('.btn-primary[data-product-id]');
    
    // Add event listeners
    modalClose.addEventListener('click', closeQuickView);
    modalOverlay.addEventListener('click', closeQuickView);
    
    // Add to cart from quick view
    if (addToCartBtn && product.in_stock !== false) {
        addToCartBtn.addEventListener('click', function() {
            if (window.cart) {
                window.cart.addItem(product.product_id);
                closeQuickView();
            }
        });
    }
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
    
    // Add active class after a small delay for animation
    setTimeout(() => {
        modal.classList.add('active');
    }, 10);
    
    // Close on escape key
    document.addEventListener('keydown', handleEscapeKey);
}

function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Remove escape key listener
        document.removeEventListener('keydown', handleEscapeKey);
        
        // Remove modal after animation
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeQuickView();
    }
}

// ========================================
// Utility Functions
// ========================================
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const iconMap = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    notification.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : type === 'warning' ? '#f39c12' : '#3498db'};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 99999;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ========================================
// Make functions globally available
// ========================================
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
window.closeQuickView = closeQuickView;
window.openQuickView = openQuickView;
window.formatPrice = formatPrice;
window.showNotification = showNotification;
