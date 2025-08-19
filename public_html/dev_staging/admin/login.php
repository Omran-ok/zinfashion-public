<?php
/**
 * ZIN Fashion - Admin Login (with translations)
 * Location: /public_html/dev_staging/admin/login.php
 */

session_start();
require_once '../includes/config.php';
require_once 'includes/translations.php';

// Handle language switch
if (isset($_GET['lang'])) {
    setAdminLanguage($_GET['lang']);
    header('Location: login.php');
    exit;
}

$currentLang = getAdminLanguage();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $pdo = getDBConnection();
        
        // Get admin user
        $sql = "SELECT admin_id, username, password_hash, first_name, last_name, role_id 
                FROM admin_users 
                WHERE username = :username AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Login successful
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['admin_role'] = $admin['role_id'];
            
            // Update last login
            $updateSql = "UPDATE admin_users SET last_login = NOW() WHERE admin_id = :admin_id";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute(['admin_id' => $admin['admin_id']]);
            
            // Log activity
            $logSql = "INSERT INTO admin_activity_log (admin_id, action_type, action_description, ip_address, created_at) 
                      VALUES (:admin_id, 'login', 'Admin login successful', :ip, NOW())";
            $logStmt = $pdo->prepare($logSql);
            $logStmt->execute([
                'admin_id' => $admin['admin_id'],
                'ip' => getUserIP()
            ]);
            
            header('Location: index.php');
            exit;
        } else {
            $error = __('invalid_credentials');
        }
    } else {
        $error = __('please_enter_credentials');
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .language-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 5px;
        }
        
        .lang-btn {
            padding: 6px 12px;
            border: 1px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            transition: all 0.3s;
        }
        
        .lang-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .lang-btn.active {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher">
        <a href="?lang=de" class="lang-btn <?= $currentLang == 'de' ? 'active' : '' ?>">🇩🇪 DE</a>
        <a href="?lang=en" class="lang-btn <?= $currentLang == 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
    </div>

    <div class="login-container">
        <div class="logo">
            <img src="../assets/images/logo.svg" alt="ZIN Fashion">
            <h1><?= __('admin_panel') ?></h1>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><?= __('username') ?></label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password"><?= __('password') ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login"><?= __('login') ?></button>
        </form>
        
        <div class="info-message">
            <strong><?= __('first_login') ?></strong><br>
            <?= __('default_credentials') ?>: admin / Admin123!<br>
            <?= __('change_after_login') ?>
        </div>
        
        <div class="back-link">
            <a href="../">← <?= __('back_to_website') ?></a>
        </div>
    </div>
</body>
</html>
