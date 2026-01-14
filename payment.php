<?php
/**
 * Payment Page
 * 
 * Paystack payment integration.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

require_login();

$orderId = Session::get('pending_order_id');
$orderNumber = get('order') ?: Session::get('pending_order_number');

if (!$orderId || !$orderNumber) {
    flash_error('Invalid order. Please try again.');
    redirect(url('cart.php'));
}

$db = db();
$order = $db->fetchOne(
    "SELECT * FROM orders WHERE id = ? AND order_number = ? AND user_id = ? AND payment_status = 'pending'",
    [$orderId, $orderNumber, Session::getUserId()]
);

if (!$order) {
    flash_error('Order not found or already paid.');
    redirect(url('orders.php'));
}

// Generate Paystack reference
$reference = 'SF_' . $orderId . '_' . time();

// Store reference in session
Session::set('payment_reference', $reference);

$pageTitle = 'Complete Payment';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 text-center">
            <!-- Order Info -->
            <div class="mb-8">
                <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-primary-100 to-accent-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-credit-card text-primary-600 text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Complete Your Payment</h1>
                <p class="text-gray-600">Order #<?= e($orderNumber) ?></p>
            </div>
            
            <!-- Order Summary -->
            <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold"><?= format_price($order['subtotal']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-semibold"><?= $order['shipping_amount'] == 0 ? 'FREE' : format_price($order['shipping_amount']) ?></span>
                    </div>
                    <?php if ($order['tax_amount'] > 0): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-semibold"><?= format_price($order['tax_amount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="pt-3 border-t border-gray-200 flex justify-between">
                        <span class="text-lg font-semibold text-gray-900">Total</span>
                        <span class="text-xl font-bold text-primary-600"><?= format_price($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Button -->
            <button type="button" id="paystack-btn" 
                    class="w-full py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-green-500/30 transition-all duration-300 flex items-center justify-center gap-2">
                <i class="fas fa-lock"></i>
                Pay <?= format_price($order['total_amount']) ?>
            </button>
            
            <!-- Security Notice -->
            <div class="mt-6 flex items-center justify-center gap-4 text-gray-400 text-sm">
                <span><i class="fas fa-shield-alt mr-1"></i> Secure</span>
                <span><i class="fas fa-lock mr-1"></i> SSL Encrypted</span>
            </div>
            
            <!-- Powered by Paystack -->
            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-400">Powered by</p>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="text-lg font-bold text-blue-500">Paystack</span>
                </div>
            </div>
        </div>
        
        <!-- Cancel Link -->
        <div class="text-center mt-6">
            <a href="<?= url('cancel-order.php?order=' . e($orderNumber)) ?>" class="text-gray-500 hover:text-red-600 transition">
                Cancel Order
            </a>
        </div>
    </div>
</div>

<!-- Paystack Inline Script -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('paystack-btn').addEventListener('click', function() {
    const handler = PaystackPop.setup({
        key: '<?= PAYSTACK_PUBLIC_KEY ?>',
        email: '<?= e($order['shipping_email']) ?>',
        amount: <?= $order['total_amount'] * 100 ?>, // Amount in kobo
        currency: '<?= CURRENCY_CODE ?>',
        ref: '<?= $reference ?>',
        metadata: {
            order_id: <?= $order['id'] ?>,
            order_number: '<?= e($orderNumber) ?>',
            custom_fields: [
                {
                    display_name: "Order Number",
                    variable_name: "order_number",
                    value: '<?= e($orderNumber) ?>'
                }
            ]
        },
        callback: function(response) {
            // Redirect to callback URL
            window.location.href = '<?= url('payment-callback.php') ?>?reference=' + response.reference;
        },
        onClose: function() {
            showNotification('warning', 'Payment window closed. You can try again or cancel the order.');
        }
    });
    handler.openIframe();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
