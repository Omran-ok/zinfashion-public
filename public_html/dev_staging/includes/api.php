<?php
// ===================================
// API Handler File - With Translation Support
// Location: /public_html/dev_staging/includes/api.php
// ===================================

/**
 * ZIN Fashion - API Handler with Translation Support
 */

// Check if this is an API request (both old and new format)
if (isset($_GET['api']) || isset($_GET['action'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Support both api parameter and action parameter
    $action = sanitizeInput($_GET['api'] ?? $_GET['action'] ?? '');
    $pdo = getDBConnection();
    
    try {
        switch ($action) {
            // New endpoints for header.php
            case 'getCartCount':
                handleCartCount($pdo);
                break;
                
            case 'getWishlistCount':
                handleWishlistCount($pdo);
                break;
                
            // Existing endpoints
            case 'categories':
                handleCategories($pdo);
                break;
                
            case 'products':
                handleProducts($pdo);
                break;
                
            case 'product':
                handleSingleProduct($pdo);
                break;
                
            case 'search':
                handleSearch($pdo);
                break;
                
            case 'newsletter':
                handleNewsletter($pdo);
                break;
                
            case 'cart':
                handleCart($pdo);
                break;
                
            case 'wishlist':
                handleWishlist($pdo);
                break;
                
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error occurred']);
    }
    
    exit;
}

/**
 * Get product name based on current language
 */
function getTranslatedProductName($product, $currentLang = null) {
    if (!$currentLang) {
        $currentLang = $_SESSION['language'] ?? 'de';
    }
    
    // Default to German (base) name
    $productName = $product['product_name'];
    
    // Use translated name if available
    if ($currentLang === 'en' && !empty($product['product_name_en'])) {
        $productName = $product['product_name_en'];
    } elseif ($currentLang === 'ar' && !empty($product['product_name_ar'])) {
        $productName = $product['product_name_ar'];
    }
    
    return $productName;
}

/**
 * Get color name based on current language
 */
function getTranslatedColorName($color, $currentLang = null) {
    if (!$color) return null;
    
    if (!$currentLang) {
        $currentLang = $_SESSION['language'] ?? 'de';
    }
    
    // Default to German (base) name
    $colorName = $color['color_name'] ?? $color;
    
    // Use translated name if available
    if ($currentLang === 'en' && !empty($color['color_name_en'])) {
        $colorName = $color['color_name_en'];
    } elseif ($currentLang === 'ar' && !empty($color['color_name_ar'])) {
        $colorName = $color['color_name_ar'];
    }
    
    return $colorName;
}

/**
 * Get size name based on current language (if translations are available)
 */
function getTranslatedSizeName($size, $currentLang = null) {
    // For now, sizes are usually universal (S, M, L, XL, etc.)
    // But if you have translations, implement here
    return $size['size_name'] ?? $size;
}

/**
 * Handle Cart Count Request
 */
function handleCartCount($pdo) {
    $count = 0;
    
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
    } else {
        if (isset($_SESSION['cart'])) {
            $count = array_sum($_SESSION['cart']);
        }
    }
    
    echo json_encode(['count' => intval($count)]);
}

/**
 * Handle Wishlist Count Request
 */
function handleWishlistCount($pdo) {
    $count = 0;
    
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $sql = "SELECT COUNT(*) as count FROM wishlists WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
    } else {
        if (isset($_SESSION['wishlist'])) {
            $count = count($_SESSION['wishlist']);
        }
    }
    
    echo json_encode(['count' => intval($count)]);
}

/**
 * Handle Categories Request
 */
function handleCategories($pdo) {
    $parentId = isset($_GET['parent']) ? intval($_GET['parent']) : null;
    $currentLang = $_SESSION['language'] ?? 'de';
    
    $sql = "SELECT * FROM categories WHERE is_active = 1 AND ";
    $sql .= $parentId ? "parent_id = :parent_id" : "parent_id IS NULL";
    $sql .= " ORDER BY display_order, category_name";
    
    $stmt = $pdo->prepare($sql);
    if ($parentId) {
        $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    $categories = $stmt->fetchAll();
    
    // Translate category names and add product count
    foreach ($categories as &$category) {
        // Translate category name
        $categoryName = $category['category_name'];
        if ($currentLang === 'en' && !empty($category['category_name_en'])) {
            $categoryName = $category['category_name_en'];
        } elseif ($currentLang === 'ar' && !empty($category['category_name_ar'])) {
            $categoryName = $category['category_name_ar'];
        }
        $category['display_name'] = $categoryName;
        
        // Add product count
        $countSql = "SELECT COUNT(*) as count FROM products WHERE category_id = :cat_id AND is_active = 1";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute(['cat_id' => $category['category_id']]);
        $category['product_count'] = $countStmt->fetchColumn();
    }
    
    echo json_encode($categories);
}

/**
 * Handle Products Request
 */
function handleProducts($pdo) {
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
    $filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = PRODUCTS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    $currentLang = $_SESSION['language'] ?? 'de';
    
    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, c.category_name, c.slug as category_slug,
            (SELECT pi.image_url FROM product_images pi 
             WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.is_active = 1";
    
    $params = [];
    
    if ($category !== 'all') {
        $sql .= " AND c.slug = :category";
        $params['category'] = $category;
    }
    
    switch ($filter) {
        case 'new':
            $sql .= " AND p.badge = 'new'";
            break;
        case 'sale':
            $sql .= " AND p.badge = 'sale'";
            break;
        case 'bestseller':
            $sql .= " AND p.badge = 'bestseller'";
            break;
    }
    
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'featured';
    switch ($sort) {
        case 'price-asc':
            $sql .= " ORDER BY p.base_price ASC";
            break;
        case 'price-desc':
            $sql .= " ORDER BY p.base_price DESC";
            break;
        case 'newest':
            $sql .= " ORDER BY p.created_at DESC";
            break;
        default:
            $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";
    }
    
    $sql .= " LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $products = $stmt->fetchAll();
    
    foreach ($products as &$product) {
        // Translate product name
        $product['product_name'] = getTranslatedProductName($product, $currentLang);
        
        $product['image'] = $product['image'] ?: '/assets/images/placeholder.jpg';
        $product['formatted_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
        $product['has_sale'] = false;
        
        if ($product['sale_price'] && $product['sale_price'] > 0) {
            $product['formatted_regular_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
            $product['formatted_price'] = '€' . number_format($product['sale_price'], 2, ',', '.');
            $product['has_sale'] = true;
            $product['old_price'] = $product['base_price'];
        }
        
        $badge = $product['badge'];
        $product['badge_text'] = null;
        
        if (!empty($badge)) {
            switch ($badge) {
                case 'new':
                    $product['badge_text'] = 'NEU';
                    break;
                case 'sale':
                    if ($product['sale_price'] && $product['sale_price'] > 0) {
                        $discount = round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100);
                        $product['badge_text'] = "-{$discount}%";
                    } else {
                        $product['badge_text'] = 'SALE';
                    }
                    break;
                case 'bestseller':
                    $product['badge_text'] = 'BESTSELLER';
                    break;
            }
        }
        
        if (empty($badge) && $product['sale_price'] && $product['sale_price'] > 0) {
            $discount = round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100);
            $product['badge'] = 'sale';
            $product['badge_text'] = "-{$discount}%";
        }
    }
    
    $countSql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.is_active = 1";
    if ($category !== 'all') {
        $countSql .= " AND c.slug = :category";
    }
    
    switch ($filter) {
        case 'new':
            $countSql .= " AND p.badge = 'new'";
            break;
        case 'sale':
            $countSql .= " AND p.badge = 'sale'";
            break;
        case 'bestseller':
            $countSql .= " AND p.badge = 'bestseller'";
            break;
    }
    
    $countStmt = $pdo->prepare($countSql);
    if ($category !== 'all') {
        $countStmt->bindValue('category', $category);
    }
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'products' => $products,
            'pages' => ceil($totalProducts / $limit),
            'total' => $totalProducts
        ]
    ]);
}

