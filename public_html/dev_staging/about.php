<?php
/**
 * ZIN Fashion - About Page
 * Location: /public_html/dev_staging/about.php
 * 
 * Company information page with team section and values
 */

session_start();
require_once 'includes/config.php';
require_once 'includes/language-handler.php';

// Initialize database connection for header component
$pdo = getDBConnection();

// Prepare breadcrumbs (don't include 'Home' as breadcrumb.php will add it)
$breadcrumbs = [
    ['title' => $lang['about_us'] ?? 'About Us', 'url' => null]
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['about_us'] ?? 'About Us' ?> - ZIN Fashion</title>
    <meta name="description" content="Learn about ZIN Fashion - Your trusted fashion retailer in Germany, offering premium quality clothing and exceptional customer service since 2024.">
    
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
    <link rel="stylesheet" href="/assets/css/about.css">
    
    <!-- RTL Support -->
    <?php if ($currentLang === 'ar'): ?>
    <link rel="stylesheet" href="/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="theme-dark">
    
    <?php include 'includes/components/header.php'; ?>
    
    <!-- Breadcrumb -->
    <?php include 'includes/components/breadcrumb.php'; ?>
    
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <div class="about-hero-content">
                <h1 class="hero-title animate-on-scroll">
                    <?= $lang['about_hero_title'] ?? 'Welcome to ZIN Fashion' ?>
                </h1>
                <p class="hero-subtitle animate-on-scroll">
                    <?= $lang['about_hero_subtitle'] ?? 'Your Trusted Partner for Premium Fashion Since 2024' ?>
                </p>
            </div>
        </div>
    </section>
    
    <!-- Story Section -->
    <section class="about-story">
        <div class="container">
            <div class="story-grid">
                <div class="story-content animate-on-scroll">
                    <h2 class="section-title"><?= $lang['our_story'] ?? 'Our Story' ?></h2>
                    <p>
                        <?= $lang['story_text_1'] ?? 'Founded in 2024 in Oebisfelde, Germany, ZIN Fashion emerged from a simple vision: to make premium fashion accessible to everyone. What started as a small family business has grown into a trusted name in fashion retail.' ?>
                    </p>
                    <p>
                        <?= $lang['story_text_2'] ?? 'We believe that fashion is more than just clothing - it\'s a form of self-expression. Our carefully curated collections blend timeless elegance with modern trends, ensuring that every customer finds something that speaks to their unique style.' ?>
                    </p>
                    <p>
                        <?= $lang['story_text_3'] ?? 'Led by Amineh Al Rabbat, our dedicated team works tirelessly to bring you the latest fashion trends from around the world, while maintaining our commitment to quality and affordability.' ?>
                    </p>
                    <div class="story-stats">
                        <div class="stat-item">
                            <span class="stat-number" data-counter="1000">1000+</span>
                            <span class="stat-label"><?= $lang['happy_customers'] ?? 'Happy Customers' ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-counter="500">500+</span>
                            <span class="stat-label"><?= $lang['products'] ?? 'Products' ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number" data-counter="3">3</span>
                            <span class="stat-label"><?= $lang['languages'] ?? 'Languages' ?></span>
                        </div>
                    </div>
                </div>
                <div class="story-image animate-on-scroll">
                    <img src="/assets/images/about-story.jpg" alt="ZIN Fashion Store" loading="lazy">
                    <div class="image-decoration"></div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Values Section -->
    <section class="about-values">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['our_values'] ?? 'Our Values' ?></h2>
            <div class="values-grid">
                <div class="value-card animate-on-scroll">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3><?= $lang['quality'] ?? 'Quality' ?></h3>
                    <p><?= $lang['quality_desc'] ?? 'We source only the finest materials and work with trusted manufacturers to ensure every product meets our high standards.' ?></p>
                </div>
                <div class="value-card animate-on-scroll">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3><?= $lang['passion'] ?? 'Passion' ?></h3>
                    <p><?= $lang['passion_desc'] ?? 'Fashion is our passion, and we pour our hearts into curating collections that inspire and delight our customers.' ?></p>
                </div>
                <div class="value-card animate-on-scroll">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3><?= $lang['community'] ?? 'Community' ?></h3>
                    <p><?= $lang['community_desc'] ?? 'We believe in building lasting relationships with our customers and contributing positively to our local community.' ?></p>
                </div>
                <div class="value-card animate-on-scroll">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3><?= $lang['sustainability'] ?? 'Sustainability' ?></h3>
                    <p><?= $lang['sustainability_desc'] ?? 'We are committed to sustainable practices and work with suppliers who share our vision for a greener future.' ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mission Section -->
    <section class="about-mission">
        <div class="container">
            <div class="mission-content">
                <div class="mission-text animate-on-scroll">
                    <h2><?= $lang['our_mission'] ?? 'Our Mission' ?></h2>
                    <p class="mission-statement">
                        <?= $lang['mission_statement'] ?? '"To inspire confidence and self-expression through fashion, making premium quality clothing accessible to everyone while maintaining our commitment to sustainability and exceptional customer service."' ?>
                    </p>
                </div>
                <div class="mission-image animate-on-scroll">
                    <img src="/assets/images/mission-bg.jpg" alt="Our Mission" loading="lazy">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Team Section -->
    <section class="about-team">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['meet_our_team'] ?? 'Meet Our Team' ?></h2>
            <div class="team-grid">
                <div class="team-member animate-on-scroll">
                    <div class="member-image">
                        <img src="/assets/images/team/amineh.jpg" alt="Amineh Al Rabbat" loading="lazy">
                        <div class="member-overlay">
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Amineh Al Rabbat</h3>
                    <p class="member-role"><?= $lang['founder_ceo'] ?? 'Founder & CEO' ?></p>
                </div>
                <div class="team-member animate-on-scroll">
                    <div class="member-image">
                        <img src="/assets/images/team/member2.jpg" alt="Team Member" loading="lazy">
                        <div class="member-overlay">
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Sarah Weber</h3>
                    <p class="member-role"><?= $lang['fashion_director'] ?? 'Fashion Director' ?></p>
                </div>
                <div class="team-member animate-on-scroll">
                    <div class="member-image">
                        <img src="/assets/images/team/member3.jpg" alt="Team Member" loading="lazy">
                        <div class="member-overlay">
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <h3>Michael Schmidt</h3>
                    <p class="member-role"><?= $lang['operations_manager'] ?? 'Operations Manager' ?></p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Why Choose Us Section -->
    <section class="about-why-us">
        <div class="container">
            <h2 class="section-title animate-on-scroll"><?= $lang['why_choose_us'] ?? 'Why Choose ZIN Fashion?' ?></h2>
            <div class="features-list">
                <div class="feature-item animate-on-scroll">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4><?= $lang['premium_quality'] ?? 'Premium Quality' ?></h4>
                        <p><?= $lang['premium_quality_desc'] ?? 'Carefully selected materials and meticulous attention to detail in every product.' ?></p>
                    </div>
                </div>
                <div class="feature-item animate-on-scroll">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4><?= $lang['competitive_prices'] ?? 'Competitive Prices' ?></h4>
                        <p><?= $lang['competitive_prices_desc'] ?? 'Fashion doesn\'t have to be expensive. We offer great value without compromising on quality.' ?></p>
                    </div>
                </div>
                <div class="feature-item animate-on-scroll">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4><?= $lang['fast_shipping'] ?? 'Fast Shipping' ?></h4>
                        <p><?= $lang['fast_shipping_desc'] ?? 'Quick and reliable delivery across Germany and Europe with free shipping on orders over €50.' ?></p>
                    </div>
                </div>
                <div class="feature-item animate-on-scroll">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h4><?= $lang['customer_first'] ?? 'Customer First' ?></h4>
                        <p><?= $lang['customer_first_desc'] ?? 'Your satisfaction is our priority with easy returns and dedicated customer support.' ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="about-cta">
        <div class="container">
            <div class="cta-content animate-on-scroll">
                <h2><?= $lang['ready_to_shop'] ?? 'Ready to Start Shopping?' ?></h2>
                <p><?= $lang['explore_collection'] ?? 'Explore our latest collection and find your perfect style today.' ?></p>
                <div class="cta-buttons">
                    <a href="/shop" class="btn btn-primary"><?= $lang['shop_now'] ?? 'Shop Now' ?></a>
                    <a href="/contact" class="btn btn-outline"><?= $lang['contact_us'] ?? 'Contact Us' ?></a>
                </div>
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
