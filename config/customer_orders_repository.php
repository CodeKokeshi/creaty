<?php

function customer_orders_repository_path()
{
    return __DIR__ . '/customer_orders.json';
}

function customer_order_generate_id()
{
    try {
        return 'ord-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));
    } catch (Throwable $error) {
        return 'ord-' . gmdate('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8);
    }
}

function customer_order_allowed_status_tokens()
{
    return ['pending', 'approved', 'ongoing', 'return', 'completed', 'canceled', 'rejected', 'refunded'];
}

function customer_order_status_requires_reason($statusToken)
{
    return in_array((string) $statusToken, ['canceled', 'rejected', 'refunded'], true);
}

function customer_order_is_terminal_status($statusToken)
{
    return in_array((string) $statusToken, ['canceled', 'rejected', 'refunded'], true);
}

function customer_order_payment_receipt_timeout_seconds()
{
    return 10 * 60;
}

function customer_order_payment_receipt_timeout_reason()
{
    return 'Failure to upload payment receipt.';
}

function normalize_customer_order_status_token($value)
{
    $status = strtolower(trim((string) $value));
    $status = preg_replace('/[^a-z0-9-]+/', '-', $status) ?? $status;
    $status = trim((string) $status, '-');

    if ($status === 'confirmed') {
        $status = 'approved';
    } elseif ($status === 'past-return') {
        $status = 'return';
    }

    if (!in_array($status, customer_order_allowed_status_tokens(), true)) {
        $status = 'pending';
    }

    return $status;
}

function normalize_customer_order_status($value)
{
    $statusToken = normalize_customer_order_status_token($value);

    if ($statusToken === 'return') {
        return 'Return';
    }

    return ucfirst($statusToken);
}

function normalize_customer_order_date($value)
{
    $normalized = trim((string) $value);

    if ($normalized === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
        return '';
    }

    return $normalized;
}

function normalize_customer_order_time($value)
{
    $normalized = trim((string) $value);

    if ($normalized === '' || !preg_match('/^\d{2}:\d{2}$/', $normalized)) {
        return '';
    }

    return $normalized;
}

function normalize_customer_order_payment_method($value)
{
    $method = strtolower(trim((string) $value));
    $allowed = ['gcash', 'cash-pickup', 'cash-meetup'];

    if (!in_array($method, $allowed, true)) {
        return '';
    }

    return $method;
}

function normalize_customer_order_method($value, $allowed)
{
    $method = strtolower(trim((string) $value));

    if (!in_array($method, $allowed, true)) {
        return '';
    }

    return $method;
}

function normalize_customer_order_receiving_method($value)
{
    return normalize_customer_order_method($value, ['pickup', 'meetup', 'delivery']);
}

function normalize_customer_order_returning_method($value)
{
    return normalize_customer_order_method($value, ['pickup', 'meetup', 'delivery']);
}

function normalize_customer_order_courier($value)
{
    return normalize_customer_order_method($value, ['lalamove', 'grab-express', 'lbc', 'j-and-t', 'self-booked']);
}

function normalize_customer_order_canceled_by($value)
{
    return normalize_customer_order_method($value, ['admin', 'customer', 'system']);
}

function normalize_customer_order_cancel_reason($value)
{
    $reason = trim((string) $value);
    $reason = preg_replace('/\s+/', ' ', $reason) ?? $reason;

    if ($reason === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return trim((string) mb_substr($reason, 0, 500));
    }

    return trim((string) substr($reason, 0, 500));
}

function normalize_customer_order_asset_path($value)
{
    $path = trim((string) $value);
    $path = str_replace('\\', '/', $path);

    if ($path === '' || strpos($path, '..') !== false) {
        return '';
    }

    $path = ltrim($path, '/');

    if (!preg_match('/^[a-zA-Z0-9_\-\/.]+$/', $path)) {
        return '';
    }

    return $path;
}

function normalize_customer_order_receipt_uploaded_at($value)
{
    $timestamp = trim((string) $value);

    if ($timestamp === '') {
        return '';
    }

    return $timestamp;
}

function customer_order_requires_payment_review($record)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');

    return $statusToken === 'pending' && $paymentMethod === 'gcash' && $receiptPath !== '';
}

