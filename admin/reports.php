<?php
/**
 * Admin Reports Page
 * 
 * Sales analytics and business reports.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();

// Date range
$startDate = get('start_date', date('Y-m-01')); // First day of current month
$endDate = get('end_date', date('Y-m-d'));

// Sales summary
$salesSummary = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue
     FROM orders 
     WHERE DATE(created_at) BETWEEN ? AND ?",
    [$startDate, $endDate]
);

// Daily revenue chart
$dailyRevenue = $db->fetchAll(
    "SELECT DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders
     FROM orders 
     WHERE payment_status = 'paid' AND DATE(created_at) BETWEEN ? AND ?
     GROUP BY DATE(created_at)
     ORDER BY date",
    [$startDate, $endDate]
);

// Top selling products
$topProducts = $db->fetchAll(
    "SELECT p.id, p.name, p.featured_image, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as total_revenue
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY p.id
     ORDER BY total_sold DESC
     LIMIT 10",
    [$startDate, $endDate]
);

// Top categories
$topCategories = $db->fetchAll(
    "SELECT c.name, SUM(oi.quantity) as total_sold, SUM(oi.total_price) as total_revenue
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     JOIN categories c ON p.category_id = c.id
     JOIN orders o ON oi.order_id = o.id
     WHERE o.payment_status = 'paid' AND DATE(o.created_at) BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY total_revenue DESC
     LIMIT 5",
    [$startDate, $endDate]
);

// Order status breakdown
$statusBreakdown = $db->fetchAll(
    "SELECT status, COUNT(*) as count 
     FROM orders 
     WHERE DATE(created_at) BETWEEN ? AND ?
     GROUP BY status",
    [$startDate, $endDate]
);

// New customers in period
$newCustomers = $db->fetchColumn(
    "SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN ? AND ?",
    [$startDate, $endDate]
);

$pageTitle = 'Reports';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <!-- Date Range Filter -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <form action="" method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">From:</label>
                    <input type="date" name="start_date" value="<?= e($startDate) ?>"
                           class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700">To:</label>
                    <input type="date" name="end_date" value="<?= e($endDate) ?>"
                           class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                    Apply
                </button>
                <div class="flex gap-2">
                    <a href="?start_date=<?= date('Y-m-d', strtotime('-7 days')) ?>&end_date=<?= date('Y-m-d') ?>" 
                       class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">Last 7 Days</a>
                    <a href="?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-d') ?>" 
                       class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">This Month</a>
                    <a href="?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-m-d') ?>" 
                       class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm transition">This Year</a>
                </div>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900"><?= format_price($salesSummary['total_revenue'] ?? 0) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900"><?= number_format($salesSummary['total_orders'] ?? 0) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Avg. Order Value</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900"><?= format_price($salesSummary['avg_order_value'] ?? 0) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">New Customers</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900"><?= number_format($newCustomers) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-plus text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Daily Revenue Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Revenue</h3>
                <canvas id="dailyRevenueChart" height="150"></canvas>
            </div>
            
            <!-- Order Status -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Status</h3>
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Products -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Top Selling Products</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($topProducts)): ?>
                    <div class="p-6 text-center text-gray-500">No data available</div>
                    <?php else: ?>
                    <?php foreach ($topProducts as $prod): ?>
                    <div class="p-4 flex items-center gap-4">
                        <?php if ($prod['featured_image']): ?>
                        <img src="<?= upload_url('products/' . e($prod['featured_image'])) ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                        <?php else: ?>
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-gray-400"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate"><?= e($prod['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= number_format($prod['total_sold']) ?> sold</p>
                        </div>
                        <p class="font-semibold text-gray-900"><?= format_price($prod['total_revenue']) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Top Categories -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Revenue by Category</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($topCategories)): ?>
                    <div class="p-6 text-center text-gray-500">No data available</div>
                    <?php else: ?>
                    <?php foreach ($topCategories as $cat): ?>
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900"><?= e($cat['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= number_format($cat['total_sold']) ?> items sold</p>
                        </div>
                        <p class="font-semibold text-gray-900"><?= format_price($cat['total_revenue']) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php
$extraScripts = <<<SCRIPTS
<script>
// Daily Revenue Chart
const dailyCtx = document.getElementById('dailyRevenueChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($r) => date('M d', strtotime($r['date'])), $dailyRevenue)) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_map(fn($r) => (float)$r['revenue'], $dailyRevenue)) ?>,
            backgroundColor: 'rgba(99, 102, 241, 0.8)',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { callback: function(value) { return '₦' + value.toLocaleString(); } }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($s) => ucfirst($s['status']), $statusBreakdown)) ?>,
        datasets: [{
            data: <?= json_encode(array_map(fn($s) => (int)$s['count'], $statusBreakdown)) ?>,
            backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
SCRIPTS;
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
