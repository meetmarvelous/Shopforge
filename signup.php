<?php
/**
 * User Registration Page
 * 
 * Registration form with validation and email verification.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect(url());
}

$errors = [];
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $formData = [
        'first_name' => Security::sanitizeString($_POST['first_name'] ?? ''),
        'last_name' => Security::sanitizeString($_POST['last_name'] ?? ''),
        'email' => Security::sanitizeEmail($_POST['email'] ?? ''),
        'phone' => Security::sanitizeString($_POST['phone'] ?? '')
    ];
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $agreeTerms = isset($_POST['agree_terms']);
    
    // Validation
    if (empty($formData['first_name'])) {
        $errors['first_name'] = 'First name is required.';
    } elseif (strlen($formData['first_name']) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters.';
    }
    
    if (empty($formData['last_name'])) {
        $errors['last_name'] = 'Last name is required.';
    } elseif (strlen($formData['last_name']) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters.';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!Security::isValidEmail($formData['email'])) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $db = db();
        if ($db->exists('users', 'email = ?', [$formData['email']])) {
            $errors['email'] = 'This email is already registered. <a href="' . url('login.php') . '" class="underline">Login instead</a>';
        }
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } else {
        $passwordCheck = Security::validatePasswordStrength($password);
        if (!$passwordCheck['valid']) {
            $errors['password'] = $passwordCheck['errors'][0];
        }
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }
    
    if (!$agreeTerms) {
        $errors['agree_terms'] = 'You must agree to the terms and conditions.';
    }
    
    // If no errors, create user
    if (empty($errors)) {
        $db = db();
        
        $verificationToken = Security::generateToken(32);
        
        try {
            $userId = $db->insert('users', [
                'first_name' => $formData['first_name'],
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'phone' => $formData['phone'],
                'password' => Security::hashPassword($password),
                'verification_token' => $verificationToken,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Send verification email (placeholder - implement with PHPMailer)
            $verifyUrl = url('verify.php?token=' . $verificationToken);
            
            // For now, we'll just flash a message
            // In production, send actual email
            flash_success('Registration successful! Please check your email to verify your account.');
            
            // If in development mode, show the verification link
            if (APP_DEBUG) {
                Session::flash('verify_link', $verifyUrl);
            }
            
            redirect(url('login.php'));
            
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred. Please try again.';
            if (APP_DEBUG) {
                $errors['general'] = $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Sign Up Page -->
<div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-accent-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-lg w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?= url() ?>" class="inline-flex items-center justify-center space-x-3">
                <div class="w-14 h-14 bg-gradient-to-br from-primary-600 to-accent-500 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
            </a>
            <h1 class="mt-6 text-3xl font-bold text-gray-900">Create Account</h1>
            <p class="mt-2 text-gray-600">Join us and start shopping today</p>
        </div>
        
        <!-- Registration Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8">
            <?php if (isset($errors['general'])): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-start">
                <i class="fas fa-exclamation-circle mt-0.5 mr-3 flex-shrink-0"></i>
                <span><?= $errors['general'] ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (Session::hasFlash('verify_link')): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm">
                <p class="font-medium mb-2">Development Mode: Verification Link</p>
                <a href="<?= e(Session::getFlash('verify_link')) ?>" class="break-all underline">
                    <?= e(Session::getFlash('verify_link')) ?>
                </a>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5" id="signup-form">
                <?= csrf_field() ?>
                
                <!-- Name Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" id="first_name" name="first_name" value="<?= e($formData['first_name']) ?>" required
                                   class="w-full pl-11 pr-4 py-3.5 border <?= isset($errors['first_name']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                                   placeholder="John">
                        </div>
                        <?php if (isset($errors['first_name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['first_name'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" id="last_name" name="last_name" value="<?= e($formData['last_name']) ?>" required
                                   class="w-full pl-11 pr-4 py-3.5 border <?= isset($errors['last_name']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                                   placeholder="Doe">
                        </div>
                        <?php if (isset($errors['last_name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?= $errors['last_name'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" value="<?= e($formData['email']) ?>" required
                               class="w-full pl-11 pr-4 py-3.5 border <?= isset($errors['email']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="john@example.com">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['email'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Phone (Optional) -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-gray-400">(Optional)</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="tel" id="phone" name="phone" value="<?= e($formData['phone']) ?>"
                               class="w-full pl-11 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="+234 800 000 0000">
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
                               class="w-full pl-11 pr-12 py-3.5 border <?= isset($errors['password']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Create a strong password">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['password'] ?></p>
                    <?php else: ?>
                    <p class="mt-1 text-xs text-gray-500">Min 8 characters with uppercase, lowercase, and numbers.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full pl-11 pr-12 py-3.5 border <?= isset($errors['confirm_password']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                               placeholder="Confirm your password">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['confirm_password'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Terms Agreement -->
                <div>
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="agree_terms" class="w-4 h-4 mt-0.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" required>
                        <span class="ml-3 text-sm text-gray-600">
                            I agree to the 
                            <a href="<?= url('terms.php') ?>" class="text-primary-600 hover:underline">Terms of Service</a>
                            and
                            <a href="<?= url('privacy.php') ?>" class="text-primary-600 hover:underline">Privacy Policy</a>
                        </span>
                    </label>
                    <?php if (isset($errors['agree_terms'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= $errors['agree_terms'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl 
                                             hover:shadow-lg hover:shadow-primary-500/30 focus:ring-4 focus:ring-primary-300 
                                             transition-all duration-300 transform hover:-translate-y-0.5">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </form>
            
            <!-- Login Link -->
            <p class="mt-8 text-center text-gray-600">
                Already have an account?
                <a href="<?= url('login.php') ?>" class="text-primary-600 hover:text-primary-700 font-semibold">
                    Sign in
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
