<?php
/**
 * Admin Dashboard
 * 
 * Main admin dashboard with analytics and key metrics.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();

// Key Metrics
$totalOrders = $db->fetchColumn("SELECT COUNT(*) FROM orders");
$pendingOrders = $db->fetchColumn("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$totalRevenue = $db->fetchColumn("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid'");
$todayRevenue = $db->fetchColumn("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'paid' AND DATE(created_at) = CURDATE()");

$totalProducts = $db->fetchColumn("SELECT COUNT(*) FROM products WHERE is_active = 1");
$lowStockProducts = $db->fetchColumn("SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_quantity <= low_stock_threshold");

$totalCustomers = $db->fetchColumn("SELECT COUNT(*) FROM users");
$newCustomersToday = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");

// Monthly revenue for chart (last 6 months)
$monthlyRevenue = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue 
     FROM orders 
     WHERE payment_status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month"
);

// Recent orders
$recentOrders = $db->fetchAll(
    "SELECT o.*, CONCAT(o.shipping_first_name, ' ', o.shipping_last_name) as customer_name
     FROM orders o
     ORDER BY o.created_at DESC
     LIMIT 5"
);

// Top selling products
$topProducts = $db->fetchAll(
    "SELECT p.*, c.name as category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.is_active = 1
     ORDER BY p.sales_count DESC
     LIMIT 5"
);

// Order status breakdown
$statusBreakdown = $db->fetchAll(
    "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Revenue -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900"><?= format_price($totalRevenue) ?></p>
                        <p class="mt-1 text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            <?= format_price($todayRevenue) ?> today
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-white text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Orders -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900"><?= number_format($totalOrders) ?></p>
                        <p class="mt-1 text-sm text-orange-600">
                            <i class="fas fa-clock mr-1"></i>
                            <?= $pendingOrders ?> pending
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-white text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Products -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Products</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900"><?= number_format($totalProducts) ?></p>
                        <p class="mt-1 text-sm text-red-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <?= $lowStockProducts ?> low stock
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-box text-white text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Customers -->
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Customers</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900"><?= number_format($totalCustomers) ?></p>
                        <p class="mt-1 text-sm text-blue-600">
                            <i class="fas fa-user-plus mr-1"></i>
                            +<?= $newCustomersToday ?> today
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Overview</h3>
                <canvas id="revenueChart" height="120"></canvas>
            </div>
            
            <!-- Order Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Recent Orders & Top Products -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Orders -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                    <a href="orders.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View All</a>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($recentOrders)): ?>
                    <div class="p-6 text-center text-gray-500">No orders yet</div>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div>
                            <p class="font-medium text-gray-900">#<?= e($order['order_number']) ?></p>
                            <p class="text-sm text-gray-500"><?= e($order['customer_name']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900"><?= format_price($order['total_amount']) ?></p>
                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full 
                                <?php
                                switch ($order['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                                    case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Top Products -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Top Products</h3>
                    <a href="products.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View All</a>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($topProducts)): ?>
                    <div class="p-6 text-center text-gray-500">No products yet</div>
                    <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                    <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition">
                        <?php if ($product['featured_image']): ?>
                        <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" alt="" class="w-12 h-12 rounded-xl object-cover">
                        <?php else: ?>
                        <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-image text-gray-400"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate"><?= e($product['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= e($product['category_name'] ?? 'Uncategorized') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900"><?= $product['sales_count'] ?> sold</p>
                            <p class="text-sm text-gray-500"><?= format_price($product['price']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php
// Prepare chart data
$revenueLabels = json_encode(array_map(fn($r) => date('M Y', strtotime($r['month'] . '-01')), $monthlyRevenue));
$revenueData = json_encode(array_map(fn($r) => (float)$r['revenue'], $monthlyRevenue));
$statusLabels = json_encode(array_map(fn($s) => ucfirst($s['status']), $statusBreakdown));
$statusData = json_encode(array_map(fn($s) => (int)$s['count'], $statusBreakdown));

$extraScripts = <<<SCRIPTS
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: {$revenueLabels},
        datasets: [{
            label: 'Revenue',
            data: {$revenueData},
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₦' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: {$statusLabels},
        datasets: [{
            data: {$statusData},
            backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>
SCRIPTS;
?>

    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>