/**
 * Handle Single Product Request
 */
function handleSingleProduct($pdo) {
    $productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $currentLang = $_SESSION['language'] ?? 'de';
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        return;
    }
    
    $sql = "SELECT p.*, c.category_name, c.slug as category_slug
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.product_id = :product_id AND p.is_active = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['product_id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        return;
    }
    
    // Translate product name
    $product['product_name'] = getTranslatedProductName($product, $currentLang);
    
    // Get product variants with size and color information
    $variantSql = "SELECT pv.*, s.size_name, c.color_name, c.color_name_en, c.color_name_ar, c.color_code 
                   FROM product_variants pv
                   LEFT JOIN sizes s ON pv.size_id = s.size_id
                   LEFT JOIN colors c ON pv.color_id = c.color_id
                   WHERE pv.product_id = :product_id AND pv.is_available = 1";
    $variantStmt = $pdo->prepare($variantSql);
    $variantStmt->execute(['product_id' => $productId]);
    $variants = $variantStmt->fetchAll();
    
    // Translate variant colors
    foreach ($variants as &$variant) {
        if ($variant['color_name']) {
            $variant['color_name'] = getTranslatedColorName($variant, $currentLang);
        }
    }
    $product['variants'] = $variants;
    
    // Get product images
    $imageSql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, display_order";
    $imageStmt = $pdo->prepare($imageSql);
    $imageStmt->execute(['product_id' => $productId]);
    $product['images'] = $imageStmt->fetchAll();
    
    // Get related products
    $relatedSql = "SELECT p.*, p.product_name_en, p.product_name_ar, c.category_name,
                   (SELECT pi.image_url FROM product_images pi 
                    WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image
                   FROM products p
                   JOIN categories c ON p.category_id = c.category_id
                   WHERE p.category_id = :category_id 
                   AND p.product_id != :product_id 
                   AND p.is_active = 1
                   LIMIT 4";
    
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->execute([
        'category_id' => $product['category_id'],
        'product_id' => $productId
    ]);
    $relatedProducts = $relatedStmt->fetchAll();
    
    // Translate related product names
    foreach ($relatedProducts as &$relatedProduct) {
        $relatedProduct['product_name'] = getTranslatedProductName($relatedProduct, $currentLang);
    }
    $product['related_products'] = $relatedProducts;
    
    echo json_encode($product);
}

