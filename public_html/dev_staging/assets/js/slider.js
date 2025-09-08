/**
 * ZIN Fashion - Hero Slider
 * Location: /public_html/dev_staging/assets/js/slider.js
 */

class HeroSlider {
    constructor() {
        this.currentSlide = 0;
        this.slides = document.querySelectorAll('.hero-slider .slide');
        this.dots = document.querySelectorAll('.hero-dots .dot');
        this.autoPlayInterval = null;
        this.autoPlayDelay = 5000; // 5 seconds
        
        if (this.slides.length > 0) {
            this.init();
        }
    }
    
    init() {
        // Start autoplay
        this.startAutoPlay();
        
        // Add click events to dots
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                this.goToSlide(index);
                this.resetAutoPlay();
            });
        });
        
        // Pause autoplay on hover
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', () => {
                this.stopAutoPlay();
            });
            
            heroSection.addEventListener('mouseleave', () => {
                this.startAutoPlay();
            });
        }
        
        // Handle visibility change (pause when tab is not visible)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopAutoPlay();
            } else {
                this.startAutoPlay();
            }
        });
    }
    
    goToSlide(index) {
        // Remove active class from current slide and dot
        this.slides[this.currentSlide].classList.remove('active');
        this.dots[this.currentSlide].classList.remove('active');
        
        // Update current slide index
        this.currentSlide = index;
        
        // Add active class to new slide and dot
        this.slides[this.currentSlide].classList.add('active');
        this.dots[this.currentSlide].classList.add('active');
        
        // Add animation effect
        this.slides[this.currentSlide].style.animation = 'slideIn 1.5s ease';
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }
    
    prevSlide() {
        const prevIndex = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }
    
    startAutoPlay() {
        this.stopAutoPlay(); // Clear any existing interval
        this.autoPlayInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoPlayDelay);
    }
    
    stopAutoPlay() {
        if (this.autoPlayInterval) {
            clearInterval(this.autoPlayInterval);
            this.autoPlayInterval = null;
        }
    }
    
    resetAutoPlay() {
        this.stopAutoPlay();
        this.startAutoPlay();
    }
}

// Initialize slider when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new HeroSlider();
});

// Add CSS animations
const sliderStyles = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: scale(1.1);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
`;

const styleElement = document.createElement('style');
styleElement.textContent = sliderStyles;
document.head.appendChild(styleElement);
