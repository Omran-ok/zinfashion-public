/**
 * ZIN Fashion - Cart Management
 * Location: /public_html/dev_staging/assets/js/cart.js
 */

// Cart state
let cart = {
    items: [],
    subtotal: 0,
    shipping: 0,
    total: 0
};

// Wishlist state
let wishlist = [];

/**
 * Initialize Cart
 */
document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    loadWishlist();
    initializeCartEvents();
});

/**
 * Initialize Cart Events
 */
function initializeCartEvents() {
    // Cart icon click
    const cartIcon = document.getElementById('cartIcon');
    if (cartIcon) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            openCart();
        });
    }
    
    // Cart overlay click to close
    const cartOverlay = document.getElementById('cartOverlay');
    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }
    
    // Cart close button
    const cartClose = document.querySelector('.cart-close');
    if (cartClose) {
        cartClose.addEventListener('click', closeCart);
    }
}

/**
 * Load Cart from Server
 */
async function loadCart() {
    try {
        const response = await fetch('/api.php?api=cart');
        if (response.ok) {
            const data = await response.json();
            cart = data;
            updateCartUI();
            updateCartCount();
        }
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

/**
 * Load Wishlist from Server
 */
async function loadWishlist() {
    try {
        const response = await fetch('/api.php?api=wishlist');
        if (response.ok) {
            const data = await response.json();
            wishlist = data.items || [];
            updateWishlistCount();
        }
    } catch (error) {
        // User might not be logged in
        console.log('Wishlist requires login');
    }
}

/**
 * Add to Cart
 */
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch('/api.php?api=cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        });
        
        if (response.ok) {
            await loadCart();
            openCart();
            showCartNotification('Product added to cart!');
            
            // Animate add to cart button
            const btn = event.target;
            if (btn && btn.classList.contains('add-to-cart')) {
                animateCartButton(btn);
            }
        } else {
            const error = await response.json();
            showCartNotification(error.error || 'Failed to add to cart', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showCartNotification('Failed to add to cart', 'error');
    }
}

/**
 * Update Cart Item Quantity
 */
async function updateCartItem(itemId, quantity) {
    if (quantity < 1) {
        removeFromCart(itemId);
        return;
    }
    
    try {
        const response = await fetch('/api.php?api=cart', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                item_id: itemId,
                quantity: quantity
            })
        });
        
        if (response.ok) {
            await loadCart();
        }
    } catch (error) {
        console.error('Error updating cart:', error);
    }
}

/**
 * Remove from Cart
 */
