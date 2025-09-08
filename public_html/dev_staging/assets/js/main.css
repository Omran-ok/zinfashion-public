/**
 * ZIN Fashion - Main JavaScript
 * Location: /public_html/dev_staging/assets/js/main.js
 */

// ========================================
// DOM Ready
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeLanguageSelector();
    initializeMobileMenu();
    initializeSearch();
    initializeNewsletterForm();
    initializeBackToTop();
    initializeCookieNotice();
    initializeCartSidebar();
    ensureLogoColors();
});

// ========================================
// Ensure Logo Colors (Fix for any CSS conflicts)
// ========================================
function ensureLogoColors() {
    // Get all logo images
    const logos = document.querySelectorAll('.logo-img, .footer-logo img, .mobile-logo, img[alt="ZIN Fashion"]');
    
    // Remove any filters applied to logos
    logos.forEach(logo => {
        logo.style.filter = 'none';
        logo.style.webkitFilter = 'none';
        logo.style.opacity = '1';
    });
    
    // Also ensure no filter is applied when theme changes
    const observer = new MutationObserver(function(mutations) {
        logos.forEach(logo => {
            if (logo.style.filter !== 'none') {
                logo.style.filter = 'none';
                logo.style.webkitFilter = 'none';
            }
        });
    });
    
    // Observe body for class changes (theme switches)
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
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('theme') || 'dark';
    body.className = `theme-${savedTheme}`;
    
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const currentTheme = body.classList.contains('theme-light') ? 'light' : 'dark';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            body.className = `theme-${newTheme}`;
            localStorage.setItem('theme', newTheme);
            
            // Smooth transition
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
    
    if (langToggle && langDropdown) {
        langToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            langDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            langDropdown.classList.remove('active');
        });
        
        // Handle language selection
        const langOptions = langDropdown.querySelectorAll('.lang-option');
        langOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                const lang = this.href.split('lang=')[1];
                changeLanguage(lang);
            });
        });
    }
}

function changeLanguage(lang) {
    // Set language cookie
    document.cookie = `language=${lang};path=/;max-age=31536000`;
    
    // Update URL with language parameter
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
}

// ========================================
// Mobile Menu
// ========================================
function initializeMobileMenu() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    document.body.appendChild(overlay);
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        mobileMenuClose.addEventListener('click', closeMobileMenu);
        overlay.addEventListener('click', closeMobileMenu);
        
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        // Mobile submenu toggles
        const mobileNavToggles = document.querySelectorAll('.mobile-nav-toggle');
        mobileNavToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
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
        
        // Close suggestions when clicking outside
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
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        // Show/hide button based on scroll position
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        // Scroll to top when clicked
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// ========================================
// Cookie Notice
// ========================================
function initializeCookieNotice() {
    const cookieNotice = document.getElementById('cookieNotice');
    const cookieAccept = document.getElementById('cookieAccept');
    const cookieDecline = document.getElementById('cookieDecline');
    
    if (cookieNotice) {
        // Check if user has already made a choice
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
                // Initialize analytics and tracking
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
}

function initializeAnalytics() {
    // Initialize Google Analytics, Facebook Pixel, etc.
    console.log('Analytics initialized');
}

// ========================================
// Cart Sidebar
// ========================================
function initializeCartSidebar() {
    const cartTrigger = document.getElementById('cartTrigger');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartClose = document.getElementById('cartClose');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartTrigger && cartSidebar) {
        cartTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            openCartSidebar();
        });
        
        if (cartClose) {
            cartClose.addEventListener('click', closeCartSidebar);
        }
        
        if (cartOverlay) {
            cartOverlay.addEventListener('click', closeCartSidebar);
        }
    }
    
    // Load cart content on page load
    loadCartContent();
}

function openCartSidebar() {
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    
    cartSidebar.classList.add('active');
    cartOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Load latest cart content
    loadCartContent();
}

