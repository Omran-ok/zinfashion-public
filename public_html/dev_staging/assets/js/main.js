/**
 * ZIN Fashion - Main JavaScript
 * Location: /public_html/dev_staging/assets/js/main.js
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

/**
 * Initialize Application
 */
function initializeApp() {
    // Initialize modules
    initializeTheme();
    initializeLanguage();
    initializeNavigation();
    initializeSearch();
    initializeHeroSlider();
    // // initializeAnimations(); // Fixed - function does not exist // Commented out - function doesn't exist
    initializeNewsletter();
    
    // Load initial data
    loadCategories();
    loadProducts();
}

/**
 * Theme Management
 */
function initializeTheme() {
    const themeBtn = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Set initial theme
    document.documentElement.setAttribute('data-theme', currentTheme);
    
    // Theme toggle
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            const current = document.documentElement.getAttribute('data-theme');
            const newTheme = current === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Set cookie for PHP
            document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
        });
    }
}

/**
 * Language Management
 */
function initializeLanguage() {
    const langBtns = document.querySelectorAll('.lang-btn');
    let currentLang = localStorage.getItem('lang') || 'de';
    
    // Update translations on load
    updateTranslations(currentLang);
    
    langBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all
            langBtns.forEach(b => b.classList.remove('active'));
            
            // Add active to clicked
            this.classList.add('active');
            
            // Get new language
            currentLang = this.getAttribute('data-lang');
            
            // Update translations
            updateTranslations(currentLang);
            
            // Save preference
            localStorage.setItem('lang', currentLang);
            document.cookie = `lang=${currentLang};path=/;max-age=31536000`;
            
            // Update HTML attributes
            document.documentElement.lang = currentLang;
            document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
        });
    });
}

/**
 * Update all translations
 */
function updateTranslations(lang) {
    // Check if translations object exists
    if (typeof translations === 'undefined') return;
    
    const elements = document.querySelectorAll('[data-i18n]');
    elements.forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (translations[lang] && translations[lang][key]) {
            element.textContent = translations[lang][key];
        }
    });
    
    // Update placeholders
    const placeholders = document.querySelectorAll('[data-i18n-placeholder]');
    placeholders.forEach(element => {
        const key = element.getAttribute('data-i18n-placeholder');
        if (translations[lang] && translations[lang][key]) {
            element.placeholder = translations[lang][key];
        }
    });
}

/**
 * Navigation Management
 */
function initializeNavigation() {
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    const nav = document.querySelector('.nav');
    
    // Mobile menu toggle
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            document.body.style.overflow = nav.classList.contains('active') ? 'hidden' : '';
        });
    }
    
    // Close mobile menu on outside click
    document.addEventListener('click', function(e) {
        if (nav && nav.classList.contains('active')) {
            if (!nav.contains(e.target) && !mobileToggle.contains(e.target)) {
                nav.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add scroll effect to header
    let lastScroll = 0;
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.header');
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
}

/**
 * Search Functionality
 */
function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchInput && searchBtn) {
        // Search on button click
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            performSearch(searchInput.value);
        });
        
        // Search on enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value);
            }
        });
        
        // Live search (debounced)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    performSearch(this.value);
                }
            }, 500);
        });
    }
}

/**
 * Perform search
 */
function performSearch(query) {
    if (!query || query.length < 2) return;
    
    // Show loading state
    showLoading();
    
    // API call
    fetch(`/api.php?api=search&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            // Handle search results
            console.log('Search results:', data);
            hideLoading();
        })
        .catch(error => {
            console.error('Search error:', error);
            hideLoading();
        });
}

/**
 * Hero Slider
 */
function initializeHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    let slideInterval;
    
    // Function to show specific slide
    function showSlide(n) {
        // Hide all slides and remove active from dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Calculate the actual slide index
        currentSlide = (n + slides.length) % slides.length;
        
        // Show current slide and activate dot
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add('active');
        }
    }
    
    // Next slide function
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    // Previous slide function
    function prevSlide() {
        showSlide(currentSlide - 1);
    }
    
    // Auto play functionality
    function startAutoPlay() {
        stopAutoPlay();
        slideInterval = setInterval(nextSlide, 5000); // Change every 5 seconds
    }
    
    function stopAutoPlay() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
            startAutoPlay(); // Reset timer
        });
    });
    
    // Start auto play
    startAutoPlay();
    
    // Pause on hover (optional)
    const heroSection = document.querySelector('.hero');
    if (heroSection) {
        heroSection.addEventListener('mouseenter', stopAutoPlay);
        heroSection.addEventListener('mouseleave', startAutoPlay);
    }
}

/**
 * Initialize Newsletter
 */
function initializeNewsletter() {
    const form = document.getElementById('newsletterForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = form.querySelector('input[type="email"]');
            const email = emailInput.value;
            
            if (!email) return;
            
            // Send to API
            fetch('/api.php?api=newsletter', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thank you for subscribing!');
                    emailInput.value = '';
                } else {
                    alert(data.error || 'Subscription failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Newsletter error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
}

/**
 * Load Categories
 */
function loadCategories() {
    fetch('/api.php?api=categories')
        .then(response => response.json())
        .then(data => {
            console.log('Categories loaded:', data);
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
}

/**
 * Load Products
 */
function loadProducts() {
    // This is handled by products.js if product grid exists
    if (document.getElementById('productGrid')) {
        console.log('Product grid found, products.js will handle loading');
    }
}

/**
 * Show Loading
 */
function showLoading() {
    // Implement loading indicator
}

/**
 * Hide Loading
 */
function hideLoading() {
    // Hide loading indicator
}

// Make certain functions globally available
window.updateTranslations = updateTranslations;
window.performSearch = performSearch;
