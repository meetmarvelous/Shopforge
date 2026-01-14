<?php
/**
 * Cart AJAX Handler
 * 
 * Handles add, update, remove, and clear cart operations via AJAX.
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

$action = post('action', '');
$db = db();

try {
    switch ($action) {
        case 'add':
            $productId = get_int('product_id', 0);
            $quantity = max(1, get_int('quantity', 1));
            
            if ($productId <= 0) {
                json_response(['error' => 'Invalid product'], 400);
            }
            
            // Check product exists and has stock
            $product = $db->fetchOne(
                "SELECT id, name, stock_quantity FROM products WHERE id = ? AND is_active = 1",
                [$productId]
            );
            
            if (!$product) {
                json_response(['error' => 'Product not found'], 404);
            }
            
            if ($product['stock_quantity'] <= 0) {
                json_response(['error' => 'Product is out of stock'], 400);
            }
            
            // Check for existing cart item
            if (Session::isLoggedIn()) {
                $existing = $db->fetchOne(
                    "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
                    [Session::getUserId(), $productId]
                );
            } else {
                $existing = $db->fetchOne(
                    "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND user_id IS NULL",
                    [session_id(), $productId]
                );
            }
            
            if ($existing) {
                // Update quantity
                $newQty = min($existing['quantity'] + $quantity, $product['stock_quantity']);
                $db->update('cart', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
            } else {
                // Insert new cart item
                $quantity = min($quantity, $product['stock_quantity']);
                $data = [
                    'product_id' => $productId,
                    'quantity' => $quantity
                ];
                
                if (Session::isLoggedIn()) {
                    $data['user_id'] = Session::getUserId();
                } else {
                    $data['session_id'] = session_id();
                }
                
                $db->insert('cart', $data);
            }
            
            json_response([
                'success' => true,
                'message' => 'Added to cart!',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        case 'update':
            $cartId = get_int('cart_id', 0);
            $quantity = get_int('quantity', 1);
            
            if ($cartId <= 0) {
                json_response(['error' => 'Invalid cart item'], 400);
            }
            
            // Verify ownership and get product info
            if (Session::isLoggedIn()) {
                $cartItem = $db->fetchOne(
                    "SELECT c.*, p.stock_quantity FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.id = ? AND c.user_id = ?",
                    [$cartId, Session::getUserId()]
                );
            } else {
                $cartItem = $db->fetchOne(
                    "SELECT c.*, p.stock_quantity FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.id = ? AND c.session_id = ? AND c.user_id IS NULL",
                    [$cartId, session_id()]
                );
            }
            
            if (!$cartItem) {
                json_response(['error' => 'Cart item not found'], 404);
            }
            
            if ($quantity <= 0) {
                // Remove item
                $db->delete('cart', 'id = ?', [$cartId]);
            } else {
                // Update quantity (ensure within stock limits)
                $quantity = min($quantity, $cartItem['stock_quantity']);
                $db->update('cart', ['quantity' => $quantity], 'id = ?', [$cartId]);
            }
            
            json_response([
                'success' => true,
                'message' => 'Cart updated',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        case 'remove':
            $cartId = get_int('cart_id', 0);
            
            if ($cartId <= 0) {
                json_response(['error' => 'Invalid cart item'], 400);
            }
            
            // Verify ownership
            if (Session::isLoggedIn()) {
                $db->delete('cart', 'id = ? AND user_id = ?', [$cartId, Session::getUserId()]);
            } else {
                $db->delete('cart', 'id = ? AND session_id = ? AND user_id IS NULL', [$cartId, session_id()]);
            }
            
            json_response([
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        case 'clear':
            if (Session::isLoggedIn()) {
                $db->delete('cart', 'user_id = ?', [Session::getUserId()]);
            } else {
                $db->delete('cart', 'session_id = ? AND user_id IS NULL', [session_id()]);
            }
            
            json_response([
                'success' => true,
                'message' => 'Cart cleared',
                'cart_count' => 0
            ]);
            break;
            
        case 'count':
            json_response([
                'success' => true,
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
