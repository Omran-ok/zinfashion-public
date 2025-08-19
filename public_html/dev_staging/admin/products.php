<?php
/**
 * ZIN Fashion - Products Management (with translations)
 * Location: /public_html/dev_staging/admin/products.php
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
    $redirect = 'products.php';
    if (isset($_GET['msg'])) {
        $redirect .= '?msg=' . $_GET['msg'];
    }
    header('Location: ' . $redirect);
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    $sql = "UPDATE products SET is_active = 0 WHERE product_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $productId]);
    header('Location: products.php?msg=deleted');
    exit;
}

// Get all products
$sql = "SELECT p.*, c.category_name, 
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.product_id) as variant_count,
        (SELECT SUM(stock_quantity) FROM product_variants WHERE product_id = p.product_id) as total_stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('products_management') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .products-table {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }
        
        .success-msg {
            background: #2ecc7120;
            color: #27ae60;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #27ae60;
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
                <h1><?= __('products_management') ?></h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=de<?= isset($_GET['msg']) ? '&msg=' . $_GET['msg'] : '' ?>" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?lang=en<?= isset($_GET['msg']) ? '&msg=' . $_GET['msg'] : '' ?>" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
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

        <?php if (isset($_GET['msg'])): ?>
            <div class="success-msg">
                <?php if ($_GET['msg'] == 'deleted'): ?>
                    <?= __('product_deleted') ?>
                <?php elseif ($_GET['msg'] == 'added'): ?>
                    <?= __('product_added') ?>
                <?php elseif ($_GET['msg'] == 'updated'): ?>
                    <?= __('product_updated') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h2><?= __('all_products') ?> (<?= count($products) ?>)</h2>
            <a href="add-product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?= __('add_new_product') ?>
            </a>
        </div>

        <div class="products-table">
            <div class="table-responsive">
                <?php if (count($products) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= __('product_name') ?></th>
                                <th><?= __('category') ?></th>
                                <th><?= __('sku') ?></th>
                                <th><?= __('price') ?></th>
                                <th><?= __('sale_price') ?></th>
                                <th><?= __('stock') ?></th>
                                <th><?= __('variants') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= $product['product_id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td><?= htmlspecialchars($product['sku']) ?></td>
                                    <td>€<?= number_format($product['base_price'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ($product['sale_price']): ?>
                                            €<?= number_format($product['sale_price'], 2, ',', '.') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['total_stock'] < 10): ?>
                                            <span class="badge badge-danger"><?= $product['total_stock'] ?? 0 ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><?= $product['total_stock'] ?? 0 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $product['variant_count'] ?></td>
                                    <td>
                                        <?php if ($product['is_featured']): ?>
                                            <span class="badge badge-info"><?= __('featured') ?></span>
                                        <?php endif; ?>
                                        <?php if ($product['badge']): ?>
                                            <span class="badge badge-warning"><?= __($product['badge']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-product.php?id=<?= $product['product_id'] ?>" class="btn btn-primary btn-sm" title="<?= __('edit') ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="product-variants.php?id=<?= $product['product_id'] ?>" class="btn btn-success btn-sm" title="<?= __('variants') ?>">
                                                <i class="fas fa-layer-group"></i>
                                            </a>
                                            <a href="products.php?delete=<?= $product['product_id'] ?>" 
                                               onclick="return confirm('<?= __('confirm_delete') ?>')" 
                                               class="btn btn-danger btn-sm" title="<?= __('delete') ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state" style="padding: 60px;">
                        <i class="fas fa-box-open" style="font-size: 4rem;"></i>
                        <h3><?= __('no_products_yet') ?></h3>
                        <p><?= __('start_adding_first_product') ?></p>
                        <a href="add-product.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> <?= __('add_first_product') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }
    </script>
</body>
</html>
