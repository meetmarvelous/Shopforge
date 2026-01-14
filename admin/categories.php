<?php
/**
 * Admin Categories Management
 * 
 * CRUD operations for categories.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$categoryId = get_int('id', 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'delete') {
        $deleteId = get_int('id', 0);
        if ($deleteId > 0) {
            // Check if category has products
            $productCount = $db->fetchColumn("SELECT COUNT(*) FROM products WHERE category_id = ?", [$deleteId]);
            if ($productCount > 0) {
                flash_error("Cannot delete category with $productCount product(s). Move or delete products first.");
            } else {
                $db->delete('categories', 'id = ?', [$deleteId]);
                flash_success('Category deleted successfully.');
            }
        }
        redirect(url('admin/categories.php'));
    }
    
    if ($postAction === 'save') {
        $categoryData = [
            'name' => Security::sanitizeString($_POST['name'] ?? ''),
            'slug' => Security::createSlug($_POST['name'] ?? ''),
            'description' => Security::sanitizeString($_POST['description'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'meta_title' => Security::sanitizeString($_POST['meta_title'] ?? ''),
            'meta_description' => Security::sanitizeString($_POST['meta_description'] ?? '')
        ];
        
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $uploadResult = upload_image($_FILES['image'], 'categories', 400, 400);
            if ($uploadResult['success']) {
                $categoryData['image'] = $uploadResult['filename'];
            } else {
                flash_error($uploadResult['error']);
            }
        }
        
        $editId = get_int('id', 0);
        
        if ($editId > 0) {
            $db->update('categories', $categoryData, 'id = ?', [$editId]);
            flash_success('Category updated successfully.');
        } else {
            // Ensure unique slug
            $baseSlug = $categoryData['slug'];
            $counter = 1;
            while ($db->exists('categories', 'slug = ?', [$categoryData['slug']])) {
                $categoryData['slug'] = $baseSlug . '-' . $counter++;
            }
            $db->insert('categories', $categoryData);
            flash_success('Category created successfully.');
        }
        
        redirect(url('admin/categories.php'));
    }
}

// Fetch category for edit
$category = null;
if ($action === 'edit' && $categoryId > 0) {
    $category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);
    if (!$category) {
        flash_error('Category not found.');
        redirect(url('admin/categories.php'));
    }
}

// Fetch categories list with product counts
$categories = [];
if ($action === 'list') {
    $categories = $db->fetchAll(
        "SELECT c.*, COUNT(p.id) as product_count 
         FROM categories c 
         LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
         GROUP BY c.id
         ORDER BY c.sort_order, c.name"
    );
}

$pageTitle = match($action) {
    'add' => 'Add Category',
    'edit' => 'Edit Category',
    default => 'Categories'
};

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Categories List -->
        <div class="flex items-center justify-between mb-6">
            <div></div>
            <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                <i class="fas fa-plus mr-2"></i> Add Category
            </a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Category</th>
                            <th>Products</th>
                            <th>Sort Order</th>
                            <th>Status</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="text-center py-8 text-gray-500">No categories found</td></tr>
                        <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <?php if ($cat['image']): ?>
                                    <img src="<?= upload_url('categories/' . e($cat['image'])) ?>" alt="" class="w-12 h-12 rounded-lg object-cover">
                                    <?php else: ?>
                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-100 to-accent-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-tag text-primary-600"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium text-gray-900"><?= e($cat['name']) ?></p>
                                        <p class="text-sm text-gray-500"><?= e($cat['slug']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= (int)$cat['product_count'] ?></td>
                            <td><?= $cat['sort_order'] ?></td>
                            <td>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $cat['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' ?>">
                                    <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="?action=edit&id=<?= $cat['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="?action=delete&id=<?= $cat['id'] ?>" class="inline" onsubmit="return confirmDelete()">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
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
        </div>
        
        <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="max-w-2xl">
            <div class="mb-6">
                <a href="categories.php" class="text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Categories
                </a>
            </div>
            
            <form method="POST" action="?action=<?= $action ?><?= $categoryId ? '&id=' . $categoryId : '' ?>" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                            <input type="text" name="name" value="<?= e($category['name'] ?? '') ?>" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none resize-none"><?= e($category['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                                <input type="number" name="sort_order" value="<?= e($category['sort_order'] ?? 0) ?>" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" <?= ($category['is_active'] ?? 1) ? 'checked' : '' ?>
                                           class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-3 text-gray-700">Active</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                            <?php if (!empty($category['image'])): ?>
                            <div class="mb-4">
                                <img src="<?= upload_url('categories/' . e($category['image'])) ?>" alt="" class="w-24 h-24 rounded-xl object-cover">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <a href="categories.php" class="px-6 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        <?= $action === 'edit' ? 'Update' : 'Create' ?> Category
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
