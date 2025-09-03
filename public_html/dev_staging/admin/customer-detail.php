<?php
/**
 * ZIN Fashion - Customer Detail
 * Location: /public_html/dev_staging/admin/customer-detail.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$customerId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$customerId) {
    header('Location: customers.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Get customer details
$sql = "SELECT * FROM users WHERE user_id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Get customer addresses
$addressSql = "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
$addressStmt = $pdo->prepare($addressSql);
$addressStmt->execute(['user_id' => $customerId]);
$addresses = $addressStmt->fetchAll();

// Get customer orders
$ordersSql = "SELECT o.*, 
              (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
              FROM orders o 
              WHERE o.user_id = :user_id 
              ORDER BY o.created_at DESC 
              LIMIT 10";
$ordersStmt = $pdo->prepare($ordersSql);
$ordersStmt->execute(['user_id' => $customerId]);
$orders = $ordersStmt->fetchAll();

// Get customer statistics
$statsSql = "SELECT 
             COUNT(DISTINCT o.order_id) as total_orders,
             COALESCE(SUM(o.total_amount), 0) as lifetime_value,
             COALESCE(AVG(o.total_amount), 0) as avg_order_value,
             MAX(o.created_at) as last_order_date
             FROM orders o 
             WHERE o.user_id = :user_id AND o.payment_status = 'paid'";
$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute(['user_id' => $customerId]);
$stats = $statsStmt->fetch();

// Get wishlist items
$wishlistSql = "SELECT w.*, p.product_name, p.base_price, p.sale_price, pi.image_url
                FROM wishlists w
                JOIN products p ON w.product_id = p.product_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE w.user_id = :user_id
                ORDER BY w.added_at DESC
                LIMIT 6";
$wishlistStmt = $pdo->prepare($wishlistSql);
$wishlistStmt->execute(['user_id' => $customerId]);
$wishlistItems = $wishlistStmt->fetchAll();

// Handle status update
if (isset($_POST['toggle_status'])) {
    $updateSql = "UPDATE users SET is_active = NOT is_active WHERE user_id = :user_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute(['user_id' => $customerId]);
    
    // Refresh customer data
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Detail - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .customer-header {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .customer-header-content {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        
        .customer-main-info {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .customer-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .customer-info h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .customer-meta {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .customer-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .detail-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .detail-card h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .info-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .address-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .address-type {
            display: inline-block;
            padding: 2px 8px;
            background: #3498db;
            color: white;
            border-radius: 3px;
            font-size: 0.75rem;
            margin-bottom: 5px;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .wishlist-item {
            text-align: center;
        }
        
        .wishlist-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .wishlist-item-name {
            font-size: 0.85rem;
            margin-top: 5px;
            color: #2c3e50;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
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
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
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
            <li><a href="categories.php"><i class="fas fa-list"></i> <?= __('categories') ?></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
            <li><a href="customers.php" class="active"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
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
                <h1>Customer Details</h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?id=<?= $customerId ?>&lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?id=<?= $customerId ?>&lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
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

        <!-- Customer Header -->
        <div class="customer-header">
            <div class="customer-header-content">
                <div class="customer-main-info">
                    <div class="customer-avatar-large">
                        <?= strtoupper(substr($customer['first_name'] ?? 'U', 0, 1) . substr($customer['last_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="customer-info">
                        <h2><?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?></h2>
                        <div class="customer-meta">
                            <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($customer['email']) ?></div>
                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($customer['phone'] ?: 'No phone') ?></div>
                            <div><i class="fas fa-calendar"></i> Customer since <?= date('F Y', strtotime($customer['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
                <div class="btn-group">
                    <a href="customers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="toggle_status" 
                                class="btn btn-<?= $customer['is_active'] ? 'warning' : 'success' ?>">
                            <?= $customer['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="customer-stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['total_orders'] ?? 0) ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">€<?= number_format($stats['lifetime_value'] ?? 0, 2, ',', '.') ?></div>
                    <div class="stat-label">Lifetime Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">€<?= number_format($stats['avg_order_value'] ?? 0, 2, ',', '.') ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">
                        <span class="badge badge-<?= $customer['is_active'] ? 'success' : 'danger' ?>">
                            <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div class="stat-label">Account Status</div>
                </div>
            </div>
        </div>

        <!-- Detail Grid -->
        <div class="detail-grid">
            <!-- Account Information -->
            <div class="detail-card">
                <h3><i class="fas fa-user"></i> Account Information</h3>
                <div class="info-row">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= $customer['user_id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Language</span>
                    <span class="info-value"><?= strtoupper($customer['preferred_language'] ?? 'de') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Newsletter</span>
                    <span class="info-value">
                        <?= $customer['newsletter_subscribed'] ? 
                            '<span class="badge badge-success">Subscribed</span>' : 
                            '<span class="badge badge-secondary">Not Subscribed</span>' ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email Verified</span>
                    <span class="info-value">
                        <?= $customer['is_verified'] ? 
                            '<span class="badge badge-success">Verified</span>' : 
                            '<span class="badge badge-warning">Unverified</span>' ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Last Login</span>
                    <span class="info-value">
                        <?= $customer['last_login'] ? 
                            date('d.m.Y H:i', strtotime($customer['last_login'])) : 
                            'Never' ?>
                    </span>
                </div>
            </div>

            <!-- Addresses -->
            <div class="detail-card">
                <h3><i class="fas fa-map-marker-alt"></i> Addresses</h3>
                <?php if (count($addresses) > 0): ?>
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-item">
                            <?php if ($address['is_default']): ?>
                                <span class="address-type">Default</span>
                            <?php endif; ?>
                            <span class="address-type"><?= ucfirst($address['address_type']) ?></span>
                            <div style="margin-top: 10px;">
                                <strong><?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?></strong><br>
                                <?= htmlspecialchars($address['street_address']) ?><br>
                                <?php if ($address['street_address_2']): ?>
                                    <?= htmlspecialchars($address['street_address_2']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($address['postal_code'] . ' ' . $address['city']) ?><br>
                                <?= htmlspecialchars($address['country']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <p>No addresses added</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="detail-card" style="margin-top: 30px;">
            <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
            <?php if (count($orders) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                                    <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                                    <td><?= $order['item_count'] ?> items</td>
                                    <td>€<?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $order['order_status'] == 'delivered' ? 'success' : 
                                            ($order['order_status'] == 'cancelled' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($order['order_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $order['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($order['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order-detail.php?id=<?= $order['order_id'] ?>" 
                                           class="btn btn-small btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($stats['total_orders'] > 10): ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="orders.php?customer=<?= $customerId ?>">View all <?= $stats['total_orders'] ?> orders</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>No orders yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Wishlist -->
        <div class="detail-card" style="margin-top: 30px;">
            <h3><i class="fas fa-heart"></i> Wishlist Items</h3>
            <?php if (count($wishlistItems) > 0): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlistItems as $item): ?>
                        <div class="wishlist-item">
                            <img src="<?= htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div class="wishlist-item-name">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </div>
                            <div style="color: #e74c3c; font-weight: bold;">
                                €<?= number_format($item['sale_price'] ?? $item['base_price'], 2, ',', '.') ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-heart"></i>
                    <p>No items in wishlist</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
