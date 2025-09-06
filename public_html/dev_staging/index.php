<?php
/**
 * ZIN Fashion - Homepage
 * Location: /public_html/dev_staging/index.php
 */

// Include configuration
require_once 'includes/config.php';

// Handle API requests
if (isset($_GET['api'])) {
    require_once 'includes/api.php';
    exit;
}

// Page-specific SEO settings
$pageTitle = 'ZIN Fashion - Premium Mode in Großen Größen | Luxury Fashion Store';
$pageDescription = 'Entdecken Sie luxuriöse Mode für Damen, Herren und Kinder bei ZIN Fashion. Premium Qualität in großen Größen. ✓ Kostenloser Versand ab 50€ ✓ Top Marken';
$pageKeywords = 'Mode, Fashion, Premium, Luxury, Große Größen, Plus Size, Damen, Herren, Kinder, Oebisfelde, Deutschland';
$ogTitle = 'ZIN Fashion - Luxury Fashion in Plus Sizes';
$ogDescription = 'Premium Mode für die ganze Familie. Entdecken Sie unsere exklusive Kollektion.';
$ogImage = SITE_URL . '/assets/images/og-home.jpg';
$ogType = 'website';

// Additional CSS for homepage
$additionalStyles = [
    SITE_URL . '/assets/css/homepage.css',
    SITE_URL . '/assets/css/components.css',
    SITE_URL . '/assets/css/main-enhanced.css',
];

// Get database connection
$pdo = getDBConnection();

// Get featured products with correct column names
$featuredSql = "SELECT 
                p.product_id,
                p.product_name,
                p.product_slug,
                p.base_price,
                p.sale_price,
                p.badge,
                p.description,
                pi.image_url,
                COUNT(DISTINCT pv.size_id) as size_count,
                COUNT(DISTINCT pv.color_id) as color_count,
                COALESCE(SUM(pv.stock_quantity), 0) as total_stock
                FROM products p
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_available = 1
                WHERE p.is_active = 1 AND p.is_featured = 1
                GROUP BY p.product_id, p.product_name, p.product_slug, p.base_price, p.sale_price, p.badge, p.description, pi.image_url
                ORDER BY p.created_at DESC
                LIMIT 8";

try {
    $featuredStmt = $pdo->query($featuredSql);
    $featuredProducts = $featuredStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Featured products query error: " . $e->getMessage());
    $featuredProducts = [];
}

// Get new arrivals
$newArrivalsSql = "SELECT 
                   p.product_id,
                   p.product_name,
                   p.product_slug,
                   p.base_price,
                   p.sale_price,
                   p.badge,
                   pi.image_url,
                   COUNT(DISTINCT pv.size_id) as size_count,
                   COUNT(DISTINCT pv.color_id) as color_count,
                   COALESCE(SUM(pv.stock_quantity), 0) as total_stock
                   FROM products p
                   LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                   LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_available = 1
                   WHERE p.is_active = 1 AND p.badge = 'new'
                   GROUP BY p.product_id, p.product_name, p.product_slug, p.base_price, p.sale_price, p.badge, pi.image_url
                   ORDER BY p.created_at DESC
                   LIMIT 4";

try {
    $newArrivalsStmt = $pdo->query($newArrivalsSql);
    $newArrivals = $newArrivalsStmt->fetchAll();
} catch (PDOException $e) {
    error_log("New arrivals query error: " . $e->getMessage());
    $newArrivals = [];
}

// Get bestsellers
$bestsellersSql = "SELECT 
                   p.product_id,
                   p.product_name,
                   p.product_slug,
                   p.base_price,
                   p.sale_price,
                   p.badge,
                   pi.image_url,
                   COUNT(DISTINCT pv.size_id) as size_count,
                   COUNT(DISTINCT pv.color_id) as color_count,
                   COALESCE(SUM(pv.stock_quantity), 0) as total_stock,
                   COUNT(DISTINCT oi.order_item_id) as order_count
                   FROM products p
                   LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                   LEFT JOIN product_variants pv ON p.product_id = pv.product_id AND pv.is_available = 1
                   LEFT JOIN order_items oi ON p.product_id = oi.product_id
                   WHERE p.is_active = 1
                   GROUP BY p.product_id, p.product_name, p.product_slug, p.base_price, p.sale_price, p.badge, pi.image_url
                   HAVING order_count > 0
                   ORDER BY order_count DESC
                   LIMIT 4";

