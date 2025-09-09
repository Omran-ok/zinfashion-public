<?php
/**
 * ZIN Fashion - Header Component
 * Location: /public_html/dev_staging/includes/components/header.php
 */

// Get cart and wishlist counts
$cartCount = getCartCount();
$wishlistCount = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;

// Get all categories for mega menu
$menuSql = "SELECT c.*, 
            (SELECT GROUP_CONCAT(CONCAT(category_id, ':', category_name, ':', slug) SEPARATOR '|') 
             FROM categories 
             WHERE parent_id = c.category_id AND is_active = 1 
             ORDER BY display_order) as subcategories
            FROM categories c
            WHERE c.parent_id IS NULL AND c.is_active = 1
            ORDER BY c.display_order";
$menuStmt = $pdo->query($menuSql);
$menuCategories = $menuStmt->fetchAll();
?>

<!-- Top Banner -->
<div class="top-banner">
    <div class="container">
        <div class="top-banner-content">
            <div class="banner-left">
                <span class="banner-text"><?= $lang['free_shipping_banner'] ?? 'Free shipping on orders over €50' ?></span>
            </div>
            <div class="banner-right">
                <!-- Language Selector -->
                <div class="language-selector">
                    <button class="lang-current" id="langToggle">
                        <span class="flag flag-<?= $currentLang ?>"></span>
                        <span><?= strtoupper($currentLang) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="lang-dropdown" id="langDropdown">
                        <a href="?lang=de" class="lang-option <?= $currentLang === 'de' ? 'active' : '' ?>">
                            <span class="flag flag-de"></span>
                            <span>Deutsch</span>
                        </a>
                        <a href="?lang=en" class="lang-option <?= $currentLang === 'en' ? 'active' : '' ?>">
                            <span class="flag flag-en"></span>
                            <span>English</span>
                        </a>
                        <a href="?lang=ar" class="lang-option <?= $currentLang === 'ar' ? 'active' : '' ?>">
                            <span class="flag flag-ar"></span>
                            <span>العربية</span>
                        </a>
                    </div>
                </div>
                
                <!-- Theme Toggle -->
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="fas fa-sun" id="themeIconLight"></i>
                    <i class="fas fa-moon" id="themeIconDark"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="main-header">
    <div class="container">
        <div class="header-content">
            <!-- Logo -->
            <div class="header-logo">
                <a href="/">
                    <img src="/assets/images/logo.svg" alt="ZIN Fashion" class="logo-img">
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="header-search">
                <form class="search-form" action="/search" method="GET">
                    <input type="text" name="q" placeholder="<?= $lang['search_placeholder'] ?? 'Search for products...' ?>" 
                           autocomplete="off" id="searchInput">
                    <button type="submit" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
            
            <!-- Header Actions -->
            <div class="header-actions">
                <!-- Account -->
                <div class="header-action-item">
                    <a href="/account" class="action-link">
                        <i class="far fa-user"></i>
                        <span class="action-text"><?= $lang['account'] ?? 'Account' ?></span>
                    </a>
                    <div class="account-dropdown">
                        <?php if (isLoggedIn()): ?>
                            <a href="/account/dashboard"><?= $lang['my_account'] ?? 'My Account' ?></a>
                            <a href="/account/orders"><?= $lang['my_orders'] ?? 'My Orders' ?></a>
                            <a href="/account/wishlist"><?= $lang['my_wishlist'] ?? 'My Wishlist' ?></a>
                            <a href="/logout"><?= $lang['logout'] ?? 'Logout' ?></a>
                        <?php else: ?>
                            <a href="/login"><?= $lang['login'] ?? 'Login' ?></a>
                            <a href="/register"><?= $lang['register'] ?? 'Register' ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Wishlist -->
                <div class="header-action-item">
                    <a href="/wishlist" class="action-link">
                        <i class="far fa-heart"></i>
                        <span class="action-text"><?= $lang['wishlist'] ?? 'Wishlist' ?></span>
                        <?php if ($wishlistCount > 0): ?>
                        <span class="action-badge" id="wishlistCount"><?= $wishlistCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Cart -->
                <div class="header-action-item">
                    <a href="/cart" class="action-link" id="cartTrigger">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="action-text"><?= $lang['cart'] ?? 'Cart' ?></span>
                        <?php if ($cartCount > 0): ?>
                        <span class="action-badge" id="cartCount"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Navigation Menu -->
