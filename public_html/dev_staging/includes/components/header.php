<?php
/**
 * ZIN Fashion - Header Component (Fixed)
 * Location: /public_html/dev_staging/includes/components/header.php
 * 
 * This version uses getCartCount() from config.php
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_url = $_SERVER['REQUEST_URI'];

// Get user preferences
$theme = $_COOKIE['theme'] ?? 'dark';
$lang = $_COOKIE['lang'] ?? 'de';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';

// Helper function for wishlist count (only if not exists)
if (!function_exists('getWishlistCount')) {
    function getWishlistCount() {
        if (!isset($_SESSION['user_id'])) {
            return 0;
        }
        
        try {
            $pdo = getDBConnection();
            $sql = "SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            return $stmt->fetchColumn() ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
}

// Helper function for categories (only if not exists)
if (!function_exists('getHeaderCategories')) {
    function getHeaderCategories() {
        try {
            $pdo = getDBConnection();
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM products WHERE category_id = c.category_id AND is_active = 1) as product_count
                    FROM categories c 
                    WHERE c.is_active = 1 
                    ORDER BY c.parent_id, c.display_order";
            
            $stmt = $pdo->query($sql);
            $categories = [];
            
            while ($row = $stmt->fetch()) {
                if ($row['parent_id'] === null) {
                    $categories[$row['category_id']] = [
                        'info' => $row,
                        'children' => []
                    ];
                }
            }
            
            // Get subcategories
            $stmt->execute();
            while ($row = $stmt->fetch()) {
                if ($row['parent_id'] !== null && isset($categories[$row['parent_id']])) {
                    $categories[$row['parent_id']]['children'][] = $row;
                }
            }
            
            return $categories;
        } catch (PDOException $e) {
            error_log("Error loading categories: " . $e->getMessage());
            return [];
        }
    }
}

// Get data using functions (getCartCount is from config.php)
try {
    $cartCount = getCartCount();  // This uses the function from config.php
    $wishlistCount = getWishlistCount();
    $headerCategories = getHeaderCategories();
} catch (Exception $e) {
    $cartCount = 0;
    $wishlistCount = 0;
    $headerCategories = [];
}

// Set default page variables if not set
$pageTitle = $pageTitle ?? 'ZIN Fashion - Luxury Fashion Store';
$pageDescription = $pageDescription ?? 'ZIN Fashion - Premium Mode für Herren, Damen und Kinder.';
$pageKeywords = $pageKeywords ?? 'Mode, Fashion, Premium, Luxury';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="author" content="ZIN Fashion">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= SITE_URL . $current_url ?>">
    <meta property="og:site_name" content="ZIN Fashion">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/responsive.css">
    
    <?php if (isset($additionalStyles) && is_array($additionalStyles)): ?>
        <?php foreach ($additionalStyles as $style): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($style) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/images/favicon.svg">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
</head>
<body>
    
    <!-- Skip to Content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Header -->
    <header class="header">
        <!-- Top Bar -->
        <div class="header-top">
            <div class="container">
                <div class="header-top-content">
                    <div class="header-info">
                        <span class="info-text">
                            <i class="fas fa-truck"></i>
                            <span data-i18n="free-shipping">Kostenloser Versand ab 50€</span>
                        </span>
                        <span class="info-text hide-mobile">
                            <i class="fas fa-phone"></i>
                            <a href="tel:+4915563576774">+49 15563 576774</a>
                        </span>
                    </div>
                    <div class="header-controls">
                        <!-- Language Selector -->
                        <div class="language-selector">
                            <!-- Desktop buttons -->
                            <button class="lang-btn hide-mobile <?= $lang === 'de' ? 'active' : '' ?>" 
                                    data-lang="de">🇩🇪 DE</button>
                            <button class="lang-btn hide-mobile <?= $lang === 'en' ? 'active' : '' ?>" 
                                    data-lang="en">🇬🇧 EN</button>
                            <button class="lang-btn hide-mobile <?= $lang === 'ar' ? 'active' : '' ?>" 
                                    data-lang="ar">🇸🇦 AR</button>
                            
                            <!-- Mobile dropdown -->
                            <select class="language-select show-mobile" onchange="changeLanguage(this.value)">
                                <option value="de" <?= $lang === 'de' ? 'selected' : '' ?>>🇩🇪 DE</option>
                                <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>🇬🇧 EN</option>
                                <option value="ar" <?= $lang === 'ar' ? 'selected' : '' ?>>🇸🇦 AR</option>
                            </select>
                        </div>
                        
                        <!-- Theme Toggle with animation -->
                        <button class="theme-toggle" id="themeToggle" title="Toggle theme" onclick="toggleTheme(this)">
                            <i class="fas fa-moon" data-theme-icon="dark"></i>
                            <i class="fas fa-sun" data-theme-icon="light" style="display:none;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <!-- Logo -->
                    <a href="<?= SITE_URL ?>" class="logo">
                        <img src="<?= SITE_URL ?>/assets/images/logo.svg" 
                             alt="ZIN Fashion Logo" 
                             width="50" height="50" 
                             class="logo-svg"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span class="logo-text" style="display:none;">ZIN Fashion</span>
                    </a>

                    <!-- Search Bar -->
                    <form class="search-bar" action="<?= SITE_URL ?>/search" method="GET">
                        <input type="search" 
                               name="q" 
                               class="search-input" 
                               placeholder="Suche nach Produkten..." 
                               data-i18n-placeholder="search-placeholder"
                               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>

                    <!-- Header Actions -->
                    <div class="header-actions">
                        <!-- Account -->
                        <a href="<?= $isLoggedIn ? SITE_URL . '/account' : SITE_URL . '/login' ?>" 
                           class="header-action">
                            <i class="fas fa-user"></i>
                            <span class="header-action-label" data-i18n="account">
                                <?= $isLoggedIn ? htmlspecialchars($userName) : 'Konto' ?>
                            </span>
                        </a>
                        
                        <!-- Wishlist -->
                        <a href="<?= SITE_URL ?>/wishlist" class="header-action">
                            <i class="fas fa-heart"></i>
                            <span class="header-action-label" data-i18n="wishlist">Wunschliste</span>
                            <?php if ($wishlistCount > 0): ?>
                                <span class="badge wishlist-count"><?= $wishlistCount ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Cart -->
                        <button class="header-action" id="cartIcon" onclick="openCart()">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="header-action-label" data-i18n="cart">Warenkorb</span>
                            <?php if ($cartCount > 0): ?>
                                <span class="badge cart-count"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="mobile-menu-toggle" id="mobileMenuToggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="nav">
            <div class="container">
                <ul class="nav-menu" id="navMenu">
                    <li class="nav-item">
                        <a href="<?= SITE_URL ?>" 
                           class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>">
                            <span data-i18n="home">Startseite</span>
                        </a>
                    </li>

                    <?php if (!empty($headerCategories)): ?>
                        <?php foreach ($headerCategories as $category): ?>
                            <?php $cat = $category['info']; ?>
                            <li class="nav-item <?= !empty($category['children']) ? 'has-dropdown' : '' ?>">
                                <a href="<?= SITE_URL ?>/category/<?= $cat['slug'] ?>" 
                                   class="nav-link">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                    <?php if (!empty($category['children'])): ?>
                                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                                    <?php endif; ?>
                                </a>
                                
                                <?php if (!empty($category['children'])): ?>
                                    <div class="mega-menu">
                                        <div class="mega-menu-content">
                                            <div class="mega-menu-column">
                                                <h3><?= htmlspecialchars($cat['category_name']) ?></h3>
                                                <ul>
                                                    <?php foreach ($category['children'] as $child): ?>
                                                        <li>
                                                            <a href="<?= SITE_URL ?>/category/<?= $child['slug'] ?>">
                                                                <?= htmlspecialchars($child['category_name']) ?>
                                                                <?php if ($child['product_count'] > 0): ?>
                                                                    <span class="product-count">(<?= $child['product_count'] ?>)</span>
                                                                <?php endif; ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a href="<?= SITE_URL ?>/sale" 
                           class="nav-link <?= $current_page === 'sale' ? 'active' : '' ?>">
                            <span class="sale-badge" data-i18n="sale">SALE</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= SITE_URL ?>/about" 
                           class="nav-link <?= $current_page === 'about' ? 'active' : '' ?>">
                            <span data-i18n="about">Über uns</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= SITE_URL ?>/contact" 
                           class="nav-link <?= $current_page === 'contact' ? 'active' : '' ?>">
                            <span data-i18n="contact">Kontakt</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Cart Modal -->
    <div class="cart-overlay" id="cartOverlay" onclick="closeCart()"></div>
    <div class="cart-modal" id="cartModal">
        <!-- Cart content will be loaded dynamically -->
    </div>

    <!-- Main Content Start -->
    <main id="main-content">
