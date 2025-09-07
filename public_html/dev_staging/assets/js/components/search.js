/**
 * FILE: /assets/js/components/search.js
 * Search functionality
 */

window.ZIN = window.ZIN || {};

ZIN.search = {
    searchInput: null,
    searchForm: null,
    searchOverlay: null,
    searchResults: null,
    searchTimeout: null,
    isOpen: false,
    minChars: 2,
    
    /**
     * Initialize search
     */
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.createSearchOverlay();
        ZIN.utils.debug('Search component initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.searchForm = document.querySelector('.search-form');
        this.searchInput = document.querySelector('.search-input');
        this.searchBtn = document.querySelector('.search-btn');
        this.searchTriggers = document.querySelectorAll('[data-search-trigger]');
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Search form submit
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
        
        // Live search on input
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                this.handleSearchInput(e.target.value);
            });
            
            // Focus/blur events
            this.searchInput.addEventListener('focus', () => {
                if (this.searchInput.value.length >= this.minChars) {
                    this.showResults();
                }
            });
        }
        
        // Search triggers (mobile search icon, etc.)
        this.searchTriggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                this.openSearchModal();
            });
        });
        
        // Close on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeSearchModal();
            }
        });
    },
    
    /**
     * Create search overlay
     */
    createSearchOverlay: function() {
        // Create overlay container
        this.searchOverlay = document.createElement('div');
        this.searchOverlay.className = 'search-overlay';
        this.searchOverlay.innerHTML = `
            <div class="search-modal">
                <div class="search-modal-header">
                    <h3>Suche</h3>
                    <button class="search-close" onclick="ZIN.search.closeSearchModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-modal-body">
                    <form class="search-modal-form" onsubmit="return ZIN.search.performModalSearch(event)">
                        <input type="search" 
                               class="search-modal-input" 
                               placeholder="Wonach suchen Sie?" 
                               autocomplete="off">
                        <button type="submit" class="search-modal-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <div class="search-suggestions">
                        <h4>Beliebte Suchbegriffe</h4>
                        <div class="suggestion-tags">
                            <a href="#" onclick="ZIN.search.searchFor('Kleider')">Kleider</a>
                            <a href="#" onclick="ZIN.search.searchFor('Herren Jacken')">Herren Jacken</a>
                            <a href="#" onclick="ZIN.search.searchFor('Kinder')">Kinder</a>
                            <a href="#" onclick="ZIN.search.searchFor('Sale')">Sale</a>
                        </div>
                    </div>
                    <div class="search-modal-results" style="display: none;">
                        <!-- Results will be inserted here -->
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(this.searchOverlay);
        
        // Create inline results dropdown
        this.searchResults = document.createElement('div');
        this.searchResults.className = 'search-results-dropdown';
        this.searchResults.style.display = 'none';
        
        if (this.searchForm) {
            this.searchForm.appendChild(this.searchResults);
        }
    },
    
    /**
     * Handle search input
     */
    handleSearchInput: function(value) {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Check minimum characters
        if (value.length < this.minChars) {
            this.hideResults();
            return;
        }
        
        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.liveSearch(value);
        }, 300);
    },
    
    /**
     * Perform live search
     */
    liveSearch: async function(query) {
        try {
            // Show loading
            this.showLoading();
            
            // Perform search
            const response = await ZIN.api.search(query);
            
            if (response.success) {
                this.displayResults(response.results);
            }
        } catch (error) {
            ZIN.utils.debug('Search failed:', error);
            this.showError();
        }
    },
    
    /**
     * Perform full search
     */
    performSearch: function() {
        const query = this.searchInput.value.trim();
        
        if (query.length < this.minChars) {
            ZIN.notification.show('Bitte geben Sie mindestens 2 Zeichen ein', 'warning');
            return;
        }
        
        // Redirect to search results page
        window.location.href = `/search?q=${encodeURIComponent(query)}`;
    },
    
    /**
     * Perform modal search
     */
    performModalSearch: function(e) {
        e.preventDefault();
        const input = e.target.querySelector('.search-modal-input');
        const query = input.value.trim();
        
        if (query.length < this.minChars) {
            ZIN.notification.show('Bitte geben Sie mindestens 2 Zeichen ein', 'warning');
            return false;
        }
        
        // Redirect to search results page
        window.location.href = `/search?q=${encodeURIComponent(query)}`;
        return false;
    },
    
    /**
     * Search for specific term
     */
    searchFor: function(term) {
        window.location.href = `/search?q=${encodeURIComponent(term)}`;
    },
    
    /**
     * Display search results
     */
    displayResults: function(results) {
        if (!results || results.length === 0) {
            this.showNoResults();
            return;
        }
        
        // Group results by type
        const grouped = this.groupResults(results);
        
        let html = '';
        
        // Products
        if (grouped.products && grouped.products.length > 0) {
            html += '<div class="search-results-section">';
            html += '<h4>Produkte</h4>';
            html += '<ul class="search-results-list">';
            
            grouped.products.slice(0, 5).forEach(product => {
                html += `
                    <li>
                        <a href="/product/${product.slug}" class="search-result-item">
                            <img src="${product.image || '/assets/images/placeholder.jpg'}" 
                                 alt="${product.name}">
                            <div class="search-result-info">
                                <span class="search-result-name">${this.highlightMatch(product.name)}</span>
                                <span class="search-result-price">${ZIN.utils.formatPrice(product.price)}</span>
                            </div>
                        </a>
                    </li>
                `;
            });
            
            html += '</ul>';
            
            if (grouped.products.length > 5) {
                html += `<a href="/search?q=${encodeURIComponent(this.searchInput.value)}" 
                            class="search-see-all">Alle Produkte anzeigen (${grouped.products.length})</a>`;
            }
            
            html += '</div>';
        }
        
        // Categories
        if (grouped.categories && grouped.categories.length > 0) {
            html += '<div class="search-results-section">';
            html += '<h4>Kategorien</h4>';
            html += '<ul class="search-results-list">';
            
            grouped.categories.forEach(category => {
                html += `
                    <li>
                        <a href="/category/${category.slug}" class="search-result-item">
                            <i class="fas fa-folder"></i>
                            <span>${this.highlightMatch(category.name)}</span>
                        </a>
                    </li>
                `;
            });
            
            html += '</ul>';
            html += '</div>';
        }
        
        // Update results container
        this.searchResults.innerHTML = html;
        this.showResults();
        
        // Update modal results if open
        const modalResults = document.querySelector('.search-modal-results');
        if (modalResults && this.isOpen) {
            modalResults.innerHTML = html;
            modalResults.style.display = 'block';
        }
    },
    
    /**
     * Group results by type
     */
    groupResults: function(results) {
        const grouped = {
            products: [],
            categories: [],
            pages: []
        };
        
        results.forEach(result => {
            if (result.type === 'product') {
                grouped.products.push(result);
            } else if (result.type === 'category') {
                grouped.categories.push(result);
            } else if (result.type === 'page') {
                grouped.pages.push(result);
            }
        });
        
        return grouped;
    },
    
    /**
     * Highlight search match
     */
    highlightMatch: function(text) {
        const query = this.searchInput.value;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    },
    
    /**
     * Show loading state
     */
    showLoading: function() {
        this.searchResults.innerHTML = `
            <div class="search-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Suche läuft...</span>
            </div>
        `;
        this.searchResults.style.display = 'block';
    },
    
    /**
     * Show no results
     */
    showNoResults: function() {
        this.searchResults.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search"></i>
                <p>Keine Ergebnisse gefunden</p>
                <small>Versuchen Sie es mit anderen Suchbegriffen</small>
            </div>
        `;
        this.searchResults.style.display = 'block';
    },
    
    /**
     * Show error state
     */
    showError: function() {
        this.searchResults.innerHTML = `
            <div class="search-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Fehler bei der Suche</p>
                <small>Bitte versuchen Sie es später erneut</small>
            </div>
        `;
        this.searchResults.style.display = 'block';
    },
    
    /**
     * Show results dropdown
     */
    showResults: function() {
        if (this.searchResults) {
            this.searchResults.style.display = 'block';
        }
    },
    
    /**
     * Hide results dropdown
     */
    hideResults: function() {
        if (this.searchResults) {
            this.searchResults.style.display = 'none';
        }
    },
    
    /**
     * Open search modal (mobile)
     */
    openSearchModal: function() {
        if (!this.searchOverlay) return;
        
        this.searchOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
        
        // Focus on input
        setTimeout(() => {
            const input = this.searchOverlay.querySelector('.search-modal-input');
            if (input) input.focus();
        }, 100);
    },
    
    /**
     * Close search modal
     */
    closeSearchModal: function() {
        if (!this.searchOverlay) return;
        
        this.searchOverlay.classList.remove('active');
        document.body.style.overflow = '';
        this.isOpen = false;
        
        // Clear modal results
        const modalResults = document.querySelector('.search-modal-results');
        if (modalResults) {
            modalResults.style.display = 'none';
            modalResults.innerHTML = '';
        }
    },
    
    /**
     * Initialize autocomplete
     */
    initAutocomplete: function() {
        // This can be extended with a proper autocomplete library
        // For now, using the live search functionality
    }
};
