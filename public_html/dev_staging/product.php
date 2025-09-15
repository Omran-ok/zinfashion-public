<?php
/**
 * ZIN Fashion - Product Detail Page
 * Location: /public_html/dev_staging/product.php
 * Updated: Fixed category display to show actual product category instead of parent
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

$pdo = getDBConnection();

// Get product slug from URL
$productSlug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (!$productSlug) {
    header('Location: /shop');
    exit;
}

// Get product details with all translations
// Updated query to get both the direct category and parent category info
$productSql = "SELECT p.*, 
               c.category_id, c.category_name, c.category_name_en, c.category_name_ar, c.slug as category_slug, c.parent_id,
               pc.category_name as parent_category_name, 
               pc.category_name_en as parent_category_name_en, 
               pc.category_name_ar as parent_category_name_ar,
               pc.slug as parent_category_slug,
               sg.group_name, sg.group_name_en, sg.group_name_ar,
               (SELECT COUNT(*) FROM product_reviews pr WHERE pr.product_id = p.product_id AND pr.is_approved = 1) as review_count,
               (SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.product_id AND pr.is_approved = 1) as avg_rating
               FROM products p
               LEFT JOIN categories c ON p.category_id = c.category_id
               LEFT JOIN categories pc ON c.parent_id = pc.category_id
               LEFT JOIN size_groups sg ON p.size_group_id = sg.size_group_id
               WHERE p.product_slug = :slug AND p.is_active = 1";

$stmt = $pdo->prepare($productSql);
$stmt->execute(['slug' => $productSlug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: /shop');
    exit;
}

// Get product images
$imagesSql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, display_order";
$imagesStmt = $pdo->prepare($imagesSql);
$imagesStmt->execute(['product_id' => $product['product_id']]);
$images = $imagesStmt->fetchAll();

// Get product variants with sizes that have measurements
$variantsSql = "SELECT pv.*, s.size_name, s.size_order, s.measurements, c.color_name, c.color_name_en, c.color_name_ar, c.color_code
                FROM product_variants pv
                JOIN sizes s ON pv.size_id = s.size_id
                LEFT JOIN colors c ON pv.color_id = c.color_id
                WHERE pv.product_id = :product_id 
                AND pv.is_available = 1
                AND s.is_active = 1
                ORDER BY s.size_order, c.color_name";

$variantsStmt = $pdo->prepare($variantsSql);
$variantsStmt->execute(['product_id' => $product['product_id']]);
$variants = $variantsStmt->fetchAll();

// Group variants by size and color
$sizes = [];
$colors = [];
$variantMap = [];

foreach ($variants as $variant) {
    // Collect unique sizes with measurements
    if (!isset($sizes[$variant['size_id']])) {
        $sizes[$variant['size_id']] = [
            'name' => $variant['size_name'],
            'order' => $variant['size_order'],
            'measurements' => json_decode($variant['measurements'], true),
            'stock' => $variant['stock_quantity']
        ];
    }
    
    // Collect unique colors
    if ($variant['color_id'] && !isset($colors[$variant['color_id']])) {
        $colorName = $variant['color_name'];
        if ($currentLang === 'en' && $variant['color_name_en']) {
            $colorName = $variant['color_name_en'];
        } elseif ($currentLang === 'ar' && $variant['color_name_ar']) {
            $colorName = $variant['color_name_ar'];
        }
        
        $colors[$variant['color_id']] = [
            'name' => $colorName,
            'code' => $variant['color_code']
        ];
    }
    
    // Create variant map for JavaScript
    $variantKey = $variant['size_id'] . '-' . ($variant['color_id'] ?: '0');
    $variantMap[$variantKey] = [
        'variant_id' => $variant['variant_id'],
        'stock' => $variant['stock_quantity'],
        'price_adjustment' => $variant['price_adjustment']
    ];
}

// Sort sizes by order
uasort($sizes, function($a, $b) {
    return $a['order'] - $b['order'];
});

// Get first available variant if no colors
$defaultVariantId = null;
if (empty($colors) && !empty($variants)) {
    $defaultVariantId = $variants[0]['variant_id'];
}

// Get related products with proper image and category info
$relatedSql = "SELECT p.*, pi.image_url,
               c.category_name, c.category_name_en, c.category_name_ar, c.slug as category_slug
               FROM products p
               LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
               LEFT JOIN categories c ON p.category_id = c.category_id
               WHERE p.category_id = :category_id 
               AND p.product_id != :product_id 
               AND p.is_active = 1
               LIMIT 4";

$relatedStmt = $pdo->prepare($relatedSql);
$relatedStmt->execute([
    'category_id' => $product['category_id'],
    'product_id' => $product['product_id']
]);
$relatedProducts = $relatedStmt->fetchAll();

// Get approved reviews
$reviewsSql = "SELECT pr.*, u.first_name, u.last_name
               FROM product_reviews pr
               LEFT JOIN users u ON pr.user_id = u.user_id
               WHERE pr.product_id = :product_id AND pr.is_approved = 1
               ORDER BY pr.created_at DESC
               LIMIT 5";

$reviewsStmt = $pdo->prepare($reviewsSql);
$reviewsStmt->execute(['product_id' => $product['product_id']]);
$reviews = $reviewsStmt->fetchAll();

// Get translated content
$productName = $product['product_name'];
$productDescription = $product['description'];
$productCategoryName = $product['category_name'];  // This is the actual category (e.g., "Nachthemden")
$productParentCategoryName = $product['parent_category_name'];  // This is the parent (e.g., "Damen")
$sizeGroupName = $product['group_name'];

if ($currentLang === 'en') {
    if ($product['product_name_en']) $productName = $product['product_name_en'];
    if ($product['description_en']) $productDescription = $product['description_en'];
    if ($product['category_name_en']) $categoryName = $product['category_name_en'];
    if ($product['parent_category_name_en']) $parentCategoryName = $product['parent_category_name_en'];
    if ($product['group_name_en']) $sizeGroupName = $product['group_name_en'];
} elseif ($currentLang === 'ar') {
    if ($product['product_name_ar']) $productName = $product['product_name_ar'];
    if ($product['description_ar']) $productDescription = $product['description_ar'];
    if ($product['category_name_ar']) $categoryName = $product['category_name_ar'];
    if ($product['parent_category_name_ar']) $parentCategoryName = $product['parent_category_name_ar'];
    if ($product['group_name_ar']) $sizeGroupName = $product['group_name_ar'];
}

// Calculate price
$price = $product['sale_price'] ?: $product['base_price'];
$hasDiscount = $product['sale_price'] && $product['sale_price'] < $product['base_price'];
$discountPercent = $hasDiscount ? round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100) : 0;

// Check overall stock
$inStock = count($variants) > 0 && array_sum(array_column($variants, 'stock_quantity')) > 0;

// Prepare breadcrumbs
$breadcrumbs = [
    ['title' => $lang['shop'] ?? 'Shop', 'url' => '/shop']
];

// Add parent category if exists
if ($product['parent_id'] && $productParentCategoryName) {
    $breadcrumbs[] = [
        'title' => $productParentCategoryName,
        'url' => '/category/' . $product['parent_category_slug']
    ];
}

// Add product's direct category
$breadcrumbs[] = [
    'title' => $productCategoryName,
    'url' => '/category/' . $product['category_slug']
];

// Add product name (current page)
$breadcrumbs[] = [
    'title' => $productName,
    'url' => null // Current page
];

// Generate full product URL for sharing
$productUrl = SITE_URL . '/product/' . $productSlug;
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($productName) ?> - ZIN Fashion</title>
    <meta name="description" content="<?= htmlspecialchars($product['meta_description'] ?? substr($productDescription, 0, 160)) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($productName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr($productDescription, 0, 160)) ?>">
    <meta property="og:image" content="<?= SITE_URL . htmlspecialchars($images[0]['image_url'] ?? '/assets/images/placeholder.jpg') ?>">
    <meta property="og:url" content="<?= $productUrl ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.svg">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    
    <!-- Component Stylesheets -->
    <link rel="stylesheet" href="/assets/css/breadcrumb.css">
    <link rel="stylesheet" href="/assets/css/product.css">
    
    <!-- RTL Support -->
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <?php include 'includes/components/header.php'; ?>
    
    <!-- Breadcrumb Component -->
    <?php include 'includes/components/breadcrumb.php'; ?>
    
    <!-- Product Section -->
    <section class="product-section">
        <div class="container">
            <div class="product-container">
                
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="gallery-main">
                        <?php if ($product['badge']): ?>
                        <span class="product-badge badge-<?= htmlspecialchars($product['badge']) ?>">
                            <?php if ($product['badge'] === 'sale' && $discountPercent > 0): ?>
                                -<?= $discountPercent ?>%
                            <?php else: ?>
                                <?= $lang['badge_' . $product['badge']] ?? strtoupper($product['badge']) ?>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        
                        <div class="main-image-container">
                            <img id="mainImage" src="<?= htmlspecialchars($images[0]['image_url'] ?? '/assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($productName) ?>" class="main-image">
                            <button class="zoom-btn" id="zoomBtn">
                                <i class="fas fa-search-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="thumb-item <?= $index === 0 ? 'active' : '' ?>" data-image="<?= htmlspecialchars($image['image_url']) ?>">
                            <img src="<?= htmlspecialchars($image['image_url']) ?>" alt="<?= htmlspecialchars($image['alt_text'] ?? $productName) ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-meta">
                        <span class="sku"><?= $lang['sku'] ?? 'SKU' ?>: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></span>
                        <?php if ($product['avg_rating']): ?>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?= $i <= round($product['avg_rating']) ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                            <span>(<?= $product['review_count'] ?> <?= $lang['reviews'] ?? 'reviews' ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="product-title"><?= htmlspecialchars($productName) ?></h1>
                    
                    <div class="product-price">
                        <?php if ($hasDiscount): ?>
                        <span class="price-old">€<?= number_format($product['base_price'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                        <span class="price-current" id="currentPrice">€<?= number_format($price, 2, ',', '.') ?></span>
                        <?php if ($hasDiscount): ?>
                        <span class="discount-badge">-<?= $discountPercent ?>%</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-excerpt">
                        <?= nl2br(htmlspecialchars(substr($productDescription, 0, 200))) ?>...
                    </div>
                    
                    <form id="productForm" class="product-options">
                        <input type="hidden" name="product_id" id="productId" value="<?= $product['product_id'] ?>">
                        <input type="hidden" name="variant_id" id="variantId" value="<?= $defaultVariantId ?>">
                        
                        <?php if (!empty($colors)): ?>
                        <div class="option-group">
                            <label for="color-selection"><?= $lang['color'] ?? 'Color' ?>:</label>
                            <div class="color-options" id="color-selection">
                                <?php foreach ($colors as $colorId => $color): ?>
                                <label class="color-option">
                                    <input type="radio" name="color" value="<?= $colorId ?>" required>
                                    <span class="color-swatch" style="background: <?= htmlspecialchars($color['code'] ?: '#ccc') ?>;" 
                                          title="<?= htmlspecialchars($color['name']) ?>"></span>
                                    <span class="color-name"><?= htmlspecialchars($color['name']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sizes)): ?>
                        <div class="option-group">
                            <label for="size-selection"><?= $lang['size'] ?? 'Size' ?>:</label>
                            <button type="button" class="size-guide-link" onclick="showSizeGuide()">
                                <i class="fas fa-ruler"></i> <?= $lang['size_guide'] ?? 'Size Guide' ?>
                            </button>
                            <div class="size-options" id="size-selection">
                                <?php 
                                $firstSize = true;
                                foreach ($sizes as $sizeId => $size): 
                                    $isDisabled = isset($size['stock']) && $size['stock'] <= 0;
                                ?>
                                <label class="size-option">
                                    <input type="radio" name="size" value="<?= $sizeId ?>" 
                                           <?= $firstSize && !$isDisabled ? 'checked' : '' ?>
                                           <?= $isDisabled ? 'disabled' : '' ?> 
                                           required>
                                    <span class="size-label"><?= htmlspecialchars($size['name']) ?></span>
                                </label>
                                <?php 
                                    if (!$isDisabled) $firstSize = false;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Quantity Section -->
                        <div class="quantity-section">
                            <label for="quantity"><?= $lang['quantity'] ?? 'Quantity' ?>:</label>
                            <div class="quantity-selector">
                                <button type="button" class="qty-btn minus" onclick="updateQuantity(-1)">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="99" readonly>
                                <button type="button" class="qty-btn plus" onclick="updateQuantity(1)">+</button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="product-detail-actions">
                            <button type="submit" class="btn-detail-cart" <?= !$inStock ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i> 
                                <span id="addToCartText"><?= $lang['add_to_cart'] ?? 'Add to Cart' ?></span>
                            </button>
                            <button type="button" class="btn-detail-wishlist" data-product-id="<?= $product['product_id'] ?>">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        
                        <!-- Stock Info - Separate -->
                        <div class="stock-info">
                            <?php if ($inStock): ?>
                            <span class="in-stock"><i class="fas fa-check-circle"></i> <?= $lang['in_stock'] ?? 'In Stock' ?></span>
                            <span id="stockCount" class="stock-count"></span>
                            <?php else: ?>
                            <span class="out-of-stock"><i class="fas fa-times-circle"></i> <?= $lang['out_of_stock'] ?? 'Out of Stock' ?></span>
                            <?php endif; ?>
                        </div>
                    </form>
                    
                    <!-- Share Section -->
                    <div class="product-share">
                        <span class="share-label"><?= $lang['share'] ?? 'Share' ?>:</span>
                        <div class="share-buttons">
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($productUrl) ?>" 
                               target="_blank" class="share-btn facebook" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            
                            <!-- WhatsApp -->
                            <a href="https://wa.me/?text=<?= urlencode($productName . ' - ' . $productUrl) ?>" 
                               target="_blank" class="share-btn whatsapp" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            
                            <!-- Instagram (Copy link with message) -->
                            <button class="share-btn instagram" onclick="shareInstagram()" title="Share on Instagram">
                                <i class="fab fa-instagram"></i>
                            </button>
                            
                            <!-- TikTok (Copy link with message) -->
                            <button class="share-btn tiktok" onclick="shareTikTok()" title="Share on TikTok">
                                <i class="fab fa-tiktok"></i>
                            </button>
                            
                            <!-- Copy Link -->
                            <button class="share-btn copy-link" onclick="copyProductLink()" title="Copy Link">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Product Features -->
                    <div class="product-features">
                        <div class="feature">
                            <i class="fas fa-truck"></i>
                            <span><?= $lang['free_shipping_desc'] ?? 'Free shipping on orders over €50' ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-undo"></i>
                            <span><?= $lang['easy_returns_desc'] ?? '30 days return policy' ?></span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <span><?= $lang['secure_payment_desc'] ?? '100% secure payment' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Tabs -->
            <div class="product-tabs">
                <div class="tabs-nav">
                    <button class="tab-btn active" data-tab="description">
                        <?= $lang['description'] ?? 'Description' ?>
                    </button>
                    <button class="tab-btn" data-tab="details">
                        <?= $lang['product_details'] ?? 'Product Details' ?>
                    </button>
                    <?php if (!empty($sizes)): ?>
                    <button class="tab-btn" data-tab="size-guide">
                        <?= $lang['size_guide'] ?? 'Size Guide' ?>
                    </button>
                    <?php endif; ?>
                    <button class="tab-btn" data-tab="reviews">
                        <?= $lang['reviews'] ?? 'Reviews' ?> (<?= $product['review_count'] ?>)
                    </button>
                </div>
                
                <div class="tabs-content">
                    <!-- Description Tab -->
                    <div class="tab-pane active" id="description">
                        <div class="content-wrapper">
                            <?= nl2br(htmlspecialchars($productDescription)) ?>
                        </div>
                    </div>
                    
                    <!-- Details Tab - FIXED to show correct category -->
                    <div class="tab-pane" id="details">
                        <div class="content-wrapper">
                            <table class="details-table">
                                <tr>
                                    <td><?= $lang['sku'] ?? 'SKU' ?>:</td>
                                    <td><?= htmlspecialchars($product['sku'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <td><?= $lang['category'] ?? 'Category' ?>:</td>
                                    <td>
                                        <?php if ($productParentCategoryName): ?>
                                            <?= htmlspecialchars($productParentCategoryName) ?> / 
                                        <?php endif; ?>
                                        <?= htmlspecialchars($productCategoryName) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><?= $lang['material'] ?? 'Material' ?>:</td>
                                    <td><?= htmlspecialchars($product['material' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $product['material']) ?></td>
                                </tr>
                                <?php if ($product['care_instructions']): ?>
                                <tr>
                                    <td><?= $lang['care_instructions'] ?? 'Care Instructions' ?>:</td>
                                    <td><?= nl2br(htmlspecialchars($product['care_instructions'])) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Size Guide Tab -->
                    <?php if (!empty($sizes)): ?>
                    <div class="tab-pane" id="size-guide">
                        <div class="content-wrapper">
                            <h3><?= $sizeGroupName ?: ($lang['size_guide'] ?? 'Size Guide') ?></h3>
                            <div class="size-guide-content">
                                <table class="size-chart">
                                    <thead>
                                        <tr>
                                            <th><?= $lang['size'] ?? 'Size' ?></th>
                                            <?php 
                                            // Get measurement keys from first size
                                            $firstSize = reset($sizes);
                                            $measurementKeys = $firstSize['measurements'] ? array_keys($firstSize['measurements']) : [];
                                            foreach ($measurementKeys as $key): 
                                            ?>
                                            <th><?= ucfirst(str_replace('_', ' ', $key)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sizes as $size): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($size['name']) ?></strong></td>
                                            <?php foreach ($measurementKeys as $key): ?>
                                            <td>
                                                <?php 
                                                $value = $size['measurements'][$key] ?? '-';
                                                if (is_numeric($value)) {
                                                    echo $value . (strpos($key, 'weight') !== false ? ' kg' : ' cm');
                                                } else {
                                                    echo htmlspecialchars($value);
                                                }
                                                ?>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div class="size-guide-tips">
                                    <h4><?= $lang['how_to_measure'] ?? 'How to Measure' ?></h4>
                                    <ul>
                                        <li><?= $lang['measure_tip_1'] ?? 'Use a flexible measuring tape' ?></li>
                                        <li><?= $lang['measure_tip_2'] ?? 'Stand straight and relaxed' ?></li>
                                        <li><?= $lang['measure_tip_3'] ?? 'For best fit, measure over undergarments' ?></li>
                                        <li><?= $lang['measure_tip_4'] ?? 'If between sizes, choose the larger size' ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reviews Tab -->
                    <div class="tab-pane" id="reviews">
                        <div class="content-wrapper">
                            <?php if (!empty($reviews)): ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <strong><?= htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.') ?></strong>
                                            <?php if ($review['is_verified_purchase']): ?>
                                            <span class="verified"><i class="fas fa-check-circle"></i> <?= $lang['verified_purchase'] ?? 'Verified Purchase' ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="review-date"><?= date('d.m.Y', strtotime($review['created_at'])) ?></div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['review_title']): ?>
                                    <h4 class="review-title"><?= htmlspecialchars($review['review_title']) ?></h4>
                                    <?php endif; ?>
                                    <p class="review-text"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <p class="no-reviews"><?= $lang['no_reviews_yet'] ?? 'No reviews yet. Be the first to review this product!' ?></p>
                            <?php endif; ?>
                            
                            <?php if (isLoggedIn()): ?>
                            <button class="btn btn-outline" onclick="openReviewForm()">
                                <?= $lang['write_review'] ?? 'Write a Review' ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <h2><?= $lang['related_products'] ?? 'Related Products' ?></h2>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $related): 
                        // Get translated names
                        $relatedName = $related['product_name'];
                        $relatedCategoryName = $related['category_name'];
                        
                        if ($currentLang === 'en') {
                            if ($related['product_name_en']) $relatedName = $related['product_name_en'];
                            if ($related['category_name_en']) $relatedCategoryName = $related['category_name_en'];
                        } elseif ($currentLang === 'ar') {
                            if ($related['product_name_ar']) $relatedName = $related['product_name_ar'];
                            if ($related['category_name_ar']) $relatedCategoryName = $related['category_name_ar'];
                        }
                        
                        $relatedPrice = $related['sale_price'] ?: $related['base_price'];
                        $relatedOriginalPrice = $related['sale_price'] ? $related['base_price'] : null;
                        $relatedDiscountPercent = $relatedOriginalPrice ? round((($relatedOriginalPrice - $relatedPrice) / $relatedOriginalPrice) * 100) : 0;
                    ?>
                    <div class="product-card">
                        <?php if ($related['badge']): ?>
                        <span class="product-badge badge-<?= htmlspecialchars($related['badge']) ?>">
                            <?php if ($related['badge'] === 'sale' && $relatedDiscountPercent > 0): ?>
                                -<?= $relatedDiscountPercent ?>%
                            <?php else: ?>
                                <?= $lang['badge_' . $related['badge']] ?? strtoupper($related['badge']) ?>
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                        
                        <div class="product-image">
                            <a href="/product/<?= htmlspecialchars($related['product_slug']) ?>">
                                <img src="<?= htmlspecialchars($related['image_url'] ?? '/assets/images/placeholder.jpg') ?>" 
                                     alt="<?= htmlspecialchars($relatedName) ?>" loading="lazy">
                            </a>
                            <div class="product-actions">
                                <button class="btn-icon add-to-wishlist" data-product-id="<?= $related['product_id'] ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                                <button class="btn-icon quick-view" data-product-id="<?= $related['product_id'] ?>">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <p class="product-category"><?= htmlspecialchars($relatedCategoryName) ?></p>
                            <h3 class="product-name">
                                <a href="/product/<?= htmlspecialchars($related['product_slug']) ?>">
                                    <?= htmlspecialchars($relatedName) ?>
                                </a>
                            </h3>
                            <div class="product-price">
                                <?php if ($relatedOriginalPrice): ?>
                                <span class="price-old">€<?= number_format($relatedOriginalPrice, 2, ',', '.') ?></span>
                                <?php endif; ?>
                                <span class="price-current">€<?= number_format($relatedPrice, 2, ',', '.') ?></span>
                            </div>
                            <button class="btn btn-add-to-cart" data-product-id="<?= $related['product_id'] ?>" type="button">
                                <i class="fas fa-shopping-cart"></i> <?= $lang['add_to_cart'] ?? 'Add to Cart' ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include 'includes/components/footer.php'; ?>
    
    <!-- JavaScript Data - FIXED: Using window.productData -->
    <script>
        // Make productData globally available
        window.productData = {
            productId: <?= $product['product_id'] ?>,
            productName: '<?= addslashes($productName) ?>',
            productUrl: '<?= addslashes($productUrl) ?>',
            basePrice: <?= $price ?>,
            variantMap: <?= json_encode($variantMap) ?>,
            hasColors: <?= !empty($colors) ? 'true' : 'false' ?>,
            defaultVariantId: <?= $defaultVariantId ?: 'null' ?>,
            translations: {
                selectSize: '<?= addslashes($lang['select_size'] ?? 'Please select a size') ?>',
                selectColor: '<?= addslashes($lang['select_color'] ?? 'Please select a color') ?>',
                inStock: '<?= addslashes($lang['in_stock'] ?? 'In Stock') ?>',
                outOfStock: '<?= addslashes($lang['out_of_stock'] ?? 'Out of Stock') ?>',
                onlyLeft: '<?= addslashes($lang['only_left'] ?? 'Only {count} left') ?>',
                addToCart: '<?= addslashes($lang['add_to_cart'] ?? 'Add to Cart') ?>',
                linkCopied: '<?= addslashes($lang['link_copied'] ?? 'Link copied to clipboard!') ?>',
                shareInstagram: '<?= addslashes($lang['share_instagram'] ?? 'Link copied! Share it on Instagram Stories or in your bio.') ?>',
                shareTikTok: '<?= addslashes($lang['share_tiktok'] ?? 'Link copied! Share it on TikTok or in your bio.') ?>'
            }
        };
        
        // Check if user is logged in
        window.isLoggedIn = <?= isLoggedIn() ? 'true' : 'false' ?>;
        
        // Social sharing functions
        function copyProductLink() {
            navigator.clipboard.writeText(window.productData.productUrl).then(() => {
                if (window.showNotification) {
                    window.showNotification(window.productData.translations.linkCopied, 'success');
                }
            }).catch(() => {
                // Fallback for older browsers
                const input = document.createElement('input');
                input.value = window.productData.productUrl;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                if (window.showNotification) {
                    window.showNotification(window.productData.translations.linkCopied, 'success');
                }
            });
        }
        
        function shareInstagram() {
            copyProductLink();
            setTimeout(() => {
                if (window.showNotification) {
                    window.showNotification(window.productData.translations.shareInstagram, 'info');
                }
            }, 1500);
        }
        
        function shareTikTok() {
            copyProductLink();
            setTimeout(() => {
                if (window.showNotification) {
                    window.showNotification(window.productData.translations.shareTikTok, 'info');
                }
            }, 1500);
        }
        
        // Initialize variant selection if no colors (single variant products)
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (empty($colors) && !empty($sizes)): ?>
            // Trigger variant update for the first selected size
            const firstSize = document.querySelector('input[name="size"]:checked');
            if (firstSize) {
                const event = new Event('change');
                firstSize.dispatchEvent(event);
            }
            <?php endif; ?>
        });
    </script>
    
    <!-- Scripts -->
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/product.js"></script>
</body>
</html>
