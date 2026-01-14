<?php
/**
 * Products Listing Page
 * 
 * Browse all products with filtering and sorting.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$db = db();

// Get filter parameters
$search = get('q', '');
$categorySlug = get('category', '');
$minPrice = get_int('min_price', 0);
$maxPrice = get_int('max_price', 0);
$sortBy = get('sort', 'newest');
$featured = get('featured', '');
$page = max(1, get_int('page', 1));

// Build base query
$conditions = ['p.is_active = 1'];
$params = [];

if (!empty($search)) {
    $conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($categorySlug)) {
    $conditions[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

if ($minPrice > 0) {
    $conditions[] = 'COALESCE(p.sale_price, p.price) >= ?';
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $conditions[] = 'COALESCE(p.sale_price, p.price) <= ?';
    $params[] = $maxPrice;
}

if ($featured === '1') {
    $conditions[] = 'p.is_featured = 1';
}

$whereClause = implode(' AND ', $conditions);

// Get total count
$totalProducts = $db->fetchColumn(
    "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE {$whereClause}",
    $params
);

// Sorting
$orderBy = match($sortBy) {
    'oldest' => 'p.created_at ASC',
    'price_low' => 'COALESCE(p.sale_price, p.price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.price) DESC',
    'popular' => 'p.sales_count DESC, p.views_count DESC',
    'rating' => 'p.sales_count DESC', // Placeholder until reviews are implemented
    default => 'p.created_at DESC'
};

// Pagination
$pagination = paginate($totalProducts, PRODUCTS_PER_PAGE, $page, current_url());

// Get products
$products = $db->fetchAll(
    "SELECT p.*, c.name as category_name, c.slug as category_slug
     FROM products p 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE {$whereClause}
     ORDER BY {$orderBy}
     LIMIT ? OFFSET ?",
    array_merge($params, [$pagination['per_page'], $pagination['offset']])
);

// Page title
if (!empty($search)) {
    $pageTitle = 'Search Results for "' . $search . '"';
} elseif ($featured === '1') {
    $pageTitle = 'Featured Products';
} else {
    $pageTitle = 'All Products';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumbs -->
    <?= breadcrumbs(['Home' => url(), $pageTitle => '']) ?>
    
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2"><?= e($pageTitle) ?></h1>
        <?php if (!empty($search)): ?>
        <p class="text-gray-600">
            Found <?= number_format($totalProducts) ?> result<?= $totalProducts !== 1 ? 's' : '' ?> for "<?= e($search) ?>"
        </p>
        <?php else: ?>
        <p class="text-gray-600">Showing <?= number_format($totalProducts) ?> product<?= $totalProducts !== 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </div>
    
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Products Grid -->
        <div class="flex-1">
            <!-- Sort Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">
                        Showing <?= $pagination['from'] ?>-<?= $pagination['to'] ?> of <?= number_format($totalProducts) ?>
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <label class="text-gray-600 text-sm">Sort by:</label>
                    <select onchange="updateSort(this.value)" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                        <option value="price_low" <?= $sortBy === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $sortBy === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="popular" <?= $sortBy === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
            <!-- No Products Found -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-search text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Products Found</h3>
                <p class="text-gray-600 mb-6">Try adjusting your search or filter to find what you're looking for.</p>
                <a href="<?= url('products.php') ?>" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                    <i class="fas fa-th-large mr-2"></i>
                    View All Products
                </a>
            </div>
            
            <?php else: ?>
            <!-- Products Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 lg:gap-6">
                <?php foreach ($products as $product): ?>
                <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 card-hover">
                    <!-- Product Image -->
                    <div class="relative overflow-hidden aspect-square product-image-container">
                        <a href="<?= url('product.php?slug=' . e($product['slug'])) ?>">
                            <?php if ($product['featured_image']): ?>
                            <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" alt="<?= e($product['name']) ?>" 
                                 class="w-full h-full object-cover" loading="lazy">
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
                            <?php if ($product['stock_quantity'] <= 0): ?>
                            <span class="px-2 py-1 bg-gray-800 text-white text-xs font-bold rounded-lg">Out of Stock</span>
                            <?php elseif ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                            <span class="px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded-lg">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <button onclick="addToWishlist(<?= $product['id'] ?>)" 
                                    class="w-9 h-9 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-red-500 hover:bg-red-50 transition">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        
                        <!-- Add to Cart Button -->
                        <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                            <button onclick="addToCart(<?= $product['id'] ?>)" 
                                    class="w-full py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-cart"></i>
                                Add to Cart
                            </button>
                        </div>
                        <?php endif; ?>
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
            
            <!-- Pagination -->
            <div class="mt-8">
                <?= render_pagination($pagination) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
