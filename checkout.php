<?php
/**
 * Checkout Page
 * 
 * Complete order with shipping info and payment.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

require_login();

$db = db();
$userId = Session::getUserId();
$user = Session::getUser();

// Get cart items
$cartItems = $db->fetchAll(
    "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.featured_image, p.stock_quantity, p.sku
     FROM cart c 
     JOIN products p ON c.product_id = p.id 
     WHERE c.user_id = ? AND p.is_active = 1",
    [$userId]
);

if (empty($cartItems)) {
    flash_error('Your cart is empty.');
    redirect(url('cart.php'));
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['sale_price'] && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}

$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : DEFAULT_SHIPPING_COST;
$tax = TAX_ENABLED ? ($subtotal * (TAX_RATE / 100)) : 0;
$discount = 0;
$total = $subtotal + $shipping + $tax - $discount;

// Get user's default address
$defaultAddress = $db->fetchOne(
    "SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1",
    [$userId]
);

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    // Collect shipping info
    $shippingData = [
        'first_name' => Security::sanitizeString($_POST['first_name'] ?? ''),
        'last_name' => Security::sanitizeString($_POST['last_name'] ?? ''),
        'email' => Security::sanitizeEmail($_POST['email'] ?? ''),
        'phone' => Security::sanitizeString($_POST['phone'] ?? ''),
        'address' => Security::sanitizeString($_POST['address'] ?? ''),
        'city' => Security::sanitizeString($_POST['city'] ?? ''),
        'state' => Security::sanitizeString($_POST['state'] ?? ''),
        'postal_code' => Security::sanitizeString($_POST['postal_code'] ?? ''),
        'notes' => Security::sanitizeString($_POST['notes'] ?? '')
    ];
    
    // Validation
    if (empty($shippingData['first_name'])) $errors['first_name'] = 'First name is required';
    if (empty($shippingData['last_name'])) $errors['last_name'] = 'Last name is required';
    if (empty($shippingData['email'])) $errors['email'] = 'Email is required';
    if (empty($shippingData['phone'])) $errors['phone'] = 'Phone number is required';
    if (empty($shippingData['address'])) $errors['address'] = 'Address is required';
    if (empty($shippingData['city'])) $errors['city'] = 'City is required';
    if (empty($shippingData['state'])) $errors['state'] = 'State is required';
    
    if (empty($errors)) {
        // Verify stock availability
        $stockError = false;
        foreach ($cartItems as $item) {
            $currentStock = $db->fetchColumn("SELECT stock_quantity FROM products WHERE id = ?", [$item['product_id']]);
            if ($item['quantity'] > $currentStock) {
                $stockError = true;
                flash_error($item['name'] . ' has only ' . $currentStock . ' in stock.');
            }
        }
        
        if (!$stockError) {
            try {
                $db->beginTransaction();
                
                // Create order
                $orderNumber = Security::generateOrderNumber();
                $orderId = $db->insert('orders', [
                    'user_id' => $userId,
                    'order_number' => $orderNumber,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => $subtotal,
                    'tax_amount' => $tax,
                    'shipping_amount' => $shipping,
                    'discount_amount' => $discount,
                    'total_amount' => $total,
                    'shipping_first_name' => $shippingData['first_name'],
                    'shipping_last_name' => $shippingData['last_name'],
                    'shipping_email' => $shippingData['email'],
                    'shipping_phone' => $shippingData['phone'],
                    'shipping_address' => $shippingData['address'],
                    'shipping_city' => $shippingData['city'],
                    'shipping_state' => $shippingData['state'],
                    'shipping_postal_code' => $shippingData['postal_code'],
                    'notes' => $shippingData['notes']
                ]);
                
                // Create order items
                foreach ($cartItems as $item) {
                    $price = ($item['sale_price'] && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price'];
                    $db->insert('order_items', [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'product_sku' => $item['sku'],
                        'product_image' => $item['featured_image'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $price,
                        'total_price' => $price * $item['quantity']
                    ]);
                    
                    // Reduce stock
                    $db->query(
                        "UPDATE products SET stock_quantity = stock_quantity - ?, sales_count = sales_count + ? WHERE id = ?",
                        [$item['quantity'], $item['quantity'], $item['product_id']]
                    );
                }
                
                // Clear cart
                $db->delete('cart', 'user_id = ?', [$userId]);
                
                $db->commit();
                
                // Store order ID for payment
                Session::set('pending_order_id', $orderId);
                Session::set('pending_order_number', $orderNumber);
                Session::set('pending_order_total', $total);
                Session::set('pending_order_email', $shippingData['email']);
                
                // Redirect to payment
                redirect(url('payment.php?order=' . $orderNumber));
                
            } catch (Exception $e) {
                $db->rollback();
                flash_error('An error occurred. Please try again.');
                if (APP_DEBUG) flash_error($e->getMessage());
            }
        }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <?= breadcrumbs(['Home' => url(), 'Cart' => url('cart.php'), 'Checkout' => '']) ?>
    
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
    
    <form method="POST" action="" id="checkout-form">
        <?= csrf_field() ?>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Shipping Form -->
            <div class="flex-1">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 lg:p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-truck text-primary-500 mr-3"></i>
                        Shipping Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?= e($defaultAddress['first_name'] ?? $user['first_name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border <?= isset($errors['first_name']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                            <?php if (isset($errors['first_name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?= $errors['first_name'] ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?= e($defaultAddress['last_name'] ?? $user['last_name'] ?? '') ?>"
                                   class="w-full px-4 py-3 border <?= isset($errors['last_name']) ? 'border-red-300' : 'border-gray-200' ?> rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?= e($user['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required
                                   value="<?= e($defaultAddress['phone'] ?? $user['phone'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Street Address *</label>
                            <input type="text" id="address" name="address" required
                                   value="<?= e($defaultAddress['address_line1'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition"
                                   placeholder="House number, street name">
                        </div>
                        
                        <!-- City -->
                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                            <input type="text" id="city" name="city" required
                                   value="<?= e($defaultAddress['city'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- State -->
                        <div>
                            <label for="state" class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                            <input type="text" id="state" name="state" required
                                   value="<?= e($defaultAddress['state'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- Postal Code -->
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code"
                                   value="<?= e($defaultAddress['postal_code'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                        </div>
                        
                        <!-- Order Notes -->
                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Order Notes (Optional)</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition resize-none"
                                      placeholder="Special instructions for delivery..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:w-96">
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-28">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>
                    
                    <!-- Cart Items Preview -->
                    <div class="space-y-4 mb-6 max-h-64 overflow-y-auto">
                        <?php foreach ($cartItems as $item): ?>
                        <?php $price = ($item['sale_price'] && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price']; ?>
                        <div class="flex items-center gap-3">
                            <div class="relative flex-shrink-0">
                                <?php if ($item['featured_image']): ?>
                                <img src="<?= upload_url('products/' . e($item['featured_image'])) ?>" alt="<?= e($item['name']) ?>" 
                                     class="w-16 h-16 object-cover rounded-xl">
                                <?php else: ?>
                                <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <?php endif; ?>
                                <span class="absolute -top-2 -right-2 w-5 h-5 bg-primary-600 text-white text-xs font-bold rounded-full flex items-center justify-center">
                                    <?= $item['quantity'] ?>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate"><?= e($item['name']) ?></p>
                                <p class="text-sm text-gray-500"><?= format_price($price) ?> × <?= $item['quantity'] ?></p>
                            </div>
                            <span class="font-semibold text-gray-900"><?= format_price($price * $item['quantity']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Totals -->
                    <div class="space-y-3 py-4 border-t border-gray-100">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-semibold text-gray-900"><?= format_price($subtotal) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <?php if ($shipping == 0): ?>
                            <span class="font-semibold text-green-600">FREE</span>
                            <?php else: ?>
                            <span class="font-semibold text-gray-900"><?= format_price($shipping) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (TAX_ENABLED): ?>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax (<?= TAX_RATE ?>%)</span>
                            <span class="font-semibold text-gray-900"><?= format_price($tax) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Total -->
                    <div class="flex justify-between items-center py-4 border-t border-gray-100 mb-6">
                        <span class="text-lg font-semibold text-gray-900">Total</span>
                        <span class="text-2xl font-bold text-primary-600"><?= format_price($total) ?></span>
                    </div>
                    
                    <!-- Place Order Button -->
                    <button type="submit" 
                            class="w-full py-4 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-lock"></i>
                        Proceed to Payment
                    </button>
                    
                    <!-- Security Info -->
                    <p class="mt-4 text-center text-sm text-gray-500">
                        <i class="fas fa-shield-alt text-green-600 mr-1"></i>
                        Your payment information is secure and encrypted
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
