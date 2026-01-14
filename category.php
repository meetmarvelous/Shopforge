<?php
/**
 * Category Page
 * 
 * Displays products filtered by category.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$db = db();

// Get category by slug
$slug = Security::sanitizeString($_GET['slug'] ?? '');

if (empty($slug)) {
    redirect(url('products.php'));
}

$category = $db->fetchOne(
    "SELECT * FROM categories WHERE slug = ? AND is_active = 1",
    [$slug]
);

if (!$category) {
    flash_error('Category not found.');
    redirect(url('products.php'));
}

// Pagination
$page = max(1, get_int('page', 1));
$perPage = PRODUCTS_PER_PAGE;

// Get products in this category
$totalProducts = $db->fetchColumn(
    "SELECT COUNT(*) FROM products WHERE category_id = ? AND is_active = 1",
    [$category['id']]
);

$pagination = paginate($totalProducts, $perPage, $page, current_url());

$products = $db->fetchAll(
    "SELECT * FROM products 
     WHERE category_id = ? AND is_active = 1
     ORDER BY is_featured DESC, created_at DESC
     LIMIT ? OFFSET ?",
    [$category['id'], $perPage, $pagination['offset']]
);

// SEO
$pageTitle = $category['name'] . ' - ' . SITE_NAME;
$metaDescription = $category['description'] ?? "Browse our {$category['name']} collection at " . SITE_NAME;

require_once INCLUDES_PATH . '/header.php';
require_once INCLUDES_PATH . '/navbar.php';
?>

<!-- Category Hero -->
<section class="bg-gradient-to-r from-primary-600 to-accent-600 py-12 lg:py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <nav class="mb-4">
                <ol class="flex items-center justify-center space-x-2 text-white/80 text-sm">
                    <li><a href="<?= url() ?>" class="hover:text-white">Home</a></li>
                    <li><span>/</span></li>
                    <li><a href="<?= url('products.php') ?>" class="hover:text-white">Products</a></li>
                    <li><span>/</span></li>
                    <li class="text-white font-medium"><?= e($category['name']) ?></li>
                </ol>
            </nav>
            <h1 class="text-3xl lg:text-4xl font-bold text-white mb-4"><?= e($category['name']) ?></h1>
            <?php if ($category['description']): ?>
            <p class="text-lg text-white/90"><?= e($category['description']) ?></p>
            <?php endif; ?>
            <p class="mt-4 text-white/70"><?= number_format($totalProducts) ?> product<?= $totalProducts != 1 ? 's' : '' ?> found</p>
        </div>
    </div>
</section>

<!-- Products Grid -->
<section class="py-12 lg:py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <?php if (empty($products)): ?>
        <!-- No Products -->
        <div class="text-center py-16">
            <div class="w-24 h-24 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-box-open text-4xl text-gray-400"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">No Products Yet</h2>
            <p class="text-gray-600 mb-8">We're still adding products to this category. Check back soon!</p>
            <a href="<?= url('products.php') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Browse All Products
            </a>
        </div>
        <?php else: ?>
        <!-- Products Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
            <?php foreach ($products as $product): ?>
            <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                <!-- Product Image -->
                <div class="relative aspect-square overflow-hidden">
                    <?php if ($product['featured_image']): ?>
                    <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" 
                         alt="<?= e($product['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <?php $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>
                        <span class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-lg">-<?= $discount ?>%</span>
                        <?php endif; ?>
                        <?php if ($product['is_featured']): ?>
                        <span class="px-2 py-1 bg-amber-500 text-white text-xs font-bold rounded-lg">Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="w-9 h-9 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-red-500 hover:scale-110 transition add-to-wishlist"
                                data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>" class="block">
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-600 transition">
                            <?= e($product['name']) ?>
                        </h3>
                    </a>
                    
                    <!-- Price -->
                    <div class="flex items-center gap-2 mb-3">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                        <span class="text-lg font-bold text-primary-600"><?= format_price($product['sale_price']) ?></span>
                        <span class="text-sm text-gray-400 line-through"><?= format_price($product['price']) ?></span>
                        <?php else: ?>
                        <span class="text-lg font-bold text-gray-900"><?= format_price($product['price']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add to Cart -->
                    <?php if ($product['stock_quantity'] > 0): ?>
                    <button class="w-full py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition add-to-cart"
                            data-product-id="<?= $product['id'] ?>">
                        <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                    </button>
                    <?php else: ?>
                    <button class="w-full py-2.5 bg-gray-200 text-gray-500 text-sm font-semibold rounded-xl cursor-not-allowed" disabled>
                        Out of Stock
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="mt-12">
            <?= render_pagination($pagination) ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