function customer_order_payment_receipt_deadline_timestamp($record)
{
    if (!is_array($record)) {
        return null;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');
    $createdAtRaw = trim((string) ($record['created_at'] ?? ''));

    if ($statusToken !== 'pending' || $paymentMethod !== 'gcash' || $receiptPath !== '' || $createdAtRaw === '') {
        return null;
    }

    $createdAtTimestamp = strtotime($createdAtRaw);

    if ($createdAtTimestamp === false) {
        return null;
    }

    return (int) $createdAtTimestamp + customer_order_payment_receipt_timeout_seconds();
}

function expire_customer_orders_missing_payment_receipts($orders, &$didExpire = false, $nowTimestamp = null, &$expiredOrders = null)
{
    $didExpire = false;

    if (!is_array($expiredOrders)) {
        $expiredOrders = [];
    }

    if (!is_array($orders)) {
        return [];
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();
    $cancelReason = customer_order_payment_receipt_timeout_reason();

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        $deadlineTimestamp = customer_order_payment_receipt_deadline_timestamp($record);

        if ($deadlineTimestamp === null || $deadlineTimestamp > $currentTimestamp) {
            continue;
        }

        $record['status'] = 'canceled';
        $record['cancel_reason'] = $cancelReason;
        $record['canceled_by'] = 'system';
        $normalizedRecord = normalize_customer_order_record($record);
        $orders[$index] = $normalizedRecord;
        $expiredOrders[] = $normalizedRecord;
        $didExpire = true;
    }

    return $orders;
}

function normalize_customer_order_items($items)
{
    if (!is_array($items)) {
        return [];
    }

    $normalized = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            $name = 'Item';
        }

        $qty = (int) ($item['qty'] ?? 1);
        $days = (int) ($item['days'] ?? 1);

        if ($qty < 1) {
            $qty = 1;
        }

        if ($days < 1) {
            $days = 1;
        }

        $normalized[] = [
            'name' => $name,
            'qty' => $qty,
            'days' => $days,
        ];
    }

    return $normalized;
}

