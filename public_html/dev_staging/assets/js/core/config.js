/**
 * FILE: /assets/js/core/config.js
 * Global configuration and settings
 */

window.ZIN = window.ZIN || {};

ZIN.config = {
    // Site settings
    siteUrl: 'https://dev.zinfashion.de',
    apiUrl: '/api.php',
    assetsUrl: '/assets',
    
    // Localization
    currency: '€',
    currencyCode: 'EUR',
    currencyPosition: 'after', // 'before' or 'after'
    decimalSeparator: ',',
    thousandsSeparator: '.',
    dateFormat: 'DD.MM.YYYY',
    
    // Default settings
    language: localStorage.getItem('lang') || 'de',
    theme: localStorage.getItem('theme') || 'dark',
    
    // Responsive breakpoints
    breakpoints: {
        xs: 480,
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1400
    },
    
    // Animation durations (ms)
    animations: {
        fast: 200,
        normal: 300,
        slow: 500
    },
    
    // API endpoints
    endpoints: {
        cart: '/api.php?action=cart',
        cartAdd: '/api.php?action=cart',
        cartUpdate: '/api.php?action=cart',
        cartRemove: '/api.php?action=cart',
        cartCount: '/api.php?action=getCartCount',
        
        wishlist: '/api.php?action=wishlist',
        wishlistCount: '/api.php?action=getWishlistCount',
        
        products: '/api.php?action=products',
        product: '/api.php?action=product',
        search: '/api.php?action=search',
        
        newsletter: '/api.php?action=newsletter',
        
        categories: '/api.php?action=categories',
        filters: '/api.php?action=filters'
    },
    
    // Search settings
    search: {
        minLength: 2,
        debounceDelay: 300,
        maxSuggestions: 10
    },
    
    // Cart settings
    cart: {
        freeShippingThreshold: 50,
        shippingCost: 4.99,
        taxRate: 0.19 // 19% German VAT
    },
    
    // Product settings
    products: {
        itemsPerPage: 12,
        defaultSort: 'newest',
        imageplaceholder: '/assets/images/placeholder.jpg'
    },
    
    // Newsletter settings
    newsletter: {
        successMessage: 'Vielen Dank für Ihre Anmeldung!',
        errorMessage: 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    },
    
    // Toast notification settings
    toast: {
        duration: 3000,
        position: 'bottom-right' // 'top-right', 'top-left', 'bottom-right', 'bottom-left'
    },
    
    // Slider settings
    slider: {
        autoplay: true,
        autoplayDelay: 5000,
        transitionDuration: 500,
        pauseOnHover: true
    },
    
    // Debug mode
    debug: false
};
