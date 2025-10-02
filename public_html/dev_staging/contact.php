<?php
/**
 * ZIN Fashion - Contact Page
 * Location: /public_html/dev_staging/contact.php
 * 
 * Contact form with business information and interactive map
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

// Initialize database connection for header component and form processing
$pdo = getDBConnection();

// Handle form submission
$success = false;
$error = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = true;
        $message = $lang['csrf_error'] ?? 'Security validation failed. Please try again.';
    } else {
        // Sanitize inputs
        $name = sanitizeInput($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone']);
        $subject = sanitizeInput($_POST['subject']);
        $messageText = sanitizeInput($_POST['message']);
        
        // Validate
        if (empty($name) || empty($email) || empty($subject) || empty($messageText)) {
            $error = true;
            $message = $lang['required_fields'] ?? 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = true;
            $message = $lang['invalid_email'] ?? 'Please enter a valid email address.';
        } else {
            // Save to database
            try {
                $sql = "INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, created_at) 
                        VALUES (:name, :email, :phone, :subject, :message, :ip, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'subject' => $subject,
                    'message' => $messageText,
                    'ip' => getUserIP()
                ]);
                
                // Send email notification
                $emailBody = "
                    <h3>New Contact Form Submission</h3>
                    <p><strong>Name:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Subject:</strong> $subject</p>
                    <p><strong>Message:</strong><br>$messageText</p>
                ";
                
                sendEmail(ADMIN_EMAIL, "New Contact Form: $subject", $emailBody);
                
                $success = true;
                $message = $lang['message_sent'] ?? 'Thank you for your message. We will get back to you soon!';
                
                // Clear form
                $name = $email = $phone = $subject = $messageText = '';
            } catch (Exception $e) {
                $error = true;
                $message = $lang['send_error'] ?? 'An error occurred. Please try again later.';
                error_log("Contact form error: " . $e->getMessage());
            }
        }
    }
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Prepare breadcrumbs (don't include 'Home' as breadcrumb.php will add it)
$breadcrumbs = [
    ['title' => $lang['contact'] ?? 'Contact', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['contact'] ?? 'Contact' ?> - ZIN Fashion</title>
    <meta name="description" content="Get in touch with ZIN Fashion. We're here to help with any questions about our products, orders, or services.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.svg">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Main Stylesheets -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/breadcrumb.css">
    <link rel="stylesheet" href="/assets/css/contact.css">
    
    <!-- RTL Support -->
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <?php include 'includes/components/header.php'; ?>
    
    <!-- Breadcrumb -->
    <?php include 'includes/components/breadcrumb.php'; ?>
    
    <!-- Contact Header -->
    <section class="contact-header">
        <div class="container">
            <h1 class="page-title animate-on-scroll"><?= $lang['get_in_touch'] ?? 'Get in Touch' ?></h1>
            <p class="page-subtitle animate-on-scroll">
                <?= $lang['contact_subtitle'] ?? 'We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.' ?>
            </p>
        </div>
    </section>
    
    <!-- Contact Content -->
    <section class="contact-content">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form-wrapper animate-on-scroll">
                    <h2><?= $lang['send_message'] ?? 'Send us a Message' ?></h2>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <?php elseif ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form class="contact-form" method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name"><?= $lang['your_name'] ?? 'Your Name' ?> *</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                       placeholder="<?= $lang['enter_name'] ?? 'Enter your name' ?>">
                            </div>
                            <div class="form-group">
                                <label for="email"><?= $lang['your_email'] ?? 'Your Email' ?> *</label>
                                <input type="email" id="email" name="email" required 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       placeholder="<?= $lang['enter_email'] ?? 'Enter your email' ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone"><?= $lang['phone_number'] ?? 'Phone Number' ?></label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       placeholder="<?= $lang['enter_phone'] ?? 'Enter your phone number' ?>">
                            </div>
                            <div class="form-group">
                                <label for="subject"><?= $lang['subject'] ?? 'Subject' ?> *</label>
                                <select id="subject" name="subject" required>
                                    <option value=""><?= $lang['select_subject'] ?? 'Select a subject' ?></option>
                                    <option value="General Inquiry" <?= ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : '' ?>>
                                        <?= $lang['general_inquiry'] ?? 'General Inquiry' ?>
                                    </option>
                                    <option value="Order Support" <?= ($_POST['subject'] ?? '') === 'Order Support' ? 'selected' : '' ?>>
                                        <?= $lang['order_support'] ?? 'Order Support' ?>
                                    </option>
                                    <option value="Product Information" <?= ($_POST['subject'] ?? '') === 'Product Information' ? 'selected' : '' ?>>
                                        <?= $lang['product_info'] ?? 'Product Information' ?>
                                    </option>
                                    <option value="Returns & Exchanges" <?= ($_POST['subject'] ?? '') === 'Returns & Exchanges' ? 'selected' : '' ?>>
                                        <?= $lang['returns_exchanges'] ?? 'Returns & Exchanges' ?>
                                    </option>
                                    <option value="Partnership" <?= ($_POST['subject'] ?? '') === 'Partnership' ? 'selected' : '' ?>>
                                        <?= $lang['partnership'] ?? 'Partnership' ?>
                                    </option>
                                    <option value="Other" <?= ($_POST['subject'] ?? '') === 'Other' ? 'selected' : '' ?>>
                                        <?= $lang['other'] ?? 'Other' ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="message"><?= $lang['your_message'] ?? 'Your Message' ?> *</label>
                            <textarea id="message" name="message" rows="6" required
                                      placeholder="<?= $lang['enter_message'] ?? 'Enter your message here...' ?>"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="privacy" required>
                                <span><?= $lang['privacy_agreement'] ?? 'I agree to the privacy policy and terms of service' ?> *</span>
                            </label>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-paper-plane"></i>
                            <?= $lang['send_message_btn'] ?? 'Send Message' ?>
                        </button>
                    </form>
                </div>
                
                <!-- Contact Information -->
                <div class="contact-info-wrapper">
                    <div class="contact-info-card animate-on-scroll">
                        <h3><?= $lang['contact_information'] ?? 'Contact Information' ?></h3>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <h4><?= $lang['address'] ?? 'Address' ?></h4>
                                <p>
                                    ZIN Fashion<br>
                                    Friedrich-Engels-Straße 3<br>
                                    39646 Oebisfelde<br>
                                    Deutschland
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-content">
                                <h4><?= $lang['phone'] ?? 'Phone' ?></h4>
                                <p>
                                    <a href="tel:+4915563576774">+49 15563 576774</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <h4><?= $lang['email'] ?? 'Email' ?></h4>
                                <p>
                                    <a href="mailto:trend@zinfashion.de">trend@zinfashion.de</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="info-content">
                                <h4><?= $lang['business_hours'] ?? 'Business Hours' ?></h4>
                                <p>
                                    <?= $lang['monday_friday'] ?? 'Monday - Friday' ?>: 9:00 - 18:00<br>
                                    <?= $lang['saturday'] ?? 'Saturday' ?>: 10:00 - 16:00<br>
                                    <?= $lang['sunday'] ?? 'Sunday' ?>: <?= $lang['closed'] ?? 'Closed' ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="social-contact">
                            <h4><?= $lang['follow_us'] ?? 'Follow Us' ?></h4>
                            <div class="social-links">
                                <a href="https://facebook.com/zinfashion" target="_blank" rel="noopener">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="https://instagram.com/zinfashion" target="_blank" rel="noopener">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="https://tiktok.com/@zinfashion" target="_blank" rel="noopener">
                                    <i class="fab fa-tiktok"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links Card -->
                    <div class="quick-links-card animate-on-scroll">
                        <h3><?= $lang['quick_links'] ?? 'Quick Links' ?></h3>
                        <ul>
                            <li>
                                <a href="/shipping">
                                    <i class="fas fa-truck"></i>
                                    <?= $lang['shipping_info'] ?? 'Shipping Information' ?>
                                </a>
                            </li>
                            <li>
                                <a href="/returns">
                                    <i class="fas fa-undo"></i>
                                    <?= $lang['return_policy'] ?? 'Return Policy' ?>
                                </a>
                            </li>
                            <li>
                                <a href="/size-guide">
                                    <i class="fas fa-ruler"></i>
                                    <?= $lang['size_guide'] ?? 'Size Guide' ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Map Section -->
    <section class="contact-map">
        <div class="map-container">
            <!-- OpenStreetMap iframe for Oebisfelde -->
            <iframe 
                src="https://www.openstreetmap.org/export/embed.html?bbox=11.1447%2C52.4183%2C11.2047%2C52.4383&amp;layer=mapnik&amp;marker=52.4283%2C11.1747" 
                loading="lazy">
            </iframe>
        </div>
        <div class="map-overlay animate-on-scroll">
            <div class="map-info">
                <h3><?= $lang['visit_us'] ?? 'Visit Us' ?></h3>
                <p>ZIN Fashion Store<br>Friedrich-Engels-Straße 3<br>39646 Oebisfelde</p>
                <a href="https://www.google.com/maps/search/Friedrich-Engels-Straße+3,+39646+Oebisfelde" 
                   target="_blank" 
                   class="btn btn-primary">
                    <i class="fas fa-directions"></i> <?= $lang['get_directions'] ?? 'Get Directions' ?>
                </a>
            </div>
        </div>
    </section>
    
    <?php include 'includes/components/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/translations.js"></script>
    <script src="/assets/js/cart.js"></script>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/animations.js"></script>
</body>
</html>
