/**
 * ZIN Fashion - Product Page JavaScript
 * Location: /public_html/dev_staging/assets/js/product.js
 * Version: Fixed cart integration
 */

// ========================================
// Product Page Initialization
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeGallery();
    initializeVariantSelection();
    initializeQuantitySelector();
    initializeProductTabs();
    initializeProductForm();
    initializeSizeGuide();
    initializeShareButtons();
    initializeWishlistButton();
});

// ========================================
// Gallery Management
// ========================================
function initializeGallery() {
    const mainImage = document.getElementById('mainImage');
    const thumbItems = document.querySelectorAll('.thumb-item');
    const zoomBtn = document.getElementById('zoomBtn');
    const mainImageContainer = document.querySelector('.main-image-container');
    
    if (!mainImage) return;
    
    // Thumbnail clicks
    thumbItems.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const imageUrl = this.dataset.image;
            if (imageUrl) {
                mainImage.src = imageUrl;
                
                // Update active state
                thumbItems.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Zoom functionality - both button and image click
    if (zoomBtn) {
        zoomBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            openImageZoom(mainImage.src);
        });
    }
    
    // Click on main image to zoom
    if (mainImageContainer) {
        mainImageContainer.style.cursor = 'zoom-in';
        mainImageContainer.addEventListener('click', function(e) {
            // Don't trigger if clicking the zoom button
            if (!e.target.closest('.zoom-btn')) {
                openImageZoom(mainImage.src);
            }
        });
    }
}