/**
 * Handle Search Request
 */
function handleSearch($pdo) {
    $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
    $currentLang = $_SESSION['language'] ?? 'de';
    
    if (strlen($query) < 2) {
        echo json_encode(['results' => []]);
        return;
    }
    
    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, c.category_name,
            (SELECT pi.image_url FROM product_images pi 
             WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.is_active = 1 
            AND (p.product_name LIKE :query 
                 OR p.product_name_en LIKE :query
                 OR p.product_name_ar LIKE :query
                 OR p.description LIKE :query
                 OR c.category_name LIKE :query)
            LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $searchTerm = "%{$query}%";
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    // Translate product names in search results
    foreach ($results as &$result) {
        $result['product_name'] = getTranslatedProductName($result, $currentLang);
    }
    
    echo json_encode(['results' => $results]);
}

/**
 * Handle Newsletter Subscription
 */
function handleNewsletter($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $language = sanitizeInput($data['language'] ?? $_SESSION['language'] ?? 'de');
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid email required']);
        return;
    }
    
    $checkSql = "SELECT subscriber_id, is_active FROM newsletter_subscribers WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['email' => $email]);
    $subscriber = $checkStmt->fetch();
    
    if ($subscriber) {
        if ($subscriber['is_active']) {
            echo json_encode(['success' => true, 'message' => 'Already subscribed']);
        } else {
            $updateSql = "UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE subscriber_id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute(['id' => $subscriber['subscriber_id']]);
            echo json_encode(['success' => true, 'message' => 'Subscription reactivated']);
        }
        return;
    }
    
    $sql = "INSERT INTO newsletter_subscribers (email, preferred_language, subscribed_at) VALUES (:email, :language, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email, 'language' => $language]);
    
    logActivity('newsletter_subscription', ['email' => $email]);
    
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
}

/**
 * Handle Cart Operations - WITH TRANSLATION SUPPORT
 */
