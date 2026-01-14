<?php
/**
 * Payment Callback Handler
 * 
 * Verify Paystack payment and update order status.
 * 
 * @package ShopForge
 */

require_once __DIR__ . '/includes/functions.php';

$reference = get('reference', '');

if (empty($reference)) {
    flash_error('Invalid payment reference.');
    redirect(url('orders.php'));
}

// Verify payment with Paystack API
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Cache-Control: no-cache"
    ]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    flash_error('Unable to verify payment. Please contact support.');
    redirect(url('orders.php'));
}

$result = json_decode($response, true);

if (!$result['status'] || $result['data']['status'] !== 'success') {
    flash_error('Payment verification failed. Please try again or contact support.');
    redirect(url('orders.php'));
}

$db = db();

// Get order from reference (reference format: SF_orderId_timestamp)
$refParts = explode('_', $reference);
$orderId = isset($refParts[1]) ? (int)$refParts[1] : 0;

// Also try to get from session
if (!$orderId) {
    $orderId = Session::get('pending_order_id');
}

if (!$orderId) {
    flash_error('Order not found. Please contact support with reference: ' . e($reference));
    redirect(url('orders.php'));
}

// Verify order belongs to user and is pending
$order = $db->fetchOne(
    "SELECT * FROM orders WHERE id = ? AND payment_status = 'pending'",
    [$orderId]
);

if (!$order) {
    // Payment already processed or order doesn't exist
    flash_info('Payment has already been processed.');
    redirect(url('orders.php'));
}

// Update order status
$db->update('orders', [
    'payment_status' => 'paid',
    'status' => 'processing',
    'payment_method' => 'paystack',
    'payment_reference' => $reference
], 'id = ?', [$orderId]);

// Record payment
$db->insert('payments', [
    'order_id' => $orderId,
    'transaction_reference' => $reference,
    'gateway' => 'paystack',
    'amount' => $result['data']['amount'] / 100, // Convert from kobo
    'currency' => $result['data']['currency'],
    'status' => 'success',
    'gateway_response' => json_encode($result['data']),
    'paid_at' => date('Y-m-d H:i:s')
]);

// Clear session data
Session::remove('pending_order_id');
Session::remove('pending_order_number');
Session::remove('pending_order_total');
Session::remove('pending_order_email');
Session::remove('payment_reference');

// Redirect to success page
flash_success('Payment successful! Your order has been placed.');
redirect(url('order-success.php?order=' . $order['order_number']));
