<?php
/**
 * Navigation Bar Template
 * 
 * Responsive navbar with search, cart, and user menu.
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

$cartCount = get_cart_count();
$isLoggedIn = Session::isLoggedIn();
$user = Session::getUser();

// Get categories for mega menu
$db = db();
$navCategories = $db->fetchAll("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order, name LIMIT 10");
?>

<!-- Top Announcement Bar -->
<div class="bg-gradient-to-r from-primary-600 via-primary-500 to-accent-500 text-white py-2 text-center text-sm font-medium">
    <div class="container mx-auto px-4">
        <p class="flex items-center justify-center gap-2">
            <i class="fas fa-truck animate-pulse-soft"></i>
            Free shipping on orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?>
            <span class="hidden md:inline">| 24/7 Customer Support</span>
        </p>
    </div>
</div>

<!-- Main Navigation -->
<header class="bg-white shadow-sm sticky top-0 z-50 transition-all duration-300" id="main-header">
    <nav class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <a href="<?= url() ?>" class="flex items-center space-x-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-accent-500 rounded-xl flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-store text-white text-lg"></i>
                </div>
                <span class="text-xl lg:text-2xl font-bold bg-gradient-to-r from-primary-600 to-accent-500 bg-clip-text text-transparent">
                    <?= e(SITE_NAME) ?>
                </span>
            </a>
            
            <!-- Search Bar (Desktop) -->
            <div class="hidden lg:flex flex-1 max-w-xl mx-8">
                <form action="<?= url('search.php') ?>" method="GET" class="w-full relative group">
                    <input 
                        type="text" 
                        name="q" 
                        placeholder="Search for products..." 
                        class="w-full pl-12 pr-4 py-3 bg-gray-100 border-2 border-transparent rounded-2xl 
                               focus:bg-white focus:border-primary-500 focus:ring-4 focus:ring-primary-100 
                               transition-all duration-300 outline-none"
                        value="<?= e(get('q')) ?>"
                        autocomplete="off"
                    >
                    <button type="submit" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary-600 transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <!-- Right Actions -->
            <div class="flex items-center space-x-2 lg:space-x-4">
                <!-- Mobile Search Toggle -->
                <button type="button" class="lg:hidden p-2 text-gray-600 hover:text-primary-600 transition-colors" id="mobile-search-toggle">
                    <i class="fas fa-search text-xl"></i>
                </button>
                
                <!-- Wishlist -->
                <?php if ($isLoggedIn): ?>
                <a href="<?= url('wishlist.php') ?>" class="hidden md:flex items-center gap-2 p-2 text-gray-600 hover:text-primary-600 transition-colors group">
                    <i class="far fa-heart text-xl group-hover:scale-110 transition-transform"></i>
                    <span class="hidden lg:inline font-medium">Wishlist</span>
                </a>
                <?php endif; ?>
                
                <!-- Cart -->
                <a href="<?= url('cart.php') ?>" class="relative p-2 text-gray-600 hover:text-primary-600 transition-colors group">
                    <i class="fas fa-shopping-bag text-xl group-hover:scale-110 transition-transform"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-accent-500 text-white text-xs font-bold rounded-full flex items-center justify-center animate-scale-in">
                        <?= $cartCount > 99 ? '99+' : $cartCount ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <!-- User Menu -->
                <?php if ($isLoggedIn): ?>
                <div class="relative" id="user-menu-container">
                    <button type="button" class="flex items-center space-x-2 p-2 rounded-xl hover:bg-gray-100 transition-colors" id="user-menu-button">
                        <?php if (!empty($user['avatar'])): ?>
                        <img src="<?= upload_url('users/' . e($user['avatar'])) ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary-100">
                        <?php else: ?>
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white font-semibold">
                            <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <span class="hidden lg:block font-medium text-gray-700"><?= e($user['first_name'] ?? 'User') ?></span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 hidden animate-slide-down" id="user-dropdown">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-semibold text-gray-900"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
                            <p class="text-xs text-gray-500"><?= e($user['email'] ?? '') ?></p>
                        </div>
                        <a href="<?= url('profile.php') ?>" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-user w-5 text-gray-400"></i>
                            <span class="ml-3">My Profile</span>
                        </a>
                        <a href="<?= url('orders.php') ?>" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-box w-5 text-gray-400"></i>
                            <span class="ml-3">My Orders</span>
                        </a>
                        <a href="<?= url('wishlist.php') ?>" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-heart w-5 text-gray-400"></i>
                            <span class="ml-3">Wishlist</span>
                        </a>
                        <hr class="my-2 border-gray-100">
                        <a href="<?= url('logout.php') ?>" class="flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt w-5"></i>
                            <span class="ml-3">Logout</span>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex items-center space-x-2">
                    <a href="<?= url('login.php') ?>" class="hidden md:block px-4 py-2 text-gray-700 font-medium hover:text-primary-600 transition-colors">
                        Login
                    </a>
                    <a href="<?= url('signup.php') ?>" class="px-4 py-2 bg-primary-600 text-white font-medium rounded-xl hover:bg-primary-700 transition-all duration-300 hover:shadow-lg hover:shadow-primary-500/25">
                        Sign Up
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Mobile Menu Toggle -->
                <button type="button" class="lg:hidden p-2 text-gray-600 hover:text-primary-600 transition-colors" id="mobile-menu-toggle">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
        
        <!-- Category Navigation (Desktop) -->
        <div class="hidden lg:flex items-center space-x-1 py-3 border-t border-gray-100">
            <a href="<?= url('products.php') ?>" class="px-4 py-2 text-gray-700 font-medium hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                <i class="fas fa-th-large mr-2"></i>All Products
            </a>
            <?php foreach ($navCategories as $cat): ?>
            <a href="<?= url('category.php?slug=' . e($cat['slug'])) ?>" class="px-4 py-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-all">
                <?= e($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <!-- Mobile Search Panel -->
    <div class="lg:hidden bg-white border-t border-gray-100 p-4 hidden" id="mobile-search-panel">
        <form action="<?= url('search.php') ?>" method="GET" class="relative">
            <input 
                type="text" 
                name="q" 
                placeholder="Search products..." 
                class="w-full pl-10 pr-4 py-3 bg-gray-100 rounded-xl focus:bg-white focus:ring-2 focus:ring-primary-500 outline-none transition"
                value="<?= e(get('q')) ?>"
            >
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </form>
    </div>
</header>

<!-- Mobile Navigation Menu -->
<div class="fixed inset-0 z-50 hidden" id="mobile-menu">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="mobile-menu-overlay"></div>
    <div class="absolute right-0 top-0 bottom-0 w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300" id="mobile-menu-panel">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <span class="text-lg font-bold text-gray-900">Menu</span>
            <button type="button" class="p-2 text-gray-500 hover:text-gray-700" id="mobile-menu-close">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="overflow-y-auto h-full pb-20">
            <!-- Mobile User Section -->
            <?php if ($isLoggedIn): ?>
            <div class="p-4 bg-gradient-to-r from-primary-50 to-accent-50">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white text-lg font-semibold">
                        <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900"><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></p>
                        <p class="text-sm text-gray-500"><?= e($user['email'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-4 bg-gray-50">
                <div class="flex space-x-3">
                    <a href="<?= url('login.php') ?>" class="flex-1 py-2.5 text-center text-primary-600 font-medium border-2 border-primary-600 rounded-xl hover:bg-primary-50 transition">
                        Login
                    </a>
                    <a href="<?= url('signup.php') ?>" class="flex-1 py-2.5 text-center text-white font-medium bg-primary-600 rounded-xl hover:bg-primary-700 transition">
                        Sign Up
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Navigation Links -->
            <nav class="p-4">
                <a href="<?= url() ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                    <i class="fas fa-home w-6 text-gray-400"></i>
                    <span class="ml-3 font-medium">Home</span>
                </a>
                <a href="<?= url('products.php') ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                    <i class="fas fa-th-large w-6 text-gray-400"></i>
                    <span class="ml-3 font-medium">All Products</span>
                </a>
                <?php if ($isLoggedIn): ?>
                <a href="<?= url('orders.php') ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                    <i class="fas fa-box w-6 text-gray-400"></i>
                    <span class="ml-3 font-medium">My Orders</span>
                </a>
                <a href="<?= url('wishlist.php') ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                    <i class="fas fa-heart w-6 text-gray-400"></i>
                    <span class="ml-3 font-medium">Wishlist</span>
                </a>
                <a href="<?= url('profile.php') ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                    <i class="fas fa-user w-6 text-gray-400"></i>
                    <span class="ml-3 font-medium">Profile</span>
                </a>
                <?php endif; ?>
                
                <!-- Categories -->
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Categories</p>
                    <?php foreach ($navCategories as $cat): ?>
                    <a href="<?= url('category.php?slug=' . e($cat['slug'])) ?>" class="flex items-center px-4 py-3 text-gray-600 hover:bg-gray-50 rounded-xl transition">
                        <i class="fas fa-tag w-6 text-gray-400"></i>
                        <span class="ml-3"><?= e($cat['name']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($isLoggedIn): ?>
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="<?= url('logout.php') ?>" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition">
                        <i class="fas fa-sign-out-alt w-6"></i>
                        <span class="ml-3 font-medium">Logout</span>
                    </a>
                </div>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</div>

<!-- Main Content Container -->
<main class="flex-1">
    <!-- Flash Messages -->
    <div class="container mx-auto px-4 pt-4">
        <?= get_flash_messages() ?>
    </div>
