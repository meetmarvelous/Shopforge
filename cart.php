<?php
/**
 * Shopping Cart Page
 * 
 * View and manage cart items.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$db = db();

// Get cart items
if (Session::isLoggedIn()) {
    $cartItems = $db->fetchAll(
        "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.featured_image, p.stock_quantity 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.user_id = ? AND p.is_active = 1 
         ORDER BY c.created_at DESC",
        [Session::getUserId()]
    );
} else {
    $cartItems = $db->fetchAll(
        "SELECT c.*, p.name, p.slug, p.price, p.sale_price, p.featured_image, p.stock_quantity 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.session_id = ? AND c.user_id IS NULL AND p.is_active = 1 
         ORDER BY c.created_at DESC",
        [session_id()]
    );
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $price = ($item['sale_price'] && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}

$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : DEFAULT_SHIPPING_COST;
$tax = TAX_ENABLED ? ($subtotal * (TAX_RATE / 100)) : 0;
$total = $subtotal + $shipping + $tax;

$pageTitle = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <?= breadcrumbs(['Home' => url(), 'Shopping Cart' => '']) ?>
    
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
    
    <?php if (empty($cartItems)): ?>
    <!-- Empty Cart -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
            <i class="fas fa-shopping-cart text-gray-400 text-4xl"></i>
        </div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-3">Your cart is empty</h2>
        <p class="text-gray-600 mb-6">Looks like you haven't added anything to your cart yet.</p>
        <a href="<?= url('products.php') ?>" class="inline-flex items-center px-8 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
            <i class="fas fa-shopping-bag mr-2"></i>
            Start Shopping
        </a>
    </div>
    
    <?php else: ?>
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Cart Items -->
        <div class="flex-1">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-gray-100 hidden md:grid md:grid-cols-12 gap-4 text-sm font-semibold text-gray-500 uppercase">
                    <div class="col-span-6">Product</div>
                    <div class="col-span-2 text-center">Price</div>
                    <div class="col-span-2 text-center">Quantity</div>
                    <div class="col-span-2 text-right">Total</div>
                </div>
                
                <!-- Items -->
                <div id="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                    <?php 
                    $price = ($item['sale_price'] && $item['sale_price'] < $item['price']) ? $item['sale_price'] : $item['price'];
                    $itemTotal = $price * $item['quantity'];
                    ?>
                    <div class="p-6 border-b border-gray-100 last:border-0 cart-item" data-id="<?= $item['id'] ?>">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                            <!-- Product Info -->
                            <div class="col-span-6 flex items-center gap-4">
                                <a href="<?= url('product.php?slug=' . e($item['slug'])) ?>" class="flex-shrink-0">
                                    <?php if ($item['featured_image']): ?>
                                    <img src="<?= upload_url('products/' . e($item['featured_image'])) ?>" alt="<?= e($item['name']) ?>" 
                                         class="w-20 h-20 md:w-24 md:h-24 object-cover rounded-xl">
                                    <?php else: ?>
                                    <div class="w-20 h-20 md:w-24 md:h-24 bg-gray-100 rounded-xl flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                </a>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 hover:text-primary-600 transition">
                                        <a href="<?= url('product.php?slug=' . e($item['slug'])) ?>"><?= e($item['name']) ?></a>
                                    </h3>
                                    <?php if ($item['stock_quantity'] <= 5): ?>
                                    <span class="text-xs text-orange-600">Only <?= $item['stock_quantity'] ?> left in stock</span>
                                    <?php endif; ?>
                                    <button onclick="removeFromCart(<?= $item['id'] ?>)" 
                                            class="mt-2 text-sm text-red-500 hover:text-red-700 transition md:hidden">
                                        <i class="fas fa-trash mr-1"></i> Remove
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Price -->
                            <div class="col-span-2 text-center">
                                <span class="font-semibold text-gray-900"><?= format_price($price) ?></span>
                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                <span class="block text-sm text-gray-400 line-through"><?= format_price($item['price']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Quantity -->
                            <div class="col-span-2 flex justify-center">
                                <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                                    <button onclick="updateCartQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)" 
                                            class="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition"
                                            <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                        <i class="fas fa-minus text-sm"></i>
                                    </button>
                                    <span class="w-12 text-center font-semibold cart-qty"><?= $item['quantity'] ?></span>
                                    <button onclick="updateCartQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)" 
                                            class="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition"
                                            <?= $item['quantity'] >= $item['stock_quantity'] ? 'disabled' : '' ?>>
                                        <i class="fas fa-plus text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Total -->
                            <div class="col-span-2 text-right flex items-center justify-end gap-4">
                                <span class="font-bold text-gray-900 text-lg item-total"><?= format_price($itemTotal) ?></span>
                                <button onclick="removeFromCart(<?= $item['id'] ?>)" 
                                        class="hidden md:block text-gray-400 hover:text-red-500 transition" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Actions -->
                <div class="p-6 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <a href="<?= url('products.php') ?>" class="text-primary-600 font-medium hover:text-primary-700 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Continue Shopping
                    </a>
                    <button onclick="clearCart()" class="text-red-500 font-medium hover:text-red-700 transition">
                        <i class="fas fa-trash mr-2"></i>
                        Clear Cart
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:w-96">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 sticky top-28">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Order Summary</h2>
                
                <!-- Summary Lines -->
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-semibold text-gray-900" id="cart-subtotal"><?= format_price($subtotal) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping</span>
                        <?php if ($shipping == 0): ?>
                        <span class="font-semibold text-green-600">FREE</span>
                        <?php else: ?>
                        <span class="font-semibold text-gray-900" id="cart-shipping"><?= format_price($shipping) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (TAX_ENABLED): ?>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax (<?= TAX_RATE ?>%)</span>
                        <span class="font-semibold text-gray-900" id="cart-tax"><?= format_price($tax) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Free Shipping Progress -->
                    <?php if ($subtotal < FREE_SHIPPING_THRESHOLD): ?>
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Free shipping progress</span>
                            <span><?= format_price(FREE_SHIPPING_THRESHOLD - $subtotal) ?> more to go</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-primary-500 to-accent-500 h-2 rounded-full transition-all" 
                                 style="width: <?= min(100, ($subtotal / FREE_SHIPPING_THRESHOLD) * 100) ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Total -->
                <div class="flex justify-between items-center py-4 border-t border-gray-100 mb-6">
                    <span class="text-lg font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-primary-600" id="cart-total"><?= format_price($total) ?></span>
                </div>
                
                <!-- Coupon -->
                <div class="mb-6">
                    <div class="flex gap-2">
                        <input type="text" id="coupon-code" placeholder="Coupon code" 
                               class="flex-1 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <button onclick="applyCoupon()" 
                                class="px-4 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition">
                            Apply
                        </button>
                    </div>
                </div>
                
                <!-- Checkout Button -->
                <a href="<?= url('checkout.php') ?>" 
                   class="block w-full py-4 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-bold text-center rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300">
                    <i class="fas fa-lock mr-2"></i>
                    Proceed to Checkout
                </a>
                
                <!-- Security Badges -->
                <div class="mt-6 flex items-center justify-center gap-4 text-gray-400 text-sm">
                    <span><i class="fas fa-shield-alt mr-1"></i> Secure</span>
                    <span><i class="fas fa-lock mr-1"></i> Encrypted</span>
                    <span><i class="fas fa-undo mr-1"></i> Easy Returns</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updateCartQuantity(cartId, newQuantity) {
    if (newQuantity < 1) {
        removeFromCart(cartId);
        return;
    }
    
    fetch('<?= url('ajax/cart.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=update&cart_id=${cartId}&quantity=${newQuantity}&<?= CSRF_TOKEN_NAME ?>=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showNotification('error', data.error || 'Failed to update cart.');
        }
    });
}

function removeFromCart(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    
    fetch('<?= url('ajax/cart.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=remove&cart_id=${cartId}&<?= CSRF_TOKEN_NAME ?>=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showNotification('error', data.error || 'Failed to remove item.');
        }
    });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your cart?')) return;
    
    fetch('<?= url('ajax/cart.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=clear&<?= CSRF_TOKEN_NAME ?>=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showNotification('error', data.error || 'Failed to clear cart.');
        }
    });
}

function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    if (!code) {
        showNotification('warning', 'Please enter a coupon code.');
        return;
    }
    
    // Store coupon and redirect to checkout where it will be applied
    sessionStorage.setItem('coupon_code', code);
    showNotification('info', 'Coupon will be applied at checkout.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