function handleCart($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $currentLang = $_SESSION['language'] ?? 'de';
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    switch ($method) {
        case 'GET':
            $items = [];
            $total = 0;
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $sql = "SELECT ci.*, 
                        p.product_id,
                        p.product_name, 
                        p.product_name_en, 
                        p.product_name_ar, 
                        p.base_price, 
                        p.sale_price, 
                        pv.variant_sku,
                        s.size_name, 
                        c.color_name,
                        c.color_name_en,
                        c.color_name_ar,
                        (SELECT pi.image_url FROM product_images pi 
                         WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
                        FROM cart_items ci
                        JOIN product_variants pv ON ci.variant_id = pv.variant_id
                        JOIN products p ON pv.product_id = p.product_id
                        LEFT JOIN sizes s ON pv.size_id = s.size_id
                        LEFT JOIN colors c ON pv.color_id = c.color_id
                        WHERE ci.user_id = :user_id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId]);
                $cartItems = $stmt->fetchAll();
                
                foreach ($cartItems as $item) {
                    // Translate product name
                    $productName = getTranslatedProductName($item, $currentLang);
                    
                    // Translate color name
                    $colorName = null;
                    if ($item['color_name']) {
                        $colorName = getTranslatedColorName($item, $currentLang);
                    }
                    
                    $price = $item['sale_price'] ?: $item['base_price'];
                    $subtotal = $price * $item['quantity'];
                    
                    $items[] = [
                        'cart_item_id' => $item['cart_item_id'],
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'product_name' => $productName,
                        'size' => $item['size_name'],
                        'color' => $colorName,
                        'base_price' => $item['base_price'],
                        'sale_price' => $item['sale_price'],
                        'price' => $price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                        'image_url' => $item['image_url'] ?: '/assets/images/placeholder.jpg'
                    ];
                    
                    $total += $subtotal;
                }
            } else {
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, pi.image_url 
                            FROM products p
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                            WHERE p.product_id = :product_id AND p.is_active = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['product_id' => $productId]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $productName = getTranslatedProductName($product, $currentLang);
                        $price = $product['sale_price'] ?: $product['base_price'];
                        $subtotal = $price * $quantity;
                        
                        $items[] = [
                            'cart_item_id' => $productId,
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'base_price' => $product['base_price'],
                            'sale_price' => $product['sale_price'],
                            'price' => $price,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'image_url' => $product['image_url'] ?: '/assets/images/placeholder.jpg'
                        ];
                        
                        $total += $subtotal;
                    }
                }
            }
            
            $shipping = 0;
            if (count($items) > 0) {
                $shipping = $total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            }
            
            $finalTotal = $total;
            if (count($items) > 0) {
                $finalTotal = $total + $shipping;
            }
            
            echo json_encode([
                'items' => $items,
                'subtotal' => $total,
                'shipping' => $shipping,
                'total' => $finalTotal,
                'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
                'free_shipping_remaining' => max(0, FREE_SHIPPING_THRESHOLD - $total)
            ]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = intval($data['product_id'] ?? 0);
            $variantId = intval($data['variant_id'] ?? 0);
            $quantity = intval($data['quantity'] ?? 1);
            
            if (!$productId || $quantity < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid product or quantity']);
                return;
            }
            
            $sql = "SELECT product_id FROM products WHERE product_id = :product_id AND is_active = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['product_id' => $productId]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                
                $checkSql = "SELECT cart_item_id, quantity FROM cart_items 
                            WHERE user_id = :user_id AND variant_id = :variant_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['user_id' => $userId, 'variant_id' => $variantId ?: $productId]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $updateSql = "UPDATE cart_items SET quantity = quantity + :quantity WHERE cart_item_id = :id";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute(['quantity' => $quantity, 'id' => $existing['cart_item_id']]);
                } else {
                    $insertSql = "INSERT INTO cart_items (user_id, variant_id, quantity) VALUES (:user_id, :variant_id, :quantity)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        'user_id' => $userId,
                        'variant_id' => $variantId ?: $productId,
                        'quantity' => $quantity
                    ]);
                }
            } else {
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $itemId = intval($data['item_id'] ?? 0);
            $quantity = intval($data['quantity'] ?? 1);
            
            if (!$itemId || $quantity < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid item or quantity']);
                return;
            }
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $sql = "UPDATE cart_items SET quantity = :quantity 
                       WHERE cart_item_id = :item_id AND user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'quantity' => $quantity,
                    'item_id' => $itemId,
                    'user_id' => $userId
                ]);
            } else {
                if (isset($_SESSION['cart'][$itemId])) {
                    $_SESSION['cart'][$itemId] = $quantity;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
            break;
            
        case 'DELETE':
            $itemId = intval($_GET['item_id'] ?? 0);
            
            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'Item ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $sql = "DELETE FROM cart_items WHERE cart_item_id = :item_id AND user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['item_id' => $itemId, 'user_id' => $userId]);
            } else {
                if (isset($_SESSION['cart'][$itemId])) {
                    unset($_SESSION['cart'][$itemId]);
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Item removed']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

/**
 * Handle Wishlist Operations
 */
function handleWishlist($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    $currentLang = $_SESSION['language'] ?? 'de';
    
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    switch ($method) {
        case 'GET':
            $items = [];
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, pi.image_url 
                        FROM wishlists w
                        JOIN products p ON w.product_id = p.product_id
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE w.user_id = :user_id AND p.is_active = 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId]);
                $items = $stmt->fetchAll();
                
                foreach ($items as &$item) {
                    $item['product_name'] = getTranslatedProductName($item, $currentLang);
                    $item['image_url'] = $item['image_url'] ?: '/assets/images/placeholder.jpg';
                }
            } else {
                foreach ($_SESSION['wishlist'] as $productId) {
                    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, pi.image_url 
                            FROM products p
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                            WHERE p.product_id = :product_id AND p.is_active = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['product_id' => $productId]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $product['product_name'] = getTranslatedProductName($product, $currentLang);
                        $product['image_url'] = $product['image_url'] ?: '/assets/images/placeholder.jpg';
                        $items[] = $product;
                    }
                }
            }
            
            echo json_encode(['items' => $items]);
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = intval($data['product_id'] ?? 0);
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                
                $checkSql = "SELECT wishlist_id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['user_id' => $userId, 'product_id' => $productId]);
                
                if (!$checkStmt->fetch()) {
                    $insertSql = "INSERT INTO wishlists (user_id, product_id) VALUES (:user_id, :product_id)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute(['user_id' => $userId, 'product_id' => $productId]);
                }
            } else {
                if (!in_array($productId, $_SESSION['wishlist'])) {
                    $_SESSION['wishlist'][] = $productId;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
            break;
            
        case 'DELETE':
            $productId = intval($_GET['product_id'] ?? 0);
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                $userId = getCurrentUserId();
                $sql = "DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
            } else {
                $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$productId]);
                $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
?>
