<?php
/**
 * ZIN Fashion - Language Handler
 * Location: /public_html/dev_staging/includes/language-handler.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define available languages
$availableLanguages = ['de', 'en', 'ar'];

// Check if language change is requested via GET parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLanguages)) {
    $_SESSION['language'] = $_GET['lang'];
    
    // Redirect to clean URL without the lang parameter
    $cleanUrl = strtok($_SERVER["REQUEST_URI"], '?');
    $queryParams = $_GET;
    unset($queryParams['lang']); // Remove lang parameter
    
    if (!empty($queryParams)) {
        $cleanUrl .= '?' . http_build_query($queryParams);
    }
    
    header("Location: " . $cleanUrl);
    exit();
}

// Set current language from session or default to German
$currentLang = $_SESSION['language'] ?? 'de';

// Include the appropriate language file
$langFile = __DIR__ . "/languages/{$currentLang}.php";
if (file_exists($langFile)) {
    require_once $langFile;
} else {
    require_once __DIR__ . "/languages/de.php";
}
?>
