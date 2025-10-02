<?php
/**
 * ZIN Fashion - Registration Page
 * Location: /public_html/dev_staging/register.php
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/account/dashboard');
}

$pdo = getDBConnection();
$errors = [];
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $newsletter = isset($_POST['newsletter']);
    $terms = isset($_POST['terms']);
    
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = $lang['invalid_request'] ?? 'Invalid request. Please try again.';
    }
    
    // Validate required fields
    if (empty($firstName)) {
        $errors[] = $lang['first_name_required'] ?? 'First name is required.';
    }
    if (empty($lastName)) {
        $errors[] = $lang['last_name_required'] ?? 'Last name is required.';
    }
    if (empty($email)) {
        $errors[] = $lang['email_required'] ?? 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $lang['invalid_email'] ?? 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors[] = $lang['password_required'] ?? 'Password is required.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = $lang['password_too_short'] ?? 'Password must be at least 8 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = $lang['password_mismatch'] ?? 'Passwords do not match.';
    }
    if (!$terms) {
        $errors[] = $lang['accept_terms'] ?? 'You must accept the terms and conditions.';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $sql = "SELECT user_id FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            $errors[] = $lang['email_exists'] ?? 'This email is already registered.';
        }
    }
    
    // Create account if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $sql = "INSERT INTO users (
                        email, password_hash, first_name, last_name, phone,
                        newsletter_subscribed, verification_token, created_at
                    ) VALUES (
                        :email, :password, :first_name, :last_name, :phone,
                        :newsletter, :token, NOW()
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'newsletter' => $newsletter ? 1 : 0,
                'token' => $verificationToken
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Add newsletter subscription if requested
            if ($newsletter) {
                $sql = "INSERT INTO newsletter_subscribers (email, preferred_language, subscribed_at, confirmed_at) 
                        VALUES (:email, :lang, NOW(), NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['email' => $email, 'lang' => $currentLang]);
            }
            
            // Record consent for GDPR
            $sql = "INSERT INTO user_consent (user_id, consent_type, consent_given, ip_address, consent_date) 
                    VALUES (:user_id, 'terms_of_service', 1, :ip, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'ip' => getUserIP()]);
            
            if ($newsletter) {
                $sql = "INSERT INTO user_consent (user_id, consent_type, consent_given, ip_address, consent_date) 
                        VALUES (:user_id, 'newsletter', 1, :ip, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId, 'ip' => getUserIP()]);
            }
            
            $pdo->commit();
            
            // Send verification email (simplified for now)
            $verificationLink = SITE_URL . "/verify-email?token=" . $verificationToken;
            $emailBody = "Welcome to ZIN Fashion! Please verify your email by clicking: " . $verificationLink;
            sendEmail($email, "Verify Your Email - ZIN Fashion", $emailBody);
            
            // Log activity
            logActivity('user_registration', ['email' => $email]);
            
            $success = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = $lang['registration_error'] ?? 'An error occurred during registration. Please try again.';
            error_log("Registration error: " . $e->getMessage());
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
    <title><?= $lang['register'] ?? 'Register' ?> - ZIN Fashion</title>
    
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
                <div class="auth-box auth-box-large">
                    <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h2><?= $lang['registration_successful'] ?? 'Registration Successful!' ?></h2>
                        <p><?= $lang['verification_email_sent'] ?? 'We have sent a verification email to your address. Please check your inbox.' ?></p>
                        <a href="/login" class="btn btn-primary">
                            <?= $lang['go_to_login'] ?? 'Go to Login' ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="auth-header">
                        <h1><?= $lang['create_account'] ?? 'Create Account' ?></h1>
                        <p><?= $lang['register_subtitle'] ?? 'Join us for exclusive offers and seamless shopping.' ?></p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="auth-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">
                                    <i class="fas fa-user"></i>
                                    <?= $lang['first_name'] ?? 'First Name' ?> *
                                </label>
                                <input type="text" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                       placeholder="<?= $lang['enter_first_name'] ?? 'Enter your first name' ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">
                                    <i class="fas fa-user"></i>
                                    <?= $lang['last_name'] ?? 'Last Name' ?> *
                                </label>
                                <input type="text" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                       placeholder="<?= $lang['enter_last_name'] ?? 'Enter your last name' ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                <?= $lang['email'] ?? 'Email Address' ?> *
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="<?= $lang['enter_email'] ?? 'Enter your email' ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i>
                                <?= $lang['phone'] ?? 'Phone Number' ?>
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   placeholder="<?= $lang['enter_phone'] ?? 'Enter your phone number' ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i>
                                    <?= $lang['password'] ?? 'Password' ?> *
                                </label>
                                <div class="password-input">
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           placeholder="<?= $lang['min_8_chars'] ?? 'Minimum 8 characters' ?>"
                                           required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i>
                                    <?= $lang['confirm_password'] ?? 'Confirm Password' ?> *
                                </label>
                                <div class="password-input">
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="<?= $lang['re_enter_password'] ?? 'Re-enter your password' ?>"
                                           required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="newsletter" name="newsletter" <?= isset($_POST['newsletter']) ? 'checked' : '' ?>>
                            <label for="newsletter">
                                <?= $lang['subscribe_newsletter'] ?? 'Subscribe to our newsletter for exclusive offers' ?>
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">
                                <?= $lang['agree_to'] ?? 'I agree to the' ?> 
                                <a href="/agb" target="_blank"><?= $lang['terms_conditions'] ?? 'Terms & Conditions' ?></a> 
                                <?= $lang['and'] ?? 'and' ?> 
                                <a href="/datenschutz" target="_blank"><?= $lang['privacy_policy'] ?? 'Privacy Policy' ?></a> *
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i>
                            <?= $lang['create_account'] ?? 'Create Account' ?>
                        </button>
                    </form>
                    
                    <div class="auth-divider">
                        <span><?= $lang['or'] ?? 'OR' ?></span>
                    </div>
                    
                    <div class="auth-footer">
                        <p><?= $lang['already_registered'] ?? 'Already have an account?' ?></p>
                        <a href="/login" class="btn btn-outline">
                            <?= $lang['login'] ?? 'Login' ?>
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
