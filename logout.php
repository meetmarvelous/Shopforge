<?php
/**
 * Logout Handler
 * 
 * Securely destroys user session and cookies.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

// Clear remember token from database
if (Session::isLoggedIn()) {
    $db = db();
    $db->update('users', ['remember_token' => null], 'id = ?', [Session::getUserId()]);
}

// Clear remember cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', SESSION_SECURE, true);
}

// Destroy session
Session::logout();

// Flash message and redirect
flash_success('You have been logged out successfully.');
redirect(url('login.php'));
