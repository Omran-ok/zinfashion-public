/**
 * FILE: /assets/js/modules/notifications.js
 * Toast notification system
 */

window.ZIN = window.ZIN || {};

ZIN.notifications = {
    container: null,
    queue: [],
    
    /**
     * Initialize notifications module
     */
    init: function() {
        this.createContainer();
        ZIN.utils.debug('Notifications module initialized');
    },
    
    /**
     * Create notification container
     */
    createContainer: function() {
        // Check if container already exists
        this.container = document.getElementById('zin-notifications');
        
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'zin-notifications';
            this.container.className = `notifications-container ${ZIN.config.toast.position}`;
            this.container.setAttribute('aria-live', 'polite');
            this.container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(this.container);
        }
    },
    
    /**
     * Show notification
     */
    show: function(message, type = 'info', duration = null) {
        if (!this.container) {
            this.createContainer();
        }
        
        const id = ZIN.utils.generateId('notification');
        const notification = this.createNotification(id, message, type);
        
        // Add to container
        this.container.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto remove
        const timeout = duration || ZIN.config.toast.duration;
        setTimeout(() => {
            this.remove(id);
        }, timeout);
        
        return id;
    },
    
    /**
     * Create notification element
     */
    createNotification: function(id, message, type) {
        const notification = document.createElement('div');
        notification.id = id;
        notification.className = `notification notification-${type}`;
        notification.setAttribute('role', 'alert');
        
        // Icon based on type
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const icon = icons[type] || icons.info;
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${icon} notification-icon"></i>
                <span class="notification-message">${ZIN.utils.escapeHtml(message)}</span>
            </div>
            <button class="notification-close" aria-label="Close notification">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add close button event
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            this.remove(id);
        });
        
        return notification;
    },
    
    /**
     * Remove notification
     */
    remove: function(id) {
        const notification = document.getElementById(id);
        if (!notification) return;
        
        notification.classList.remove('show');
        notification.classList.add('hide');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    },
    
    /**
     * Show success notification
     */
    success: function(message, duration) {
        return this.show(message, 'success', duration);
    },
    
    /**
     * Show error notification
     */
    error: function(message, duration) {
        return this.show(message, 'error', duration);
    },
    
    /**
     * Show warning notification
     */
    warning: function(message, duration) {
        return this.show(message, 'warning', duration);
    },
    
    /**
     * Show info notification
     */
    info: function(message, duration) {
        return this.show(message, 'info', duration);
    },
    
    /**
     * Clear all notifications
     */
    clear: function() {
        if (!this.container) return;
        
        const notifications = this.container.querySelectorAll('.notification');
        notifications.forEach(notification => {
            this.remove(notification.id);
        });
    },
    
    /**
     * Show loading notification
     */
    loading: function(message = 'Loading...') {
        const id = ZIN.utils.generateId('notification');
        const notification = document.createElement('div');
        notification.id = id;
        notification.className = 'notification notification-loading';
        notification.setAttribute('role', 'status');
        
        notification.innerHTML = `
            <div class="notification-content">
                <div class="spinner"></div>
                <span class="notification-message">${ZIN.utils.escapeHtml(message)}</span>
            </div>
        `;
        
        if (!this.container) {
            this.createContainer();
        }
        
        this.container.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        return {
            id: id,
            update: (newMessage) => {
                const msgElement = notification.querySelector('.notification-message');
                if (msgElement) {
                    msgElement.textContent = newMessage;
                }
            },
            close: () => {
                this.remove(id);
            }
        };
    }
};

// Add required CSS if not already present
(function() {
    if (!document.getElementById('zin-notifications-styles')) {
        const styles = document.createElement('style');
        styles.id = 'zin-notifications-styles';
        styles.textContent = `
            .notifications-container {
                position: fixed;
                z-index: 9999;
                pointer-events: none;
                padding: 20px;
            }
            
            .notifications-container.top-right {
                top: 0;
                right: 0;
            }
            
            .notifications-container.top-left {
                top: 0;
                left: 0;
            }
            
            .notifications-container.bottom-right {
                bottom: 0;
                right: 0;
            }
            
            .notifications-container.bottom-left {
                bottom: 0;
                left: 0;
            }
            
            .notification {
                background: var(--bg-tertiary);
                border: 1px solid var(--border-light);
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 10px;
                min-width: 300px;
                max-width: 500px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                pointer-events: all;
                transform: translateX(120%);
                opacity: 0;
                transition: all 0.3s ease;
            }
            
            .notifications-container.top-left .notification,
            .notifications-container.bottom-left .notification {
                transform: translateX(-120%);
            }
            
            .notification.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .notification.hide {
                transform: translateX(120%);
                opacity: 0;
            }
            
            .notifications-container.top-left .notification.hide,
            .notifications-container.bottom-left .notification.hide {
                transform: translateX(-120%);
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 12px;
                flex: 1;
            }
            
            .notification-icon {
                font-size: 20px;
            }
            
            .notification-success {
                border-left: 4px solid var(--success);
            }
            
            .notification-success .notification-icon {
                color: var(--success);
            }
            
            .notification-error {
                border-left: 4px solid var(--error);
            }
            
            .notification-error .notification-icon {
                color: var(--error);
            }
            
            .notification-warning {
                border-left: 4px solid var(--warning);
            }
            
            .notification-warning .notification-icon {
                color: var(--warning);
            }
            
            .notification-info {
                border-left: 4px solid var(--info);
            }
            
            .notification-info .notification-icon {
                color: var(--info);
            }
            
            .notification-loading {
                border-left: 4px solid var(--primary-gold);
            }
            
            .notification-close {
                background: none;
                border: none;
                color: var(--text-tertiary);
                cursor: pointer;
                padding: 4px;
                font-size: 16px;
                transition: color 0.2s;
            }
            
            .notification-close:hover {
                color: var(--text-primary);
            }
            
            .spinner {
                width: 20px;
                height: 20px;
                border: 2px solid var(--border-light);
                border-top-color: var(--primary-gold);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(styles);
    }
})();
