<?php
/**
 * ZIN Fashion - Admin Dashboard (with translations)
 * Location: /public_html/dev_staging/admin/index.php
 */

session_start();
require_once '../includes/config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include translations
require_once 'includes/translations.php';

// Handle language switch
if (isset($_GET['lang'])) {
    setAdminLanguage($_GET['lang']);
    header('Location: index.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Get statistics
$stats = [];

// Total products
$sql = "SELECT COUNT(*) as count FROM products WHERE is_active = 1";
$stmt = $pdo->query($sql);
$stats['products'] = $stmt->fetchColumn();

// Total orders
$sql = "SELECT COUNT(*) as count FROM orders";
$stmt = $pdo->query($sql);
$stats['orders'] = $stmt->fetchColumn();

// Total customers
$sql = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
$stmt = $pdo->query($sql);
$stats['customers'] = $stmt->fetchColumn();

// Revenue (this month)
$sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND payment_status = 'paid'";
$stmt = $pdo->query($sql);
$stats['revenue'] = $stmt->fetchColumn();

// Recent orders
$sql = "SELECT order_id, order_number, total_amount, order_status, payment_status, created_at 
        FROM orders 
        ORDER BY created_at DESC 
        LIMIT 5";
$stmt = $pdo->query($sql);
$recentOrders = $stmt->fetchAll();

// Low stock products
$sql = "SELECT p.product_id, p.product_name, p.sku, 
        COALESCE(SUM(pv.stock_quantity), 0) as total_stock
        FROM products p
        LEFT JOIN product_variants pv ON p.product_id = pv.product_id
        WHERE p.is_active = 1
        GROUP BY p.product_id
        HAVING total_stock < 10
        ORDER BY total_stock ASC
        LIMIT 5";
$stmt = $pdo->query($sql);
$lowStockProducts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('dashboard') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
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
            display: flex;
            align-items: center;
            gap: 5px;
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
        
        .flag-icon {
            width: 20px;
            height: 15px;
            display: inline-block;
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
            <li>
                <a href="index.php" class="active">
                    <i class="fas fa-home"></i>
                    <?= __('dashboard') ?>
                </a>
            </li>
            <li>
                <a href="products.php">
                    <i class="fas fa-box"></i>
                    <?= __('products') ?>
                </a>
            </li>
            <li>
                <a href="categories.php">
                    <i class="fas fa-list"></i>
                    <?= __('categories') ?>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <?= __('orders') ?>
                </a>
            </li>
            <li>
                <a href="customers.php">
                    <i class="fas fa-users"></i>
                    <?= __('customers') ?>
                </a>
            </li>
            <li>
                <a href="newsletter.php">
                    <i class="fas fa-envelope"></i>
                    <?= __('newsletter') ?>
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <?= __('settings') ?>
                </a>
            </li>
            <li>
                <a href="change-password.php">
                    <i class="fas fa-key"></i>
                    <?= __('change_password') ?>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <div class="header">
            <div>
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><?= __('dashboard') ?></h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">
                        <span class="flag-icon">🇩🇪</span> DE
                    </a>
                    <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">
                        <span class="flag-icon">🇬🇧</span> EN
                    </a>
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="icon">
                    <i class="fas fa-box"></i>
                </div>
                <h3><?= __('total_products') ?></h3>
                <div class="value"><?= number_format($stats['products']) ?></div>
            </div>
            
            <div class="stat-card green">
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3><?= __('total_orders') ?></h3>
                <div class="value"><?= number_format($stats['orders']) ?></div>
            </div>
            
            <div class="stat-card orange">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?= __('total_customers') ?></h3>
                <div class="value"><?= number_format($stats['customers']) ?></div>
            </div>
            
            <div class="stat-card purple">
                <div class="icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <h3><?= __('revenue_this_month') ?></h3>
                <div class="value">€<?= number_format($stats['revenue'], 2, ',', '.') ?></div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <h2><?= __('recent_orders') ?></h2>
                <div class="table-responsive">
                    <?php if (count($recentOrders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th><?= __('order_number') ?></th>
                                    <th><?= __('amount') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                        <td>€<?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $order['order_status'] == 'completed' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p><?= __('no_orders_yet') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="dashboard-card">
                <h2><?= __('low_stock_alert') ?></h2>
                <div class="table-responsive">
                    <?php if (count($lowStockProducts) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th><?= __('product') ?></th>
                                    <th><?= __('sku') ?></th>
                                    <th><?= __('stock') ?></th>
                                    <th><?= __('action') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><?= htmlspecialchars($product['sku']) ?></td>
                                        <td>
                                            <span class="badge badge-danger">
                                                <?= $product['total_stock'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-product.php?id=<?= $product['product_id'] ?>" class="btn btn-primary btn-sm">
                                                <?= __('edit') ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p><?= __('all_products_stocked') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }
    </script>
</body>
</html>
