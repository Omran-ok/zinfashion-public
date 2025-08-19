<?php
/**
 * ZIN Fashion - Add Product (with translations)
 * Location: /public_html/dev_staging/admin/add-product.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Handle language switch
if (isset($_GET['lang'])) {
    setAdminLanguage($_GET['lang']);
    header('Location: add-product.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Get categories for dropdown
$catSql = "SELECT * FROM categories WHERE is_active = 1 ORDER BY parent_id, display_order";
$catStmt = $pdo->query($catSql);
$categories = $catStmt->fetchAll();

// Get size groups
$sizeSql = "SELECT * FROM size_groups ORDER BY group_name";
$sizeStmt = $pdo->query($sizeSql);
$sizeGroups = $sizeStmt->fetchAll();

// Get colors
$colorSql = "SELECT * FROM colors WHERE is_active = 1 ORDER BY color_name";
$colorStmt = $pdo->query($colorSql);
$colors = $colorStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Generate slug from product name
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['product_name'])));
        
        // Insert product
        $productSql = "INSERT INTO products (
            category_id, size_group_id, product_name, product_name_en, product_name_ar,
            product_slug, sku, description, description_en, description_ar,
            base_price, sale_price, material, material_en, material_ar,
            care_instructions, badge, is_featured, is_active,
            meta_title, meta_description, created_at
        ) VALUES (
            :category_id, :size_group_id, :product_name, :product_name_en, :product_name_ar,
            :product_slug, :sku, :description, :description_en, :description_ar,
            :base_price, :sale_price, :material, :material_en, :material_ar,
            :care_instructions, :badge, :is_featured, :is_active,
            :meta_title, :meta_description, NOW()
        )";
        
        $stmt = $pdo->prepare($productSql);
        $stmt->execute([
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
            'meta_title' => $_POST['meta_title'] ?: $_POST['product_name'],
            'meta_description' => $_POST['meta_description'] ?: $_POST['description']
        ]);
        
        $productId = $pdo->lastInsertId();
        
        // Add product variants (sizes and stock)
        if (isset($_POST['variants']) && is_array($_POST['variants'])) {
            foreach ($_POST['variants'] as $variant) {
                if (!empty($variant['size_id']) && !empty($variant['stock'])) {
                    $variantSql = "INSERT INTO product_variants (
                        product_id, size_id, color_id, variant_sku, 
                        stock_quantity, is_available
                    ) VALUES (
                        :product_id, :size_id, :color_id, :variant_sku,
                        :stock_quantity, 1
                    )";
                    
                    $variantStmt = $pdo->prepare($variantSql);
                    $variantStmt->execute([
                        'product_id' => $productId,
                        'size_id' => $variant['size_id'],
                        'color_id' => $variant['color_id'] ?: null,
                        'variant_sku' => $_POST['sku'] . '-' . $variant['size_id'],
                        'stock_quantity' => $variant['stock']
                    ]);
                }
            }
        }
        
        // Handle image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = $productId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                    // Insert image record
                    $imageSql = "INSERT INTO product_images (product_id, image_url, is_primary) 
                                VALUES (:product_id, :image_url, 1)";
                    $imageStmt = $pdo->prepare($imageSql);
                    $imageStmt->execute([
                        'product_id' => $productId,
                        'image_url' => '/uploads/products/' . $fileName
                    ]);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect with success message
        header('Location: products.php?msg=added');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        $message = __('error') . ': ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('add_new_product') ?> - ZIN Fashion Admin</title>
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
            color: #7f8c8d;
            cursor: pointer;
            font-size: 16px;
            position: relative;
        }
        
        .form-tab.active {
            color: #3498db;
        }
        
        .form-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3498db;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .variant-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .variant-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .variant-row input,
        .variant-row select {
            padding: 8px;
        }
        
        .btn-remove {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-add-variant {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .image-upload {
            border: 2px dashed #ddd;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .image-upload:hover {
            border-color: #3498db;
        }
        
        .image-preview {
            margin-top: 20px;
            max-width: 300px;
        }
        
        .image-preview img {
            width: 100%;
            border-radius: 5px;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .language-switcher {
            display: flex;
            gap: 5px;
            align-items: center;
            margin-right: 20px;
        }
        
        .lang-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .lang-btn:hover {
            background: #f5f5f5;
            border-color: #3498db;
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
                <h1><?= __('add_new_product') ?></h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
                </div>
                
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?= __('welcome') ?>, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> <?= __('logout') ?>
                </a>
            </div>
        </div>

        <!-- Product Form -->
        <div class="product-form">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <!-- Tabs -->
                <div class="form-tabs">
                    <button type="button" class="form-tab active" onclick="switchTab('basic')">
                        <i class="fas fa-info-circle"></i> <?= __('basic_info') ?>
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('pricing')">
                        <i class="fas fa-euro-sign"></i> <?= __('pricing_stock') ?>
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('images')">
                        <i class="fas fa-image"></i> <?= __('images') ?>
                    </button>
                    <button type="button" class="form-tab" onclick="switchTab('seo')">
                        <i class="fas fa-search"></i> <?= __('seo') ?>
                    </button>
                </div>

                <!-- Basic Info Tab -->
                <div class="tab-content active" id="basic-tab">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= __('product_name_de') ?> <span class="required">*</span></label>
                            <input type="text" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label><?= __('sku') ?> <span class="required">*</span></label>
                            <input type="text" name="sku" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= __('product_name_en') ?></label>
                            <input type="text" name="product_name_en">
                        </div>
                        <div class="form-group">
                            <label><?= __('product_name_ar') ?></label>
                            <input type="text" name="product_name_ar" dir="rtl">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= __('category') ?> <span class="required">*</span></label>
                            <select name="category_id" required>
                                <option value=""><?= __('select_category') ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['parent_id'] == null): ?>
                                        <optgroup label="<?= htmlspecialchars($cat['category_name']) ?>">
                                        <?php foreach ($categories as $subcat): ?>
                                            <?php if ($subcat['parent_id'] == $cat['category_id']): ?>
                                                <option value="<?= $subcat['category_id'] ?>">
                                                    <?= htmlspecialchars($subcat['category_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= __('select_size_group') ?></label>
                            <select name="size_group_id">
                                <option value=""><?= __('select_size_group') ?></option>
                                <?php foreach ($sizeGroups as $group): ?>
                                    <option value="<?= $group['size_group_id'] ?>">
                                        <?= htmlspecialchars($group['group_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= __('description_de') ?></label>
                        <textarea name="description"></textarea>
                    </div>

                    <div class="form-group">
                        <label><?= __('description_en') ?></label>
                        <textarea name="description_en"></textarea>
                    </div>

                    <div class="form-group">
                        <label><?= __('description_ar') ?></label>
                        <textarea name="description_ar" dir="rtl"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><?= __('material') ?></label>
                            <input type="text" name="material" value="Cotton">
                        </div>
                        <div class="form-group">
                            <label><?= __('badge') ?></label>
                            <select name="badge">
                                <option value=""><?= __('none') ?></option>
                                <option value="new"><?= __('new') ?></option>
                                <option value="sale"><?= __('sale') ?></option>
                                <option value="bestseller"><?= __('bestseller') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= __('care_instructions') ?></label>
                        <textarea name="care_instructions" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1">
                            <label for="is_featured"><?= __('featured_product') ?></label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label for="is_active"><?= __('active') ?></label>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Stock Tab -->
                <div class="tab-content" id="pricing-tab">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?= __('base_price') ?> (€) <span class="required">*</span></label>
                            <input type="number" name="base_price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label><?= __('sale_price') ?> (€)</label>
                            <input type="number" name="sale_price" step="0.01">
                        </div>
                    </div>

                    <div class="variant-section">
                        <h3><?= __('product_variants') ?></h3>
                        <div id="variantContainer">
                            <div class="variant-row">
                                <select name="variants[0][size_id]">
                                    <option value=""><?= __('select_size') ?></option>
                                    <?php
                                    // Get all sizes
                                    $sizeSql = "SELECT s.*, sg.group_name FROM sizes s 
                                              JOIN size_groups sg ON s.size_group_id = sg.size_group_id 
                                              WHERE s.is_active = 1 ORDER BY sg.group_name, s.size_order";
                                    $sizeStmt = $pdo->query($sizeSql);
                                    $sizes = $sizeStmt->fetchAll();
                                    foreach ($sizes as $size): ?>
                                        <option value="<?= $size['size_id'] ?>">
                                            <?= htmlspecialchars($size['size_name'] . ' (' . $size['group_name'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="variants[0][color_id]">
                                    <option value=""><?= __('select_color') ?></option>
                                    <?php foreach ($colors as $color): ?>
                                        <option value="<?= $color['color_id'] ?>">
                                            <?= htmlspecialchars($color['color_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="variants[0][stock]" placeholder="<?= __('stock') ?>" min="0">
                                <input type="text" placeholder="SKU Suffix" disabled>
                                <button type="button" class="btn-remove" onclick="removeVariant(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn-add-variant" onclick="addVariant()">
                            <i class="fas fa-plus"></i> <?= __('add_variant') ?>
                        </button>
                    </div>
                </div>

                <!-- Images Tab -->
                <div class="tab-content" id="images-tab">
                    <div class="form-group">
                        <label><?= __('product_image') ?></label>
                        <div class="image-upload" onclick="document.getElementById('productImage').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #ddd;"></i>
                            <p><?= __('click_to_upload') ?></p>
                            <p style="font-size: 12px; color: #999;">JPG, PNG, WebP (Max 5MB)</p>
                        </div>
                        <input type="file" id="productImage" name="product_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        <div id="imagePreview" class="image-preview" style="display: none;">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                    </div>
                </div>

                <!-- SEO Tab -->
                <div class="tab-content" id="seo-tab">
                    <div class="form-group">
                        <label><?= __('meta_title') ?></label>
                        <input type="text" name="meta_title" placeholder="<?= $currentLang == 'de' ? 'Leer lassen um Produktname zu verwenden' : 'Leave empty to use product name' ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('meta_description') ?></label>
                        <textarea name="meta_description" rows="3" placeholder="<?= $currentLang == 'de' ? 'Leer lassen um Produktbeschreibung zu verwenden' : 'Leave empty to use product description' ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= __('material') ?> (English)</label>
                        <input type="text" name="material_en" value="Cotton">
                    </div>
                    <div class="form-group">
                        <label><?= __('material') ?> (Arabic)</label>
                        <input type="text" name="material_ar" value="قطن" dir="rtl">
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= __('save_product') ?>
                    </button>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?= __('cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let variantCount = 1;

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }

        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.form-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.closest('.form-tab').classList.add('active');
        }

        function addVariant() {
            const container = document.getElementById('variantContainer');
            const newRow = document.createElement('div');
            newRow.className = 'variant-row';
            newRow.innerHTML = `
                <select name="variants[${variantCount}][size_id]">
                    ${document.querySelector('select[name="variants[0][size_id]"]').innerHTML}
                </select>
                <select name="variants[${variantCount}][color_id]">
                    ${document.querySelector('select[name="variants[0][color_id]"]').innerHTML}
                </select>
                <input type="number" name="variants[${variantCount}][stock]" placeholder="<?= __('stock') ?>" min="0">
                <input type="text" placeholder="SKU Suffix" disabled>
                <button type="button" class="btn-remove" onclick="removeVariant(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newRow);
            variantCount++;
        }

        function removeVariant(button) {
            button.closest('.variant-row').remove();
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
