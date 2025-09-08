/**
 * ZIN Fashion - Animations
 * Location: /public_html/dev_staging/assets/js/animations.js
 */

// ========================================
// Scroll Animations
// ========================================
class ScrollAnimations {
    constructor() {
        this.elements = document.querySelectorAll('.animate-on-scroll');
        this.init();
    }
    
    init() {
        if ('IntersectionObserver' in window) {
            this.createObserver();
        } else {
            // Fallback for older browsers
            this.elements.forEach(element => {
                element.classList.add('visible');
            });
        }
    }
    
    createObserver() {
        const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add visible class with staggered delay
                    const delay = entry.target.dataset.delay || 0;
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, delay);
                    
                    // Stop observing this element
                    observer.unobserve(entry.target);
                }
            });
        }, options);
        
        this.elements.forEach(element => {
            observer.observe(element);
        });
    }
}

// ========================================
// Parallax Effects
// ========================================
class ParallaxEffect {
    constructor() {
        this.parallaxElements = document.querySelectorAll('[data-parallax]');
        this.init();
    }
    
    init() {
        if (this.parallaxElements.length > 0) {
            window.addEventListener('scroll', () => {
                this.updateParallax();
            });
            
            // Initial update
            this.updateParallax();
        }
    }
    
    updateParallax() {
        const scrolled = window.pageYOffset;
        
        this.parallaxElements.forEach(element => {
            const speed = element.dataset.parallax || 0.5;
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }
}

// ========================================
// Counter Animation
// ========================================
class CounterAnimation {
    constructor() {
        this.counters = document.querySelectorAll('[data-counter]');
        this.init();
    }
    
    init() {
        if (this.counters.length > 0) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.animateCounter(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            this.counters.forEach(counter => {
                observer.observe(counter);
            });
        }
    }
    
    animateCounter(element) {
        const target = parseInt(element.dataset.counter);
        const duration = parseInt(element.dataset.duration) || 2000;
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target;
            }
        };
        
        updateCounter();
    }
}

// ========================================
// Hover Effects
// ========================================
class HoverEffects {
    constructor() {
        this.init();
    }
    
    init() {
        // Product card hover effect
        this.initProductCardEffects();
        
        // Button hover effects
        this.initButtonEffects();
        
        // Category card effects
        this.initCategoryEffects();
    }
    
    initProductCardEffects() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
    
    initButtonEffects() {
        const buttons = document.querySelectorAll('.btn');
        
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function(e) {
                const ripple = document.createElement('span');
                ripple.className = 'btn-ripple';
                this.appendChild(ripple);
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
    }
    
    initCategoryEffects() {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            const image = card.querySelector('.category-image');
            
            card.addEventListener('mouseenter', function() {
                if (image) {
                    image.style.transform = 'scale(1.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (image) {
                    image.style.transform = 'scale(1)';
                }
            });
        });
    }
}

// ========================================
// Loading Animation
// ========================================
class LoadingAnimation {
    constructor() {
        this.createLoader();
    }
    
