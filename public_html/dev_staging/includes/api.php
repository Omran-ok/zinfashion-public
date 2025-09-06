<?php
// ===================================
// API Handler File - Updated for Header Integration
// Location: /public_html/dev_staging/includes/api.php
// ===================================

/**
 * ZIN Fashion - API Handler
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
 * Handle Cart Count Request (New for header.php)
 */
function handleCartCount($pdo) {
    $count = 0;
    
    // If user is logged in, get from database
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $sql = "SELECT SUM(quantity) as count FROM cart_items WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
    } else {
        // Get from session for guests
        if (isset($_SESSION['cart'])) {
            $count = array_sum($_SESSION['cart']);
        }
    }
    
    echo json_encode(['count' => intval($count)]);
}

/**
 * Handle Wishlist Count Request (New for header.php)
 */
function handleWishlistCount($pdo) {
    $count = 0;
    
    // If user is logged in, get from database
    if (isLoggedIn()) {
        $userId = getCurrentUserId();
        $sql = "SELECT COUNT(*) as count FROM wishlists WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
    } else {
        // Get from session for guests
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
    
    $sql = "SELECT * FROM categories WHERE is_active = 1 AND ";
    $sql .= $parentId ? "parent_id = :parent_id" : "parent_id IS NULL";
    $sql .= " ORDER BY display_order, category_name";
    
    $stmt = $pdo->prepare($sql);
    if ($parentId) {
        $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    $categories = $stmt->fetchAll();
    
    // Add product count for each category
    foreach ($categories as &$category) {
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
    
    // Include multilingual product names in the query
    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, c.category_name, c.slug as category_slug,
            (SELECT pi.image_url FROM product_images pi 
             WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.is_active = 1";
    
    $params = [];
    
    // Category filter
    if ($category !== 'all') {
        $sql .= " AND c.slug = :category";
        $params['category'] = $category;
    }
    
    // Additional filters - Only filter by badge, don't override it
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
    
    // Add sorting
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
    
    // Process products for frontend display
    foreach ($products as &$product) {
        // Add image URL
        $product['image'] = $product['image'] ?: '/assets/images/placeholder.jpg';
        
        // Format prices for display
        $product['formatted_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
        $product['has_sale'] = false;
        
        if ($product['sale_price'] && $product['sale_price'] > 0) {
            $product['formatted_regular_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
            $product['formatted_price'] = '€' . number_format($product['sale_price'], 2, ',', '.');
            $product['has_sale'] = true;
            $product['old_price'] = $product['base_price'];
        }
        
        // Use the badge from database, don't override it
        $badge = $product['badge']; // This comes from the database
        $product['badge_text'] = null;
        
        // Only set badge_text if there's actually a badge
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
        
        // If no badge but has sale price, show discount percentage
        if (empty($badge) && $product['sale_price'] && $product['sale_price'] > 0) {
            $discount = round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100);
            $product['badge'] = 'sale';
            $product['badge_text'] = "-{$discount}%";
        }
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.is_active = 1";
    if ($category !== 'all') {
        $countSql .= " AND c.slug = :category";
    }
    
    // Apply same filter to count query
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
    
    // Get product variants with size and color information
    $variantSql = "SELECT pv.*, s.size_name, c.color_name, c.color_code 
                   FROM product_variants pv
                   LEFT JOIN sizes s ON pv.size_id = s.size_id
                   LEFT JOIN colors c ON pv.color_id = c.color_id
                   WHERE pv.product_id = :product_id AND pv.is_available = 1";
    $variantStmt = $pdo->prepare($variantSql);
    $variantStmt->execute(['product_id' => $productId]);
    $product['variants'] = $variantStmt->fetchAll();
    
    // Get product images
    $imageSql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, display_order";
    $imageStmt = $pdo->prepare($imageSql);
    $imageStmt->execute(['product_id' => $productId]);
    $product['images'] = $imageStmt->fetchAll();
    
    // Get related products
    $relatedSql = "SELECT p.*, c.category_name,
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
    $product['related_products'] = $relatedStmt->fetchAll();
    
    echo json_encode($product);
}

/**
 * Handle Search Request
 */
function handleSearch($pdo) {
    $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
    
    if (strlen($query) < 2) {
        echo json_encode(['results' => []]);
        return;
    }
    
    $sql = "SELECT p.*, c.category_name,
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
    $language = sanitizeInput($data['language'] ?? 'de');
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid email required']);
        return;
    }
    
    // Check if already subscribed
    $checkSql = "SELECT subscriber_id, is_active FROM newsletter_subscribers WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['email' => $email]);
    $subscriber = $checkStmt->fetch();
    
    if ($subscriber) {
        if ($subscriber['is_active']) {
            echo json_encode(['success' => true, 'message' => 'Already subscribed']);
        } else {
            // Reactivate subscription
            $updateSql = "UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE subscriber_id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute(['id' => $subscriber['subscriber_id']]);
            echo json_encode(['success' => true, 'message' => 'Subscription reactivated']);
        }
        return;
    }
    
    // New subscription
    $sql = "INSERT INTO newsletter_subscribers (email, preferred_language, subscribed_at) VALUES (:email, :language, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email, 'language' => $language]);
    
    // Log activity
    logActivity('newsletter_subscription', ['email' => $email]);
    
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
}

/**
 * Handle Cart Operations
 */
function handleCart($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Initialize session cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    switch ($method) {
        case 'GET':
            // Get cart items
            $items = [];
            $total = 0;
            
            if (isLoggedIn()) {
                // Get from database for logged-in users
                $userId = getCurrentUserId();
                $sql = "SELECT ci.*, p.product_name, p.product_name_en, p.product_name_ar, 
                        p.base_price, p.sale_price, pv.variant_sku,
                        s.size_name, c.color_name,
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
                    $price = $item['sale_price'] ?: $item['base_price'];
                    $subtotal = $price * $item['quantity'];
                    
                    $items[] = [
                        'cart_item_id' => $item['cart_item_id'],
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'product_name' => $item['product_name'],
                        'size' => $item['size_name'],
                        'color' => $item['color_name'],
                        'base_price' => $item['base_price'],
                        'sale_price' => $item['sale_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $subtotal,
                        'image_url' => $item['image_url'] ?: '/assets/images/placeholder.jpg'
                    ];
                    
                    $total += $subtotal;
                }
            } else {
                // Get from session for guests
                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    $sql = "SELECT p.*, pi.image_url 
                            FROM products p
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                            WHERE p.product_id = :product_id AND p.is_active = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['product_id' => $productId]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $price = $product['sale_price'] ?: $product['base_price'];
                        $subtotal = $price * $quantity;
                        
                        $items[] = [
                            'cart_item_id' => $productId,
                            'product_id' => $productId,
                            'product_name' => $product['product_name'],
                            'base_price' => $product['base_price'],
                            'sale_price' => $product['sale_price'],
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                            'image_url' => $product['image_url'] ?: '/assets/images/placeholder.jpg'
                        ];
                        
                        $total += $subtotal;
                    }
                }
            }
            
            $shipping = $total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
            
            echo json_encode([
                'items' => $items,
                'subtotal' => $total,
                'shipping' => $shipping,
                'total' => $total + $shipping,
                'free_shipping_threshold' => FREE_SHIPPING_THRESHOLD,
                'free_shipping_remaining' => max(0, FREE_SHIPPING_THRESHOLD - $total)
            ]);
            break;
            
        case 'POST':
            // Add to cart
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = intval($data['product_id'] ?? 0);
            $variantId = intval($data['variant_id'] ?? 0);
            $quantity = intval($data['quantity'] ?? 1);
            
            if (!$productId || $quantity < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid product or quantity']);
                return;
            }
            
            // Check if product exists
            $sql = "SELECT product_id FROM products WHERE product_id = :product_id AND is_active = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['product_id' => $productId]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                return;
            }
            
            if (isLoggedIn()) {
                // Add to database for logged-in users
                $userId = getCurrentUserId();
                
                // Check if item already in cart
                $checkSql = "SELECT cart_item_id, quantity FROM cart_items 
                            WHERE user_id = :user_id AND variant_id = :variant_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['user_id' => $userId, 'variant_id' => $variantId ?: $productId]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    // Update quantity
                    $updateSql = "UPDATE cart_items SET quantity = quantity + :quantity WHERE cart_item_id = :id";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute(['quantity' => $quantity, 'id' => $existing['cart_item_id']]);
                } else {
                    // Insert new item
                    $insertSql = "INSERT INTO cart_items (user_id, variant_id, quantity) VALUES (:user_id, :variant_id, :quantity)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        'user_id' => $userId,
                        'variant_id' => $variantId ?: $productId,
                        'quantity' => $quantity
                    ]);
                }
            } else {
                // Add to session for guests
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
            break;
            
        case 'PUT':
            // Update cart item
            $data = json_decode(file_get_contents('php://input'), true);
            $itemId = intval($data['item_id'] ?? 0);
            $quantity = intval($data['quantity'] ?? 1);
            
            if (!$itemId || $quantity < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid item or quantity']);
                return;
            }
            
            if (isLoggedIn()) {
                // Update in database
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
                // Update in session
                if (isset($_SESSION['cart'][$itemId])) {
                    $_SESSION['cart'][$itemId] = $quantity;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cart updated']);
            break;
            
        case 'DELETE':
            // Remove from cart
            $itemId = intval($_GET['item_id'] ?? 0);
            
            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'Item ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                // Remove from database
                $userId = getCurrentUserId();
                $sql = "DELETE FROM cart_items WHERE cart_item_id = :item_id AND user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['item_id' => $itemId, 'user_id' => $userId]);
            } else {
                // Remove from session
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
    
    // Initialize session wishlist if not exists
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    switch ($method) {
        case 'GET':
            // Get wishlist items
            $items = [];
            
            if (isLoggedIn()) {
                // Get from database for logged-in users
                $userId = getCurrentUserId();
                $sql = "SELECT p.*, pi.image_url 
                        FROM wishlists w
                        JOIN products p ON w.product_id = p.product_id
                        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                        WHERE w.user_id = :user_id AND p.is_active = 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId]);
                $items = $stmt->fetchAll();
                
                foreach ($items as &$item) {
                    $item['image_url'] = $item['image_url'] ?: '/assets/images/placeholder.jpg';
                }
            } else {
                // Get from session for guests
                foreach ($_SESSION['wishlist'] as $productId) {
                    $sql = "SELECT p.*, pi.image_url 
                            FROM products p
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                            WHERE p.product_id = :product_id AND p.is_active = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['product_id' => $productId]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        $product['image_url'] = $product['image_url'] ?: '/assets/images/placeholder.jpg';
                        $items[] = $product;
                    }
                }
            }
            
            echo json_encode(['items' => $items]);
            break;
            
        case 'POST':
            // Add to wishlist
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = intval($data['product_id'] ?? 0);
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                // Add to database for logged-in users
                $userId = getCurrentUserId();
                
                // Check if already in wishlist
                $checkSql = "SELECT wishlist_id FROM wishlists WHERE user_id = :user_id AND product_id = :product_id";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute(['user_id' => $userId, 'product_id' => $productId]);
                
                if (!$checkStmt->fetch()) {
                    $insertSql = "INSERT INTO wishlists (user_id, product_id) VALUES (:user_id, :product_id)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute(['user_id' => $userId, 'product_id' => $productId]);
                }
            } else {
                // Add to session for guests
                if (!in_array($productId, $_SESSION['wishlist'])) {
                    $_SESSION['wishlist'][] = $productId;
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
            break;
            
        case 'DELETE':
            // Remove from wishlist
            $productId = intval($_GET['product_id'] ?? 0);
            
            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                return;
            }
            
            if (isLoggedIn()) {
                // Remove from database
                $userId = getCurrentUserId();
                $sql = "DELETE FROM wishlists WHERE user_id = :user_id AND product_id = :product_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
            } else {
                // Remove from session
                $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$productId]);
                $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index array
            }
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
?>
