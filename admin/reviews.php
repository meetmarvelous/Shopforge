<?php
/**
 * Admin Reviews Management
 * 
 * Moderate and manage product reviews.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

require_admin();

$db = db();

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_ajax()) {
    validate_csrf();
    
    $postAction = post('action', '');
    $reviewId = get_int('id', 0);
    
    if ($postAction === 'approve' && $reviewId > 0) {
        $db->update('reviews', ['is_approved' => 1], 'id = ?', [$reviewId]);
        json_response(['success' => true, 'message' => 'Review approved']);
    }
    
    if ($postAction === 'reject' && $reviewId > 0) {
        $db->update('reviews', ['is_approved' => 0], 'id = ?', [$reviewId]);
        json_response(['success' => true, 'message' => 'Review rejected']);
    }
    
    if ($postAction === 'delete' && $reviewId > 0) {
        $db->delete('reviews', 'id = ?', [$reviewId]);
        json_response(['success' => true, 'message' => 'Review deleted']);
    }
    
    json_response(['success' => false, 'error' => 'Invalid action'], 400);
}

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $postAction = post('action', '');
    $reviewId = get_int('id', 0);
    
    if ($postAction === 'approve' && $reviewId > 0) {
        $db->update('reviews', ['is_approved' => 1], 'id = ?', [$reviewId]);
        flash_success('Review approved.');
    }
    
    if ($postAction === 'reject' && $reviewId > 0) {
        $db->update('reviews', ['is_approved' => 0], 'id = ?', [$reviewId]);
        flash_success('Review rejected.');
    }
    
    if ($postAction === 'delete' && $reviewId > 0) {
        $db->delete('reviews', 'id = ?', [$reviewId]);
        flash_success('Review deleted.');
    }
    
    redirect(url('admin/reviews.php' . (!empty($_GET['status']) ? '?status=' . $_GET['status'] : '')));
}

// Filter
$statusFilter = get('status', 'all');
$page = max(1, get_int('page', 1));

$conditions = [];
$params = [];

if ($statusFilter === 'pending') {
    $conditions[] = 'r.is_approved = 0';
} elseif ($statusFilter === 'approved') {
    $conditions[] = 'r.is_approved = 1';
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count total
$totalReviews = $db->fetchColumn("SELECT COUNT(*) FROM reviews r {$whereClause}", $params);
$pagination = paginate($totalReviews, ADMIN_ITEMS_PER_PAGE, $page, current_url());

// Fetch reviews
$reviews = $db->fetchAll(
    "SELECT r.*, p.name as product_name, p.featured_image as product_image, p.slug as product_slug,
            u.first_name, u.last_name, u.email
     FROM reviews r
     JOIN products p ON r.product_id = p.id
     JOIN users u ON r.user_id = u.id
     {$whereClause}
     ORDER BY r.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$pagination['per_page'], $pagination['offset']])
);

// Stats
$pendingCount = $db->fetchColumn("SELECT COUNT(*) FROM reviews WHERE is_approved = 0");
$approvedCount = $db->fetchColumn("SELECT COUNT(*) FROM reviews WHERE is_approved = 1");
$averageRating = $db->fetchColumn("SELECT AVG(rating) FROM reviews WHERE is_approved = 1");

$pageTitle = 'Reviews';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="lg:ml-64 min-h-screen flex flex-col">
    <?php require_once __DIR__ . '/includes/topbar.php'; ?>
    
    <main class="flex-1 p-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Pending Reviews</p>
                        <p class="text-2xl font-bold text-orange-600"><?= number_format($pendingCount) ?></p>
                    </div>
                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-orange-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Approved Reviews</p>
                        <p class="text-2xl font-bold text-green-600"><?= number_format($approvedCount) ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Average Rating</p>
                        <p class="text-2xl font-bold text-primary-600">
                            <?= $averageRating ? number_format($averageRating, 1) : '-' ?>
                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                        </p>
                    </div>
                    <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-star text-primary-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 mb-6">
            <a href="?status=all" class="px-4 py-2 rounded-xl font-medium transition <?= $statusFilter === 'all' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
                All Reviews
            </a>
            <a href="?status=pending" class="px-4 py-2 rounded-xl font-medium transition <?= $statusFilter === 'pending' ? 'bg-orange-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
                Pending
                <?php if ($pendingCount > 0): ?>
                <span class="ml-1 px-1.5 py-0.5 bg-white/20 rounded-full text-xs"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="?status=approved" class="px-4 py-2 rounded-xl font-medium transition <?= $statusFilter === 'approved' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">
                Approved
            </a>
        </div>
        
        <!-- Reviews List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if (empty($reviews)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-star text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No reviews found</h3>
                <p class="text-gray-500">Reviews will appear here when customers leave them.</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($reviews as $review): ?>
                <div class="p-6 hover:bg-gray-50 transition" id="review-<?= $review['id'] ?>">
                    <div class="flex items-start gap-4">
                        <!-- Product Image -->
                        <a href="<?= url('product.php?slug=' . e($review['product_slug'])) ?>" target="_blank">
                            <?php if ($review['product_image']): ?>
                            <img src="<?= upload_url('products/' . e($review['product_image'])) ?>" alt="" class="w-16 h-16 rounded-xl object-cover">
                            <?php else: ?>
                            <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Review Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <a href="<?= url('product.php?slug=' . e($review['product_slug'])) ?>" target="_blank" class="font-semibold text-gray-900 hover:text-primary-600">
                                        <?= e($review['product_name']) ?>
                                    </a>
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <span><?= e($review['first_name'] . ' ' . $review['last_name']) ?></span>
                                        <span>•</span>
                                        <span><?= time_ago($review['created_at']) ?></span>
                                        <?php if ($review['is_verified_purchase']): ?>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Verified Purchase</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <?php if ($review['is_approved']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Approved</span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 bg-orange-100 text-orange-700 text-xs font-medium rounded-full">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Rating Stars -->
                            <div class="flex items-center gap-1 mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>"></i>
                                <?php endfor; ?>
                                <span class="ml-2 text-sm font-medium text-gray-600"><?= $review['rating'] ?>/5</span>
                            </div>
                            
                            <?php if ($review['title']): ?>
                            <h4 class="font-medium text-gray-900 mb-1"><?= e($review['title']) ?></h4>
                            <?php endif; ?>
                            
                            <?php if ($review['comment']): ?>
                            <p class="text-gray-600"><?= nl2br(e($review['comment'])) ?></p>
                            <?php endif; ?>
                            
                            <!-- Actions -->
                            <div class="flex items-center gap-2 mt-4">
                                <?php if (!$review['is_approved']): ?>
                                <form method="POST" action="?id=<?= $review['id'] ?>&status=<?= e($statusFilter) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="?id=<?= $review['id'] ?>&status=<?= e($statusFilter) ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition">
                                        <i class="fas fa-times mr-1"></i> Unapprove
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="?id=<?= $review['id'] ?>&status=<?= e($statusFilter) ?>" class="inline" onsubmit="return confirmDelete()">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-600 text-sm font-medium rounded-lg hover:bg-red-200 transition">
                                        <i class="fas fa-trash mr-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($pagination['total_pages'] > 1): ?>
            <div class="p-4 border-t border-gray-100">
                <?= render_pagination($pagination) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
<?php require_once __DIR__ . '/includes/footer.php'; ?>
