<?php
/**
 * Admin Login Page
 * 
 * Secure admin authentication.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (Session::isAdminLoggedIn()) {
    redirect(url('admin/index.php'));
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $email = Security::sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Rate limiting
    $rateLimitKey = 'admin_login_' . Security::getClientIP();
    $rateLimit = Security::checkRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
    
    if (!$rateLimit['allowed']) {
        $minutes = ceil($rateLimit['retry_after'] / 60);
        $error = "Too many login attempts. Please try again in {$minutes} minutes.";
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
        Security::incrementRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
    } else {
        $db = db();
        $admin = $db->fetchOne(
            "SELECT * FROM admins WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($admin && Security::verifyPassword($password, $admin['password'])) {
            // Reset rate limit
            Security::resetRateLimit($rateLimitKey);
            
            // Update last login
            $db->update('admins', [
                'last_login' => date('Y-m-d H:i:s')
            ], 'id = ?', [$admin['id']]);
            
            // Set admin session - add username alias for compatibility
            unset($admin['password']);
            $admin['username'] = $admin['name']; // Alias for sidebar/navbar display
            Session::setAdmin($admin);
            
            // Log activity
            $db->insert('activity_log', [
                'admin_id' => $admin['id'],
                'action' => 'login',
                'description' => 'Admin logged in',
                'ip_address' => Security::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            flash_success('Welcome back, ' . e($admin['name']) . '!');
            redirect(url('admin/index.php'));
        } else {
            Security::incrementRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | <?= e(SITE_NAME) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-purple-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur-lg rounded-2xl mb-4">
                <i class="fas fa-store text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white"><?= e(SITE_NAME) ?></h1>
            <p class="text-white/60">Admin Panel</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h2 class="text-xl font-bold text-gray-900 mb-6 text-center">Sign in to continue</h2>
            
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
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="<?= e($email) ?>" required
                               class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-11 pr-12 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Enter your password">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Submit -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl 
                                             hover:shadow-lg hover:shadow-primary-500/30 focus:ring-4 focus:ring-primary-300 
                                             transition-all duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="<?= url() ?>" class="text-gray-500 hover:text-primary-600 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Store
                </a>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="mt-8 text-center text-white/40 text-sm">
            &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.
        </p>
    </div>
    
    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('password-icon');
        
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
</body>
</html>
