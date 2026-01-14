<?php
/**
 * User Login Page
 * 
 * Secure login with rate limiting and CSRF protection.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect(url());
}

$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    validate_csrf();
    
    $email = Security::sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Rate limiting
    $rateLimitKey = 'login_' . Security::getClientIP();
    $rateLimit = Security::checkRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
    
    if (!$rateLimit['allowed']) {
        $minutes = ceil($rateLimit['retry_after'] / 60);
        $error = "Too many login attempts. Please try again in {$minutes} minutes.";
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
        Security::incrementRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
    } else {
        // Fetch user
        $db = db();
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if ($user && Security::verifyPassword($password, $user['password'])) {
            // Check if email is verified
            if (empty($user['email_verified_at'])) {
                $error = 'Please verify your email address before logging in. <a href="' . url('resend-verification.php?email=' . urlencode($email)) . '" class="underline">Resend verification email</a>';
            } else {
                // Reset rate limit on successful login
                Security::resetRateLimit($rateLimitKey);
                
                // Update last login
                $db->update('users', [
                    'last_login' => date('Y-m-d H:i:s'),
                    'login_attempts' => 0,
                    'locked_until' => null
                ], 'id = ?', [$user['id']]);
                
                // Rehash password if needed
                if (Security::needsRehash($user['password'])) {
                    $db->update('users', [
                        'password' => Security::hashPassword($password)
                    ], 'id = ?', [$user['id']]);
                }
                
                // Set session
                unset($user['password']);
                Session::setUser($user);
                
                // Handle remember me
                if ($remember) {
                    $token = Security::generateToken(32);
                    $db->update('users', ['remember_token' => $token], 'id = ?', [$user['id']]);
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', SESSION_SECURE, true);
                }
                
                // Merge guest cart
                merge_guest_cart($user['id']);
                
                // Redirect
                flash_success('Welcome back, ' . e($user['first_name']) . '!');
                $intended = Session::getIntendedUrl(url());
                redirect($intended);
            }
        } else {
            Security::incrementRateLimit($rateLimitKey, LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_TIME);
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Login Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-accent-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= url() ?>" class="inline-flex items-center justify-center space-x-3">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-600 to-accent-500 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="mt-6 text-3xl font-bold text-gray-900">Welcome Back</h1>
            <p class="mt-2 text-gray-600">Sign in to your account to continue</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= $error ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5" id="login-form">
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
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="<?= url('forgot-password.php') ?>" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl 
                                             hover:shadow-lg hover:shadow-primary-500/30 focus:ring-4 focus:ring-primary-300 
                                             transition-all duration-300 transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>
            
            <!-- Divider -->
            <div class="my-6 flex items-center">
                <div class="flex-1 border-t border-gray-200"></div>
                <span class="px-4 text-sm text-gray-400">or continue with</span>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>
            
            <!-- Social Login (Placeholder) -->
            <div class="flex gap-3">
                <button type="button" class="flex-1 py-3 border border-gray-200 rounded-xl flex items-center justify-center hover:bg-gray-50 transition">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5 mr-2">
                    <span class="text-sm font-medium text-gray-700">Google</span>
                </button>
                <button type="button" class="flex-1 py-3 border border-gray-200 rounded-xl flex items-center justify-center hover:bg-gray-50 transition">
                    <i class="fab fa-facebook text-blue-600 text-lg mr-2"></i>
                    <span class="text-sm font-medium text-gray-700">Facebook</span>
                </button>
            </div>
            
            <!-- Sign Up Link -->
            <p class="mt-8 text-center text-gray-600">
                Don't have an account?
                <a href="<?= url('signup.php') ?>" class="text-primary-600 hover:text-primary-700 font-semibold">
                    Create one
                </a>
            </p>
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
