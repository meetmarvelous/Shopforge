<?php
/**
 * Homepage
 * 
 * Main landing page with featured products, categories, and promotions.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$db = db();

// Get featured products
$featuredProducts = $db->fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_active = 1 AND p.is_featured = 1 
     ORDER BY p.created_at DESC 
     LIMIT 8"
);

// Get new arrivals
$newArrivals = $db->fetchAll(
    "SELECT p.*, c.name as category_name 
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.is_active = 1 
     ORDER BY p.created_at DESC 
     LIMIT 8"
);

// Get categories with product counts
$categories = $db->fetchAll(
    "SELECT c.*, 
            (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) as product_count
     FROM categories c 
     WHERE c.is_active = 1 
     ORDER BY c.sort_order"
);

// Get banners
$banners = $db->fetchAll(
    "SELECT * FROM banners 
     WHERE is_active = 1 
     AND (starts_at IS NULL OR starts_at <= NOW()) 
     AND (ends_at IS NULL OR ends_at >= NOW()) 
     ORDER BY sort_order"
);

$pageTitle = SITE_TAGLINE;
$pageDescription = SITE_NAME . ' - ' . SITE_TAGLINE . '. Shop quality products with free shipping and secure payment.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden bg-gradient-to-br from-primary-900 via-primary-800 to-accent-900">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>
    
    <div class="container mx-auto px-4 py-16 lg:py-24 relative">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="text-center lg:text-left animate-fade-in">
                <span class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white/90 text-sm font-medium mb-6">
                    <i class="fas fa-sparkles text-yellow-400 mr-2"></i>
                    New Season Collection
                </span>
                <h1 class="text-4xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
                    Discover Your
                    <span class="bg-gradient-to-r from-yellow-400 to-orange-400 bg-clip-text text-transparent">
                        Perfect Style
                    </span>
                </h1>
                <p class="text-lg lg:text-xl text-white/80 mb-8 max-w-xl mx-auto lg:mx-0">
                    Shop the latest trends with exclusive deals and enjoy free shipping on all orders over <?= format_price(FREE_SHIPPING_THRESHOLD) ?>.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="<?= url('products.php') ?>" class="px-8 py-4 bg-white text-primary-600 font-bold rounded-2xl hover:shadow-2xl hover:shadow-white/20 transition-all duration-300 transform hover:-translate-y-1">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        Shop Now
                    </a>
                    <a href="#categories" class="px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-2xl hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-th-large mr-2"></i>
                        View Categories
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="flex items-center justify-center lg:justify-start gap-8 mt-12">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">10K+</div>
                        <div class="text-white/60 text-sm">Products</div>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">15K+</div>
                        <div class="text-white/60 text-sm">Customers</div>
                    </div>
                    <div class="w-px h-12 bg-white/20"></div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white">4.9</div>
                        <div class="text-white/60 text-sm flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400 text-xs"></i> Rating
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="relative hidden lg:block animate-slide-up">
                <div class="absolute -top-10 -right-10 w-72 h-72 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full blur-3xl opacity-30"></div>
                <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full blur-3xl opacity-30"></div>
                <div class="relative bg-white/10 backdrop-blur-lg rounded-3xl p-6 border border-white/20">
                    <img src="<?= asset('images/hero-products.png') ?>" alt="Featured Products" class="w-full rounded-2xl" onerror="this.src='https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=600&fit=crop'">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Wave Bottom -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 100L60 91.7C120 83.3 240 66.7 360 58.3C480 50 600 50 720 58.3C840 66.7 960 83.3 1080 83.3C1200 83.3 1320 66.7 1380 58.3L1440 50V100H1380C1320 100 1200 100 1080 100C960 100 840 100 720 100C600 100 480 100 360 100C240 100 120 100 60 100H0Z" fill="#f9fafb"/>
        </svg>
    </div>
</section>

<!-- Features Bar -->
<section class="py-8 border-b border-gray-100 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-truck text-primary-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">Free Shipping</h4>
                    <p class="text-sm text-gray-500">Above <?= format_price(FREE_SHIPPING_THRESHOLD) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">Secure Payment</h4>
                    <p class="text-sm text-gray-500">100% Protected</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-headset text-orange-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">24/7 Support</h4>
                    <p class="text-sm text-gray-500">Dedicated Help</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-undo text-purple-600 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">Easy Returns</h4>
                    <p class="text-sm text-gray-500">30 Day Policy</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section id="categories" class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">Shop by Category</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Browse our curated collections and find exactly what you're looking for.</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-6">
            <?php foreach ($categories as $category): ?>
            <a href="<?= url('category.php?slug=' . e($category['slug'])) ?>" 
               class="group bg-white rounded-2xl p-6 shadow-sm hover:shadow-xl transition-all duration-300 text-center card-hover">
                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-primary-100 to-accent-100 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-tag text-primary-600 text-2xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors"><?= e($category['name']) ?></h3>
                <p class="text-sm text-gray-500"><?= (int)($category['product_count'] ?? 0) ?> Products</p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">Featured Products</h2>
                <p class="text-gray-600">Handpicked items just for you</p>
            </div>
            <a href="<?= url('products.php?featured=1') ?>" class="hidden md:inline-flex items-center text-primary-600 font-semibold hover:text-primary-700 transition">
                View All <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover">
                <!-- Product Image -->
                <div class="relative overflow-hidden aspect-square product-image-container">
                    <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>">
                        <?php if ($product['featured_image']): ?>
                        <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" alt="<?= e($product['name']) ?>" 
                             class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                        <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-lg">-<?= $discount ?>%</span>
                        <?php endif; ?>
                        <?php if ($product['is_featured']): ?>
                        <span class="px-2 py-1 bg-yellow-500 text-white text-xs font-bold rounded-lg">Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <button onclick="addToWishlist(<?= $product['id'] ?>)" 
                                class="w-9 h-9 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-red-50 transition">
                            <i class="far fa-heart"></i>
                        </button>
                        <button onclick="quickView(<?= $product['id'] ?>)"
                                class="w-9 h-9 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-primary-600 hover:bg-primary-50 transition">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                        <button onclick="addToCart(<?= $product['id'] ?>)" 
                                class="w-full py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <p class="text-xs text-gray-500 mb-1"><?= e($product['category_name'] ?? 'Uncategorized') ?></p>
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                        <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>"><?= e($product['name']) ?></a>
                    </h3>
                    <div class="flex items-center gap-2">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="text-lg font-bold text-primary-600"><?= format_price($product['sale_price']) ?></span>
                        <span class="text-sm text-gray-400 line-through"><?= format_price($product['price']) ?></span>
                        <?php else: ?>
                        <span class="text-lg font-bold text-gray-900"><?= format_price($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8 md:hidden">
            <a href="<?= url('products.php?featured=1') ?>" class="inline-flex items-center text-primary-600 font-semibold">
                View All Products <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Promo Banner -->
<section class="py-16 bg-gradient-to-r from-primary-600 via-primary-500 to-accent-500 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-20 -right-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white rounded-full blur-3xl"></div>
    </div>
    <div class="container mx-auto px-4 relative">
        <div class="max-w-3xl mx-auto text-center">
            <span class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-medium mb-6">
                <i class="fas fa-gift mr-2"></i> Limited Time Offer
            </span>
            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4">
                Get 20% Off Your First Order
            </h2>
            <p class="text-lg text-white/80 mb-8">
                Sign up for our newsletter and receive an exclusive discount code for your first purchase.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <input type="email" placeholder="Enter your email" 
                       class="px-6 py-4 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30 text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50 w-full sm:w-80">
                <button class="px-8 py-4 bg-white text-primary-600 font-bold rounded-2xl hover:shadow-2xl hover:shadow-white/20 transition-all duration-300">
                    Get Discount
                </button>
            </div>
        </div>
    </div>
</section>

<!-- New Arrivals -->
<?php if (!empty($newArrivals)): ?>
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">New Arrivals</h2>
                <p class="text-gray-600">Check out the latest additions to our store</p>
            </div>
            <a href="<?= url('products.php?sort=newest') ?>" class="hidden md:inline-flex items-center text-primary-600 font-semibold hover:text-primary-700 transition">
                View All <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            <?php foreach ($newArrivals as $product): ?>
            <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover">
                <!-- Product Image -->
                <div class="relative overflow-hidden aspect-square product-image-container">
                    <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>">
                        <?php if ($product['featured_image']): ?>
                        <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" alt="<?= e($product['name']) ?>" 
                             class="w-full h-full object-cover">
                        <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                    
                    <!-- New Badge -->
                    <div class="absolute top-3 left-3">
                        <span class="px-2 py-1 bg-green-500 text-white text-xs font-bold rounded-lg">New</span>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <button onclick="addToWishlist(<?= $product['id'] ?>)" 
                                class="w-9 h-9 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-red-50 transition">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                        <button onclick="addToCart(<?= $product['id'] ?>)" 
                                class="w-full py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-cart"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <p class="text-xs text-gray-500 mb-1"><?= e($product['category_name'] ?? 'Uncategorized') ?></p>
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-600 transition-colors">
                        <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>"><?= e($product['name']) ?></a>
                    </h3>
                    <div class="flex items-center gap-2">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="text-lg font-bold text-primary-600"><?= format_price($product['sale_price']) ?></span>
                        <span class="text-sm text-gray-400 line-through"><?= format_price($product['price']) ?></span>
                        <?php else: ?>
                        <span class="text-lg font-bold text-gray-900"><?= format_price($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">What Our Customers Say</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Don't just take our word for it - hear from our satisfied customers.</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
            <div class="bg-gray-50 rounded-2xl p-6 lg:p-8">
                <div class="flex items-center gap-1 mb-4">
                    <?php for($i = 0; $i < 5; $i++): ?>
                    <i class="fas fa-star text-yellow-400"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-gray-600 mb-6">"Amazing quality products and super fast delivery! I've been shopping here for months and never been disappointed. Highly recommended!"</p>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full flex items-center justify-center text-white font-bold">
                        JD
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">John Doe</h4>
                        <p class="text-sm text-gray-500">Verified Buyer</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-2xl p-6 lg:p-8">
                <div class="flex items-center gap-1 mb-4">
                    <?php for($i = 0; $i < 5; $i++): ?>
                    <i class="fas fa-star text-yellow-400"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-gray-600 mb-6">"The customer service is exceptional. They helped me choose the perfect gift and even wrapped it beautifully. Will definitely shop again!"</p>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center text-white font-bold">
                        SA
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Sarah Adams</h4>
                        <p class="text-sm text-gray-500">Verified Buyer</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 rounded-2xl p-6 lg:p-8">
                <div class="flex items-center gap-1 mb-4">
                    <?php for($i = 0; $i < 5; $i++): ?>
                    <i class="fas fa-star text-yellow-400"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-gray-600 mb-6">"Best prices I've found online! The free shipping offer is fantastic and the products always arrive in perfect condition."</p>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center text-white font-bold">
                        MK
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Michael King</h4>
                        <p class="text-sm text-gray-500">Verified Buyer</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
