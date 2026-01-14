<?php
/**
 * Reset Password Page
 * 
 * Reset password using token from email link.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect(url());
}

$token = Security::sanitizeString($_GET['token'] ?? '');
$error = '';
$success = false;
$validToken = false;

if (empty($token)) {
    $error = 'Invalid password reset link.';
} else {
    $db = db();
    $user = $db->fetchOne(
        "SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
        [$token]
    );
    
    if ($user) {
        $validToken = true;
    } else {
        $error = 'This password reset link has expired or is invalid.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    validate_csrf();
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = 'Please enter a new password.';
    } else {
        $passwordCheck = Security::validatePasswordStrength($password);
        if (!$passwordCheck['valid']) {
            $error = $passwordCheck['errors'][0];
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Update password
            $db->update('users', [
                'password' => Security::hashPassword($password),
                'reset_token' => null,
                'reset_token_expires' => null
            ], 'id = ?', [$user['id']]);
            
            $success = true;
        }
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Reset Password Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-accent-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= url() ?>" class="inline-flex items-center justify-center space-x-3">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-600 to-accent-500 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="mt-6 text-3xl font-bold text-gray-900">
                <?= $success ? 'Password Reset!' : 'Reset Password' ?>
            </h1>
            <p class="mt-2 text-gray-600">
                <?= $success ? 'Your password has been successfully reset.' : 'Enter your new password below.' ?>
            </p>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
            <?php if ($success): ?>
            <!-- Success Message -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
                    <i class="fas fa-check text-green-600 text-2xl"></i>
                </div>
                <p class="text-gray-600 mb-6">
                    Your password has been changed. You can now login with your new password.
                </p>
                <a href="<?= url('login.php') ?>" class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login Now
                </a>
            </div>
            
            <?php elseif (!$validToken): ?>
            <!-- Invalid Token -->
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                    <i class="fas fa-times text-red-600 text-2xl"></i>
                </div>
                <p class="text-gray-600 mb-6"><?= e($error) ?></p>
                <a href="<?= url('forgot-password.php') ?>" class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
                    <i class="fas fa-redo mr-2"></i>
                    Request New Link
                </a>
            </div>
            
            <?php else: ?>
            <!-- Reset Form -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5">
                <?= csrf_field() ?>
                
                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-11 pr-12 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Enter new password">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Min 8 characters with uppercase, lowercase, and numbers.</p>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full pl-11 pr-12 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Confirm new password">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl 
                                             hover:shadow-lg hover:shadow-primary-500/30 focus:ring-4 focus:ring-primary-300 
                                             transition-all duration-300">
                    <i class="fas fa-key mr-2"></i>
                    Reset Password
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

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
