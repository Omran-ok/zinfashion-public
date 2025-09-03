<?php
/**
 * ZIN Fashion - Main Index File
 * Location: /public_html/dev_staging/index.php
 */

// Include configuration
require_once 'includes/config.php';

// Only include API if this is an API request
if (isset($_GET['api'])) {
    require_once 'includes/api.php';
    exit; // Stop execution after API handling
}

// Get user preferences
$theme = $_COOKIE['theme'] ?? 'dark';
$lang = $_COOKIE['lang'] ?? 'de';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="ZIN Fashion - Premium Mode für Herren, Damen und Kinder. Luxuriöse Kleidung in großen Größen.">
    <meta name="keywords" content="Mode, Fashion, Premium, Luxury, Große Größen, Plus Size, Damen, Herren, Kinder">
    <meta name="author" content="ZIN Fashion">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="ZIN Fashion - Luxury Fashion in Plus Sizes">
    <meta property="og:description" content="Entdecken Sie Premium Mode für die ganze Familie">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://dev.zinfashion.de">
    <meta property="og:image" content="https://dev.zinfashion.de/assets/images/og-image.jpg">
    
    <title>ZIN Fashion - Luxury Fashion Store</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <!-- Top Bar -->
        <div class="header-top">
            <div class="container">
                <div class="header-top-content">
                    <span class="info-text" data-i18n="free-shipping">Kostenloser Versand ab 50€</span>
                    <div class="header-controls">
                        <!-- Language Selector -->
                        <div class="language-selector">
                            <button class="lang-btn <?= $lang === 'de' ? 'active' : '' ?>" data-lang="de">🇩🇪 DE</button>
                            <button class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>" data-lang="en">🇬🇧 EN</button>
                            <button class="lang-btn <?= $lang === 'ar' ? 'active' : '' ?>" data-lang="ar">🇸🇦 AR</button>
                        </div>
                        <!-- Theme Toggle -->
                        <div class="theme-toggle">
                            <button class="theme-btn" id="themeToggle" aria-label="Toggle theme">
                                <span class="theme-icon-light">☀️</span>
                                <span class="theme-icon-dark">🌙</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="header-main">
            <div class="container">
                <div class="header-content">
                    <!-- Logo -->
                    <a href="/" class="logo">
                        <img src="assets/images/logo.svg" alt="zinfashion logo" width="80px" height="80px">
                    </a>

                    <!-- Search Bar -->
                    <div class="search-bar">
                        <input type="search" class="search-input" placeholder="Suche nach Produkten..." data-i18n-placeholder="search-placeholder">
                        <button class="search-btn" aria-label="Search">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Header Actions -->
                    <div class="header-actions">
                        <a href="/account" class="header-action">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                            </svg>
                            <span class="header-action-label" data-i18n="account">Konto</span>
                        </a>
                        <a href="/wishlist" class="header-action">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span class="header-action-label" data-i18n="wishlist">Wunschliste</span>
                            <span class="badge wishlist-count">0</span>
                        </a>
                        <button class="header-action" id="cartIcon">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                            <span class="header-action-label" data-i18n="cart">Warenkorb</span>
                            <span class="badge cart-count">0</span>
                        </button>
                        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menu">
                            <svg class="icon" viewBox="0 0 24 24">
                                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                            </svg>
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
                        <a href="/" class="nav-link active" data-i18n="home">Startseite</a>
                    </li>

                    <!-- Women's Category -->
                    <li class="nav-item has-dropdown">
                        <a href="/damen" class="nav-link" data-i18n="women">Damen</a>
                        <div class="mega-menu">
                            <div class="mega-menu-column">
                                <h3 data-i18n="clothing">Kleidung</h3>
                                <a href="/damen/nachthemden" data-i18n="nightgowns">Nachthemden</a>
                                <a href="/damen/kleider" data-i18n="dresses">Kleider</a>
                                <a href="/damen/pyjamas" data-i18n="pajamas">Pyjamas</a>
                                <a href="/damen/hosen" data-i18n="pants">Hosen</a>
                                <a href="/damen/jacken" data-i18n="jackets">Jacken</a>
                                <a href="/damen/abayas" data-i18n="abayas">Abayas</a>
                                <a href="/damen/schals" data-i18n="shawls">Schals</a>
                            </div>
                        </div>
                    </li>

                    <!-- Men's Category -->
                    <li class="nav-item has-dropdown">
                        <a href="/herren" class="nav-link" data-i18n="men">Herren</a>
                        <div class="mega-menu">
                            <div class="mega-menu-column">
                                <h3 data-i18n="clothing">Kleidung</h3>
                                <a href="/herren/jacken" data-i18n="jackets">Jacken</a>
                                <a href="/herren/hemden" data-i18n="shirts">Hemden</a>
                                <a href="/herren/pyjamas" data-i18n="pajamas">Pyjamas</a>
                                <a href="/herren/unterwasche" data-i18n="underwear">Unterwäsche</a>
                                <a href="/herren/hosen" data-i18n="pants">Hosen</a>
                            </div>
                        </div>
                    </li>

                    <!-- Children's Category -->
                    <li class="nav-item has-dropdown">
                        <a href="/kinder" class="nav-link" data-i18n="children">Kinder</a>
                        <div class="mega-menu">
                            <div class="mega-menu-column">
                                <h3 data-i18n="clothing">Kleidung</h3>
                                <a href="/kinder/pullover" data-i18n="sweaters">Pullover</a>
                                <a href="/kinder/pyjamas" data-i18n="pajamas">Pyjamas</a>
                                <a href="/kinder/unterwasche" data-i18n="underwear">Unterwäsche</a>
                                <a href="/kinder/hosen" data-i18n="pants">Hosen</a>
                                <a href="/kinder/shorts" data-i18n="shorts">Shorts</a>
                            </div>
                        </div>
                    </li>

                    <!-- Home Textiles Category -->
                    <li class="nav-item has-dropdown">
                        <a href="/heimtextilien" class="nav-link" data-i18n="home-textiles">Heimtextilien</a>
                        <div class="mega-menu">
                            <div class="mega-menu-column">
                                <h3 data-i18n="textiles">Textilien</h3>
                                <a href="/heimtextilien/handtucher" data-i18n="towels">Handtücher</a>
                                <a href="/heimtextilien/bademantel" data-i18n="bathrobes">Bademäntel</a>
                                <a href="/heimtextilien/bettlaken" data-i18n="bedsheets">Bettlaken</a>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="/sale" class="nav-link sale-link" data-i18n="sale">SALE</a>
                    </li>
                    <li class="nav-item">
                        <a href="/about" class="nav-link" data-i18n="about">Über uns</a>
                    </li>
                    <li class="nav-item">
                        <a href="/contact" class="nav-link" data-i18n="contact">Kontakt</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-slider">
            <!-- Slide 1 -->
            <div class="hero-slide active">
                <div class="hero-image" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('assets/images/hero-1.jpg')"></div>
                <div class="hero-content">
                    <h1 class="hero-title animate-fade-up" data-i18n="hero-title">Neue Kollektion 2025</h1>
                    <p class="hero-subtitle animate-fade-up-delay" data-i18n="hero-subtitle">Entdecken Sie Luxus in Ihrer Größe</p>
                    <div class="hero-buttons animate-fade-up-delay-2">
                        <a href="/women" class="btn btn-primary" data-i18n="shop-women">Damen Shop</a>
                        <a href="/men" class="btn btn-secondary" data-i18n="shop-men">Herren Shop</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="hero-slide">
                <div class="hero-image" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('assets/images/hero-2.jpg')"></div>
                <div class="hero-content">
                    <h1 class="hero-title animate-fade-up" data-i18n="hero-title">Neue Kollektion 2025</h1>
                    <p class="hero-subtitle animate-fade-up-delay" data-i18n="hero-subtitle">Entdecken Sie Luxus in Ihrer Größe</p>
                    <div class="hero-buttons animate-fade-up-delay-2">
                        <a href="/women" class="btn btn-primary" data-i18n="shop-women">Damen Shop</a>
                        <a href="/men" class="btn btn-secondary" data-i18n="shop-men">Herren Shop</a>
                    </div>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="hero-slide">
                <div class="hero-image" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('assets/images/hero-3.jpg')"></div>
                <div class="hero-content">
                    <h1 class="hero-title animate-fade-up" data-i18n="hero-title">Neue Kollektion 2025</h1>
                    <p class="hero-subtitle animate-fade-up-delay" data-i18n="hero-subtitle">Entdecken Sie Luxus in Ihrer Größe</p>
                    <div class="hero-buttons animate-fade-up-delay-2">
                        <a href="/women" class="btn btn-primary" data-i18n="shop-women">Damen Shop</a>
                        <a href="/men" class="btn btn-secondary" data-i18n="shop-men">Herren Shop</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-dots">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-i18n="categories">Unsere Kollektionen</h2>
                <p class="section-subtitle" data-i18n="categories-subtitle">Entdecken Sie Premium Mode für die ganze Familie</p>
            </div>
            
            <div class="category-grid">
                <!-- Women's Category -->
                <div class="category-card" data-aos="fade-up">
                    <a href="/women">
                        <div class="category-image">
                            <img src="assets/images/category-women.jpg" alt="Women's Fashion" loading="lazy">
                            <div class="category-overlay">
                                <h3 class="category-name" data-i18n="women">Damen</h3>
                                <p class="category-count" data-i18n="women-count">500+ Produkte</p>
                                <span class="category-link" data-i18n="explore">Entdecken →</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Men's Category -->
                <div class="category-card" data-aos="fade-up" data-aos-delay="100">
                    <a href="/men">
                        <div class="category-image">
                            <img src="assets/images/category-men.jpg" alt="Men's Fashion" loading="lazy">
                            <div class="category-overlay">
                                <h3 class="category-name" data-i18n="men">Herren</h3>
                                <p class="category-count" data-i18n="men-count">400+ Produkte</p>
                                <span class="category-link" data-i18n="explore">Entdecken →</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Kids' Category -->
                <div class="category-card" data-aos="fade-up" data-aos-delay="200">
                    <a href="/kids">
                        <div class="category-image">
                            <img src="assets/images/category-kids.jpg" alt="Kids Fashion" loading="lazy">
                            <div class="category-overlay">
                                <h3 class="category-name" data-i18n="kids">Kinder</h3>
                                <p class="category-count" data-i18n="kids-count">300+ Produkte</p>
                                <span class="category-link" data-i18n="explore">Entdecken →</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="products">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-i18n="featured-products">Ausgewählte Produkte</h2>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" data-i18n="all">Alle</button>
                    <button class="filter-tab" data-filter="new" data-i18n="new">Neu</button>
                    <button class="filter-tab" data-filter="bestseller" data-i18n="bestseller">Bestseller</button>
                    <button class="filter-tab" data-filter="sale" data-i18n="sale">Sale</button>
                </div>
            </div>

            <div class="product-grid" id="productGrid">
                <!-- Products will be loaded dynamically -->
            </div>

            <div class="load-more">
                <button class="btn btn-outline" data-i18n="load-more">Mehr laden</button>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" data-i18n="why-choose-us">Warum ZIN Fashion wählen?</h2>
                <p class="section-subtitle" data-i18n="features-subtitle">Entdecken Sie die Vorteile des Einkaufens bei uns</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-gem" style="font-size: 48px; color: var(--primary-gold);"></i>
                    </div>
                    <h3 data-i18n="premium-quality">Premium Qualität</h3>
                    <p data-i18n="quality-text">Hochwertige Materialien und erstklassige Verarbeitung</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast" style="font-size: 48px; color: var(--primary-gold);"></i>
                    </div>
                    <h3 data-i18n="fast-shipping">Schneller Versand</h3>
                    <p data-i18n="shipping-text">Kostenloser Versand ab 50€ Bestellwert</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt" style="font-size: 48px; color: var(--primary-gold);"></i>
                    </div>
                    <h3 data-i18n="secure-payment">Sichere Zahlung</h3>
                    <p data-i18n="payment-text">Verschlüsselte und sichere Zahlungsmethoden</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset" style="font-size: 48px; color: var(--primary-gold);"></i>
                    </div>
                    <h3 data-i18n="customer-service">24/7 Support</h3>
                    <p data-i18n="support-text">Unser Kundenservice ist immer für Sie da</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2 class="newsletter-title" data-i18n="newsletter-title">Bleiben Sie auf dem Laufenden</h2>
                <p class="newsletter-text" data-i18n="newsletter-text">Melden Sie sich für unseren Newsletter an und erhalten Sie 10% Rabatt auf Ihre erste Bestellung</p>
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" class="newsletter-input" placeholder="Ihre E-Mail-Adresse" required>
                    <button type="submit" class="btn btn-primary" data-i18n="subscribe" style="background: #000000 !important; color: #D4AF37 !important; border: 2px solid #D4AF37 !important;">Abonnieren</button>
                </form>
                <p class="newsletter-privacy" data-i18n="privacy-note">
                    Wir respektieren Ihre Privatsphäre. Keine Spam-Mails.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="assets/images/logo.svg" alt="zinfashion logo" width="80px" height="80px"> 
                    </div>
                    <p class="footer-description" data-i18n="about-text">
                        Premium Mode für die ganze Familie. Qualität, Stil und Nachhaltigkeit stehen bei uns im Mittelpunkt.
                    </p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.405a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z"/></svg>
                        </a>
                        <a href="#" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3 data-i18n="customer-service">Kundenservice</h3>
                    <ul class="footer-links">
                        <li><a href="/shipping" data-i18n="shipping">Versand & Lieferung</a></li>
                        <li><a href="/returns" data-i18n="returns">Rückgabe & Umtausch</a></li>
                        <li><a href="/size-guide" data-i18n="size-guide">Größentabelle</a></li>
                        <li><a href="/contact" data-i18n="contact-us">Kontakt</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 data-i18n="information">Rechtliches</h3>
                    <ul class="footer-links">
                        <li><a href="/privacy" data-i18n="privacy">Datenschutz</a></li>
                        <li><a href="/terms" data-i18n="terms">AGB</a></li>
                        <li><a href="/imprint" data-i18n="imprint">Impressum</a></li>
                        <li><a href="/revocation" data-i18n="revocation">Widerrufsbelehrung</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 data-i18n="contact-info">Kontakt</h3>
                    <div class="contact-info">
                        <p>📍 Friedrich-Engels-Straße 3<br>39646 Oebisfelde<br>Deutschland</p>
                        <p>📞 +49 15563 576774</p>
                        <p>✉️ trend@zinfashion.de</p>
                    </div>
                    <div class="payment-methods">
                        <span>Visa</span>
                        <span>Mastercard</span>
                        <span>PayPal</span>
                        <span>Klarna</span>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 ZIN Fashion. <span data-i18n="all-rights">Alle Rechte vorbehalten</span>. Powered by <a href="https://elwafamedia.de" target="_blank" rel="noopener" class="footer-credit">El Wafa Media</a></p>
            </div>
        </div>
    </footer>

    <!-- Cart Modal -->
    <div class="cart-overlay" id="cartOverlay"></div>
    <div class="cart-modal" id="cartModal">
        <!-- Cart content will be loaded dynamically -->
    </div>

    <!-- Quick View Modal -->
    <div class="modal" id="quickViewModal">
        <!-- Quick view content will be loaded dynamically -->
    </div>

    <!-- Scripts -->
    <script src="assets/js/translations.js?v=<?= filemtime('assets/js/translations.js') ?>"></script>
    <script src="assets/js/main.js?v=<?= filemtime('assets/js/main.js') ?>"></script>
    <script src="assets/js/cart.js?v=<?= filemtime('assets/js/cart.js') ?>"></script>
    <script src="assets/js/products-v2.js?v=<?= filemtime('assets/js/products-v2.js') ?>"></script>
    
    <!-- Force products initialization after page load -->
    <script>
    // Ensure products load after all scripts are ready
    window.addEventListener('load', function() {
        setTimeout(function() {
            if (typeof productsManager !== 'undefined' && document.getElementById('productGrid')) {
                console.log('Initializing products...');
                productsManager.init();
            }
        }, 100);
    });
    </script>
</body>
</html>
