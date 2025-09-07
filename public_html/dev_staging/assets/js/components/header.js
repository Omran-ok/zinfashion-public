/**
 * FILE: /assets/js/components/header.js
 * Header component functionality
 */

window.ZIN = window.ZIN || {};

ZIN.header = {
    elements: {},
    isScrolled: false,
    lastScrollTop: 0,
    searchTimeout: null,
    
    /**
     * Initialize header component
     */
    init: function() {
        this.cacheElements();
        this.bindEvents();
        this.initStickyHeader();
        this.updateCartCount();
        this.updateWishlistCount();
        ZIN.utils.debug('Header component initialized');
    },
    
    /**
     * Cache DOM elements
     */
    cacheElements: function() {
        this.elements = {
            header: document.querySelector('.header'),
            searchBar: document.querySelector('.search-bar'),
            searchForm: document.querySelector('.search-form'),
            searchInput: document.querySelector('.search-input'),
            searchBtn: document.querySelector('.search-btn'),
            searchOverlay: document.querySelector('.search-overlay'),
            
            cartIcon: document.getElementById('cartIcon'),
            cartCount: document.querySelector('.cart-count'),
            wishlistCount: document.querySelector('.wishlist-count'),
            
            mobileMenuToggle: document.querySelector('.mobile-menu-toggle'),
            nav: document.querySelector('.nav'),
            navOverlay: document.querySelector('.nav-overlay'),
            navClose: document.querySelector('.nav-close'),
            
            languageButtons: document.querySelectorAll('.lang-btn'),
            themeToggle: document.querySelector('.theme-toggle')
        };
    },
    
    /**
     * Bind events
     */
    bindEvents: function() {
        // Search functionality
        this.bindSearchEvents();
        
        // Mobile menu
        this.bindMobileMenuEvents();
        
        // Language switcher
        this.bindLanguageEvents();
        
        // Cart icon click
        if (this.elements.cartIcon) {
            this.elements.cartIcon.addEventListener('click', (e) => {
                e.preventDefault();
                if (typeof ZIN.cart !== 'undefined' && ZIN.cart.toggle) {
                    ZIN.cart.toggle();
                }
            });
        }
        
        // Dropdown menus for desktop
        this.bindDropdownEvents();
    },
    
    /**
     * Bind search events
     */
    bindSearchEvents: function() {
        // Search form submission
        if (this.elements.searchForm) {
            this.elements.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSearch();
            });
        }
        
        // Live search on input
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', 
                ZIN.utils.debounce(() => {
                    const query = this.elements.searchInput.value.trim();
                    if (query.length >= ZIN.config.search.minLength) {
                        this.performSearch(query);
                    } else {
                        this.clearSearchResults();
                    }
                }, ZIN.config.search.debounceDelay)
            );
            
            // Clear search on escape
            this.elements.searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.elements.searchInput.value = '';
                    this.clearSearchResults();
                    this.elements.searchInput.blur();
                }
            });
        }
        
        // Mobile search toggle
        const mobileSearchToggle = document.querySelector('.mobile-search-toggle');
        if (mobileSearchToggle && ZIN.utils.isMobile()) {
            mobileSearchToggle.addEventListener('click', () => {
                this.toggleMobileSearch();
            });
        }
    },
    
    /**
     * Bind mobile menu events
     */
    bindMobileMenuEvents: function() {
        // Menu toggle
        if (this.elements.mobileMenuToggle) {
            this.elements.mobileMenuToggle.addEventListener('click', () => {
                this.toggleMobileMenu();
            });
        }
        
        // Close button
        if (this.elements.navClose) {
            this.elements.navClose.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        }
        
        // Overlay click
        if (this.elements.navOverlay) {
            this.elements.navOverlay.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        }
        
        // Mobile dropdown toggles
        const dropdownToggles = document.querySelectorAll('.nav-item.has-dropdown > .nav-link');
        dropdownToggles.forEach(toggle => {
            if (ZIN.utils.isMobile()) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleMobileDropdown(toggle.parentElement);
                });
            }
        });
    },
    
    /**
     * Bind language switcher events
     */
    bindLanguageEvents: function() {
        this.elements.languageButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const lang = button.textContent.toLowerCase().includes('de') ? 'de' :
                           button.textContent.toLowerCase().includes('en') ? 'en' : 'ar';
                this.changeLanguage(lang);
            });
        });
    },
    
    /**
     * Bind dropdown events for desktop
     */
    bindDropdownEvents: function() {
        if (ZIN.utils.isDesktop()) {
            const dropdowns = document.querySelectorAll('.nav-item.has-dropdown');
            
            dropdowns.forEach(dropdown => {
                let timeout;
                
                dropdown.addEventListener('mouseenter', () => {
                    clearTimeout(timeout);
                    this.openDropdown(dropdown);
                });
                
                dropdown.addEventListener('mouseleave', () => {
                    timeout = setTimeout(() => {
                        this.closeDropdown(dropdown);
                    }, 200);
                });
            });
        }
    },
    
    /**
     * Initialize sticky header
     */
    initStickyHeader: function() {
        if (!this.elements.header) return;
        
        let scrollTimer;
        
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimer);
            
            scrollTimer = setTimeout(() => {
                this.handleScroll();
            }, 10);
        });
        
        // Initial check
        this.handleScroll();
    },
    
    /**
     * Handle scroll for sticky header
     */
    handleScroll: function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add scrolled class
        if (scrollTop > 100) {
            if (!this.isScrolled) {
                this.elements.header.classList.add('scrolled');
                this.isScrolled = true;
            }
        } else {
            if (this.isScrolled) {
                this.elements.header.classList.remove('scrolled');
                this.isScrolled = false;
            }
        }
        
        // Hide/show on scroll direction (optional)
        if (scrollTop > this.lastScrollTop && scrollTop > 200) {
            // Scrolling down
            this.elements.header.style.transform = 'translateY(-100%)';
        } else {
            // Scrolling up
            this.elements.header.style.transform = 'translateY(0)';
        }
        
        this.lastScrollTop = scrollTop;
    },
    
    /**
     * Handle search
     */
    handleSearch: function() {
        const query = this.elements.searchInput.value.trim();
        
        if (query.length < 1) {
            ZIN.notifications.warning('Please enter a search term');
            return;
        }
        
        // Redirect to search results page
        window.location.href = `/shop.php?search=${encodeURIComponent(query)}`;
    },
    
    /**
     * Perform live search
     */
    performSearch: async function(query) {
        ZIN.utils.addLoading(this.elements.searchBtn);
        
        const result = await ZIN.api.searchProducts(query);
        
        if (result.success) {
            this.showSearchResults(result.data);
        } else {
            ZIN.notifications.error('Search failed. Please try again.');
        }
        
        ZIN.utils.removeLoading(this.elements.searchBtn);
    },
    
    /**
     * Show search results dropdown
     */
    showSearchResults: function(data) {
        // Implementation for search results dropdown
        // This would create a dropdown below the search bar with results
        ZIN.utils.debug('Search results:', data);
    },
    
    /**
     * Clear search results
     */
    clearSearchResults: function() {
        // Clear search results dropdown
        const resultsDropdown = document.querySelector('.search-results');
        if (resultsDropdown) {
            resultsDropdown.remove();
        }
    },
    
    /**
     * Toggle mobile search
     */
    toggleMobileSearch: function() {
        if (!this.elements.searchBar) return;
        
        this.elements.searchBar.classList.toggle('active');
        
        if (this.elements.searchBar.classList.contains('active')) {
            this.elements.searchInput?.focus();
        }
    },
    
    /**
     * Toggle mobile menu
     */
    toggleMobileMenu: function() {
        if (!this.elements.nav) return;
        
        const isActive = this.elements.nav.classList.contains('active');
        
        if (isActive) {
            this.closeMobileMenu();
        } else {
            this.openMobileMenu();
        }
    },
    
    /**
     * Open mobile menu
     */
    openMobileMenu: function() {
        if (!this.elements.nav) return;
        
        this.elements.nav.classList.add('active');
        this.elements.navOverlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Animate menu icon
        this.elements.mobileMenuToggle?.classList.add('active');
    },
    
    /**
     * Close mobile menu
     */
    closeMobileMenu: function() {
        if (!this.elements.nav) return;
        
        this.elements.nav.classList.remove('active');
        this.elements.navOverlay?.classList.remove('active');
        document.body.style.overflow = '';
        
        // Animate menu icon
        this.elements.mobileMenuToggle?.classList.remove('active');
    },
    
    /**
     * Toggle mobile dropdown
     */
    toggleMobileDropdown: function(item) {
        const isOpen = item.classList.contains('open');
        
        // Close all other dropdowns
        document.querySelectorAll('.nav-item.open').forEach(openItem => {
            if (openItem !== item) {
                openItem.classList.remove('open');
            }
        });
        
        // Toggle current dropdown
        item.classList.toggle('open');
    },
    
    /**
     * Open dropdown (desktop)
     */
    openDropdown: function(dropdown) {
        dropdown.classList.add('hover');
    },
    
    /**
     * Close dropdown (desktop)
     */
    closeDropdown: function(dropdown) {
        dropdown.classList.remove('hover');
    },
    
    /**
     * Change language
     */
    changeLanguage: function(lang) {
        // Update active button
        this.elements.languageButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.toLowerCase().includes(lang)) {
                btn.classList.add('active');
            }
        });
        
        // Save preference
        localStorage.setItem('lang', lang);
        ZIN.utils.setCookie('lang', lang, 365);
        
        // Update page direction for Arabic
        document.documentElement.setAttribute('lang', lang);
        document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
        
        // Reload page to apply language change
        window.location.reload();
    },
    
    /**
     * Update cart count
     */
    updateCartCount: async function() {
        const result = await ZIN.api.getCartCount();
        
        if (result.success && this.elements.cartCount) {
            const count = result.data.count || 0;
            this.elements.cartCount.textContent = count;
            this.elements.cartCount.style.display = count > 0 ? 'flex' : 'none';
            
            // Add animation
            this.elements.cartCount.classList.add('updated');
            setTimeout(() => {
                this.elements.cartCount.classList.remove('updated');
            }, 300);
        }
    },
    
    /**
     * Update wishlist count
     */
    updateWishlistCount: async function() {
        const result = await ZIN.api.getWishlistCount();
        
        if (result.success && this.elements.wishlistCount) {
            const count = result.data.count || 0;
            this.elements.wishlistCount.textContent = count;
            this.elements.wishlistCount.style.display = count > 0 ? 'flex' : 'none';
        }
    }
};