function closeCartSidebar() {
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    
    cartSidebar.classList.remove('active');
    cartOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

async function loadCartContent() {
    try {
        const response = await fetch('/api.php?action=cart');
        const data = await response.json();
        
        updateCartDisplay(data);
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

function updateCartDisplay(cartData) {
    const cartContent = document.getElementById('cartContent');
    const cartCount = document.getElementById('cartCount');
    const cartTotalAmount = document.getElementById('cartTotalAmount');
    
    if (cartData.items && cartData.items.length > 0) {
        let html = '<div class="cart-items">';
        
        cartData.items.forEach(item => {
            html += `
                <div class="cart-item" data-item-id="${item.cart_item_id}">
                    <div class="cart-item-image">
                        <img src="${item.image_url}" alt="${item.product_name}">
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.product_name}</div>
                        <div class="cart-item-meta">
                            ${item.size ? `Size: ${item.size}` : ''}
                            ${item.color ? ` | Color: ${item.color}` : ''}
                        </div>
                        <div class="cart-item-price">
                            <span class="quantity">${item.quantity} x</span>
                            <span class="price">€${formatPrice(item.price || item.base_price)}</span>
                        </div>
                    </div>
                    <button class="cart-item-remove" onclick="removeFromCart(${item.cart_item_id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        });
        
        html += '</div>';
        
        if (cartData.free_shipping_remaining > 0) {
            html += `
                <div class="free-shipping-notice">
                    <i class="fas fa-truck"></i>
                    Add €${formatPrice(cartData.free_shipping_remaining)} more for free shipping!
                </div>
            `;
        }
        
        cartContent.innerHTML = html;
    } else {
        cartContent.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-shopping-bag"></i>
                <p>Your cart is empty</p>
                <a href="/shop" class="btn btn-primary btn-small">Start Shopping</a>
            </div>
        `;
    }
    
    // Update cart count
    if (cartCount) {
        const itemCount = cartData.items ? cartData.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
        if (itemCount > 0) {
            cartCount.textContent = itemCount;
            cartCount.style.display = 'flex';
        } else {
            cartCount.style.display = 'none';
        }
    }
    
    // Update total amount
    if (cartTotalAmount) {
        cartTotalAmount.textContent = '€' + formatPrice(cartData.total || 0);
    }
}

// ========================================
// Cart Operations
// ========================================
async function addToCart(productId, variantId = null, quantity = 1) {
    try {
        const response = await fetch('/api.php?action=cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                variant_id: variantId,
                quantity: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            showNotification('Product added to cart!', 'success');
            
            // Update cart display
            loadCartContent();
            
            // Open cart sidebar
            openCartSidebar();
        } else {
            showNotification(data.message || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('An error occurred', 'error');
    }
}

async function removeFromCart(itemId) {
    try {
        const response = await fetch(`/api.php?action=cart&item_id=${itemId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCartContent();
            showNotification('Item removed from cart', 'info');
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
    }
}

async function updateCartQuantity(itemId, quantity) {
    try {
        const response = await fetch('/api.php?action=cart', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: quantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCartContent();
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
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
            
            // Update heart icon
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
            
            // Update heart icon
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
        addToCart(productId);
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
// Quick View Modal
// ========================================
async function openQuickView(productId) {
    try {
        const response = await fetch(`/api.php?action=product&id=${productId}`);
        const product = await response.json();
        
        if (product) {
            showQuickViewModal(product);
        }
    } catch (error) {
        console.error('Error loading product:', error);
    }
}

function showQuickViewModal(product) {
    // Create modal HTML
    const modalHtml = `
        <div class="modal" id="quickViewModal">
            <div class="modal-overlay" onclick="closeQuickView()"></div>
            <div class="modal-content">
                <button class="modal-close" onclick="closeQuickView()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="quick-view-content">
                    <div class="quick-view-images">
                        <img src="${product.image_url || '/assets/images/placeholder.jpg'}" alt="${product.product_name}">
                    </div>
                    <div class="quick-view-info">
                        <h2>${product.product_name}</h2>
                        <div class="quick-view-price">
                            ${product.sale_price ? `
                                <span class="price-old">€${formatPrice(product.base_price)}</span>
                                <span class="price-current">€${formatPrice(product.sale_price)}</span>
                            ` : `
                                <span class="price-current">€${formatPrice(product.base_price)}</span>
                            `}
                        </div>
                        <div class="quick-view-description">
                            ${product.description || ''}
                        </div>
                        <div class="quick-view-actions">
                            <button class="btn btn-primary" onclick="addToCart(${product.product_id})">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <a href="/product/${product.product_slug}" class="btn btn-outline">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal with animation
    setTimeout(() => {
        document.getElementById('quickViewModal').classList.add('active');
    }, 10);
}

function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// ========================================
// Utility Functions
// ========================================
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ========================================
// Mobile Menu Overlay Styles
// ========================================
const mobileMenuStyles = `
    .mobile-menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1002;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .mobile-menu-overlay.active {
        opacity: 1;
        visibility: visible;
    }
`;

// Add mobile menu styles to page
const styleSheet = document.createElement('style');
styleSheet.textContent = mobileMenuStyles;
document.head.appendChild(styleSheet);

// ========================================
// Make functions globally available
// ========================================
window.addToCart = addToCart;
window.removeFromCart = removeFromCart;
window.updateCartQuantity = updateCartQuantity;
window.addToWishlist = addToWishlist;
window.removeFromWishlist = removeFromWishlist;
window.closeQuickView = closeQuickView;
window.openQuickView = openQuickView;
