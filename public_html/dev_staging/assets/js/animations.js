/**
 * ZIN Fashion - Animations
 * Location: /public_html/dev_staging/assets/js/animations.js
 * Updated: Animations disabled for Arabic language and mobile devices
 */

// ========================================
// Check if animations should be disabled
// ========================================
function shouldDisableAnimations() {
    const isMobile = window.innerWidth < 768;
    const isArabic = document.documentElement.lang === 'ar' || document.documentElement.dir === 'rtl';
    
    return isMobile || isArabic;
}

// ========================================
// Scroll Animations
// ========================================
class ScrollAnimations {
    constructor() {
        this.elements = document.querySelectorAll('.animate-on-scroll');
        this.disabled = shouldDisableAnimations();
        this.init();
    }
    
    init() {
        // If animations are disabled, show all elements immediately
        if (this.disabled) {
            this.showAllElements();
            return;
        }
        
        // Otherwise use IntersectionObserver for animations
        if ('IntersectionObserver' in window) {
            this.createObserver();
        } else {
            this.showAllElements();
        }
    }
    
    showAllElements() {
        this.elements.forEach(element => {
            element.classList.add('visible');
            element.style.opacity = '1';
            element.style.transform = 'none';
            element.style.transition = 'none';
        });
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
                    const delay = entry.target.dataset.delay || 0;
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, delay);
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
        this.disabled = shouldDisableAnimations();
        this.init();
    }
    
    init() {
        // Skip parallax on mobile and Arabic
        if (this.disabled || this.parallaxElements.length === 0) {
            return;
        }
        
        window.addEventListener('scroll', () => {
            this.updateParallax();
        });
        
        this.updateParallax();
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
        this.disabled = shouldDisableAnimations();
        this.init();
    }
    
    init() {
        if (this.counters.length === 0) return;
        
        // If disabled, show final values immediately
        if (this.disabled) {
            this.counters.forEach(counter => {
                const target = parseInt(counter.dataset.counter);
                counter.textContent = target;
            });
            return;
        }
        
        // Otherwise animate
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
    
    animateCounter(element) {
        const target = parseInt(element.dataset.counter);
        const duration = parseInt(element.dataset.duration) || 2000;
        const increment = target / (duration / 16);
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
        this.disabled = shouldDisableAnimations();
        this.init();
    }
    
    init() {
        // Skip hover effects on mobile and Arabic
        if (this.disabled) return;
        
        this.initProductCardEffects();
        this.initButtonEffects();
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
        this.disabled = shouldDisableAnimations();
        
        // Skip loader on mobile and Arabic
        if (!this.disabled) {
            this.createLoader();
        }
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
        this.disabled = shouldDisableAnimations();
        this.init();
    }
    
    init() {
        // Skip text effects on mobile and Arabic
        if (this.disabled) {
            this.showAllText();
            return;
        }
        
        this.initTypewriter();
        this.initTextReveal();
    }
    
    showAllText() {
        // Show typewriter text immediately
        const typewriterElements = document.querySelectorAll('[data-typewriter]');
        typewriterElements.forEach(element => {
            element.textContent = element.dataset.typewriter;
        });
        
        // Show reveal text immediately
        const revealElements = document.querySelectorAll('[data-text-reveal]');
        revealElements.forEach(element => {
            element.style.opacity = '1';
        });
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
    const animationsDisabled = shouldDisableAnimations();
    
    if (animationsDisabled) {
        console.log('Animations disabled for mobile/Arabic');
        
        // Remove all animation classes and ensure visibility
        document.querySelectorAll('.animate-on-scroll, .animate-fade-up, .animate-fade-up-delay, .animate-fade-up-delay-2').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
            el.style.transition = 'none';
            el.classList.add('no-animation');
        });
    }
    
    // Initialize animations (they will check internally if disabled)
    new ScrollAnimations();
    new ParallaxEffect();
    new CounterAnimation();
    new HoverEffects();
    new LoadingAnimation();
    new TextEffects();
});

// ========================================
// Animation Styles
// ========================================
const animationStyles = `
    /* Base animation styles */
    .animate-on-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease;
    }
    
    .animate-on-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* Disable animations for mobile and Arabic */
    html[lang="ar"] .animate-on-scroll,
    html[dir="rtl"] .animate-on-scroll,
    .no-animation {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    
    @media screen and (max-width: 767px) {
        .animate-on-scroll,
        .animate-fade-up,
        .animate-fade-up-delay,
        .animate-fade-up-delay-2,
        [data-parallax],
        [data-typewriter],
        [data-text-reveal] {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
        
        .page-loader {
            display: none !important;
        }
    }
    
    /* Loader Styles (Desktop only) */
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
        filter: none !important;
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
    
    /* Button Ripple Effect (Desktop only) */
    @media (hover: hover) and (pointer: fine) {
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
    }
    
    /* Text Reveal (Desktop only) */
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
    
    /* Disable text animations on mobile and Arabic */
    html[lang="ar"] .reveal-word,
    html[dir="rtl"] .reveal-word {
        opacity: 1 !important;
        transform: none !important;
    }
    
    @media screen and (max-width: 767px) {
        .reveal-word {
            opacity: 1 !important;
            transform: none !important;
        }
    }
`;

// Only add animation styles if not disabled
if (!shouldDisableAnimations()) {
    const animationStyleElement = document.createElement('style');
    animationStyleElement.textContent = animationStyles;
    document.head.appendChild(animationStyleElement);
} else {
    // Add minimal styles for disabled animations
    const minimalStyles = `
        .animate-on-scroll,
        .animate-fade-up,
        .animate-fade-up-delay,
        .animate-fade-up-delay-2,
        .reveal-word,
        [data-parallax],
        [data-typewriter],
        [data-text-reveal] {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
    `;
    const styleElement = document.createElement('style');
    styleElement.textContent = minimalStyles;
    document.head.appendChild(styleElement);
}
