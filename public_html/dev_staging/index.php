<?php
/**
 * ZIN Fashion - Homepage
 * Location: /public_html/dev_staging/index.php
 * Updated: Fixed category name translations in product cards
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

$pdo = getDBConnection();

// Get featured products with category translations
$featuredSql = "SELECT p.*, pi.image_url, 
                c.category_name, 
                c.category_name_en, 
                c.category_name_ar,
                c.slug as category_slug
                FROM products p
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.is_featured = 1 AND p.is_active = 1
                ORDER BY 
                    CASE 
                        WHEN p.badge = 'sale' THEN 1
                        WHEN p.badge = 'new' THEN 2
                        WHEN p.badge = 'bestseller' THEN 3
                        ELSE 4
                    END,
                    p.created_at DESC
                LIMIT 20";
$featuredStmt = $pdo->query($featuredSql);
$featuredProducts = $featuredStmt->fetchAll();

// Get main categories with product count
$categoriesSql = "SELECT c.*, 
                  (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id AND p.is_active = 1) as product_count
                  FROM categories c
                  WHERE c.parent_id IS NULL AND c.is_active = 1
                  ORDER BY c.display_order";
$categoriesStmt = $pdo->query($categoriesSql);
$mainCategories = $categoriesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $lang['meta_description'] ?? 'ZIN Fashion - Premium Fashion Store in Germany' ?>">
    <meta name="keywords" content="fashion, clothing, mode, kleidung, germany, deutschland">
    <title><?= $lang['site_title'] ?? 'ZIN Fashion - Home' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.svg">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Main Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    
    <!-- RTL Support -->
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <!-- Include Header -->
    <?php include 'includes/components/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-slider" id="heroSlider">
            <div class="slide active" style="background-image: url('/assets/images/hero/slide1.jpg')"></div>
            <div class="slide" style="background-image: url('/assets/images/hero/slide2.jpg')"></div>
            <div class="slide" style="background-image: url('/assets/images/hero/slide3.jpg')"></div>
        </div>
        
        <div class="hero-content">
            <div class="glass-box">
                <h1 class="hero-title animate-fade-up"><?= $lang['hero_title'] ?? 'Welcome to ZIN Fashion' ?></h1>
                <p class="hero-subtitle animate-fade-up-delay"><?= $lang['hero_subtitle'] ?? 'Discover Premium Fashion' ?></p>
                <div class="hero-buttons animate-fade-up-delay-2">
                    <a href="/shop" class="btn btn-primary"><?= $lang['shop_now'] ?? 'Shop Now' ?></a>
                    <a href="/sale" class="btn btn-outline"><?= $lang['view_sale'] ?? 'View Sale' ?></a>
                </div>
            </div>
        </div>
        
        <div class="hero-dots">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['shop_by_category'] ?? 'Shop by Category' ?></h2>
            <div class="categories-grid">
                <?php foreach ($mainCategories as $category): 
                    // Get translated category name
                    $categoryName = $category['category_name'];
                    if ($currentLang === 'en' && !empty($category['category_name_en'])) {
                        $categoryName = $category['category_name_en'];
                    } elseif ($currentLang === 'ar' && !empty($category['category_name_ar'])) {
                        $categoryName = $category['category_name_ar'];
                    }
                ?>
                <div class="category-card animate-on-scroll">
                    <a href="/category/<?= htmlspecialchars($category['slug']) ?>">
                        <div class="category-image" style="background-image: url('/assets/images/categories/<?= htmlspecialchars($category['slug']) ?>.jpg')">
                            <div class="category-overlay">
                                <h3><?= htmlspecialchars($categoryName) ?></h3>
                                <span class="product-count"><?= $category['product_count'] ?> <?= $lang['products'] ?? 'Products' ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Products Section with Badge Filtering -->
    <section class="featured-products-section">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['featured_products'] ?? 'Featured Products' ?></h2>
            
            <!-- Badge Filter Buttons -->
            <div class="product-filters">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-th"></i> <?= $lang['filter_all'] ?? 'All' ?>
                </button>
                <button class="filter-btn" data-filter="new">
                    <i class="fas fa-star"></i> <?= $lang['filter_new'] ?? 'New' ?>
                </button>
                <button class="filter-btn" data-filter="sale">
                    <i class="fas fa-tag"></i> <?= $lang['filter_sale'] ?? 'Sale' ?>
                </button>
                <button class="filter-btn" data-filter="bestseller">
                    <i class="fas fa-fire"></i> <?= $lang['filter_bestseller'] ?? 'Bestseller' ?>
                </button>
            </div>
            
            <div class="products-grid" id="featuredProductsGrid">
                <?php foreach ($featuredProducts as $product): 
                    // Get translated product name
                    $productName = $product['product_name'];
                    if ($currentLang === 'en' && !empty($product['product_name_en'])) {
                        $productName = $product['product_name_en'];
                    } elseif ($currentLang === 'ar' && !empty($product['product_name_ar'])) {
                        $productName = $product['product_name_ar'];
                    }
                    
                    // Get translated category name
                    $categoryName = $product['category_name'];
                    if ($currentLang === 'en' && !empty($product['category_name_en'])) {
                        $categoryName = $product['category_name_en'];
                    } elseif ($currentLang === 'ar' && !empty($product['category_name_ar'])) {
                        $categoryName = $product['category_name_ar'];
                    }
                    
                    $price = $product['sale_price'] ?: $product['base_price'];
                    $originalPrice = $product['sale_price'] ? $product['base_price'] : null;
                    $discountPercent = $originalPrice ? round((($originalPrice - $price) / $originalPrice) * 100) : 0;
                ?>
                <div class="product-card animate-on-scroll" data-badge="<?= htmlspecialchars($product['badge'] ?? 'none') ?>">
                    <?php if ($product['badge']): ?>
                    <span class="product-badge badge-<?= htmlspecialchars($product['badge']) ?>">
                        <?php if ($product['badge'] === 'sale' && $discountPercent > 0): ?>
                            -<?= $discountPercent ?>%
                        <?php else: ?>
                            <?= $lang['badge_' . $product['badge']] ?? strtoupper($product['badge']) ?>
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <a href="/product/<?= htmlspecialchars($product['product_slug']) ?>">
                            <img src="<?= htmlspecialchars($product['image_url'] ?? '/assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($productName) ?>" loading="lazy">
                        </a>
                        <div class="product-actions">
                            <button class="btn-icon add-to-wishlist" data-product-id="<?= $product['product_id'] ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            <button class="btn-icon quick-view" data-product-id="<?= $product['product_id'] ?>">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <p class="product-category"><?= htmlspecialchars($categoryName) ?></p>
                        <h3 class="product-name">
                            <a href="/product/<?= htmlspecialchars($product['product_slug']) ?>">
                                <?= htmlspecialchars($productName) ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <?php if ($originalPrice): ?>
                            <span class="price-old">€<?= number_format($originalPrice, 2, ',', '.') ?></span>
                            <?php endif; ?>
                            <span class="price-current">€<?= number_format($price, 2, ',', '.') ?></span>
                        </div>
                        <button class="btn btn-add-to-cart" data-product-id="<?= $product['product_id'] ?>">
                            <i class="fas fa-shopping-cart"></i> <?= $lang['add_to_cart'] ?? 'Add to Cart' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <a href="/shop" class="btn btn-primary btn-large animate-on-scroll">
                    <?= $lang['view_all_products'] ?? 'View All Products' ?>
                </a>
            </div>
        </div>
    </section>
    
    <!-- Why Choose Section -->
    <section class="why-choose-section">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['why_choose_zin'] ?? 'Why Choose ZIN Fashion' ?></h2>
            <div class="features-grid">
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3><?= $lang['free_shipping'] ?? 'Free Shipping' ?></h3>
                    <p><?= $lang['free_shipping_desc'] ?? 'Free delivery on orders over €50' ?></p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3><?= $lang['secure_payment'] ?? 'Secure Payment' ?></h3>
                    <p><?= $lang['secure_payment_desc'] ?? '100% secure transactions' ?></p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-undo"></i>
                    </div>
                    <h3><?= $lang['easy_returns'] ?? 'Easy Returns' ?></h3>
                    <p><?= $lang['easy_returns_desc'] ?? '30 days return policy' ?></p>
                </div>
                <div class="feature-card animate-on-scroll">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3><?= $lang['customer_support'] ?? '24/7 Support' ?></h3>
                    <p><?= $lang['customer_support_desc'] ?? 'Dedicated customer service' ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content animate-on-scroll">
                <h2><?= $lang['newsletter_title'] ?? 'Subscribe to Our Newsletter' ?></h2>
                <p><?= $lang['newsletter_subtitle'] ?? 'Get exclusive offers and be the first to know about new arrivals' ?></p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" name="email" placeholder="<?= $lang['enter_email'] ?? 'Enter your email' ?>" required>
                    <button type="submit" class="btn btn-newsletter">
                        <?= $lang['subscribe'] ?? 'Subscribe' ?>
                    </button>
                </form>
                <div class="newsletter-privacy">
                    <i class="fas fa-lock"></i>
                    <?= $lang['privacy_notice'] ?? 'We respect your privacy' ?>
                </div>
                <div class="newsletter-message" id="newsletterMessage"></div>
            </div>
        </div>
    </section>
    
    <!-- Include Footer -->
    <?php include 'includes/components/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/animations.js"></script>
    <script src="/assets/js/slider.js"></script>
    <script src="/assets/js/product-filters.js"></script>
</body>
</html>
