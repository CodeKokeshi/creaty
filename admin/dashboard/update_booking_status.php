<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    header('Location: ../');
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ./?admin_view=bookings');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/customer_orders_repository.php';

$orderId = trim((string) ($_POST['order_id'] ?? ''));
$nextStatus = trim((string) ($_POST['next_status'] ?? 'pending'));
$redirectInput = trim((string) ($_POST['redirect'] ?? './?admin_view=bookings'));

$redirectTarget = './?admin_view=bookings';

if ($redirectInput !== ''
    && strpos($redirectInput, '://') === false
    && strpos($redirectInput, "\n") === false
    && strpos($redirectInput, "\r") === false
) {
    $redirectTarget = $redirectInput;
}

if ($orderId === '') {
    header('Location: ' . $redirectTarget);
    exit;
}

$updatedOrder = update_customer_order_status_by_id($orderId, $nextStatus);

if ($updatedOrder === null) {
    header('Location: ' . $redirectTarget);
    exit;
}

header('Location: ' . $redirectTarget);
exit;
