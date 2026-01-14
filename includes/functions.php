<?php
/**
 * Helper Functions
 * 
 * Common utility functions used throughout the application.
 * 
 * @package ShopForge
 * @version 1.0
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Session.php';

// =============================================================================
// URL HELPERS
// =============================================================================

/**
 * Generate URL relative to base
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_URL . ($path ? '/' . $path : '');
}

/**
 * Generate asset URL
 */
function asset(string $path): string
{
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Generate URL for uploaded files
 */
function upload_url(string $path): string
{
    return UPLOADS_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect to URL
 */
function redirect(string $url, int $code = 302): void
{
    header("Location: {$url}", true, $code);
    exit;
}

/**
 * Redirect back to previous page
 */
function back(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? url();
    redirect($referer);
}

/**
 * Get current URL
 */
function current_url(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

// =============================================================================
// FORMATTING HELPERS
// =============================================================================

/**
 * Format price with currency symbol
 */
function format_price(float $amount): string
{
    $formatted = number_format($amount, 2);
    
    if (CURRENCY_POSITION === 'before') {
        return CURRENCY_SYMBOL . $formatted;
    }
    
    return $formatted . ' ' . CURRENCY_SYMBOL;
}

/**
 * Format date
 */
function format_date(string $date, string $format = 'M j, Y'): string
{
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function format_datetime(string $datetime, string $format = 'M j, Y g:i A'): string
{
    return date($format, strtotime($datetime));
}

/**
 * Time ago format
 */
function time_ago(string $datetime): string
{
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    
    return format_date($datetime);
}

/**
 * Truncate text
 */
function str_limit(string $text, int $limit = 100, string $end = '...'): string
{
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    
    return mb_substr($text, 0, $limit) . $end;
}

// =============================================================================
// INPUT HELPERS
// =============================================================================

/**
 * Get POST input with sanitization
 */
function post(string $key, mixed $default = null): mixed
{
    return isset($_POST[$key]) ? Security::sanitizeString($_POST[$key]) : $default;
}

/**
 * Get GET input with sanitization
 */
function get(string $key, mixed $default = null): mixed
{
    return isset($_GET[$key]) ? Security::sanitizeString($_GET[$key]) : $default;
}

/**
 * Get integer from request
 */
function get_int(string $key, int $default = 0): int
{
    $value = $_GET[$key] ?? $_POST[$key] ?? $default;
    return Security::sanitizeInt($value);
}

/**
 * Old input value for form repopulation
 */
function old(string $key, mixed $default = ''): mixed
{
    $old = Session::get('old_input', []);
    return $old[$key] ?? $default;
}

/**
 * Store old input
 */
function store_old_input(): void
{
    Session::set('old_input', $_POST);
}

/**
 * Clear old input
 */
function clear_old_input(): void
{
    Session::remove('old_input');
}

// =============================================================================
// FLASH MESSAGE HELPERS
// =============================================================================

/**
 * Set success flash message
 */
function flash_success(string $message): void
{
    Session::flash('success', $message);
}

/**
 * Set error flash message
 */
function flash_error(string $message): void
{
    Session::flash('error', $message);
}

/**
 * Set warning flash message
 */
function flash_warning(string $message): void
{
    Session::flash('warning', $message);
}

/**
 * Set info flash message
 */
function flash_info(string $message): void
{
    Session::flash('info', $message);
}

/**
 * Get flash message HTML
 */
function get_flash_messages(): string
{
    $html = '';
    
    $types = [
        'success' => ['bg-green-100', 'border-green-500', 'text-green-700', 'check-circle'],
        'error' => ['bg-red-100', 'border-red-500', 'text-red-700', 'x-circle'],
        'warning' => ['bg-yellow-100', 'border-yellow-500', 'text-yellow-700', 'exclamation-triangle'],
        'info' => ['bg-blue-100', 'border-blue-500', 'text-blue-700', 'info-circle']
    ];
    
    foreach ($types as $type => $classes) {
        if (Session::hasFlash($type)) {
            $message = Security::escape(Session::getFlash($type));
            $html .= sprintf(
                '<div class="flash-message %s border-l-4 %s p-4 mb-4 rounded-r-lg flex items-center" role="alert">
                    <i class="fas fa-%s mr-3 text-lg"></i>
                    <span>%s</span>
                    <button type="button" class="ml-auto text-lg opacity-50 hover:opacity-100" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>',
                $classes[0], $classes[2], $classes[3], $message
            );
        }
    }
    
    return $html;
}

// =============================================================================
// IMAGE UPLOAD HELPERS
// =============================================================================

/**
 * Handle image upload
 * 
 * @param array $file $_FILES array element
 * @param string $directory Upload directory path
 * @param int $maxWidth Maximum image width
 * @param int $maxHeight Maximum image height
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function upload_image(array $file, string $directory, int $maxWidth = 0, int $maxHeight = 0): array
{
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        return [
            'success' => false,
            'filename' => '',
            'error' => $errors[$file['error']] ?? 'Unknown upload error'
        ];
    }
    
    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return [
            'success' => false,
            'filename' => '',
            'error' => 'File size exceeds ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . 'MB limit'
        ];
    }
    
    // Check extension
    if (!Security::isAllowedExtension($file['name'], UPLOAD_ALLOWED_IMAGES)) {
        return [
            'success' => false,
            'filename' => '',
            'error' => 'Invalid file type. Allowed: ' . implode(', ', UPLOAD_ALLOWED_IMAGES)
        ];
    }
    
    // Validate MIME type
    if (!Security::isValidImageMime($file['tmp_name'])) {
        return [
            'success' => false,
            'filename' => '',
            'error' => 'Invalid image file'
        ];
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Generate unique filename
    $filename = Security::generateUniqueFilename($file['name']);
    $filepath = $directory . '/' . $filename;
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'filename' => '',
            'error' => 'Failed to move uploaded file'
        ];
    }
    
    // Resize if needed
    if ($maxWidth > 0 || $maxHeight > 0) {
        resize_image($filepath, $maxWidth, $maxHeight);
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'error' => ''
    ];
}

/**
 * Resize image maintaining aspect ratio
 */
function resize_image(string $filepath, int $maxWidth, int $maxHeight): bool
{
    $info = getimagesize($filepath);
    if (!$info) {
        return false;
    }
    
    list($width, $height, $type) = $info;
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    
    if ($ratio >= 1) {
        return true; // No resize needed
    }
    
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return false;
    }
    
    if (!$source) {
        return false;
    }
    
    // Create new image
    $dest = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dest, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dest, $filepath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($dest, $filepath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dest, $filepath, 90);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($dest);
    
    return true;
}

/**
 * Delete a file safely
 */
function delete_file(string $filepath): bool
{
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// =============================================================================
// PAGINATION HELPER
// =============================================================================

/**
 * Generate pagination data
 * 
 * @param int $total Total items
 * @param int $perPage Items per page
 * @param int $currentPage Current page number
 * @param string $baseUrl Base URL for pagination links
 * @return array Pagination data
 */
function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): array
{
    $totalPages = max(1, ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $offset = ($currentPage - 1) * $perPage;
    
    // Generate page links
    $links = [];
    $range = 2; // Pages to show on each side of current
    
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);
    
    // Previous
    if ($currentPage > 1) {
        $links[] = [
            'page' => $currentPage - 1,
            'label' => '&laquo; Previous',
            'url' => $baseUrl . '?page=' . ($currentPage - 1),
            'active' => false,
            'disabled' => false
        ];
    }
    
    // First page
    if ($start > 1) {
        $links[] = [
            'page' => 1,
            'label' => '1',
            'url' => $baseUrl . '?page=1',
            'active' => false,
            'disabled' => false
        ];
        
        if ($start > 2) {
            $links[] = [
                'page' => null,
                'label' => '...',
                'url' => '',
                'active' => false,
                'disabled' => true
            ];
        }
    }
    
    // Page numbers
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'page' => $i,
            'label' => (string)$i,
            'url' => $baseUrl . '?page=' . $i,
            'active' => $i === $currentPage,
            'disabled' => false
        ];
    }
    
    // Last page
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $links[] = [
                'page' => null,
                'label' => '...',
                'url' => '',
                'active' => false,
                'disabled' => true
            ];
        }
        
        $links[] = [
            'page' => $totalPages,
            'label' => (string)$totalPages,
            'url' => $baseUrl . '?page=' . $totalPages,
            'active' => false,
            'disabled' => false
        ];
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $links[] = [
            'page' => $currentPage + 1,
            'label' => 'Next &raquo;',
            'url' => $baseUrl . '?page=' . ($currentPage + 1),
            'active' => false,
            'disabled' => false
        ];
    }
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'from' => $offset + 1,
        'to' => min($offset + $perPage, $total),
        'links' => $links
    ];
}

