// ===================================
// API Handler File
// Location: /public_html/dev/includes/api.php
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
            (SELECT GROUP_CONCAT(DISTINCT pc.color_code) FROM product_colors pc 
             WHERE pc.product_id = p.product_id) as colors
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
    
    // Process products
    foreach ($products as &$product) {
        // Format prices
        $product['base_price'] = number_format($product['base_price'], 2, '.', '');
        if ($product['sale_price']) {
            $product['old_price'] = $product['base_price'];
            $product['base_price'] = number_format($product['sale_price'], 2, '.', '');
        }
        
        // Add badge
        if ($filter === 'new' || (time() - strtotime($product['created_at']) < 30 * 24 * 3600)) {
            $product['badge'] = 'new';
            $product['badge_text'] = 'NEU';
        } elseif ($product['sale_price']) {
            $product['badge'] = 'sale';
            $discount = round((($product['old_price'] - $product['base_price']) / $product['old_price']) * 100);
            $product['badge_text'] = "-{$discount}%";
        } elseif ($product['is_featured']) {
            $product['badge'] = 'bestseller';
            $product['badge_text'] = 'BESTSELLER';
        }
        
        // Process colors
        if ($product['colors']) {
            $product['colors'] = explode(',', $product['colors']);
        } else {
            $product['colors'] = [];
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
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalProducts / $limit),
            'total_products' => $totalProducts,
            'per_page' => $limit
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
                 OR p.tags LIKE :query
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
    $sql = "INSERT INTO newsletter_subscribers (email, ip_address, created_at) VALUES (:email, :ip, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'email' => $email,
        'ip' => getUserIP()
    ]);
    
    // Log activity
    logActivity('newsletter_subscription', ['email' => $email]);
    
    echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
}

/**
 * Handle Cart Operations
 */
function handleCart($pdo) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get cart items
            $cartId = $_SESSION['cart_id'] ?? null;
            if (!$cartId) {
                echo json_encode(['items' => [], 'total' => 0]);
                return;
            }
            
            $sql = "SELECT ci.*, p.product_name, p.base_price, p.sale_price 
                    FROM cart_items ci
                    JOIN products p ON ci.product_id = p.product_id
                    WHERE ci.cart_id = :cart_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cart_id' => $cartId]);
            $items = $stmt->fetchAll();
            
            $total = 0;
            foreach ($items as &$item) {
                $price = $item['sale_price'] ?: $item['base_price'];
                $item['subtotal'] = $price * $item['quantity'];
                $total += $item['subtotal'];
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
            
            // Create or get cart
            if (!isset($_SESSION['cart_id'])) {
                $cartSql = "INSERT INTO carts (session_id, created_at) VALUES (:session_id, NOW())";
                $cartStmt = $pdo->prepare($cartSql);
                $cartStmt->execute(['session_id' => session_id()]);
                $_SESSION['cart_id'] = $pdo->lastInsertId();
            }
            
            $cartId = $_SESSION['cart_id'];
            
            // Check if item already in cart
            $checkSql = "SELECT cart_item_id, quantity FROM cart_items 
                        WHERE cart_id = :cart_id AND product_id = :product_id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                'cart_id' => $cartId,
                'product_id' => $productId
            ]);
            
            $existingItem = $checkStmt->fetch();
            
            if ($existingItem) {
                // Update quantity
                $updateSql = "UPDATE cart_items SET quantity = quantity + :quantity 
                             WHERE cart_item_id = :item_id";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    'quantity' => $quantity,
                    'item_id' => $existingItem['cart_item_id']
                ]);
            } else {
                // Add new item
                $insertSql = "INSERT INTO cart_items (cart_id, product_id, quantity) 
                             VALUES (:cart_id, :product_id, :quantity)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    'cart_id' => $cartId,
                    'product_id' => $productId,
                    'quantity' => $quantity
                ]);
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
            
            $sql = "UPDATE cart_items SET quantity = :quantity 
                   WHERE cart_item_id = :item_id AND cart_id = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'quantity' => $quantity,
                'item_id' => $itemId,
                'cart_id' => $_SESSION['cart_id'] ?? 0
            ]);
            
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
            
            $sql = "DELETE FROM cart_items 
                   WHERE cart_item_id = :item_id AND cart_id = :cart_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'item_id' => $itemId,
                'cart_id' => $_SESSION['cart_id'] ?? 0
            ]);
            
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
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Login required']);
        return;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $_SESSION['user_id'];
    
    switch ($method) {
        case 'GET':
            // Get wishlist items
            $sql = "SELECT w.*, p.product_name, p.base_price, p.sale_price, p.image_url 
                   FROM wishlist w
                   JOIN products p ON w.product_id = p.product_id
                   WHERE w.user_id = :user_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $items = $stmt->fetchAll();
            
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
            
            // Check if already in wishlist
            $checkSql = "SELECT wishlist_id FROM wishlist 
                        WHERE user_id = :user_id AND product_id = :product_id";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
            if ($checkStmt->fetch()) {
                echo json_encode(['success' => true, 'message' => 'Already in wishlist']);
                return;
            }
            
            // Add to wishlist
            $sql = "INSERT INTO wishlist (user_id, product_id, created_at) 
                   VALUES (:user_id, :product_id, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
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
            
            $sql = "DELETE FROM wishlist 
                   WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
