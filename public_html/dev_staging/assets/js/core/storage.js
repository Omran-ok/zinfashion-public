/**
 * FILE: /assets/js/core/storage.js
 * Local storage management with fallback
 */

window.ZIN = window.ZIN || {};

ZIN.storage = {
    prefix: 'zin_',
    isAvailable: false,
    
    /**
     * Initialize storage
     */
    init: function() {
        this.checkAvailability();
        this.cleanOldData();
        ZIN.utils.debug('Storage module initialized');
    },
    
    /**
     * Check if localStorage is available
     */
    checkAvailability: function() {
        try {
            const test = '__storage_test__';
            localStorage.setItem(test, test);
            localStorage.removeItem(test);
            this.isAvailable = true;
        } catch (e) {
            this.isAvailable = false;
            ZIN.utils.debug('LocalStorage not available, using fallback');
        }
    },
    
    /**
     * Get item from storage
     */
    get: function(key, defaultValue = null) {
        const fullKey = this.prefix + key;
        
        try {
            if (this.isAvailable) {
                const item = localStorage.getItem(fullKey);
                if (item === null) return defaultValue;
                
                // Try to parse JSON
                try {
                    return JSON.parse(item);
                } catch (e) {
                    return item;
                }
            } else {
                return this.getCookie(fullKey) || defaultValue;
            }
        } catch (e) {
            ZIN.utils.debug('Storage get error:', e);
            return defaultValue;
        }
    },
    
    /**
     * Set item in storage
     */
    set: function(key, value, expires = null) {
        const fullKey = this.prefix + key;
        const data = typeof value === 'object' ? JSON.stringify(value) : value;
        
        try {
            if (this.isAvailable) {
                localStorage.setItem(fullKey, data);
                
                // Store expiration if provided
                if (expires) {
                    const expiryKey = fullKey + '_expires';
                    localStorage.setItem(expiryKey, expires);
                }
            } else {
                this.setCookie(fullKey, data, expires);
            }
            
            return true;
        } catch (e) {
            ZIN.utils.debug('Storage set error:', e);
            
            // Handle quota exceeded
            if (e.name === 'QuotaExceededError') {
                this.clearOldest();
                // Try again
                try {
                    if (this.isAvailable) {
                        localStorage.setItem(fullKey, data);
                    }
                    return true;
                } catch (e2) {
                    return false;
                }
            }
            
            return false;
        }
    },
    
    /**
     * Remove item from storage
     */
    remove: function(key) {
        const fullKey = this.prefix + key;
        
        try {
            if (this.isAvailable) {
                localStorage.removeItem(fullKey);
                localStorage.removeItem(fullKey + '_expires');
            } else {
                this.deleteCookie(fullKey);
            }
            return true;
        } catch (e) {
            ZIN.utils.debug('Storage remove error:', e);
            return false;
        }
    },
    
    /**
     * Clear all storage
     */
    clear: function() {
        try {
            if (this.isAvailable) {
                // Only clear items with our prefix
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        localStorage.removeItem(key);
                    }
                });
            } else {
                // Clear cookies with our prefix
                document.cookie.split(';').forEach(cookie => {
                    const key = cookie.split('=')[0].trim();
                    if (key.startsWith(this.prefix)) {
                        this.deleteCookie(key);
                    }
                });
            }
            return true;
        } catch (e) {
            ZIN.utils.debug('Storage clear error:', e);
            return false;
        }
    },
    
    /**
     * Get all storage keys
     */
    keys: function() {
        const keys = [];
        
        try {
            if (this.isAvailable) {
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        keys.push(key.replace(this.prefix, ''));
                    }
                });
            }
        } catch (e) {
            ZIN.utils.debug('Storage keys error:', e);
        }
        
        return keys;
    },
    
    /**
     * Get storage size
     */
    size: function() {
        let total = 0;
        
        try {
            if (this.isAvailable) {
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        total += localStorage.getItem(key).length;
                    }
                });
            }
        } catch (e) {
            ZIN.utils.debug('Storage size error:', e);
        }
        
        return total;
    },
    
    /**
     * Clean expired data
     */
    cleanOldData: function() {
        if (!this.isAvailable) return;
        
        const now = Date.now();
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.endsWith('_expires')) {
                const expires = parseInt(localStorage.getItem(key));
                if (expires && expires < now) {
                    const dataKey = key.replace('_expires', '');
                    localStorage.removeItem(dataKey);
                    localStorage.removeItem(key);
                }
            }
        });
    },
    
    /**
     * Clear oldest items when storage is full
     */
    clearOldest: function() {
        if (!this.isAvailable) return;
        
        const items = [];
        const keys = Object.keys(localStorage);
        
        keys.forEach(key => {
            if (key.startsWith(this.prefix) && !key.endsWith('_expires')) {
                items.push({
                    key: key,
                    size: localStorage.getItem(key).length,
                    time: localStorage.getItem(key + '_time') || 0
                });
            }
        });
        
        // Sort by time (oldest first)
        items.sort((a, b) => a.time - b.time);
        
        // Remove oldest 25%
        const removeCount = Math.ceil(items.length * 0.25);
        for (let i = 0; i < removeCount; i++) {
            localStorage.removeItem(items[i].key);
            localStorage.removeItem(items[i].key + '_expires');
            localStorage.removeItem(items[i].key + '_time');
        }
    },
    
    /**
     * Cookie fallback methods
     */
    getCookie: function(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            const cookieValue = parts.pop().split(';').shift();
            try {
                return JSON.parse(decodeURIComponent(cookieValue));
            } catch (e) {
                return decodeURIComponent(cookieValue);
            }
        }
        return null;
    },
    
    setCookie: function(name, value, expires = null) {
        const data = typeof value === 'object' ? JSON.stringify(value) : value;
        const encodedValue = encodeURIComponent(data);
        
        let cookie = `${name}=${encodedValue};path=/;SameSite=Lax`;
        
        if (expires) {
            const date = new Date(expires);
            cookie += `;expires=${date.toUTCString()}`;
        } else {
            // Default to 1 year
            const date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            cookie += `;expires=${date.toUTCString()}`;
        }
        
        document.cookie = cookie;
    },
    
    deleteCookie: function(name) {
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
    }
};