function openImageZoom(imageSrc) {
    // Remove existing modal if present
    const existingModal = document.getElementById('imageZoomModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'imageZoomModal';
    modal.className = 'image-zoom-modal';
    modal.innerHTML = `
        <div class="zoom-modal-content">
            <button class="zoom-close" onclick="closeImageZoom()">
                <i class="fas fa-times"></i>
            </button>
            <img src="${imageSrc}" alt="Zoomed image">
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Close on click outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeImageZoom();
        }
    });
    
    // Close on escape key
    document.addEventListener('keydown', handleEscapeKey);
}

window.closeImageZoom = function() {
    const modal = document.getElementById('imageZoomModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleEscapeKey);
    }
};

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        closeImageZoom();
    }
}

// ========================================
// Variant Selection
// ========================================
function initializeVariantSelection() {
    const sizeOptions = document.querySelectorAll('input[name="size"]');
    const colorOptions = document.querySelectorAll('input[name="color"]');
    
    sizeOptions.forEach(option => {
        option.addEventListener('change', updateVariant);
    });
    
    colorOptions.forEach(option => {
        option.addEventListener('change', updateVariant);
    });
    
    // Trigger initial variant selection
    const checkedSize = document.querySelector('input[name="size"]:checked');
    if (checkedSize) {
        updateVariant();
    }
}

function updateVariant() {
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const selectedColor = document.querySelector('input[name="color"]:checked');
    
    if (!selectedSize) return;
    
    const sizeId = selectedSize.value;
    const colorId = selectedColor ? selectedColor.value : '0';
    const variantKey = `${sizeId}-${colorId}`;
    
    if (window.productData && window.productData.variantMap) {
        const variant = window.productData.variantMap[variantKey];
        
        if (variant) {
            // Update variant ID
            const variantIdInput = document.getElementById('variantId');
            if (variantIdInput) {
                variantIdInput.value = variant.variant_id;
            }
            
            // Update stock status
            updateStockStatus(variant.stock);
            
            // Update price if there's adjustment
            if (variant.price_adjustment && variant.price_adjustment !== 0) {
                updatePrice(window.productData.basePrice + parseFloat(variant.price_adjustment));
            }
        }
    }
}

function updateStockStatus(stock) {
    const stockInfo = document.querySelector('.stock-info');
    const addToCartBtn = document.querySelector('.btn-detail-cart');
    
    if (!stockInfo) return;
    
    if (stock > 0) {
        stockInfo.innerHTML = `
            <span class="in-stock">
                <i class="fas fa-check-circle"></i> ${window.productData.translations.inStock}
            </span>
            ${stock <= 5 ? `<span class="stock-count">${window.productData.translations.onlyLeft.replace('{count}', stock)}</span>` : ''}
        `;
        
        if (addToCartBtn) {
            addToCartBtn.disabled = false;
            const btnText = document.getElementById('addToCartText');
            if (btnText) {
                btnText.textContent = window.productData.translations.addToCart || 'Add to Cart';
            }
        }
    } else {
        stockInfo.innerHTML = `
            <span class="out-of-stock">
                <i class="fas fa-times-circle"></i> ${window.productData.translations.outOfStock}
            </span>
        `;
        
        if (addToCartBtn) {
            addToCartBtn.disabled = true;
            const btnText = document.getElementById('addToCartText');
            if (btnText) {
                btnText.textContent = window.productData.translations.outOfStock || 'Out of Stock';
            }
        }
    }
}

function updatePrice(newPrice) {
    const priceElement = document.getElementById('currentPrice');
    if (priceElement) {
        priceElement.textContent = `€${newPrice.toFixed(2).replace('.', ',')}`;
    }
}

// ========================================
// Quantity Selector
// ========================================
function initializeQuantitySelector() {
    const quantityInput = document.getElementById('quantity');
    
    if (!quantityInput) return;
    
    window.updateQuantity = function(change) {
        let currentValue = parseInt(quantityInput.value) || 1;
        let newValue = currentValue + change;
        
        if (newValue < 1) newValue = 1;
        if (newValue > 99) newValue = 99;
        
        quantityInput.value = newValue;
    };
}

// ========================================
// Product Form Submission
// ========================================
function initializeProductForm() {
    const productForm = document.getElementById('productForm');
    
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleAddToCart();
        });
    }
}

function handleAddToCart() {
    // Get form values first
    const productId = document.getElementById('productId')?.value || window.productData?.productId;
    const variantId = document.getElementById('variantId')?.value;
    const quantity = parseInt(document.getElementById('quantity')?.value || 1);
    const selectedSize = document.querySelector('input[name="size"]:checked');
    const selectedColor = document.querySelector('input[name="color"]:checked');
    
    // Validation
    if (!productId) {
        showNotification('Product information missing', 'error');
        console.error('Product ID not found');
        return;
    }
    
    // Check if sizes exist and if one is selected
    const sizeOptions = document.querySelectorAll('input[name="size"]');
    if (sizeOptions.length > 0 && !selectedSize) {
        showNotification(window.productData?.translations?.selectSize || 'Please select a size', 'warning');
        document.querySelector('.size-options')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    
    // Check if colors exist and if one is selected
    const colorOptions = document.querySelectorAll('input[name="color"]');
    if (colorOptions.length > 0 && !selectedColor) {
        showNotification(window.productData?.translations?.selectColor || 'Please select a color', 'warning');
        document.querySelector('.color-options')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    
    // Check variant ID
    if (!variantId || variantId === '') {
        // If no variant ID but also no options to select, there might be a default variant
        if (window.productData?.defaultVariantId) {
            document.getElementById('variantId').value = window.productData.defaultVariantId;
        } else if (sizeOptions.length > 0 || colorOptions.length > 0) {
            showNotification('Please select product options', 'warning');
            return;
        }
    }
    
    // Use the cart class if available
    if (window.cart && window.cart.addItem) {
        // Use cart class method
        window.cart.addItem(productId, variantId || null, quantity);
    } else {
        // Fallback: Direct API call
        showLoadingState();
        
        fetch('/api.php?action=cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                variant_id: variantId || null,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingState();
            
            if (data.success) {
                showNotification('Added to cart successfully!', 'success');
                
                // Update cart count
                updateCartCount(data.cart_count || data.items?.length || 1);
                
                // Try to open cart sidebar
                openCartSidebar();
                
                // Reload cart if cart object exists
                if (window.cart && window.cart.loadCart) {
                    window.cart.loadCart();
                }
            } else {
                showNotification(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(error => {
            hideLoadingState();
            console.error('Error adding to cart:', error);
            showNotification('Failed to add to cart. Please try again.', 'error');
        });
    }
}

function showLoadingState() {
    const btn = document.querySelector('.btn-detail-cart');
    if (btn) {
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.dataset.originalText = originalText;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    }
}

function hideLoadingState() {
    const btn = document.querySelector('.btn-detail-cart');
    if (btn && btn.dataset.originalText) {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText;
        delete btn.dataset.originalText;
    }
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('#cartCount, .cart-count, .action-badge');
    cartCountElements.forEach(element => {
        if (count > 0) {
            element.textContent = count;
            element.style.display = 'flex';
        } else {
            element.style.display = 'none';
        }
    });
}

function openCartSidebar() {
    // Try cart class method first
    if (window.cart && window.cart.openCartSidebar) {
        window.cart.openCartSidebar();
    } else {
        // Manual implementation
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
}

// ========================================
// Wishlist Functionality
// ========================================
function initializeWishlistButton() {
    const wishlistBtn = document.querySelector('.btn-detail-wishlist');
    
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleWishlist(this);
        });
    }
}

function handleWishlist(button) {
    const productId = button.dataset.productId;
    
    if (!productId) return;
    
    const icon = button.querySelector('i');
    const isActive = icon.classList.contains('fas');
    
    if (isActive) {
        removeFromWishlist(productId, button);
    } else {
        addToWishlist(productId, button);
    }
}

function addToWishlist(productId, button) {
    fetch('/api.php?action=wishlist', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Added to wishlist!', 'success');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('far');
                icon.classList.add('fas');
            }
            button.style.background = 'var(--gold-primary)';
            button.style.color = '#000';
        } else if (data.login_required) {
            showNotification('Please login to add to wishlist', 'warning');
            // Optionally redirect to login
            setTimeout(() => {
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
            }, 1500);
        } else {
            showNotification(data.message || 'Failed to add to wishlist', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to wishlist:', error);
        showNotification('Failed to add to wishlist', 'error');
    });
}

function removeFromWishlist(productId, button) {
    fetch(`/api.php?action=wishlist&product_id=${productId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Removed from wishlist', 'info');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.add('far');
                icon.classList.remove('fas');
            }
            button.style.background = 'transparent';
            button.style.color = 'var(--gold-primary)';
        }
    })
    .catch(error => {
        console.error('Error removing from wishlist:', error);
    });
}

