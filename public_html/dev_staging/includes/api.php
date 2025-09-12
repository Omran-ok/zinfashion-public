<?php
/**
 * ZIN Fashion - API Handler with Translation Support
 * Location: /public_html/dev_staging/includes/api.php
 * Fixed: Error handling and proper JSON output
 */

// Disable error display to prevent HTML output in JSON responses
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config file
require_once __DIR__ . '/config.php';

// Clean any output that might have occurred
ob_clean();

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Check if this is an API request
$action = isset($_GET['api']) ? $_GET['api'] : (isset($_GET['action']) ? $_GET['action'] : '');

if (empty($action)) {
    echo json_encode(['error' => 'No action specified']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'getCartCount':
            handleCartCount($pdo);
            break;
            
        case 'getWishlistCount':
            handleWishlistCount($pdo);
            break;
            
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
            echo json_encode(['error' => 'API endpoint not found: ' . $action]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error occurred', 'message' => $e->getMessage()]);
}

// End output buffering and send clean JSON
ob_end_flush();
exit;

/**
 * Get product name based on current language
 */
function getTranslatedProductName($product, $currentLang = null) {
    if (!$currentLang) {
        $currentLang = $_SESSION['language'] ?? 'de';
    }
    
    $productName = $product['product_name'] ?? '';
    
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
    
    $colorName = is_array($color) ? ($color['color_name'] ?? '') : $color;
    
    if ($currentLang === 'en' && !empty($color['color_name_en'])) {
        $colorName = $color['color_name_en'];
    } elseif ($currentLang === 'ar' && !empty($color['color_name_ar'])) {
        $colorName = $color['color_name_ar'];
    }
    
    return $colorName;
}

/**
 * Handle Cart Count Request
 */
function handleCartCount($pdo) {
    try {
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
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle Wishlist Count Request
 */
function handleWishlistCount($pdo) {
    try {
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
    } catch (Exception $e) {
        echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    }
}

/**
 * Handle Categories Request
 */
function handleCategories($pdo) {
    try {
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
        
        foreach ($categories as &$category) {
            $categoryName = $category['category_name'];
            if ($currentLang === 'en' && !empty($category['category_name_en'])) {
                $categoryName = $category['category_name_en'];
            } elseif ($currentLang === 'ar' && !empty($category['category_name_ar'])) {
                $categoryName = $category['category_name_ar'];
            }
            $category['display_name'] = $categoryName;
            
            $countSql = "SELECT COUNT(*) as count FROM products WHERE category_id = :cat_id AND is_active = 1";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute(['cat_id' => $category['category_id']]);
            $category['product_count'] = $countStmt->fetchColumn();
        }
        
        echo json_encode($categories);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Single Product Request
 */
function handleSingleProduct($pdo) {
    try {
        $productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $currentLang = $_SESSION['language'] ?? 'de';
        
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID required']);
            return;
        }
        
        $sql = "SELECT p.*, 
                c.category_name, 
                c.category_name_en, 
                c.category_name_ar,
                c.slug as category_slug
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
        
        // Include all translations
        $product['product_name'] = $product['product_name'] ?? '';
        $product['product_name_en'] = $product['product_name_en'] ?? '';
        $product['product_name_ar'] = $product['product_name_ar'] ?? '';
        $product['description'] = $product['description'] ?? '';
        $product['description_en'] = $product['description_en'] ?? '';
        $product['description_ar'] = $product['description_ar'] ?? '';
        
        // Get primary image
        $imageSql = "SELECT image_url FROM product_images 
                     WHERE product_id = :product_id AND is_primary = 1 
                     LIMIT 1";
        $imageStmt = $pdo->prepare($imageSql);
        $imageStmt->execute(['product_id' => $productId]);
        $image = $imageStmt->fetch();
        $product['image_url'] = $image ? $image['image_url'] : '/assets/images/placeholder.jpg';
        
        // Check stock
        $stockSql = "SELECT COALESCE(SUM(stock_quantity), 0) as total_stock 
                     FROM product_variants 
                     WHERE product_id = :product_id AND is_available = 1";
        $stockStmt = $pdo->prepare($stockSql);
        $stockStmt->execute(['product_id' => $productId]);
        $stockResult = $stockStmt->fetch();
        $product['total_stock'] = intval($stockResult['total_stock']);
        $product['in_stock'] = $product['total_stock'] > 0;
        
        echo json_encode($product);
    } catch (Exception $e) {
        error_log("Product API Error: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to load product', 'message' => $e->getMessage()]);
    }
}

/**
 * Handle Cart Operations
 */
function handleCart($pdo) {
    try {
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
                            p.product_slug,
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
                        $productName = getTranslatedProductName($item, $currentLang);
                        $colorName = $item['color_name'] ? getTranslatedColorName($item, $currentLang) : null;
                        
                        $price = $item['sale_price'] ?: $item['base_price'];
                        $subtotal = $price * $item['quantity'];
                        
                        $items[] = [
                            'cart_item_id' => $item['cart_item_id'],
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'],
                            'product_name' => $productName,
                            'product_slug' => $item['product_slug'],
                            'size' => $item['size_name'],
                            'color' => $colorName,
                            'base_price' => floatval($item['base_price']),
                            'sale_price' => $item['sale_price'] ? floatval($item['sale_price']) : null,
                            'price' => floatval($price),
                            'quantity' => intval($item['quantity']),
                            'subtotal' => floatval($subtotal),
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
                                'product_slug' => $product['product_slug'],
                                'base_price' => floatval($product['base_price']),
                                'sale_price' => $product['sale_price'] ? floatval($product['sale_price']) : null,
                                'price' => floatval($price),
                                'quantity' => intval($quantity),
                                'subtotal' => floatval($subtotal),
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
                
                $tax = $total * TAX_RATE;
                $finalTotal = $total + $shipping + $tax;
                
                echo json_encode([
                    'items' => $items,
                    'subtotal' => floatval($total),
                    'shipping' => floatval($shipping),
                    'tax' => floatval($tax),
                    'total' => floatval($finalTotal),
                    'free_shipping_threshold' => floatval(FREE_SHIPPING_THRESHOLD),
                    'free_shipping_remaining' => floatval(max(0, FREE_SHIPPING_THRESHOLD - $total))
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
                
                // Verify product exists
                $sql = "SELECT product_id FROM products WHERE product_id = :product_id AND is_active = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['product_id' => $productId]);
                
                if (!$stmt->fetch()) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Product not found']);
                    return;
                }
                
                // Get first available variant if none specified
                if (!$variantId) {
                    $variantSql = "SELECT variant_id FROM product_variants 
                                   WHERE product_id = :product_id AND is_available = 1 
                                   AND stock_quantity > 0
                                   LIMIT 1";
                    $variantStmt = $pdo->prepare($variantSql);
                    $variantStmt->execute(['product_id' => $productId]);
                    $variantResult = $variantStmt->fetch();
                    $variantId = $variantResult ? $variantResult['variant_id'] : null;
                    
                    // If no variant found, create a default
                    if (!$variantId) {
                        // Use product_id as variant_id for products without variants
                        $variantId = $productId;
                    }
                }
                
                if (isLoggedIn()) {
                    $userId = getCurrentUserId();
                    
                    $checkSql = "SELECT cart_item_id, quantity FROM cart_items 
                                WHERE user_id = :user_id AND variant_id = :variant_id";
                    $checkStmt = $pdo->prepare($checkSql);
                    $checkStmt->execute(['user_id' => $userId, 'variant_id' => $variantId]);
                    $existing = $checkStmt->fetch();
                    
                    if ($existing) {
                        $updateSql = "UPDATE cart_items SET quantity = quantity + :quantity 
                                     WHERE cart_item_id = :id";
                        $updateStmt = $pdo->prepare($updateSql);
                        $updateStmt->execute(['quantity' => $quantity, 'id' => $existing['cart_item_id']]);
                    } else {
                        $insertSql = "INSERT INTO cart_items (user_id, variant_id, quantity) 
                                     VALUES (:user_id, :variant_id, :quantity)";
                        $insertStmt = $pdo->prepare($insertSql);
                        $insertStmt->execute([
                            'user_id' => $userId,
                            'variant_id' => $variantId,
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
    } catch (Exception $e) {
        error_log("Cart API Error: " . $e->getMessage());
        echo json_encode(['error' => 'Cart operation failed', 'message' => $e->getMessage()]);
    }
}

/**
 * Handle Products Request
 */
function handleProducts($pdo) {
    try {
        $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : 'all';
        $filter = isset($_GET['filter']) ? sanitizeInput($_GET['filter']) : 'all';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = PRODUCTS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $currentLang = $_SESSION['language'] ?? 'de';
        
        $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, 
                c.category_name, c.category_name_en, c.category_name_ar, c.slug as category_slug,
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
        
        $sql .= " ORDER BY p.is_featured DESC, p.created_at DESC";
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
            $product['product_name'] = getTranslatedProductName($product, $currentLang);
            
            if ($currentLang === 'en' && !empty($product['category_name_en'])) {
                $product['category_name'] = $product['category_name_en'];
            } elseif ($currentLang === 'ar' && !empty($product['category_name_ar'])) {
                $product['category_name'] = $product['category_name_ar'];
            }
            
            $product['image'] = $product['image'] ?: '/assets/images/placeholder.jpg';
            $product['formatted_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
            
            if ($product['sale_price'] && $product['sale_price'] > 0) {
                $product['formatted_regular_price'] = '€' . number_format($product['base_price'], 2, ',', '.');
                $product['formatted_price'] = '€' . number_format($product['sale_price'], 2, ',', '.');
                $product['has_sale'] = true;
            }
        }
        
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
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Search Request
 */
function handleSearch($pdo) {
    try {
        $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
        $currentLang = $_SESSION['language'] ?? 'de';
        
        if (strlen($query) < 2) {
            echo json_encode(['results' => []]);
            return;
        }
        
        $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, 
                c.category_name, c.category_name_en, c.category_name_ar,
                (SELECT pi.image_url FROM product_images pi 
                 WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
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
        
        foreach ($results as &$result) {
            $result['product_name'] = getTranslatedProductName($result, $currentLang);
            
            if ($currentLang === 'en' && !empty($result['category_name_en'])) {
                $result['category_name'] = $result['category_name_en'];
            } elseif ($currentLang === 'ar' && !empty($result['category_name_ar'])) {
                $result['category_name'] = $result['category_name_ar'];
            }
            
            $result['price'] = number_format($result['sale_price'] ?: $result['base_price'], 2, ',', '.');
            $result['image_url'] = $result['image_url'] ?: '/assets/images/placeholder.jpg';
        }
        
        echo json_encode(['results' => $results]);
    } catch (Exception $e) {
        echo json_encode(['results' => [], 'error' => $e->getMessage()]);
    }
}

/**
 * Handle Newsletter Subscription
 */
function handleNewsletter($pdo) {
    try {
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
        } else {
            $sql = "INSERT INTO newsletter_subscribers (email, preferred_language, subscribed_at) VALUES (:email, :language, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email, 'language' => $language]);
            echo json_encode(['success' => true, 'message' => 'Successfully subscribed']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle Wishlist Operations
 */
function handleWishlist($pdo) {
    try {
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
                    $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, 
                            c.category_name, c.category_name_en, c.category_name_ar,
                            pi.image_url 
                            FROM wishlists w
                            JOIN products p ON w.product_id = p.product_id
                            LEFT JOIN categories c ON p.category_id = c.category_id
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                            WHERE w.user_id = :user_id AND p.is_active = 1";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['user_id' => $userId]);
                    $items = $stmt->fetchAll();
                } else {
                    foreach ($_SESSION['wishlist'] as $productId) {
                        $sql = "SELECT p.*, p.product_name_en, p.product_name_ar, 
                                c.category_name, c.category_name_en, c.category_name_ar,
                                pi.image_url 
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.category_id
                                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                                WHERE p.product_id = :product_id AND p.is_active = 1";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(['product_id' => $productId]);
                        $product = $stmt->fetch();
                        
                        if ($product) {
                            $items[] = $product;
                        }
                    }
                }
                
                foreach ($items as &$item) {
                    $item['product_name'] = getTranslatedProductName($item, $currentLang);
                    
                    if ($currentLang === 'en' && !empty($item['category_name_en'])) {
                        $item['category_name'] = $item['category_name_en'];
                    } elseif ($currentLang === 'ar' && !empty($item['category_name_ar'])) {
                        $item['category_name'] = $item['category_name_ar'];
                    }
                    
                    $item['image_url'] = $item['image_url'] ?: '/assets/images/placeholder.jpg';
                    $item['formatted_price'] = '€' . number_format($item['sale_price'] ?: $item['base_price'], 2, ',', '.');
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
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
