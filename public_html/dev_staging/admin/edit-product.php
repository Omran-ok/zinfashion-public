<?php
/**
 * ZIN Fashion - Edit Product
 * Location: /public_html/dev_staging/admin/edit-product.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$productId) {
    header('Location: products.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Get product details
$sql = "SELECT * FROM products WHERE product_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get categories for dropdown
$catSql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY parent_id, display_order";
$catStmt = $pdo->query($catSql);
$categories = $catStmt->fetchAll();

// Get size groups
$sizeSql = "SELECT * FROM size_groups ORDER BY group_name";
$sizeStmt = $pdo->query($sizeSql);
$sizeGroups = $sizeStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Generate slug from product name if changed
        $slug = $product['product_slug'];
        if ($_POST['product_name'] != $product['product_name']) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['product_name'])));
        }
        
        // Update product
        $updateSql = "UPDATE products SET 
            category_id = :category_id,
            size_group_id = :size_group_id,
            product_name = :product_name,
            product_name_en = :product_name_en,
            product_name_ar = :product_name_ar,
            product_slug = :product_slug,
            sku = :sku,
            description = :description,
            description_en = :description_en,
            description_ar = :description_ar,
            base_price = :base_price,
            sale_price = :sale_price,
            material = :material,
            material_en = :material_en,
            material_ar = :material_ar,
            care_instructions = :care_instructions,
            badge = :badge,
            is_featured = :is_featured,
            is_active = :is_active,
            meta_title = :meta_title,
            meta_description = :meta_description,
            updated_at = NOW()
            WHERE product_id = :product_id";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            'product_id' => $productId,
            'category_id' => $_POST['category_id'],
            'size_group_id' => $_POST['size_group_id'] ?: null,
            'product_name' => $_POST['product_name'],
            'product_name_en' => $_POST['product_name_en'] ?: null,
            'product_name_ar' => $_POST['product_name_ar'] ?: null,
            'product_slug' => $slug,
            'sku' => $_POST['sku'],
            'description' => $_POST['description'],
            'description_en' => $_POST['description_en'] ?: null,
            'description_ar' => $_POST['description_ar'] ?: null,
            'base_price' => $_POST['base_price'],
            'sale_price' => $_POST['sale_price'] ?: null,
            'material' => $_POST['material'] ?: 'Cotton',
            'material_en' => $_POST['material_en'] ?: 'Cotton',
            'material_ar' => $_POST['material_ar'] ?: 'قطن',
            'care_instructions' => $_POST['care_instructions'] ?: null,
            'badge' => $_POST['badge'] ?: null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => $_POST['meta_title'] ?: null,
            'meta_description' => $_POST['meta_description'] ?: null
        ]);
        
        $pdo->commit();
        
        $message = __('product_updated');
        $messageType = 'success';
        
        // Reload product data
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .product-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .form-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            color: #7f8c8d;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-tab.active {
            color: #3498db;
            border-bottom: 2px solid #3498db;
            margin-bottom: -2px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .language-switcher {
            display: flex;
            gap: 5px;
            margin-right: 20px;
        }
        
        .lang-btn {
            padding: 5px 10px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
        }
        
        .lang-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../assets/images/logo.svg" alt="ZIN Fashion">
            <h2><?= __('admin_panel') ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> <?= __('dashboard') ?></a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box"></i> <?= __('products') ?></a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> <?= __('categories') ?></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
            <li><a href="newsletter.php"><i class="fas fa-envelope"></i> <?= __('newsletter') ?></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <?= __('settings') ?></a></li>
            <li><a href="change-password.php"><i class="fas fa-key"></i> <?= __('change_password') ?></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div>
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Edit Product</h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?id=<?= $productId ?>&lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?id=<?= $productId ?>&lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
                </div>
                
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?= __('welcome') ?>, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
                </a>
            </div>
        </div>

        <!-- Actions -->
        <div class="btn-group">
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
            <a href="product-images.php?id=<?= $productId ?>" class="btn btn-info">
                <i class="fas fa-images"></i> Manage Images
            </a>
            <a href="product-variants.php?id=<?= $productId ?>" class="btn btn-info">
                <i class="fas fa-layer-group"></i> Manage Variants
            </a>
        </div>

        <!-- Product Form -->
        <div class="product-form">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Tabs -->
                <div class="form-tabs">
                    <button type="button" class="form-tab active" onclick="switchTab('basic')">
                        <i class="fas fa-info-circle"></i> Basic Info
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('pricing')">
                        <i class="fas fa-euro-sign"></i> Pricing & Stock
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('details')">
                        <i class="fas fa-list-ul"></i> Details
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('seo')">
                        <i class="fas fa-search"></i> SEO
                    </button>
                </div>

                <!-- Basic Info Tab -->
                <div class="tab-content active" id="basic-tab">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name (German) *</label>
                            <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>SKU *</label>
                            <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name (English)</label>
                            <input type="text" name="product_name_en" value="<?= htmlspecialchars($product['product_name_en'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Product Name (Arabic)</label>
                            <input type="text" name="product_name_ar" value="<?= htmlspecialchars($product['product_name_ar'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description (German)</label>
                        <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="description_en"><?= htmlspecialchars($product['description_en'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Description (Arabic)</label>
                        <textarea name="description_ar"><?= htmlspecialchars($product['description_ar'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Pricing Tab -->
                <div class="tab-content" id="pricing-tab">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if (!$cat['parent_id']): ?>
                                        <option value="<?= $cat['category_id'] ?>" 
                                                <?= $product['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category_name']) ?>
                                        </option>
                                        <?php foreach ($categories as $subcat): ?>
                                            <?php if ($subcat['parent_id'] == $cat['category_id']): ?>
                                                <option value="<?= $subcat['category_id'] ?>" 
                                                        <?= $product['category_id'] == $subcat['category_id'] ? 'selected' : '' ?>>
                                                    -- <?= htmlspecialchars($subcat['category_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Size Group</label>
                            <select name="size_group_id">
                                <option value="">None</option>
                                <?php foreach ($sizeGroups as $group): ?>
                                    <option value="<?= $group['size_group_id'] ?>" 
                                            <?= $product['size_group_id'] == $group['size_group_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($group['group_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Base Price (€) *</label>
                            <input type="number" name="base_price" value="<?= $product['base_price'] ?>" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Sale Price (€)</label>
                            <input type="number" name="sale_price" value="<?= $product['sale_price'] ?>" 
                                   step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Badge</label>
                            <select name="badge">
                                <option value="">None</option>
                                <option value="new" <?= $product['badge'] == 'new' ? 'selected' : '' ?>>New</option>
                                <option value="sale" <?= $product['badge'] == 'sale' ? 'selected' : '' ?>>Sale</option>
                                <option value="bestseller" <?= $product['badge'] == 'bestseller' ? 'selected' : '' ?>>Bestseller</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_featured" id="is_featured" 
                                       <?= $product['is_featured'] ? 'checked' : '' ?>>
                                <label for="is_featured">Featured Product</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       <?= $product['is_active'] ? 'checked' : '' ?>>
                                <label for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Tab -->
                <div class="tab-content" id="details-tab">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Material (German)</label>
                            <input type="text" name="material" value="<?= htmlspecialchars($product['material'] ?? 'Cotton') ?>">
                        </div>
                        <div class="form-group">
                            <label>Material (English)</label>
                            <input type="text" name="material_en" value="<?= htmlspecialchars($product['material_en'] ?? 'Cotton') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Material (Arabic)</label>
                        <input type="text" name="material_ar" value="<?= htmlspecialchars($product['material_ar'] ?? 'قطن') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Care Instructions</label>
                        <textarea name="care_instructions"><?= htmlspecialchars($product['care_instructions'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- SEO Tab -->
                <div class="tab-content" id="seo-tab">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.form-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
