/**
 * FILE: /assets/js/core/utils.js
 * Utility functions used throughout the application
 */

window.ZIN = window.ZIN || {};

ZIN.utils = {
    /**
     * Debounce function - delays execution until after wait milliseconds
     */
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func.apply(this, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(this, args);
        };
    },
    
    /**
     * Throttle function - limits execution to once per limit milliseconds
     */
    throttle: function(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    /**
     * Format price with currency
     */
    formatPrice: function(price) {
        const num = parseFloat(price);
        const formatted = num.toFixed(2)
            .replace('.', ZIN.config.decimalSeparator)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ZIN.config.thousandsSeparator);
        
        return ZIN.config.currencyPosition === 'before' 
            ? `${ZIN.config.currency}${formatted}`
            : `${formatted} ${ZIN.config.currency}`;
    },
    
    /**
     * Get cookie value by name
     */
    getCookie: function(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    },
    
    /**
     * Set cookie
     */
    setCookie: function(name, value, days = 365) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/`;
    },
    
    /**
     * Delete cookie
     */
    deleteCookie: function(name) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`;
    },
    
    /**
     * Check if device is mobile
     */
    isMobile: function() {
        return window.innerWidth <= ZIN.config.breakpoints.md || 
               /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    },
    
    /**
     * Check if device is tablet
     */
    isTablet: function() {
        return window.innerWidth > ZIN.config.breakpoints.md && 
               window.innerWidth <= ZIN.config.breakpoints.lg;
    },
    
    /**
     * Check if device is desktop
     */
    isDesktop: function() {
        return window.innerWidth > ZIN.config.breakpoints.lg;
    },
    
    /**
     * Smooth scroll to element
     */
    scrollTo: function(target, offset = 0, duration = 500) {
        const element = typeof target === 'string' 
            ? document.querySelector(target) 
            : target;
            
        if (!element) return;
        
        const start = window.pageYOffset;
        const elementTop = element.getBoundingClientRect().top + start;
        const distance = elementTop - start - offset;
        const startTime = performance.now();
        
        function animation(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const ease = progress * (2 - progress); // easeInOut
            
            window.scrollTo(0, start + distance * ease);
            
            if (progress < 1) {
                requestAnimationFrame(animation);
            }
        }
        
        requestAnimationFrame(animation);
    },
    
    /**
     * Generate unique ID
     */
    generateId: function(prefix = 'zin') {
        return `${prefix}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    /**
     * Deep merge objects
     */
    deepMerge: function(target, ...sources) {
        if (!sources.length) return target;
        const source = sources.shift();
        
        if (this.isObject(target) && this.isObject(source)) {
            for (const key in source) {
                if (this.isObject(source[key])) {
                    if (!target[key]) Object.assign(target, { [key]: {} });
                    this.deepMerge(target[key], source[key]);
                } else {
                    Object.assign(target, { [key]: source[key] });
                }
            }
        }
        
        return this.deepMerge(target, ...sources);
    },
    
    /**
     * Check if value is object
     */
    isObject: function(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    },
    
    /**
     * Add loading state to element
     */
    addLoading: function(element) {
        if (!element) return;
        element.classList.add('loading');
        element.setAttribute('aria-busy', 'true');
        element.disabled = true;
    },
    
    /**
     * Remove loading state from element
     */
    removeLoading: function(element) {
        if (!element) return;
        element.classList.remove('loading');
        element.setAttribute('aria-busy', 'false');
        element.disabled = false;
    },
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },
    
    /**
     * Parse query string
     */
    parseQueryString: function(queryString) {
        const params = {};
        const searchParams = new URLSearchParams(queryString || window.location.search);
        for (const [key, value] of searchParams) {
            params[key] = value;
        }
        return params;
    },
    
    /**
     * Build query string from object
     */
    buildQueryString: function(params) {
        return Object.keys(params)
            .filter(key => params[key] !== null && params[key] !== undefined)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
    },
    
    /**
     * Load script dynamically
     */
    loadScript: function(src, callback) {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        
        script.onload = function() {
            if (callback) callback();
        };
        
        document.head.appendChild(script);
    },
    
    /**
     * Load CSS dynamically
     */
    loadCSS: function(href, callback) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        
        link.onload = function() {
            if (callback) callback();
        };
        
        document.head.appendChild(link);
    },
    
    /**
     * Log debug message
     */
    debug: function(...args) {
        if (ZIN.config.debug) {
            console.log('[ZIN Debug]:', ...args);
        }
    }
};