function normalize_customer_order_record($record)
{
    if (!is_array($record)) {
        $record = [];
    }

    $customerId = trim((string) ($record['customer_id'] ?? ''));
    $customerName = trim((string) ($record['customer_name'] ?? ''));
    $customerEmail = trim((string) ($record['customer_email'] ?? ''));
    $items = normalize_customer_order_items($record['items'] ?? []);

    $id = trim((string) ($record['id'] ?? ''));
    if ($id === '') {
        $id = customer_order_generate_id();
    }

    $createdAt = trim((string) ($record['created_at'] ?? ''));
    if ($createdAt === '') {
        $createdAt = gmdate('c');
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $statusLabel = normalize_customer_order_status($statusToken);

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? '');
    $returningMethod = normalize_customer_order_returning_method($record['returning_method'] ?? '');
    $courier = normalize_customer_order_courier($record['courier'] ?? '');
    $cancelReason = normalize_customer_order_cancel_reason($record['cancel_reason'] ?? '');
    $canceledBy = normalize_customer_order_canceled_by($record['canceled_by'] ?? '');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $paymentReceiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? $record['paymentReceiptPath'] ?? '');
    $paymentReceiptUploadedAt = normalize_customer_order_receipt_uploaded_at($record['payment_receipt_uploaded_at'] ?? $record['paymentReceiptUploadedAt'] ?? '');
    $refundProofPath = normalize_customer_order_asset_path($record['refund_proof_path'] ?? $record['refundProofPath'] ?? '');
    $refundProofUploadedAt = normalize_customer_order_receipt_uploaded_at($record['refund_proof_uploaded_at'] ?? $record['refundProofUploadedAt'] ?? '');

    if ($receivingMethod !== 'delivery' && $returningMethod !== 'delivery') {
        $courier = '';
    }

    if (!customer_order_status_requires_reason($statusToken)) {
        $cancelReason = '';
        $canceledBy = '';
    } elseif ($canceledBy === '') {
        $canceledBy = 'admin';
    }

    if ($paymentMethod !== 'gcash') {
        $paymentReceiptPath = '';
        $paymentReceiptUploadedAt = '';
    } elseif ($paymentReceiptPath === '') {
        $paymentReceiptUploadedAt = '';
    } elseif ($paymentReceiptUploadedAt === '') {
        $paymentReceiptUploadedAt = gmdate('c');
    }

    if ($statusToken !== 'refunded') {
        $refundProofPath = '';
        $refundProofUploadedAt = '';
    } elseif ($refundProofPath === '') {
        $refundProofUploadedAt = '';
    } elseif ($refundProofUploadedAt === '') {
        $refundProofUploadedAt = gmdate('c');
    }

    return [
        'id' => $id,
        'customer_id' => $customerId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'status' => $statusLabel,
        'items' => $items,
        'receive_date' => normalize_customer_order_date($record['receive_date'] ?? ''),
        'receive_time' => normalize_customer_order_time($record['receive_time'] ?? ''),
        'return_date' => normalize_customer_order_date($record['return_date'] ?? ''),
        'return_time' => normalize_customer_order_time($record['return_time'] ?? ''),
        'place' => trim((string) ($record['place'] ?? '')),
        'receiving_method' => $receivingMethod,
        'returning_method' => $returningMethod,
        'courier' => $courier,
        'cancel_reason' => $cancelReason,
        'canceled_by' => $canceledBy,
        'payment_method' => $paymentMethod,
        'payment_receipt_path' => $paymentReceiptPath,
        'payment_receipt_uploaded_at' => $paymentReceiptUploadedAt,
        'refund_proof_path' => $refundProofPath,
        'refund_proof_uploaded_at' => $refundProofUploadedAt,
        'created_at' => $createdAt,
    ];
}

function load_customer_orders_repository()
{
    $path = customer_orders_repository_path();

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return [];
    }

    $orders = [];

    foreach ($decoded as $record) {
        $normalized = normalize_customer_order_record($record);

        if ($normalized['customer_id'] === '') {
            continue;
        }

        $orders[] = $normalized;
    }

    $didExpireOrders = false;
    $expiredOrders = [];
    $orders = expire_customer_orders_missing_payment_receipts($orders, $didExpireOrders, null, $expiredOrders);

    if ($didExpireOrders) {
        save_customer_orders_repository($orders);

        if (!function_exists('append_customer_order_status_notification')) {
            require_once __DIR__ . '/customer_notifications_repository.php';
        }

        if (function_exists('append_customer_order_status_notification')) {
            foreach ($expiredOrders as $expiredOrder) {
                if (!is_array($expiredOrder)) {
                    continue;
                }

                append_customer_order_status_notification(
                    (string) ($expiredOrder['customer_id'] ?? ''),
                    (string) ($expiredOrder['id'] ?? ''),
                    normalize_customer_order_status_token($expiredOrder['status'] ?? 'pending'),
                    (string) ($expiredOrder['status'] ?? ''),
                    (string) ($expiredOrder['cancel_reason'] ?? '')
                );
            }
        }
    }

    return $orders;
}

function save_customer_orders_repository($orders)
{
    if (!is_array($orders)) {
        return false;
    }

    $normalized = [];

    foreach ($orders as $record) {
        $next = normalize_customer_order_record($record);

        if ($next['customer_id'] === '') {
            continue;
        }

        $normalized[] = $next;
    }

    $encoded = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents(customer_orders_repository_path(), $encoded . PHP_EOL, LOCK_EX) !== false;
}