    createLoader() {
        const loaderHtml = `
            <div class="page-loader" id="pageLoader">
                <div class="loader-content">
                    <div class="loader-logo">
                        <img src="/assets/images/logo.svg" alt="ZIN Fashion">
                    </div>
                    <div class="loader-spinner">
                        <div class="spinner-ring"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('afterbegin', loaderHtml);
        
        // Hide loader when page is fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hideLoader();
            }, 500);
        });
    }
    
    hideLoader() {
        const loader = document.getElementById('pageLoader');
        if (loader) {
            loader.classList.add('fade-out');
            setTimeout(() => {
                loader.remove();
            }, 500);
        }
    }
}

// ========================================
// Text Effects
// ========================================
class TextEffects {
    constructor() {
        this.init();
    }
    
    init() {
        // Typewriter effect
        this.initTypewriter();
        
        // Text reveal effect
        this.initTextReveal();
    }
    
    initTypewriter() {
        const typewriterElements = document.querySelectorAll('[data-typewriter]');
        
        typewriterElements.forEach(element => {
            const text = element.dataset.typewriter;
            const speed = parseInt(element.dataset.speed) || 100;
            let index = 0;
            
            element.textContent = '';
            
            const type = () => {
                if (index < text.length) {
                    element.textContent += text.charAt(index);
                    index++;
                    setTimeout(type, speed);
                }
            };
            
            // Start typing when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        type();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(element);
        });
    }
    
    initTextReveal() {
        const revealElements = document.querySelectorAll('[data-text-reveal]');
        
        revealElements.forEach(element => {
            const text = element.textContent;
            const words = text.split(' ');
            
            element.innerHTML = words.map(word => 
                `<span class="reveal-word">${word}</span>`
            ).join(' ');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const words = entry.target.querySelectorAll('.reveal-word');
                        words.forEach((word, index) => {
                            setTimeout(() => {
                                word.classList.add('revealed');
                            }, index * 100);
                        });
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(element);
        });
    }
}

// ========================================
// Initialize All Animations
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize scroll animations
    new ScrollAnimations();
    
    // Initialize parallax effects
    new ParallaxEffect();
    
    // Initialize counter animations
    new CounterAnimation();
    
    // Initialize hover effects
    new HoverEffects();
    
    // Initialize loading animation
    new LoadingAnimation();
    
    // Initialize text effects
    new TextEffects();
});

// ========================================
// Animation Styles
// ========================================
const animationStyles = `
    /* Loader Styles */
    .page-loader {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--bg-primary);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .page-loader.fade-out {
        opacity: 0;
        transition: opacity 0.5s ease;
    }
    
    .loader-content {
        text-align: center;
    }
    
    .loader-logo {
        margin-bottom: 30px;
        animation: pulse 2s infinite;
    }
    
    .loader-logo img {
        height: 60px;
        width: auto;
    }
    
    .spinner-ring {
        width: 50px;
        height: 50px;
        border: 3px solid var(--border-color);
        border-top-color: var(--gold-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Button Ripple Effect */
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .btn-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    /* Text Reveal */
    .reveal-word {
        display: inline-block;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
    }
    
    .reveal-word.revealed {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Notification Styles */
    .notification {
        position: fixed;
        top: 100px;
        right: 20px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-left: 4px solid;
        padding: 15px 20px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 300px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        z-index: 9999;
        box-shadow: var(--shadow-lg);
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left-color: #2ecc71;
        color: #2ecc71;
    }
    
    .notification-error {
        border-left-color: #e74c3c;
        color: #e74c3c;
    }
    
    .notification-info {
        border-left-color: var(--gold-primary);
        color: var(--gold-primary);
    }
    
    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1004;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .modal.active {
        opacity: 1;
        visibility: visible;
    }
    
    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.7);
    }
    
    .modal-content {
        position: relative;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        max-width: 90%;
        max-height: 90vh;
        overflow: auto;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    
    .modal.active .modal-content {
        transform: scale(1);
    }
    
    .modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: transparent;
        border: none;
        color: var(--text-primary);
        font-size: 24px;
        cursor: pointer;
        z-index: 1;
        transition: color 0.3s ease;
    }
    
    .modal-close:hover {
        color: var(--gold-primary);
    }
    
    .quick-view-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        padding: 30px;
    }
    
    .quick-view-images img {
        width: 100%;
        height: auto;
        border-radius: 5px;
    }
    
    .quick-view-info h2 {
        color: var(--gold-primary);
        margin-bottom: 15px;
    }
    
    .quick-view-price {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 24px;
    }
    
    .quick-view-description {
        color: var(--text-secondary);
        margin-bottom: 30px;
        line-height: 1.6;
    }
    
    .quick-view-actions {
        display: flex;
        gap: 15px;
    }
`;

// Add animation styles to page
const animationStyleElement = document.createElement('style');
animationStyleElement.textContent = animationStyles;
document.head.appendChild(animationStyleElement);
