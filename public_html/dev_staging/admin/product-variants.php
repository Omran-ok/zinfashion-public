<?php
/**
 * ZIN Fashion - Product Variants Manager
 * Location: /public_html/dev_staging/admin/product-variants.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Get product ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    header('Location: products.php');
    exit;
}

// Get product details
$sql = "SELECT p.*, sg.group_name FROM products p 
        LEFT JOIN size_groups sg ON p.size_group_id = sg.size_group_id 
        WHERE p.product_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add new variant
    if (isset($_POST['add_variant'])) {
        $sizeId = intval($_POST['size_id']);
        $colorId = !empty($_POST['color_id']) ? intval($_POST['color_id']) : null;
        $stock = intval($_POST['stock_quantity']);
        $priceAdjustment = !empty($_POST['price_adjustment']) ? floatval($_POST['price_adjustment']) : 0;
        
        // Check if variant already exists
        $checkSql = "SELECT variant_id FROM product_variants 
                     WHERE product_id = :product_id AND size_id = :size_id 
                     AND (color_id = :color_id OR (color_id IS NULL AND :color_id IS NULL))";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            'product_id' => $productId,
            'size_id' => $sizeId,
            'color_id' => $colorId
        ]);
        
        if ($checkStmt->fetch()) {
            $message = "This size/color combination already exists!";
            $messageType = 'error';
        } else {
            // Generate variant SKU
            $variantSku = $product['sku'] . '-S' . $sizeId;
            if ($colorId) {
                $variantSku .= '-C' . $colorId;
            }
            
            $insertSql = "INSERT INTO product_variants 
                         (product_id, size_id, color_id, variant_sku, price_adjustment, 
                          stock_quantity, low_stock_threshold, is_available) 
                         VALUES 
                         (:product_id, :size_id, :color_id, :variant_sku, :price_adjustment, 
                          :stock_quantity, :low_stock_threshold, 1)";
            
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([
                'product_id' => $productId,
                'size_id' => $sizeId,
                'color_id' => $colorId,
                'variant_sku' => $variantSku,
                'price_adjustment' => $priceAdjustment,
                'stock_quantity' => $stock,
                'low_stock_threshold' => 10
            ]);
            
            $message = "Variant added successfully!";
            $messageType = 'success';
        }
    }
    
    // Update variant
    if (isset($_POST['update_variant'])) {
        $variantId = intval($_POST['variant_id']);
        $stock = intval($_POST['stock_quantity']);
        $priceAdjustment = floatval($_POST['price_adjustment']);
        $lowStockThreshold = intval($_POST['low_stock_threshold']);
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        $updateSql = "UPDATE product_variants 
                     SET stock_quantity = :stock, 
                         price_adjustment = :price_adjustment,
                         low_stock_threshold = :threshold,
                         is_available = :available,
                         updated_at = NOW()
                     WHERE variant_id = :variant_id";
        
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            'stock' => $stock,
            'price_adjustment' => $priceAdjustment,
            'threshold' => $lowStockThreshold,
            'available' => $isAvailable,
            'variant_id' => $variantId
        ]);
        
        $message = "Variant updated successfully!";
        $messageType = 'success';
    }
    
    // Bulk stock update
    if (isset($_POST['bulk_update'])) {
        $variants = $_POST['variants'] ?? [];
        $updateCount = 0;
        
        foreach ($variants as $variantId => $data) {
            $stock = intval($data['stock']);
            $available = isset($data['available']) ? 1 : 0;
            
            $bulkSql = "UPDATE product_variants 
                       SET stock_quantity = :stock, 
                           is_available = :available,
                           updated_at = NOW()
                       WHERE variant_id = :variant_id";
            
            $bulkStmt = $pdo->prepare($bulkSql);
            $bulkStmt->execute([
                'stock' => $stock,
                'available' => $available,
                'variant_id' => $variantId
            ]);
            $updateCount++;
        }
        
        $message = "$updateCount variants updated successfully!";
        $messageType = 'success';
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $variantId = intval($_GET['delete']);
    
    $deleteSql = "DELETE FROM product_variants WHERE variant_id = :variant_id AND product_id = :product_id";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute(['variant_id' => $variantId, 'product_id' => $productId]);
    
    header("Location: product-variants.php?id=$productId&msg=deleted");
    exit;
}

// Get all variants for this product
$variantsSql = "SELECT pv.*, s.size_name, s.size_order, c.color_name, c.color_code,
                sg.group_name
                FROM product_variants pv
                JOIN sizes s ON pv.size_id = s.size_id
                LEFT JOIN colors c ON pv.color_id = c.color_id
                LEFT JOIN size_groups sg ON s.size_group_id = sg.size_group_id
                WHERE pv.product_id = :product_id
                ORDER BY s.size_order, c.color_name";

$variantsStmt = $pdo->prepare($variantsSql);
$variantsStmt->execute(['product_id' => $productId]);
$variants = $variantsStmt->fetchAll();

// Get available sizes based on product's size group
$sizesSql = "SELECT s.*, sg.group_name 
             FROM sizes s
             JOIN size_groups sg ON s.size_group_id = sg.size_group_id
             WHERE s.is_active = 1";

if ($product['size_group_id']) {
    $sizesSql .= " AND s.size_group_id = :size_group_id";
}
$sizesSql .= " ORDER BY sg.group_name, s.size_order";

$sizesStmt = $pdo->prepare($sizesSql);
if ($product['size_group_id']) {
    $sizesStmt->execute(['size_group_id' => $product['size_group_id']]);
} else {
    $sizesStmt->execute();
}
$availableSizes = $sizesStmt->fetchAll();

// Get all colors
$colorsSql = "SELECT * FROM colors WHERE is_active = 1 ORDER BY color_name";
$colorsStmt = $pdo->query($colorsSql);
$colors = $colorsStmt->fetchAll();

// Calculate total stock
$totalStock = array_sum(array_column($variants, 'stock_quantity'));
$lowStockCount = count(array_filter($variants, function($v) {
    return $v['stock_quantity'] <= $v['low_stock_threshold'];
}));

// Get sales data for variants
$salesSql = "SELECT pv.variant_id, 
             COALESCE(SUM(oi.quantity), 0) as total_quantity
             FROM product_variants pv
             LEFT JOIN order_items oi ON pv.variant_id = oi.variant_id
             WHERE pv.product_id = :product_id
             GROUP BY pv.variant_id";
$salesStmt = $pdo->prepare($salesSql);
$salesStmt->execute(['product_id' => $productId]);
$salesData = $salesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Variants - <?= htmlspecialchars($product['product_name']) ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .variants-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .stock-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .summary-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .summary-card.low-stock .value {
            color: #e74c3c;
        }
        
        .summary-card.good-stock .value {
            color: #27ae60;
        }
        
        .summary-card .label {
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .add-variant-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .variants-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .color-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #ddd;
            vertical-align: middle;
            margin-right: 5px;
        }
        
        .stock-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .stock-status.in-stock {
            background: #d4edda;
            color: #155724;
        }
        
        .stock-status.low-stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .stock-status.out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quick-edit {
            display: none;
        }
        
        .quick-edit.active {
            display: table-row;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
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
        
        .size-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .size-box {
            background: white;
            border: 2px solid #ddd;
            padding: 15px;
            text-align: center;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .size-box:hover {
            border-color: #3498db;
        }
        
        .size-box.has-variant {
            border-color: #27ae60;
            background: #f0fff4;
        }
        
        .size-box.out-of-stock {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .size-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .size-stock {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-top: 30px;
        }
        
        .stock-chart {
            display: flex;
            align-items: flex-end;
            height: 200px;
            gap: 10px;
            padding: 20px 0;
        }
        
        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, #3498db, #5dade2);
            border-radius: 5px 5px 0 0;
            position: relative;
            min-height: 20px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }
        
        .chart-bar.low {
            background: linear-gradient(to top, #e74c3c, #ec7063);
        }
        
        .chart-label {
            position: absolute;
            bottom: -25px;
            font-size: 11px;
            width: 100%;
            text-align: center;
        }
        
        .chart-value {
            position: absolute;
            top: -20px;
            font-size: 12px;
            font-weight: bold;
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
                <h1>Product Variants & Stock</h1>
            </div>
            <div class="header-right">
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
                Variant deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Product Header -->
        <div class="variants-header">
            <div>
                <h2><?= htmlspecialchars($product['product_name']) ?></h2>
                <p>SKU: <?= htmlspecialchars($product['sku']) ?> | Base Price: €<?= number_format($product['base_price'], 2, ',', '.') ?></p>
                <?php if ($product['group_name']): ?>
                    <p>Size Group: <?= htmlspecialchars($product['group_name']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
                <a href="product-images.php?id=<?= $productId ?>" class="btn btn-info">
                    <i class="fas fa-images"></i> Manage Images
                </a>
            </div>
        </div>

        <!-- Stock Summary -->
        <div class="stock-summary">
            <div class="summary-card <?= $totalStock > 50 ? 'good-stock' : ($totalStock > 0 ? '' : 'low-stock') ?>">
                <div class="value"><?= $totalStock ?></div>
                <div class="label">Total Stock</div>
            </div>
            <div class="summary-card">
                <div class="value"><?= count($variants) ?></div>
                <div class="label">Active Variants</div>
            </div>
            <div class="summary-card <?= $lowStockCount > 0 ? 'low-stock' : 'good-stock' ?>">
                <div class="value"><?= $lowStockCount ?></div>
                <div class="label">Low Stock Items</div>
            </div>
            <div class="summary-card">
                <div class="value"><?= count($availableSizes) ?></div>
                <div class="label">Available Sizes</div>
            </div>
        </div>

        <!-- Add New Variant -->
        <div class="add-variant-form">
            <h3>Add New Variant</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Size <span style="color: red;">*</span></label>
                        <select name="size_id" required>
                            <option value="">Select Size</option>
                            <?php 
                            $currentGroup = '';
                            foreach ($availableSizes as $size): 
                                if ($currentGroup != $size['group_name']):
                                    if ($currentGroup != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($size['group_name']) . '">';
                                    $currentGroup = $size['group_name'];
                                endif;
                            ?>
                                <option value="<?= $size['size_id'] ?>">
                                    <?= htmlspecialchars($size['size_name']) ?>
                                </option>
                            <?php 
                            endforeach; 
                            if ($currentGroup != '') echo '</optgroup>';
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Color (Optional)</label>
                        <select name="color_id">
                            <option value="">No Color</option>
                            <?php foreach ($colors as $color): ?>
                                <option value="<?= $color['color_id'] ?>">
                                    <?= htmlspecialchars($color['color_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity <span style="color: red;">*</span></label>
                        <input type="number" name="stock_quantity" min="0" required value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Price Adjustment (€)</label>
                        <input type="number" name="price_adjustment" step="0.01" value="0.00">
                    </div>
                </div>
                <button type="submit" name="add_variant" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Variant
                </button>
            </form>
        </div>

        <!-- Quick Size Overview -->
        <div class="add-variant-form">
            <h3>Size Overview</h3>
            <div class="size-grid">
                <?php 
                // Create a map of existing variants
                $variantMap = [];
                foreach ($variants as $variant) {
                    $key = $variant['size_id'] . '_' . ($variant['color_id'] ?: '0');
                    $variantMap[$key] = $variant;
                }
                
                foreach ($availableSizes as $size): 
                    $sizeVariants = array_filter($variants, function($v) use ($size) {
                        return $v['size_id'] == $size['size_id'];
                    });
                    $sizeStock = array_sum(array_column($sizeVariants, 'stock_quantity'));
                    $hasVariant = count($sizeVariants) > 0;
                ?>
                    <div class="size-box <?= $hasVariant ? ($sizeStock > 0 ? 'has-variant' : 'out-of-stock') : '' ?>">
                        <div class="size-name"><?= htmlspecialchars($size['size_name']) ?></div>
                        <div class="size-stock">
                            <?php if ($hasVariant): ?>
                                Stock: <?= $sizeStock ?>
                            <?php else: ?>
                                Not Added
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bulk Update Form -->
        <?php if (count($variants) > 0): ?>
        <form method="POST">
            <div class="bulk-actions">
                <div>
                    <label>
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        Select All
                    </label>
                </div>
                <button type="submit" name="bulk_update" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save All Changes
                </button>
            </div>

            <!-- Variants Table -->
            <div class="variants-table">
                <table>
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" disabled>
                            </th>
                            <th>SKU</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Stock</th>
                            <th>Price Adj.</th>
                            <th>Status</th>
                            <th>Available</th>
                            <th>Sales</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $variant): ?>
                            <?php 
                            $stockStatus = 'in-stock';
                            if ($variant['stock_quantity'] == 0) {
                                $stockStatus = 'out-of-stock';
                            } elseif ($variant['stock_quantity'] <= $variant['low_stock_threshold']) {
                                $stockStatus = 'low-stock';
                            }
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="variant-checkbox" name="selected[]" value="<?= $variant['variant_id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($variant['variant_sku']) ?></td>
                                <td><?= htmlspecialchars($variant['size_name']) ?></td>
                                <td>
                                    <?php if ($variant['color_name']): ?>
                                        <span class="color-badge" style="background: <?= $variant['color_code'] ?>"></span>
                                        <?= htmlspecialchars($variant['color_name']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" name="variants[<?= $variant['variant_id'] ?>][stock]" 
                                           value="<?= $variant['stock_quantity'] ?>" min="0" 
                                           style="width: 80px;">
                                </td>
                                <td>
                                    €<?= number_format($variant['price_adjustment'], 2, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="stock-status <?= $stockStatus ?>">
                                        <?= $stockStatus == 'in-stock' ? 'In Stock' : 
                                            ($stockStatus == 'low-stock' ? 'Low Stock' : 'Out of Stock') ?>
                                    </span>
                                </td>
                                <td>
                                    <input type="checkbox" name="variants[<?= $variant['variant_id'] ?>][available]" 
                                           <?= $variant['is_available'] ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <?= $salesData[$variant['variant_id']] ?? 0 ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" onclick="toggleQuickEdit(<?= $variant['variant_id'] ?>)" 
                                                class="btn btn-primary btn-sm" title="Quick Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?id=<?= $productId ?>&delete=<?= $variant['variant_id'] ?>" 
                                           onclick="return confirm('Delete this variant?')" 
                                           class="btn btn-danger btn-sm" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Quick Edit Row (Hidden by default) -->
                            <tr class="quick-edit" id="edit-<?= $variant['variant_id'] ?>">
                                <td colspan="10">
                                    <div style="padding: 20px; background: #f8f9fa;">
                                        <h4>Quick Edit: <?= htmlspecialchars($variant['variant_sku']) ?></h4>
                                        <div class="form-grid">
                                            <div class="form-group">
                                                <label>Stock Quantity</label>
                                                <input type="number" id="qe-stock-<?= $variant['variant_id'] ?>" 
                                                       value="<?= $variant['stock_quantity'] ?>" min="0">
                                            </div>
                                            <div class="form-group">
                                                <label>Price Adjustment</label>
                                                <input type="number" id="qe-price-<?= $variant['variant_id'] ?>" 
                                                       value="<?= $variant['price_adjustment'] ?>" step="0.01">
                                            </div>
                                            <div class="form-group">
                                                <label>Low Stock Threshold</label>
                                                <input type="number" id="qe-threshold-<?= $variant['variant_id'] ?>" 
                                                       value="<?= $variant['low_stock_threshold'] ?>" min="0">
                                            </div>
                                            <div class="form-group">
                                                <label>Available</label>
                                                <select id="qe-available-<?= $variant['variant_id'] ?>">
                                                    <option value="1" <?= $variant['is_available'] ? 'selected' : '' ?>>Yes</option>
                                                    <option value="0" <?= !$variant['is_available'] ? 'selected' : '' ?>>No</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button type="button" onclick="saveQuickEdit(<?= $variant['variant_id'] ?>)" 
                                                class="btn btn-success">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                        <button type="button" onclick="toggleQuickEdit(<?= $variant['variant_id'] ?>)" 
                                                class="btn btn-secondary">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Stock Level Chart -->
        <div class="chart-container">
            <h3>Stock Levels by Variant</h3>
            <div class="stock-chart">
                <?php 
                $maxStock = max(array_column($variants, 'stock_quantity'));
                foreach ($variants as $variant): 
                    $height = $maxStock > 0 ? ($variant['stock_quantity'] / $maxStock * 100) : 0;
                    $isLow = $variant['stock_quantity'] <= $variant['low_stock_threshold'];
                ?>
                    <div class="chart-bar <?= $isLow ? 'low' : '' ?>" 
                         style="height: <?= $height ?>%;"
                         title="<?= htmlspecialchars($variant['size_name']) ?> - Stock: <?= $variant['stock_quantity'] ?>">
                        <span class="chart-value"><?= $variant['stock_quantity'] ?></span>
                        <span class="chart-label"><?= htmlspecialchars($variant['size_name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
            <div style="background: white; padding: 60px; text-align: center; border-radius: 5px;">
                <i class="fas fa-layer-group" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <h3>No Variants Yet</h3>
                <p>Start by adding size and color variants for this product above.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.variant-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        function toggleQuickEdit(variantId) {
            const editRow = document.getElementById('edit-' + variantId);
            editRow.classList.toggle('active');
        }

        async function saveQuickEdit(variantId) {
            const stock = document.getElementById('qe-stock-' + variantId).value;
            const price = document.getElementById('qe-price-' + variantId).value;
            const threshold = document.getElementById('qe-threshold-' + variantId).value;
            const available = document.getElementById('qe-available-' + variantId).value;
            
            // Create form data
            const formData = new FormData();
            formData.append('update_variant', '1');
            formData.append('variant_id', variantId);
            formData.append('stock_quantity', stock);
            formData.append('price_adjustment', price);
            formData.append('low_stock_threshold', threshold);
            if (available == '1') {
                formData.append('is_available', '1');
            }
            
            // Submit via AJAX
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error saving variant:', error);
                alert('Error saving variant. Please try again.');
            }
        }

        // Color preview in select dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const colorSelect = document.querySelector('select[name="color_id"]');
            if (colorSelect) {
                colorSelect.addEventListener('change', function() {
                    // You could add color preview functionality here
                });
            }
        });
    </script>
</body>
</html>
