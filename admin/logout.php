<?php
/**
 * Admin Logout Handler
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

// Log activity before logout
if (Session::isAdminLoggedIn()) {
    $db = db();
    $db->insert('activity_log', [
        'admin_id' => Session::getAdminId(),
        'action' => 'logout',
        'description' => 'Admin logged out',
        'ip_address' => Security::getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Destroy admin session
Session::adminLogout();

flash_success('You have been logged out successfully.');
redirect(url('admin/login.php'));
