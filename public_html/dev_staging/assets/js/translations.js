/**
 * ZIN Fashion - Translations Handler
 * Location: /public_html/dev_staging/assets/js/translations.js
 */

// Translation dictionary
const translations = {
    de: {
        // Quick View Modal
        'quick_view': 'Schnellansicht',
        'in_stock': 'Auf Lager',
        'out_of_stock': 'Ausverkauft',
        'add_to_cart': 'In den Warenkorb',
        'view_details': 'Details ansehen',
        'close': 'Schließen',
        'no_description': 'Keine Beschreibung verfügbar.',
        
        // Notifications
        'item_added_cart': 'Artikel zum Warenkorb hinzugefügt',
        'item_removed_cart': 'Artikel aus dem Warenkorb entfernt',
        'item_added_wishlist': 'Artikel zur Wunschliste hinzugefügt',
        'item_removed_wishlist': 'Artikel von der Wunschliste entfernt',
        'error_loading_product': 'Fehler beim Laden der Produktdetails',
        
        // Cart
        'cart_empty': 'Ihr Warenkorb ist leer',
        'subtotal': 'Zwischensumme',
        'total': 'Gesamt',
        'checkout': 'Zur Kasse',
        'view_cart': 'Warenkorb ansehen',
        'continue_shopping': 'Weiter einkaufen',
        'proceed_to_checkout': 'Zur Kasse gehen',
        
        // General
        'loading': 'Lädt...',
        'error': 'Fehler',
        'success': 'Erfolg',
        'please_wait': 'Bitte warten...',
        'product': 'Produkt',
        'price': 'Preis',
        'quantity': 'Menge',
        'remove': 'Entfernen',
        'free': 'KOSTENLOS',
        'shipping': 'Versand'
    },
    
    en: {
        // Quick View Modal
        'quick_view': 'Quick View',
        'in_stock': 'In Stock',
        'out_of_stock': 'Out of Stock',
        'add_to_cart': 'Add to Cart',
        'view_details': 'View Details',
        'close': 'Close',
        'no_description': 'No description available.',
        
        // Notifications
        'item_added_cart': 'Item added to cart',
        'item_removed_cart': 'Item removed from cart',
        'item_added_wishlist': 'Item added to wishlist',
        'item_removed_wishlist': 'Item removed from wishlist',
        'error_loading_product': 'Failed to load product details',
        
        // Cart
        'cart_empty': 'Your cart is empty',
        'subtotal': 'Subtotal',
        'total': 'Total',
        'checkout': 'Checkout',
        'view_cart': 'View Cart',
        'continue_shopping': 'Continue Shopping',
        'proceed_to_checkout': 'Proceed to Checkout',
        
        // General
        'loading': 'Loading...',
        'error': 'Error',
        'success': 'Success',
        'please_wait': 'Please wait...',
        'product': 'Product',
        'price': 'Price',
        'quantity': 'Quantity',
        'remove': 'Remove',
        'free': 'FREE',
        'shipping': 'Shipping'
    },
    
    ar: {
        // Quick View Modal
        'quick_view': 'عرض سريع',
        'in_stock': 'متوفر',
        'out_of_stock': 'نفذ المخزون',
        'add_to_cart': 'أضف إلى السلة',
        'view_details': 'عرض التفاصيل',
        'close': 'إغلاق',
        'no_description': 'لا يوجد وصف متاح.',
        
        // Notifications
        'item_added_cart': 'تمت إضافة المنتج إلى السلة',
        'item_removed_cart': 'تمت إزالة المنتج من السلة',
        'item_added_wishlist': 'تمت إضافة المنتج إلى قائمة الأمنيات',
        'item_removed_wishlist': 'تمت إزالة المنتج من قائمة الأمنيات',
        'error_loading_product': 'فشل في تحميل تفاصيل المنتج',
        
        // Cart
        'cart_empty': 'سلة التسوق فارغة',
        'subtotal': 'المجموع الفرعي',
        'total': 'المجموع',
        'checkout': 'إتمام الشراء',
        'view_cart': 'عرض السلة',
        'continue_shopping': 'متابعة التسوق',
        'proceed_to_checkout': 'إتمام الشراء',
        
        // General
        'loading': 'جاري التحميل...',
        'error': 'خطأ',
        'success': 'نجاح',
        'please_wait': 'الرجاء الانتظار...',
        'product': 'المنتج',
        'price': 'السعر',
        'quantity': 'الكمية',
        'remove': 'إزالة',
        'free': 'مجاني',
        'shipping': 'الشحن'
    }
};

// Get current language from HTML lang attribute
function getCurrentLanguage() {
    return document.documentElement.lang || 'de';
}

// Translation helper function
function t(key, fallback) {
    const currentLang = getCurrentLanguage();
    const langTranslations = translations[currentLang] || translations['de'];
    return langTranslations[key] || fallback || key;
}

// Make translations available globally
window.translations = translations[getCurrentLanguage()] || translations['de'];
window.currentLanguage = getCurrentLanguage();
window.t = t;

// Update translations when language changes
document.addEventListener('DOMContentLoaded', function() {
    // Watch for language changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'lang') {
                window.translations = translations[getCurrentLanguage()] || translations['de'];
                window.currentLanguage = getCurrentLanguage();
                
                // Dispatch custom event for language change
                window.dispatchEvent(new CustomEvent('languageChanged', {
                    detail: { language: window.currentLanguage }
                }));
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['lang']
    });
});
