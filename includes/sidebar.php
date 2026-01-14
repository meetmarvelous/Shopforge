<?php
/**
 * Category Sidebar Template
 * 
 * Sidebar with category filters and price range.
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Get categories with product counts
$db = db();
$categories = $db->fetchAll(
    "SELECT c.*, COUNT(p.id) as product_count 
     FROM categories c 
     LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1 
     WHERE c.is_active = 1 
     GROUP BY c.id 
     ORDER BY c.sort_order, c.name"
);

// Current filter values
$currentCategory = get('category', '');
$minPrice = get('min_price', '');
$maxPrice = get('max_price', '');
$sortBy = get('sort', 'newest');
?>

<aside class="w-full lg:w-72 flex-shrink-0">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-28">
        <!-- Categories Section -->
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-layer-group text-primary-500 mr-2"></i>
                Categories
            </h3>
            <ul class="space-y-1">
                <li>
                    <a href="<?= url('products.php') ?>" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all
                              <?= empty($currentCategory) ? 'bg-primary-50 text-primary-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                        <span>All Products</span>
                        <span class="text-sm text-gray-400">
                            <?= $db->fetchColumn("SELECT COUNT(*) FROM products WHERE is_active = 1") ?>
                        </span>
                    </a>
                </li>
                <?php foreach ($categories as $category): ?>
                <li>
                    <a href="<?= url('category.php?slug=' . e($category['slug'])) ?>" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition-all
                              <?= $currentCategory === $category['slug'] ? 'bg-primary-50 text-primary-600 font-medium' : 'text-gray-600 hover:bg-gray-50' ?>">
                        <span><?= e($category['name']) ?></span>
                        <span class="text-sm text-gray-400"><?= (int)$category['product_count'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Price Filter -->
        <div class="p-5 border-b border-gray-100">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-tag text-primary-500 mr-2"></i>
                Price Range
            </h3>
            <form action="" method="GET" id="price-filter-form">
                <!-- Preserve existing query params -->
                <?php if (!empty($currentCategory)): ?>
                <input type="hidden" name="slug" value="<?= e($currentCategory) ?>">
                <?php endif; ?>
                <?php if (!empty($sortBy)): ?>
                <input type="hidden" name="sort" value="<?= e($sortBy) ?>">
                <?php endif; ?>
                
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 mb-1 block">Min</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><?= CURRENCY_SYMBOL ?></span>
                            <input type="number" name="min_price" value="<?= e($minPrice) ?>" placeholder="0"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        </div>
                    </div>
                    <span class="text-gray-400 pt-5">-</span>
                    <div class="flex-1">
                        <label class="text-xs text-gray-500 mb-1 block">Max</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"><?= CURRENCY_SYMBOL ?></span>
                            <input type="number" name="max_price" value="<?= e($maxPrice) ?>" placeholder="Any"
                                   class="w-full pl-8 pr-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        </div>
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 bg-primary-600 text-white rounded-xl font-medium hover:bg-primary-700 transition">
                    Apply Filter
                </button>
            </form>
        </div>
        
        <!-- Sort By -->
        <div class="p-5">
            <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-sort text-primary-500 mr-2"></i>
                Sort By
            </h3>
            <div class="space-y-2">
                <?php
                $sortOptions = [
                    'newest' => 'Newest First',
                    'oldest' => 'Oldest First',
                    'price_low' => 'Price: Low to High',
                    'price_high' => 'Price: High to Low',
                    'popular' => 'Most Popular',
                    'rating' => 'Top Rated'
                ];
                foreach ($sortOptions as $value => $label):
                ?>
                <label class="flex items-center px-3 py-2 rounded-xl cursor-pointer hover:bg-gray-50 transition
                              <?= $sortBy === $value ? 'bg-primary-50' : '' ?>">
                    <input type="radio" name="sort_option" value="<?= $value ?>" 
                           class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                           <?= $sortBy === $value ? 'checked' : '' ?>
                           onchange="updateSort('<?= $value ?>')">
                    <span class="ml-3 text-sm <?= $sortBy === $value ? 'text-primary-600 font-medium' : 'text-gray-600' ?>">
                        <?= $label ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Clear Filters -->
        <?php if (!empty($minPrice) || !empty($maxPrice) || !empty($currentCategory)): ?>
        <div class="p-5 pt-0">
            <a href="<?= url('products.php') ?>" class="flex items-center justify-center py-2.5 text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition">
                <i class="fas fa-times mr-2"></i>
                Clear All Filters
            </a>
        </div>
        <?php endif; ?>
    </div>
</aside>

<script>
function updateSort(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', value);
    window.location.href = url.toString();
}
</script>