<nav class="main-navigation">
    <div class="container">
        <ul class="nav-menu">
            <!-- Categories with Mega Menu -->
            <?php foreach ($menuCategories as $category): 
                $categoryName = $category['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $category['category_name'];
                $subcategories = $category['subcategories'] ? explode('|', $category['subcategories']) : [];
            ?>
            <li class="nav-item has-mega-menu">
                <a href="/category/<?= htmlspecialchars($category['slug']) ?>" class="nav-link">
                    <?= htmlspecialchars($categoryName) ?>
                    <?php if (!empty($subcategories)): ?>
                    <i class="fas fa-chevron-down"></i>
                    <?php endif; ?>
                </a>
                <?php if (!empty($subcategories)): ?>
                <div class="mega-menu">
                    <div class="mega-menu-content">
                        <div class="mega-menu-column">
                            <h4><?= htmlspecialchars($categoryName) ?></h4>
                            <ul>
                                <?php foreach ($subcategories as $subcat): 
                                    list($subId, $subName, $subSlug) = explode(':', $subcat);
                                ?>
                                <li>
                                    <a href="/category/<?= htmlspecialchars($subSlug) ?>">
                                        <?= htmlspecialchars($subName) ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mega-menu-promo">
                            <img src="/assets/images/mega-menu/<?= htmlspecialchars($category['slug']) ?>.jpg" 
                                 alt="<?= htmlspecialchars($categoryName) ?>">
                            <div class="promo-content">
                                <h5><?= $lang['new_collection'] ?? 'New Collection' ?></h5>
                                <a href="/category/<?= htmlspecialchars($category['slug']) ?>/new" class="btn btn-small">
                                    <?= $lang['shop_now'] ?? 'Shop Now' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            
            <!-- Sale Link -->
            <li class="nav-item">
                <a href="/sale" class="nav-link nav-sale">
                    <span><?= $lang['sale'] ?? 'Sale' ?></span>
                </a>
            </li>
            
            <!-- About Us -->
            <li class="nav-item">
                <a href="/about" class="nav-link"><?= $lang['about_us'] ?? 'About Us' ?></a>
            </li>
            
            <!-- Contact -->
            <li class="nav-item">
                <a href="/contact" class="nav-link"><?= $lang['contact'] ?? 'Contact' ?></a>
            </li>
        </ul>
    </div>
</nav>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <img src="/assets/images/logo.svg" alt="ZIN Fashion" class="mobile-logo">
        <button class="mobile-menu-close" id="mobileMenuClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="mobile-menu-content">
        <!-- Mobile Search -->
        <form class="mobile-search" action="/search" method="GET">
            <input type="text" name="q" placeholder="<?= $lang['search_placeholder'] ?? 'Search...' ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        
        <!-- Mobile Navigation -->
        <ul class="mobile-nav">
            <?php foreach ($menuCategories as $category): 
                $categoryName = $category['category_name' . ($currentLang !== 'de' ? '_' . $currentLang : '')] ?? $category['category_name'];
                $subcategories = $category['subcategories'] ? explode('|', $category['subcategories']) : [];
            ?>
            <li class="mobile-nav-item">
                <a href="/category/<?= htmlspecialchars($category['slug']) ?>" class="mobile-nav-link">
                    <?= htmlspecialchars($categoryName) ?>
                    <?php if (!empty($subcategories)): ?>
                    <i class="fas fa-plus mobile-nav-toggle"></i>
                    <?php endif; ?>
                </a>
                <?php if (!empty($subcategories)): ?>
                <ul class="mobile-submenu">
                    <?php foreach ($subcategories as $subcat): 
                        list($subId, $subName, $subSlug) = explode(':', $subcat);
                    ?>
                    <li><a href="/category/<?= htmlspecialchars($subSlug) ?>"><?= htmlspecialchars($subName) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
            <li class="mobile-nav-item">
                <a href="/sale" class="mobile-nav-link mobile-sale"><?= $lang['sale'] ?? 'Sale' ?></a>
            </li>
            <li class="mobile-nav-item">
                <a href="/about" class="mobile-nav-link"><?= $lang['about_us'] ?? 'About Us' ?></a>
            </li>
            <li class="mobile-nav-item">
                <a href="/contact" class="mobile-nav-link"><?= $lang['contact'] ?? 'Contact' ?></a>
            </li>
        </ul>
        
        <!-- Mobile Account Links -->
        <div class="mobile-account">
            <?php if (isLoggedIn()): ?>
                <a href="/account/dashboard" class="mobile-account-link">
                    <i class="far fa-user"></i> <?= $lang['my_account'] ?? 'My Account' ?>
                </a>
                <a href="/logout" class="mobile-account-link">
                    <i class="fas fa-sign-out-alt"></i> <?= $lang['logout'] ?? 'Logout' ?>
                </a>
            <?php else: ?>
                <a href="/login" class="mobile-account-link">
                    <i class="fas fa-sign-in-alt"></i> <?= $lang['login'] ?? 'Login' ?>
                </a>
                <a href="/register" class="mobile-account-link">
                    <i class="fas fa-user-plus"></i> <?= $lang['register'] ?? 'Register' ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-sidebar-header">
        <h3><?= $lang['shopping_cart'] ?? 'Shopping Cart' ?></h3>
        <button class="cart-close" id="cartClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="cart-sidebar-content" id="cartContent">
        <!-- Cart items will be loaded here via AJAX -->
    </div>
    <div class="cart-sidebar-footer">
        <div class="cart-total">
            <span><?= $lang['total'] ?? 'Total' ?>:</span>
            <span class="cart-total-amount" id="cartTotalAmount">€0,00</span>
        </div>
        <a href="/cart" class="btn btn-outline btn-block"><?= $lang['view_cart'] ?? 'View Cart' ?></a>
        <a href="/checkout" class="btn btn-primary btn-block"><?= $lang['checkout'] ?? 'Checkout' ?></a>
    </div>
</div>

<!-- Cart Overlay -->
<div class="cart-overlay" id="cartOverlay"></div>
