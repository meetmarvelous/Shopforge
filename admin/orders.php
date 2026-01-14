<?php
/**
 * Admin Orders Management
 * 
 * View and manage customer orders.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$orderId = get_int('id', 0);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'update_status') {
        $updateId = get_int('id', 0);
        $newStatus = Security::sanitizeString($_POST['status'] ?? '');
        
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if ($updateId > 0 && in_array($newStatus, $validStatuses)) {
            $db->update('orders', ['status' => $newStatus], 'id = ?', [$updateId]);
            
            // Log activity
            $db->insert('activity_log', [
                'admin_id' => Session::getAdminId(),
                'action' => 'order_status_update',
                'description' => "Updated order #$updateId to $newStatus",
                'ip_address' => Security::getClientIP()
            ]);
            
            flash_success('Order status updated successfully.');
        }
        redirect(url('admin/orders.php?action=view&id=' . $updateId));
    }
}

// Fetch order for view
$order = null;
$orderItems = [];
if ($action === 'view' && $orderId > 0) {
    $order = $db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
    if (!$order) {
        flash_error('Order not found.');
        redirect(url('admin/orders.php'));
    }
    $orderItems = $db->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
}

// Fetch orders list
$page = max(1, get_int('page', 1));
$statusFilter = get('status', '');
$search = get('q', '');

$conditions = ['1=1'];
$params = [];

if (!empty($search)) {
    $conditions[] = '(order_number LIKE ? OR shipping_email LIKE ? OR shipping_first_name LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $conditions[] = 'status = ?';
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $conditions);
$totalOrders = $db->fetchColumn("SELECT COUNT(*) FROM orders WHERE {$whereClause}", $params);
$pagination = paginate($totalOrders, ADMIN_ITEMS_PER_PAGE, $page, current_url());

$orders = [];
if ($action === 'list') {
    $orders = $db->fetchAll(
        "SELECT * FROM orders WHERE {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        array_merge($params, [$pagination['per_page'], $pagination['offset']])
    );
}

$pageTitle = $action === 'view' ? 'Order #' . ($order['order_number'] ?? '') : 'Orders';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Orders List -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <form action="" method="GET" class="flex gap-2">
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search orders..." 
                           class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                    <select name="status" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="text-center py-8 text-gray-500">No orders found</td></tr>
                        <?php else: ?>
                        <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td>
                                <p class="font-semibold text-primary-600">#<?= e($ord['order_number']) ?></p>
                            </td>
                            <td>
                                <p class="font-medium"><?= e($ord['shipping_first_name'] . ' ' . $ord['shipping_last_name']) ?></p>
                                <p class="text-sm text-gray-500"><?= e($ord['shipping_email']) ?></p>
                            </td>
                            <td class="font-semibold"><?= format_price($ord['total_amount']) ?></td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?= $ord['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                    <?= ucfirst($ord['payment_status']) ?>
                                </span>
                            </td>
                            <td>
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
                            </td>
                            <td class="text-sm text-gray-500"><?= format_datetime($ord['created_at']) ?></td>
                            <td class="text-right">
                                <a href="?action=view&id=<?= $ord['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition">
                                    <i class="fas fa-eye"></i>
                                </a>
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
        
        <?php elseif ($action === 'view' && $order): ?>
        <!-- Order Details -->
        <div class="mb-6">
            <a href="orders.php" class="text-gray-500 hover:text-gray-700 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Orders
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Items -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Order Items</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($orderItems as $item): ?>
                        <div class="p-4 flex items-center gap-4">
                            <?php if ($item['product_image']): ?>
                            <img src="<?= upload_url('products/' . e($item['product_image'])) ?>" alt="" class="w-16 h-16 rounded-lg object-cover">
                            <?php else: ?>
                            <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900"><?= e($item['product_name']) ?></p>
                                <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?> × <?= format_price($item['unit_price']) ?></p>
                            </div>
                            <p class="font-semibold"><?= format_price($item['total_price']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-4 bg-gray-50 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span><?= format_price($order['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span><?= $order['shipping_amount'] == 0 ? 'FREE' : format_price($order['shipping_amount']) ?></span>
                        </div>
                        <?php if ($order['tax_amount'] > 0): ?>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span><?= format_price($order['tax_amount']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between pt-2 border-t border-gray-200 font-bold text-gray-900">
                            <span>Total</span>
                            <span><?= format_price($order['total_amount']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Shipping Address</h3>
                    <div class="text-gray-600">
                        <p class="font-medium text-gray-900"><?= e($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?></p>
                        <p><?= e($order['shipping_address']) ?></p>
                        <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_postal_code']) ?></p>
                        <p class="mt-2"><?= e($order['shipping_phone']) ?></p>
                        <p><?= e($order['shipping_email']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Status -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Order Status</h3>
                    <form method="POST" action="?action=update_status&id=<?= $order['id'] ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_status">
                        <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none mb-4">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="w-full py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                            Update Status
                        </button>
                    </form>
                </div>
                
                <!-- Payment Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Payment</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Method</span>
                            <span class="font-medium"><?= e(ucfirst($order['payment_method'] ?? 'Paystack')) ?></span>
                        </div>
                        <?php if ($order['payment_reference']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Reference</span>
                            <span class="font-mono text-xs"><?= e($order['payment_reference']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Order Info</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Order ID</span>
                            <span class="font-medium">#<?= e($order['order_number']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span><?= format_datetime($order['created_at']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
