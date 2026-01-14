<?php
/**
 * Admin Products Management
 * 
 * CRUD operations for products.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$productId = get_int('id', 0);

// Get categories for form
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'delete') {
        $deleteId = get_int('id', 0);
        if ($deleteId > 0) {
            $db->update('products', ['is_active' => 0], 'id = ?', [$deleteId]);
            flash_success('Product deleted successfully.');
        }
        redirect(url('admin/products.php'));
    }
    
    if ($postAction === 'save') {
        $productData = [
            'name' => Security::sanitizeString($_POST['name'] ?? ''),
            'slug' => Security::createSlug($_POST['name'] ?? ''),
            'category_id' => get_int('category_id', 0) ?: null,
            'sku' => Security::sanitizeString($_POST['sku'] ?? ''),
            'short_description' => Security::sanitizeString($_POST['short_description'] ?? ''),
            'description' => $_POST['description'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'sale_price' => !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null,
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 5),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => Security::sanitizeString($_POST['meta_title'] ?? ''),
            'meta_description' => Security::sanitizeString($_POST['meta_description'] ?? '')
        ];
        
        // Handle image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $uploadResult = upload_image($_FILES['featured_image'], 'products', 800, 800);
            if ($uploadResult['success']) {
                $productData['featured_image'] = $uploadResult['filename'];
            } else {
                flash_error($uploadResult['error']);
            }
        }
        
        $editId = get_int('id', 0);
        
        if ($editId > 0) {
            // Update existing
            $db->update('products', $productData, 'id = ?', [$editId]);
            flash_success('Product updated successfully.');
        } else {
            // Create new - ensure unique slug
            $baseSlug = $productData['slug'];
            $counter = 1;
            while ($db->exists('products', 'slug = ?', [$productData['slug']])) {
                $productData['slug'] = $baseSlug . '-' . $counter++;
            }
            $db->insert('products', $productData);
            flash_success('Product created successfully.');
        }
        
        redirect(url('admin/products.php'));
    }
}

// Fetch product for edit
$product = null;
if ($action === 'edit' && $productId > 0) {
    $product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    if (!$product) {
        flash_error('Product not found.');
        redirect(url('admin/products.php'));
    }
}

// Fetch products list
$page = max(1, get_int('page', 1));
$search = get('q', '');
$categoryFilter = get_int('category', 0);

$conditions = ['is_active = 1'];
$params = [];

if (!empty($search)) {
    $conditions[] = '(name LIKE ? OR sku LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($categoryFilter > 0) {
    $conditions[] = 'category_id = ?';
    $params[] = $categoryFilter;
}

$whereClause = implode(' AND ', $conditions);
$totalProducts = $db->fetchColumn("SELECT COUNT(*) FROM products WHERE {$whereClause}", $params);
$pagination = paginate($totalProducts, ADMIN_ITEMS_PER_PAGE, $page, current_url());

$products = [];
if ($action === 'list') {
    $products = $db->fetchAll(
        "SELECT p.*, c.name as category_name 
         FROM products p 
         LEFT JOIN categories c ON p.category_id = c.id 
         WHERE p.is_active = 1 " . (!empty($search) ? "AND (p.name LIKE ? OR p.sku LIKE ?) " : "") . 
         ($categoryFilter > 0 ? "AND p.category_id = ? " : "") .
        "ORDER BY p.created_at DESC 
         LIMIT ? OFFSET ?",
        array_merge($params, [$pagination['per_page'], $pagination['offset']])
    );
}

$pageTitle = match($action) {
    'add' => 'Add Product',
    'edit' => 'Edit Product',
    default => 'Products'
};

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Products List -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <form action="" method="GET" class="flex gap-2">
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search products..." 
                           class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                    <select name="category" class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                <i class="fas fa-plus mr-2"></i> Add Product
            </a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                        <tr><td colspan="6" class="text-center py-8 text-gray-500">No products found</td></tr>
                        <?php else: ?>
                        <?php foreach ($products as $prod): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <?php if ($prod['featured_image']): ?>
                                    <img src="<?= upload_url('products/' . e($prod['featured_image'])) ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= e($prod['name']) ?></p>
                                        <p class="text-sm text-gray-500">SKU: <?= e($prod['sku'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($prod['category_name'] ?? 'Uncategorized') ?></td>
                            <td>
                                <?php if ($prod['sale_price'] && $prod['sale_price'] < $prod['price']): ?>
                                <span class="font-semibold text-primary-600"><?= format_price($prod['sale_price']) ?></span>
                                <span class="text-sm text-gray-400 line-through ml-1"><?= format_price($prod['price']) ?></span>
                                <?php else: ?>
                                <span class="font-semibold"><?= format_price($prod['price']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prod['stock_quantity'] <= 0): ?>
                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">Out of Stock</span>
                                <?php elseif ($prod['stock_quantity'] <= $prod['low_stock_threshold']): ?>
                                <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">Low: <?= $prod['stock_quantity'] ?></span>
                                <?php else: ?>
                                <span class="text-green-600"><?= $prod['stock_quantity'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prod['is_featured']): ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-medium rounded-full">Featured</span>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="?action=edit&id=<?= $prod['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="?action=delete&id=<?= $prod['id'] ?>" class="inline" onsubmit="return confirmDelete()">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
        
        <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="max-w-4xl">
            <div class="mb-6">
                <a href="products.php" class="text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Products
                </a>
            </div>
            
            <form method="POST" action="?action=<?= $action ?><?= $productId ? '&id=' . $productId : '' ?>" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" name="name" value="<?= e($product['name'] ?? '') ?>" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input type="text" name="sku" value="<?= e($product['sku'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                            <textarea name="short_description" rows="2"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none resize-none"><?= e($product['short_description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
                            <textarea name="description" rows="5"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none resize-none"><?= e($product['description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pricing & Stock</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price (<?= CURRENCY_SYMBOL ?>) *</label>
                            <input type="number" name="price" value="<?= e($product['price'] ?? '') ?>" required step="0.01" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sale Price</label>
                            <input type="number" name="sale_price" value="<?= e($product['sale_price'] ?? '') ?>" step="0.01" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                            <input type="number" name="stock_quantity" value="<?= e($product['stock_quantity'] ?? 0) ?>" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" value="<?= e($product['low_stock_threshold'] ?? 5) ?>" min="0"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Image</h3>
                    
                    <div>
                        <?php if (!empty($product['featured_image'])): ?>
                        <div class="mb-4">
                            <img src="<?= upload_url('products/' . e($product['featured_image'])) ?>" alt="" class="w-32 h-32 rounded-xl object-cover">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="featured_image" accept="image/*"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        <p class="mt-1 text-sm text-gray-500">Recommended: 800x800px, JPG or PNG</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Options</h3>
                    
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700">Active</span>
                        </label>
                        
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>
                                   class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="ml-3 text-gray-700">Featured Product</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <a href="products.php" class="px-6 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        <?= $action === 'edit' ? 'Update Product' : 'Create Product' ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
