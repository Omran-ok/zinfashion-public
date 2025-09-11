/**
 * ZIN Fashion - Cart Operations (FIXED)
 * Location: /public_html/dev_staging/assets/js/cart.js
 * Fixed: Empty cart showing €4.99 and centered empty cart message
 */

class ShoppingCart {
    constructor() {
        this.cartItems = [];
        this.cartTotal = 0;
        this.cartCount = 0;
        this.freeShippingThreshold = 50;
        this.shippingCost = 4.99;
        this.taxRate = 0.19; // 19% German VAT
        
        this.init();
    }
    
    init() {
        // Load cart on page load
        this.loadCart();
        
        // Initialize event listeners
        this.initEventListeners();
        
        // Update cart display
        this.updateCartDisplay();
    }
    
    initEventListeners() {
        // Product page add to cart
        const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const productId = btn.dataset.productId;
                const variantId = btn.dataset.variantId || null;
                const quantity = parseInt(btn.dataset.quantity || 1);
                this.addItem(productId, variantId, quantity);
            });
        });
        
        // Quantity updates
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cart-quantity-input')) {
                const itemId = e.target.dataset.itemId;
                const quantity = parseInt(e.target.value);
                this.updateQuantity(itemId, quantity);
            }
        });
        
        // Remove item buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-cart-item')) {
                e.preventDefault();
                const itemId = e.target.dataset.itemId;
                this.removeItem(itemId);
            }
        });
        
        // Apply coupon
        const couponForm = document.getElementById('couponForm');
        if (couponForm) {
            couponForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const couponCode = document.getElementById('couponCode').value;
                this.applyCoupon(couponCode);
            });
        }
    }
    
    async loadCart() {
        try {
            const response = await fetch('/api.php?action=cart');
            const data = await response.json();
            
            if (data.success !== false) {
                this.cartItems = data.items || [];
                this.cartTotal = data.total || 0;
                this.cartCount = data.items ? data.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
                
                this.updateCartDisplay();
            }
        } catch (error) {
            console.error('Error loading cart:', error);
        }
    }
    
    async addItem(productId, variantId = null, quantity = 1) {
        // Show loading state
        this.showLoading();
        
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
                // Reload cart
                await this.loadCart();
                
                // Show success message
                this.showNotification('Product added to cart!', 'success');
                
                // Open cart sidebar/modal
                this.openCartSidebar();
                
                // Update mini cart icon animation
                this.animateCartIcon();
            } else {
                this.showNotification(data.message || 'Failed to add to cart', 'error');
            }
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    async updateQuantity(itemId, quantity) {
        if (quantity < 1) {
            return this.removeItem(itemId);
        }
        
        this.showLoading();
        
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
                await this.loadCart();
                this.showNotification('Cart updated', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update cart', 'error');
            }
        } catch (error) {
            console.error('Error updating quantity:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    async removeItem(itemId) {
        if (!confirm('Remove this item from cart?')) {
            return;
        }
        
        this.showLoading();
        
        try {
            const response = await fetch(`/api.php?action=cart&item_id=${itemId}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCart();
                this.showNotification('Item removed from cart', 'info');
                
                // Animate removal
                const itemElement = document.querySelector(`[data-cart-item-id="${itemId}"]`);
                if (itemElement) {
                    itemElement.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        itemElement.remove();
                    }, 300);
                }
            }
        } catch (error) {
            console.error('Error removing item:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    async applyCoupon(code) {
        if (!code) {
            this.showNotification('Please enter a coupon code', 'warning');
            return;
        }
        
        this.showLoading();
        
        try {
            const response = await fetch('/api.php?action=applyCoupon', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    coupon_code: code
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                await this.loadCart();
                this.showNotification(`Coupon applied! You saved €${data.discount}`, 'success');
            } else {
                this.showNotification(data.message || 'Invalid coupon code', 'error');
            }
        } catch (error) {
            console.error('Error applying coupon:', error);
            this.showNotification('An error occurred', 'error');
        } finally {
            this.hideLoading();
        }
    }
    
    updateCartDisplay() {
        // Update cart count badge
        this.updateCartCount();
        
        // Update cart sidebar
        this.updateCartSidebar();
        
        // Update cart page if on cart page
        if (document.getElementById('cartPage')) {
            this.updateCartPage();
        }
        
        // Update checkout page if on checkout
        if (document.getElementById('checkoutPage')) {
            this.updateCheckoutSummary();
        }
    }
    
    updateCartCount() {
        const cartCountElements = document.querySelectorAll('.cart-count, #cartCount');
        cartCountElements.forEach(element => {
            if (this.cartCount > 0) {
                element.textContent = this.cartCount;
                element.style.display = 'flex';
            } else {
                element.style.display = 'none';
            }
        });
    }
    
    updateCartSidebar() {
        const cartContent = document.getElementById('cartContent');
        const cartTotalAmount = document.getElementById('cartTotalAmount');
        
        if (!cartContent) return;
        
        if (this.cartItems.length > 0) {
            let html = '<div class="cart-items">';
            
            this.cartItems.forEach(item => {
                const itemTotal = (item.price || item.base_price) * item.quantity;
                html += `
                    <div class="cart-item" data-cart-item-id="${item.cart_item_id}">
                        <div class="cart-item-image">
                            <img src="${item.image_url || '/assets/images/placeholder.jpg'}" 
                                 alt="${item.product_name}">
                        </div>
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.product_name}</div>
                            <div class="cart-item-meta">
                                ${item.size ? `Size: ${item.size}` : ''}
                                ${item.color ? ` | Color: ${item.color}` : ''}
                            </div>
                            <div class="cart-item-price">
                                <div class="quantity-controls">
                                    <button class="qty-btn qty-minus" onclick="cart.updateQuantity(${item.cart_item_id}, ${item.quantity - 1})">-</button>
                                    <input type="number" class="qty-input" value="${item.quantity}" 
                                           min="1" max="99" 
                                           onchange="cart.updateQuantity(${item.cart_item_id}, this.value)">
                                    <button class="qty-btn qty-plus" onclick="cart.updateQuantity(${item.cart_item_id}, ${item.quantity + 1})">+</button>
                                </div>
                                <span class="price">€${this.formatPrice(itemTotal)}</span>
                            </div>
                        </div>
                        <button class="cart-item-remove" onclick="cart.removeItem(${item.cart_item_id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Add shipping notice
            const subtotal = this.calculateSubtotal();
            if (subtotal < this.freeShippingThreshold) {
                const remaining = this.freeShippingThreshold - subtotal;
                html += `
                    <div class="free-shipping-notice">
                        <i class="fas fa-truck"></i>
                        Add €${this.formatPrice(remaining)} more for free shipping!
                        <div class="shipping-progress">
                            <div class="shipping-progress-bar" style="width: ${(subtotal / this.freeShippingThreshold) * 100}%"></div>
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div class="free-shipping-notice success">
                        <i class="fas fa-check-circle"></i>
                        You qualify for free shipping!
                    </div>
                `;
            }
            
            cartContent.innerHTML = html;
        } else {
            // FIXED: Centered empty cart display
            cartContent.innerHTML = `
                <div class="cart-empty-centered">
                    <i class="fas fa-shopping-bag"></i>
                    <p>Your cart is empty</p>
                    <a href="/shop" class="btn btn-primary btn-small">Start Shopping</a>
                </div>
            `;
        }
        
        // Update total - FIXED: Show 0 when cart is empty
        if (cartTotalAmount) {
            const total = this.calculateTotal();
            cartTotalAmount.textContent = '€' + this.formatPrice(total);
        }
    }
    
    updateCartPage() {
        const cartTable = document.getElementById('cartTable');
        const cartSummary = document.getElementById('cartSummary');
        
        if (!cartTable) return;
        
        if (this.cartItems.length > 0) {
            // Update cart table
            let tableHtml = `
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            this.cartItems.forEach(item => {
                const itemTotal = (item.price || item.base_price) * item.quantity;
                tableHtml += `
                    <tr data-cart-item-id="${item.cart_item_id}">
                        <td class="cart-product">
                            <img src="${item.image_url || '/assets/images/placeholder.jpg'}" 
                                 alt="${item.product_name}">
                            <div>
                                <h4>${item.product_name}</h4>
                                <p>${item.size ? `Size: ${item.size}` : ''} ${item.color ? `| Color: ${item.color}` : ''}</p>
                            </div>
                        </td>
                        <td class="cart-price">€${this.formatPrice(item.price || item.base_price)}</td>
                        <td class="cart-quantity">
                            <div class="quantity-controls">
                                <button class="qty-btn qty-minus" onclick="cart.updateQuantity(${item.cart_item_id}, ${item.quantity - 1})">-</button>
                                <input type="number" class="qty-input" value="${item.quantity}" 
                                       min="1" max="99" 
                                       onchange="cart.updateQuantity(${item.cart_item_id}, this.value)">
                                <button class="qty-btn qty-plus" onclick="cart.updateQuantity(${item.cart_item_id}, ${item.quantity + 1})">+</button>
                            </div>
                        </td>
                        <td class="cart-total">€${this.formatPrice(itemTotal)}</td>
                        <td class="cart-remove">
                            <button class="remove-btn" onclick="cart.removeItem(${item.cart_item_id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tableHtml += `
                    </tbody>
                </table>
            `;
            
            cartTable.innerHTML = tableHtml;
            
            // Update summary
            if (cartSummary) {
                const subtotal = this.calculateSubtotal();
                const shipping = this.calculateShipping(subtotal);
                const tax = this.calculateTax(subtotal);
                const total = subtotal + shipping + tax;
                
                cartSummary.innerHTML = `
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>€${this.formatPrice(subtotal)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>${shipping === 0 ? 'FREE' : '€' + this.formatPrice(shipping)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (19%):</span>
                        <span>€${this.formatPrice(tax)}</span>
                    </div>
                    <div class="summary-row total">
                        <strong>Total:</strong>
                        <strong>€${this.formatPrice(total)}</strong>
                    </div>
                    <a href="/checkout" class="btn btn-primary btn-block">Proceed to Checkout</a>
                `;
            }
        } else {
            cartTable.innerHTML = `
                <div class="cart-empty-page">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="/shop" class="btn btn-primary">Continue Shopping</a>
                </div>
            `;
        }
    }
    
    calculateSubtotal() {
        return this.cartItems.reduce((sum, item) => {
            return sum + ((item.price || item.base_price) * item.quantity);
        }, 0);
    }
    
    calculateShipping(subtotal) {
        // FIXED: Return 0 shipping if cart is empty
        if (this.cartItems.length === 0) {
            return 0;
        }
        return subtotal >= this.freeShippingThreshold ? 0 : this.shippingCost;
    }
    
    calculateTax(subtotal) {
        return subtotal * this.taxRate;
    }
    
    calculateTotal() {
        // FIXED: Return 0 if cart is empty
        if (this.cartItems.length === 0) {
            return 0;
        }
        
        const subtotal = this.calculateSubtotal();
        const shipping = this.calculateShipping(subtotal);
        const tax = this.calculateTax(subtotal);
        return subtotal + shipping + tax;
    }
    
    formatPrice(price) {
        return parseFloat(price).toFixed(2).replace('.', ',');
    }
    
    openCartSidebar() {
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');
        
        if (cartSidebar) {
            cartSidebar.classList.add('active');
            if (cartOverlay) {
                cartOverlay.classList.add('active');
            }
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeCartSidebar() {
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');
        
        if (cartSidebar) {
            cartSidebar.classList.remove('active');
            if (cartOverlay) {
                cartOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    }
    
    animateCartIcon() {
        const cartIcon = document.querySelector('.header-actions .action-link[href="/cart"] i');
        if (cartIcon) {
            cartIcon.style.animation = 'bounce 0.5s ease';
            setTimeout(() => {
                cartIcon.style.animation = '';
            }, 500);
        }
    }
    
    showLoading() {
        // Create or show loading overlay
        let loader = document.getElementById('cartLoader');
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'cartLoader';
            loader.className = 'cart-loader';
            loader.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loader);
        }
        loader.classList.add('active');
    }
    
    hideLoading() {
        const loader = document.getElementById('cartLoader');
        if (loader) {
            loader.classList.remove('active');
        }
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `cart-notification notification-${type}`;
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
}

// Initialize cart when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.cart = new ShoppingCart();
});

// Add CSS for cart-specific styles
const cartStyles = `
    .cart-loader {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .cart-loader.active {
        opacity: 1;
        visibility: visible;
    }
    
    .cart-loader .spinner {
        width: 50px;
        height: 50px;
        border: 3px solid var(--border-color);
        border-top-color: var(--gold-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    /* FIXED: More specific selectors for empty cart centering */
    #cartSidebar .cart-empty-centered,
    .cart-sidebar .cart-empty-centered {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 300px !important;
        text-align: center !important;
        padding: 40px 20px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    
    #cartSidebar .cart-empty-centered i,
    .cart-sidebar .cart-empty-centered i {
        font-size: 64px !important;
        color: var(--text-muted) !important;
        margin-bottom: 20px !important;
        opacity: 0.3 !important;
        display: block !important;
    }
    
    #cartSidebar .cart-empty-centered p,
    .cart-sidebar .cart-empty-centered p {
        color: var(--text-secondary) !important;
        font-size: 16px !important;
        margin-bottom: 25px !important;
        display: block !important;
    }
    
    #cartSidebar .cart-empty-centered .btn,
    .cart-sidebar .cart-empty-centered .btn {
        padding: 12px 30px !important;
        display: inline-block !important;
    }
    
    /* Ensure cart sidebar content takes full height for centering */
    #cartContent {
        min-height: 300px;
        display: flex;
        flex-direction: column;
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    @keyframes slideOut {
        to {
            transform: translateX(-100%);
            opacity: 0;
        }
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .qty-btn {
        width: 30px;
        height: 30px;
        border: 1px solid var(--border-color);
        background: var(--bg-secondary);
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .qty-btn:hover {
        background: var(--gold-primary);
        color: var(--bg-primary);
    }
    
    .qty-input {
        width: 50px;
        height: 30px;
        text-align: center;
        border: 1px solid var(--border-color);
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }
    
    .shipping-progress {
        width: 100%;
        height: 4px;
        background: var(--bg-tertiary);
        border-radius: 2px;
        margin-top: 10px;
        overflow: hidden;
    }
    
    .shipping-progress-bar {
        height: 100%;
        background: var(--gradient-gold);
        transition: width 0.3s ease;
    }
    
    .free-shipping-notice.success {
        color: #2ecc71;
        border-color: #2ecc71;
    }
    
    .cart-notification {
        position: fixed;
        top: 100px;
        right: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-left: 4px solid;
        padding: 15px 20px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 300px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 9999;
        box-shadow: var(--shadow-lg);
    }
    
    .cart-notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left-color: #2ecc71;
        color: #2ecc71;
    }
    
    .notification-error {
        border-left-color: #e74c3c;
        color: #e74c3c;
    }
    
    .notification-warning {
        border-left-color: #f39c12;
        color: #f39c12;
    }
    
    .notification-info {
        border-left-color: var(--gold-primary);
        color: var(--gold-primary);
    }
`;

// Add cart styles to page
const cartStyleElement = document.createElement('style');
cartStyleElement.textContent = cartStyles;
document.head.appendChild(cartStyleElement);
