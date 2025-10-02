<?php
/**
 * ZIN Fashion - Login Page
 * Location: /public_html/dev_staging/login.php
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? '/account/dashboard';
    redirect($redirect);
}

$pdo = getDBConnection();
$error = '';
$success = getFlashMessage('success');

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = $lang['invalid_request'] ?? 'Invalid request. Please try again.';
    } else if (empty($email) || empty($password)) {
        $error = $lang['fill_all_fields'] ?? 'Please fill in all fields.';
    } else {
        // Check user credentials
        $sql = "SELECT user_id, email, password_hash, first_name, last_name, is_active, is_verified 
                FROM users WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if (!$user['is_active']) {
                $error = $lang['account_disabled'] ?? 'Your account has been disabled.';
            } else if (!$user['is_verified']) {
                $error = $lang['email_not_verified'] ?? 'Please verify your email address first.';
            } else {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Handle remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    
                    // Store token in database
                    $sql = "INSERT INTO user_sessions (session_id, user_id, expires_at) 
                            VALUES (:token, :user_id, DATE_ADD(NOW(), INTERVAL 30 DAY))";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['token' => $tokenHash, 'user_id' => $user['user_id']]);
                    
                    // Set cookie
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                }
                
                // Update last login
                $sql = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $user['user_id']]);
                
                // Log activity
                logActivity('user_login', ['email' => $email]);
                
                // Redirect
                $redirect = $_GET['redirect'] ?? '/account/dashboard';
                redirect($redirect);
            }
        } else {
            $error = $lang['invalid_credentials'] ?? 'Invalid email or password.';
            
            // Log failed attempt
            $sql = "INSERT INTO login_attempts (email, ip_address, success) VALUES (:email, :ip, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email, 'ip' => getUserIP()]);
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['login'] ?? 'Login' ?> - ZIN Fashion</title>
    
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
    
    <?php include 'includes/components/header.php'; ?>
    
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-box">
                    <div class="auth-header">
                        <h1><?= $lang['login'] ?? 'Login' ?></h1>
                        <p><?= $lang['login_subtitle'] ?? 'Welcome back! Please login to your account.' ?></p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                <?= $lang['email'] ?? 'Email Address' ?>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="<?= $lang['enter_email'] ?? 'Enter your email' ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i>
                                <?= $lang['password'] ?? 'Password' ?>
                            </label>
                            <div class="password-input">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       placeholder="<?= $lang['enter_password'] ?? 'Enter your password' ?>"
                                       required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-check">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember"><?= $lang['remember_me'] ?? 'Remember me' ?></label>
                            </div>
                            <a href="/forgot-password" class="form-link">
                                <?= $lang['forgot_password'] ?? 'Forgot Password?' ?>
                            </a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i>
                            <?= $lang['login'] ?? 'Login' ?>
                        </button>
                    </form>
                    
                    <div class="auth-divider">
                        <span><?= $lang['or'] ?? 'OR' ?></span>
                    </div>
                    
                    <div class="social-login">
                        <button class="btn-social btn-google" disabled>
                            <i class="fab fa-google"></i>
                            <?= $lang['login_with_google'] ?? 'Login with Google' ?>
                        </button>
                        <button class="btn-social btn-facebook" disabled>
                            <i class="fab fa-facebook-f"></i>
                            <?= $lang['login_with_facebook'] ?? 'Login with Facebook' ?>
                        </button>
                    </div>
                    
                    <div class="auth-footer">
                        <p><?= $lang['new_customer'] ?? "Don't have an account?" ?></p>
                        <a href="/register" class="btn btn-outline">
                            <?= $lang['create_account'] ?? 'Create Account' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/components/footer.php'; ?>
    
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/auth.js"></script>
</body>
</html>
