/**
 * FILE: /assets/js/core/api.js
 * API communication layer for all server requests
 */

window.ZIN = window.ZIN || {};

ZIN.api = {
    /**
     * Base API request method
     */
    request: async function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Merge options
        const config = { ...defaultOptions, ...options };
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (csrfToken) {
            config.headers['X-CSRF-Token'] = csrfToken;
        }
        
        // Add JSON headers for POST/PUT requests with body
        if ((config.method === 'POST' || config.method === 'PUT') && config.body) {
            if (!(config.body instanceof FormData)) {
                config.headers['Content-Type'] = 'application/json';
                if (typeof config.body === 'object') {
                    config.body = JSON.stringify(config.body);
                }
            }
        }
        
        try {
            ZIN.utils.debug('API Request:', url, config);
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            ZIN.utils.debug('API Response:', data);
            
            return {
                success: true,
                data: data
            };
            
        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    },
    
    /**
     * GET request
     */
    get: function(url, params = {}) {
        if (Object.keys(params).length > 0) {
            url += '?' + ZIN.utils.buildQueryString(params);
        }
        return this.request(url, { method: 'GET' });
    },
    
    /**
     * POST request
     */
    post: function(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: data
        });
    },
    
    /**
     * PUT request
     */
    put: function(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: data
        });
    },
    
    /**
     * DELETE request
     */
    delete: function(url, data = {}) {
        return this.request(url, {
            method: 'DELETE',
            body: data
        });
    },
    
    // ========== Cart API ==========
    
    /**
     * Get cart data
     */
    getCart: async function() {
        return await this.get(ZIN.config.endpoints.cart);
    },
    
    /**
     * Add item to cart
     */
    addToCart: async function(productId, quantity = 1, variantId = null) {
        return await this.post(ZIN.config.endpoints.cartAdd, {
            product_id: productId,
            quantity: quantity,
            variant_id: variantId
        });
    },
    
    /**
     * Update cart item quantity
     */
    updateCartItem: async function(itemId, quantity) {
        return await this.put(ZIN.config.endpoints.cartUpdate, {
            item_id: itemId,
            quantity: quantity
        });
    },
    
    /**
     * Remove item from cart
     */
    removeFromCart: async function(itemId) {
        return await this.delete(ZIN.config.endpoints.cartRemove, {
            item_id: itemId
        });
    },
    
    /**
     * Get cart count
     */
    getCartCount: async function() {
        return await this.get(ZIN.config.endpoints.cartCount);
    },
    
    // ========== Wishlist API ==========
    
    /**
     * Get wishlist
     */
    getWishlist: async function() {
        return await this.get(ZIN.config.endpoints.wishlist);
    },
    
    /**
     * Toggle wishlist item
     */
    toggleWishlist: async function(productId) {
        return await this.post(ZIN.config.endpoints.wishlist, {
            product_id: productId
        });
    },
    
    /**
     * Get wishlist count
     */
    getWishlistCount: async function() {
        return await this.get(ZIN.config.endpoints.wishlistCount);
    },
    
    // ========== Products API ==========
    
    /**
     * Get products
     */
    getProducts: async function(params = {}) {
        return await this.get(ZIN.config.endpoints.products, params);
    },
    
    /**
     * Get single product
     */
    getProduct: async function(productId) {
        return await this.get(ZIN.config.endpoints.product, { id: productId });
    },
    
    /**
     * Search products
     */
    searchProducts: async function(query) {
        return await this.get(ZIN.config.endpoints.search, { q: query });
    },
    
    // ========== Newsletter API ==========
    
    /**
     * Subscribe to newsletter
     */
    subscribeNewsletter: async function(email) {
        const formData = new FormData();
        formData.append('email', email);
        
        // Get CSRF token from form if exists
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        return await this.request(ZIN.config.endpoints.newsletter, {
            method: 'POST',
            body: formData
        });
    },
    
    // ========== Categories API ==========
    
    /**
     * Get categories
     */
    getCategories: async function(parentId = null) {
        const params = parentId ? { parent: parentId } : {};
        return await this.get(ZIN.config.endpoints.categories, params);
    },
    
    // ========== Filters API ==========
    
    /**
     * Get available filters
     */
    getFilters: async function(categoryId = null) {
        const params = categoryId ? { category: categoryId } : {};
        return await this.get(ZIN.config.endpoints.filters, params);
    }
};
