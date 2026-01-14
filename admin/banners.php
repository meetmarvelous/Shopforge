<?php
/**
 * Admin Banners Management
 * 
 * CRUD operations for homepage banners/carousel.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$bannerId = get_int('id', 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'delete') {
        $deleteId = get_int('id', 0);
        if ($deleteId > 0) {
            // Get image to delete
            $banner = $db->fetchOne("SELECT image FROM banners WHERE id = ?", [$deleteId]);
            if ($banner && $banner['image']) {
                delete_file(UPLOADS_PATH . '/banners/' . $banner['image']);
            }
            $db->delete('banners', 'id = ?', [$deleteId]);
            flash_success('Banner deleted successfully.');
        }
        redirect(url('admin/banners.php'));
    }
    
    if ($postAction === 'toggle') {
        $toggleId = get_int('id', 0);
        if ($toggleId > 0) {
            $db->query("UPDATE banners SET is_active = NOT is_active WHERE id = ?", [$toggleId]);
            flash_success('Banner status updated.');
        }
        redirect(url('admin/banners.php'));
    }
    
    if ($postAction === 'save') {
        $bannerData = [
            'title' => Security::sanitizeString($_POST['title'] ?? ''),
            'subtitle' => Security::sanitizeString($_POST['subtitle'] ?? ''),
            'link' => Security::sanitizeUrl($_POST['link'] ?? ''),
            'button_text' => Security::sanitizeString($_POST['button_text'] ?? ''),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'starts_at' => !empty($_POST['starts_at']) ? $_POST['starts_at'] : null,
            'ends_at' => !empty($_POST['ends_at']) ? $_POST['ends_at'] : null
        ];
        
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            // Create banners directory if it doesn't exist
            $bannersDir = UPLOADS_PATH . '/banners';
            if (!is_dir($bannersDir)) {
                mkdir($bannersDir, 0755, true);
            }
            
            $uploadResult = upload_image($_FILES['image'], $bannersDir, 1920, 600);
            if ($uploadResult['success']) {
                $bannerData['image'] = $uploadResult['filename'];
                
                // Delete old image if updating
                if ($bannerId > 0) {
                    $oldBanner = $db->fetchOne("SELECT image FROM banners WHERE id = ?", [$bannerId]);
                    if ($oldBanner && $oldBanner['image']) {
                        delete_file($bannersDir . '/' . $oldBanner['image']);
                    }
                }
            } else {
                flash_error($uploadResult['error']);
                redirect(url('admin/banners.php?action=' . $action . ($bannerId ? '&id=' . $bannerId : '')));
            }
        } elseif ($action === 'add') {
            flash_error('Please upload a banner image.');
            redirect(url('admin/banners.php?action=add'));
        }
        
        if ($bannerId > 0) {
            $db->update('banners', $bannerData, 'id = ?', [$bannerId]);
            flash_success('Banner updated successfully.');
        } else {
            $db->insert('banners', $bannerData);
            flash_success('Banner created successfully.');
        }
        
        redirect(url('admin/banners.php'));
    }
}

// Fetch banner for edit
$banner = null;
if ($action === 'edit' && $bannerId > 0) {
    $banner = $db->fetchOne("SELECT * FROM banners WHERE id = ?", [$bannerId]);
    if (!$banner) {
        flash_error('Banner not found.');
        redirect(url('admin/banners.php'));
    }
}

// Fetch banners list
$banners = $db->fetchAll("SELECT * FROM banners ORDER BY sort_order, created_at DESC");

$pageTitle = match($action) {
    'add' => 'Add Banner',
    'edit' => 'Edit Banner',
    default => 'Banners'
};

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Banners List -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Homepage Banners</h2>
            <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                <i class="fas fa-plus mr-2"></i> Add Banner
            </a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Image</th>
                            <th>Title</th>
                            <th>Link</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Schedule</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                        <tr><td colspan="7" class="text-center py-8 text-gray-500">No banners yet</td></tr>
                        <?php else: ?>
                        <?php foreach ($banners as $b): ?>
                        <tr>
                            <td>
                                <img src="<?= upload_url('banners/' . e($b['image'])) ?>" alt="" class="w-32 h-16 rounded-lg object-cover">
                            </td>
                            <td>
                                <p class="font-medium text-gray-900"><?= e($b['title'] ?: 'Untitled') ?></p>
                                <?php if ($b['subtitle']): ?>
                                <p class="text-sm text-gray-500"><?= e(str_limit($b['subtitle'], 40)) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($b['link']): ?>
                                <a href="<?= e($b['link']) ?>" target="_blank" class="text-primary-600 hover:underline text-sm">
                                    <?= e(str_limit($b['link'], 30)) ?>
                                </a>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $b['sort_order'] ?></td>
                            <td>
                                <form method="POST" action="?action=toggle&id=<?= $b['id'] ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <button type="submit" class="px-2 py-1 text-xs font-medium rounded-full <?= $b['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                        <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="text-sm text-gray-500">
                                <?php if ($b['starts_at'] || $b['ends_at']): ?>
                                <?= $b['starts_at'] ? format_date($b['starts_at']) : 'Always' ?>
                                -
                                <?= $b['ends_at'] ? format_date($b['ends_at']) : 'Forever' ?>
                                <?php else: ?>
                                Always
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="?action=edit&id=<?= $b['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="?action=delete&id=<?= $b['id'] ?>" class="inline" onsubmit="return confirmDelete()">
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
        </div>
        
        <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="max-w-3xl">
            <div class="mb-6">
                <a href="banners.php" class="text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Banners
                </a>
            </div>
            
            <form method="POST" action="?action=<?= $action ?><?= $bannerId ? '&id=' . $bannerId : '' ?>" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Banner Details</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Banner Image *</label>
                            <?php if (!empty($banner['image'])): ?>
                            <div class="mb-3">
                                <img src="<?= upload_url('banners/' . e($banner['image'])) ?>" alt="" class="w-64 h-20 rounded-xl object-cover">
                            </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" <?= $action === 'add' ? 'required' : '' ?>
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            <p class="mt-1 text-sm text-gray-500">Recommended: 1920x600px, JPG or PNG</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" value="<?= e($banner['title'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                                <input type="text" name="subtitle" value="<?= e($banner['subtitle'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
                                <input type="url" name="link" value="<?= e($banner['link'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                       placeholder="https://...">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
                                <input type="text" name="button_text" value="<?= e($banner['button_text'] ?? 'Shop Now') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                                <input type="number" name="sort_order" value="<?= e($banner['sort_order'] ?? 0) ?>" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="datetime-local" name="starts_at" value="<?= $banner['starts_at'] ? date('Y-m-d\TH:i', strtotime($banner['starts_at'])) : '' ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="datetime-local" name="ends_at" value="<?= $banner['ends_at'] ? date('Y-m-d\TH:i', strtotime($banner['ends_at'])) : '' ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                        
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" <?= ($banner['is_active'] ?? 1) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-3 text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <a href="banners.php" class="px-6 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        <?= $action === 'edit' ? 'Update Banner' : 'Create Banner' ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
