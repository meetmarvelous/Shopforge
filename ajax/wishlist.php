<?php
/**
 * Wishlist AJAX Handler
 * 
 * Handles add and remove wishlist operations via AJAX.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/../includes/functions.php';

// Only accept POST and AJAX requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_ajax()) {
    json_response(['error' => 'Invalid request'], 400);
}

// Validate CSRF
$token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!Security::validateCSRFToken($token)) {
    json_response(['error' => 'Invalid security token. Please refresh the page.'], 403);
}

// Require login for wishlist
if (!Session::isLoggedIn()) {
    json_response(['error' => 'Please login to use wishlist', 'login_required' => true], 401);
}

$action = post('action', '');
$db = db();
$userId = Session::getUserId();

try {
    switch ($action) {
        case 'add':
            $productId = get_int('product_id', 0);
            
            if ($productId <= 0) {
                json_response(['error' => 'Invalid product'], 400);
            }
            
            // Check product exists
            $product = $db->fetchOne(
                "SELECT id, name FROM products WHERE id = ? AND is_active = 1",
                [$productId]
            );
            
            if (!$product) {
                json_response(['error' => 'Product not found'], 404);
            }
            
            // Check if already in wishlist
            $existing = $db->exists('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            
            if ($existing) {
                json_response(['success' => true, 'message' => 'Already in wishlist']);
            }
            
            // Add to wishlist
            $db->insert('wishlist', [
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            
            json_response([
                'success' => true,
                'message' => 'Added to wishlist!'
            ]);
            break;
            
        case 'remove':
            $productId = get_int('product_id', 0);
            
            if ($productId <= 0) {
                json_response(['error' => 'Invalid product'], 400);
            }
            
            $db->delete('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            
            json_response([
                'success' => true,
                'message' => 'Removed from wishlist'
            ]);
            break;
            
        case 'toggle':
            $productId = get_int('product_id', 0);
            
            if ($productId <= 0) {
                json_response(['error' => 'Invalid product'], 400);
            }
            
            $existing = $db->fetchOne(
                "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
                [$userId, $productId]
            );
            
            if ($existing) {
                $db->delete('wishlist', 'id = ?', [$existing['id']]);
                json_response(['success' => true, 'message' => 'Removed from wishlist', 'in_wishlist' => false]);
            } else {
                $db->insert('wishlist', ['user_id' => $userId, 'product_id' => $productId]);
                json_response(['success' => true, 'message' => 'Added to wishlist!', 'in_wishlist' => true]);
            }
            break;
            
        case 'move_to_cart':
            $productId = get_int('product_id', 0);
            
            if ($productId <= 0) {
                json_response(['error' => 'Invalid product'], 400);
            }
            
            // Check product stock
            $product = $db->fetchOne(
                "SELECT id, stock_quantity FROM products WHERE id = ? AND is_active = 1",
                [$productId]
            );
            
            if (!$product) {
                json_response(['error' => 'Product not found'], 404);
            }
            
            if ($product['stock_quantity'] <= 0) {
                json_response(['error' => 'Product is out of stock'], 400);
            }
            
            // Add to cart
            $existingCart = $db->fetchOne(
                "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
                [$userId, $productId]
            );
            
            if ($existingCart) {
                $newQty = min($existingCart['quantity'] + 1, $product['stock_quantity']);
                $db->update('cart', ['quantity' => $newQty], 'id = ?', [$existingCart['id']]);
            } else {
                $db->insert('cart', [
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'quantity' => 1
                ]);
            }
            
            // Remove from wishlist
            $db->delete('wishlist', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            
            json_response([
                'success' => true,
                'message' => 'Moved to cart!',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        default:
            json_response(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    if (APP_DEBUG) {
        json_response(['error' => $e->getMessage()], 500);
    } else {
        json_response(['error' => 'An error occurred. Please try again.'], 500);
    }
}
