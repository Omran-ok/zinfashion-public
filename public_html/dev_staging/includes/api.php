<?php
// ===================================
// API Handler File
// Location: /public_html/dev_staging/includes/api.php
// ===================================

/**
 * ZIN Fashion - API Handler
 */

// Check if this is an API request
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $action = sanitizeInput($_GET['api']);
    $pdo = getDBConnection();
    
    try {
        switch ($action) {
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
 * Handle Categories Request
 */
function handleCategories($pdo) {
    $parentId = isset($_GET['parent']) ? intval($_GET['parent']) : null;
    
    $sql = "SELECT * FROM categories WHERE ";
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
    
    $sql = "SELECT p.*, c.category_name, c.slug as category_slug,
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
    
    // Additional filters
    switch ($filter) {
        case 'new':
            $sql .= " AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'sale':
            $sql .= " AND p.sale_price IS NOT NULL AND p.sale_price > 0";
            break;
        case 'bestseller':
            $sql .= " AND p.is_featured = 1";
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
        
        if ($product['sale_price']) {
            $product['formatted_regular_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
            $product['formatted_price'] = '€' . number_format($product['sale_price'], 2, ',', '.');
            $product['has_sale'] = true;
            $product['old_price'] = $product['base_price'];
        }
        
        // Add badge
        if ($product['badge'] === 'new' || (time() - strtotime($product['created_at']) < 30 * 24 * 3600)) {
            $product['badge'] = 'new';
            $product['badge_text'] = 'NEU';
        } elseif ($product['sale_price']) {
            $product['badge'] = 'sale';
            $discount = round((($product['base_price'] - $product['sale_price']) / $product['base_price']) * 100);
            $product['badge_text'] = "-{$discount}%";
        } elseif ($product['is_featured']) {
            $product['badge'] = 'bestseller';
            $product['badge_text'] = 'BESTSELLER';
        }
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.is_active = 1";
    if ($category !== 'all') {
        $countSql .= " AND c.slug = :category";
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
    
    // Get product variants
    $variantSql = "SELECT * FROM product_variants WHERE product_id = :product_id AND is_available = 1";
    $variantStmt = $pdo->prepare($variantSql);
    $variantStmt->execute(['product_id' => $productId]);
    $product['variants'] = $variantStmt->fetchAll();
    
    // Get product images
    $imageSql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, display_order";
    $imageStmt = $pdo->prepare($imageSql);
    $imageStmt->execute(['product_id' => $productId]);
    $product['images'] = $imageStmt->fetchAll();
    
    // Get related products
    $relatedSql = "SELECT p.*, c.category_name 
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
    
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            JOIN categories c ON p.category_id = c.category_id
            WHERE p.is_active = 1 
            AND (p.product_name LIKE :query 
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
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid email required']);
        return;
    }
    
    // Check if already subscribed
    $checkSql = "SELECT subscriber_id FROM newsletter_subscribers WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['email' => $email]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => true, 'message' => 'Already subscribed']);
        return;
    }
    
    // Subscribe
    $sql = "INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (:email, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    
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
            
            echo json_encode([
                'items' => $items,
                'subtotal' => $total,
                'shipping' => $total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST,
                'total' => $total + ($total >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST)
            ]);
            break;
            
        case 'POST':
            // Add to cart
            $data = json_decode(file_get_contents('php://input'), true);
            $productId = intval($data['product_id'] ?? 0);
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
            
            // Add or update cart
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
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
            
            if (isset($_SESSION['cart'][$itemId])) {
                $_SESSION['cart'][$itemId] = $quantity;
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
            
            if (isset($_SESSION['cart'][$itemId])) {
                unset($_SESSION['cart'][$itemId]);
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
    // Initialize session wishlist if not exists
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get wishlist items
            $items = [];
            
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
            
            if (!in_array($productId, $_SESSION['wishlist'])) {
                $_SESSION['wishlist'][] = $productId;
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
            
            $_SESSION['wishlist'] = array_diff($_SESSION['wishlist'], [$productId]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index array
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
?>
