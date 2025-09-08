<?php
/**
 * ZIN Fashion - Footer Component
 * Location: /public_html/dev_staging/includes/components/footer.php
 */
?>

<!-- Footer -->
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <!-- Column 1: Logo & Social -->
            <div class="footer-column footer-about">
                <div class="footer-logo">
                    <img src="/assets/images/logo.svg" alt="ZIN Fashion">
                </div>
                <p class="footer-description">
                    <?= $lang['footer_description'] ?? 'Premium fashion store offering the latest trends and timeless classics for the modern lifestyle.' ?>
                </p>
                <div class="social-links">
                    <a href="https://facebook.com/zinfashion" target="_blank" rel="noopener" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://instagram.com/zinfashion" target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://tiktok.com/@zinfashion" target="_blank" rel="noopener" aria-label="TikTok">
                        <i class="fab fa-tiktok"></i>
                    </a>
                </div>
            </div>
            
            <!-- Column 2: Customer Service -->
            <div class="footer-column">
                <h4><?= $lang['customer_service'] ?? 'Customer Service' ?></h4>
                <ul class="footer-links">
                    <li><a href="/about"><?= $lang['about_us'] ?? 'About Us' ?></a></li>
                    <li><a href="/contact"><?= $lang['contact'] ?? 'Contact' ?></a></li>
                    <li><a href="/shipping"><?= $lang['shipping_delivery'] ?? 'Shipping & Delivery' ?></a></li>
                    <li><a href="/returns"><?= $lang['returns_refunds'] ?? 'Returns & Refunds' ?></a></li>
                    <li><a href="/size-guide"><?= $lang['size_guide'] ?? 'Size Guide' ?></a></li>
                </ul>
            </div>
            
            <!-- Column 3: Legal Information -->
            <div class="footer-column">
                <h4><?= $lang['legal_info'] ?? 'Legal Information' ?></h4>
                <ul class="footer-links">
                    <li><a href="/impressum"><?= $lang['impressum'] ?? 'Impressum' ?></a></li>
                    <li><a href="/datenschutz"><?= $lang['privacy_policy'] ?? 'Datenschutzerklärung' ?></a></li>
                    <li><a href="/agb"><?= $lang['terms_conditions'] ?? 'AGB' ?></a></li>
                    <li><a href="/widerruf"><?= $lang['revocation'] ?? 'Widerrufsbelehrung' ?></a></li>
                    <li><a href="/cookies"><?= $lang['cookie_policy'] ?? 'Cookie Policy' ?></a></li>
                </ul>
            </div>
            
            <!-- Column 4: Contact Info -->
            <div class="footer-column">
                <h4><?= $lang['contact_info'] ?? 'Contact Information' ?></h4>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <p>Friedrich-Engels-Straße 3</p>
                            <p>39646 Oebisfelde, Deutschland</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:trend@zinfashion.de">trend@zinfashion.de</a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:+4915563576774">+49 15563 576774</a>
                    </div>
                </div>
                
                <!-- Payment Methods -->
                <div class="payment-methods">
                    <h5><?= $lang['payment_methods'] ?? 'Payment Methods' ?></h5>
                    <div class="payment-icons">
                        <img src="/assets/images/payment/visa.svg" alt="Visa">
                        <img src="/assets/images/payment/mastercard.svg" alt="Mastercard">
                        <img src="/assets/images/payment/paypal.svg" alt="PayPal">
                        <img src="/assets/images/payment/klarna.svg" alt="Klarna">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> ZIN Fashion. <?= $lang['all_rights_reserved'] ?? 'All rights reserved.' ?></p>
                <p><?= $lang['powered_by'] ?? 'Powered by' ?> <a href="https://elwafamedia.de" target="_blank" rel="noopener">El Wafa Media</a></p>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Cookie Notice -->
<div class="cookie-notice" id="cookieNotice">
    <div class="cookie-content">
        <p><?= $lang['cookie_notice'] ?? 'We use cookies to enhance your shopping experience. By continuing to browse, you agree to our use of cookies.' ?></p>
        <div class="cookie-actions">
            <button class="btn btn-small btn-outline" id="cookieDecline"><?= $lang['decline'] ?? 'Decline' ?></button>
            <button class="btn btn-small btn-primary" id="cookieAccept"><?= $lang['accept'] ?? 'Accept' ?></button>
        </div>
    </div>
</div>
