/**
 * ZIN Fashion - Shop Page JavaScript (Simplified)
 * Location: /public_html/dev_staging/assets/js/shop.js
 * Uses quick view and cart functions from main.js
 * IMPORTANT: No duplicate event handlers for cart/wishlist/quick-view
 */

// ========================================
// Shop Page Initialization
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeViewMode();
    initializeSidebar();
    initializePriceFilter();
    // NOTE: Cart, wishlist, and quick view handlers are in main.js
    // Do NOT add duplicate handlers here
});

// ========================================
// View Mode Toggle (Grid/List)
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
// Make functions globally available
// ========================================
window.applyPriceFilter = applyPriceFilter;
window.updateSort = updateSort;