// ========================================
// Product Tabs
// ========================================
function initializeProductTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Update active states
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            this.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
}

// ========================================
// Size Guide
// ========================================
function initializeSizeGuide() {
    const sizeGuideLink = document.querySelector('.size-guide-link');
    
    if (sizeGuideLink) {
        sizeGuideLink.addEventListener('click', function(e) {
            e.preventDefault();
            showSizeGuide();
        });
    }
}

window.showSizeGuide = function() {
    const sizeGuideTab = document.querySelector('.tab-btn[data-tab="size-guide"]');
    if (sizeGuideTab) {
        // Click the size guide tab
        sizeGuideTab.click();
        
        // Scroll to the tabs section
        const tabsSection = document.querySelector('.product-tabs');
        if (tabsSection) {
            setTimeout(() => {
                tabsSection.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start',
                    inline: 'nearest'
                });
            }, 100);
        }
    }
};

// ========================================
// Share Buttons
// ========================================
function initializeShareButtons() {
    // These functions are already defined in product.php inline script
    // Just ensure they're available globally
    if (!window.copyProductLink && window.productData) {
        window.copyProductLink = function() {
            navigator.clipboard.writeText(window.productData.productUrl).then(() => {
                showNotification(window.productData.translations.linkCopied, 'success');
            }).catch(() => {
                // Fallback for older browsers
                const input = document.createElement('input');
                input.value = window.productData.productUrl;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showNotification(window.productData.translations.linkCopied, 'success');
            });
        };
        
        window.shareInstagram = function() {
            copyProductLink();
            setTimeout(() => {
                showNotification(window.productData.translations.shareInstagram, 'info');
            }, 1500);
        };
        
        window.shareTikTok = function() {
            copyProductLink();
            setTimeout(() => {
                showNotification(window.productData.translations.shareTikTok, 'info');
            }, 1500);
        };
    }
}

// ========================================
// Notification System
// ========================================
function showNotification(message, type = 'info') {
    // Try to use cart's notification system first
    if (window.cart && window.cart.showNotification) {
        window.cart.showNotification(message, type);
        return;
    }
    
    // Otherwise use local implementation
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const iconMap = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    notification.innerHTML = `
        <i class="fas fa-${iconMap[type] || 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : type === 'warning' ? '#f39c12' : '#3498db'};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 99999;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// ========================================
// Review Form
// ========================================
window.openReviewForm = function() {
    // Check if user is logged in
    if (!window.isLoggedIn) {
        showNotification('Please login to write a review', 'info');
        setTimeout(() => {
            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
        }, 1500);
        return;
    }
    
    // Create review modal
    const modal = document.createElement('div');
    modal.className = 'review-modal';
    modal.innerHTML = `
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h3>Write a Review</h3>
                <button class="modal-close" onclick="closeReviewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form class="review-form" id="reviewForm">
                <div class="form-group">
                    <label>Rating</label>
                    <div class="star-rating-input">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2"><i class="fas fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1"><i class="fas fa-star"></i></label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reviewTitle">Title</label>
                    <input type="text" id="reviewTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="reviewText">Your Review</label>
                    <textarea id="reviewText" name="review" rows="4" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeReviewModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Handle form submission
    document.getElementById('reviewForm').addEventListener('submit', submitReview);
};

window.closeReviewModal = function() {
    const modal = document.querySelector('.review-modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
};

function submitReview(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('product_id', window.productData.productId);
    
    fetch('/api.php?action=review', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Review submitted successfully! It will appear after moderation.', 'success');
            closeReviewModal();
        } else {
            showNotification(data.message || 'Failed to submit review', 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting review:', error);
        showNotification('Failed to submit review', 'error');
    });
}

// Make functions globally available
window.showNotification = showNotification;
window.handleAddToCart = handleAddToCart;
window.handleWishlist = handleWishlist;