function find_customer_order_record_by_id($orderId)
{
    $targetOrderId = trim((string) $orderId);

    if ($targetOrderId === '') {
        return null;
    }

    foreach (load_customer_orders_repository() as $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        return $record;
    }

    return null;
}

function customer_order_requires_payment_receipt($record)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');

    return $statusToken === 'pending' && $paymentMethod === 'gcash' && $receiptPath === '';
}

function customer_orders_live_state_signature($orders)
{
    if (!is_array($orders)) {
        return '';
    }

    $signatureRows = [];

    foreach ($orders as $record) {
        if (!is_array($record)) {
            continue;
        }

        $orderId = trim((string) ($record['id'] ?? ''));
        if ($orderId === '') {
            continue;
        }

        $signatureRows[] = [
            'id' => $orderId,
            'status' => normalize_customer_order_status_token($record['status'] ?? 'pending'),
            'cancel_reason' => normalize_customer_order_cancel_reason($record['cancel_reason'] ?? ''),
            'canceled_by' => normalize_customer_order_canceled_by($record['canceled_by'] ?? ''),
            'payment_receipt_path' => normalize_customer_order_asset_path($record['payment_receipt_path'] ?? ''),
            'payment_receipt_uploaded_at' => normalize_customer_order_receipt_uploaded_at($record['payment_receipt_uploaded_at'] ?? ''),
            'refund_proof_path' => normalize_customer_order_asset_path($record['refund_proof_path'] ?? ''),
            'refund_proof_uploaded_at' => normalize_customer_order_receipt_uploaded_at($record['refund_proof_uploaded_at'] ?? ''),
        ];
    }

    $encodedRows = json_encode($signatureRows, JSON_UNESCAPED_SLASHES);

    if (!is_string($encodedRows)) {
        return '';
    }

    return sha1($encodedRows);
}

function customer_orders_live_state_signature_for_customer($customerId)
{
    $targetCustomerId = trim((string) $customerId);

    if ($targetCustomerId === '') {
        return '';
    }

    $filteredOrders = [];

    foreach (load_customer_orders_repository() as $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['customer_id'] ?? '') !== $targetCustomerId) {
            continue;
        }

        $filteredOrders[] = $record;
    }

    return customer_orders_live_state_signature($filteredOrders);
}

function map_customer_order_for_frontend($record)
{
    if (!is_array($record)) {
        return [];
    }

    $paymentReceiptDeadlineTimestamp = customer_order_payment_receipt_deadline_timestamp($record);
    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

    return [
        'id' => (string) ($record['id'] ?? ''),
        'status' => (string) ($record['status'] ?? 'Pending'),
        'statusToken' => $statusToken,
        'items' => is_array($record['items'] ?? null) ? $record['items'] : [],
        'receiveDate' => (string) ($record['receive_date'] ?? ''),
        'receiveTime' => (string) ($record['receive_time'] ?? ''),
        'returnDate' => (string) ($record['return_date'] ?? ''),
        'returnTime' => (string) ($record['return_time'] ?? ''),
        'place' => (string) ($record['place'] ?? ''),
        'receivingMethod' => (string) ($record['receiving_method'] ?? ''),
        'returningMethod' => (string) ($record['returning_method'] ?? ''),
        'courier' => (string) ($record['courier'] ?? ''),
        'cancelReason' => (string) ($record['cancel_reason'] ?? ''),
        'cancelBy' => (string) ($record['canceled_by'] ?? ''),
        'paymentMethod' => (string) ($record['payment_method'] ?? ''),
        'paymentReceiptPath' => (string) ($record['payment_receipt_path'] ?? ''),
        'paymentReceiptUploadedAt' => (string) ($record['payment_receipt_uploaded_at'] ?? ''),
        'refundProofPath' => (string) ($record['refund_proof_path'] ?? ''),
        'refundProofUploadedAt' => (string) ($record['refund_proof_uploaded_at'] ?? ''),
        'paymentReceiptDeadlineAt' => $paymentReceiptDeadlineTimestamp !== null ? gmdate('c', $paymentReceiptDeadlineTimestamp) : '',
        'paymentReceiptTimeoutSeconds' => customer_order_payment_receipt_timeout_seconds(),
        'createdAt' => (string) ($record['created_at'] ?? ''),
    ];
}

