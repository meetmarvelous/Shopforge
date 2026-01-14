<?php
/**
 * Admin Sidebar Navigation
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../../config.php';

$admin = Session::getAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Navigation items
$navItems = [
    ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard', 'href' => 'index.php', 'page' => 'index'],
    ['icon' => 'fa-box', 'label' => 'Products', 'href' => 'products.php', 'page' => 'products', 'badge' => null],
    ['icon' => 'fa-tags', 'label' => 'Categories', 'href' => 'categories.php', 'page' => 'categories'],
    ['icon' => 'fa-shopping-cart', 'label' => 'Orders', 'href' => 'orders.php', 'page' => 'orders', 'badge' => 'pending_orders'],
    ['icon' => 'fa-users', 'label' => 'Customers', 'href' => 'customers.php', 'page' => 'customers'],
    ['icon' => 'fa-ticket-alt', 'label' => 'Coupons', 'href' => 'coupons.php', 'page' => 'coupons'],
    ['icon' => 'fa-images', 'label' => 'Banners', 'href' => 'banners.php', 'page' => 'banners'],
    ['icon' => 'fa-star', 'label' => 'Reviews', 'href' => 'reviews.php', 'page' => 'reviews'],
    ['icon' => 'fa-chart-bar', 'label' => 'Reports', 'href' => 'reports.php', 'page' => 'reports'],
    ['icon' => 'fa-cog', 'label' => 'Settings', 'href' => 'settings.php', 'page' => 'settings'],
];

// Get pending orders count
$db = db();
$pendingOrders = $db->fetchColumn("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
?>

<!-- Sidebar -->
<aside class="fixed left-0 top-0 bottom-0 w-64 bg-sidebar-bg z-40 flex flex-col transition-transform duration-300 lg:translate-x-0" id="admin-sidebar">
    <!-- Logo -->
    <div class="p-6 border-b border-white/10">
        <a href="<?= url('admin/') ?>" class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-500 rounded-xl flex items-center justify-center">
                <i class="fas fa-store text-white"></i>
            </div>
            <div>
                <span class="text-xl font-bold text-white"><?= e(SITE_NAME) ?></span>
                <span class="block text-xs text-white/50">Admin Panel</span>
            </div>
        </a>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto">
        <ul class="space-y-1">
            <?php foreach ($navItems as $item): ?>
            <li>
                <a href="<?= $item['href'] ?>" 
                   class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                    <?php if (isset($item['badge']) && $item['badge'] === 'pending_orders' && $pendingOrders > 0): ?>
                    <span class="ml-auto px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full">
                        <?= $pendingOrders ?>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        
        <!-- Divider -->
        <hr class="my-6 border-white/10">
        
        <!-- Quick Links -->
        <ul class="space-y-1">
            <li>
                <a href="<?= url() ?>" target="_blank" class="sidebar-link">
                    <i class="fas fa-external-link-alt"></i>
                    <span>View Store</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <!-- User Section -->
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center space-x-3 p-3 rounded-xl bg-white/5">
            <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                <?= strtoupper(substr($admin['username'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate"><?= e($admin['username'] ?? 'Admin') ?></p>
                <p class="text-xs text-white/50 capitalize"><?= e($admin['role'] ?? 'admin') ?></p>
            </div>
            <a href="logout.php" class="text-white/50 hover:text-white transition" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Toggle -->
<button type="button" class="lg:hidden fixed bottom-4 right-4 z-50 w-14 h-14 bg-primary-600 text-white rounded-full shadow-lg flex items-center justify-center" id="sidebar-toggle">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Overlay -->
<div class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden" id="sidebar-overlay"></div>

<script>
// Sidebar toggle for mobile
const sidebar = document.getElementById('admin-sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebarOverlay = document.getElementById('sidebar-overlay');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    });
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
    });
}

// Hide sidebar by default on mobile
if (window.innerWidth < 1024) {
    sidebar.classList.add('-translate-x-full');
}
</script>
