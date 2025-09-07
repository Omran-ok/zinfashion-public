/**
 * FILE: /assets/js/pages/homepage.js
 * Homepage specific functionality
 */

window.ZIN = window.ZIN || {};

ZIN.homepage = {
    // Hero slider properties
    slider: {
        element: null,
        slides: [],
        currentIndex: 0,
        autoPlayInterval: null,
        isPlaying: true,
        touchStartX: 0,
        touchEndX: 0
    },
    
    // Product filters
    filters: {
        currentFilter: 'all',
        isAnimating: false
    },
    
    /**
     * Initialize homepage
     */
    init: function() {
        // Only initialize on homepage
        const isHomepage = document.body.classList.contains('page-homepage') || 
                          document.body.dataset.page === 'homepage' ||
                          document.querySelector('.hero-slider');
        
        if (!isHomepage) return;
        
        this.initHeroSlider();
        this.initProductFilters();
        this.initCategoryCards();
        this.initProductCards();
        this.initSpecialOffers();
        this.initTestimonials();
        this.initInstagramFeed();
        this.initCountdowns();
        this.initAnimations();
        this.initParallax();
        
        if (ZIN.config && ZIN.config.debug) {
            console.log('Homepage initialized');
        }
    },
    
    /**
     * Initialize hero slider
     */
    initHeroSlider: function() {
        this.slider.element = document.querySelector('.hero-slider');
        if (!this.slider.element) return;
        
        this.slider.slides = this.slider.element.querySelectorAll('.hero-slide');
        if (this.slider.slides.length <= 1) return;
        
        // Create slider controls if they don't exist
        this.createSliderControls();
        
        // Bind control events
        const prevBtn = this.slider.element.querySelector('.slider-prev');
        const nextBtn = this.slider.element.querySelector('.slider-next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.changeSlide(-1));
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.changeSlide(1));
        }
        
        // Bind dot navigation
        const dots = this.slider.element.querySelectorAll('.slider-dot');
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Touch/swipe support
        this.initSliderTouch();
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.changeSlide(-1);
            if (e.key === 'ArrowRight') this.changeSlide(1);
        });
        
        // Start autoplay
        if (ZIN.config && ZIN.config.slider && ZIN.config.slider.autoplay) {
            this.startAutoPlay();
        }
        
        // Pause on hover
        this.slider.element.addEventListener('mouseenter', () => this.pauseAutoPlay());
        this.slider.element.addEventListener('mouseleave', () => this.startAutoPlay());
        
        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseAutoPlay();
            } else {
                this.startAutoPlay();
            }
        });
    },
    
    /**
     * Create slider controls
     */
    createSliderControls: function() {
        // Check if controls already exist
        if (this.slider.element.querySelector('.slider-controls')) return;
        
        // Create controls container
        const controls = document.createElement('div');
        controls.className = 'slider-controls';
        
        // Previous button
        controls.innerHTML = `
            <button class="slider-prev" aria-label="Previous slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-next" aria-label="Next slide">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        this.slider.element.appendChild(controls);
        
        // Create dots navigation
        const dotsContainer = document.createElement('div');
        dotsContainer.className = 'slider-dots';
        
        this.slider.slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.className = 'slider-dot' + (index === 0 ? ' active' : '');
            dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
            dotsContainer.appendChild(dot);
        });
        
        this.slider.element.appendChild(dotsContainer);
    },
    
    /**
     * Initialize touch support
     */
    initSliderTouch: function() {
        if (!this.slider.element) return;
        
        this.slider.element.addEventListener('touchstart', (e) => {
            this.slider.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        this.slider.element.addEventListener('touchend', (e) => {
            this.slider.touchEndX = e.changedTouches[0].screenX;
            this.handleSliderSwipe();
        }, { passive: true });
        
        // Mouse drag support
        let isDragging = false;
        let startX = 0;
        
        this.slider.element.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            this.slider.element.style.cursor = 'grabbing';
        });
        
        this.slider.element.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
        });
        
        this.slider.element.addEventListener('mouseup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            this.slider.element.style.cursor = 'grab';
            
            const endX = e.clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.changeSlide(1);
                } else {
                    this.changeSlide(-1);
                }
            }
        });
        
        this.slider.element.addEventListener('mouseleave', () => {
            isDragging = false;
            this.slider.element.style.cursor = 'grab';
        });
    },
    
    /**
     * Handle swipe gesture
     */
    handleSliderSwipe: function() {
        const swipeThreshold = 50;
        const diff = this.slider.touchStartX - this.slider.touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                this.changeSlide(1); // Swipe left - next slide
            } else {
                this.changeSlide(-1); // Swipe right - previous slide
            }
        }
    },
    
    /**
     * Change slide
     */
    changeSlide: function(direction) {
        if (!this.slider.slides.length) return;
        
        const totalSlides = this.slider.slides.length;
        this.slider.currentIndex = (this.slider.currentIndex + direction + totalSlides) % totalSlides;
        this.showSlide(this.slider.currentIndex);
    },
    
    /**
     * Go to specific slide
     */
    goToSlide: function(index) {
        if (index < 0 || index >= this.slider.slides.length) return;
        
        this.slider.currentIndex = index;
        this.showSlide(index);
    },
    
    /**
     * Show slide
     */
    showSlide: function(index) {
        // Hide all slides
        this.slider.slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
            slide.setAttribute('aria-hidden', i !== index);
        });
        
        // Update dots
        const dots = this.slider.element.querySelectorAll('.slider-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
            dot.setAttribute('aria-current', i === index ? 'true' : 'false');
        });
        
        // Trigger custom event
        this.slider.element.dispatchEvent(new CustomEvent('slideChange', {
            detail: { index: index }
        }));
    },
    
    /**
     * Start autoplay
     */
    startAutoPlay: function() {
        if (!this.slider.isPlaying || this.slider.slides.length <= 1) return;
        
        this.pauseAutoPlay(); // Clear any existing interval
        
        const delay = (ZIN.config && ZIN.config.slider && ZIN.config.slider.autoplayDelay) || 5000;
        
        this.slider.autoPlayInterval = setInterval(() => {
            this.changeSlide(1);
        }, delay);
    },
    
    /**
     * Pause autoplay
     */
    pauseAutoPlay: function() {
        if (this.slider.autoPlayInterval) {
            clearInterval(this.slider.autoPlayInterval);
            this.slider.autoPlayInterval = null;
        }
    },
    
    /**
     * Initialize product filters
     */
    initProductFilters: function() {
        const filterTabs = document.querySelectorAll('.filter-tab, [data-filter]');
        const productGrid = document.querySelector('.product-grid, #productGrid');
        
        if (!filterTabs.length || !productGrid) return;
        
        filterTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                
                if (this.filters.isAnimating) return;
                
                const filter = tab.dataset.filter || tab.getAttribute('data-category');
                this.filterProducts(filter, tab, filterTabs);
            });
        });
        
        // Initialize with 'all' filter
        this.filterProducts('all');
    },
    
    /**
     * Filter products
     */
    filterProducts: function(filter, activeTab = null, allTabs = null) {
        if (this.filters.currentFilter === filter) return;
        
        this.filters.isAnimating = true;
        this.filters.currentFilter = filter;
        
        // Update active tab
        if (activeTab && allTabs) {
            allTabs.forEach(tab => tab.classList.remove('active'));
            activeTab.classList.add('active');
        }
        
        const products = document.querySelectorAll('.product-card');
        let visibleCount = 0;
        
        products.forEach((product, index) => {
            const category = product.dataset.category;
            const shouldShow = filter === 'all' || category === filter;
            
            // Staggered animation
            setTimeout(() => {
                if (shouldShow) {
                    product.style.display = '';
                    product.classList.remove('fade-out');
                    product.classList.add('fade-in');
                    visibleCount++;
                } else {
                    product.classList.remove('fade-in');
                    product.classList.add('fade-out');
                    setTimeout(() => {
                        product.style.display = 'none';
                    }, 300);
                }
            }, index * 30);
        });
        
        // Show/hide empty state
        setTimeout(() => {
            this.filters.isAnimating = false;
            
            const emptyState = document.querySelector('.products-empty-state');
            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        }, products.length * 30 + 300);
        
        // Update URL without reload (optional)
        if (window.history && window.history.replaceState) {
            const url = new URL(window.location);
            if (filter === 'all') {
                url.searchParams.delete('filter');
            } else {
                url.searchParams.set('filter', filter);
            }
            window.history.replaceState({}, '', url);
        }
    },
    
    /**
     * Initialize category cards
     */
    initCategoryCards: function() {
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            // Preload image on hover
            const image = card.querySelector('img');
            if (image && image.dataset.hoverSrc) {
                const hoverImage = new Image();
                hoverImage.src = image.dataset.hoverSrc;
                
                card.addEventListener('mouseenter', () => {
                    image.src = hoverImage.src;
                });
                
                card.addEventListener('mouseleave', () => {
                    image.src = image.dataset.originalSrc || image.src;
                });
            }
            
            // Add ripple effect on click
            card.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 600);
            });
        });
    },
    
    /**
     * Initialize product cards
     */
    initProductCards: function() {
        const productCards = document.querySelectorAll('.product-card');
        
        productCards.forEach(card => {
            const productId = card.dataset.productId;
            const addToCartBtn = card.querySelector('.add-to-cart, [data-add-to-cart]');
            const quickViewBtn = card.querySelector('.quick-view, [data-quick-view]');
            const wishlistBtn = card.querySelector('.wishlist-btn, [data-wishlist]');
            const productImage = card.querySelector('.product-image img');
            
            // Image hover effect
            if (productImage && productImage.dataset.hoverImage) {
                const originalSrc = productImage.src;
                const hoverSrc = productImage.dataset.hoverImage;
                
                card.addEventListener('mouseenter', () => {
                    productImage.src = hoverSrc;
                });
                
                card.addEventListener('mouseleave', () => {
                    productImage.src = originalSrc;
                });
            }
            
            // Add to cart
            if (addToCartBtn && productId) {
                addToCartBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Add loading state
                    addToCartBtn.classList.add('loading');
                    addToCartBtn.disabled = true;
                    
                    try {
                        if (ZIN.cart && ZIN.cart.addItem) {
                            await ZIN.cart.addItem(productId, 1);
                        } else if (ZIN.api && ZIN.api.addToCart) {
                            await ZIN.api.addToCart(productId, 1);
                        }
                        
                        // Success animation
                        this.animateAddToCart(card);
                        
                    } catch (error) {
                        console.error('Failed to add to cart:', error);
                        if (ZIN.notifications) {
                            ZIN.notifications.error('Fehler beim Hinzufügen zum Warenkorb');
                        }
                    } finally {
                        addToCartBtn.classList.remove('loading');
                        addToCartBtn.disabled = false;
                    }
                });
            }
            
            // Quick view
            if (quickViewBtn && productId) {
                quickViewBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (ZIN.quickView && ZIN.quickView.open) {
                        ZIN.quickView.open(productId);
                    } else {
                        this.openQuickView(productId);
                    }
                });
            }
            
            // Wishlist
            if (wishlistBtn && productId) {
                wishlistBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (ZIN.wishlist && ZIN.wishlist.toggle) {
                        await ZIN.wishlist.toggle(productId, wishlistBtn);
                    } else {
                        wishlistBtn.classList.toggle('active');
                        const icon = wishlistBtn.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('far');
                            icon.classList.toggle('fas');
                        }
                    }
                });
            }
        });
    },
    
    /**
     * Animate add to cart
     */
    animateAddToCart: function(card) {
        const image = card.querySelector('.product-image img');
        const cartIcon = document.querySelector('.cart-icon, #cartIcon');
        
        if (!image || !cartIcon) return;
        
        // Clone image for animation
        const flyingImage = image.cloneNode();
        flyingImage.className = 'flying-image';
        
        const imageRect = image.getBoundingClientRect();
        const cartRect = cartIcon.getBoundingClientRect();
        
        flyingImage.style.position = 'fixed';
        flyingImage.style.left = imageRect.left + 'px';
        flyingImage.style.top = imageRect.top + 'px';
        flyingImage.style.width = imageRect.width + 'px';
        flyingImage.style.height = imageRect.height + 'px';
        flyingImage.style.zIndex = '9999';
        flyingImage.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        
        document.body.appendChild(flyingImage);
        
        // Force reflow
        flyingImage.offsetHeight;
        
        // Animate to cart
        flyingImage.style.left = cartRect.left + 'px';
        flyingImage.style.top = cartRect.top + 'px';
        flyingImage.style.width = '30px';
        flyingImage.style.height = '30px';
        flyingImage.style.opacity = '0';
        flyingImage.style.transform = 'rotate(360deg)';
        
        setTimeout(() => {
            flyingImage.remove();
            
            // Pulse cart icon
            cartIcon.classList.add('pulse');
            setTimeout(() => cartIcon.classList.remove('pulse'), 600);
        }, 800);
    },
    
    /**
     * Open quick view (fallback)
     */
    openQuickView: function(productId) {
        // This is a fallback if ZIN.quickView doesn't exist
        console.log('Quick view for product:', productId);
        
        // You could open a modal or redirect to product page
        window.location.href = `/product/${productId}`;
    },
    
    /**
     * Initialize special offers section
     */
    initSpecialOffers: function() {
        const offerCards = document.querySelectorAll('.offer-card');
        
        offerCards.forEach(card => {
            const countdown = card.querySelector('.offer-countdown');
            if (countdown) {
                const endDate = countdown.dataset.endDate;
                if (endDate) {
                    this.startCountdown(countdown, endDate);
                }
            }
        });
    },
    
    /**
     * Initialize testimonials carousel
     */
    initTestimonials: function() {
        const testimonials = document.querySelector('.testimonials-carousel');
        if (!testimonials) return;
        
        const items = testimonials.querySelectorAll('.testimonial-item');
        if (items.length <= 1) return;
        
        let currentIndex = 0;
        
        const showTestimonial = (index) => {
            items.forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });
        };
        
        // Auto-rotate testimonials
        setInterval(() => {
            currentIndex = (currentIndex + 1) % items.length;
            showTestimonial(currentIndex);
        }, 5000);
    },
    
    /**
     * Initialize Instagram feed
     */
    initInstagramFeed: function() {
        const feed = document.querySelector('.instagram-feed');
        if (!feed) return;
        
        // This would typically fetch from Instagram API
        // For now, just add hover effects
        const posts = feed.querySelectorAll('.instagram-post');
        
        posts.forEach(post => {
            post.addEventListener('click', () => {
                const link = post.dataset.link;
                if (link) {
                    window.open(link, '_blank');
                }
            });
        });
    },
    
    /**
     * Initialize countdown timers
     */
    initCountdowns: function() {
        const countdowns = document.querySelectorAll('[data-countdown]');
        
        countdowns.forEach(countdown => {
            const endDate = countdown.dataset.countdown;
            if (endDate) {
                this.startCountdown(countdown, endDate);
            }
        });
    },
    
    /**
     * Start countdown timer
     */
    startCountdown: function(element, endDate) {
        const end = new Date(endDate).getTime();
        
        const updateCountdown = () => {
            const now = new Date().getTime();
            const distance = end - now;
            
            if (distance < 0) {
                element.innerHTML = 'Angebot abgelaufen';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            element.innerHTML = `
                <span class="countdown-item">
                    <span class="countdown-value">${days}</span>
                    <span class="countdown-label">Tage</span>
                </span>
                <span class="countdown-item">
                    <span class="countdown-value">${hours.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">Std</span>
                </span>
                <span class="countdown-item">
                    <span class="countdown-value">${minutes.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">Min</span>
                </span>
                <span class="countdown-item">
                    <span class="countdown-value">${seconds.toString().padStart(2, '0')}</span>
                    <span class="countdown-label">Sek</span>
                </span>
            `;
        };
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    },
    
    /**
     * Initialize scroll animations
     */
    initAnimations: function() {
        if (!('IntersectionObserver' in window)) return;
        
        const animatedElements = document.querySelectorAll(
            '.animate-fade-up, .animate-fade-in, .animate-slide-in, [data-animate]'
        );
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const delay = element.dataset.animateDelay || 0;
                    
                    setTimeout(() => {
                        element.classList.add('animated');
                        
                        // Trigger custom event
                        element.dispatchEvent(new CustomEvent('animated'));
                    }, delay);
                    
                    observer.unobserve(element);
                }
            });
        }, observerOptions);
        
        animatedElements.forEach(el => observer.observe(el));
    },
    
    /**
     * Initialize parallax effects
     */
    initParallax: function() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        if (!parallaxElements.length) return;
        
        const handleParallax = () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const speed = element.dataset.parallax || 0.5;
                const yPos = -(scrolled * speed);
                
                element.style.transform = `translateY(${yPos}px)`;
            });
        };
        
        // Use throttle from utils if available
        const throttledHandler = ZIN.utils && ZIN.utils.throttle 
            ? ZIN.utils.throttle(handleParallax, 16)
            : handleParallax;
        
        window.addEventListener('scroll', throttledHandler, { passive: true });
    }
};
