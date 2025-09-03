<?php
/**
 * ZIN Fashion - Newsletter Management
 * Location: /public_html/dev_staging/admin/newsletter.php
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
    header('Location: newsletter.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Handle export
if (isset($_GET['export'])) {
    $sql = "SELECT email, preferred_language, subscribed_at FROM newsletter_subscribers WHERE is_active = 1";
    $stmt = $pdo->query($sql);
    $subscribers = $stmt->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Email', 'Language', 'Subscribed Date']);
    
    foreach ($subscribers as $subscriber) {
        fputcsv($output, [
            $subscriber['email'],
            $subscriber['preferred_language'],
            $subscriber['subscribed_at']
        ]);
    }
    fclose($output);
    exit;
}

// Handle unsubscribe
if (isset($_POST['unsubscribe'])) {
    $subscriberId = intval($_POST['subscriber_id']);
    $sql = "UPDATE newsletter_subscribers SET is_active = 0, unsubscribed_at = NOW() WHERE subscriber_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $subscriberId]);
    $message = "Subscriber unsubscribed successfully!";
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $subscriberId = intval($_GET['delete']);
    $sql = "DELETE FROM newsletter_subscribers WHERE subscriber_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $subscriberId]);
    header('Location: newsletter.php?msg=deleted');
    exit;
}

// Get statistics
$statsSql = "SELECT 
             COUNT(*) as total,
             SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
             SUM(CASE WHEN preferred_language = 'de' THEN 1 ELSE 0 END) as german,
             SUM(CASE WHEN preferred_language = 'en' THEN 1 ELSE 0 END) as english,
             SUM(CASE WHEN preferred_language = 'ar' THEN 1 ELSE 0 END) as arabic,
             SUM(CASE WHEN subscribed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
             FROM newsletter_subscribers";
$statsStmt = $pdo->query($statsSql);
$stats = $statsStmt->fetch();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '1'; // Default to active

$where = [];
$params = [];

if ($search) {
    $where[] = "email LIKE :search";
    $params['search'] = "%$search%";
}

if ($statusFilter !== '') {
    $where[] = "is_active = :status";
    $params['status'] = $statusFilter;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) FROM newsletter_subscribers $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalSubscribers = $countStmt->fetchColumn();
$totalPages = ceil($totalSubscribers / $perPage);

// Get subscribers
$sql = "SELECT * FROM newsletter_subscribers 
        $whereClause
        ORDER BY subscribed_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$subscribers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('newsletter') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .newsletter-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card-small {
            background: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card-small .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-card-small .label {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .toolbar {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
        }
        
        .search-form input {
            flex: 1;
            max-width: 300px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .toolbar-actions {
            display: flex;
            gap: 10px;
        }
        
        .lang-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .lang-badge.de { background: #f39c12; color: white; }
        .lang-badge.en { background: #3498db; color: white; }
        .lang-badge.ar { background: #27ae60; color: white; }
        
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
            <li><a href="customers.php"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
            <li><a href="newsletter.php" class="active"><i class="fas fa-envelope"></i> <?= __('newsletter') ?></a></li>
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
                <h1><?= __('newsletter') ?></h1>
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
                Subscriber deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="newsletter-stats">
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['total'] ?? 0) ?></div>
                <div class="label">Total Subscribers</div>
            </div>
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['active'] ?? 0) ?></div>
                <div class="label">Active</div>
            </div>
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['german'] ?? 0) ?></div>
                <div class="label">German (DE)</div>
            </div>
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['english'] ?? 0) ?></div>
                <div class="label">English (EN)</div>
            </div>
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['arabic'] ?? 0) ?></div>
                <div class="label">Arabic (AR)</div>
            </div>
            <div class="stat-card-small">
                <div class="number"><?= number_format($stats['new_this_month'] ?? 0) ?></div>
                <div class="label">New (30 days)</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <form class="search-form" method="GET">
                <input type="text" name="search" placeholder="Search by email..." 
                       value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Inactive</option>
                    <option value="">All</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
            
            <div class="toolbar-actions">
                <a href="?export=1" class="btn btn-success">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <button class="btn btn-primary" onclick="showSendNewsletter()">
                    <i class="fas fa-paper-plane"></i> Send Newsletter
                </button>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Language</th>
                        <th>Subscribed Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <tr>
                            <td><?= $subscriber['subscriber_id'] ?></td>
                            <td><?= htmlspecialchars($subscriber['email']) ?></td>
                            <td>
                                <span class="lang-badge <?= $subscriber['preferred_language'] ?>">
                                    <?= strtoupper($subscriber['preferred_language']) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($subscriber['subscribed_at'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $subscriber['is_active'] ? 'success' : 'danger' ?>">
                                    <?= $subscriber['is_active'] ? 'Active' : 'Unsubscribed' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($subscriber['is_active']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="subscriber_id" value="<?= $subscriber['subscriber_id'] ?>">
                                        <button type="submit" name="unsubscribe" class="btn btn-small btn-warning"
                                                onclick="return confirm('Unsubscribe this email?')">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="?delete=<?= $subscriber['subscriber_id'] ?>" 
                                   class="btn btn-small btn-danger"
                                   onclick="return confirm('Delete this subscriber permanently?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
        
        function showSendNewsletter() {
            alert('Newsletter sending feature will be implemented with email configuration.');
            // This would open a modal or redirect to a newsletter composer page
        }
    </script>
</body>
</html>
