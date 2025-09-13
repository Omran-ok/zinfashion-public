/**
 * ZIN Fashion - Shop Page JavaScript
 * Location: /public_html/dev_staging/assets/js/shop.js
 */

// ========================================
// Shop Page Functions
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initializeViewMode();
    initializeSidebar();
    initializePriceFilter();
    initializeProductActions();
});

// ========================================
// View Mode Toggle
// ========================================
function initializeViewMode() {
    const viewBtns = document.querySelectorAll('.view-btn');
    const productsGrid = document.getElementById('productsContainer');
    
    if (!viewBtns.length || !productsGrid) return;
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active button
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update grid class
            if (view === 'list') {
                productsGrid.classList.remove('view-grid');
                productsGrid.classList.add('view-list');
            } else {
                productsGrid.classList.remove('view-list');
                productsGrid.classList.add('view-grid');
            }
            
            // Save preference
            localStorage.setItem('shopViewMode', view);
        });
    });
    
    // Load saved view mode
    const savedView = localStorage.getItem('shopViewMode');
    if (savedView && savedView === 'list') {
        document.querySelector('[data-view="list"]')?.click();
    }
}

// ========================================
// Mobile Sidebar
// ========================================
function initializeSidebar() {
    const sidebar = document.querySelector('.shop-sidebar');
    if (!sidebar) return;
    
    // Create toggle button for mobile
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'sidebar-toggle';
    toggleBtn.innerHTML = '<i class="fas fa-filter"></i>';
    document.body.appendChild(toggleBtn);
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Add close button to sidebar
    const closeBtn = document.createElement('button');
    closeBtn.className = 'sidebar-close';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText = 'position: absolute; top: 20px; right: 20px; background: transparent; border: none; color: var(--text-primary); font-size: 24px; cursor: pointer; display: none;';
    sidebar.insertBefore(closeBtn, sidebar.firstChild);
    
    // Toggle sidebar
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        closeBtn.style.display = 'block';
    });
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        closeBtn.style.display = 'none';
    }
    
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);
    
    // Show/hide toggle based on screen size
    function checkScreenSize() {
        if (window.innerWidth > 991) {
            toggleBtn.style.display = 'none';
            closeSidebar();
        } else {
            toggleBtn.style.display = 'flex';
        }
    }
    
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
}

// ========================================
// Price Filter
// ========================================
function applyPriceFilter() {
    const minPrice = document.getElementById('minPrice')?.value;
    const maxPrice = document.getElementById('maxPrice')?.value;
    
    const params = new URLSearchParams(window.location.search);
    
    if (minPrice) {
        params.set('min_price', minPrice);
    } else {
        params.delete('min_price');
    }
    
    if (maxPrice) {
        params.set('max_price', maxPrice);
    } else {
        params.delete('max_price');
    }
    
    // Reset to page 1 when applying new filters
    params.set('page', '1');
    
    window.location.search = params.toString();
}

function initializePriceFilter() {
    const minInput = document.getElementById('minPrice');
    const maxInput = document.getElementById('maxPrice');
    
    if (!minInput || !maxInput) return;
    
    // Allow Enter key to apply filter
    [minInput, maxInput].forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyPriceFilter();
            }
        });
    });
}

// ========================================
// Sort Update
// ========================================
function updateSort(value) {
    const params = new URLSearchParams(window.location.search);
    params.set('sort', value);
    params.set('page', '1'); // Reset to first page
    window.location.search = params.toString();
}

// ========================================
// Product Actions
// ========================================
function initializeProductActions() {
    // Add to cart buttons
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            if (!productId) return;
            
            // Disable button
            this.disabled = true;
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (window.cartTranslations?.loading || 'Loading...');
            
            try {
                const response = await fetch('/api.php?api=cart', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update cart count
                    updateCartCount();
                    
                    // Show notification
                    showNotification(window.cartTranslations?.item_added_cart || 'Item added to cart', 'success');
                    
                    // Animate cart icon
                    animateCartIcon();
                    
                    // Reset button
                    this.innerHTML = '<i class="fas fa-check"></i> ' + (window.cartTranslations?.added || 'Added');
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification(window.cartTranslations?.error_adding_cart || 'Failed to add to cart', 'error');
                this.innerHTML = originalContent;
            } finally {
                this.disabled = false;
            }
        });
    });
    
    // Add to wishlist buttons
    document.querySelectorAll('.add-to-wishlist').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            if (!productId) return;
            
            try {
                const response = await fetch('/api.php?api=wishlist', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update icon
                    const icon = this.querySelector('i');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    
                    // Update wishlist count
                    updateWishlistCount();
                    
                    // Show notification
                    showNotification(window.cartTranslations?.item_added_wishlist || 'Added to wishlist', 'success');
                } else if (data.error && data.error.includes('login')) {
                    // Redirect to login
                    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                } else {
                    throw new Error(data.message || 'Failed to add to wishlist');
                }
            } catch (error) {
                console.error('Error adding to wishlist:', error);
                showNotification(window.cartTranslations?.error_occurred || 'An error occurred', 'error');
            }
        });
    });
    
    // Quick view buttons
    document.querySelectorAll('.quick-view').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            
            if (!productId) return;
            
            // Show loading
            showQuickViewModal(productId);
        });
    });
}