try {
    $bestsellersStmt = $pdo->query($bestsellersSql);
    $bestsellers = $bestsellersStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Bestsellers query error: " . $e->getMessage());
    $bestsellers = [];
}

// Get main categories for display
$categoriesSql = "SELECT 
                  c.category_id,
                  c.category_name,
                  c.slug,
                  c.category_image,
                  COUNT(p.product_id) as product_count
                  FROM categories c
                  LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
                  WHERE c.parent_id IS NULL AND c.is_active = 1
                  GROUP BY c.category_id, c.category_name, c.slug, c.category_image
                  ORDER BY c.display_order
                  LIMIT 4";

try {
    $categoriesStmt = $pdo->query($categoriesSql);
    $mainCategories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories query error: " . $e->getMessage());
    $mainCategories = [];
}

// Include header component if it exists, otherwise use inline header
if (file_exists('includes/components/header.php')) {
    require_once 'includes/components/header.php';
} else {
    // Fallback: include basic header HTML
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?></title>
        <meta name="description" content="<?= $pageDescription ?>">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
        <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">
        <?php foreach ($additionalStyles as $style): ?>
            <link rel="stylesheet" href="<?= $style ?>">
        <?php endforeach; ?>
    </head>
    <body>
    <header class="header">
        <div class="header-top">
            <div class="container">
                <div class="header-top-content">
                    <span class="info-text">Kostenloser Versand ab 50€</span>
                </div>
            </div>
        </div>
        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <a href="<?= SITE_URL ?>" class="logo">
                        <span class="logo-text">ZIN Fashion</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <main id="main-content">
    <?php
}
?>

<!-- Hero Section with Slider -->
<section class="hero hero-slider" id="heroSlider">
    <div class="hero-slides">
        <!-- Slide 1 -->
        <div class="hero-slide active" style="background-image: url('<?= SITE_URL ?>/assets/images/hero-1.jpg')">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title animate-fade-up" data-i18n="hero-title-1">
                        Neue Kollektion 2025
                    </h1>
                    <p class="hero-subtitle animate-fade-up" data-delay="200" data-i18n="hero-subtitle-1">
                        Entdecken Sie Luxus in Ihrer Größe
                    </p>
                    <div class="hero-buttons animate-fade-up" data-delay="400">
                        <a href="<?= SITE_URL ?>/category/damen" class="btn btn-primary" data-i18n="shop-women">
                            Damen Shop
                        </a>
                        <a href="<?= SITE_URL ?>/category/herren" class="btn btn-secondary" data-i18n="shop-men">
                            Herren Shop
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="hero-slide" style="background-image: url('<?= SITE_URL ?>/assets/images/hero-2.jpg')">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h2 class="hero-title animate-fade-up">
                        Premium Plus Size Fashion
                    </h2>
                    <p class="hero-subtitle animate-fade-up" data-delay="200">
                        Große Größen, Großartige Styles
                    </p>
                    <div class="hero-buttons animate-fade-up" data-delay="400">
                        <a href="<?= SITE_URL ?>/sale" class="btn btn-primary">
                            Sale bis zu 50%
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="hero-slide" style="background-image: url('<?= SITE_URL ?>/assets/images/hero-3.jpg')">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h2 class="hero-title animate-fade-up">
                        Mode für die ganze Familie
                    </h2>
                    <p class="hero-subtitle animate-fade-up" data-delay="200">
                        Von XS bis 10XL - Für jeden die perfekte Größe
                    </p>
                    <div class="hero-buttons animate-fade-up" data-delay="400">
                        <a href="<?= SITE_URL ?>/shop" class="btn btn-primary">
                            Jetzt Entdecken
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Slider Controls -->
    <div class="hero-controls">
        <button class="hero-prev" onclick="changeSlide(-1)" aria-label="Previous slide">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-next" onclick="changeSlide(1)" aria-label="Next slide">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    
    <!-- Slider Dots -->
    <div class="hero-dots">
        <span class="dot active" onclick="currentSlide(1)"></span>
        <span class="dot" onclick="currentSlide(2)"></span>
        <span class="dot" onclick="currentSlide(3)"></span>
    </div>
