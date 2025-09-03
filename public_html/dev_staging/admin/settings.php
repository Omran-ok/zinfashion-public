<?php
/**
 * ZIN Fashion - Settings Management
 * Location: /public_html/dev_staging/admin/settings.php
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
    header('Location: settings.php');
    exit;
}

$currentLang = getAdminLanguage();
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Get all settings
function getAllSettings($pdo) {
    $sql = "SELECT * FROM system_settings ORDER BY setting_type, setting_key";
    $stmt = $pdo->query($sql);
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// Save setting
function saveSetting($pdo, $key, $value, $type = 'general') {
    $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
            VALUES (:key, :value, :type)
            ON DUPLICATE KEY UPDATE setting_value = :value, setting_type = :type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['key' => $key, 'value' => $value, 'type' => $type]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        // General Settings
        saveSetting($pdo, 'store_name', $_POST['store_name'], 'general');
        saveSetting($pdo, 'store_email', $_POST['store_email'], 'general');
        saveSetting($pdo, 'store_phone', $_POST['store_phone'], 'general');
        saveSetting($pdo, 'store_address', $_POST['store_address'], 'general');
        saveSetting($pdo, 'store_city', $_POST['store_city'], 'general');
        saveSetting($pdo, 'store_postal_code', $_POST['store_postal_code'], 'general');
        saveSetting($pdo, 'store_country', $_POST['store_country'], 'general');
        saveSetting($pdo, 'primary_language', $_POST['primary_language'], 'general');
        saveSetting($pdo, 'supported_languages', implode(',', $_POST['supported_languages'] ?? ['de']), 'general');
        saveSetting($pdo, 'currency', $_POST['currency'], 'general');
        saveSetting($pdo, 'currency_symbol', $_POST['currency_symbol'], 'general');
        saveSetting($pdo, 'tax_rate', $_POST['tax_rate'], 'general');
        saveSetting($pdo, 'tax_number', $_POST['tax_number'], 'general');
        saveSetting($pdo, 'vat_id', $_POST['vat_id'], 'general');
        
        $message = "General settings saved successfully!";
        $messageType = 'success';
        
    } elseif (isset($_POST['save_shipping'])) {
        // Shipping Settings
        saveSetting($pdo, 'free_shipping_threshold', $_POST['free_shipping_threshold'], 'shipping');
        saveSetting($pdo, 'standard_shipping_cost', $_POST['standard_shipping_cost'], 'shipping');
        saveSetting($pdo, 'express_shipping_cost', $_POST['express_shipping_cost'], 'shipping');
        saveSetting($pdo, 'shipping_zones', $_POST['shipping_zones'], 'shipping');
        saveSetting($pdo, 'estimated_delivery_days', $_POST['estimated_delivery_days'], 'shipping');
        
        $message = "Shipping settings saved successfully!";
        $messageType = 'success';
        
    } elseif (isset($_POST['save_payment'])) {
        // Payment Settings
        $paymentMethods = $_POST['payment_methods'] ?? [];
        saveSetting($pdo, 'payment_methods', implode(',', $paymentMethods), 'payment');
        saveSetting($pdo, 'paypal_email', $_POST['paypal_email'] ?? '', 'payment');
        saveSetting($pdo, 'stripe_public_key', $_POST['stripe_public_key'] ?? '', 'payment');
        saveSetting($pdo, 'stripe_secret_key', $_POST['stripe_secret_key'] ?? '', 'payment');
        saveSetting($pdo, 'klarna_api_key', $_POST['klarna_api_key'] ?? '', 'payment');
        saveSetting($pdo, 'bank_account_name', $_POST['bank_account_name'] ?? '', 'payment');
        saveSetting($pdo, 'bank_account_iban', $_POST['bank_account_iban'] ?? '', 'payment');
        saveSetting($pdo, 'bank_account_bic', $_POST['bank_account_bic'] ?? '', 'payment');
        
        $message = "Payment settings saved successfully!";
        $messageType = 'success';
        
    } elseif (isset($_POST['save_email'])) {
        // Email Settings
        saveSetting($pdo, 'smtp_host', $_POST['smtp_host'], 'email');
        saveSetting($pdo, 'smtp_port', $_POST['smtp_port'], 'email');
        saveSetting($pdo, 'smtp_username', $_POST['smtp_username'], 'email');
        saveSetting($pdo, 'smtp_password', $_POST['smtp_password'], 'email');
        saveSetting($pdo, 'smtp_encryption', $_POST['smtp_encryption'], 'email');
        saveSetting($pdo, 'email_from_address', $_POST['email_from_address'], 'email');
        saveSetting($pdo, 'email_from_name', $_POST['email_from_name'], 'email');
        saveSetting($pdo, 'send_order_confirmation', isset($_POST['send_order_confirmation']) ? '1' : '0', 'email');
        saveSetting($pdo, 'send_shipping_notification', isset($_POST['send_shipping_notification']) ? '1' : '0', 'email');
        saveSetting($pdo, 'admin_notification_email', $_POST['admin_notification_email'], 'email');
        
        $message = "Email settings saved successfully!";
        $messageType = 'success';
        
    } elseif (isset($_POST['save_seo'])) {
        // SEO Settings
        saveSetting($pdo, 'meta_title', $_POST['meta_title'], 'seo');
        saveSetting($pdo, 'meta_description', $_POST['meta_description'], 'seo');
        saveSetting($pdo, 'meta_keywords', $_POST['meta_keywords'], 'seo');
        saveSetting($pdo, 'google_analytics_id', $_POST['google_analytics_id'], 'seo');
        saveSetting($pdo, 'facebook_pixel_id', $_POST['facebook_pixel_id'], 'seo');
        saveSetting($pdo, 'google_tag_manager_id', $_POST['google_tag_manager_id'], 'seo');
        saveSetting($pdo, 'sitemap_enabled', isset($_POST['sitemap_enabled']) ? '1' : '0', 'seo');
        
        $message = "SEO settings saved successfully!";
        $messageType = 'success';
        
    } elseif (isset($_POST['save_social'])) {
        // Social Media Settings
        saveSetting($pdo, 'facebook_url', $_POST['facebook_url'], 'social');
        saveSetting($pdo, 'instagram_url', $_POST['instagram_url'], 'social');
        saveSetting($pdo, 'twitter_url', $_POST['twitter_url'], 'social');
        saveSetting($pdo, 'pinterest_url', $_POST['pinterest_url'], 'social');
        saveSetting($pdo, 'youtube_url', $_POST['youtube_url'], 'social');
        saveSetting($pdo, 'tiktok_url', $_POST['tiktok_url'], 'social');
        saveSetting($pdo, 'whatsapp_number', $_POST['whatsapp_number'], 'social');
        
        $message = "Social media settings saved successfully!";
        $messageType = 'success';
    }
}

// Get current settings
$settings = getAllSettings($pdo);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('settings') ?> - ZIN Fashion Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .settings-nav {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .settings-nav h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .settings-nav ul {
            list-style: none;
            padding: 0;
        }
        
        .settings-nav li {
            margin-bottom: 10px;
        }
        
        .settings-nav a {
            display: block;
            padding: 10px;
            color: #7f8c8d;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .settings-nav a:hover {
            background: #f5f5f5;
            color: #2c3e50;
        }
        
        .settings-nav a.active {
            background: #3498db;
            color: white;
        }
        
        .settings-content {
            background: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .settings-section h2 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .form-helper {
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
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
        
        .btn-save {
            background: #27ae60;
            color: white;
        }
        
        .btn-save:hover {
            background: #229954;
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
        
        .payment-method-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }
            
            .settings-nav {
                position: static;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <?= __('orders') ?></a></li>
            <li><a href="customers.php"><i class="fas fa-users"></i> <?= __('customers') ?></a></li>
            <li><a href="newsletter.php"><i class="fas fa-envelope"></i> <?= __('newsletter') ?></a></li>
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> <?= __('settings') ?></a></li>
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
                <h1><?= __('settings') ?></h1>
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

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Settings Container -->
        <div class="settings-container">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <h3>Settings Menu</h3>
                <ul>
                    <li><a href="#general" class="nav-link active" data-section="general">
                        <i class="fas fa-store"></i> General
                    </a></li>
                    <li><a href="#shipping" class="nav-link" data-section="shipping">
                        <i class="fas fa-truck"></i> Shipping
                    </a></li>
                    <li><a href="#payment" class="nav-link" data-section="payment">
                        <i class="fas fa-credit-card"></i> Payment
                    </a></li>
                    <li><a href="#email" class="nav-link" data-section="email">
                        <i class="fas fa-envelope"></i> Email
                    </a></li>
                    <li><a href="#seo" class="nav-link" data-section="seo">
                        <i class="fas fa-search"></i> SEO
                    </a></li>
                    <li><a href="#social" class="nav-link" data-section="social">
                        <i class="fas fa-share-alt"></i> Social Media
                    </a></li>
                </ul>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <!-- General Settings -->
                <div id="general" class="settings-section active">
                    <h2><i class="fas fa-store"></i> General Settings</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Store Name</label>
                                <input type="text" name="store_name" value="<?= htmlspecialchars($settings['store_name'] ?? 'ZIN Fashion') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Store Email</label>
                                <input type="email" name="store_email" value="<?= htmlspecialchars($settings['store_email'] ?? 'trend@zinfashion.de') ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Store Phone</label>
                                <input type="text" name="store_phone" value="<?= htmlspecialchars($settings['store_phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>VAT ID (Umsatzsteuer-ID)</label>
                                <input type="text" name="vat_id" value="<?= htmlspecialchars($settings['vat_id'] ?? '') ?>" placeholder="DE123456789">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Store Address</label>
                            <input type="text" name="store_address" value="<?= htmlspecialchars($settings['store_address'] ?? '') ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="store_city" value="<?= htmlspecialchars($settings['store_city'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="store_postal_code" value="<?= htmlspecialchars($settings['store_postal_code'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Country</label>
                                <select name="store_country">
                                    <option value="DE" <?= ($settings['store_country'] ?? 'DE') == 'DE' ? 'selected' : '' ?>>Germany</option>
                                    <option value="AT" <?= ($settings['store_country'] ?? '') == 'AT' ? 'selected' : '' ?>>Austria</option>
                                    <option value="CH" <?= ($settings['store_country'] ?? '') == 'CH' ? 'selected' : '' ?>>Switzerland</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Primary Language</label>
                                <select name="primary_language">
                                    <option value="de" <?= ($settings['primary_language'] ?? 'de') == 'de' ? 'selected' : '' ?>>Deutsch</option>
                                    <option value="en" <?= ($settings['primary_language'] ?? '') == 'en' ? 'selected' : '' ?>>English</option>
                                    <option value="ar" <?= ($settings['primary_language'] ?? '') == 'ar' ? 'selected' : '' ?>>العربية</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Supported Languages</label>
                            <?php $supportedLangs = explode(',', $settings['supported_languages'] ?? 'de,en,ar'); ?>
                            <div class="checkbox-group">
                                <input type="checkbox" name="supported_languages[]" value="de" id="lang_de" 
                                       <?= in_array('de', $supportedLangs) ? 'checked' : '' ?>>
                                <label for="lang_de">Deutsch</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" name="supported_languages[]" value="en" id="lang_en"
                                       <?= in_array('en', $supportedLangs) ? 'checked' : '' ?>>
                                <label for="lang_en">English</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" name="supported_languages[]" value="ar" id="lang_ar"
                                       <?= in_array('ar', $supportedLangs) ? 'checked' : '' ?>>
                                <label for="lang_ar">العربية</label>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Currency</label>
                                <select name="currency">
                                    <option value="EUR" <?= ($settings['currency'] ?? 'EUR') == 'EUR' ? 'selected' : '' ?>>EUR (Euro)</option>
                                    <option value="USD" <?= ($settings['currency'] ?? '') == 'USD' ? 'selected' : '' ?>>USD (Dollar)</option>
                                    <option value="GBP" <?= ($settings['currency'] ?? '') == 'GBP' ? 'selected' : '' ?>>GBP (Pound)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Currency Symbol</label>
                                <input type="text" name="currency_symbol" value="<?= htmlspecialchars($settings['currency_symbol'] ?? '€') ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <input type="number" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate'] ?? '19') ?>" step="0.01" required>
                                <div class="form-helper">German VAT is typically 19%</div>
                            </div>
                            <div class="form-group">
                                <label>Tax Number (Steuernummer)</label>
                                <input type="text" name="tax_number" value="<?= htmlspecialchars($settings['tax_number'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="save_general" class="btn btn-save">
                            <i class="fas fa-save"></i> Save General Settings
                        </button>
                    </form>
                </div>

                <!-- Shipping Settings -->
                <div id="shipping" class="settings-section">
                    <h2><i class="fas fa-truck"></i> Shipping Settings</h2>
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Free Shipping Threshold (€)</label>
                                <input type="number" name="free_shipping_threshold" 
                                       value="<?= htmlspecialchars($settings['free_shipping_threshold'] ?? '50') ?>" 
                                       step="0.01" required>
                                <div class="form-helper">Orders above this amount get free shipping</div>
                            </div>
                            <div class="form-group">
                                <label>Standard Shipping Cost (€)</label>
                                <input type="number" name="standard_shipping_cost" 
                                       value="<?= htmlspecialchars($settings['standard_shipping_cost'] ?? '4.99') ?>" 
                                       step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Express Shipping Cost (€)</label>
                                <input type="number" name="express_shipping_cost" 
                                       value="<?= htmlspecialchars($settings['express_shipping_cost'] ?? '9.99') ?>" 
                                       step="0.01">
                            </div>
                            <div class="form-group">
                                <label>Estimated Delivery Days</label>
                                <input type="text" name="estimated_delivery_days" 
                                       value="<?= htmlspecialchars($settings['estimated_delivery_days'] ?? '2-4') ?>" 
                                       placeholder="e.g., 2-4">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Shipping Zones</label>
                            <textarea name="shipping_zones" rows="4" 
                                      placeholder="Germany, Austria, Switzerland"><?= htmlspecialchars($settings['shipping_zones'] ?? 'Germany, Austria, Switzerland') ?></textarea>
                            <div class="form-helper">Countries or regions you ship to</div>
                        </div>
                        
                        <button type="submit" name="save_shipping" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Shipping Settings
                        </button>
                    </form>
                </div>

                <!-- Payment Settings -->
                <div id="payment" class="settings-section">
                    <h2><i class="fas fa-credit-card"></i> Payment Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Accepted Payment Methods</label>
                            <?php $paymentMethods = explode(',', $settings['payment_methods'] ?? 'paypal,stripe,klarna,bank_transfer'); ?>
                            
                            <div class="payment-method-item">
                                <input type="checkbox" name="payment_methods[]" value="paypal" id="pm_paypal"
                                       <?= in_array('paypal', $paymentMethods) ? 'checked' : '' ?>>
                                <label for="pm_paypal"><i class="fab fa-paypal"></i> PayPal</label>
                            </div>
                            
                            <div class="payment-method-item">
                                <input type="checkbox" name="payment_methods[]" value="stripe" id="pm_stripe"
                                       <?= in_array('stripe', $paymentMethods) ? 'checked' : '' ?>>
                                <label for="pm_stripe"><i class="fab fa-stripe"></i> Stripe (Credit/Debit Cards)</label>
                            </div>
                            
                            <div class="payment-method-item">
                                <input type="checkbox" name="payment_methods[]" value="klarna" id="pm_klarna"
                                       <?= in_array('klarna', $paymentMethods) ? 'checked' : '' ?>>
                                <label for="pm_klarna">Klarna (Buy Now, Pay Later)</label>
                            </div>
                            
                            <div class="payment-method-item">
                                <input type="checkbox" name="payment_methods[]" value="bank_transfer" id="pm_bank"
                                       <?= in_array('bank_transfer', $paymentMethods) ? 'checked' : '' ?>>
                                <label for="pm_bank"><i class="fas fa-university"></i> Bank Transfer</label>
                            </div>
                        </div>
                        
                        <h3>PayPal Configuration</h3>
                        <div class="form-group">
                            <label>PayPal Business Email</label>
                            <input type="email" name="paypal_email" 
                                   value="<?= htmlspecialchars($settings['paypal_email'] ?? '') ?>"
                                   placeholder="business@zinfashion.de">
                        </div>
                        
                        <h3>Stripe Configuration</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Stripe Public Key</label>
                                <input type="text" name="stripe_public_key" 
                                       value="<?= htmlspecialchars($settings['stripe_public_key'] ?? '') ?>"
                                       placeholder="pk_live_...">
                            </div>
                            <div class="form-group">
                                <label>Stripe Secret Key</label>
                                <input type="password" name="stripe_secret_key" 
                                       value="<?= htmlspecialchars($settings['stripe_secret_key'] ?? '') ?>"
                                       placeholder="sk_live_...">
                            </div>
                        </div>
                        
                        <h3>Klarna Configuration</h3>
                        <div class="form-group">
                            <label>Klarna API Key</label>
                            <input type="text" name="klarna_api_key" 
                                   value="<?= htmlspecialchars($settings['klarna_api_key'] ?? '') ?>">
                        </div>
                        
                        <h3>Bank Transfer Details</h3>
                        <div class="form-group">
                            <label>Account Name</label>
                            <input type="text" name="bank_account_name" 
                                   value="<?= htmlspecialchars($settings['bank_account_name'] ?? '') ?>"
                                   placeholder="ZIN Fashion GmbH">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>IBAN</label>
                                <input type="text" name="bank_account_iban" 
                                       value="<?= htmlspecialchars($settings['bank_account_iban'] ?? '') ?>"
                                       placeholder="DE89 3704 0044 0532 0130 00">
                            </div>
                            <div class="form-group">
                                <label>BIC/SWIFT</label>
                                <input type="text" name="bank_account_bic" 
                                       value="<?= htmlspecialchars($settings['bank_account_bic'] ?? '') ?>"
                                       placeholder="DEUTDEDBKOE">
                            </div>
                        </div>
                        
                        <button type="submit" name="save_payment" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Payment Settings
                        </button>
                    </form>
                </div>

                <!-- Email Settings -->
                <div id="email" class="settings-section">
                    <h2><i class="fas fa-envelope"></i> Email Settings</h2>
                    <form method="POST">
                        <h3>SMTP Configuration</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>SMTP Host</label>
                                <input type="text" name="smtp_host" 
                                       value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.gmail.com') ?>">
                            </div>
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" name="smtp_port" 
                                       value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>SMTP Username</label>
                                <input type="text" name="smtp_username" 
                                       value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>SMTP Password</label>
                                <input type="password" name="smtp_password" 
                                       value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : '' ?>>None</option>
                            </select>
                        </div>
                        
                        <h3>Email Settings</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>From Email Address</label>
                                <input type="email" name="email_from_address" 
                                       value="<?= htmlspecialchars($settings['email_from_address'] ?? 'noreply@zinfashion.de') ?>">
                            </div>
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" name="email_from_name" 
                                       value="<?= htmlspecialchars($settings['email_from_name'] ?? 'ZIN Fashion') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Admin Notification Email</label>
                            <input type="email" name="admin_notification_email" 
                                   value="<?= htmlspecialchars($settings['admin_notification_email'] ?? 'admin@zinfashion.de') ?>">
                            <div class="form-helper">Email address to receive order notifications</div>
                        </div>
                        
                        <h3>Email Notifications</h3>
                        <div class="checkbox-group">
                            <input type="checkbox" name="send_order_confirmation" id="send_order_confirmation"
                                   <?= ($settings['send_order_confirmation'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label for="send_order_confirmation">Send order confirmation emails to customers</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="send_shipping_notification" id="send_shipping_notification"
                                   <?= ($settings['send_shipping_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label for="send_shipping_notification">Send shipping notification emails</label>
                        </div>
                        
                        <button type="submit" name="save_email" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Email Settings
                        </button>
                    </form>
                </div>

                <!-- SEO Settings -->
                <div id="seo" class="settings-section">
                    <h2><i class="fas fa-search"></i> SEO Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Meta Title</label>
                            <input type="text" name="meta_title" 
                                   value="<?= htmlspecialchars($settings['meta_title'] ?? 'ZIN Fashion - Trendy Clothing & Accessories') ?>"
                                   maxlength="60">
                            <div class="form-helper">Recommended: 50-60 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="meta_description" rows="3" maxlength="160"><?= htmlspecialchars($settings['meta_description'] ?? 'Discover the latest fashion trends at ZIN Fashion. Shop quality clothing, accessories, and more with fast shipping across Germany.') ?></textarea>
                            <div class="form-helper">Recommended: 150-160 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <input type="text" name="meta_keywords" 
                                   value="<?= htmlspecialchars($settings['meta_keywords'] ?? 'fashion, clothing, accessories, trendy, german fashion, online shopping') ?>">
                            <div class="form-helper">Separate keywords with commas</div>
                        </div>
                        
                        <h3>Analytics & Tracking</h3>
                        <div class="form-group">
                            <label>Google Analytics ID</label>
                            <input type="text" name="google_analytics_id" 
                                   value="<?= htmlspecialchars($settings['google_analytics_id'] ?? '') ?>"
                                   placeholder="UA-XXXXXXXXX-X or G-XXXXXXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label>Facebook Pixel ID</label>
                            <input type="text" name="facebook_pixel_id" 
                                   value="<?= htmlspecialchars($settings['facebook_pixel_id'] ?? '') ?>"
                                   placeholder="XXXXXXXXXXXXXXX">
                        </div>
                        
                        <div class="form-group">
                            <label>Google Tag Manager ID</label>
                            <input type="text" name="google_tag_manager_id" 
                                   value="<?= htmlspecialchars($settings['google_tag_manager_id'] ?? '') ?>"
                                   placeholder="GTM-XXXXXXX">
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="sitemap_enabled" id="sitemap_enabled"
                                   <?= ($settings['sitemap_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label for="sitemap_enabled">Enable automatic sitemap generation</label>
                        </div>
                        
                        <button type="submit" name="save_seo" class="btn btn-save">
                            <i class="fas fa-save"></i> Save SEO Settings
                        </button>
                    </form>
                </div>

                <!-- Social Media Settings -->
                <div id="social" class="settings-section">
                    <h2><i class="fas fa-share-alt"></i> Social Media Settings</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fab fa-facebook"></i> Facebook URL</label>
                            <input type="url" name="facebook_url" 
                                   value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>"
                                   placeholder="https://facebook.com/zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-instagram"></i> Instagram URL</label>
                            <input type="url" name="instagram_url" 
                                   value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>"
                                   placeholder="https://instagram.com/zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-twitter"></i> Twitter/X URL</label>
                            <input type="url" name="twitter_url" 
                                   value="<?= htmlspecialchars($settings['twitter_url'] ?? '') ?>"
                                   placeholder="https://twitter.com/zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-pinterest"></i> Pinterest URL</label>
                            <input type="url" name="pinterest_url" 
                                   value="<?= htmlspecialchars($settings['pinterest_url'] ?? '') ?>"
                                   placeholder="https://pinterest.com/zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-youtube"></i> YouTube URL</label>
                            <input type="url" name="youtube_url" 
                                   value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>"
                                   placeholder="https://youtube.com/@zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-tiktok"></i> TikTok URL</label>
                            <input type="url" name="tiktok_url" 
                                   value="<?= htmlspecialchars($settings['tiktok_url'] ?? '') ?>"
                                   placeholder="https://tiktok.com/@zinfashion">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-whatsapp"></i> WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" 
                                   value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '') ?>"
                                   placeholder="+49 XXX XXXXXXX">
                            <div class="form-helper">Include country code (e.g., +49 for Germany)</div>
                        </div>
                        
                        <button type="submit" name="save_social" class="btn btn-save">
                            <i class="fas fa-save"></i> Save Social Media Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
        
        // Settings section navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and sections
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
                
                // Add active class to clicked link and corresponding section
                this.classList.add('active');
                const section = this.getAttribute('data-section');
                document.getElementById(section).classList.add('active');
            });
        });
    </script>
</body>
</html>