// ========================================
// Quick View Modal
// ========================================
async function showQuickViewModal(productId) {
    // Check if modal already exists
    let modal = document.getElementById('quickViewModal');
    if (!modal) {
        modal = createQuickViewModal();
    }
    
    // Show modal with loading
    modal.style.display = 'flex';
    const modalContent = modal.querySelector('.quick-view-content');
    modalContent.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    try {
        // Fetch product details
        const response = await fetch(`/api.php?api=product&id=${productId}`);
        const product = await response.json();
        
        if (product.error) {
            throw new Error(product.error);
        }
        
        // Populate modal with product details
        modalContent.innerHTML = `
            <div class="quick-view-images">
                <img src="${product.image || '/assets/images/placeholder.jpg'}" alt="${product.product_name}">
                ${product.badge ? `<span class="product-badge badge-${product.badge}">${product.badge.toUpperCase()}</span>` : ''}
            </div>
            <div class="quick-view-details">
                <h2>${product.product_name}</h2>
                <p class="product-category">${product.category_name}</p>
                <div class="product-price">
                    ${product.sale_price ? `<span class="price-old">€${formatPrice(product.base_price)}</span>` : ''}
                    <span class="price-current">€${formatPrice(product.sale_price || product.base_price)}</span>
                </div>
                <div class="product-description">
                    ${product.description || ''}
                </div>
                <div class="quick-view-actions">
                    <button class="btn btn-primary" onclick="addToCartFromQuickView(${product.product_id})">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <a href="/product/${product.product_slug}" class="btn btn-outline">
                        View Details
                    </a>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Error loading product:', error);
        modalContent.innerHTML = '<div class="error">Failed to load product details</div>';
    }
}

function createQuickViewModal() {
    const modal = document.createElement('div');
    modal.id = 'quickViewModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeQuickViewModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="closeQuickViewModal()">
                <i class="fas fa-times"></i>
            </button>
            <div class="quick-view-content"></div>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickViewModal();
        }
    });
    
    return modal;
}

function closeQuickViewModal() {
    const modal = document.getElementById('quickViewModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function addToCartFromQuickView(productId) {
    try {
        const response = await fetch('/api.php?api=cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateCartCount();
            showNotification('Item added to cart', 'success');
            animateCartIcon();
            setTimeout(() => {
                closeQuickViewModal();
            }, 1000);
        } else {
            throw new Error(data.message || 'Failed to add to cart');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('Failed to add to cart', 'error');
    }
}

// ========================================
// Helper Functions
// ========================================
function formatPrice(price) {
    return parseFloat(price).toFixed(2).replace('.', ',');
}

function updateCartCount() {
    // This should be implemented in cart.js
    if (window.cart && window.cart.updateCartCount) {
        window.cart.updateCartCount();
    }
}

function updateWishlistCount() {
    // Update wishlist badge
    fetch('/api.php?api=wishlist')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('wishlistCount');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error updating wishlist count:', error));
}

function animateCartIcon() {
    const cartIcon = document.querySelector('.header-actions .action-link[href="/cart"] i');
    if (cartIcon) {
        cartIcon.style.animation = 'bounce 0.5s ease';
        setTimeout(() => {
            cartIcon.style.animation = '';
        }, 500);
    }
}

function showNotification(message, type = 'info') {
    // Use notification from cart.js if available
    if (window.cart && window.cart.showNotification) {
        window.cart.showNotification(message, type);
    } else {
        // Fallback notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-left: 3px solid ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : 'var(--gold-primary)'};
            color: var(--text-primary);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
`;
document.head.appendChild(style);
