/**
 * ZIN Fashion - Product Page JavaScript
 * Location: /public_html/dev_staging/assets/js/product.js
 * Handles product interactions, variant selection, and tabs
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========================================
    // Gallery Functionality
    // ========================================
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumb-item');
    const zoomBtn = document.getElementById('zoomBtn');
    
    // Thumbnail click handler
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const imageUrl = this.dataset.image;
            if (imageUrl && mainImage) {
                mainImage.src = imageUrl;
                
                // Update active state
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Zoom functionality
    if (zoomBtn && mainImage) {
        zoomBtn.addEventListener('click', function() {
            // Create zoom modal
            const modal = document.createElement('div');
            modal.className = 'image-zoom-modal';
            modal.innerHTML = `
                <div class="zoom-modal-content">
                    <img src="${mainImage.src}" alt="Zoomed image">
                    <button class="zoom-close">&times;</button>
                </div>
            `;
            
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            // Close modal
            modal.querySelector('.zoom-close').addEventListener('click', function() {
                document.body.removeChild(modal);
                document.body.style.overflow = '';
            });
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                    document.body.style.overflow = '';
                }
            });
        });
    }
    
    // ========================================
    // Variant Selection
    // ========================================
    const productForm = document.getElementById('productForm');
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const colorInputs = document.querySelectorAll('input[name="color"]');
    const variantIdInput = document.getElementById('variantId');
    const currentPriceElement = document.getElementById('currentPrice');
    const stockCountElement = document.getElementById('stockCount');
    
    function updateVariant() {
        if (!window.productData) return;
        
        const selectedSize = document.querySelector('input[name="size"]:checked')?.value;
        const selectedColor = document.querySelector('input[name="color"]:checked')?.value || '0';
        
        if (!selectedSize) {
            if (stockCountElement) {
                stockCountElement.textContent = '';
            }
            return;
        }
        
        const variantKey = `${selectedSize}-${selectedColor}`;
        const variant = window.productData.variantMap[variantKey];
        
        if (variant) {
            // Update variant ID
            if (variantIdInput) {
                variantIdInput.value = variant.variant_id;
            }
            
            // Update price if there's adjustment
            if (variant.price_adjustment && variant.price_adjustment != 0) {
                const newPrice = window.productData.basePrice + parseFloat(variant.price_adjustment);
                if (currentPriceElement) {
                    currentPriceElement.textContent = '€' + newPrice.toFixed(2).replace('.', ',');
                }
            }
            
            // Update stock display
            if (stockCountElement) {
                const stock = parseInt(variant.stock);
                if (stock > 0 && stock <= 5) {
                    stockCountElement.textContent = window.productData.translations.onlyLeft.replace('{count}', stock);
                    stockCountElement.style.color = '#e74c3c';
                } else if (stock > 5) {
                    stockCountElement.textContent = `(${stock} available)`;
                    stockCountElement.style.color = 'var(--text-muted)';
                } else {
                    stockCountElement.textContent = window.productData.translations.outOfStock;
                    stockCountElement.style.color = '#e74c3c';
                }
            }
            
            // Update add to cart button
            const addToCartBtn = productForm.querySelector('button[type="submit"]');
            if (addToCartBtn) {
                if (variant.stock > 0) {
                    addToCartBtn.disabled = false;
                    addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> ' + (window.cartTranslations?.add_to_cart || 'Add to Cart');
                } else {
                    addToCartBtn.disabled = true;
                    addToCartBtn.innerHTML = '<i class="fas fa-times-circle"></i> ' + (window.productData.translations.outOfStock || 'Out of Stock');
                }
            }
        }
    }
    
    // Add event listeners for variant selection
    sizeInputs.forEach(input => {
        input.addEventListener('change', updateVariant);
    });
    
    colorInputs.forEach(input => {
        input.addEventListener('change', updateVariant);
    });
    
    // ========================================
    // Quantity Selector
    // ========================================
    window.updateQuantity = function(change) {
        const quantityInput = document.getElementById('quantity');
        if (!quantityInput) return;
        
        let currentValue = parseInt(quantityInput.value) || 1;
        let newValue = currentValue + change;
        
        if (newValue < 1) newValue = 1;
        if (newValue > 99) newValue = 99;
        
        quantityInput.value = newValue;
    };
    
    // ========================================
    // Product Form Submission
    // ========================================
    if (productForm) {
        productForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate selection
            const selectedSize = document.querySelector('input[name="size"]:checked');
            const hasColors = window.productData && window.productData.hasColors;
            const selectedColor = document.querySelector('input[name="color"]:checked');
            
            if (!selectedSize) {
                showNotification(window.productData?.translations.selectSize || 'Please select a size', 'error');
                return;
            }
            
            if (hasColors && !selectedColor) {
                showNotification(window.productData?.translations.selectColor || 'Please select a color', 'error');
                return;
            }
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api.php?api=cart', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update cart count
                    updateCartCount(data.cart_count);
                    
                    // Show success message
                    showNotification(window.cartTranslations?.item_added_cart || 'Item added to cart', 'success');
                    
                    // Refresh cart sidebar if open
                    if (typeof loadCartSidebar === 'function') {
                        loadCartSidebar();
                    }
                } else {
                    showNotification(data.message || 'Failed to add to cart', 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification('An error occurred', 'error');
            }
        });
    }
    
    // ========================================
    // Tabs Functionality
    // ========================================
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Update button states
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Update pane visibility
            tabPanes.forEach(pane => {
                if (pane.id === targetTab) {
                    pane.classList.add('active');
                } else {
                    pane.classList.remove('active');
                }
            });
        });
    });
    
    // ========================================
    // Size Guide Modal
    // ========================================
    window.showSizeGuide = function() {
        const sizeGuideTab = document.getElementById('size-guide');
        const sizeGuideBtn = document.querySelector('[data-tab="size-guide"]');
        
        if (sizeGuideTab && sizeGuideBtn) {
            // Activate size guide tab
            tabButtons.forEach(btn => btn.classList.remove('active'));
            sizeGuideBtn.classList.add('active');
            
            tabPanes.forEach(pane => pane.classList.remove('active'));
            sizeGuideTab.classList.add('active');
            
            // Scroll to tabs
            document.querySelector('.product-tabs').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    };
    
    // ========================================
    // Review Form Modal
    // ========================================
    window.openReviewForm = function() {
        // Create review form modal
        const modal = document.createElement('div');
        modal.className = 'review-modal';
        modal.innerHTML = `
            <div class="review-modal-content">
                <div class="review-modal-header">
                    <h3>Write a Review</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <form id="reviewForm" class="review-form">
                    <input type="hidden" name="product_id" value="${window.productData?.productId}">
                    
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="star-rating-input">
                            ${[5,4,3,2,1].map(i => `
                                <input type="radio" name="rating" value="${i}" id="star${i}" required>
                                <label for="star${i}"><i class="fas fa-star"></i></label>
                            `).join('')}
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Review Title</label>
                        <input type="text" name="review_title" placeholder="Summary of your experience" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="review_text" rows="5" placeholder="Tell us about your experience with this product" required></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline cancel-btn">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';
        
        // Close modal handlers
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.cancel-btn').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
        
        function closeModal() {
            document.body.removeChild(modal);
            document.body.style.overflow = '';
        }
        
        // Handle review submission
        modal.querySelector('#reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/api.php?api=reviews', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Review submitted successfully! It will appear after moderation.', 'success');
                    closeModal();
                } else {
                    showNotification(data.message || 'Failed to submit review', 'error');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                showNotification('An error occurred', 'error');
            }
        });
    };
    
    // ========================================
    // Share Product
    // ========================================
    const shareBtn = document.querySelector('.share-product');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            const url = window.location.href;
            const title = document.querySelector('.product-title')?.textContent || 'Check out this product';
            
            // Check if Web Share API is available
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    showNotification('Product link copied to clipboard!', 'success');
                }).catch(() => {
                    showNotification('Failed to copy link', 'error');
                });
            }
        });
    }
    
    // ========================================
    // Helper Functions
    // ========================================
    function updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('#cartCount');
        cartCountElements.forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }
    
    function showNotification(message, type = 'info') {
        // Use existing notification system if available
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback notification
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
                color: white;
                border-radius: 4px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
});

// ========================================
// Additional CSS for modals (inject dynamically)
// ========================================
const modalStyles = `
<style>
.image-zoom-modal,
.review-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.zoom-modal-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.zoom-modal-content img {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
}

.zoom-close,
.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.zoom-close:hover,
.modal-close:hover {
    background: var(--gold-primary);
    color: #000;
}

.review-modal-content {
    background: var(--bg-card);
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.review-modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.review-modal-header h3 {
    margin: 0;
    color: var(--gold-primary);
}

.review-form {
    padding: 20px;
}

.review-form .form-group {
    margin-bottom: 20px;
}

.review-form label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-primary);
    font-weight: 500;
}

.review-form input[type="text"],
.review-form textarea {
    width: 100%;
    padding: 10px;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    border-radius: 4px;
}

.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.star-rating-input input {
    display: none;
}

.star-rating-input label {
    cursor: pointer;
    font-size: 24px;
    color: var(--text-muted);
    transition: color 0.3s ease;
}

.star-rating-input input:checked ~ label,
.star-rating-input label:hover,
.star-rating-input label:hover ~ label {
    color: var(--gold-primary);
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

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
</style>
`;

// Inject modal styles
if (!document.querySelector('#productModalStyles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'productModalStyles';
    styleElement.innerHTML = modalStyles;
    document.head.appendChild(styleElement.firstElementChild);
}
