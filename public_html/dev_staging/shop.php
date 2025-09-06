<?php
/**
 * ZIN Fashion - Shop Page
 * Location: /public_html/dev_staging/shop.php
 */

// Include configuration
require_once 'includes/config.php';

// Get user preferences
$theme = $_COOKIE['theme'] ?? 'dark';
$lang = $_COOKIE['lang'] ?? 'de';

// Pagination settings
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$subcategory = isset($_GET['subcategory']) ? sanitizeInput($_GET['subcategory']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 9999;
$sizes = isset($_GET['sizes']) ? $_GET['sizes'] : [];
$colors = isset($_GET['colors']) ? $_GET['colors'] : [];

$pdo = getDBConnection();

// Build query
$where = ["p.is_active = 1"];
$params = [];

// Category filter
if ($category) {
    $where[] = "c.slug = :category";
    $params['category'] = $category;
}

if ($subcategory) {
    $where[] = "c.slug = :subcategory";
    $params['subcategory'] = $subcategory;
}

// Search filter
if ($search) {
    $where[] = "(p.product_name LIKE :search OR p.product_description LIKE :search2)";
    $params['search'] = "%$search%";
    $params['search2'] = "%$search%";
}

// Price filter
$where[] = "COALESCE(p.sale_price, p.base_price) BETWEEN :min_price AND :max_price";
$params['min_price'] = $minPrice;
$params['max_price'] = $maxPrice;

// Size filter
if (!empty($sizes)) {
    $sizePlaceholders = [];
    foreach ($sizes as $i => $size) {
        $sizePlaceholders[] = ":size$i";
        $params["size$i"] = $size;
    }
    $where[] = "EXISTS (
        SELECT 1 FROM product_variants pv 
        JOIN sizes s ON pv.size_id = s.size_id 
        WHERE pv.product_id = p.product_id 
        AND s.size_code IN (" . implode(',', $sizePlaceholders) . ")
    )";
}

// Color filter
if (!empty($colors)) {
    $colorPlaceholders = [];
    foreach ($colors as $i => $color) {
        $colorPlaceholders[] = ":color$i";
        $params["color$i"] = $color;
    }
    $where[] = "EXISTS (
        SELECT 1 FROM product_variants pv 
        JOIN colors col ON pv.color_id = col.color_id 
        WHERE pv.product_id = p.product_id 
        AND col.color_code IN (" . implode(',', $colorPlaceholders) . ")
    )";
}

$whereClause = implode(' AND ', $where);

// Determine sort order
$orderBy = match($sort) {
    'price_low' => 'COALESCE(p.sale_price, p.base_price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.base_price) DESC',
    'name' => 'p.product_name ASC',
    'newest' => 'p.created_at DESC',
    default => 'p.created_at DESC'
};

// Get total count for pagination
$countSql = "SELECT COUNT(DISTINCT p.product_id) as total 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.category_id 
             WHERE $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $itemsPerPage);

