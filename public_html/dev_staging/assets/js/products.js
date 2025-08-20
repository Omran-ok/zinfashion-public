/**
 * ZIN Fashion - Products Handler
 * Location: /public_html/dev_staging/assets/js/products.js
 */

// Products Manager
const productsManager = {
    currentPage: 1,
    currentFilter: 'all',
    currentCategory: '',
    currentSubcategory: '',
    isLoading: false,

    init() {
        this.bindEvents();
        this.loadProducts();
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
        if (this.isLoading) return;
        this.isLoading = true;

        const productGrid = document.getElementById('productGrid');
        
        if (!append && productGrid) {
            productGrid.innerHTML = '<div class="loading">Produkte werden geladen...</div>';
        }

        try {
            const params = new URLSearchParams({
                api: 'products',
                page: this.currentPage,
                filter: this.currentFilter,
                category: this.currentCategory,
                subcategory: this.currentSubcategory
            });

            const response = await fetch(`api.php?${params}`);
            const data = await response.json();

            if (data.success && productGrid) {
                if (!append) {
                    productGrid.innerHTML = '';
                } else {
                    // Remove loading message if appending
                    const loading = productGrid.querySelector('.loading');
                    if (loading) loading.remove();
                }

                if (data.data.products.length === 0) {
                    if (!append) {
                        productGrid.innerHTML = '<div class="no-products">Keine Produkte gefunden</div>';
                    }
                } else {
                    data.data.products.forEach(product => {
                        productGrid.appendChild(this.createProductCard(product));
                    });
                }

                // Show/hide load more button
                const loadMoreBtn = document.querySelector('.load-more button');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 
                        this.currentPage >= data.data.pages ? 'none' : 'block';
                }
            } else if (!data.success && productGrid) {
                productGrid.innerHTML = `<div class="error">Fehler beim Laden der Produkte</div>`;
            }
        } catch (error) {
            console.error('Error loading products:', error);
            
            if (productGrid) {
                productGrid.innerHTML = `
                    <div class="no-products">
                        <p>Fehler beim Laden der Produkte.</p>
                        <p>Bitte versuchen Sie es später erneut.</p>
                    </div>
                `;
            }
        } finally {
            this.isLoading = false;
        }
    },

    createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        
        const badge = product.badge_text ? 
            `<span class="product-badge ${product.badge}">${product.badge_text}</span>` : '';

        card.innerHTML = `
            <div class="product-image">
                <img src="${product.image || '/assets/images/placeholder.jpg'}" alt="${product.product_name}" loading="lazy">
                ${badge}
                <div class="product-actions">
                    <button class="action-btn wishlist-btn" data-product-id="${product.product_id}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    <button class="action-btn quick-view-btn" data-product-id="${product.product_id}">
                        <svg class="icon" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.product_name}</h3>
                <div class="product-price">
                    ${product.has_sale ? 
                        `<span class="old-price">${product.formatted_regular_price}</span>` : ''}
                    <span class="price">${product.formatted_price}</span>
                </div>
                <button class="add-to-cart" data-product-id="${product.product_id}">
                    In den Warenkorb
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
                this.toggleWishlist(product.product_id, wishlistBtn);
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

    async toggleWishlist(productId, button) {
        const isInWishlist = button.classList.contains('active');
        
        try {
            const response = await fetch(`api.php?api=wishlist${isInWishlist ? '&product_id=' + productId : ''}`, {
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
                const data = await response.json();
                
                // Show notification if function exists
                if (typeof showCartNotification === 'function') {
                    showCartNotification(
                        isInWishlist ? 'Removed from wishlist' : 'Added to wishlist',
                        'success'
                    );
                }
            }
        } catch (error) {
            console.error('Wishlist error:', error);
        }
    },

    showQuickView(productId) {
        console.log('Show quick view:', productId);
        // Quick view modal implementation
        // This will be handled by cart.js if it exists
    },

    async addToCart(productId) {
        try {
            const response = await fetch('api.php?api=cart', {
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
                const data = await response.json();
                
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
                if (typeof showCartNotification === 'function') {
                    showCartNotification('Product added to cart!', 'success');
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
        productsManager.init();
    }
});

// Export for global use
window.productsManager = productsManager;
