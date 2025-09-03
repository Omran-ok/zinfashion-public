<?php
/**
 * ZIN Fashion - Orders Management
 * Location: /public_html/dev_staging/admin/orders.php
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
    header('Location: orders.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $orderStatus = sanitizeInput($_POST['order_status']);
    $paymentStatus = sanitizeInput($_POST['payment_status']);
    $adminNotes = sanitizeInput($_POST['admin_notes']);
    
    $updateSql = "UPDATE orders 
                  SET order_status = :order_status,
                      payment_status = :payment_status,
                      admin_notes = :admin_notes,
                      updated_at = NOW()
                  WHERE order_id = :order_id";
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'order_status' => $orderStatus,
        'payment_status' => $paymentStatus,
        'admin_notes' => $adminNotes,
        'order_id' => $orderId
    ]);
    
    // Log the status change
    $logSql = "INSERT INTO order_status_history (order_id, status, changed_by, changed_at, notes) 
               VALUES (:order_id, :status, :changed_by, NOW(), :notes)";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([
        'order_id' => $orderId,
        'status' => $orderStatus,
        'changed_by' => $_SESSION['admin_id'],
        'notes' => "Status changed to: $orderStatus, Payment: $paymentStatus"
    ]);
    
    // Send email notification to customer (if needed)
    // sendOrderStatusEmail($orderId, $orderStatus);
    
    $message = "Order status updated successfully!";
    $messageType = 'success';
}

// Get filter parameters
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$filterPayment = isset($_GET['payment']) ? sanitizeInput($_GET['payment']) : '';
$filterDate = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query with filters
$sql = "SELECT o.*, 
        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as customer_name,
        COALESCE(u.email, o.guest_email) as customer_email,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE 1=1";

$params = [];

if ($filterStatus) {
    $sql .= " AND o.order_status = :status";
    $params['status'] = $filterStatus;
}

if ($filterPayment) {
    $sql .= " AND o.payment_status = :payment";
    $params['payment'] = $filterPayment;
}

if ($filterDate) {
    switch($filterDate) {
        case 'today':
            $sql .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $sql .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($searchTerm) {
    $sql .= " AND (o.order_number LIKE :search 
              OR u.email LIKE :search 
              OR o.guest_email LIKE :search
              OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
    $params['search'] = "%$searchTerm%";
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE 1=1";

if ($filterStatus) {
    $countSql .= " AND o.order_status = :status";
}

if ($filterPayment) {
    $countSql .= " AND o.payment_status = :payment";
}

if ($filterDate) {
    switch($filterDate) {
        case 'today':
            $countSql .= " AND DATE(o.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $countSql .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $countSql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $countSql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if ($searchTerm) {
    $countSql .= " AND (o.order_number LIKE :search 
              OR u.email LIKE :search 
              OR o.guest_email LIKE :search
              OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
}

$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Add pagination to main query
$sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

// Get order statistics
$statsSql = "SELECT 
             COUNT(*) as total_orders,
             SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
             SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
             SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
             SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
             SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
             SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
             SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
             FROM orders";
$statsStmt = $pdo->query($statsSql);
$stats = $statsStmt->fetch();

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'badge-warning';
        case 'processing': return 'badge-info';
        case 'shipped': return 'badge-primary';
        case 'delivered': return 'badge-success';
        case 'cancelled': return 'badge-danger';
        case 'refunded': return 'badge-secondary';
        default: return 'badge-secondary';
    }
}

function getPaymentBadgeClass($status) {
    switch($status) {
        case 'pending': return 'badge-warning';
        case 'paid': return 'badge-success';
        case 'failed': return 'badge-danger';
        case 'refunded': return 'badge-secondary';
        default: return 'badge-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('orders') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .stats-grid {
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
        
        .stat-card.primary .value {
            color: #3498db;
        }
        
        .stat-card.success .value {
            color: #27ae60;
        }
        
        .stat-card.warning .value {
            color: #f39c12;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .orders-table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
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
        
        .pagination .disabled {
            padding: 8px 12px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #999;
        }
        
        .order-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .order-modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 10px;
            padding: 30px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        
        .detail-section h4 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .detail-label {
            color: #7f8c8d;
        }
        
        .detail-value {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .order-items-table {
            width: 100%;
            margin-top: 20px;
        }
        
        .order-items-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
        }
        
        .order-items-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .status-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group select,
        .form-group textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .timeline {
            margin-top: 20px;
        }
        
        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .timeline-content {
            flex: 1;
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
        
        .timeline-date {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        
        .print-button {
            background: #95a5a6;
            color: white;
        }
        
        .print-button:hover {
            background: #7f8c8d;
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
            <li><a href="products.php"><i class="fas fa-box"></i> <?= __('products') ?></a></li>
            <li><a href="categories.php"><i class="fas fa-list"></i> <?= __('categories') ?></a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
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
                <h1><?= __('orders') ?></h1>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['today_orders'] ?></div>
                <div class="label">Today's Orders</div>
            </div>
            <div class="stat-card success">
                <div class="value">€<?= number_format($stats['today_revenue'] ?? 0, 2, ',', '.') ?></div>
                <div class="label">Today's Revenue</div>
            </div>
            <div class="stat-card warning">
                <div class="value"><?= $stats['pending_orders'] ?></div>
                <div class="label">Pending Orders</div>
            </div>
            <div class="stat-card primary">
                <div class="value"><?= $stats['processing_orders'] ?></div>
                <div class="label">Processing</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['shipped_orders'] ?></div>
                <div class="label">Shipped</div>
            </div>
            <div class="stat-card success">
                <div class="value">€<?= number_format($stats['total_revenue'] ?? 0, 2, ',', '.') ?></div>
                <div class="label">Total Revenue</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Search Order</label>
                        <input type="text" name="search" placeholder="Order #, Email, Name..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Order Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?= $filterStatus == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $filterStatus == 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $filterStatus == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $filterStatus == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $filterStatus == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Payment Status</label>
                        <select name="payment">
                            <option value="">All Payments</option>
                            <option value="pending" <?= $filterPayment == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $filterPayment == 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= $filterPayment == 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= $filterPayment == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Date Range</label>
                        <select name="date">
                            <option value="">All Time</option>
                            <option value="today" <?= $filterDate == 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="yesterday" <?= $filterDate == 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                            <option value="week" <?= $filterDate == 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                            <option value="month" <?= $filterDate == 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <div class="export-buttons" style="margin-left: auto;">
                        <button type="button" onclick="exportOrders('csv')" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </button>
                        <button type="button" onclick="exportOrders('pdf')" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th width="100">Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($order['customer_name'] ?: 'Guest') ?><br>
                                    <small style="color: #999;"><?= htmlspecialchars($order['customer_email']) ?></small>
                                </td>
                                <td>
                                    <?= date('d.m.Y', strtotime($order['created_at'])) ?><br>
                                    <small style="color: #999;"><?= date('H:i', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td><?= $order['item_count'] ?></td>
                                <td>
                                    <strong>€<?= number_format($order['total_amount'], 2, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= getPaymentBadgeClass($order['payment_status']) ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= getStatusBadgeClass($order['order_status']) ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="viewOrder(<?= $order['order_id'] ?>)" 
                                                class="btn btn-primary btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="printInvoice(<?= $order['order_id'] ?>)" 
                                                class="btn btn-secondary btn-sm" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                                            <button onclick="quickUpdateStatus(<?= $order['order_id'] ?>, 'shipped')" 
                                                    class="btn btn-info btn-sm" title="Mark as Shipped">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-shopping-cart" style="font-size: 48px; color: #ddd;"></i>
                                <p>No orders found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPayment ? '&payment='.$filterPayment : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?><?= $searchTerm ? '&search='.$searchTerm : '' ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $page - 1 ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPayment ? '&payment='.$filterPayment : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?><?= $searchTerm ? '&search='.$searchTerm : '' ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                    <span class="disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>

                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <a href="?page=<?= $i ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPayment ? '&payment='.$filterPayment : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?><?= $searchTerm ? '&search='.$searchTerm : '' ?>" 
                       class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPayment ? '&payment='.$filterPayment : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?><?= $searchTerm ? '&search='.$searchTerm : '' ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?= $totalPages ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterPayment ? '&payment='.$filterPayment : '' ?><?= $filterDate ? '&date='.$filterDate : '' ?><?= $searchTerm ? '&search='.$searchTerm : '' ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class="fas fa-angle-right"></i></span>
                    <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Modal -->
    <div class="order-modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Order Details</h2>
                <button class="modal-close" onclick="closeOrderModal()">×</button>
            </div>
            <div id="orderDetailsContent">
                <!-- Order details will be loaded here via AJAX -->
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

        async function viewOrder(orderId) {
            const modal = document.getElementById('orderModal');
            const content = document.getElementById('orderDetailsContent');
            
            // Show loading
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 48px;"></i></div>';
            modal.classList.add('active');
            
            // Fetch order details
            try {
                const response = await fetch(`order-details.php?id=${orderId}`);
                const html = await response.text();
                content.innerHTML = html;
            } catch (error) {
                content.innerHTML = '<div style="text-align: center; padding: 40px; color: red;">Error loading order details</div>';
            }
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        async function quickUpdateStatus(orderId, status) {
            if (confirm(`Update order status to ${status}?`)) {
                const formData = new FormData();
                formData.append('update_status', '1');
                formData.append('order_id', orderId);
                formData.append('order_status', status);
                formData.append('payment_status', 'paid'); // Keep existing
                formData.append('admin_notes', `Status updated to ${status} via quick action`);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    }
                } catch (error) {
                    alert('Error updating order status');
                }
            }
        }

        function printInvoice(orderId) {
            window.open(`invoice.php?id=${orderId}`, '_blank', 'width=800,height=600');
        }

        function exportOrders(format) {
            const params = new URLSearchParams(window.location.search);
            params.append('export', format);
            window.location.href = `export-orders.php?${params.toString()}`;
        }

        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>