// Get products
$sql = "SELECT DISTINCT p.*, c.category_name, c.slug as category_slug,
        COALESCE(p.sale_price, p.base_price) as display_price,
        CASE WHEN p.sale_price IS NOT NULL THEN ROUND((1 - p.sale_price/p.base_price) * 100) ELSE 0 END as discount_percentage,
        (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
        (SELECT GROUP_CONCAT(DISTINCT s.size_code) 
         FROM product_variants pv 
         JOIN sizes s ON pv.size_id = s.size_id 
         WHERE pv.product_id = p.product_id AND pv.stock_quantity > 0) as available_sizes
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get all categories for filter
$catSql = "SELECT c.*, COUNT(p.product_id) as product_count 
           FROM categories c 
           LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
           WHERE c.is_active = 1 AND c.parent_id IS NULL
           GROUP BY c.category_id
           ORDER BY c.display_order";
$categories = $pdo->query($catSql)->fetchAll();

// Get available sizes
$sizeSql = "SELECT DISTINCT s.* FROM sizes s 
            JOIN product_variants pv ON s.size_id = pv.size_id 
            WHERE pv.stock_quantity > 0 
            ORDER BY s.display_order";
$availableSizes = $pdo->query($sizeSql)->fetchAll();

// Get available colors
$colorSql = "SELECT DISTINCT c.* FROM colors c 
             JOIN product_variants pv ON c.color_id = pv.color_id 
             WHERE pv.stock_quantity > 0 
             ORDER BY c.color_name";
$availableColors = $pdo->query($colorSql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Shop - ZIN Fashion</title>
    <meta name="description" content="Browse our collection of premium fashion for men, women and children">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/shop.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
</head>
<body>
    <!-- Include Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Shop Page -->
    <div class="shop-page">
        <div class="container">
            <!-- Page Header -->
            <div class="shop-header">
                <div class="breadcrumb">
                    <a href="/">Home</a>
                    <span>/</span>
                    <span>Shop</span>
                    <?php if ($category): ?>
                        <span>/</span>
                        <span><?= htmlspecialchars($category) ?></span>
                    <?php endif; ?>
                </div>
                
                <h1 class="page-title">
                    <?= $category ? htmlspecialchars(ucfirst($category)) : 'All Products' ?>
                    <span class="product-count">(<?= $totalItems ?> Products)</span>
                </h1>
                
                <!-- Sort and View Options -->
                <div class="shop-controls">
                    <div class="view-options">
                        <button class="view-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    
                    <select class="sort-select" onchange="updateSort(this.value)">
                        <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_low" <?= $sort == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sort == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>

            <div class="shop-content">
                <!-- Sidebar Filters -->
                <aside class="shop-sidebar">
                    <button class="mobile-filter-toggle">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                    
                    <div class="filters-wrapper">
                        <!-- Close button for mobile -->
                        <button class="close-filters">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <!-- Active Filters -->
                        <?php if ($search || $category || !empty($sizes) || !empty($colors) || $minPrice > 0 || $maxPrice < 9999): ?>
                        <div class="active-filters">
                            <h3>Active Filters</h3>
                            <div class="filter-tags">
                                <?php if ($search): ?>
                                    <span class="filter-tag">
                                        Search: <?= htmlspecialchars($search) ?>
                                        <a href="?" class="remove-filter">&times;</a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($category): ?>
                                    <span class="filter-tag">
                                        <?= htmlspecialchars($category) ?>
                                        <a href="?" class="remove-filter">&times;</a>
                                    </span>
                                <?php endif; ?>
                                
                                <?php foreach ($sizes as $size): ?>
                                    <span class="filter-tag">
                                        Size: <?= htmlspecialchars($size) ?>
                                        <a href="#" class="remove-filter" data-filter="size" data-value="<?= $size ?>">&times;</a>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php foreach ($colors as $color): ?>
                                    <span class="filter-tag">
                                        Color: <?= htmlspecialchars($color) ?>
                                        <a href="#" class="remove-filter" data-filter="color" data-value="<?= $color ?>">&times;</a>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <a href="shop.php" class="clear-all-filters">Clear All</a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Categories Filter -->
                        <div class="filter-section">
                            <h3>Categories</h3>
                            <ul class="filter-list">
                                <li>
                                    <a href="shop.php" class="<?= !$category ? 'active' : '' ?>">
                                        All Products
                                        <span class="count">(<?= $totalItems ?>)</span>
                                    </a>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                <li>
                                    <a href="?category=<?= $cat['slug'] ?>" 
                                       class="<?= $category == $cat['slug'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                        <span class="count">(<?= $cat['product_count'] ?>)</span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Price Filter -->
                        <div class="filter-section">
                            <h3>Price Range</h3>
                            <div class="price-filter">
                                <div class="price-inputs">
                                    <input type="number" id="minPrice" placeholder="Min" 
                                           value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0">
                                    <span>-</span>
                                    <input type="number" id="maxPrice" placeholder="Max" 
                                           value="<?= $maxPrice < 9999 ? $maxPrice : '' ?>" min="0">
                                </div>
                                <button class="apply-price-filter" onclick="applyPriceFilter()">Apply</button>
                            </div>
                        </div>
                        
                        <!-- Size Filter -->
                        <div class="filter-section">
                            <h3>Sizes</h3>
                            <div class="size-filter">
                                <?php foreach ($availableSizes as $size): ?>
                                <label class="size-option">
                                    <input type="checkbox" name="sizes[]" value="<?= $size['size_code'] ?>"
                                           <?= in_array($size['size_code'], $sizes) ? 'checked' : '' ?>
                                           onchange="applyFilters()">
                                    <span class="size-label"><?= $size['size_code'] ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Color Filter -->
                        <div class="filter-section">
                            <h3>Colors</h3>
                            <div class="color-filter">
                                <?php foreach ($availableColors as $color): ?>
                                <label class="color-option">
                                    <input type="checkbox" name="colors[]" value="<?= $color['color_code'] ?>"
                                           <?= in_array($color['color_code'], $colors) ? 'checked' : '' ?>
                                           onchange="applyFilters()">
                                    <span class="color-swatch" style="background-color: <?= $color['hex_value'] ?>"></span>
                                    <span class="color-name"><?= $color['color_name'] ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Products Grid -->
                <div class="products-section">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <i class="fas fa-search"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or search terms</p>
                            <a href="shop.php" class="btn btn-primary">View All Products</a>
                        </div>
                    <?php else: ?>
                        <div class="products-grid" id="productsGrid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-card" data-product-id="<?= $product['product_id'] ?>">
                                <?php if ($product['discount_percentage'] > 0): ?>
                                <span class="badge badge-sale">-<?= $product['discount_percentage'] ?>%</span>
                                <?php endif; ?>
                                
                                <?php if ($product['is_featured']): ?>
                                <span class="badge badge-featured">Featured</span>
                                <?php endif; ?>
                                
                                <div class="product-image">
                                    <a href="product.php?id=<?= $product['product_id'] ?>">
                                        <img src="<?= $product['primary_image'] ?: '/assets/images/placeholder.jpg' ?>" 
                                             alt="<?= htmlspecialchars($product['product_name']) ?>"
                                             loading="lazy">
                                    </a>
                                    
                                    <div class="product-overlay">
                                        <button class="quick-view" onclick="quickView(<?= $product['product_id'] ?>)">
                                            <i class="fas fa-eye"></i> Quick View
                                        </button>
                                    </div>
                                    
                                    <button class="wishlist-btn" onclick="toggleWishlist(<?= $product['product_id'] ?>)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-category">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </div>
                                    
                                    <h3 class="product-title">
                                        <a href="product.php?id=<?= $product['product_id'] ?>">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-price">
                                        <?php if ($product['sale_price']): ?>
                                            <span class="price-old">€<?= number_format($product['base_price'], 2, ',', '.') ?></span>
                                            <span class="price-current">€<?= number_format($product['sale_price'], 2, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="price-current">€<?= number_format($product['base_price'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($product['available_sizes']): ?>
                                    <div class="product-sizes">
                                        <small>Available: <?= $product['available_sizes'] ?></small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <button class="btn-add-cart" onclick="addToCart(<?= $product['product_id'] ?>)">
                                        <i class="fas fa-shopping-bag"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)) ?>" 
                                   class="page-link prev">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="?page=<?= $i ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)) ?>" 
                                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($_GET, fn($k) => $k != 'page', ARRAY_FILTER_USE_KEY)) ?>" 
                                   class="page-link next">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Modal -->
    <div id="quickViewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="quickViewContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/translations.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Filter Functions
        function applyFilters() {
            const form = document.createElement('form');
            form.method = 'GET';
            
            // Get all checked sizes
            document.querySelectorAll('input[name="sizes[]"]:checked').forEach(input => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'sizes[]';
                hiddenInput.value = input.value;
                form.appendChild(hiddenInput);
            });
            
            // Get all checked colors
            document.querySelectorAll('input[name="colors[]"]:checked').forEach(input => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'colors[]';
                hiddenInput.value = input.value;
                form.appendChild(hiddenInput);
            });
            
            // Keep existing parameters
            const urlParams = new URLSearchParams(window.location.search);
            ['category', 'search', 'sort'].forEach(param => {
                if (urlParams.has(param)) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = param;
                    hiddenInput.value = urlParams.get(param);
                    form.appendChild(hiddenInput);
                }
            });
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function applyPriceFilter() {
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const urlParams = new URLSearchParams(window.location.search);
            
            if (minPrice) urlParams.set('min_price', minPrice);
            else urlParams.delete('min_price');
            
            if (maxPrice) urlParams.set('max_price', maxPrice);
            else urlParams.delete('max_price');
            
            window.location.search = urlParams.toString();
        }
        
        function updateSort(value) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('sort', value);
            window.location.search = urlParams.toString();
        }
        
        // View Toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const grid = document.getElementById('productsGrid');
                if (this.dataset.view === 'list') {
                    grid.classList.add('list-view');
                } else {
                    grid.classList.remove('list-view');
                }
            });
        });
        
        // Mobile Filter Toggle
        document.querySelector('.mobile-filter-toggle')?.addEventListener('click', function() {
            document.querySelector('.filters-wrapper').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        document.querySelector('.close-filters')?.addEventListener('click', function() {
            document.querySelector('.filters-wrapper').classList.remove('active');
            document.body.style.overflow = '';
        });
        
        // Remove individual filters
        document.querySelectorAll('.remove-filter').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.dataset.filter;
                const value = this.dataset.value;
                
                if (filter === 'size') {
                    document.querySelector(`input[name="sizes[]"][value="${value}"]`).checked = false;
                } else if (filter === 'color') {
                    document.querySelector(`input[name="colors[]"][value="${value}"]`).checked = false;
                }
                
                applyFilters();
            });
        });
        
        // Quick View
        function quickView(productId) {
            // Implementation for quick view modal
            fetch(`api.php?action=product&id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate modal with product data
                    document.getElementById('quickViewContent').innerHTML = `
                        <div class="quick-view-product">
                            <img src="${data.image}" alt="${data.name}">
                            <div class="quick-view-info">
                                <h2>${data.name}</h2>
                                <p class="price">€${data.price}</p>
                                <p>${data.description}</p>
                                <button onclick="addToCart(${productId})">Add to Cart</button>
                            </div>
                        </div>
                    `;
                    document.getElementById('quickViewModal').style.display = 'block';
                });
        }
        
        // Add to Cart
        function addToCart(productId) {
            fetch('api.php?action=cart', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({product_id: productId, quantity: 1})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product added to cart', 'success');
                    updateCartCount();
                }
            });
        }
        
        // Toggle Wishlist
        function toggleWishlist(productId) {
            fetch('api.php?action=wishlist', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({product_id: productId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    event.target.closest('.wishlist-btn').classList.toggle('active');
                    showNotification(data.message, 'success');
                }
            });
        }
    </script>
</body>
</html>
