<?php
/**
 * ZIN Fashion - Footer Component (Fixed)
 * Location: /public_html/dev_staging/includes/components/footer.php
 * 
 * Usage: require_once 'includes/components/footer.php';
 */

// Get business information from settings
$businessInfo = [
    'name' => 'ZIN Fashion',
    'street' => 'Friedrich-Engels-Straße 3',
    'postal' => '39646',
    'city' => 'Oebisfelde',
    'country' => 'Deutschland',
    'phone' => '+49 15563 576774',
    'email' => 'trend@zinfashion.de'
];

// Social media links (NO WhatsApp)
$socialLinks = [
    'facebook' => '#',
    'instagram' => '#',
    'tiktok' => '#'
];

// Payment methods
$paymentMethods = ['Visa', 'Mastercard', 'PayPal', 'Klarna'];

// Newsletter subscription status
$newsletterMessage = $_SESSION['newsletter_message'] ?? '';
$newsletterType = $_SESSION['newsletter_type'] ?? '';
unset($_SESSION['newsletter_message'], $_SESSION['newsletter_type']);
?>

    </main>
    <!-- End of main content (from header.php) -->

    <!-- Newsletter Section -->
    <section class="newsletter" id="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2 class="newsletter-title" data-i18n="newsletter-title">Bleiben Sie auf dem Laufenden</h2>
                <p class="newsletter-text" data-i18n="newsletter-text">
                    Melden Sie sich für unseren Newsletter an und erhalten Sie 10% Rabatt auf Ihre erste Bestellung
                </p>
                
                <?php if ($newsletterMessage): ?>
                    <div class="alert alert-<?= $newsletterType ?>" role="alert">
                        <?= htmlspecialchars($newsletterMessage) ?>
                    </div>
                <?php endif; ?>
                
                <form class="newsletter-form" id="newsletterForm" method="POST" action="<?= SITE_URL ?>/api.php?action=newsletter">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="email" 
                           name="email" 
                           class="newsletter-input" 
                           placeholder="Ihre E-Mail-Adresse" 
                           data-i18n-placeholder="email-placeholder"
                           required 
                           aria-label="Email address">
                    <button type="submit" class="btn btn-primary" data-i18n="subscribe">
                        Abonnieren
                    </button>
                </form>
                
                <p class="newsletter-privacy" data-i18n="privacy-note">
                    <i class="fas fa-lock"></i>
                    Wir respektieren Ihre Privatsphäre. Keine Spam-Mails.
                    <a href="<?= SITE_URL ?>/privacy" target="_blank">Datenschutz</a>
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" role="contentinfo">
        <div class="container">
            <div class="footer-content">
                <!-- Company Info -->
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="<?= SITE_URL ?>/assets/images/logo.svg" 
                             alt="ZIN Fashion Logo" 
                             width="80" 
                             height="80"
                             loading="lazy">
                    </div>
                    <p class="footer-description" data-i18n="about-text">
                        Premium Mode für die ganze Familie. Qualität, Stil und Nachhaltigkeit stehen bei uns im Mittelpunkt.
                    </p>
                    
                    <!-- Social Links (NO WhatsApp) -->
                    <div class="social-links">
                        <?php if ($socialLinks['facebook']): ?>
                            <a href="<?= $socialLinks['facebook'] ?>" 
                               aria-label="Facebook" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($socialLinks['instagram']): ?>
                            <a href="<?= $socialLinks['instagram'] ?>" 
                               aria-label="Instagram" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <i class="fab fa-instagram"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($socialLinks['tiktok']): ?>
                            <a href="<?= $socialLinks['tiktok'] ?>" 
                               aria-label="TikTok" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <i class="fab fa-tiktok"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer Service (NO FAQ, NO Order Tracking) -->
                <div class="footer-section">
                    <h3 data-i18n="customer-service">Kundenservice</h3>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/shipping" data-i18n="shipping">Versand & Lieferung</a></li>
                        <li><a href="<?= SITE_URL ?>/returns" data-i18n="returns">Rückgabe & Umtausch</a></li>
                        <li><a href="<?= SITE_URL ?>/size-guide" data-i18n="size-guide">Größentabelle</a></li>
                        <li><a href="<?= SITE_URL ?>/contact" data-i18n="contact-us">Kontakt</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="<?= SITE_URL ?>/account/orders" data-i18n="my-orders">Meine Bestellungen</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Legal Information -->
                <div class="footer-section">
                    <h3 data-i18n="legal">Rechtliches</h3>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/privacy" data-i18n="privacy">Datenschutz</a></li>
                        <li><a href="<?= SITE_URL ?>/terms" data-i18n="terms">AGB</a></li>
                        <li><a href="<?= SITE_URL ?>/imprint" data-i18n="imprint">Impressum</a></li>
                        <li><a href="<?= SITE_URL ?>/revocation" data-i18n="revocation">Widerrufsbelehrung</a></li>
                        <li><a href="<?= SITE_URL ?>/cookies" data-i18n="cookies">Cookie-Richtlinie</a></li>
                    </ul>
                </div>

                <!-- Contact Information (NO WhatsApp) -->
                <div class="footer-section">
                    <h3 data-i18n="contact-info">Kontakt</h3>
                    <div class="contact-info">
                        <p>
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($businessInfo['street']) ?><br>
                            <?= htmlspecialchars($businessInfo['postal'] . ' ' . $businessInfo['city']) ?><br>
                            <?= htmlspecialchars($businessInfo['country']) ?>
                        </p>
                        <p>
                            <i class="fas fa-phone"></i>
                            <a href="tel:<?= str_replace(' ', '', $businessInfo['phone']) ?>">
                                <?= htmlspecialchars($businessInfo['phone']) ?>
                            </a>
                        </p>
                        <p>
                            <i class="fas fa-envelope"></i>
                            <a href="mailto:<?= $businessInfo['email'] ?>">
                                <?= htmlspecialchars($businessInfo['email']) ?>
                            </a>
                        </p>
                    </div>
                    
                    <!-- Payment Methods -->
                    <div class="payment-methods">
                        <h4 data-i18n="payment-methods">Zahlungsmethoden</h4>
                        <div class="payment-icons">
                            <?php foreach ($paymentMethods as $method): ?>
                                <img src="<?= SITE_URL ?>/assets/images/payment/<?= $method ?>.svg" 
                                     alt="<?= ucfirst($method) ?>" 
                                     width="40" 
                                     height="25"
                                     loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom (NO Sitemap, NO Accessibility, NO Careers) -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="copyright">
                        &copy; <?= date('Y') ?> ZIN Fashion. 
                        <span data-i18n="all-rights">Alle Rechte vorbehalten</span>.
                        <br class="show-mobile">
                        <span style="color: var(--text-tertiary); font-size: 12px;">
                            Powered by <a href="https://elwafamedia.com" target="_blank" style="color: var(--primary-gold); text-decoration: none;">El Wafa Media</a>
                        </span>
                    </p>
                    
                    <!-- Trust Badges -->
                    <div class="trust-badges">
                        <img src="<?= SITE_URL ?>/assets/images/ssl-secure.svg" 
                             alt="SSL Secured" 
                             width="60" 
                             height="30"
                             loading="lazy">
                        <img src="<?= SITE_URL ?>/assets/images/trusted-shop.svg" 
                             alt="Trusted Shop" 
                             width="60" 
                             height="30"
                             loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Back to top" style="display: none;">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Cookie Consent Banner -->
    <?php if (!isset($_COOKIE['cookie_consent'])): ?>
    <div class="cookie-banner" id="cookieBanner">
        <div class="cookie-content">
            <p data-i18n="cookie-text">
                Diese Website verwendet Cookies für einen störungsfreien Betrieb und bessere Benutzererfahrung.
            </p>
            <div class="cookie-actions">
                <button class="btn btn-secondary" onclick="declineCookies()" data-i18n="decline">
                    Ablehnen
                </button>
                <button class="btn btn-primary" onclick="acceptCookies()" data-i18n="accept">
                    Akzeptieren
                </button>
                <a href="<?= SITE_URL ?>/cookies" class="cookie-link" data-i18n="learn-more">
                    Mehr erfahren
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>/assets/js/translations.js?v=<?= filemtime('assets/js/translations.js') ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/main.js?v=<?= filemtime('assets/js/main.js') ?>"></script>
    <script src="<?= SITE_URL ?>/assets/js/cart.js?v=<?= filemtime('assets/js/cart.js') ?>"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Initialize JavaScript -->
    <script>
        // Pass PHP data to JavaScript
        window.siteConfig = {
            siteUrl: '<?= SITE_URL ?>',
            currentLang: '<?= $lang ?>',
            currentTheme: '<?= $theme ?>',
            isLoggedIn: <?= $isLoggedIn ? 'true' : 'false' ?>,
            csrfToken: '<?= generateCSRFToken() ?>',
            currency: '€',
            currencyCode: 'EUR'
        };
        
        // Mobile language dropdown handler
        function changeLanguage(lang) {
            localStorage.setItem('lang', lang);
            document.cookie = `lang=${lang};path=/;max-age=31536000`;
            location.reload();
        }
        
        // Theme toggle with animation
        function toggleTheme(button) {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Add animation class
            button.classList.add('switching');
            
            // Switch theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.cookie = `theme=${newTheme};path=/;max-age=31536000`;
            
            // Update icons
            const darkIcon = button.querySelector('[data-theme-icon="dark"]');
            const lightIcon = button.querySelector('[data-theme-icon="light"]');
            
            if (newTheme === 'dark') {
                darkIcon.style.display = 'block';
                lightIcon.style.display = 'none';
            } else {
                darkIcon.style.display = 'none';
                lightIcon.style.display = 'block';
            }
            
            // Remove animation class after animation completes
            setTimeout(() => {
                button.classList.remove('switching');
            }, 500);
        }
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize main app if function exists
            if (typeof initializeApp === 'function') {
                initializeApp();
            }
            
            // Add mobile menu overlay
            if (!document.querySelector('.mobile-menu-overlay')) {
                const overlay = document.createElement('div');
                overlay.className = 'mobile-menu-overlay';
                overlay.onclick = function() {
                    document.querySelector('.nav').classList.remove('mobile-active');
                    this.classList.remove('active');
                };
                document.body.appendChild(overlay);
            }
            
            // Mobile menu toggle
            const mobileToggle = document.getElementById('mobileMenuToggle');
            if (mobileToggle) {
                mobileToggle.onclick = function() {
                    const nav = document.querySelector('.nav');
                    const overlay = document.querySelector('.mobile-menu-overlay');
                    nav.classList.toggle('mobile-active');
                    overlay.classList.toggle('active');
                };
            }
            
            // Back to top button
            const backToTop = document.getElementById('backToTop');
            if (backToTop) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        backToTop.style.display = 'block';
                    } else {
                        backToTop.style.display = 'none';
                    }
                });
                
                backToTop.addEventListener('click', function() {
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                });
            }
        });
        
        // Cookie functions
        function acceptCookies() {
            document.cookie = 'cookie_consent=accepted;path=/;max-age=31536000';
            document.getElementById('cookieBanner').style.display = 'none';
        }
        
        function declineCookies() {
            document.cookie = 'cookie_consent=declined;path=/;max-age=31536000';
            document.getElementById('cookieBanner').style.display = 'none';
        }
    </script>
    
    <!-- Custom JavaScript (page-specific) -->
    <?php if (isset($pageScripts)): ?>
        <script>
            <?= $pageScripts ?>
        </script>
    <?php endif; ?>
    
    <!-- Google Analytics / Matomo (if configured) -->
    <?php if (defined('ANALYTICS_ID') && ANALYTICS_ID): ?>
    <!-- Add your analytics code here -->
    <?php endif; ?>
</body>
</html>
