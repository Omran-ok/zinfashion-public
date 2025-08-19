<?php
/**
 * ZIN Fashion - Admin Logout
 * Location: /public_html/dev_staging/admin/logout.php
 */

session_start();

// Log the logout activity if admin was logged in
if (isset($_SESSION['admin_id'])) {
    require_once '../includes/config.php';
    
    try {
        $pdo = getDBConnection();
        $logSql = "INSERT INTO admin_activity_log (admin_id, action_type, action_description, ip_address, created_at) 
                  VALUES (:admin_id, 'logout', 'Admin logout', :ip, NOW())";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([
            'admin_id' => $_SESSION['admin_id'],
            'ip' => getUserIP()
        ]);
    } catch (Exception $e) {
        // Silent fail - don't prevent logout
    }
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
