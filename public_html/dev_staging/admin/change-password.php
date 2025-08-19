<?php
/**
 * ZIN Fashion - Change Password (with translations)
 * Location: /public_html/dev_staging/admin/change-password.php
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
    header('Location: change-password.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = __('all_fields_required');
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = __('passwords_not_match');
        $messageType = 'error';
    } elseif (strlen($newPassword) < 8) {
        $message = __('min_8_chars');
        $messageType = 'error';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $message = __('one_uppercase');
        $messageType = 'error';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $message = __('one_lowercase');
        $messageType = 'error';
    } elseif (!preg_match('/[0-9]/', $newPassword)) {
        $message = __('one_number');
        $messageType = 'error';
    } else {
        // Get current admin data
        $sql = "SELECT password_hash FROM admin_users WHERE admin_id = :admin_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['admin_id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        
        // Verify current password
        if (!password_verify($currentPassword, $admin['password_hash'])) {
            $message = __('incorrect_password');
            $messageType = 'error';
        } else {
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateSql = "UPDATE admin_users SET password_hash = :hash, updated_at = NOW() WHERE admin_id = :admin_id";
            $updateStmt = $pdo->prepare($updateSql);
            $result = $updateStmt->execute([
                'hash' => $newHash,
                'admin_id' => $_SESSION['admin_id']
            ]);
            
            if ($result) {
                // Log the password change
                $logSql = "INSERT INTO admin_activity_log (admin_id, action_type, action_description, ip_address, created_at) 
                          VALUES (:admin_id, 'password_change', 'Admin password changed', :ip, NOW())";
                $logStmt = $pdo->prepare($logSql);
                $logStmt->execute([
                    'admin_id' => $_SESSION['admin_id'],
                    'ip' => getUserIP()
                ]);
                
                $message = __('password_changed');
                $messageType = 'success';
            } else {
                $message = __('error');
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('change_password') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .password-form {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .password-requirements h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #7f8c8d;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .show-password {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
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
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
            <li><a href="newsletter.php"><i class="fas fa-envelope"></i> <?= __('newsletter') ?></a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> <?= __('settings') ?></a></li>
            <li><a href="change-password.php" class="active"><i class="fas fa-key"></i> <?= __('change_password') ?></a></li>
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
                <h1><?= __('change_password') ?></h1>
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

        <!-- Password Change Form -->
        <div class="password-form">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="password-requirements">
                <h4><?= __('password_requirements') ?>:</h4>
                <ul>
                    <li><?= __('min_8_chars') ?></li>
                    <li><?= __('one_uppercase') ?></li>
                    <li><?= __('one_lowercase') ?></li>
                    <li><?= __('one_number') ?></li>
                    <li><?= __('special_chars_recommended') ?></li>
                </ul>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password"><?= __('current_password') ?></label>
                    <div class="show-password">
                        <input type="password" id="current_password" name="current_password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password"><?= __('new_password') ?></label>
                    <div class="show-password">
                        <input type="password" id="new_password" name="new_password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password"><?= __('confirm_password') ?></label>
                    <div class="show-password">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= __('change_password') ?>
                    </button>
                    <a href="index.php" class="btn-secondary">
                        <i class="fas fa-times"></i> <?= __('cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('expanded');
        }

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