function load_customer_orders_for_customer($customerId)
{
    $targetCustomerId = trim((string) $customerId);

    if ($targetCustomerId === '') {
        return [];
    }

    $allOrders = load_customer_orders_repository();
    $filtered = [];

    foreach ($allOrders as $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['customer_id'] ?? '') !== $targetCustomerId) {
            continue;
        }

        $filtered[] = map_customer_order_for_frontend($record);
    }

    usort($filtered, function ($a, $b) {
        $first = (string) ($a['createdAt'] ?? '');
        $second = (string) ($b['createdAt'] ?? '');

        return strcmp($second, $first);
    });

    return $filtered;
}

function append_customer_order_for_customer($customerId, $customerName, $customerEmail, $orderPayload)
{
    $targetCustomerId = trim((string) $customerId);

    if ($targetCustomerId === '') {
        return null;
    }

    $payload = is_array($orderPayload) ? $orderPayload : [];
    $items = normalize_customer_order_items($payload['items'] ?? []);

    if ($items === []) {
        return null;
    }

    $allOrders = load_customer_orders_repository();

    $newOrder = normalize_customer_order_record([
        'id' => customer_order_generate_id(),
        'customer_id' => $targetCustomerId,
        'customer_name' => trim((string) $customerName),
        'customer_email' => trim((string) $customerEmail),
        'status' => 'pending',
        'items' => $items,
        'receive_date' => $payload['receiveDate'] ?? '',
        'receive_time' => $payload['receiveTime'] ?? '',
        'return_date' => $payload['returnDate'] ?? '',
        'return_time' => $payload['returnTime'] ?? '',
        'place' => $payload['place'] ?? '',
        'receiving_method' => $payload['receivingMethod'] ?? '',
        'returning_method' => $payload['returningMethod'] ?? '',
        'courier' => $payload['courier'] ?? '',
        'cancel_reason' => '',
        'canceled_by' => '',
        'payment_method' => $payload['paymentMethod'] ?? '',
        'payment_receipt_path' => '',
        'payment_receipt_uploaded_at' => '',
        'created_at' => gmdate('c'),
    ]);

    array_unshift($allOrders, $newOrder);

    if (!save_customer_orders_repository($allOrders)) {
        return null;
    }

    return map_customer_order_for_frontend($newOrder);
}

