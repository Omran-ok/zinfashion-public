<?php
/**
 * ZIN Fashion - Order Detail
 * Location: /public_html/dev_staging/admin/order-detail.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$orderId) {
    header('Location: orders.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();

// Get order details
$sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.order_id = :order_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['order_id' => $orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$itemsSql = "SELECT oi.*, p.product_name, p.product_name_en, p.product_name_ar, p.sku as product_sku,
             pv.variant_sku, s.size_name, c.color_name, pi.image_url
             FROM order_items oi
             JOIN products p ON oi.product_id = p.product_id
             LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
             LEFT JOIN sizes s ON pv.size_id = s.size_id
             LEFT JOIN colors c ON pv.color_id = c.color_id
             LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
             WHERE oi.order_id = :order_id";
$itemsStmt = $pdo->prepare($itemsSql);
$itemsStmt->execute(['order_id' => $orderId]);
$items = $itemsStmt->fetchAll();

// Get shipping address
$shippingSql = "SELECT * FROM order_shipping WHERE order_id = :order_id";
$shippingStmt = $pdo->prepare($shippingSql);
$shippingStmt->execute(['order_id' => $orderId]);
$shipping = $shippingStmt->fetch();

// Get billing address (if different)
$billingSql = "SELECT * FROM order_billing WHERE order_id = :order_id";
$billingStmt = $pdo->prepare($billingSql);
$billingStmt->execute(['order_id' => $orderId]);
$billing = $billingStmt->fetch();

// Handle status update
if (isset($_POST['update_status'])) {
    $newStatus = $_POST['order_status'];
    $paymentStatus = $_POST['payment_status'];
    $trackingNumber = $_POST['tracking_number'] ?? null;
    $adminNotes = $_POST['admin_notes'] ?? '';
    
    // Check if tracking_number column exists, if not use admin_notes for tracking
    $updateSql = "UPDATE orders SET 
                  order_status = :order_status,
                  payment_status = :payment_status,
                  admin_notes = :admin_notes,
                  updated_at = NOW()
                  WHERE order_id = :order_id";
    
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        'order_status' => $newStatus,
        'payment_status' => $paymentStatus,
        'admin_notes' => $adminNotes . ($trackingNumber ? "\nTracking: " . $trackingNumber : ''),
        'order_id' => $orderId
    ]);
    
    // Try to log status change if table exists
    try {
        $logSql = "INSERT INTO order_status_history (order_id, status, changed_by, changed_at, notes) 
                   VALUES (:order_id, :status, :changed_by, NOW(), :notes)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([
            'order_id' => $orderId,
            'status' => $newStatus,
            'changed_by' => $_SESSION['admin_id'] ?? 0,
            'notes' => "Status changed to: $newStatus, Payment: $paymentStatus"
        ]);
    } catch (Exception $e) {
        // Table might not exist, continue anyway
    }
    
    // Refresh order data
    $stmt->execute(['order_id' => $orderId]);
    $order = $stmt->fetch();
    
    $message = "Order status updated successfully!";
    $messageType = 'success';
}

// Try to get status history if table exists
$history = [];
try {
    $historySql = "SELECT * FROM order_status_history WHERE order_id = :order_id ORDER BY changed_at DESC";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute(['order_id' => $orderId]);
    $history = $historyStmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= htmlspecialchars($order['order_number']) ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .order-header {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .order-header-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
        }
        
        .order-number {
            font-size: 1.5rem;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .order-meta {
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .order-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .order-card h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }
        
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-item-variant {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .order-item-price {
            text-align: right;
        }
        
        .order-totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        
        .total-row.grand-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ecf0f1;
        }
        
        .address-block {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .status-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .form-group select,
        .form-group input,
        .form-group textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ecf0f1;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #3498db;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #ecf0f1;
        }
        
        .timeline-content {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .timeline-date {
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        
        .btn-print {
            background: #95a5a6;
            color: white;
        }
        
        .btn-print:hover {
            background: #7f8c8d;
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
            .order-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media print {
            .sidebar, .header, .btn, button {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
            }
            .order-grid {
                grid-template-columns: 1fr !important;
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
                <h1>Order Details</h1>
            </div>
            <div class="header-right">
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?id=<?= $orderId ?>&lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
                    <a href="?id=<?= $orderId ?>&lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
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

        <?php if (isset($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Order Header -->
        <div class="order-header">
            <div class="order-header-top">
                <div>
                    <div class="order-number">Order #<?= htmlspecialchars($order['order_number']) ?></div>
                    <div class="order-meta">
                        <i class="fas fa-calendar"></i> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                        <?php if ($order['user_id']): ?>
                            | <i class="fas fa-user"></i> 
                            <a href="customer-detail.php?id=<?= $order['user_id'] ?>">
                                <?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?>
                            </a>
                        <?php else: ?>
                            | <i class="fas fa-user"></i> Guest Order (<?= htmlspecialchars($order['guest_email']) ?>)
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button onclick="printOrder()" class="btn btn-print">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Order Status -->
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <span class="badge badge-<?= 
                    $order['order_status'] == 'delivered' ? 'success' : 
                    ($order['order_status'] == 'cancelled' ? 'danger' : 'warning') ?>" style="padding: 10px 15px;">
                    <i class="fas fa-box"></i> Order: <?= ucfirst($order['order_status']) ?>
                </span>
                <span class="badge badge-<?= 
                    $order['payment_status'] == 'paid' ? 'success' : 
                    ($order['payment_status'] == 'failed' ? 'danger' : 'warning') ?>" style="padding: 10px 15px;">
                    <i class="fas fa-credit-card"></i> Payment: <?= ucfirst($order['payment_status']) ?>
                </span>
                <?php if ($order['payment_method']): ?>
                    <span class="badge badge-info" style="padding: 10px 15px;">
                        <i class="fas fa-wallet"></i> Method: <?= ucfirst($order['payment_method']) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Content Grid -->
        <div class="order-grid">
            <!-- Left Column -->
            <div>
                <!-- Order Items -->
                <div class="order-card">
                    <h3><i class="fas fa-shopping-bag"></i> Order Items</h3>
                    <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <img src="<?= htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                            <div class="order-item-details">
                                <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div class="order-item-variant">
                                    SKU: <?= htmlspecialchars($item['variant_sku'] ?? $item['product_sku']) ?>
                                    <?php if ($item['size_name']): ?>
                                        | Size: <?= htmlspecialchars($item['size_name']) ?>
                                    <?php endif; ?>
                                    <?php if ($item['color_name']): ?>
                                        | Color: <?= htmlspecialchars($item['color_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-variant">Qty: <?= $item['quantity'] ?> × €<?= number_format($item['price'], 2, ',', '.') ?></div>
                            </div>
                            <div class="order-item-price">
                                <strong>€<?= number_format($item['quantity'] * $item['price'], 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Order Totals -->
                    <div class="order-totals">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span>€<?= number_format($order['subtotal'], 2, ',', '.') ?></span>
                        </div>
                        <?php if ($order['discount_amount'] > 0): ?>
                            <div class="total-row">
                                <span>Discount</span>
                                <span style="color: #27ae60;">-€<?= number_format($order['discount_amount'], 2, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="total-row">
                            <span>Tax (19%)</span>
                            <span>€<?= number_format($order['tax_amount'], 2, ',', '.') ?></span>
                        </div>
                        <div class="total-row">
                            <span>Shipping</span>
                            <span>€<?= number_format($order['shipping_cost'], 2, ',', '.') ?></span>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total</span>
                            <span>€<?= number_format($order['total_amount'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Customer Notes -->
                <?php if ($order['customer_notes']): ?>
                    <div class="order-card">
                        <h3><i class="fas fa-comment"></i> Customer Notes</h3>
                        <p><?= nl2br(htmlspecialchars($order['customer_notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Update Status -->
                <div class="order-card">
                    <h3><i class="fas fa-edit"></i> Update Order</h3>
                    <form method="POST" class="status-form">
                        <div class="form-group">
                            <label>Order Status</label>
                            <select name="order_status">
                                <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $order['order_status'] == 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $order['order_status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="refunded" <?= $order['order_status'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status">
                                <option value="pending" <?= $order['payment_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="paid" <?= $order['payment_status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                <option value="failed" <?= $order['payment_status'] == 'failed' ? 'selected' : '' ?>>Failed</option>
                                <option value="refunded" <?= $order['payment_status'] == 'refunded' ? 'selected' : '' ?>>Refunded</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tracking Number</label>
                            <input type="text" name="tracking_number" 
                                   placeholder="Enter tracking number">
                        </div>
                        
                        <div class="form-group">
                            <label>Admin Notes</label>
                            <textarea name="admin_notes" rows="3" 
                                      placeholder="Internal notes..."><?= htmlspecialchars($order['admin_notes'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Update Order
                        </button>
                    </form>
                </div>

                <!-- Status History -->
                <?php if (count($history) > 0): ?>
                    <div class="order-card">
                        <h3><i class="fas fa-history"></i> Status History</h3>
                        <div class="timeline">
                            <?php foreach ($history as $event): ?>
                                <div class="timeline-item">
                                    <div class="timeline-content">
                                        <strong><?= ucfirst($event['status']) ?></strong>
                                        <div class="timeline-date">
                                            <?= date('d.m.Y H:i', strtotime($event['changed_at'])) ?>
                                        </div>
                                        <?php if ($event['notes']): ?>
                                            <div style="margin-top: 5px; font-size: 0.9rem;">
                                                <?= htmlspecialchars($event['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Order Information -->
                <div class="order-card">
                    <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <strong>Order ID:</strong> #<?= $order['order_id'] ?>
                        </div>
                        <div>
                            <strong>Language:</strong> <?= strtoupper($order['order_language'] ?? 'de') ?>
                        </div>
                        <div>
                            <strong>Currency:</strong> <?= $order['currency'] ?? 'EUR' ?>
                        </div>
                        <div>
                            <strong>Created:</strong> <?= date('d.m.Y H:i:s', strtotime($order['created_at'])) ?>
                        </div>
                        <div>
                            <strong>Updated:</strong> <?= date('d.m.Y H:i:s', strtotime($order['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>