</section>

<!-- Shop by Category -->
<section class="categories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" data-i18n="categories">Unsere Kollektionen</h2>
            <p class="section-subtitle" data-i18n="categories-subtitle">
                Entdecken Sie Premium Mode für die ganze Familie
            </p>
        </div>
        
        <div class="category-grid">
            <?php if (!empty($mainCategories)): ?>
                <?php foreach ($mainCategories as $category): ?>
                    <div class="category-card">
                        <a href="<?= SITE_URL ?>/category/<?= $category['slug'] ?>" class="category-link">
                            <div class="category-image" style="background-image: url('<?= SITE_URL ?>/assets/images/category-<?= $category['slug'] ?>.jpg')">
                                <div class="category-overlay">
                                    <h3 class="category-name"><?= htmlspecialchars($category['category_name']) ?></h3>
                                    <p class="category-count"><?= $category['product_count'] ?>+ Produkte</p>
                                    <span class="category-explore" data-i18n="explore">Entdecken →</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <p style="color: var(--text-secondary);">Kategorien werden geladen...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" data-i18n="featured-products">Ausgewählte Produkte</h2>
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all" data-i18n="all">Alle</button>
                <button class="filter-tab" data-filter="new" data-i18n="new">Neu</button>
                <button class="filter-tab" data-filter="sale" data-i18n="sale">Sale</button>
                <button class="filter-tab" data-filter="bestseller" data-i18n="bestseller">Bestseller</button>
            </div>
        </div>
        
        <div class="product-grid" id="productGrid">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card" data-category="<?= $product['badge'] ?? 'all' ?>">
                        <?php if (!empty($product['badge'])): ?>
                            <span class="product-badge badge-<?= $product['badge'] ?>">
                                <?= ucfirst($product['badge']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="wishlist-btn" 
                                    data-product-id="<?= $product['product_id'] ?>"
                                    aria-label="Add to wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                        <?php endif; ?>
                        
                        <a href="<?= SITE_URL ?>/product/<?= $product['product_slug'] ?>" class="product-link">
                            <div class="product-image">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="<?= $product['image_url'] ?>" 
                                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <img src="<?= SITE_URL ?>/assets/images/placeholder.jpg" 
                                         alt="<?= htmlspecialchars($product['product_name']) ?>"
                                         loading="lazy">
                                <?php endif; ?>
                                
                                <div class="product-overlay">
                                    <button class="btn btn-secondary quick-view" 
                                            data-product-id="<?= $product['product_id'] ?>">
                                        <i class="fas fa-eye"></i> 
                                        <span data-i18n="quick-view">Schnellansicht</span>
                                    </button>
                                </div>
                            </div>
                        </a>
                        
                        <div class="product-info">
                            <h3 class="product-name">
                                <a href="<?= SITE_URL ?>/product/<?= $product['product_slug'] ?>">
                                    <?= htmlspecialchars($product['product_name']) ?>
                                </a>
                            </h3>
                            
                            <div class="product-price">
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                    <span class="price original-price">€<?= number_format($product['base_price'], 2, ',', '.') ?></span>
                                    <span class="price sale-price">€<?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                                <?php else: ?>
                                    <span class="price">€<?= number_format($product['base_price'], 2, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['size_count'] > 0): ?>
                                <div class="product-sizes">
                                    <span class="sizes-label" data-i18n="sizes">Größen:</span>
                                    <span class="sizes-info"><?= $product['size_count'] ?> verfügbar</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($product['color_count'] > 0): ?>
                                <div class="product-colors">
                                    <span class="colors-label" data-i18n="colors">Farben:</span>
                                    <span class="colors-info"><?= $product['color_count'] ?> verfügbar</span>
                                </div>
                            <?php endif; ?>
                            
                            <button class="btn btn-primary add-to-cart" 
                                    data-product-id="<?= $product['product_id'] ?>"
                                    <?= ($product['total_stock'] <= 0) ? 'disabled' : '' ?>
                                    data-i18n="add-to-cart">
                                <i class="fas fa-shopping-bag"></i> 
                                <?= ($product['total_stock'] <= 0) ? 'Ausverkauft' : 'In den Warenkorb' ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-box-open" style="font-size: 48px; color: var(--text-tertiary); margin-bottom: 20px;"></i>
                    <h3 style="color: var(--text-secondary); margin-bottom: 10px;">Keine Produkte verfügbar</h3>
                    <p style="color: var(--text-tertiary);">Bitte fügen Sie Produkte über das Admin-Panel hinzu.</p>
                    <a href="<?= SITE_URL ?>/admin" class="btn btn-primary" style="margin-top: 20px;">Zum Admin-Panel</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($featuredProducts)): ?>
            <div class="text-center mt-4">
                <a href="<?= SITE_URL ?>/shop" class="btn btn-secondary btn-lg" data-i18n="view-all-products">
                    Alle Produkte ansehen
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us -->
<section class="features">
    <div class="container">
        <h2 class="section-title text-center" data-i18n="why-choose-us">
            Warum ZIN Fashion wählen?
        </h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-medal"></i>
                </div>
                <h3 data-i18n="premium-quality">Premium Qualität</h3>
                <p data-i18n="quality-text">
                    Hochwertige Materialien und erstklassige Verarbeitung
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3 data-i18n="fast-shipping">Schneller Versand</h3>
                <p data-i18n="shipping-text">
                    Kostenloser Versand ab 50€ Bestellwert
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 data-i18n="secure-payment">Sichere Zahlung</h3>
                <p data-i18n="payment-text">
                    Verschlüsselte und sichere Zahlungsmethoden
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3 data-i18n="easy-returns">Einfache Rückgabe</h3>
                <p data-i18n="returns-text">
                    30 Tage Rückgaberecht ohne Wenn und Aber
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 data-i18n="customer-support">24/7 Support</h3>
                <p data-i18n="support-text">
                    Unser Kundenservice ist immer für Sie da
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-ruler"></i>
                </div>
                <h3 data-i18n="size-range">Große Größenauswahl</h3>
                <p data-i18n="size-text">
                    Von XS bis 10XL - für jeden die perfekte Passform
                </p>
            </div>
        </div>
    </div>
</section>

<?php
// Page-specific JavaScript
$pageScripts = <<<'JS'
// Hero Slider
let currentSlideIndex = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

function changeSlide(direction) {
    currentSlideIndex = (currentSlideIndex + direction + slides.length) % slides.length;
    showSlide(currentSlideIndex);
}

function currentSlide(index) {
    currentSlideIndex = index - 1;
    showSlide(currentSlideIndex);
}

// Auto-play slider
if (slides.length > 0) {
    setInterval(() => {
        changeSlide(1);
    }, 5000);
}

// Product Filter Tabs
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const filter = this.dataset.filter;
        
        // Update active tab
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        // Filter products
        document.querySelectorAll('.product-card').forEach(product => {
            if (filter === 'all' || product.dataset.category === filter) {
                product.style.display = 'block';
            } else {
                product.style.display = 'none';
            }
        });
    });
});
JS;

// Include footer component if it exists, otherwise use basic footer
if (file_exists('includes/components/footer.php')) {
    require_once 'includes/components/footer.php';
} else {
    // Fallback: basic footer
    ?>
    </main>
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> ZIN Fashion. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script>
        <?= $pageScripts ?>
    </script>
    </body>
    </html>
    <?php
}
?>
