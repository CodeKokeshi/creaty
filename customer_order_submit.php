<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function customer_order_submit_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    customer_order_submit_respond(405, [
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
}

if (!isset($_SESSION['customer_id']) || isset($_SESSION['user_id'])) {
    customer_order_submit_respond(401, [
        'ok' => false,
        'message' => 'Please log in to place a booking.',
    ]);
}

require_once __DIR__ . '/config/customer_orders_repository.php';

$rawBody = file_get_contents('php://input');
$decodedBody = json_decode((string) $rawBody, true);

$payload = [];

if (is_array($decodedBody)) {
    if (isset($decodedBody['order']) && is_array($decodedBody['order'])) {
        $payload = $decodedBody['order'];
    } else {
        $payload = $decodedBody;
    }
}

$items = $payload['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    customer_order_submit_respond(422, [
        'ok' => false,
        'message' => 'At least one item is required to create a booking.',
    ]);
}

$customerId = (string) ($_SESSION['customer_id'] ?? '');
$customerName = (string) ($_SESSION['customer_name'] ?? ('Customer #' . $customerId));
$customerEmail = (string) ($_SESSION['customer_email'] ?? '');

$createdOrder = append_customer_order_for_customer(
    $customerId,
    $customerName,
    $customerEmail,
    $payload
);

if ($createdOrder === null) {
    customer_order_submit_respond(500, [
        'ok' => false,
        'message' => 'Unable to save your booking right now.',
    ]);
}

customer_order_submit_respond(200, [
    'ok' => true,
    'message' => 'Booking saved as pending.',
    'order' => $createdOrder,
]);
