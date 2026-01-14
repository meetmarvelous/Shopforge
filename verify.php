<?php
/**
 * Email Verification Page
 * 
 * Verifies user email address using token from verification link.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$token = Security::sanitizeString($_GET['token'] ?? '');
$success = false;
$message = '';

if (empty($token)) {
    $message = 'Invalid verification link.';
} else {
    $db = db();
    
    // Find user with this token
    $user = $db->fetchOne(
        "SELECT id, email, email_verified_at FROM users WHERE verification_token = ?",
        [$token]
    );
    
    if (!$user) {
        $message = 'Invalid or expired verification link.';
    } elseif ($user['email_verified_at']) {
        $message = 'Your email has already been verified. You can now login.';
        $success = true;
    } else {
        // Verify the email
        $db->update('users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null
        ], 'id = ?', [$user['id']]);
        
        $success = true;
        $message = 'Your email has been verified successfully! You can now login to your account.';
    }
}

$pageTitle = 'Email Verification';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Verification Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-accent-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full text-center">
        <!-- Icon -->
        <div class="mb-8">
            <?php if ($success): ?>
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 rounded-full shadow-lg shadow-green-500/30 animate-scale-in">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
            <?php else: ?>
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-red-400 to-red-600 rounded-full shadow-lg shadow-red-500/30 animate-scale-in">
                <i class="fas fa-times text-white text-4xl"></i>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Message -->
        <h1 class="text-2xl font-bold text-gray-900 mb-4">
            <?= $success ? 'Email Verified!' : 'Verification Failed' ?>
        </h1>
        <p class="text-gray-600 mb-8">
            <?= e($message) ?>
        </p>
        
        <!-- Actions -->
        <div class="space-y-4">
            <?php if ($success): ?>
            <a href="<?= url('login.php') ?>" class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Login Now
            </a>
            <?php else: ?>
            <a href="<?= url('signup.php') ?>" class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
                <i class="fas fa-user-plus mr-2"></i>
                Create New Account
            </a>
            <?php endif; ?>
            
            <div>
                <a href="<?= url() ?>" class="text-primary-600 hover:text-primary-700 font-medium">
                    <i class="fas fa-home mr-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
