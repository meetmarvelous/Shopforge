<?php
/**
 * Order Success Page
 * 
 * Display order confirmation after successful payment.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

require_login();

$orderNumber = get('order', '');

if (empty($orderNumber)) {
    redirect(url('orders.php'));
}

$db = db();
$order = $db->fetchOne(
    "SELECT * FROM orders WHERE order_number = ? AND user_id = ?",
    [$orderNumber, Session::getUserId()]
);

if (!$order) {
    flash_error('Order not found.');
    redirect(url('orders.php'));
}

// Get order items
$orderItems = $db->fetchAll(
    "SELECT * FROM order_items WHERE order_id = ?",
    [$order['id']]
);

$pageTitle = 'Order Confirmed';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Success Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 text-center mb-8">
            <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center shadow-lg shadow-green-500/30 animate-scale-in">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600 mb-6">Thank you for your purchase. Your order has been received.</p>
            
            <div class="inline-flex items-center gap-3 px-6 py-3 bg-gray-50 rounded-2xl">
                <span class="text-gray-600">Order Number:</span>
                <span class="font-bold text-primary-600 text-lg">#<?= e($order['order_number']) ?></span>
            </div>
        </div>
        
        <!-- Order Details -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-900">Order Details</h2>
            </div>
            
            <!-- Items -->
            <div class="p-6 border-b border-gray-100">
                <?php foreach ($orderItems as $item): ?>
                <div class="flex items-center gap-4 py-3 <?= $item !== end($orderItems) ? 'border-b border-gray-100' : '' ?>">
                    <?php if ($item['product_image']): ?>
                    <img src="<?= upload_url('products/' . e($item['product_image'])) ?>" alt="<?= e($item['product_name']) ?>" 
                         class="w-16 h-16 object-cover rounded-xl">
                    <?php else: ?>
                    <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900"><?= e($item['product_name']) ?></p>
                        <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></p>
                    </div>
                    <span class="font-semibold text-gray-900"><?= format_price($item['total_price']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Totals -->
            <div class="p-6 bg-gray-50">
                <div class="space-y-2">
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
                    <div class="pt-3 border-t border-gray-200 flex justify-between">
                        <span class="font-bold text-gray-900">Total Paid</span>
                        <span class="font-bold text-primary-600"><?= format_price($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Shipping Info -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Shipping Address</h2>
            <div class="text-gray-600">
                <p class="font-semibold text-gray-900"><?= e($order['shipping_first_name'] . ' ' . $order['shipping_last_name']) ?></p>
                <p><?= e($order['shipping_address']) ?></p>
                <p><?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?></p>
                <p><?= e($order['shipping_phone']) ?></p>
            </div>
        </div>
        
        <!-- What's Next -->
        <div class="bg-gradient-to-br from-primary-50 to-accent-50 rounded-3xl p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">What's Next?</h2>
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">1</div>
                    <div>
                        <p class="font-semibold text-gray-900">Order Confirmation Email</p>
                        <p class="text-sm text-gray-600">We've sent a confirmation email to <?= e($order['shipping_email']) ?></p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-primary-600 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">2</div>
                    <div>
                        <p class="font-semibold text-gray-900">Processing</p>
                        <p class="text-sm text-gray-600">Your order is being prepared for shipping</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-gray-300 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">3</div>
                    <div>
                        <p class="font-semibold text-gray-900">Shipping</p>
                        <p class="text-sm text-gray-600">You'll receive a tracking number once shipped</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="<?= url('orders.php') ?>" class="flex-1 py-3.5 border-2 border-primary-600 text-primary-600 font-semibold rounded-xl hover:bg-primary-50 transition text-center">
                <i class="fas fa-box mr-2"></i>
                View All Orders
            </a>
            <a href="<?= url('products.php') ?>" class="flex-1 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition text-center">
                <i class="fas fa-shopping-bag mr-2"></i>
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
