/**
 * FILE: /assets/js/core/i18n.js
 * Internationalization and localization module
 */

window.ZIN = window.ZIN || {};

ZIN.i18n = {
    currentLang: 'de',
    fallbackLang: 'de',
    supportedLangs: ['de', 'en', 'ar'],
    translations: {},
    loaded: false,
    
    /**
     * Initialize i18n
     */
    init: function() {
        this.detectLanguage();
        this.loadTranslations();
        this.updatePageLanguage();
        this.bindLanguageSwitcher();
        ZIN.utils.debug('i18n module initialized with language:', this.currentLang);
    },
    
    /**
     * Detect user language
     */
    detectLanguage: function() {
        // Priority: URL param > localStorage > cookie > browser > default
        const urlParams = new URLSearchParams(window.location.search);
        const urlLang = urlParams.get('lang');
        
        if (urlLang && this.supportedLangs.includes(urlLang)) {
            this.currentLang = urlLang;
        } else {
            const savedLang = ZIN.storage.get('language') || 
                            ZIN.utils.getCookie('lang') || 
                            this.getBrowserLanguage();
            
            if (this.supportedLangs.includes(savedLang)) {
                this.currentLang = savedLang;
            }
        }
        
        // Save language preference
        this.saveLanguage(this.currentLang);
    },
    
    /**
     * Get browser language
     */
    getBrowserLanguage: function() {
        const lang = navigator.language || navigator.userLanguage;
        const shortLang = lang.split('-')[0].toLowerCase();
        
        return this.supportedLangs.includes(shortLang) ? shortLang : this.fallbackLang;
    },
    
    /**
     * Load translations
     */
    loadTranslations: function() {
        // Basic translations embedded
        this.translations = {
            de: {
                // Navigation
                'home': 'Startseite',
                'shop': 'Shop',
                'women': 'Damen',
                'men': 'Herren',
                'children': 'Kinder',
                'sale': 'Sale',
                'new': 'Neu',
                'about': 'Über uns',
                'contact': 'Kontakt',
                
                // Account
                'account': 'Konto',
                'login': 'Anmelden',
                'logout': 'Abmelden',
                'register': 'Registrieren',
                'my-account': 'Mein Konto',
                'my-orders': 'Meine Bestellungen',
                'my-addresses': 'Meine Adressen',
                'my-wishlist': 'Meine Wunschliste',
                
                // Cart
                'cart': 'Warenkorb',
                'shopping-cart': 'Warenkorb',
                'add-to-cart': 'In den Warenkorb',
                'checkout': 'Zur Kasse',
                'subtotal': 'Zwischensumme',
                'shipping': 'Versand',
                'tax': 'MwSt.',
                'total': 'Gesamt',
                'free': 'Kostenlos',
                'remove': 'Entfernen',
                'quantity': 'Menge',
                'cart-empty': 'Ihr Warenkorb ist leer',
                'continue-shopping': 'Weiter einkaufen',
                
                // Products
                'products': 'Produkte',
                'product': 'Produkt',
                'price': 'Preis',
                'size': 'Größe',
                'color': 'Farbe',
                'description': 'Beschreibung',
                'details': 'Details',
                'reviews': 'Bewertungen',
                'in-stock': 'Auf Lager',
                'out-of-stock': 'Ausverkauft',
                'related-products': 'Ähnliche Produkte',
                
                // Search
                'search': 'Suchen',
                'search-placeholder': 'Wonach suchen Sie?',
                'search-results': 'Suchergebnisse',
                'no-results': 'Keine Ergebnisse gefunden',
                
                // Newsletter
                'newsletter': 'Newsletter',
                'subscribe': 'Anmelden',
                'email-placeholder': 'Ihre E-Mail-Adresse',
                'newsletter-text': 'Melden Sie sich für unseren Newsletter an und erhalten Sie 10% Rabatt',
                
                // Footer
                'customer-service': 'Kundenservice',
                'information': 'Informationen',
                'follow-us': 'Folgen Sie uns',
                'payment-methods': 'Zahlungsmethoden',
                'shipping-info': 'Versandinformationen',
                'returns': 'Rückgabe & Umtausch',
                'size-guide': 'Größentabelle',
                'privacy-policy': 'Datenschutz',
                'terms': 'AGB',
                'imprint': 'Impressum',
                
                // Messages
                'success': 'Erfolgreich',
                'error': 'Fehler',
                'warning': 'Warnung',
                'info': 'Information',
                'loading': 'Lädt...',
                'please-wait': 'Bitte warten...'
            },
            
            en: {
                // Navigation
                'home': 'Home',
                'shop': 'Shop',
                'women': 'Women',
                'men': 'Men',
                'children': 'Children',
                'sale': 'Sale',
                'new': 'New',
                'about': 'About',
                'contact': 'Contact',
                
                // Account
                'account': 'Account',
                'login': 'Login',
                'logout': 'Logout',
                'register': 'Register',
                'my-account': 'My Account',
                'my-orders': 'My Orders',
                'my-addresses': 'My Addresses',
                'my-wishlist': 'My Wishlist',
                
                // Cart
                'cart': 'Cart',
                'shopping-cart': 'Shopping Cart',
                'add-to-cart': 'Add to Cart',
                'checkout': 'Checkout',
                'subtotal': 'Subtotal',
                'shipping': 'Shipping',
                'tax': 'Tax',
                'total': 'Total',
                'free': 'Free',
                'remove': 'Remove',
                'quantity': 'Quantity',
                'cart-empty': 'Your cart is empty',
                'continue-shopping': 'Continue Shopping',
                
                // Products
                'products': 'Products',
                'product': 'Product',
                'price': 'Price',
                'size': 'Size',
                'color': 'Color',
                'description': 'Description',
                'details': 'Details',
                'reviews': 'Reviews',
                'in-stock': 'In Stock',
                'out-of-stock': 'Out of Stock',
                'related-products': 'Related Products',
                
                // Search
                'search': 'Search',
                'search-placeholder': 'What are you looking for?',
                'search-results': 'Search Results',
                'no-results': 'No results found',
                
                // Newsletter
                'newsletter': 'Newsletter',
                'subscribe': 'Subscribe',
                'email-placeholder': 'Your email address',
                'newsletter-text': 'Subscribe to our newsletter and get 10% off',
                
                // Footer
                'customer-service': 'Customer Service',
                'information': 'Information',
                'follow-us': 'Follow Us',
                'payment-methods': 'Payment Methods',
                'shipping-info': 'Shipping Information',
                'returns': 'Returns & Exchange',
                'size-guide': 'Size Guide',
                'privacy-policy': 'Privacy Policy',
                'terms': 'Terms & Conditions',
                'imprint': 'Imprint',
                
                // Messages
                'success': 'Success',
                'error': 'Error',
                'warning': 'Warning',
                'info': 'Information',
                'loading': 'Loading...',
                'please-wait': 'Please wait...'
            },
            
            ar: {
                // Navigation
                'home': 'الرئيسية',
                'shop': 'المتجر',
                'women': 'نسائي',
                'men': 'رجالي',
                'children': 'أطفال',
                'sale': 'تخفيضات',
                'new': 'جديد',
                'about': 'عن المتجر',
                'contact': 'اتصل بنا',
                
                // Account
                'account': 'الحساب',
                'login': 'تسجيل الدخول',
                'logout': 'تسجيل الخروج',
                'register': 'التسجيل',
                'my-account': 'حسابي',
                'my-orders': 'طلباتي',
                'my-addresses': 'عناويني',
                'my-wishlist': 'قائمة الأمنيات',
                
                // Cart
                'cart': 'السلة',
                'shopping-cart': 'سلة التسوق',
                'add-to-cart': 'أضف إلى السلة',
                'checkout': 'الدفع',
                'subtotal': 'المجموع الفرعي',
                'shipping': 'الشحن',
                'tax': 'الضريبة',
                'total': 'المجموع',
                'free': 'مجاني',
                'remove': 'إزالة',
                'quantity': 'الكمية',
                'cart-empty': 'سلة التسوق فارغة',
                'continue-shopping': 'متابعة التسوق',
                
                // Products
                'products': 'المنتجات',
                'product': 'المنتج',
                'price': 'السعر',
                'size': 'المقاس',
                'color': 'اللون',
                'description': 'الوصف',
                'details': 'التفاصيل',
                'reviews': 'التقييمات',
                'in-stock': 'متوفر',
                'out-of-stock': 'نفذ المخزون',
                'related-products': 'منتجات مشابهة',
                
                // Messages
                'success': 'نجح',
                'error': 'خطأ',
                'warning': 'تحذير',
                'info': 'معلومات',
                'loading': 'جاري التحميل...',
                'please-wait': 'يرجى الانتظار...'
            }
        };
        
        this.loaded = true;
    },
    
    /**
     * Get translation
     */
    t: function(key, params = {}) {
        const translation = this.translations[this.currentLang]?.[key] || 
                          this.translations[this.fallbackLang]?.[key] || 
                          key;
        
        // Replace parameters
        let result = translation;
        Object.keys(params).forEach(param => {
            result = result.replace(`{${param}}`, params[param]);
        });
        
        return result;
    },
    
    /**
     * Change language
     */
    changeLanguage: function(lang) {
        if (!this.supportedLangs.includes(lang)) {
            ZIN.utils.debug('Unsupported language:', lang);
            return;
        }
        
        this.currentLang = lang;
        this.saveLanguage(lang);
        this.updatePageLanguage();
        this.translatePage();
        
        // Reload if needed
        if (this.needsReload()) {
            window.location.reload();
        }
    },
    
    /**
     * Save language preference
     */
    saveLanguage: function(lang) {
        ZIN.storage.set('language', lang);
        ZIN.utils.setCookie('lang', lang);
    },
    
    /**
     * Update page language attributes
     */
    updatePageLanguage: function() {
        document.documentElement.lang = this.currentLang;
        document.documentElement.dir = this.currentLang === 'ar' ? 'rtl' : 'ltr';
        document.body.dataset.lang = this.currentLang;
    },
    
    /**
     * Translate page elements
     */
    translatePage: function() {
        // Translate elements with data-i18n attribute
        const elements = document.querySelectorAll('[data-i18n]');
        
        elements.forEach(element => {
            const key = element.dataset.i18n;
            const translation = this.t(key);
            
            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                element.placeholder = translation;
            } else {
                element.textContent = translation;
            }
        });
        
        // Update language switcher
        this.updateLanguageSwitcher();
    },
    
    /**
     * Bind language switcher
     */
    bindLanguageSwitcher: function() {
        const switchers = document.querySelectorAll('.lang-btn, [data-lang-switch]');
        
        switchers.forEach(switcher => {
            switcher.addEventListener('click', (e) => {
                e.preventDefault();
                const lang = switcher.dataset.lang || switcher.textContent.toLowerCase().slice(0, 2);
                this.changeLanguage(lang);
            });
        });
    },
    
    /**
     * Update language switcher UI
     */
    updateLanguageSwitcher: function() {
        const switchers = document.querySelectorAll('.lang-btn, [data-lang-switch]');
        
        switchers.forEach(switcher => {
            const lang = switcher.dataset.lang || switcher.textContent.toLowerCase().slice(0, 2);
            switcher.classList.toggle('active', lang === this.currentLang);
        });
    },
    
    /**
     * Check if page needs reload for language change
     */
    needsReload: function() {
        // Reload if there's dynamic content that needs server-side rendering
        return document.querySelector('[data-requires-reload]') !== null;
    },
    
    /**
     * Format number based on locale
     */
    formatNumber: function(number, decimals = 0) {
        const locales = {
            'de': 'de-DE',
            'en': 'en-US',
            'ar': 'ar-SA'
        };
        
        return new Intl.NumberFormat(locales[this.currentLang], {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },
    
    /**
     * Format date based on locale
     */
    formatDate: function(date, format = 'short') {
        const locales = {
            'de': 'de-DE',
            'en': 'en-US',
            'ar': 'ar-SA'
        };
        
        const options = {
            short: { day: 'numeric', month: 'numeric', year: 'numeric' },
            medium: { day: 'numeric', month: 'short', year: 'numeric' },
            long: { day: 'numeric', month: 'long', year: 'numeric' },
            full: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
        };
        
        return new Intl.DateTimeFormat(locales[this.currentLang], options[format]).format(new Date(date));
    }
};
