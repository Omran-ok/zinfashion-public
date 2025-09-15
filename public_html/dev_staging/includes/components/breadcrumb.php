<?php
/**
 * ZIN Fashion - Breadcrumb Component
 * Location: /public_html/dev_staging/includes/components/breadcrumb.php
 * Updated: RTL support for Arabic
 * 
 * Usage:
 * $breadcrumbs = [
 *     ['title' => 'Shop', 'url' => '/shop'],
 *     ['title' => 'Category Name', 'url' => '/shop?category=slug'],
 *     ['title' => 'Current Page', 'url' => null] // null URL means current/active page
 * ];
 * include 'includes/components/breadcrumb.php';
 */

// If breadcrumbs array is not set, create default with just Home
if (!isset($breadcrumbs)) {
    $breadcrumbs = [];
}

// Always prepend Home unless explicitly disabled
if (!isset($hideBreadcrumbHome) || !$hideBreadcrumbHome) {
    array_unshift($breadcrumbs, [
        'title' => $lang['home'] ?? 'Home',
        'url' => '/'
    ]);
}

// Check if we're in RTL mode
$isRTL = (isset($currentLang) && $currentLang === 'ar');
?>

<!-- Breadcrumb Section -->
<div class="breadcrumb-section">
    <div class="container">
        <nav class="breadcrumb" aria-label="<?= $lang['breadcrumb'] ?? 'Breadcrumb' ?>" <?= $isRTL ? 'dir="rtl"' : '' ?>>
            <?php 
            // For RTL, we keep the same order but CSS will handle the visual presentation
            foreach ($breadcrumbs as $index => $crumb): 
                $isLast = ($index === count($breadcrumbs) - 1);
                $title = $crumb['title'];
                $url = $crumb['url'] ?? null;
            ?>
                
                <?php if (!$isLast && $url): ?>
                    <a href="<?= htmlspecialchars($url) ?>" class="breadcrumb-item">
                        <?= htmlspecialchars($title) ?>
                    </a>
                    <span class="separator" aria-hidden="true">/</span>
                <?php else: ?>
                    <span class="breadcrumb-item current" aria-current="page">
                        <?= htmlspecialchars($title) ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