/**
 * Render pagination HTML
 */
function render_pagination(array $pagination): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav class="flex items-center justify-center space-x-1">';
    
    foreach ($pagination['links'] as $link) {
        if ($link['disabled']) {
            $html .= '<span class="px-3 py-2 text-gray-400">...</span>';
        } elseif ($link['active']) {
            $html .= '<span class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium">' . $link['label'] . '</span>';
        } else {
            $html .= '<a href="' . e($link['url']) . '" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">' . $link['label'] . '</a>';
        }
    }
    
    $html .= '</nav>';
    
    return $html;
}

// =============================================================================
// CART HELPERS
// =============================================================================

/**
 * Get cart count for current user/session
 */
function get_cart_count(): int
{
    $db = db();
    
    if (Session::isLoggedIn()) {
        $userId = Session::getUserId();
        return (int) $db->fetchColumn(
            "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?",
            [$userId]
        );
    }
    
    $sessionId = session_id();
    return (int) $db->fetchColumn(
        "SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?",
        [$sessionId]
    );
}

/**
 * Merge guest cart to user cart after login
 */
function merge_guest_cart(int $userId): void
{
    $db = db();
    $sessionId = session_id();
    
    // Get guest cart items
    $guestItems = $db->fetchAll(
        "SELECT product_id, quantity FROM cart WHERE session_id = ? AND user_id IS NULL",
        [$sessionId]
    );
    
    foreach ($guestItems as $item) {
        // Check if user already has this product in cart
        $existing = $db->fetchOne(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$userId, $item['product_id']]
        );
        
        if ($existing) {
            // Update quantity
            $db->update(
                'cart',
                ['quantity' => $existing['quantity'] + $item['quantity']],
                'id = ?',
                [$existing['id']]
            );
        } else {
            // Add to user's cart
            $db->update(
                'cart',
                ['user_id' => $userId, 'session_id' => null],
                'session_id = ? AND product_id = ?',
                [$sessionId, $item['product_id']]
            );
        }
    }
    
    // Delete remaining guest cart items
    $db->delete('cart', 'session_id = ? AND user_id IS NULL', [$sessionId]);
}

