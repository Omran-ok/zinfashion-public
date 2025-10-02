<?php
/**
 * ZIN Fashion - Forgot Password Page
 * Location: /public_html/dev_staging/forgot-password.php
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/account/dashboard');
}

$pdo = getDBConnection();
$error = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = $lang['invalid_request'] ?? 'Invalid request. Please try again.';
    } else if (empty($email)) {
        $error = $lang['email_required'] ?? 'Please enter your email address.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $lang['invalid_email'] ?? 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $sql = "SELECT user_id, first_name FROM users WHERE email = :email AND is_active = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            
            // Store token in database
            $sql = "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) 
                    VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $user['user_id'],
                'token' => $tokenHash
            ]);
            
            // Send reset email
            $resetLink = SITE_URL . "/reset-password?token=" . $token;
            $emailBody = "
                <h2>Password Reset Request</h2>
                <p>Hello {$user['first_name']},</p>
                <p>You requested to reset your password for your ZIN Fashion account.</p>
                <p>Please click the link below to reset your password:</p>
                <p><a href='{$resetLink}'>Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
                <br>
                <p>Best regards,<br>ZIN Fashion Team</p>
            ";
            
            sendEmail($email, "Password Reset - ZIN Fashion", $emailBody);
            
            // Log activity
            logActivity('password_reset_request', ['email' => $email]);
            
            $success = true;
        } else {
            // Don't reveal if email exists or not for security
            $success = true;
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
    <title><?= $lang['forgot_password'] ?? 'Forgot Password' ?> - ZIN Fashion</title>
    
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
                    <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-envelope"></i>
                        <h2><?= $lang['email_sent'] ?? 'Email Sent!' ?></h2>
                        <p><?= $lang['reset_email_message'] ?? 'If an account exists with this email address, you will receive password reset instructions shortly.' ?></p>
                        <a href="/login" class="btn btn-primary">
                            <?= $lang['back_to_login'] ?? 'Back to Login' ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="auth-header">
                        <h1><?= $lang['forgot_password'] ?? 'Forgot Password' ?></h1>
                        <p><?= $lang['forgot_password_subtitle'] ?? 'Enter your email address and we\'ll send you a link to reset your password.' ?></p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
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
                                   placeholder="<?= $lang['enter_email'] ?? 'Enter your registered email' ?>"
                                   required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i>
                            <?= $lang['send_reset_link'] ?? 'Send Reset Link' ?>
                        </button>
                    </form>
                    
                    <div class="auth-divider">
                        <span><?= $lang['or'] ?? 'OR' ?></span>
                    </div>
                    
                    <div class="auth-footer">
                        <p><?= $lang['remember_password'] ?? 'Remember your password?' ?></p>
                        <a href="/login" class="btn btn-outline">
                            <?= $lang['back_to_login'] ?? 'Back to Login' ?>
                        </a>
                    </div>
                    <?php endif; ?>
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
