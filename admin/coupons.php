<?php
/**
 * Admin Coupons Management
 * 
 * CRUD operations for discount coupons.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();
$action = get('action', 'list');
$couponId = get_int('id', 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    
    if ($postAction === 'delete') {
        $deleteId = get_int('id', 0);
        if ($deleteId > 0) {
            $db->delete('coupons', 'id = ?', [$deleteId]);
            flash_success('Coupon deleted successfully.');
        }
        redirect(url('admin/coupons.php'));
    }
    
    if ($postAction === 'save') {
        $couponData = [
            'code' => strtoupper(Security::sanitizeString($_POST['code'] ?? '')),
            'description' => Security::sanitizeString($_POST['description'] ?? ''),
            'discount_type' => $_POST['discount_type'] ?? 'percentage',
            'discount_value' => (float)($_POST['discount_value'] ?? 0),
            'min_order_amount' => (float)($_POST['min_order_amount'] ?? 0) ?: null,
            'max_discount_amount' => (float)($_POST['max_discount_amount'] ?? 0) ?: null,
            'usage_limit' => (int)($_POST['usage_limit'] ?? 0) ?: null,
            'starts_at' => !empty($_POST['starts_at']) ? $_POST['starts_at'] : null,
            'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $editId = get_int('id', 0);
        
        // Check for duplicate code
        $existingCoupon = $db->fetchOne("SELECT id FROM coupons WHERE code = ? AND id != ?", [$couponData['code'], $editId]);
        if ($existingCoupon) {
            flash_error('A coupon with this code already exists.');
            redirect(url('admin/coupons.php?action=' . $action . ($editId ? '&id=' . $editId : '')));
        }
        
        if ($editId > 0) {
            $db->update('coupons', $couponData, 'id = ?', [$editId]);
            flash_success('Coupon updated successfully.');
        } else {
            $db->insert('coupons', $couponData);
            flash_success('Coupon created successfully.');
        }
        
        redirect(url('admin/coupons.php'));
    }
}

// Fetch coupon for edit
$coupon = null;
if ($action === 'edit' && $couponId > 0) {
    $coupon = $db->fetchOne("SELECT * FROM coupons WHERE id = ?", [$couponId]);
    if (!$coupon) {
        flash_error('Coupon not found.');
        redirect(url('admin/coupons.php'));
    }
}

// Fetch coupons list
$coupons = [];
if ($action === 'list') {
    $coupons = $db->fetchAll("SELECT * FROM coupons ORDER BY created_at DESC");
}

$pageTitle = match($action) {
    'add' => 'Add Coupon',
    'edit' => 'Edit Coupon',
    default => 'Coupons'
};

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <?php if ($action === 'list'): ?>
        <!-- Coupons List -->
        <div class="flex items-center justify-between mb-6">
            <div></div>
            <a href="?action=add" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                <i class="fas fa-plus mr-2"></i> Add Coupon
            </a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full data-table">
                    <thead>
                        <tr>
                            <th class="rounded-tl-2xl">Code</th>
                            <th>Discount</th>
                            <th>Usage</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th class="rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                        <tr><td colspan="6" class="text-center py-8 text-gray-500">No coupons found</td></tr>
                        <?php else: ?>
                        <?php foreach ($coupons as $c): ?>
                        <?php
                        $isExpired = $c['expires_at'] && strtotime($c['expires_at']) < time();
                        $isNotStarted = $c['starts_at'] && strtotime($c['starts_at']) > time();
                        $usageLimitReached = $c['usage_limit'] && $c['used_count'] >= $c['usage_limit'];
                        ?>
                        <tr>
                            <td>
                                <span class="font-mono font-bold text-primary-600"><?= e($c['code']) ?></span>
                                <?php if ($c['description']): ?>
                                <p class="text-sm text-gray-500"><?= e(str_limit($c['description'], 50)) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['discount_type'] === 'percentage'): ?>
                                <span class="font-semibold"><?= $c['discount_value'] ?>%</span>
                                <?php else: ?>
                                <span class="font-semibold"><?= format_price($c['discount_value']) ?></span>
                                <?php endif; ?>
                                <?php if ($c['min_order_amount']): ?>
                                <p class="text-xs text-gray-500">Min: <?= format_price($c['min_order_amount']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $c['used_count'] ?><?= $c['usage_limit'] ? '/' . $c['usage_limit'] : '' ?>
                            </td>
                            <td class="text-sm">
                                <?php if ($c['starts_at']): ?>
                                <p>From: <?= format_date($c['starts_at']) ?></p>
                                <?php endif; ?>
                                <?php if ($c['expires_at']): ?>
                                <p class="<?= $isExpired ? 'text-red-600' : '' ?>">To: <?= format_date($c['expires_at']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$c['is_active']): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Inactive</span>
                                <?php elseif ($isExpired): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Expired</span>
                                <?php elseif ($usageLimitReached): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">Limit Reached</span>
                                <?php elseif ($isNotStarted): ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Scheduled</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <a href="?action=edit&id=<?= $c['id'] ?>" class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="?action=delete&id=<?= $c['id'] ?>" class="inline" onsubmit="return confirmDelete()">
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
                <a href="coupons.php" class="text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Coupons
                </a>
            </div>
            
            <form method="POST" action="?action=<?= $action ?><?= $couponId ? '&id=' . $couponId : '' ?>" class="space-y-6">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Coupon Details</h3>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Coupon Code *</label>
                                <input type="text" name="code" value="<?= e($coupon['code'] ?? '') ?>" required
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none uppercase"
                                       placeholder="e.g., SAVE20">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Discount Type</label>
                                <select name="discount_type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                                    <option value="percentage" <?= ($coupon['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                    <option value="fixed" <?= ($coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <input type="text" name="description" value="<?= e($coupon['description'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                   placeholder="e.g., 20% off on all orders">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Discount Value *</label>
                                <input type="number" name="discount_value" value="<?= e($coupon['discount_value'] ?? '') ?>" required step="0.01" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Min Order Amount</label>
                                <input type="number" name="min_order_amount" value="<?= e($coupon['min_order_amount'] ?? '') ?>" step="0.01" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Max Discount</label>
                                <input type="number" name="max_discount_amount" value="<?= e($coupon['max_discount_amount'] ?? '') ?>" step="0.01" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                                <input type="number" name="usage_limit" value="<?= e($coupon['usage_limit'] ?? '') ?>" min="0"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none"
                                       placeholder="Unlimited">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Starts At</label>
                                <input type="datetime-local" name="starts_at" value="<?= e($coupon['starts_at'] ? date('Y-m-d\TH:i', strtotime($coupon['starts_at'])) : '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Expires At</label>
                                <input type="datetime-local" name="expires_at" value="<?= e($coupon['expires_at'] ? date('Y-m-d\TH:i', strtotime($coupon['expires_at'])) : '') ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                            </div>
                        </div>
                        
                        <div>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" <?= ($coupon['is_active'] ?? 1) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="ml-3 text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <a href="coupons.php" class="px-6 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">
                        <i class="fas fa-save mr-2"></i>
                        <?= $action === 'edit' ? 'Update' : 'Create' ?> Coupon
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