// =============================================================================
// PRODUCT HELPERS
// =============================================================================

/**
 * Increment product view count
 */
function increment_product_views(int $productId): void
{
    $db = db();
    $today = date('Y-m-d');
    
    // Try to update existing record
    $result = $db->query(
        "UPDATE product_views SET view_count = view_count + 1 WHERE product_id = ? AND view_date = ?",
        [$productId, $today]
    );
    
    if ($result->rowCount() === 0) {
        // Insert new record
        try {
            $db->insert('product_views', [
                'product_id' => $productId,
                'view_date' => $today,
                'view_count' => 1
            ]);
        } catch (Exception $e) {
            // Ignore duplicate key errors
        }
    }
    
    // Update total views on product
    $db->query(
        "UPDATE products SET views_count = views_count + 1 WHERE id = ?",
        [$productId]
    );
}

/**
 * Get product rating statistics
 */
function get_product_rating(int $productId): array
{
    $db = db();
    
    $stats = $db->fetchOne(
        "SELECT 
            AVG(rating) as average,
            COUNT(*) as count
        FROM reviews 
        WHERE product_id = ? AND is_approved = 1",
        [$productId]
    );
    
    return [
        'average' => round((float)$stats['average'], 1),
        'count' => (int)$stats['count']
    ];
}

// =============================================================================
// MISCELLANEOUS
// =============================================================================

/**
 * Check if request is AJAX
 */
function is_ajax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Return JSON response
 */
function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Require user to be logged in
 */
function require_login(): void
{
    if (!Session::isLoggedIn()) {
        Session::setIntendedUrl(current_url());
        flash_error('Please login to continue.');
        redirect(url('login.php'));
    }
}

/**
 * Require admin to be logged in
 */
function require_admin(): void
{
    if (!Session::isAdminLoggedIn()) {
        redirect(url('admin/index.php'));
    }
}

/**
 * Check admin permission
 */
function require_role(string $role): void
{
    require_admin();
    
    if (!Session::hasRole($role)) {
        flash_error('You do not have permission to access this area.');
        redirect(url('admin/dashboard.php'));
    }
}

/**
 * Validate CSRF token for POST requests
 */
function validate_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (!Security::validateCSRFToken($token)) {
            if (is_ajax()) {
                json_response(['error' => 'Invalid security token. Please refresh the page.'], 403);
            }
            flash_error('Invalid security token. Please try again.');
            back();
        }
    }
}

/**
 * Generate breadcrumb HTML
 */
function breadcrumbs(array $items): string
{
    $html = '<nav class="flex text-sm mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">';
    
    $total = count($items);
    $i = 0;
    
    foreach ($items as $label => $url) {
        $i++;
        $isLast = $i === $total;
        
        if ($i > 1) {
            $html .= '<li class="flex items-center">
                <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>';
        } else {
            $html .= '<li class="inline-flex items-center">';
        }
        
        if ($isLast || empty($url)) {
            $html .= '<span class="text-gray-500">' . e($label) . '</span>';
        } else {
            $html .= '<a href="' . e($url) . '" class="text-indigo-600 hover:text-indigo-800">' . e($label) . '</a>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}
