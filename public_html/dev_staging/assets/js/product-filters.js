/**
 * ZIN Fashion - Product Filters JavaScript
 * Location: /public_html/dev_staging/assets/js/product-filters.js
 * Purpose: Handle badge-based filtering for featured products
 */

// ========================================
// Initialize Product Filtering
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeProductFilters();
});

function initializeProductFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');
    
    if (!filterButtons.length || !productCards.length) return;
    
    // Add click event to filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Get filter type
            const filterType = this.dataset.filter;
            
            // Filter products with animation
            filterProducts(filterType, productCards);
        });
    });
}

function filterProducts(filterType, productCards) {
    const productsGrid = document.getElementById('featuredProductsGrid');
    
    // Add transition class for smooth animation
    productsGrid.style.transition = 'opacity 0.3s ease';
    productsGrid.style.opacity = '0.5';
    
    setTimeout(() => {
        let visibleCount = 0;
        
        productCards.forEach(card => {
            const badge = card.dataset.badge || 'none';
            
            if (filterType === 'all') {
                // Show all products
                card.style.display = 'block';
                card.classList.add('animate-on-scroll');
                visibleCount++;
            } else if (badge === filterType) {
                // Show only products with matching badge
                card.style.display = 'block';
                card.classList.add('animate-on-scroll');
                visibleCount++;
            } else {
                // Hide products that don't match
                card.style.display = 'none';
                card.classList.remove('animate-on-scroll');
            }
        });
        
        // If no products match the filter, show a message
        handleEmptyState(visibleCount, filterType);
        
        // Restore opacity
        productsGrid.style.opacity = '1';
        
        // Trigger scroll animations for visible products
        if (typeof initializeScrollAnimations === 'function') {
            initializeScrollAnimations();
        }
    }, 300);
}

function handleEmptyState(visibleCount, filterType) {
    const productsGrid = document.getElementById('featuredProductsGrid');
    let emptyMessage = document.getElementById('emptyFilterMessage');
    
    if (visibleCount === 0) {
        // Create empty state message if it doesn't exist
        if (!emptyMessage) {
            emptyMessage = document.createElement('div');
            emptyMessage.id = 'emptyFilterMessage';
            emptyMessage.className = 'empty-filter-message';
            productsGrid.appendChild(emptyMessage);
        }
        
        // Get appropriate message based on filter
        const messages = {
            'new': 'No new products available at the moment',
            'sale': 'No products on sale right now',
            'bestseller': 'No bestsellers available',
            'default': 'No products found'
        };
        
        emptyMessage.innerHTML = `
            <i class="fas fa-box-open"></i>
            <p>${messages[filterType] || messages['default']}</p>
            <button class="btn btn-outline" onclick="resetFilters()">Show All Products</button>
        `;
        emptyMessage.style.display = 'block';
    } else if (emptyMessage) {
        // Hide empty message if products are visible
        emptyMessage.style.display = 'none';
    }
}

// Reset filters to show all products
function resetFilters() {
    const allButton = document.querySelector('.filter-btn[data-filter="all"]');
    if (allButton) {
        allButton.click();
    }
}

// Make function globally available
window.resetFilters = resetFilters;
