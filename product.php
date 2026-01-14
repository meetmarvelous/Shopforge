<?php
/**
 * Product Detail Page
 * 
 * Display single product with images, description, and add to cart.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$db = db();

// Get product by slug or ID
$slug = get('slug', '');
$id = get_int('id', 0);

if (!empty($slug)) {
    $product = $db->fetchOne(
        "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.slug = ? AND p.is_active = 1",
        [$slug]
    );
} elseif ($id > 0) {
    $product = $db->fetchOne(
        "SELECT p.*, c.name as category_name, c.slug as category_slug 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.id = ? AND p.is_active = 1",
        [$id]
    );
} else {
    $product = null;
}

if (!$product) {
    flash_error('Product not found.');
    redirect(url('products.php'));
}

// Increment view count
increment_product_views($product['id']);

// Get additional images
$productImages = $db->fetchAll(
    "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order",
    [$product['id']]
);

// Get related products
$relatedProducts = $db->fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 
     ORDER BY RAND() 
     LIMIT 4",
    [$product['category_id'], $product['id']]
);

// Get product rating
$rating = get_product_rating($product['id']);

// Get reviews
$reviews = $db->fetchAll(
    "SELECT r.*, u.first_name, u.last_name, u.avatar 
     FROM reviews r 
     JOIN users u ON r.user_id = u.id 
     WHERE r.product_id = ? AND r.is_approved = 1 
     ORDER BY r.created_at DESC 
     LIMIT 5",
    [$product['id']]
);

// Determine current price
$currentPrice = $product['sale_price'] && $product['sale_price'] < $product['price'] 
    ? $product['sale_price'] 
    : $product['price'];

$pageTitle = $product['meta_title'] ?: $product['name'];
$pageDescription = $product['meta_description'] ?: str_limit(strip_tags($product['description']), 160);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <?= breadcrumbs([
        'Home' => url(),
        'Products' => url('products.php'),
        $product['category_name'] ?? 'All' => url('category.php?slug=' . ($product['category_slug'] ?? '')),
        $product['name'] => ''
    ]) ?>
    
    <!-- Product Section -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
        <div class="grid lg:grid-cols-2">
            <!-- Product Images -->
            <div class="p-6 lg:p-8 bg-gray-50">
                <!-- Main Image -->
                <div class="relative mb-4 rounded-2xl overflow-hidden bg-white aspect-square" id="main-image-container">
                    <?php if ($product['featured_image']): ?>
                    <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" 
                         alt="<?= e($product['name']) ?>" 
                         class="w-full h-full object-contain"
                         id="main-image">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-gray-300 text-6xl"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                        <span class="px-3 py-1.5 bg-red-500 text-white text-sm font-bold rounded-lg">-<?= $discount ?>% OFF</span>
                        <?php endif; ?>
                        <?php if ($product['is_featured']): ?>
                        <span class="px-3 py-1.5 bg-yellow-500 text-white text-sm font-bold rounded-lg">Featured</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Thumbnail Images -->
                <?php if (!empty($productImages) || $product['featured_image']): ?>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <?php if ($product['featured_image']): ?>
                    <button onclick="changeMainImage('<?= upload_url('products/' . e($product['featured_image'])) ?>')"
                            class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-primary-500 bg-white">
                        <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" 
                             alt="<?= e($product['name']) ?>" 
                             class="w-full h-full object-cover">
                    </button>
                    <?php endif; ?>
                    <?php foreach ($productImages as $img): ?>
                    <button onclick="changeMainImage('<?= upload_url('products/' . e($img['image_path'])) ?>')"
                            class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-primary-500 transition bg-white">
                        <img src="<?= upload_url('products/' . e($img['image_path'])) ?>" 
                             alt="<?= e($img['alt_text'] ?? $product['name']) ?>" 
                             class="w-full h-full object-cover">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="p-6 lg:p-8">
                <!-- Category -->
                <a href="<?= url('category.php?slug=' . e($product['category_slug'] ?? '')) ?>" 
                   class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700 mb-3">
                    <i class="fas fa-tag mr-2"></i>
                    <?= e($product['category_name'] ?? 'Uncategorized') ?>
                </a>
                
                <!-- Title -->
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-4"><?= e($product['name']) ?></h1>
                
                <!-- Rating -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex items-center">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= round($rating['average']) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-gray-600"><?= $rating['average'] ?> (<?= $rating['count'] ?> reviews)</span>
                </div>
                
                <!-- Price -->
                <div class="mb-6">
                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <div class="flex items-center gap-4">
                        <span class="text-4xl font-bold text-primary-600"><?= format_price($product['sale_price']) ?></span>
                        <span class="text-xl text-gray-400 line-through"><?= format_price($product['price']) ?></span>
                    </div>
                    <?php else: ?>
                    <span class="text-4xl font-bold text-gray-900"><?= format_price($product['price']) ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Short Description -->
                <?php if ($product['short_description']): ?>
                <p class="text-gray-600 mb-6"><?= e($product['short_description']) ?></p>
                <?php endif; ?>
                
                <!-- Stock Status -->
                <div class="mb-6">
                    <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="flex items-center text-green-600">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-medium">In Stock</span>
                        <span class="text-gray-500 ml-2">(<?= $product['stock_quantity'] ?> available)</span>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center text-red-600">
                        <i class="fas fa-times-circle mr-2"></i>
                        <span class="font-medium">Out of Stock</span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quantity & Add to Cart -->
                <?php if ($product['stock_quantity'] > 0): ?>
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <!-- Quantity Selector -->
                    <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                        <button type="button" onclick="decreaseQuantity()" 
                                class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" 
                               class="w-16 h-12 text-center border-x border-gray-200 font-semibold outline-none">
                        <button type="button" onclick="increaseQuantity(<?= $product['stock_quantity'] ?>)" 
                                class="w-12 h-12 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <button onclick="addToCart(<?= $product['id'] ?>, document.getElementById('quantity').value)" 
                            class="flex-1 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary-500/30 transition-all duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </div>
                
                <!-- Buy Now Button -->
                <button onclick="buyNow(<?= $product['id'] ?>)" 
                        class="w-full py-3.5 border-2 border-primary-600 text-primary-600 font-semibold rounded-xl hover:bg-primary-50 transition-all duration-300 flex items-center justify-center gap-2 mb-6">
                    <i class="fas fa-bolt"></i>
                    Buy Now
                </button>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="flex items-center gap-4 mb-6">
                    <button onclick="addToWishlist(<?= $product['id'] ?>)" 
                            class="flex items-center gap-2 text-gray-600 hover:text-red-500 transition">
                        <i class="far fa-heart text-xl"></i>
                        <span>Add to Wishlist</span>
                    </button>
                    <button onclick="shareProduct()" class="flex items-center gap-2 text-gray-600 hover:text-primary-600 transition">
                        <i class="fas fa-share-alt text-xl"></i>
                        <span>Share</span>
                    </button>
                </div>
                
                <!-- Product Meta -->
                <div class="border-t border-gray-100 pt-6 space-y-3 text-sm text-gray-600">
                    <?php if ($product['sku']): ?>
                    <div class="flex">
                        <span class="w-24 font-medium text-gray-900">SKU:</span>
                        <span><?= e($product['sku']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex">
                        <span class="w-24 font-medium text-gray-900">Category:</span>
                        <a href="<?= url('category.php?slug=' . e($product['category_slug'] ?? '')) ?>" class="text-primary-600 hover:underline">
                            <?= e($product['category_name'] ?? 'Uncategorized') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Product Tabs -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-12">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-100">
            <nav class="flex overflow-x-auto" id="product-tabs">
                <button onclick="showTab('description')" data-tab="description" 
                        class="tab-btn px-6 py-4 font-semibold text-primary-600 border-b-2 border-primary-600 whitespace-nowrap">
                    Description
                </button>
                <button onclick="showTab('reviews')" data-tab="reviews" 
                        class="tab-btn px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">
                    Reviews (<?= $rating['count'] ?>)
                </button>
                <button onclick="showTab('shipping')" data-tab="shipping" 
                        class="tab-btn px-6 py-4 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">
                    Shipping & Returns
                </button>
            </nav>
        </div>
        
        <!-- Tab Content -->
        <div class="p-6 lg:p-8">
            <!-- Description Tab -->
            <div id="tab-description" class="tab-content">
                <div class="prose max-w-none">
                    <?php if ($product['description']): ?>
                    <?= $product['description'] ?>
                    <?php else: ?>
                    <p class="text-gray-500">No description available for this product.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reviews Tab -->
            <div id="tab-reviews" class="tab-content hidden">
                <?php if (empty($reviews)): ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-comment-alt text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 mb-4">No reviews yet. Be the first to review this product!</p>
                    <?php if (Session::isLoggedIn()): ?>
                    <button class="px-6 py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        Write a Review
                    </button>
                    <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="text-primary-600 font-semibold hover:underline">
                        Login to write a review
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                    <div class="border-b border-gray-100 last:border-0 pb-6 last:pb-0">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                <?= strtoupper(substr($review['first_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h4 class="font-semibold text-gray-900"><?= e($review['first_name'] . ' ' . $review['last_name']) ?></h4>
                                        <div class="flex items-center gap-2">
                                            <div class="flex">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star text-sm <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php if ($review['is_verified_purchase']): ?>
                                            <span class="text-xs text-green-600 bg-green-100 px-2 py-0.5 rounded-full">Verified Purchase</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-400"><?= time_ago($review['created_at']) ?></span>
                                </div>
                                <?php if ($review['title']): ?>
                                <h5 class="font-medium text-gray-900 mb-1"><?= e($review['title']) ?></h5>
                                <?php endif; ?>
                                <p class="text-gray-600"><?= e($review['comment']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Shipping Tab -->
            <div id="tab-shipping" class="tab-content hidden">
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping Information</h3>
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>Free shipping on orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?></span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>Standard shipping: 3-5 business days</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>Express shipping available (1-2 business days)</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Return Policy</h3>
                        <ul class="space-y-3 text-gray-600">
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>30-day return policy</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>Items must be unused and in original packaging</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check text-green-500 mt-1"></i>
                                <span>Free returns for defective items</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Products</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 lg:gap-6">
            <?php foreach ($relatedProducts as $related): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover">
                <div class="relative overflow-hidden aspect-square product-image-container">
                    <a href="<?= url('product.php?slug=' . e($related['slug'])) ?>">
                        <?php if ($related['featured_image']): ?>
                        <img src="<?= upload_url('products/' . e($related['featured_image'])) ?>" alt="<?= e($related['name']) ?>" 
                             class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                        <a href="<?= url('product.php?slug=' . e($related['slug'])) ?>"><?= e($related['name']) ?></a>
                    </h3>
                    <span class="text-lg font-bold text-primary-600"><?= format_price($related['sale_price'] ?: $related['price']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
// Image Gallery
function changeMainImage(src) {
    const mainImage = document.getElementById('main-image');
    if (mainImage) {
        mainImage.src = src;
    }
}

// Quantity Controls
function decreaseQuantity() {
    const input = document.getElementById('quantity');
    const value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
    }
}

function increaseQuantity(max) {
    const input = document.getElementById('quantity');
    const value = parseInt(input.value);
    if (value < max) {
        input.value = value + 1;
    }
}

// Buy Now
function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    addToCart(productId, quantity);
    setTimeout(() => {
        window.location.href = '<?= url('checkout.php') ?>';
    }, 500);
}

// Share Product
function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '<?= e($product['name']) ?>',
            text: '<?= e($product['short_description'] ?: str_limit(strip_tags($product['description'] ?? ''), 100)) ?>',
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        showNotification('success', 'Link copied to clipboard!');
    }
}

// Tabs
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Show selected tab content
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        if (btn.dataset.tab === tabName) {
            btn.classList.add('text-primary-600', 'border-b-2', 'border-primary-600');
            btn.classList.remove('text-gray-500');
        } else {
            btn.classList.remove('text-primary-600', 'border-b-2', 'border-primary-600');
            btn.classList.add('text-gray-500');
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
