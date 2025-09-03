<?php
/**
 * ZIN Fashion - Categories Management
 * Location: /public_html/dev_staging/admin/categories.php
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
    header('Location: categories.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new category
    if (isset($_POST['add_category'])) {
        $categoryName = sanitizeInput($_POST['category_name']);
        $categoryNameEn = sanitizeInput($_POST['category_name_en']);
        $categoryNameAr = sanitizeInput($_POST['category_name_ar']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName)));
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $displayOrder = intval($_POST['display_order']);
        $metaTitle = sanitizeInput($_POST['meta_title']);
        $metaDescription = sanitizeInput($_POST['meta_description']);
        
        // Check if slug exists
        $checkSql = "SELECT category_id FROM categories WHERE slug = :slug";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute(['slug' => $slug]);
        
        if ($checkStmt->fetch()) {
            $slug = $slug . '-' . time(); // Make unique
        }
        
        $insertSql = "INSERT INTO categories 
                     (category_name, category_name_en, category_name_ar, slug, parent_id, 
                      display_order, meta_title, meta_description, is_active) 
                     VALUES 
                     (:category_name, :category_name_en, :category_name_ar, :slug, :parent_id, 
                      :display_order, :meta_title, :meta_description, 1)";
        
        try {
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'category_name' => $categoryName,
                'category_name_en' => $categoryNameEn ?: null,
                'category_name_ar' => $categoryNameAr ?: null,
                'slug' => $slug,
                'parent_id' => $parentId,
                'display_order' => $displayOrder,
                'meta_title' => $metaTitle ?: $categoryName,
                'meta_description' => $metaDescription ?: null
            ]);
            
            $message = __('category') . ' "' . $categoryName . '" ' . __('added') . ' ' . __('success');
            $messageType = 'success';
        } catch (Exception $e) {
            $message = __('error') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $categoryId = intval($_POST['category_id']);
        $categoryName = sanitizeInput($_POST['category_name']);
        $categoryNameEn = sanitizeInput($_POST['category_name_en']);
        $categoryNameAr = sanitizeInput($_POST['category_name_ar']);
        $slug = sanitizeInput($_POST['slug']);
        $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $displayOrder = intval($_POST['display_order']);
        $metaTitle = sanitizeInput($_POST['meta_title']);
        $metaDescription = sanitizeInput($_POST['meta_description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Prevent setting self as parent
        if ($parentId == $categoryId) {
            $parentId = null;
        }
        
        $updateSql = "UPDATE categories 
                     SET category_name = :category_name,
                         category_name_en = :category_name_en,
                         category_name_ar = :category_name_ar,
                         slug = :slug,
                         parent_id = :parent_id,
                         display_order = :display_order,
                         meta_title = :meta_title,
                         meta_description = :meta_description,
                         is_active = :is_active,
                         updated_at = NOW()
                     WHERE category_id = :category_id";
        
        try {
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                'category_name' => $categoryName,
                'category_name_en' => $categoryNameEn ?: null,
                'category_name_ar' => $categoryNameAr ?: null,
                'slug' => $slug,
                'parent_id' => $parentId,
                'display_order' => $displayOrder,
                'meta_title' => $metaTitle ?: $categoryName,
                'meta_description' => $metaDescription ?: null,
                'is_active' => $isActive,
                'category_id' => $categoryId
            ]);
            
            $message = __('category') . ' ' . __('updated') . ' ' . __('success');
            $messageType = 'success';
        } catch (Exception $e) {
            $message = __('error') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $categoryId = intval($_GET['delete']);
    
    // Check if category has products
    $checkSql = "SELECT COUNT(*) FROM products WHERE category_id = :category_id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['category_id' => $categoryId]);
    $productCount = $checkStmt->fetchColumn();
    
    if ($productCount > 0) {
        $message = "Cannot delete category with products. Please reassign products first.";
        $messageType = 'error';
    } else {
        // Check if category has subcategories
        $checkSubSql = "SELECT COUNT(*) FROM categories WHERE parent_id = :category_id";
        $checkSubStmt = $pdo->prepare($checkSubSql);
        $checkSubStmt->execute(['category_id' => $categoryId]);
        $subCount = $checkSubStmt->fetchColumn();
        
        if ($subCount > 0) {
            $message = "Cannot delete category with subcategories. Please delete subcategories first.";
            $messageType = 'error';
        } else {
            // Safe to delete
            $deleteSql = "DELETE FROM categories WHERE category_id = :category_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute(['category_id' => $categoryId]);
            
            header('Location: categories.php?msg=deleted');
            exit;
        }
    }
}

// Get all categories with hierarchy
$categoriesSql = "SELECT c.*, 
                  p.category_name as parent_name,
                  (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) as product_count,
                  (SELECT COUNT(*) FROM categories WHERE parent_id = c.category_id) as subcategory_count
                  FROM categories c
                  LEFT JOIN categories p ON c.parent_id = p.category_id
                  ORDER BY COALESCE(c.parent_id, c.category_id), c.display_order, c.category_name";

$categoriesStmt = $pdo->query($categoriesSql);
$categories = $categoriesStmt->fetchAll();

// Build category tree
function buildCategoryTree($categories, $parentId = null, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $category['level'] = $level;
            $category['children'] = buildCategoryTree($categories, $category['category_id'], $level + 1);
            $tree[] = $category;
        }
    }
    return $tree;
}

$categoryTree = buildCategoryTree($categories);

// Function to display category tree
function displayCategoryTree($tree, $pdo) {
    foreach ($tree as $category) {
        $indent = str_repeat('— ', $category['level']);
        $statusClass = $category['is_active'] ? 'badge-success' : 'badge-danger';
        $statusText = $category['is_active'] ? 'Active' : 'Inactive';
        ?>
        <tr>
            <td><?= $category['category_id'] ?></td>
            <td>
                <?= $indent ?>
                <strong><?= htmlspecialchars($category['category_name']) ?></strong>
                <?php if ($category['level'] == 0): ?>
                    <span class="badge badge-info">Main</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($category['slug']) ?></td>
            <td><?= htmlspecialchars($category['parent_name'] ?: '-') ?></td>
            <td><?= $category['display_order'] ?></td>
            <td>
                <span class="badge badge-primary"><?= $category['product_count'] ?></span>
            </td>
            <td>
                <span class="badge badge-secondary"><?= $category['subcategory_count'] ?></span>
            </td>
            <td>
                <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
            </td>
            <td>
                <div class="action-buttons">
                    <button onclick="editCategory(<?= htmlspecialchars(json_encode($category)) ?>)" 
                            class="btn btn-primary btn-sm" title="<?= __('edit') ?>">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($category['product_count'] == 0 && $category['subcategory_count'] == 0): ?>
                        <a href="?delete=<?= $category['category_id'] ?>" 
                           onclick="return confirm('<?= __('confirm_delete') ?>')" 
                           class="btn btn-danger btn-sm" title="<?= __('delete') ?>">
                            <i class="fas fa-trash"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled title="Has products or subcategories">
                            <i class="fas fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
        if (!empty($category['children'])) {
            displayCategoryTree($category['children'], $pdo);
        }
    }
}

// Get parent categories for dropdown
$parentCategoriesSql = "SELECT category_id, category_name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY category_name";
$parentCategoriesStmt = $pdo->query($parentCategoriesSql);
$parentCategories = $parentCategoriesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('categories') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .category-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            margin-bottom: 30px;
            display: none;
        }
        
        .category-form.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .categories-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
        
        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .slug-preview {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .tree-view {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .tree-item {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #3498db;
        }
        
        .tree-item.subcategory {
            margin-left: 30px;
            border-left-color: #95a5a6;
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
            <li><a href="products.php"><i class="fas fa-box"></i> <?= __('products') ?></a></li>
            <li><a href="categories.php" class="active"><i class="fas fa-list"></i> <?= __('categories') ?></a></li>
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
                <h1><?= __('categories') ?></h1>
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

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success">
                Category deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Category Statistics -->
        <div class="category-stats">
            <div class="stat-card">
                <div class="value"><?= count($categories) ?></div>
                <div class="label">Total Categories</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count(array_filter($categories, function($c) { return $c['parent_id'] == null; })) ?></div>
                <div class="label">Main Categories</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count(array_filter($categories, function($c) { return $c['parent_id'] != null; })) ?></div>
                <div class="label">Subcategories</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= array_sum(array_column($categories, 'product_count')) ?></div>
                <div class="label">Total Products</div>
            </div>
        </div>

        <!-- Categories Header -->
        <div class="categories-header">
            <h2><?= __('categories') ?></h2>
            <button onclick="toggleCategoryForm()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Category
            </button>
        </div>

        <!-- Add/Edit Category Form -->
        <div class="category-form" id="categoryForm">
            <h3 id="formTitle">Add New Category</h3>
            <form method="POST">
                <input type="hidden" id="category_id" name="category_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Category Name (German) <span style="color: red;">*</span></label>
                        <input type="text" id="category_name" name="category_name" required onkeyup="generateSlug()">
                        <div class="slug-preview" id="slugPreview"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Category Name (English)</label>
                        <input type="text" id="category_name_en" name="category_name_en">
                    </div>
                    
                    <div class="form-group">
                        <label>Category Name (Arabic)</label>
                        <input type="text" id="category_name_ar" name="category_name_ar" dir="rtl">
                    </div>
                    
                    <div class="form-group">
                        <label>URL Slug <span style="color: red;">*</span></label>
                        <input type="text" id="slug" name="slug" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Parent Category</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">None (Main Category)</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?= $parent['category_id'] ?>">
                                    <?= htmlspecialchars($parent['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" id="display_order" name="display_order" value="0" min="0">
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Meta Title (SEO)</label>
                        <input type="text" id="meta_title" name="meta_title" placeholder="Leave empty to use category name">
                    </div>
                    
                    <div class="form-group">
                        <label>Meta Description (SEO)</label>
                        <textarea id="meta_description" name="meta_description" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="checkbox-group" id="activeCheckbox" style="display: none;">
                    <input type="checkbox" id="is_active" name="is_active" checked>
                    <label for="is_active">Active</label>
                </div>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="add_category" id="submitBtn" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Category
                    </button>
                    <button type="button" onclick="cancelEdit()" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Visual Tree View -->
        <div class="tree-view">
            <h3>Category Structure</h3>
            <?php foreach ($categoryTree as $mainCategory): ?>
                <div class="tree-item">
                    <strong><?= htmlspecialchars($mainCategory['category_name']) ?></strong>
                    <span style="color: #7f8c8d;">(<?= $mainCategory['product_count'] ?> products)</span>
                    
                    <?php if (!empty($mainCategory['children'])): ?>
                        <?php foreach ($mainCategory['children'] as $subCategory): ?>
                            <div class="tree-item subcategory">
                                <?= htmlspecialchars($subCategory['category_name']) ?>
                                <span style="color: #7f8c8d;">(<?= $subCategory['product_count'] ?> products)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Categories Table -->
        <div class="categories-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Parent</th>
                        <th>Order</th>
                        <th>Products</th>
                        <th>Subcategories</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($categoryTree) > 0): ?>
                        <?php displayCategoryTree($categoryTree, $pdo); ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                <i class="fas fa-folder-open" style="font-size: 48px; color: #ddd;"></i>
                                <p>No categories found. Add your first category above.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }

        function toggleCategoryForm() {
            const form = document.getElementById('categoryForm');
            const formElement = form.querySelector('form');
            
            // Always show the form when this function is called
            form.classList.add('active');
            
            // Reset to Add mode
            document.getElementById('formTitle').textContent = 'Add New Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Add Category';
            document.getElementById('submitBtn').name = 'add_category';
            document.getElementById('activeCheckbox').style.display = 'none';
            
            // Clear the form
            formElement.reset();
            document.getElementById('category_id').value = '';
            document.getElementById('slugPreview').textContent = '';
            
            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth' });
        }

        function generateSlug() {
            const name = document.getElementById('category_name').value;
            const slug = name.toLowerCase()
                .replace(/[äöü]/g, function(match) {
                    const map = {'ä': 'ae', 'ö': 'oe', 'ü': 'ue'};
                    return map[match];
                })
                .replace(/ß/g, 'ss')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            
            document.getElementById('slug').value = slug;
            document.getElementById('slugPreview').textContent = 'URL: /category/' + slug;
        }

        function editCategory(category) {
            const form = document.getElementById('categoryForm');
            form.classList.add('active');
            
            document.getElementById('formTitle').textContent = 'Edit Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Category';
            document.getElementById('submitBtn').name = 'update_category';
            document.getElementById('activeCheckbox').style.display = 'block';
            
            // Fill form with category data
            document.getElementById('category_id').value = category.category_id;
            document.getElementById('category_name').value = category.category_name;
            document.getElementById('category_name_en').value = category.category_name_en || '';
            document.getElementById('category_name_ar').value = category.category_name_ar || '';
            document.getElementById('slug').value = category.slug;
            document.getElementById('parent_id').value = category.parent_id || '';
            document.getElementById('display_order').value = category.display_order;
            document.getElementById('meta_title').value = category.meta_title || '';
            document.getElementById('meta_description').value = category.meta_description || '';
            document.getElementById('is_active').checked = category.is_active == 1;
            
            // Update slug preview
            document.getElementById('slugPreview').textContent = 'URL: /category/' + category.slug;
            
            // Scroll to form
            form.scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEdit() {
            const form = document.getElementById('categoryForm');
            const formElement = form.querySelector('form');
            
            // Reset the form fields
            formElement.reset();
            document.getElementById('category_id').value = '';
            document.getElementById('slugPreview').textContent = '';
            document.getElementById('activeCheckbox').style.display = 'none';
            
            // Hide the form
            form.classList.remove('active');
            
            // Reset form title and button
            document.getElementById('formTitle').textContent = 'Add New Category';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Add Category';
            document.getElementById('submitBtn').name = 'add_category';
        }
    </script>
</body>
</html>
