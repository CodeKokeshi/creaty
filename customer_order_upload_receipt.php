<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function customer_order_upload_receipt_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    customer_order_upload_receipt_respond(405, [
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
}

if (!isset($_SESSION['customer_id']) || isset($_SESSION['user_id'])) {
    customer_order_upload_receipt_respond(401, [
        'ok' => false,
        'message' => 'Please log in to upload payment receipts.',
    ]);
}

require_once __DIR__ . '/config/customer_orders_repository.php';
require_once __DIR__ . '/config/customer_gcash_profiles_repository.php';

$rawBody = file_get_contents('php://input');
$decodedBody = json_decode((string) $rawBody, true);
$payload = is_array($decodedBody) ? $decodedBody : [];

if (isset($payload['order']) && is_array($payload['order'])) {
    $payload = $payload['order'];
}

$orderId = trim((string) ($payload['orderId'] ?? ($_POST['order_id'] ?? '')));
$imageDataUrl = trim((string) ($payload['imageDataUrl'] ?? ($_POST['image_data_url'] ?? '')));
$customerGcashName = trim((string) ($payload['customerGcashName'] ?? ($_POST['customer_gcash_name'] ?? '')));
$customerGcashNumber = trim((string) ($payload['customerGcashNumber'] ?? ($_POST['customer_gcash_number'] ?? '')));
$hasCustomerGcashPayload = array_key_exists('customerGcashName', $payload)
    || array_key_exists('customerGcashNumber', $payload)
    || array_key_exists('customer_gcash_name', $_POST)
    || array_key_exists('customer_gcash_number', $_POST);

if ($orderId === '') {
    customer_order_upload_receipt_respond(422, [
        'ok' => false,
        'message' => 'Order ID is required.',
    ]);
}

if ($imageDataUrl === '') {
    customer_order_upload_receipt_respond(422, [
        'ok' => false,
        'message' => 'Please select a payment receipt image to upload.',
    ]);
}

$customerId = (string) ($_SESSION['customer_id'] ?? '');

try {
    $updatedOrder = upload_customer_order_receipt_for_customer(
        $customerId,
        $orderId,
        $imageDataUrl,
        __DIR__
    );
} catch (Throwable $error) {
    $autoCanceledOrder = cancel_pending_gcash_order_for_customer_due_to_receipt_failure(
        $customerId,
        $orderId,
        customer_order_payment_receipt_timeout_reason()
    );

    if (is_array($autoCanceledOrder)) {
        customer_order_upload_receipt_respond(409, [
            'ok' => false,
            'autoCanceled' => true,
            'message' => 'Payment receipt upload failed. Booking has been canceled automatically.',
            'order' => $autoCanceledOrder,
        ]);
    }

    customer_order_upload_receipt_respond(422, [
        'ok' => false,
        'message' => $error->getMessage(),
    ]);
}

if ($updatedOrder === null) {
    customer_order_upload_receipt_respond(409, [
        'ok' => false,
        'message' => 'This booking is not eligible for receipt upload.',
    ]);
}

$updatedCustomerGcashProfile = find_customer_gcash_profile_for_customer($customerId);

if ($hasCustomerGcashPayload) {
    $savedCustomerGcashProfile = upsert_customer_gcash_profile_for_customer(
        $customerId,
        $customerGcashName,
        $customerGcashNumber
    );

    if (is_array($savedCustomerGcashProfile)) {
        $updatedCustomerGcashProfile = $savedCustomerGcashProfile;
    } else {
        $updatedCustomerGcashProfile = find_customer_gcash_profile_for_customer($customerId);
    }
}

customer_order_upload_receipt_respond(200, [
    'ok' => true,
    'message' => 'Payment receipt uploaded successfully.',
    'order' => $updatedOrder,
    'customerGcashProfile' => map_customer_gcash_profile_for_frontend($updatedCustomerGcashProfile),
]);
