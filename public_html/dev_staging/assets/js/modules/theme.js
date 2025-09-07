/**
 * FILE: /assets/js/modules/theme.js
 * Theme switcher functionality
 */

window.ZIN = window.ZIN || {};

ZIN.theme = {
    currentTheme: null,
    toggleButton: null,
    
    /**
     * Initialize theme module
     */
    init: function() {
        this.currentTheme = this.getTheme();
        this.applyTheme(this.currentTheme);
        this.bindEvents();
        ZIN.utils.debug('Theme initialized:', this.currentTheme);
    },
    
    /**
     * Get current theme from localStorage or default
     */
    getTheme: function() {
        return localStorage.getItem('theme') || ZIN.config.theme || 'dark';
    },
    
    /**
     * Set theme
     */
    setTheme: function(theme) {
        this.currentTheme = theme;
        localStorage.setItem('theme', theme);
        ZIN.utils.setCookie('theme', theme, 365);
        this.applyTheme(theme);
        this.updateToggleButton();
        
        // Dispatch custom event
        const event = new CustomEvent('themeChanged', { 
            detail: { theme: theme } 
        });
        document.dispatchEvent(event);
        
        ZIN.utils.debug('Theme changed to:', theme);
    },
    
    /**
     * Apply theme to document
     */
    applyTheme: function(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.body.className = document.body.className
            .replace(/theme-\w+/g, '')
            .trim() + ` theme-${theme}`;
    },
    
    /**
     * Toggle between light and dark themes
     */
    toggle: function() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    },
    
    /**
     * Bind theme toggle events
     */
    bindEvents: function() {
        // Find all theme toggle buttons
        const toggleButtons = document.querySelectorAll('.theme-toggle, [data-theme-toggle]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        });
        
        // Store reference to main toggle button
        this.toggleButton = document.querySelector('.theme-toggle');
        this.updateToggleButton();
        
        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.currentTheme === 'auto') {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    },
    
    /**
     * Update toggle button icon
     */
    updateToggleButton: function() {
        if (!this.toggleButton) return;
        
        const icon = this.toggleButton.querySelector('i');
        if (icon) {
            if (this.currentTheme === 'dark') {
                icon.className = 'fas fa-sun';
                this.toggleButton.setAttribute('aria-label', 'Switch to light mode');
            } else {
                icon.className = 'fas fa-moon';
                this.toggleButton.setAttribute('aria-label', 'Switch to dark mode');
            }
        }
    },
    
    /**
     * Check if dark mode is active
     */
    isDark: function() {
        return this.currentTheme === 'dark';
    },
    
    /**
     * Check if light mode is active
     */
    isLight: function() {
        return this.currentTheme === 'light';
    },
    
    /**
     * Get system preference
     */
    getSystemPreference: function() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }
};
