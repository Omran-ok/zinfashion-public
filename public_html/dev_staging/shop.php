<?php
/**
 * ZIN Fashion - Shop Page (Production Ready)
 * Location: /public_html/dev_staging/shop.php
 * 
 * Main product catalog page with filtering, sorting, and pagination
 * Updated: Using independent breadcrumb.css
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

$pdo = getDBConnection();

// Get query parameters with sanitization
$categorySlug = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$badge = isset($_GET['badge']) ? sanitizeInput($_GET['badge']) : null;

$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// Build the main query
$sql = "SELECT DISTINCT p.*, 
        pi.image_url,
        c.category_name, c.category_name_en, c.category_name_ar, c.slug as category_slug,
        COALESCE(p.sale_price, p.base_price) as final_price
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1";

$params = [];

// Category filter - handle both direct category and parent category
if ($categorySlug) {
    $sql .= " AND (c.slug = :category OR c.parent_id IN (SELECT category_id FROM categories WHERE slug = :category_parent))";
    $params['category'] = $categorySlug;
    $params['category_parent'] = $categorySlug;
}

// Search filter
if ($search) {
    $sql .= " AND (p.product_name LIKE :search OR p.product_name_en LIKE :search_en OR p.product_name_ar LIKE :search_ar OR p.description LIKE :search_desc)";
    $searchTerm = '%' . $search . '%';
    $params['search'] = $searchTerm;
    $params['search_en'] = $searchTerm;
    $params['search_ar'] = $searchTerm;
    $params['search_desc'] = $searchTerm;
}

// Price filter
if ($minPrice !== null) {
    $sql .= " AND COALESCE(p.sale_price, p.base_price) >= :min_price";
    $params['min_price'] = $minPrice;
}
if ($maxPrice !== null) {
    $sql .= " AND COALESCE(p.sale_price, p.base_price) <= :max_price";
    $params['max_price'] = $maxPrice;
}

// Badge filter
if ($badge) {
    $sql .= " AND p.badge = :badge";
    $params['badge'] = $badge;
}

// Count total products with same filters
$countSql = "SELECT COUNT(DISTINCT p.product_id) 
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.category_id
             WHERE p.is_active = 1";

// Apply same filters to count query
if ($categorySlug) {
    $countSql .= " AND (c.slug = :category OR c.parent_id IN (SELECT category_id FROM categories WHERE slug = :category_parent))";
}
if ($search) {
    $countSql .= " AND (p.product_name LIKE :search OR p.product_name_en LIKE :search_en OR p.product_name_ar LIKE :search_ar OR p.description LIKE :search_desc)";
}
if ($minPrice !== null) {
    $countSql .= " AND COALESCE(p.sale_price, p.base_price) >= :min_price";
}
if ($maxPrice !== null) {
    $countSql .= " AND COALESCE(p.sale_price, p.base_price) <= :max_price";
}
if ($badge) {
    $countSql .= " AND p.badge = :badge";
}

// Execute count query
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Add sorting to main query
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY final_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY final_price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.product_name ASC";
        break;
    case 'bestseller':
        $sql .= " ORDER BY p.badge = 'bestseller' DESC, p.created_at DESC";
        break;
    case 'sale':
        $sql .= " ORDER BY p.sale_price IS NOT NULL DESC, p.created_at DESC";
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC";
}

// Add pagination
$sql .= " LIMIT :limit OFFSET :offset";

// Execute main query
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get ALL categories with proper product counts
$catSql = "SELECT 
    c.*,
    (SELECT COUNT(DISTINCT p.product_id) 
     FROM products p 
     WHERE p.category_id = c.category_id 
     AND p.is_active = 1) as direct_product_count,
    (SELECT COUNT(DISTINCT p.product_id) 
     FROM products p 
     JOIN categories sub ON p.category_id = sub.category_id
     WHERE (sub.category_id = c.category_id OR sub.parent_id = c.category_id)
     AND p.is_active = 1) as total_product_count
    FROM categories c
    WHERE c.is_active = 1
    ORDER BY c.parent_id, c.display_order, c.category_name";

$catStmt = $pdo->query($catSql);
$categories = $catStmt->fetchAll();

// Get current category info if filtered
$currentCategory = null;
if ($categorySlug) {
    $catInfoSql = "SELECT * FROM categories WHERE slug = :slug AND is_active = 1";
    $catInfoStmt = $pdo->prepare($catInfoSql);
    $catInfoStmt->execute(['slug' => $categorySlug]);
    $currentCategory = $catInfoStmt->fetch();
}

// Get price range for filters
$priceSql = "SELECT 
    FLOOR(MIN(COALESCE(sale_price, base_price))) as min_price, 
    CEIL(MAX(COALESCE(sale_price, base_price))) as max_price 
    FROM products WHERE is_active = 1";
$priceStmt = $pdo->query($priceSql);
$priceRange = $priceStmt->fetch();

// Get total products count for "All Categories"
$allProductsSql = "SELECT COUNT(*) FROM products WHERE is_active = 1";
$allProductsCount = $pdo->query($allProductsSql)->fetchColumn();

// Prepare breadcrumbs
$breadcrumbs = [
    ['title' => $lang['shop'] ?? 'Shop', 'url' => '/shop']
];

if ($currentCategory) {
    // Check if it has a parent category
    if ($currentCategory['parent_id']) {
        $parentSql = "SELECT * FROM categories WHERE category_id = :parent_id";
        $parentStmt = $pdo->prepare($parentSql);
        $parentStmt->execute(['parent_id' => $currentCategory['parent_id']]);
        $parentCategory = $parentStmt->fetch();
        
        if ($parentCategory) {
            $parentName = $parentCategory['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $parentCategory['category_name'];
            $breadcrumbs[] = [
                'title' => $parentName,
                'url' => '/shop?category=' . $parentCategory['slug']
            ];
        }
    }
    
    // Add current category
    $categoryName = $currentCategory['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $currentCategory['category_name'];
    $breadcrumbs[] = [
        'title' => $categoryName,
        'url' => null // Current page
    ];
}

// Function to get translated badge name
function getTranslatedBadgeName($badge, $lang) {
    switch($badge) {
        case 'new':
            return $lang['new_arrivals'] ?? 'New Arrivals';
        case 'sale':
            return $lang['on_sale'] ?? 'On Sale';
        case 'bestseller':
            return $lang['bestsellers'] ?? 'Bestsellers';
        default:
            return ucfirst($badge);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $currentCategory ? htmlspecialchars($currentCategory['category_name']) . ' - ' : '' ?><?= $lang['shop'] ?? 'Shop' ?> - ZIN Fashion</title>
    <meta name="description" content="<?= $lang['shop_meta_description'] ?? 'Browse our collection of premium fashion products at ZIN Fashion' ?>">
    
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
    <link rel="stylesheet" href="/assets/css/shop.css">
    
    <!-- RTL Support -->
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <?php include 'includes/components/header.php'; ?>
    
    <!-- Breadcrumb Component -->
    <?php include 'includes/components/breadcrumb.php'; ?>
    
    <!-- Shop Section -->
    <section class="shop-section">
        <div class="container">
            <div class="shop-layout">
                
                <!-- Sidebar -->
                <aside class="shop-sidebar">
                    <!-- Categories -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title"><?= $lang['categories'] ?? 'Categories' ?></h3>
                        <ul class="category-list">
                            <li class="<?= !$categorySlug ? 'active' : '' ?>">
                                <a href="/shop">
                                    <?= $lang['all_categories'] ?? 'All Categories' ?> 
                                    <span class="count">(<?= $allProductsCount ?>)</span>
                                </a>
                            </li>
                            <?php 
                            // Separate main and sub categories
                            $mainCategories = array_filter($categories, function($cat) { 
                                return $cat['parent_id'] == null; 
                            });
                            
                            foreach ($mainCategories as $mainCat): 
                                $catName = $mainCat['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $mainCat['category_name'];
                                $isActive = $categorySlug === $mainCat['slug'];
                                $productCount = $mainCat['total_product_count'] ?? 0;
                                
                                // Get subcategories for this main category
                                $subCategories = array_filter($categories, function($cat) use ($mainCat) { 
                                    return $cat['parent_id'] == $mainCat['category_id']; 
                                });
                            ?>
                            <li class="<?= $isActive ? 'active' : '' ?>">
                                <a href="/shop?category=<?= htmlspecialchars($mainCat['slug']) ?>">
                                    <?= htmlspecialchars($catName) ?> 
                                    <span class="count">(<?= $productCount ?>)</span>
                                </a>
                                <?php if (!empty($subCategories)): ?>
                                <ul class="subcategory-list">
                                    <?php foreach ($subCategories as $subCat): 
                                        $subCatName = $subCat['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $subCat['category_name'];
                                        $subProductCount = $subCat['direct_product_count'] ?? 0;
                                        $isSubActive = $categorySlug === $subCat['slug'];
                                    ?>
                                    <li class="<?= $isSubActive ? 'active' : '' ?>">
                                        <a href="/shop?category=<?= htmlspecialchars($subCat['slug']) ?>">
                                            <?= htmlspecialchars($subCatName) ?> 
                                            <span class="count">(<?= $subProductCount ?>)</span>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Price Filter -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title"><?= $lang['price_range'] ?? 'Price Range' ?></h3>
                        <div class="price-filter">
                            <div class="price-range-info">
                                <span>€<?= $priceRange['min_price'] ?? 0 ?> - €<?= $priceRange['max_price'] ?? 999 ?></span>
                            </div>
                            <div class="price-inputs">
                                <input type="number" id="minPrice" placeholder="<?= $lang['min'] ?? 'Min' ?>" 
                                       value="<?= $minPrice ?>" 
                                       min="<?= $priceRange['min_price'] ?? 0 ?>" 
                                       max="<?= $priceRange['max_price'] ?? 999 ?>">
                                <span>-</span>
                                <input type="number" id="maxPrice" placeholder="<?= $lang['max'] ?? 'Max' ?>" 
                                       value="<?= $maxPrice ?>" 
                                       min="<?= $priceRange['min_price'] ?? 0 ?>" 
                                       max="<?= $priceRange['max_price'] ?? 999 ?>">
                            </div>
                            <button class="btn btn-filter" onclick="applyPriceFilter()">
                                <?= $lang['filter'] ?? 'Filter' ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Badge Filter -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title"><?= $lang['special_offers'] ?? 'Special Offers' ?></h3>
                        <div class="badge-filters">
                            <a href="?<?= http_build_query(array_merge($_GET, ['badge' => 'new', 'page' => 1])) ?>" 
                               class="badge-filter <?= $badge === 'new' ? 'active' : '' ?>">
                                <span class="badge-icon">🆕</span> 
                                <?= $lang['new_arrivals'] ?? 'New Arrivals' ?>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['badge' => 'sale', 'page' => 1])) ?>" 
                               class="badge-filter <?= $badge === 'sale' ? 'active' : '' ?>">
                                <span class="badge-icon">🏷️</span> 
                                <?= $lang['on_sale'] ?? 'On Sale' ?>
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['badge' => 'bestseller', 'page' => 1])) ?>" 
                               class="badge-filter <?= $badge === 'bestseller' ? 'active' : '' ?>">
                                <span class="badge-icon">🔥</span> 
                                <?= $lang['bestsellers'] ?? 'Bestsellers' ?>
                            </a>
                            <?php if ($badge): ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['badge' => ''])) ?>" class="clear-filter">
                                <i class="fas fa-times"></i> <?= $lang['clear'] ?? 'Clear' ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>
                
                <!-- Main Content -->
                <div class="shop-main">
                    <!-- Shop Header -->
                    <div class="shop-header">
                        <div class="shop-title">
                            <h1><?= $currentCategory ? htmlspecialchars($currentCategory['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $currentCategory['category_name']) : ($lang['all_products'] ?? 'All Products') ?></h1>
                            <p class="product-count"><?= $totalProducts ?> <?= $lang['products_found'] ?? 'products found' ?></p>
                        </div>
                        
                        <div class="shop-controls">
                            <!-- View Mode -->
                            <div class="view-mode">
                                <button class="view-btn active" data-view="grid" title="<?= $lang['grid_view'] ?? 'Grid View' ?>">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="view-btn" data-view="list" title="<?= $lang['list_view'] ?? 'List View' ?>">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                            
                            <!-- Sort -->
                            <div class="sort-control">
                                <select id="sortSelect" onchange="updateSort(this.value)">
                                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= $lang['sort_newest'] ?? 'Newest' ?></option>
                                    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= $lang['sort_price_low'] ?? 'Price: Low to High' ?></option>
                                    <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= $lang['sort_price_high'] ?? 'Price: High to Low' ?></option>
                                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>><?= $lang['sort_name'] ?? 'Name: A-Z' ?></option>
                                    <option value="sale" <?= $sort === 'sale' ? 'selected' : '' ?>><?= $lang['sort_sale'] ?? 'Sale Items' ?></option>
                                    <option value="bestseller" <?= $sort === 'bestseller' ? 'selected' : '' ?>><?= $lang['sort_bestseller'] ?? 'Bestsellers' ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Filters -->
                    <?php if ($search || $minPrice || $maxPrice || $badge || $categorySlug): ?>
                    <div class="active-filters">
                        <span><?= $lang['active_filters'] ?? 'Active Filters:' ?></span>
                        
                        <?php if ($search): ?>
                        <span class="filter-tag">
                            <?= $lang['search'] ?? 'Search' ?>: <?= htmlspecialchars($search) ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['search' => ''])) ?>" class="remove-filter">×</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($categorySlug && $currentCategory): 
                            $displayCategoryName = $currentCategory['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $currentCategory['category_name'];
                        ?>
                        <span class="filter-tag">
                            <?= $lang['category'] ?? 'Category' ?>: <?= htmlspecialchars($displayCategoryName) ?>
                            <a href="/shop" class="remove-filter">×</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($minPrice || $maxPrice): ?>
                        <span class="filter-tag">
                            <?= $lang['price'] ?? 'Price' ?>: €<?= $minPrice ?: '0' ?> - €<?= $maxPrice ?: '∞' ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['min_price' => '', 'max_price' => ''])) ?>" class="remove-filter">×</a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($badge): ?>
                        <span class="filter-tag">
                            <?= getTranslatedBadgeName($badge, $lang) ?>
                            <a href="?<?= http_build_query(array_diff_key($_GET, ['badge' => ''])) ?>" class="remove-filter">×</a>
                        </span>
                        <?php endif; ?>
                        
                        <a href="/shop" class="clear-all"><?= $lang['clear_all'] ?? 'Clear All' ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Products Grid -->
                    <?php if (!empty($products)): ?>
                    <div class="products-grid view-grid" id="productsContainer">
                        <?php foreach ($products as $product): 
                            // Get translated product name with proper fallback
                            $productName = $product['product_name'];
                            if ($currentLang === 'en' && !empty($product['product_name_en'])) {
                                $productName = $product['product_name_en'];
                            } elseif ($currentLang === 'ar' && !empty($product['product_name_ar'])) {
                                $productName = $product['product_name_ar'];
                            }
                            
                            // Get translated category name with proper fallback
                            $categoryName = $product['category_name'] ?? '';
                            if ($currentLang === 'en' && !empty($product['category_name_en'])) {
                                $categoryName = $product['category_name_en'];
                            } elseif ($currentLang === 'ar' && !empty($product['category_name_ar'])) {
                                $categoryName = $product['category_name_ar'];
                            }
                            
                            $price = $product['sale_price'] ?: $product['base_price'];
                            $originalPrice = $product['sale_price'] ? $product['base_price'] : null;
                            $discountPercent = $originalPrice ? round((($originalPrice - $price) / $originalPrice) * 100) : 0;
                        ?>
                        <div class="product-card">
                            <?php if (!empty($product['badge'])): ?>
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
                                <button class="btn btn-add-to-cart" data-product-id="<?= $product['product_id'] ?>" type="button">
                                    <i class="fas fa-shopping-cart"></i> <?= $lang['add_to_cart'] ?? 'Add to Cart' ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link prev">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">1</a>
                        <?php if ($startPage > 2): ?>
                        <span class="page-dots">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <span class="page-dots">...</span>
                        <?php endif; ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link"><?= $totalPages ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <!-- No Products -->
                    <div class="no-products">
                        <i class="fas fa-search"></i>
                        <h2><?= $lang['no_products_found'] ?? 'No products found' ?></h2>
                        <p><?= $lang['try_different_filters'] ?? 'Try adjusting your filters or search terms' ?></p>
                        <a href="/shop" class="btn btn-primary"><?= $lang['view_all_products'] ?? 'View All Products' ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/components/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/shop.js"></script>
    
    <script>
    // JavaScript function to apply price filter
    function applyPriceFilter() {
        const minPrice = document.getElementById('minPrice').value;
        const maxPrice = document.getElementById('maxPrice').value;
        const currentUrl = new URL(window.location.href);
        
        if (minPrice) {
            currentUrl.searchParams.set('min_price', minPrice);
        } else {
            currentUrl.searchParams.delete('min_price');
        }
        
        if (maxPrice) {
            currentUrl.searchParams.set('max_price', maxPrice);
        } else {
            currentUrl.searchParams.delete('max_price');
        }
        
        currentUrl.searchParams.set('page', '1'); // Reset to first page
        window.location.href = currentUrl.toString();
    }
    
    // JavaScript function to update sort
    function updateSort(sortValue) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', sortValue);
        currentUrl.searchParams.set('page', '1'); // Reset to first page
        window.location.href = currentUrl.toString();
    }
    </script>
</body>
</html>
