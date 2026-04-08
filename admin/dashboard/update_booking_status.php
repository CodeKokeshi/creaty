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
$cancelReason = trim((string) ($_POST['cancel_reason'] ?? ''));
$refundProofDataUrl = trim((string) ($_POST['refund_proof_data_url'] ?? ''));
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

$nextStatusToken = normalize_customer_order_status_token($nextStatus);
$isReturnedEarlyAction = customer_order_is_returned_early_request($nextStatus);

if ($nextStatusToken === 'awaiting-refund') {
    header('Location: ' . $redirectTarget);
    exit;
}

if (customer_order_status_requires_reason($nextStatusToken) && $cancelReason === '') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($nextStatusToken === 'refunded' && strpos($refundProofDataUrl, 'data:image/') !== 0) {
    header('Location: ' . $redirectTarget);
    exit;
}

$currentOrder = find_customer_order_record_by_id($orderId);

if (!is_array($currentOrder)) {
    header('Location: ' . $redirectTarget);
    exit;
}

$currentStatusToken = normalize_customer_order_status_token($currentOrder['status'] ?? 'pending');
if (customer_order_is_terminal_status($currentStatusToken)) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'awaiting-refund' && $nextStatusToken !== 'refunded') {
    header('Location: ' . $redirectTarget);
    exit;
}

$isWaitingForPaymentReceipt = customer_order_requires_payment_receipt($currentOrder);
if ($isWaitingForPaymentReceipt && $nextStatusToken !== 'canceled') {
    header('Location: ' . $redirectTarget);
    exit;
}

$isWaitingForPaymentReview = customer_order_requires_payment_review($currentOrder);
if ($isWaitingForPaymentReview && !in_array($nextStatusToken, ['approved', 'rejected', 'refunded'], true)) {
    header('Location: ' . $redirectTarget);
    exit;
}

if (
    $currentStatusToken === 'pending'
    && !$isWaitingForPaymentReceipt
    && !$isWaitingForPaymentReview
    && !in_array($nextStatusToken, ['approved', 'canceled'], true)
) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'approved' && $nextStatusToken !== 'canceled') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'ongoing' && (!$isReturnedEarlyAction || $nextStatusToken !== 'completed')) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'return' && $nextStatusToken !== 'completed') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($nextStatusToken === 'completed') {
    if ($isReturnedEarlyAction && $currentStatusToken !== 'ongoing') {
        header('Location: ' . $redirectTarget);
        exit;
    }

    if (!$isReturnedEarlyAction && $currentStatusToken !== 'return') {
        header('Location: ' . $redirectTarget);
        exit;
    }
}

$updatedOrder = update_customer_order_status_by_id(
    $orderId,
    $nextStatus,
    customer_order_status_requires_reason($nextStatusToken) ? $cancelReason : '',
    customer_order_status_requires_reason($nextStatusToken) ? 'admin' : '',
    [
        'refund_proof_data_url' => $nextStatusToken === 'refunded' ? $refundProofDataUrl : '',
        'project_root' => dirname(__DIR__, 2),
    ]
);

if ($updatedOrder === null) {
    header('Location: ' . $redirectTarget);
    exit;
}

header('Location: ' . $redirectTarget);
exit;
