/**
 * ZIN Fashion - Products Handler with Translation Support
 * Location: /public_html/dev_staging/assets/js/products-v2.js
 */

// Products Manager
const productsManager = {
    currentPage: 1,
    currentFilter: 'all',
    currentCategory: null,
    currentSubcategory: null,
    isLoading: false,

    init() {
        console.log('ProductsManager initializing...');
        this.bindEvents();
        this.loadProducts();
        
        // Listen for language changes
        this.setupLanguageListener();
    },

    setupLanguageListener() {
        // Re-render products when language changes
        document.querySelectorAll('.lang-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Small delay to let translations update
                setTimeout(() => {
                    this.reloadCurrentProducts();
                }, 100);
            });
        });
    },

    reloadCurrentProducts() {
        // Reload products with current filter to update translations
        const productGrid = document.getElementById('productGrid');
        if (productGrid && productGrid.children.length > 0) {
            this.loadProducts(false);
        }
    },

    bindEvents() {
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const activeTab = document.querySelector('.filter-tab.active');
                if (activeTab) {
                    activeTab.classList.remove('active');
                }
                e.target.classList.add('active');
                this.currentFilter = e.target.dataset.filter;
                this.currentPage = 1;
                this.loadProducts();
            });
        });

        // Load more button
        const loadMoreBtn = document.querySelector('.load-more button');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.currentPage++;
                this.loadProducts(true);
            });
        }
    },

    async loadProducts(append = false) {
        console.log('loadProducts called, append:', append, 'currentFilter:', this.currentFilter);
        if (this.isLoading) {
            console.log('Already loading, skipping...');
            return;
        }
        this.isLoading = true;

        const productGrid = document.getElementById('productGrid');
        
        if (!productGrid) {
            console.error('Product grid not found');
            this.isLoading = false;
            return;
        }
        
        // Get current language for loading message
        const lang = localStorage.getItem('lang') || 'de';
        const loadingText = this.getTranslation('loading', lang);
        
        if (!append) {
            productGrid.innerHTML = `<div class="loading">${loadingText}...</div>`;
        }

        try {
            const params = new URLSearchParams({
                api: 'products',
                page: this.currentPage,
                filter: this.currentFilter
            });

            // Only add category/subcategory if they have actual values
            if (this.currentCategory && this.currentCategory !== '') {
                params.append('category', this.currentCategory);
            }
            
            if (this.currentSubcategory && this.currentSubcategory !== '') {
                params.append('subcategory', this.currentSubcategory);
            }

            const url = `/api.php?${params}`;
            console.log('Fetching from:', url);
            
            const response = await fetch(url);
            const data = await response.json();
            
            console.log('API response:', data);

            if (data.success) {
                if (!append) {
                    productGrid.innerHTML = '';
                } else {
                    // Remove loading message if appending
                    const loading = productGrid.querySelector('.loading');
                    if (loading) loading.remove();
                }

                if (data.data && data.data.products && data.data.products.length > 0) {
                    console.log(`Adding ${data.data.products.length} products to grid`);
                    data.data.products.forEach(product => {
                        productGrid.appendChild(this.createProductCard(product));
                    });
                } else {
                    if (!append) {
                        const noProductsText = this.getTranslation('no-products', lang);
                        productGrid.innerHTML = `<div class="no-products">${noProductsText}</div>`;
                    }
                }

                // Show/hide load more button
                const loadMoreBtn = document.querySelector('.load-more button');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 
                        this.currentPage >= (data.data.pages || 1) ? 'none' : 'block';
                }
            } else {
                const errorText = this.getTranslation('error-loading', lang);
                productGrid.innerHTML = `<div class="error">${errorText}</div>`;
            }
        } catch (error) {
            console.error('Error loading products:', error);
            const lang = localStorage.getItem('lang') || 'de';
            const errorText = this.getTranslation('error-loading', lang);
            const tryAgainText = this.getTranslation('try-again', lang);
            
            productGrid.innerHTML = `
                <div class="no-products">
                    <p>${errorText}</p>
                    <p>${tryAgainText}</p>
                </div>
            `;
        } finally {
            this.isLoading = false;
        }
    },

    createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        
        // Get current language
        const lang = localStorage.getItem('lang') || 'de';
        
        // Get translated product name based on language
        let productName = product.product_name; // Default German
        if (lang === 'en' && product.product_name_en) {
            productName = product.product_name_en;
        } else if (lang === 'ar' && product.product_name_ar) {
            productName = product.product_name_ar;
        }
        
        // Get translations for UI elements
        const addToCartText = this.getTranslation('add-to-cart', lang);
        const quickViewText = this.getTranslation('quick-view', lang);
        const addToWishlistText = this.getTranslation('add-to-wishlist', lang);
        
        // Handle badge translation
        let badgeText = '';
        if (product.badge_text) {
            if (product.badge === 'new') {
                badgeText = this.getTranslation('new', lang);
            } else if (product.badge === 'sale') {
                badgeText = product.badge_text; // Keep percentage
            } else if (product.badge === 'bestseller') {
                badgeText = this.getTranslation('bestseller', lang);
            } else {
                badgeText = product.badge_text;
            }
        }
        
        const badge = badgeText ? 
            `<span class="product-badge ${product.badge || ''}">${badgeText}</span>` : '';

        card.innerHTML = `
            <div class="product-image">
                <img src="${product.image || '/assets/images/placeholder.jpg'}" alt="${productName}" loading="lazy">
                ${badge}
                <div class="product-actions">
                    <button class="action-btn wishlist-btn" data-product-id="${product.product_id}" title="${addToWishlistText}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <button class="action-btn quick-view-btn" data-product-id="${product.product_id}" title="${quickViewText}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-name">${productName}</h3>
                <div class="product-price">
                    ${product.has_sale ? 
                        `<span class="old-price">${product.formatted_regular_price}</span>` : ''}
                    <span class="price">${product.formatted_price}</span>
                </div>
                <button class="add-to-cart" data-product-id="${product.product_id}">
                    ${addToCartText}
                </button>
            </div>
        `;

        // Add event listeners
        const wishlistBtn = card.querySelector('.wishlist-btn');
        const quickViewBtn = card.querySelector('.quick-view-btn');
        const addToCartBtn = card.querySelector('.add-to-cart');

        if (wishlistBtn) {
            wishlistBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof toggleWishlist === 'function') {
                    toggleWishlist(product.product_id, wishlistBtn);
                } else {
                    this.toggleWishlist(product.product_id, wishlistBtn);
                }
            });
        }

        if (quickViewBtn) {
            quickViewBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof openQuickView === 'function') {
                    openQuickView(product.product_id);
                } else {
                    this.showQuickView(product.product_id);
                }
            });
        }

        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof addToCart === 'function') {
                    addToCart(product.product_id, 1);
                } else {
                    this.addToCart(product.product_id);
                }
            });
        }

        return card;
    },

    getTranslation(key, lang = null) {
        // Get language if not provided
        if (!lang) {
            lang = localStorage.getItem('lang') || 'de';
        }
        
        // Try to get translation from global translations object
        if (typeof translations !== 'undefined' && translations[lang] && translations[lang][key]) {
            return translations[lang][key];
        }
        
        // Fallback translations for critical UI elements
        const fallbackTranslations = {
            'de': {
                'add-to-cart': 'In den Warenkorb',
                'quick-view': 'Schnellansicht',
                'add-to-wishlist': 'Zur Wunschliste hinzufügen',
                'new': 'NEU',
                'bestseller': 'BESTSELLER',
                'sale': 'SALE',
                'loading': 'Produkte werden geladen',
                'no-products': 'Keine Produkte gefunden',
                'error-loading': 'Fehler beim Laden der Produkte',
                'try-again': 'Bitte versuchen Sie es später erneut'
            },
            'en': {
                'add-to-cart': 'Add to Cart',
                'quick-view': 'Quick View',
                'add-to-wishlist': 'Add to Wishlist',
                'new': 'NEW',
                'bestseller': 'BESTSELLER',
                'sale': 'SALE',
                'loading': 'Loading products',
                'no-products': 'No products found',
                'error-loading': 'Error loading products',
                'try-again': 'Please try again later'
            },
            'ar': {
                'add-to-cart': 'أضف إلى السلة',
                'quick-view': 'عرض سريع',
                'add-to-wishlist': 'أضف إلى قائمة الأمنيات',
                'new': 'جديد',
                'bestseller': 'الأكثر مبيعاً',
                'sale': 'تخفيضات',
                'loading': 'جاري تحميل المنتجات',
                'no-products': 'لا توجد منتجات',
                'error-loading': 'خطأ في تحميل المنتجات',
                'try-again': 'يرجى المحاولة مرة أخرى لاحقاً'
            }
        };
        
        if (fallbackTranslations[lang] && fallbackTranslations[lang][key]) {
            return fallbackTranslations[lang][key];
        }
        
        // Return the key if no translation found
        return key;
    },

    async toggleWishlist(productId, button) {
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
                
                const lang = localStorage.getItem('lang') || 'de';
                const message = isInWishlist ? 
                    this.getTranslation('removed-from-wishlist', lang) : 
                    this.getTranslation('added-to-wishlist', lang);
                
                if (typeof showCartNotification === 'function') {
                    showCartNotification(message, 'success');
                }
            }
        } catch (error) {
            console.error('Wishlist error:', error);
        }
    },

    showQuickView(productId) {
        console.log('Show quick view:', productId);
        // This will be handled by cart.js if it exists
    },

    async addToCart(productId) {
        try {
            const response = await fetch('/api.php?api=cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            });
            
            if (response.ok) {
                // Update cart count if element exists
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    const currentCount = parseInt(cartCount.textContent) || 0;
                    cartCount.textContent = currentCount + 1;
                    cartCount.style.display = 'flex';
                }
                
                // Open cart if function exists
                if (typeof openCart === 'function') {
                    openCart();
                }
                
                // Show notification if function exists
                const lang = localStorage.getItem('lang') || 'de';
                const message = this.getTranslation('added-to-cart', lang);
                
                if (typeof showCartNotification === 'function') {
                    showCartNotification(message, 'success');
                }
            }
        } catch (error) {
            console.error('Add to cart error:', error);
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if we have a product grid on the page
    if (document.getElementById('productGrid')) {
        console.log('Product grid found, initializing productsManager...');
        productsManager.init();
    }
});

// Export for global use
window.productsManager = productsManager;
