/**
 * ZIN Fashion - Products Handler
 * Location: /public_html/dev/assets/js/products.js
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
                document.querySelector('.filter-tab.active').classList.remove('active');
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
        
        if (!append) {
            productGrid.innerHTML = '<div class="loading">Produkte werden geladen...</div>';
        }

        try {
            const params = new URLSearchParams({
                action: 'get_products',
                page: this.currentPage,
                filter: this.currentFilter,
                category: this.currentCategory,
                subcategory: this.currentSubcategory
            });

            const response = await fetch(`includes/api.php?${params}`);
            const data = await response.json();

            if (data.success) {
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
            } else {
                productGrid.innerHTML = `<div class="error">Fehler beim Laden der Produkte: ${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error loading products:', error);
            
            // Since no products exist yet, show a friendly message
            if (!append) {
                productGrid.innerHTML = `
                    <div class="no-products">
                        <p>Noch keine Produkte verfügbar.</p>
                        <p>Neue Produkte kommen bald!</p>
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
        
        const badge = product.is_featured ? '<span class="product-badge">Bestseller</span>' : 
                     product.has_sale ? '<span class="product-badge sale">Sale</span>' : 
                     '';

        card.innerHTML = `
            <div class="product-image">
                <img src="${product.image}" alt="${product.product_name}" loading="lazy">
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

        wishlistBtn.addEventListener('click', () => this.addToWishlist(product.product_id));
        quickViewBtn.addEventListener('click', () => this.showQuickView(product.product_id));
        addToCartBtn.addEventListener('click', () => this.addToCart(product.product_id));

        return card;
    },

    addToWishlist(productId) {
        console.log('Add to wishlist:', productId);
        // Implement wishlist functionality
    },

    showQuickView(productId) {
        console.log('Show quick view:', productId);
        // Implement quick view modal
    },

    addToCart(productId) {
        console.log('Add to cart:', productId);
        // Implement add to cart functionality
        if (typeof cartManager !== 'undefined') {
            cartManager.addItem(productId);
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    productsManager.init();
});
