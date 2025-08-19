-- =============================================
-- ZIN Fashion - Sample Demo Data (PUBLIC VERSION)
-- This file contains generic demo data for testing
-- No real business information or sensitive data
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- =============================================
-- SYSTEM SETTINGS (Generic)
-- =============================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('primary_language', 'de', 'general'),
('supported_languages', 'de,en,ar', 'general'),
('tax_rate', '19', 'general'),
('currency', 'EUR', 'general'),
('store_name', 'Demo Fashion Store', 'general'),
('store_email', 'info@demo-store.com', 'general'),
('free_shipping_threshold', '50', 'shipping'),
('order_prefix', 'ORD', 'general'),
('stock_management', '1', 'inventory'),
('allow_guest_checkout', '1', 'checkout'),
('min_password_length', '8', 'security'),
('max_login_attempts', '5', 'security'),
('lockout_duration', '30', 'security');

-- =============================================
-- CATEGORIES (Same structure, generic names)
-- =============================================

-- Main categories
INSERT INTO `categories` (`category_name`, `category_name_en`, `category_name_ar`, `slug`, `parent_id`, `display_order`) VALUES
('Damen', 'Women', 'نسائي', 'damen', NULL, 1),
('Herren', 'Men', 'رجالي', 'herren', NULL, 2),
('Kinder', 'Children', 'اطفال', 'kinder', NULL, 3),
('Heimtextilien', 'Home Textiles', 'قطنيات', 'heimtextilien', NULL, 4);

-- Get IDs for subcategories
SET @women_id = (SELECT category_id FROM categories WHERE slug = 'damen');
SET @men_id = (SELECT category_id FROM categories WHERE slug = 'herren');
SET @children_id = (SELECT category_id FROM categories WHERE slug = 'kinder');
SET @home_id = (SELECT category_id FROM categories WHERE slug = 'heimtextilien');

-- Women's subcategories
INSERT INTO `categories` (`category_name`, `category_name_en`, `category_name_ar`, `slug`, `parent_id`, `display_order`) VALUES
('Kleider', 'Dresses', 'فساتين', 'kleider', @women_id, 1),
('Blusen', 'Blouses', 'بلوزات', 'blusen', @women_id, 2),
('Hosen', 'Pants', 'بناطيل', 'damen-hosen', @women_id, 3);

-- Men's subcategories
INSERT INTO `categories` (`category_name`, `category_name_en`, `category_name_ar`, `slug`, `parent_id`, `display_order`) VALUES
('Hemden', 'Shirts', 'قمصان', 'hemden', @men_id, 1),
('Hosen', 'Pants', 'بناطيل', 'herren-hosen', @men_id, 2),
('Jacken', 'Jackets', 'جاكيتات', 'herren-jacken', @men_id, 3);

-- =============================================
-- COLORS (Standard colors)
-- =============================================

INSERT INTO `colors` (`color_name`, `color_name_en`, `color_name_ar`, `color_code`) VALUES
('Schwarz', 'Black', 'أسود', '#000000'),
('Weiß', 'White', 'أبيض', '#FFFFFF'),
('Grau', 'Gray', 'رمادي', '#808080'),
('Blau', 'Blue', 'أزرق', '#0000FF'),
('Rot', 'Red', 'أحمر', '#FF0000');

-- =============================================
-- SIZE GROUPS (Standard sizing)
-- =============================================

INSERT INTO `size_groups` (`group_name`, `group_name_en`, `group_name_ar`, `product_type`) VALUES
('Standard Größen', 'Standard Sizes', 'مقاسات قياسية', 'standard'),
('Große Größen', 'Plus Sizes', 'مقاسات كبيرة', 'plus_size');

-- =============================================
-- ADMIN ROLES (Structure only)
-- =============================================

INSERT INTO `admin_roles` (`role_name`, `role_description`, `permissions`) VALUES
('Super Admin', 'Full system access', '["all"]'),
('Manager', 'General management access', '["products", "orders", "customers"]'),
('Editor', 'Content management', '["products", "content"]');

-- =============================================
-- ADMIN USER (Template - No real password)
-- =============================================

-- IMPORTANT: This is a template only
-- You must create your own admin user with a secure password
INSERT INTO `admin_users` (`username`, `email`, `password_hash`, `first_name`, `last_name`, `role_id`, `is_active`) VALUES
('admin', 'admin@demo-store.com', '', 'Demo', 'Admin', 1, 1);
-- Note: password_hash is empty - you must set your own password

-- =============================================
-- SAMPLE PRODUCTS (Demo data)
-- =============================================

-- Example product 1
INSERT INTO `products` (
    `category_id`, `product_name`, `product_name_en`, `product_slug`, 
    `sku`, `description`, `base_price`, `is_active`
) VALUES (
    @women_id, 
    'Elegantes Abendkleid', 
    'Elegant Evening Dress', 
    'elegantes-abendkleid',
    'DEMO-001',
    'Ein wunderschönes Abendkleid für besondere Anlässe.',
    89.99,
    1
);

-- Example product 2
INSERT INTO `products` (
    `category_id`, `product_name`, `product_name_en`, `product_slug`, 
    `sku`, `description`, `base_price`, `is_active`
) VALUES (
    @men_id,
    'Business Hemd', 
    'Business Shirt', 
    'business-hemd',
    'DEMO-002',
    'Klassisches Business Hemd aus hochwertiger Baumwolle.',
    49.99,
    1
);

-- =============================================
-- SHIPPING METHODS (Standard options)
-- =============================================

INSERT INTO `shipping_methods` (`method_name`, `method_name_en`, `method_name_ar`, `base_cost`, `free_shipping_threshold`, `estimated_days_min`, `estimated_days_max`) VALUES
('Standardversand', 'Standard Shipping', 'الشحن القياسي', 4.99, 50.00, 3, 5),
('Expressversand', 'Express Shipping', 'الشحن السريع', 9.99, 100.00, 1, 2);

-- =============================================
-- DEMO BUSINESS INFO (Generic)
-- =============================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('business_name', 'Demo Fashion Store', 'legal'),
('business_street', 'Example Street 123', 'legal'),
('business_postal', '12345', 'legal'),
('business_city', 'Demo City', 'legal'),
('business_country', 'Germany', 'legal'),
('business_email', 'info@demo-store.com', 'contact'),
('business_phone', '+49 (0)123 456789', 'contact'),
('customer_service_email', 'support@demo-store.com', 'contact'),
('customer_service_phone', '+49 (0)123 456789', 'contact');

COMMIT;

-- =============================================
-- IMPORTANT NOTES FOR DEPLOYMENT:
-- =============================================
-- 1. This is demo data only for testing
-- 2. Replace all demo values with your real data
-- 3. Create a proper admin user with secure password
-- 4. Update business information with real details
-- 5. Never commit real customer data or passwords
