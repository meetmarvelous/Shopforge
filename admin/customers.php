<?php
/**
 * Admin Customers Management
 * 
 * View and manage customer accounts.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$customerId = get_int('id', 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'toggle_status') {
        $updateId = get_int('id', 0);
        if ($updateId > 0) {
            $currentStatus = $db->fetchColumn("SELECT is_active FROM users WHERE id = ?", [$updateId]);
            $db->update('users', ['is_active' => $currentStatus ? 0 : 1], 'id = ?', [$updateId]);
            flash_success('Customer status updated.');
        }
        redirect(url('admin/customers.php'));
    }
}

// Fetch customer for view
$customer = null;
$customerOrders = [];
if ($action === 'view' && $customerId > 0) {
    $customer = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$customerId]);
    if (!$customer) {
        flash_error('Customer not found.');
        redirect(url('admin/customers.php'));
    }
    $customerOrders = $db->fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$customerId]);
}

// Fetch customers list
$page = max(1, get_int('page', 1));
$search = get('q', '');

$conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $conditions[] = '(email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = implode(' AND ', $conditions);
$totalCustomers = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE {$whereClause}", $params);
$pagination = paginate($totalCustomers, ADMIN_ITEMS_PER_PAGE, $page, current_url());

$customers = [];
if ($action === 'list') {
    $customers = $db->fetchAll(
        "SELECT u.*, 
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = u.id AND payment_status = 'paid') as total_spent
         FROM users u
         WHERE {$whereClause}
         ORDER BY u.created_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$pagination['per_page'], $pagination['offset']])
    );
}

$pageTitle = $action === 'view' ? 'Customer Details' : 'Customers';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Customers List -->
        <div class="flex items-center justify-between mb-6">
            <form action="" method="GET" class="flex gap-2">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search customers..." 
                       class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Customer</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($customers)): ?>
                        <tr><td colspan="7" class="text-center py-8 text-gray-500">No customers found</td></tr>
                        <?php else: ?>
                        <?php foreach ($customers as $cust): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white font-bold">
                                        <?= strtoupper(substr($cust['first_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= e($cust['first_name'] . ' ' . $cust['last_name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= e($cust['phone'] ?: 'No phone') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($cust['email']) ?></td>
                            <td><?= (int)$cust['order_count'] ?></td>
                            <td class="font-semibold"><?= format_price($cust['total_spent']) ?></td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $cust['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= $cust['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-sm text-gray-500"><?= format_date($cust['created_at']) ?></td>
                            <td class="text-right">
                                <a href="?action=view&id=<?= $cust['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" action="?id=<?= $cust['id'] ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition" title="Toggle Status">
                                        <i class="fas fa-toggle-<?= $cust['is_active'] ? 'on' : 'off' ?>"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="p-4 border-t border-gray-100">
                <?= render_pagination($pagination) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif ($action === 'view' && $customer): ?>
        <!-- Customer Details -->
        <div class="mb-6">
            <a href="customers.php" class="text-gray-500 hover:text-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Customers
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 mx-auto bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                        <?= strtoupper(substr($customer['first_name'], 0, 1)) ?>
                    </div>
                    <h3 class="mt-4 text-xl font-bold text-gray-900"><?= e($customer['first_name'] . ' ' . $customer['last_name']) ?></h3>
                    <p class="text-gray-500"><?= e($customer['email']) ?></p>
                </div>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Phone</span>
                        <span class="font-medium"><?= e($customer['phone'] ?: 'Not provided') ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $customer['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= $customer['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Verified</span>
                        <span><?= $customer['email_verified_at'] ? '<i class="fas fa-check-circle text-green-500"></i>' : '<i class="fas fa-times-circle text-gray-400"></i>' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Joined</span>
                        <span><?= format_date($customer['created_at']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Last Login</span>
                        <span><?= $customer['last_login'] ? format_datetime($customer['last_login']) : 'Never' ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Orders -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Recent Orders</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    <?php if (empty($customerOrders)): ?>
                    <div class="p-6 text-center text-gray-500">No orders yet</div>
                    <?php else: ?>
                    <?php foreach ($customerOrders as $ord): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition">
                        <div>
                            <a href="orders.php?action=view&id=<?= $ord['id'] ?>" class="font-semibold text-primary-600">#<?= e($ord['order_number']) ?></a>
                            <p class="text-sm text-gray-500"><?= format_datetime($ord['created_at']) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold"><?= format_price($ord['total_amount']) ?></p>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php
                                switch ($ord['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-700'; break;
                                    case 'processing': echo 'bg-blue-100 text-blue-700'; break;
                                    case 'shipped': echo 'bg-purple-100 text-purple-700'; break;
                                    case 'delivered': echo 'bg-green-100 text-green-700'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-700'; break;
                                }
                                ?>">
                                <?= ucfirst($ord['status']) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