function update_customer_order_status_by_id($orderId, $nextStatus, $cancelReason = '', $canceledBy = 'admin', $options = [])
{
    $targetOrderId = trim((string) $orderId);

    if ($targetOrderId === '') {
        return null;
    }

    $settings = is_array($options) ? $options : [];
    $statusToken = normalize_customer_order_status_token($nextStatus);
    $statusLabel = normalize_customer_order_status($statusToken);
    $statusNeedsReason = customer_order_status_requires_reason($statusToken);
    $normalizedCancelReason = $statusNeedsReason
        ? normalize_customer_order_cancel_reason($cancelReason)
        : '';
    $normalizedCanceledBy = $statusNeedsReason
        ? normalize_customer_order_canceled_by($canceledBy)
        : '';
    $refundProofDataUrl = trim((string) ($settings['refund_proof_data_url'] ?? ''));
    $projectRoot = trim((string) ($settings['project_root'] ?? ''));

    if ($statusNeedsReason && $normalizedCancelReason === '') {
        return null;
    }

    if ($statusNeedsReason && $normalizedCanceledBy === '') {
        $normalizedCanceledBy = 'admin';
    }

    $orders = load_customer_orders_repository();
    $updatedOrder = null;
    $didStatusChange = false;

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        $isWaitingForPaymentReceipt = customer_order_requires_payment_receipt($record);
        $isWaitingForPaymentReview = customer_order_requires_payment_review($record);

        // Terminal bookings cannot transition to any other state.
        if (customer_order_is_terminal_status($currentStatusToken)) {
            return null;
        }

        // While waiting for a GCash receipt, admin can only cancel.
        if ($isWaitingForPaymentReceipt && $statusToken !== 'canceled') {
            return null;
        }

        // During receipt review, admin can only approve, reject, or refund.
        if ($isWaitingForPaymentReview && !in_array($statusToken, ['approved', 'rejected', 'refunded'], true)) {
            return null;
        }

        if ($statusToken === 'rejected' && !$isWaitingForPaymentReview) {
            return null;
        }

        if ($statusToken === 'refunded') {
            if (!$isWaitingForPaymentReview || $refundProofDataUrl === '' || $projectRoot === '') {
                return null;
            }

            try {
                $record['refund_proof_path'] = save_customer_order_refund_proof_from_data_url($refundProofDataUrl, $projectRoot, $targetOrderId);
                $record['refund_proof_uploaded_at'] = gmdate('c');
            } catch (Throwable $error) {
                return null;
            }
        } else {
            $record['refund_proof_path'] = '';
            $record['refund_proof_uploaded_at'] = '';
        }

        $record['status'] = $statusLabel;
        $record['cancel_reason'] = $normalizedCancelReason;
        $record['canceled_by'] = $normalizedCanceledBy;
        $orders[$index] = normalize_customer_order_record($record);
        $updatedOrder = $orders[$index];
        $didStatusChange = $currentStatusToken !== $statusToken;
        break;
    }

    if ($updatedOrder === null) {
        return null;
    }

    if (!save_customer_orders_repository($orders)) {
        return null;
    }

    if ($didStatusChange) {
        if (!function_exists('append_customer_order_status_notification')) {
            require_once __DIR__ . '/customer_notifications_repository.php';
        }

        if (function_exists('append_customer_order_status_notification')) {
            append_customer_order_status_notification(
                (string) ($updatedOrder['customer_id'] ?? ''),
                (string) ($updatedOrder['id'] ?? $targetOrderId),
                $statusToken,
                (string) ($updatedOrder['status'] ?? $statusLabel),
                (string) ($updatedOrder['cancel_reason'] ?? '')
            );
        }
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function save_customer_order_payment_receipt_from_data_url($imageDataUrl, $projectRoot, $orderId)
{
    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid payment receipt image payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        throw new RuntimeException('Invalid payment receipt image data.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'png'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/payment_receipts';
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access payment receipt directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-receipt.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save payment receipt image.');
    }

    return $targetDirRelative . '/' . $filename;
}

function save_customer_order_refund_proof_from_data_url($imageDataUrl, $projectRoot, $orderId)
{
    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid refund proof image payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        throw new RuntimeException('Invalid refund proof image data.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'png'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/refund_receipts';
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access refund proof directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-refund.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save refund proof image.');
    }

    return $targetDirRelative . '/' . $filename;
}

function upload_customer_order_receipt_for_customer($customerId, $orderId, $imageDataUrl, $projectRoot)
{
    $targetCustomerId = trim((string) $customerId);
    $targetOrderId = trim((string) $orderId);

    if ($targetCustomerId === '' || $targetOrderId === '') {
        return null;
    }

    $orders = load_customer_orders_repository();
    $matchedOrderIndex = -1;

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        if ((string) ($record['customer_id'] ?? '') !== $targetCustomerId) {
            return null;
        }

        $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');

        if ($statusToken !== 'pending' || $paymentMethod !== 'gcash') {
            return null;
        }

        $matchedOrderIndex = $index;
        break;
    }

    if ($matchedOrderIndex < 0) {
        return null;
    }

    $receiptPath = save_customer_order_payment_receipt_from_data_url($imageDataUrl, $projectRoot, $targetOrderId);
    $record = $orders[$matchedOrderIndex];
    $record['payment_receipt_path'] = $receiptPath;
    $record['payment_receipt_uploaded_at'] = gmdate('c');
    $orders[$matchedOrderIndex] = normalize_customer_order_record($record);
    $updatedOrder = $orders[$matchedOrderIndex];

    if (!save_customer_orders_repository($orders)) {
        return null;
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function cancel_pending_gcash_order_for_customer_due_to_receipt_failure($customerId, $orderId, $cancelReason = '')
{
    $targetCustomerId = trim((string) $customerId);
    $targetOrderId = trim((string) $orderId);
    $normalizedReason = normalize_customer_order_cancel_reason($cancelReason);

    if ($normalizedReason === '') {
        $normalizedReason = customer_order_payment_receipt_timeout_reason();
    }

    if ($targetCustomerId === '' || $targetOrderId === '') {
        return null;
    }

    $orders = load_customer_orders_repository();
    $updatedOrder = null;

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        if ((string) ($record['customer_id'] ?? '') !== $targetCustomerId) {
            return null;
        }

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
        $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');

        if ($currentStatusToken === 'canceled') {
            return map_customer_order_for_frontend($record);
        }

        if ($currentStatusToken !== 'pending' || $paymentMethod !== 'gcash' || $receiptPath !== '') {
            return null;
        }

        $record['status'] = 'canceled';
        $record['cancel_reason'] = $normalizedReason;
        $record['canceled_by'] = 'system';
        $orders[$index] = normalize_customer_order_record($record);
        $updatedOrder = $orders[$index];
        break;
    }

    if ($updatedOrder === null) {
        return null;
    }

    if (!save_customer_orders_repository($orders)) {
        return null;
    }

    if (!function_exists('append_customer_order_status_notification')) {
        require_once __DIR__ . '/customer_notifications_repository.php';
    }

    if (function_exists('append_customer_order_status_notification')) {
        append_customer_order_status_notification(
            (string) ($updatedOrder['customer_id'] ?? ''),
            (string) ($updatedOrder['id'] ?? $targetOrderId),
            normalize_customer_order_status_token($updatedOrder['status'] ?? 'canceled'),
            (string) ($updatedOrder['status'] ?? 'Canceled'),
            (string) ($updatedOrder['cancel_reason'] ?? $normalizedReason)
        );
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function cancel_customer_order_for_customer($customerId, $orderId, $cancelReason)
{
    $targetCustomerId = trim((string) $customerId);
    $targetOrderId = trim((string) $orderId);
    $normalizedReason = normalize_customer_order_cancel_reason($cancelReason);

    if ($targetCustomerId === '' || $targetOrderId === '' || $normalizedReason === '') {
        return null;
    }

    $orders = load_customer_orders_repository();
    $updatedOrder = null;

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        if ((string) ($record['customer_id'] ?? '') !== $targetCustomerId) {
            return null;
        }

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

        if (!in_array($currentStatusToken, ['pending', 'approved'], true)) {
            return null;
        }

        $record['status'] = 'canceled';
        $record['cancel_reason'] = $normalizedReason;
        $record['canceled_by'] = 'customer';
        $orders[$index] = normalize_customer_order_record($record);
        $updatedOrder = $orders[$index];
        break;
    }

    if ($updatedOrder === null) {
        return null;
    }

    if (!save_customer_orders_repository($orders)) {
        return null;
    }

    if (!function_exists('append_customer_order_status_notification')) {
        require_once __DIR__ . '/customer_notifications_repository.php';
    }

    if (function_exists('append_customer_order_status_notification')) {
        append_customer_order_status_notification(
            (string) ($updatedOrder['customer_id'] ?? ''),
            (string) ($updatedOrder['id'] ?? $targetOrderId),
            normalize_customer_order_status_token($updatedOrder['status'] ?? 'canceled'),
            (string) ($updatedOrder['status'] ?? 'Canceled'),
            (string) ($updatedOrder['cancel_reason'] ?? $normalizedReason)
        );
    }

    return map_customer_order_for_frontend($updatedOrder);
}