async function removeFromCart(itemId) {
    try {
        const response = await fetch(`/api.php?api=cart&item_id=${itemId}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            await loadCart();
            showCartNotification('Item removed from cart');
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
    }
}

/**
 * Toggle Wishlist
 */
async function toggleWishlist(productId, button) {
    const isInWishlist = button.classList.contains('active');
    
    try {
        const url = isInWishlist 
            ? `/api.php?api=wishlist&product_id=${productId}`
            : '/api.php?api=wishlist';
            
        const response = await fetch(url, {
            method: isInWishlist ? 'DELETE' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: isInWishlist ? null : JSON.stringify({
                product_id: productId
            })
        });
        
        if (response.ok) {
            button.classList.toggle('active');
            await loadWishlist();
            showCartNotification(
                isInWishlist ? 'Removed from wishlist' : 'Added to wishlist',
                'success'
            );
        } else if (response.status === 401) {
            showCartNotification('Please login to use wishlist', 'warning');
            // Optionally redirect to login
        }
    } catch (error) {
        console.error('Error toggling wishlist:', error);
    }
}

/**
 * Open Cart Modal
 */
function openCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        renderCart();
        cartModal.classList.add('active');
        cartOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close Cart Modal
 */
function closeCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.getElementById('cartOverlay');
    
    if (cartModal && cartOverlay) {
        cartModal.classList.remove('active');
        cartOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Render Cart Content
 */
function renderCart() {
    const cartModal = document.getElementById('cartModal');
    if (!cartModal) return;
    
    const lang = localStorage.getItem('lang') || 'de';
    const t = (key) => {
        if (typeof translations !== 'undefined' && translations[lang] && translations[lang][key]) {
            return translations[lang][key];
        }
        return key;
    };
    
    if (cart.items && cart.items.length > 0) {
        cartModal.innerHTML = `
            <div class="cart-header">
                <h2 class="cart-title">${t('shopping-cart')}</h2>
                <button class="cart-close" onclick="closeCart()">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            
            <div class="cart-content">
                <div class="cart-items">
                    ${cart.items.map(item => renderCartItem(item)).join('')}
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="cart-subtotal">
                    <span>${t('subtotal')}</span>
                    <span class="cart-subtotal-price">€${cart.subtotal.toFixed(2)}</span>
                </div>
                <div class="cart-shipping">
                    <span>${t('shipping-cost')}</span>
                    <span class="cart-shipping-price">
                        ${cart.shipping === 0 ? t('free') : `€${cart.shipping.toFixed(2)}`}
                    </span>
                </div>
                <div class="cart-total">
                    <span>${t('total')}</span>
                    <span class="cart-total-price">€${cart.total.toFixed(2)}</span>
                </div>
                <button class="cart-checkout-btn" onclick="goToCheckout()">
                    ${t('checkout')}
                </button>
                <a href="/cart" class="cart-view-link">${t('view-cart')}</a>
            </div>
        `;
    } else {
        cartModal.innerHTML = `
            <div class="cart-header">
                <h2 class="cart-title">${t('shopping-cart')}</h2>
                <button class="cart-close" onclick="closeCart()">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                    </svg>
                </button>
            </div>
            
            <div class="cart-content">
                <div class="cart-empty">
                    <svg class="cart-empty-icon" viewBox="0 0 24 24">
                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                    <p class="cart-empty-text">${t('cart-empty')}</p>
                    <button class="btn btn-primary" onclick="closeCart()">
                        ${t('continue-shopping')}
                    </button>
                </div>
            </div>
        `;
    }
}

/**
 * Render Cart Item
 */
function renderCartItem(item) {
    const t = (key) => {
        const lang = localStorage.getItem('lang') || 'de';
        if (typeof translations !== 'undefined' && translations[lang] && translations[lang][key]) {
            return translations[lang][key];
        }
        return key;
    };
    
    return `
        <div class="cart-item" data-item-id="${item.cart_item_id}">
            <div class="cart-item-image">
                <img src="${item.image_url || '/assets/images/placeholder.jpg'}" alt="${item.product_name}">
            </div>
            <div class="cart-item-details">
                <h4 class="cart-item-name">${item.product_name}</h4>
                <div class="cart-item-meta">
                    ${item.size ? `<span>${t('size')}: ${item.size}</span>` : ''}
                    ${item.color ? `<span>${t('color')}: ${item.color}</span>` : ''}
                </div>
                <div class="cart-item-price">€${(item.sale_price || item.base_price).toFixed(2)}</div>
                <div class="cart-item-quantity">
                    <button class="quantity-btn" onclick="updateCartItem(${item.cart_item_id}, ${item.quantity - 1})">-</button>
                    <input type="number" class="quantity-input" value="${item.quantity}" readonly>
                    <button class="quantity-btn" onclick="updateCartItem(${item.cart_item_id}, ${item.quantity + 1})">+</button>
                </div>
            </div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.cart_item_id})" title="${t('remove')}">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
    `;
}

/**
 * Update Cart UI
 */
function updateCartUI() {
    updateCartCount();
    if (document.getElementById('cartModal') && document.getElementById('cartModal').classList.contains('active')) {
        renderCart();
    }
}

/**
 * Update Cart Count Badge
 */
function updateCartCount() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        const totalItems = cart.items ? cart.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }
}

/**
 * Update Wishlist Count Badge
 */
function updateWishlistCount() {
    const wishlistCount = document.querySelector('.wishlist-count');
    if (wishlistCount) {
        wishlistCount.textContent = wishlist.length;
        wishlistCount.style.display = wishlist.length > 0 ? 'flex' : 'none';
    }
}

/**
 * Animate Cart Button
 */
function animateCartButton(button) {
    const originalText = button.textContent;
    button.classList.add('added');
    button.textContent = '✓ Added';
    
    setTimeout(() => {
        button.classList.remove('added');
        button.textContent = originalText;
    }, 2000);
}

/**
 * Show Cart Notification
 */
function showCartNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `cart-notification cart-notification-${type}`;
    notification.innerHTML = `
        <div class="cart-notification-content">
            ${type === 'success' ? '<svg class="icon"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>' : ''}
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Go to Checkout
 */
function goToCheckout() {
    if (cart.items && cart.items.length > 0) {
        window.location.href = '/checkout';
    }
}

/**
 * Open Quick View Modal
 */
async function openQuickView(productId) {
    try {
        const response = await fetch(`/api.php?api=product&id=${productId}`);
        if (response.ok) {
            const product = await response.json();
            renderQuickView(product);
        }
    } catch (error) {
        console.error('Error loading product:', error);
    }
}

/**
 * Render Quick View Modal
 */
function renderQuickView(product) {
    const modal = document.getElementById('quickViewModal');
    if (!modal) return;
    
    const t = (key) => {
        const lang = localStorage.getItem('lang') || 'de';
        if (typeof translations !== 'undefined' && translations[lang] && translations[lang][key]) {
            return translations[lang][key];
        }
        return key;
    };
    
    modal.innerHTML = `
        <div class="modal-content">
            <button class="modal-close" onclick="closeQuickView()">
                <svg class="icon" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
            
            <div class="quick-view-content">
                <div class="quick-view-images">
                    <img src="${product.image_url || '/assets/images/placeholder.jpg'}" alt="${product.product_name}">
                </div>
                
                <div class="quick-view-info">
                    <h2 class="quick-view-title">${product.product_name}</h2>
                    <div class="quick-view-price">
                        ${product.sale_price ? `
                            <span class="price">€${product.sale_price.toFixed(2)}</span>
                            <span class="old-price">€${product.base_price.toFixed(2)}</span>
                        ` : `
                            <span class="price">€${product.base_price.toFixed(2)}</span>
                        `}
                    </div>
                    
                    <p class="quick-view-description">${product.short_description || product.description}</p>
                    
                    <div class="quick-view-options">
                        ${product.variants && product.variants.length > 0 ? `
                            <div class="size-options">
                                <label>${t('size')}</label>
                                <select class="size-select">
                                    ${product.variants.map(v => `
                                        <option value="${v.variant_id}">${v.size}</option>
                                    `).join('')}
                                </select>
                            </div>
                        ` : ''}
                        
                        <div class="quantity-options">
                            <label>${t('quantity')}</label>
                            <div class="quantity-selector">
                                <button class="quantity-btn" onclick="decreaseQuickViewQuantity()">-</button>
                                <input type="number" class="quantity-input" id="quickViewQuantity" value="1" min="1">
                                <button class="quantity-btn" onclick="increaseQuickViewQuantity()">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="quick-view-actions">
                        <button class="btn btn-primary" onclick="addToCartFromQuickView(${product.product_id})">
                            ${t('add-to-cart')}
                        </button>
                        <a href="/product/${product.product_id}" class="btn btn-outline">
                            ${t('view-details')}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.add('active');
}

/**
 * Close Quick View Modal
 */
function closeQuickView() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

/**
 * Quick View Quantity Controls
 */
function increaseQuickViewQuantity() {
    const input = document.getElementById('quickViewQuantity');
    if (input) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQuickViewQuantity() {
    const input = document.getElementById('quickViewQuantity');
    if (input && parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

/**
 * Add to Cart from Quick View
 */
function addToCartFromQuickView(productId) {
    const quantity = document.getElementById('quickViewQuantity')?.value || 1;
    addToCart(productId, parseInt(quantity));
    closeQuickView();
}

// Export functions for global use
window.addToCart = addToCart;
window.updateCartItem = updateCartItem;
window.removeFromCart = removeFromCart;
window.toggleWishlist = toggleWishlist;
window.openCart = openCart;
window.closeCart = closeCart;
window.openQuickView = openQuickView;
window.closeQuickView = closeQuickView;
window.goToCheckout = goToCheckout;
window.increaseQuickViewQuantity = increaseQuickViewQuantity;
window.decreaseQuickViewQuantity = decreaseQuickViewQuantity;
window.addToCartFromQuickView = addToCartFromQuickView;
window.showCartNotification = showCartNotification;
