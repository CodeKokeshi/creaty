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
    return ['pending', 'approved', 'ongoing', 'return', 'completed', 'canceled'];
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

    return [
        'id' => $id,
        'customer_id' => $customerId,
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'status' => normalize_customer_order_status($record['status'] ?? 'pending'),
        'items' => $items,
        'receive_date' => normalize_customer_order_date($record['receive_date'] ?? ''),
        'receive_time' => normalize_customer_order_time($record['receive_time'] ?? ''),
        'return_date' => normalize_customer_order_date($record['return_date'] ?? ''),
        'return_time' => normalize_customer_order_time($record['return_time'] ?? ''),
        'place' => trim((string) ($record['place'] ?? '')),
        'receiving_method' => normalize_customer_order_receiving_method($record['receiving_method'] ?? ''),
        'returning_method' => normalize_customer_order_returning_method($record['returning_method'] ?? ''),
        'courier' => normalize_customer_order_courier($record['courier'] ?? ''),
        'payment_method' => normalize_customer_order_payment_method($record['payment_method'] ?? ''),
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

function map_customer_order_for_frontend($record)
{
    if (!is_array($record)) {
        return [];
    }

    return [
        'id' => (string) ($record['id'] ?? ''),
        'status' => (string) ($record['status'] ?? 'Pending'),
        'items' => is_array($record['items'] ?? null) ? $record['items'] : [],
        'receiveDate' => (string) ($record['receive_date'] ?? ''),
        'receiveTime' => (string) ($record['receive_time'] ?? ''),
        'returnDate' => (string) ($record['return_date'] ?? ''),
        'returnTime' => (string) ($record['return_time'] ?? ''),
        'place' => (string) ($record['place'] ?? ''),
        'receivingMethod' => (string) ($record['receiving_method'] ?? ''),
        'returningMethod' => (string) ($record['returning_method'] ?? ''),
        'courier' => (string) ($record['courier'] ?? ''),
        'paymentMethod' => (string) ($record['payment_method'] ?? ''),
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
        'payment_method' => $payload['paymentMethod'] ?? '',
        'created_at' => gmdate('c'),
    ]);

    array_unshift($allOrders, $newOrder);

    if (!save_customer_orders_repository($allOrders)) {
        return null;
    }

    return map_customer_order_for_frontend($newOrder);
}

function update_customer_order_status_by_id($orderId, $nextStatus)
{
    $targetOrderId = trim((string) $orderId);

    if ($targetOrderId === '') {
        return null;
    }

    $statusLabel = normalize_customer_order_status($nextStatus);
    $orders = load_customer_orders_repository();
    $updatedOrder = null;

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        if ((string) ($record['id'] ?? '') !== $targetOrderId) {
            continue;
        }

        $record['status'] = $statusLabel;
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

    return map_customer_order_for_frontend($updatedOrder);
}
