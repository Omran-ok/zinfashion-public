<?php
/**
 * ZIN Fashion - Customers Management
 * Location: /public_html/dev_staging/admin/customers.php
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
    header('Location: customers.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Handle customer status update
if (isset($_POST['toggle_status'])) {
    $userId = intval($_POST['user_id']);
    $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    header('Location: customers.php?msg=updated');
    exit;
}

// Handle customer deletion (soft delete)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    $sql = "UPDATE users SET is_active = 0 WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    header('Location: customers.php?msg=deleted');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params['search'] = "%$search%";
}

if ($statusFilter !== '') {
    $where[] = "u.is_active = :status";
    $params['status'] = $statusFilter;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) FROM users u $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCustomers = $countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $perPage);

// Get customers
$sql = "SELECT u.*, 
        COUNT(DISTINCT o.order_id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.user_id = o.user_id
        $whereClause
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

// Get statistics - FIXED: Using correct column name and COALESCE for null safety
$statsSql = "SELECT 
             COALESCE(COUNT(*), 0) as total,
             COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) as active,
             COALESCE(SUM(CASE WHEN newsletter_subscribed = 1 THEN 1 ELSE 0 END), 0) as newsletter,
             COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) as new_this_month
             FROM users";
$statsStmt = $pdo->query($statsSql);
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('customers') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .customers-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-box .label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-form input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .customer-details {
            line-height: 1.2;
        }
        
        .customer-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .customer-email {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
        }
        
        .pagination a:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination a.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .customer-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-small {
            padding: 5px 10px;
            font-size: 0.85rem;
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
        
        .lang-btn:hover {
            background: #f5f5f5;
            border-color: #3498db;
        }
        
        .lang-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .text-muted {
            color: #999;
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
                <h1><?= __('customers') ?></h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
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

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['msg'] == 'updated'): ?>
                    Customer status updated successfully!
                <?php elseif ($_GET['msg'] == 'deleted'): ?>
                    Customer deleted successfully!
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="customers-stats">
            <div class="stat-box">
                <div class="number"><?= number_format($stats['total']) ?></div>
                <div class="label">Total Customers</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($stats['active']) ?></div>
                <div class="label">Active Customers</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($stats['newsletter']) ?></div>
                <div class="label">Newsletter Subscribers</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($stats['new_this_month']) ?></div>
                <div class="label">New This Month</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by name, email, or phone..." 
                       value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search || $statusFilter !== ''): ?>
                    <a href="customers.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="table-responsive">
            <?php if (count($customers) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Newsletter</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= $customer['user_id'] ?></td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <?= strtoupper(substr($customer['first_name'] ?? 'U', 0, 1) . substr($customer['last_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div class="customer-details">
                                            <div class="customer-name">
                                                <?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?>
                                            </div>
                                            <div class="customer-email">
                                                <?= htmlspecialchars($customer['email']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($customer['phone'] ?: '-') ?></td>
                                <td>
                                    <?php if ($customer['order_count'] > 0): ?>
                                        <a href="orders.php?customer=<?= $customer['user_id'] ?>">
                                            <?= $customer['order_count'] ?> orders
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No orders</span>
                                    <?php endif; ?>
                                </td>
                                <td>€<?= number_format($customer['total_spent'], 2, ',', '.') ?></td>
                                <td><?= date('d.m.Y', strtotime($customer['created_at'])) ?></td>
                                <td>
                                    <?php if ($customer['newsletter_subscribed']): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $customer['is_active'] ? 'success' : 'danger' ?>">
                                        <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="customer-actions">
                                        <a href="customer-detail.php?id=<?= $customer['user_id'] ?>" 
                                           class="btn btn-small btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $customer['user_id'] ?>">
                                            <button type="submit" name="toggle_status" 
                                                    class="btn btn-small btn-<?= $customer['is_active'] ? 'warning' : 'success' ?>"
                                                    title="<?= $customer['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                <i class="fas fa-<?= $customer['is_active'] ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 40px; color: #7f8c8d;">
                    <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p>No customers found</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>" 
                       class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function confirmDelete(customerId) {
            if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                window.location.href = 'customers.php?delete=' + customerId;
            }
        }
    </script>
</body>
</html>
