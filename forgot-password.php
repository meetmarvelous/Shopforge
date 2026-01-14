<?php
/**
 * Forgot Password Page
 * 
 * Request password reset link via email.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect(url());
}

$success = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $email = Security::sanitizeEmail($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!Security::isValidEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = db();
        $user = $db->fetchOne("SELECT id, first_name FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            // Generate reset token
            $resetToken = Security::generateToken(32);
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            $db->update('users', [
                'reset_token' => $resetToken,
                'reset_token_expires' => $expires
            ], 'id = ?', [$user['id']]);
            
            // Send email (placeholder - implement with PHPMailer)
            $resetUrl = url('reset-password.php?token=' . $resetToken);
            
            // In development, show the link
            if (APP_DEBUG) {
                Session::flash('reset_link', $resetUrl);
            }
        }
        
        // Always show success to prevent email enumeration
        $success = true;
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Forgot Password Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-accent-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= url() ?>" class="inline-flex items-center justify-center space-x-3">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-600 to-accent-500 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="mt-6 text-3xl font-bold text-gray-900">Forgot Password?</h1>
            <p class="mt-2 text-gray-600">No worries, we'll send you reset instructions.</p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
            <?php if ($success): ?>
            <!-- Success Message -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
                    <i class="fas fa-envelope-open-text text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Check your email</h2>
                <p class="text-gray-600 mb-6">
                    We've sent a password reset link to <strong><?= e($email) ?></strong>
                </p>
                
                <?php if (Session::hasFlash('reset_link')): ?>
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm text-left">
                    <p class="font-medium mb-2">Development Mode: Reset Link</p>
                    <a href="<?= e(Session::getFlash('reset_link')) ?>" class="break-all underline">
                        Click here to reset password
                    </a>
                </div>
                <?php endif; ?>
                
                <p class="text-sm text-gray-500 mb-6">
                    Didn't receive the email? Check your spam folder or
                </p>
                <button type="button" onclick="window.location.reload()" class="text-primary-600 font-medium hover:underline">
                    Try again
                </button>
            </div>
            
            <?php else: ?>
            <!-- Form -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="<?= e($email) ?>" required
                               class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl 
                                             hover:shadow-lg hover:shadow-primary-500/30 focus:ring-4 focus:ring-primary-300 
                                             transition-all duration-300">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Send Reset Link
                </button>
            </form>
            <?php endif; ?>
            
            <!-- Back to Login -->
            <div class="mt-8 text-center">
                <a href="<?= url('login.php') ?>" class="text-gray-600 hover:text-primary-600 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
