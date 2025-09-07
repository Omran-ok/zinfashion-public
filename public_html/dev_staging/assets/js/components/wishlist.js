/**
 * FILE: /assets/js/components/wishlist.js
 * Wishlist functionality
 */

window.ZIN = window.ZIN || {};

ZIN.wishlist = {
    items: [],
    
    /**
     * Initialize wishlist
     */
    init: function() {
        this.loadWishlist();
        this.bindEvents();
        ZIN.utils.debug('Wishlist component initialized');
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Wishlist toggle buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-btn, [data-wishlist-toggle]')) {
                e.preventDefault();
                const button = e.target.closest('.wishlist-btn, [data-wishlist-toggle]');
                const productId = button.dataset.productId || button.closest('[data-product-id]')?.dataset.productId;
                
                if (productId) {
                    this.toggle(productId, button);
                }
            }
        });
        
        // Remove from wishlist page
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-remove, [data-wishlist-remove]')) {
                e.preventDefault();
                const productId = e.target.closest('[data-product-id]')?.dataset.productId;
                
                if (productId) {
                    this.remove(productId);
                }
            }
        });
        
        // Add all to cart
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-add-all, [data-wishlist-add-all]')) {
                e.preventDefault();
                this.addAllToCart();
            }
        });
    },
    
    /**
     * Load wishlist
     */
    loadWishlist: async function() {
        try {
            const response = await ZIN.api.wishlist.get();
            if (response.success) {
                this.items = response.items || [];
                this.updateButtons();
                this.updateCount();
            }
        } catch (error) {
            // Fallback to localStorage
            const saved = ZIN.storage.get('wishlist');
            if (saved) {
                this.items = saved;
                this.updateButtons();
                this.updateCount();
            }
        }
    },
    
    /**
     * Save wishlist
     */
    saveWishlist: function() {
        ZIN.storage.set('wishlist', this.items);
    },
    
    /**
     * Toggle product in wishlist
     */
    toggle: async function(productId, button = null) {
        const isInWishlist = this.has(productId);
        
        try {
            if (button) {
                ZIN.utils.addLoading(button);
            }
            
            const response = await ZIN.api.wishlist.toggle(productId);
            
            if (response.success) {
                if (isInWishlist) {
                    this.items = this.items.filter(id => id !== productId);
                    this.animateRemove(button);
                    ZIN.notification.show('Aus Wunschliste entfernt', 'info');
                } else {
                    this.items.push(productId);
                    this.animateAdd(button);
                    ZIN.notification.show('Zur Wunschliste hinzugefügt', 'success');
                }
                
                this.saveWishlist();
                this.updateButtons();
                this.updateCount();
                this.updatePage();
            }
            
            if (button) {
                ZIN.utils.removeLoading(button);
            }
            
        } catch (error) {
            ZIN.utils.debug('Failed to toggle wishlist:', error);
            ZIN.notification.show('Fehler beim Aktualisieren der Wunschliste', 'error');
            
            if (button) {
                ZIN.utils.removeLoading(button);
            }
        }
    },
    
    /**
     * Add to wishlist
     */
    add: async function(productId) {
        if (this.has(productId)) {
            ZIN.notification.show('Bereits in der Wunschliste', 'info');
            return;
        }
        
        try {
            const response = await ZIN.api.wishlist.add(productId);
            
            if (response.success) {
                this.items.push(productId);
                this.saveWishlist();
                this.updateButtons();
                this.updateCount();
                ZIN.notification.show('Zur Wunschliste hinzugefügt', 'success');
            }
        } catch (error) {
            ZIN.utils.debug('Failed to add to wishlist:', error);
            ZIN.notification.show('Fehler beim Hinzufügen zur Wunschliste', 'error');
        }
    },
    
    /**
     * Remove from wishlist
     */
    remove: async function(productId) {
        if (!this.has(productId)) return;
        
        try {
            const response = await ZIN.api.wishlist.remove(productId);
            
            if (response.success) {
                this.items = this.items.filter(id => id !== productId);
                this.saveWishlist();
                this.updateButtons();
                this.updateCount();
                this.updatePage();
                ZIN.notification.show('Aus Wunschliste entfernt', 'info');
            }
        } catch (error) {
            ZIN.utils.debug('Failed to remove from wishlist:', error);
            ZIN.notification.show('Fehler beim Entfernen aus Wunschliste', 'error');
        }
    },
    
    /**
     * Check if product is in wishlist
     */
    has: function(productId) {
        return this.items.includes(productId.toString());
    },
    
    /**
     * Clear wishlist
     */
    clear: async function() {
        if (!confirm('Möchten Sie wirklich alle Artikel aus der Wunschliste entfernen?')) {
            return;
        }
        
        try {
            // Remove all items via API
            for (const productId of this.items) {
                await ZIN.api.wishlist.remove(productId);
            }
            
            this.items = [];
            this.saveWishlist();
            this.updateButtons();
            this.updateCount();
            this.updatePage();
            
            ZIN.notification.show('Wunschliste geleert', 'success');
        } catch (error) {
            ZIN.utils.debug('Failed to clear wishlist:', error);
            ZIN.notification.show('Fehler beim Leeren der Wunschliste', 'error');
        }
    },
    
    /**
     * Add all wishlist items to cart
     */
    addAllToCart: async function() {
        if (this.items.length === 0) {
            ZIN.notification.show('Ihre Wunschliste ist leer', 'warning');
            return;
        }
        
        let addedCount = 0;
        let failedCount = 0;
        
        for (const productId of this.items) {
            try {
                await ZIN.cart.addItem(productId, 1);
                addedCount++;
            } catch (error) {
                failedCount++;
            }
        }
        
        if (addedCount > 0) {
            ZIN.notification.show(`${addedCount} Artikel zum Warenkorb hinzugefügt`, 'success');
            
            if (confirm('Möchten Sie die hinzugefügten Artikel aus der Wunschliste entfernen?')) {
                this.clear();
            }
        }
        
        if (failedCount > 0) {
            ZIN.notification.show(`${failedCount} Artikel konnten nicht hinzugefügt werden`, 'warning');
        }
    },
    
    /**
     * Update wishlist buttons
     */
    updateButtons: function() {
        const buttons = document.querySelectorAll('.wishlist-btn, [data-wishlist-toggle]');
        
        buttons.forEach(button => {
            const productId = button.dataset.productId || button.closest('[data-product-id]')?.dataset.productId;
            
            if (productId) {
                const isActive = this.has(productId);
                button.classList.toggle('active', isActive);
                
                // Update icon
                const icon = button.querySelector('i');
                if (icon) {
                    if (isActive) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                }
                
                // Update tooltip
                button.title = isActive ? 'Aus Wunschliste entfernen' : 'Zur Wunschliste hinzufügen';
            }
        });
    },
    
    /**
     * Update wishlist count badge
     */
    updateCount: function() {
        const badges = document.querySelectorAll('.wishlist-count');
        const count = this.items.length;
        
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        });
    },
    
    /**
     * Update wishlist page
     */
    updatePage: function() {
        const container = document.querySelector('.wishlist-items');
        if (!container) return;
        
        if (this.items.length === 0) {
            container.innerHTML = `
                <div class="wishlist-empty">
                    <i class="fas fa-heart"></i>
                    <h3>Ihre Wunschliste ist leer</h3>
                    <p>Speichern Sie Ihre Lieblingsartikel für später</p>
                    <a href="/shop" class="btn btn-primary">Jetzt einkaufen</a>
                </div>
            `;
        } else {
            // Reload the page to show updated items
            // This assumes server-side rendering
            window.location.reload();
        }
    },
    
    /**
     * Animate add to wishlist
     */
    animateAdd: function(button) {
        if (!button) return;
        
        button.classList.add('wishlist-added');
        
        // Create heart animation
        const heart = document.createElement('span');
        heart.className = 'wishlist-heart-animation';
        heart.innerHTML = '<i class="fas fa-heart"></i>';
        button.appendChild(heart);
        
        setTimeout(() => {
            heart.remove();
            button.classList.remove('wishlist-added');
        }, 1000);
    },
    
    /**
     * Animate remove from wishlist
     */
    animateRemove: function(button) {
        if (!button) return;
        
        button.classList.add('wishlist-removed');
        
        setTimeout(() => {
            button.classList.remove('wishlist-removed');
        }, 500);
    },
    
    /**
     * Get wishlist products details
     */
    getProducts: async function() {
        if (this.items.length === 0) return [];
        
        try {
            const products = [];
            
            for (const productId of this.items) {
                const response = await ZIN.api.products.get(productId);
                if (response.success) {
                    products.push(response.product);
                }
            }
            
            return products;
        } catch (error) {
            ZIN.utils.debug('Failed to get wishlist products:', error);
            return [];
        }
    }
};
