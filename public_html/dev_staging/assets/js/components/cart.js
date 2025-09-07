/**
 * FILE: /assets/js/components/cart.js
 * Shopping cart functionality
 */

window.ZIN = window.ZIN || {};

ZIN.cart = {
    items: [],
    isOpen: false,
    modal: null,
    overlay: null,
    
    /**
     * Initialize cart
     */
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.loadCart();
        ZIN.utils.debug('Cart component initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.modal = document.getElementById('cartModal');
        this.overlay = document.getElementById('cartOverlay');
        
        // Create cart modal if it doesn't exist
        if (!this.modal) {
            this.createCartModal();
        }
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Cart icon click
        const cartIcons = document.querySelectorAll('[data-cart-trigger], #cartIcon, .cart-icon');
        cartIcons.forEach(icon => {
            icon.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        });
        
        // Close cart
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.close());
        }
        
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart, [data-add-to-cart]')) {
                e.preventDefault();
                const button = e.target.closest('.add-to-cart, [data-add-to-cart]');
                const productId = button.dataset.productId || button.closest('[data-product-id]')?.dataset.productId;
                const quantity = parseInt(button.dataset.quantity || 1);
                const variantId = button.dataset.variantId || null;
                
                if (productId) {
                    this.addItem(productId, quantity, variantId);
                }
            }
        });
        
        // Cart item actions
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                // Remove item
                if (e.target.matches('.cart-item-remove, [data-remove-item]')) {
                    const itemId = e.target.closest('[data-cart-item]')?.dataset.cartItem;
                    if (itemId) this.removeItem(itemId);
                }
                
                // Update quantity
                if (e.target.matches('.quantity-btn')) {
                    const itemId = e.target.closest('[data-cart-item]')?.dataset.cartItem;
                    const action = e.target.dataset.action;
                    if (itemId && action) {
                        const currentQty = this.getItemQuantity(itemId);
                        const newQty = action === 'increase' ? currentQty + 1 : Math.max(1, currentQty - 1);
                        this.updateQuantity(itemId, newQty);
                    }
                }
            });
        }
    },
    
    /**
     * Create cart modal
     */
    createCartModal: function() {
        // Create overlay
        this.overlay = document.createElement('div');
        this.overlay.id = 'cartOverlay';
        this.overlay.className = 'cart-overlay';
        document.body.appendChild(this.overlay);
        
        // Create modal
        this.modal = document.createElement('div');
        this.modal.id = 'cartModal';
        this.modal.className = 'cart-modal';
        this.modal.innerHTML = `
            <div class="cart-header">
                <h2 class="cart-title">Warenkorb</h2>
                <button class="cart-close" onclick="ZIN.cart.close()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="cart-content">
                <div class="cart-items" id="cartItems">
                    <!-- Cart items will be inserted here -->
                </div>
            </div>
            <div class="cart-footer">
                <div class="cart-summary">
                    <div class="cart-subtotal">
                        <span>Zwischensumme</span>
                        <span class="cart-subtotal-amount">€0,00</span>
                    </div>
                    <div class="cart-shipping">
                        <span>Versand</span>
                        <span class="cart-shipping-amount">€4,99</span>
                    </div>
                    <div class="cart-total">
                        <span>Gesamt</span>
                        <span class="cart-total-amount">€0,00</span>
                    </div>
                </div>
                <div class="cart-actions">
                    <button class="btn btn-primary btn-block" onclick="ZIN.cart.checkout()">
                        Zur Kasse
                    </button>
                    <a href="/cart" class="btn btn-secondary btn-block">
                        Warenkorb anzeigen
                    </a>
                </div>
            </div>
        `;
        document.body.appendChild(this.modal);
    },
    
    /**
     * Load cart from API or localStorage
     */
    loadCart: async function() {
        try {
            const response = await ZIN.api.get('/cart');
            if (response.success) {
                this.items = response.items || [];
                this.updateDisplay();
                this.updateCount();
            }
        } catch (error) {
            // Fallback to localStorage
            const savedCart = ZIN.storage.get('cart');
            if (savedCart) {
                this.items = savedCart;
                this.updateDisplay();
                this.updateCount();
            }
        }
    },
    
    /**
     * Save cart
     */
    saveCart: async function() {
        // Save to localStorage immediately
        ZIN.storage.set('cart', this.items);
        
        // Sync with server
        try {
            await ZIN.api.post('/cart/sync', { items: this.items });
        } catch (error) {
            ZIN.utils.debug('Failed to sync cart with server:', error);
        }
    },
    
    /**
     * Add item to cart
     */
    addItem: async function(productId, quantity = 1, variantId = null) {
        try {
            // Show loading on button
            const button = document.querySelector(`[data-product-id="${productId}"] .add-to-cart`);
            if (button) ZIN.utils.addLoading(button);
            
            const response = await ZIN.api.post('/cart', {
                product_id: productId,
                variant_id: variantId,
                quantity: quantity
            });
            
            if (response.success) {
                // Update local cart
                const existingItem = this.items.find(item => 
                    item.product_id === productId && item.variant_id === variantId
                );
                
                if (existingItem) {
                    existingItem.quantity += quantity;
                } else {
                    this.items.push(response.item);
                }
                
                this.saveCart();
                this.updateDisplay();
                this.updateCount();
                this.open();
                
                // Show notification
                ZIN.notification.show('Produkt wurde zum Warenkorb hinzugefügt', 'success');
            }
            
            if (button) ZIN.utils.removeLoading(button);
            
        } catch (error) {
            ZIN.utils.debug('Failed to add item to cart:', error);
            ZIN.notification.show('Fehler beim Hinzufügen zum Warenkorb', 'error');
        }
    },
    
    /**
     * Remove item from cart
     */
    removeItem: async function(itemId) {
        try {
            const response = await ZIN.api.delete(`/cart/${itemId}`);
            
            if (response.success) {
                this.items = this.items.filter(item => item.id !== itemId);
                this.saveCart();
                this.updateDisplay();
                this.updateCount();
                
                ZIN.notification.show('Produkt wurde entfernt', 'info');
            }
        } catch (error) {
            ZIN.utils.debug('Failed to remove item:', error);
        }
    },
    
    /**
     * Update item quantity
     */
    updateQuantity: async function(itemId, quantity) {
        if (quantity < 1) {
            return this.removeItem(itemId);
        }
        
        try {
            const response = await ZIN.api.put(`/cart/${itemId}`, { quantity });
            
            if (response.success) {
                const item = this.items.find(i => i.id === itemId);
                if (item) {
                    item.quantity = quantity;
                    this.saveCart();
                    this.updateDisplay();
                    this.updateCount();
                }
            }
        } catch (error) {
            ZIN.utils.debug('Failed to update quantity:', error);
        }
    },
    
    /**
     * Get item quantity
     */
    getItemQuantity: function(itemId) {
        const item = this.items.find(i => i.id === itemId);
        return item ? item.quantity : 0;
    },
    
    /**
     * Update cart display
     */
    updateDisplay: function() {
        const container = document.getElementById('cartItems');
        if (!container) return;
        
        if (this.items.length === 0) {
            container.innerHTML = `
                <div class="cart-empty">
                    <i class="fas fa-shopping-bag"></i>
                    <p>Ihr Warenkorb ist leer</p>
                    <button class="btn btn-primary" onclick="ZIN.cart.close(); window.location.href='/shop'">
                        Jetzt einkaufen
                    </button>
                </div>
            `;
            
            // Hide footer when empty
            const footer = this.modal.querySelector('.cart-footer');
            if (footer) footer.style.display = 'none';
        } else {
            container.innerHTML = this.items.map(item => this.renderCartItem(item)).join('');
            
            // Show footer and update totals
            const footer = this.modal.querySelector('.cart-footer');
            if (footer) {
                footer.style.display = 'block';
                this.updateTotals();
            }
        }
    },
    
    /**
     * Render cart item
     */
    renderCartItem: function(item) {
        const price = item.sale_price || item.price;
        const subtotal = price * item.quantity;
        
        return `
            <div class="cart-item" data-cart-item="${item.id}">
                <div class="cart-item-image">
                    <img src="${item.image || '/assets/images/placeholder.jpg'}" alt="${item.name}">
                </div>
                <div class="cart-item-details">
                    <h4 class="cart-item-name">${item.name}</h4>
                    ${item.variant ? `<p class="cart-item-variant">${item.variant}</p>` : ''}
                    <div class="cart-item-price">
                        ${item.sale_price ? `
                            <span class="price-sale">${ZIN.utils.formatPrice(item.sale_price)}</span>
                            <span class="price-original">${ZIN.utils.formatPrice(item.price)}</span>
                        ` : `
                            <span class="price">${ZIN.utils.formatPrice(price)}</span>
                        `}
                    </div>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn" data-action="decrease">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" value="${item.quantity}" min="1" readonly>
                        <button class="quantity-btn" data-action="increase">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="cart-item-actions">
                    <div class="cart-item-total">
                        ${ZIN.utils.formatPrice(subtotal)}
                    </div>
                    <button class="cart-item-remove" title="Entfernen">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    },
    
    /**
     * Update cart totals
     */
    updateTotals: function() {
        const subtotal = this.getSubtotal();
        const shipping = this.getShipping();
        const total = subtotal + shipping;
        
        // Update display
        const subtotalEl = this.modal.querySelector('.cart-subtotal-amount');
        const shippingEl = this.modal.querySelector('.cart-shipping-amount');
        const totalEl = this.modal.querySelector('.cart-total-amount');
        
        if (subtotalEl) subtotalEl.textContent = ZIN.utils.formatPrice(subtotal);
        if (shippingEl) {
            if (shipping === 0) {
                shippingEl.textContent = 'Kostenlos';
                shippingEl.classList.add('free-shipping');
            } else {
                shippingEl.textContent = ZIN.utils.formatPrice(shipping);
                shippingEl.classList.remove('free-shipping');
            }
        }
        if (totalEl) totalEl.textContent = ZIN.utils.formatPrice(total);
        
        // Show free shipping progress
        if (subtotal < ZIN.config.shipping.freeThreshold) {
            const remaining = ZIN.config.shipping.freeThreshold - subtotal;
            this.showFreeShippingProgress(remaining);
        } else {
            this.hideFreeShippingProgress();
        }
    },
    
    /**
     * Get cart subtotal
     */
    getSubtotal: function() {
        return this.items.reduce((total, item) => {
            const price = item.sale_price || item.price;
            return total + (price * item.quantity);
        }, 0);
    },
    
    /**
     * Get shipping cost
     */
    getShipping: function() {
        const subtotal = this.getSubtotal();
        return subtotal >= ZIN.config.shipping.freeThreshold ? 0 : ZIN.config.shipping.standardCost;
    },
    
    /**
     * Show free shipping progress
     */
    showFreeShippingProgress: function(remaining) {
        let progress = this.modal.querySelector('.free-shipping-progress');
        
        if (!progress) {
            progress = document.createElement('div');
            progress.className = 'free-shipping-progress';
            const summary = this.modal.querySelector('.cart-summary');
            if (summary) {
                summary.insertBefore(progress, summary.firstChild);
            }
        }
        
        const percentage = ((ZIN.config.shipping.freeThreshold - remaining) / ZIN.config.shipping.freeThreshold) * 100;
        
        progress.innerHTML = `
            <p>Noch ${ZIN.utils.formatPrice(remaining)} bis zum kostenlosen Versand!</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${percentage}%"></div>
            </div>
        `;
    },
    
    /**
     * Hide free shipping progress
     */
    hideFreeShippingProgress: function() {
        const progress = this.modal.querySelector('.free-shipping-progress');
        if (progress) progress.remove();
    },
    
    /**
     * Update cart count badge
     */
    updateCount: function() {
        const count = this.items.reduce((total, item) => total + item.quantity, 0);
        const badges = document.querySelectorAll('.cart-count');
        
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },
    
    /**
     * Open cart
     */
    open: function() {
        if (!this.modal || !this.overlay) return;
        
        this.modal.classList.add('active');
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
        
        // Refresh display
        this.updateDisplay();
    },
    
    /**
     * Close cart
     */
    close: function() {
        if (!this.modal || !this.overlay) return;
        
        this.modal.classList.remove('active');
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        this.isOpen = false;
    },
    
    /**
     * Toggle cart
     */
    toggle: function() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    },
    
    /**
     * Proceed to checkout
     */
    checkout: function() {
        if (this.items.length === 0) {
            ZIN.notification.show('Ihr Warenkorb ist leer', 'warning');
            return;
        }
        
        window.location.href = '/checkout';
    },
    
    /**
     * Clear cart
     */
    clear: async function() {
        try {
            const response = await ZIN.api.delete('/cart');
            if (response.success) {
                this.items = [];
                this.saveCart();
                this.updateDisplay();
                this.updateCount();
            }
        } catch (error) {
            ZIN.utils.debug('Failed to clear cart:', error);
        }
    }
};
