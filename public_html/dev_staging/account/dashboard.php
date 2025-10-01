<?php
/**
 * ZIN Fashion - Account Dashboard
 * Location: /public_html/dev_staging/account/dashboard.php
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/language-handler.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login?redirect=/account/dashboard');
}

$pdo = getDBConnection();
$userId = getCurrentUserId();

// Get user information
$sql = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user_id' => $userId]);
$user = $stmt->fetch();

// Get order statistics
$orderStatsSql = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total_amount), 0) as total_spent,
    MAX(created_at) as last_order_date
    FROM orders 
    WHERE user_id = :user_id AND payment_status = 'paid'";
$stmt = $pdo->prepare($orderStatsSql);
$stmt->execute(['user_id' => $userId]);
$orderStats = $stmt->fetch();

// Get recent orders
$recentOrdersSql = "SELECT * FROM orders 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC 
    LIMIT 5";
$stmt = $pdo->prepare($recentOrdersSql);
$stmt->execute(['user_id' => $userId]);
$recentOrders = $stmt->fetchAll();

// Get wishlist count
$wishlistSql = "SELECT COUNT(*) as count FROM wishlists WHERE user_id = :user_id";
$stmt = $pdo->prepare($wishlistSql);
$stmt->execute(['user_id' => $userId]);
$wishlistCount = $stmt->fetchColumn();

// Get addresses count
$addressSql = "SELECT COUNT(*) as count FROM user_addresses WHERE user_id = :user_id";
$stmt = $pdo->prepare($addressSql);
$stmt->execute(['user_id' => $userId]);
$addressCount = $stmt->fetchColumn();

// Success message if any
$success = getFlashMessage('success');
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['my_account'] ?? 'My Account' ?> - ZIN Fashion</title>
    
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <?php include '../includes/components/header.php'; ?>
    
    <section class="account-section">
        <div class="container">
            <div class="account-layout">
                <!-- Account Sidebar -->
                <aside class="account-sidebar">
                    <div class="account-user">
                        <div class="account-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    
                    <ul class="account-menu">
                        <li>
                            <a href="/account/dashboard" class="active">
                                <i class="fas fa-tachometer-alt"></i>
                                <?= $lang['dashboard'] ?? 'Dashboard' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/account/orders">
                                <i class="fas fa-shopping-bag"></i>
                                <?= $lang['my_orders'] ?? 'My Orders' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/account/addresses">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= $lang['addresses'] ?? 'Addresses' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/account/wishlist">
                                <i class="fas fa-heart"></i>
                                <?= $lang['my_wishlist'] ?? 'My Wishlist' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/account/profile">
                                <i class="fas fa-user-cog"></i>
                                <?= $lang['account_details'] ?? 'Account Details' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/account/password">
                                <i class="fas fa-key"></i>
                                <?= $lang['change_password'] ?? 'Change Password' ?>
                            </a>
                        </li>
                        <li>
                            <a href="/logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <?= $lang['logout'] ?? 'Logout' ?>
                            </a>
                        </li>
                    </ul>
                </aside>
                
                <!-- Main Content -->
                <main class="account-main">
                    <div class="account-header">
                        <h1><?= $lang['dashboard'] ?? 'Dashboard' ?></h1>
                        <p><?= $lang['dashboard_subtitle'] ?? 'Welcome to your account dashboard' ?></p>
                    </div>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Statistics Cards -->
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <div class="dashboard-card-content">
                                <h3><?= $orderStats['total_orders'] ?></h3>
                                <p><?= $lang['total_orders'] ?? 'Total Orders' ?></p>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="dashboard-card-content">
                                <h3>€<?= number_format($orderStats['total_spent'], 2, ',', '.') ?></h3>
                                <p><?= $lang['total_spent'] ?? 'Total Spent' ?></p>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="dashboard-card-content">
                                <h3><?= $wishlistCount ?></h3>
                                <p><?= $lang['wishlist_items'] ?? 'Wishlist Items' ?></p>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="dashboard-card-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="dashboard-card-content">
                                <h3><?= $addressCount ?></h3>
                                <p><?= $lang['saved_addresses'] ?? 'Saved Addresses' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Orders -->
                    <div class="account-section-card">
                        <div class="section-header">
                            <h2><?= $lang['recent_orders'] ?? 'Recent Orders' ?></h2>
                            <a href="/account/orders" class="btn btn-outline btn-small">
                                <?= $lang['view_all'] ?? 'View All' ?>
                            </a>
                        </div>
                        
                        <?php if (!empty($recentOrders)): ?>
                        <div class="table-container">
                            <table class="account-table">
                                <thead>
                                    <tr>
                                        <th><?= $lang['order_number'] ?? 'Order #' ?></th>
                                        <th><?= $lang['date'] ?? 'Date' ?></th>
                                        <th><?= $lang['status'] ?? 'Status' ?></th>
                                        <th><?= $lang['total'] ?? 'Total' ?></th>
                                        <th><?= $lang['action'] ?? 'Action' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                        </td>
                                        <td><?= formatDate($order['created_at']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $order['order_status'] ?>">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td>€<?= number_format($order['total_amount'], 2, ',', '.') ?></td>
                                        <td>
                                            <a href="/account/orders/<?= $order['order_id'] ?>" class="btn btn-small">
                                                <?= $lang['view'] ?? 'View' ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h2><?= $lang['no_orders_yet'] ?? 'No orders yet' ?></h2>
                            <p><?= $lang['no_orders_message'] ?? 'When you place your first order, it will appear here.' ?></p>
                            <a href="/shop" class="btn btn-primary">
                                <?= $lang['start_shopping'] ?? 'Start Shopping' ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dashboard-actions">
                        <h2><?= $lang['quick_actions'] ?? 'Quick Actions' ?></h2>
                        <div class="action-buttons">
                            <a href="/shop" class="btn btn-outline">
                                <i class="fas fa-shopping-cart"></i>
                                <?= $lang['continue_shopping'] ?? 'Continue Shopping' ?>
                            </a>
                            <a href="/account/addresses" class="btn btn-outline">
                                <i class="fas fa-plus"></i>
                                <?= $lang['add_address'] ?? 'Add Address' ?>
                            </a>
                            <a href="/account/profile" class="btn btn-outline">
                                <i class="fas fa-edit"></i>
                                <?= $lang['edit_profile'] ?? 'Edit Profile' ?>
                            </a>
                            <a href="/track-order" class="btn btn-outline">
                                <i class="fas fa-truck"></i>
                                <?= $lang['track_order'] ?? 'Track Order' ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Account Info -->
                    <div class="account-info-grid">
                        <div class="info-card">
                            <h3><?= $lang['personal_info'] ?? 'Personal Information' ?></h3>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['name'] ?? 'Name' ?>:</span>
                                <span><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['email'] ?? 'Email' ?>:</span>
                                <span><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['phone'] ?? 'Phone' ?>:</span>
                                <span><?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['member_since'] ?? 'Member Since' ?>:</span>
                                <span><?= formatDate($user['created_at'], 'F Y') ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><?= $lang['preferences'] ?? 'Preferences' ?></h3>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['language'] ?? 'Language' ?>:</span>
                                <span><?= ucfirst($user['preferred_language'] ?? 'de') ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['newsletter'] ?? 'Newsletter' ?>:</span>
                                <span>
                                    <?php if ($user['newsletter_subscribed']): ?>
                                        <i class="fas fa-check-circle" style="color: #2ecc71;"></i> <?= $lang['subscribed'] ?? 'Subscribed' ?>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle" style="color: #e74c3c;"></i> <?= $lang['not_subscribed'] ?? 'Not Subscribed' ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?= $lang['account_status'] ?? 'Account Status' ?>:</span>
                                <span>
                                    <?php if ($user['is_verified']): ?>
                                        <i class="fas fa-check-circle" style="color: #2ecc71;"></i> <?= $lang['verified'] ?? 'Verified' ?>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle" style="color: #f39c12;"></i> <?= $lang['pending_verification'] ?? 'Pending Verification' ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </section>
    
    <?php include '../includes/components/footer.php'; ?>
    
    <script>
        // Set CSRF token for JavaScript
        window.csrfToken = '<?= generateCSRFToken() ?>';
    </script>
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/auth.js"></script>
</body>
</html>
