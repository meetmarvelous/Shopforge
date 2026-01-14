<?php
/**
 * Admin Top Navigation Bar
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../../config.php';

$admin = Session::getAdmin();
?>

<!-- Top Bar -->
<header class="sticky top-0 z-20 bg-white border-b border-gray-200">
    <div class="flex items-center justify-between h-16 px-6">
        <!-- Page Title / Breadcrumb -->
        <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900"><?= e($pageTitle ?? 'Dashboard') ?></h1>
        </div>
        
        <!-- Right Side -->
        <div class="flex items-center space-x-4">
            <!-- Search -->
            <div class="hidden md:block relative">
                <form action="search.php" method="GET">
                    <input type="text" name="q" placeholder="Search..." 
                           class="w-64 pl-10 pr-4 py-2 bg-gray-100 border border-transparent rounded-xl focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition"
                           value="<?= e(get('q')) ?>">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Notifications -->
            <div class="relative" id="notifications-container">
                <button type="button" class="relative p-2 text-gray-500 hover:text-gray-700 transition" id="notifications-btn">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full" id="notification-dot"></span>
                </button>
                
                <!-- Notifications Dropdown -->
                <div class="absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 hidden" id="notifications-dropdown">
                    <div class="p-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Notifications</h3>
                    </div>
                    <div class="max-h-64 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500 text-sm">
                            No new notifications
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="relative" id="quick-actions-container">
                <button type="button" class="p-2 text-gray-500 hover:text-gray-700 transition" id="quick-actions-btn">
                    <i class="fas fa-plus-circle text-xl"></i>
                </button>
                
                <!-- Quick Actions Dropdown -->
                <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden" id="quick-actions-dropdown">
                    <a href="products.php?action=add" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-box w-5 text-gray-400"></i>
                        <span class="ml-3">Add Product</span>
                    </a>
                    <a href="categories.php?action=add" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-tag w-5 text-gray-400"></i>
                        <span class="ml-3">Add Category</span>
                    </a>
                    <a href="coupons.php?action=add" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-ticket-alt w-5 text-gray-400"></i>
                        <span class="ml-3">Create Coupon</span>
                    </a>
                </div>
            </div>
            
            <!-- User Menu -->
            <div class="relative" id="user-menu-container">
                <button type="button" class="flex items-center space-x-3 p-2 rounded-xl hover:bg-gray-100 transition" id="user-menu-btn">
                    <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-purple-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        <?= strtoupper(substr($admin['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span class="hidden md:block text-sm font-medium text-gray-700"><?= e($admin['username'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                </button>
                
                <!-- User Dropdown -->
                <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 hidden" id="user-menu-dropdown">
                    <a href="profile.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-user w-5 text-gray-400"></i>
                        <span class="ml-3">Profile</span>
                    </a>
                    <a href="settings.php" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-cog w-5 text-gray-400"></i>
                        <span class="ml-3">Settings</span>
                    </a>
                    <hr class="my-2 border-gray-100">
                    <a href="logout.php" class="flex items-center px-4 py-2 text-red-600 hover:bg-red-50 transition">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="ml-3">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Flash Messages -->
<div class="px-6 pt-4">
    <?= get_flash_messages() ?>
</div>

<script>
// Dropdown toggles
function setupDropdown(buttonId, dropdownId) {
    const button = document.getElementById(buttonId);
    const dropdown = document.getElementById(dropdownId);
    
    if (button && dropdown) {
        button.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
        });
    }
}

setupDropdown('notifications-btn', 'notifications-dropdown');
setupDropdown('quick-actions-btn', 'quick-actions-dropdown');
setupDropdown('user-menu-btn', 'user-menu-dropdown');

// Close dropdowns when clicking outside
document.addEventListener('click', () => {
    document.querySelectorAll('#notifications-dropdown, #quick-actions-dropdown, #user-menu-dropdown').forEach(el => {
        el.classList.add('hidden');
    });
});
</script>
