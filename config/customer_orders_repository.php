<?php

function customer_orders_repository_path()
{
    return __DIR__ . '/customer_orders.json';
}

function customer_order_timezone_name()
{
    return 'Asia/Manila';
}

function customer_order_timezone()
{
    static $timezone = null;

    if ($timezone instanceof DateTimeZone) {
        return $timezone;
    }

    try {
        $timezone = new DateTimeZone(customer_order_timezone_name());
    } catch (Throwable $error) {
        $timezone = new DateTimeZone('UTC');
    }

    return $timezone;
}

function customer_order_datetime_from_timestamp($timestamp = null)
{
    $resolvedTimestamp = is_int($timestamp) ? $timestamp : time();

    return (new DateTimeImmutable('@' . $resolvedTimestamp))
        ->setTimezone(customer_order_timezone());
}

function customer_order_now_iso8601($timestamp = null)
{
    return customer_order_datetime_from_timestamp($timestamp)->format('c');
}

function customer_order_parse_datetime_value($value)
{
    $raw = trim((string) $value);

    if ($raw === '') {
        return null;
    }

    try {
        $parsed = new DateTimeImmutable($raw, customer_order_timezone());
    } catch (Throwable $error) {
        return null;
    }

    return $parsed->setTimezone(customer_order_timezone());
}

function customer_order_normalize_timestamp_value($value)
{
    $raw = trim((string) $value);

    if ($raw === '') {
        return '';
    }

    $parsed = customer_order_parse_datetime_value($raw);

    if (!$parsed instanceof DateTimeImmutable) {
        return $raw;
    }

    return $parsed->format('c');
}

function customer_order_local_date_key_from_timestamp($timestamp)
{
    return customer_order_datetime_from_timestamp((int) $timestamp)->format('Y-m-d');
}

function customer_order_local_hour_from_timestamp($timestamp)
{
    return (int) customer_order_datetime_from_timestamp((int) $timestamp)->format('G');
}

function customer_order_local_datetime_format_from_timestamp($timestamp, $format)
{
    return customer_order_datetime_from_timestamp((int) $timestamp)->format((string) $format);
}

function customer_order_parse_schedule_datetime($dateValue, $timeValue)
{
    $date = normalize_customer_order_date($dateValue);
    $time = normalize_customer_order_time($timeValue);

    if ($date === '' || $time === '' || !customer_order_is_valid_booking_time_slot($time)) {
        return null;
    }

    $schedule = DateTimeImmutable::createFromFormat('Y-m-d H:i', $date . ' ' . $time, customer_order_timezone());
    $errors = DateTimeImmutable::getLastErrors();

    if (
        !$schedule instanceof DateTimeImmutable
        || ((int) ($errors['warning_count'] ?? 0)) > 0
        || ((int) ($errors['error_count'] ?? 0)) > 0
    ) {
        return null;
    }

    return $schedule;
}

function customer_order_generate_id()
{
    try {
        return 'ord-' . customer_order_datetime_from_timestamp()->format('YmdHis') . '-' . bin2hex(random_bytes(4));
    } catch (Throwable $error) {
        return 'ord-' . customer_order_datetime_from_timestamp()->format('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8);
    }
}

function customer_order_allowed_status_tokens()
{
    return ['pending', 'approved', 'ongoing', 'return', 'completed', 'canceled', 'awaiting-refund', 'rejected', 'refunded'];
}

function customer_order_status_requires_reason($statusToken)
{
    return in_array((string) $statusToken, ['canceled', 'awaiting-refund', 'rejected', 'refunded'], true);
}

function customer_order_is_terminal_status($statusToken)
{
    return in_array((string) $statusToken, ['completed', 'canceled', 'rejected', 'refunded'], true);
}

function customer_order_payment_receipt_timeout_seconds()
{
    return 10 * 60;
}

function customer_order_payment_receipt_timeout_reason()
{
    return 'Failure to upload payment receipt.';
}

function customer_order_for_return_grace_seconds()
{
    return 60 * 60;
}

function customer_order_for_return_penalty_per_hour()
{
    return 50;
}

function customer_order_pickup_ready_lead_seconds()
{
    return 60 * 60;
}

function customer_order_pickup_minimum_hour()
{
    return 9;
}

function customer_order_handover_action_tokens()
{
    return ['confirm-pickup-handover', 'confirm-meetup-handover'];
}

function customer_order_handover_mode_from_action($value)
{
    $token = strtolower(trim((string) $value));
    $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?? $token;
    $token = trim((string) $token, '-');

    if ($token === 'confirm-pickup-handover') {
        return 'pickup';
    }

    if ($token === 'confirm-meetup-handover') {
        return 'meetup';
    }

    return '';
}

function customer_order_handover_supported_receiving_methods()
{
    return ['pickup', 'meetup'];
}

function normalize_customer_order_handover_confirmed_at($value)
{
    return customer_order_normalize_timestamp_value($value);
}

function normalize_customer_order_handover_confirmed_by($value)
{
    $token = strtolower(trim((string) $value));

    if ($token === '' || !in_array($token, ['admin', 'system', 'customer'], true)) {
        return '';
    }

    return $token;
}

function customer_order_is_receive_handover_confirmed($record)
{
    if (!is_array($record)) {
        return false;
    }

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? $record['receivingMethod'] ?? '');
    if (!in_array($receivingMethod, customer_order_handover_supported_receiving_methods(), true)) {
        return false;
    }

    $confirmedAt = normalize_customer_order_handover_confirmed_at(
        $record['receive_handover_confirmed_at'] ?? $record['receiveHandoverConfirmedAt'] ?? ''
    );

    return $confirmedAt !== '';
}

function customer_order_delivery_legs()
{
    return ['receive', 'return'];
}

function normalize_customer_order_delivery_leg($value)
{
    $token = strtolower(trim((string) $value));
    $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?? $token;
    $token = trim((string) $token, '-');

    if ($token === 'returning') {
        $token = 'return';
    }

    if (!in_array($token, customer_order_delivery_legs(), true)) {
        return '';
    }

    return $token;
}

function customer_order_delivery_status_tokens_for_leg($leg)
{
    $normalizedLeg = normalize_customer_order_delivery_leg($leg);

    if ($normalizedLeg === 'receive') {
        return ['not-required', 'waiting-proof', 'in-transit', 'closed'];
    }

    if ($normalizedLeg === 'return') {
        return ['not-required', 'waiting-customer-proof', 'in-transit', 'closed'];
    }

    return ['not-required'];
}

function customer_order_delivery_default_status_token_for_leg($leg)
{
    $normalizedLeg = normalize_customer_order_delivery_leg($leg);

    if ($normalizedLeg === 'receive') {
        return 'waiting-proof';
    }

    if ($normalizedLeg === 'return') {
        return 'waiting-customer-proof';
    }

    return 'not-required';
}

function normalize_customer_order_delivery_status_token($value, $leg)
{
    $normalizedLeg = normalize_customer_order_delivery_leg($leg);
    if ($normalizedLeg === '') {
        return '';
    }

    $token = strtolower(trim((string) $value));
    $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?? $token;
    $token = trim((string) $token, '-');

    if ($token === '') {
        return '';
    }

    if (in_array($token, ['notrequired', 'not-required', 'not-required-needed'], true)) {
        $token = 'not-required';
    }

    if ($normalizedLeg === 'receive' && in_array($token, ['awaiting-dispatch', 'waiting-dispatch', 'pending-proof'], true)) {
        $token = 'waiting-proof';
    }

    if ($normalizedLeg === 'return' && in_array($token, ['awaiting-customer-shipment', 'waiting-customer-shipment'], true)) {
        $token = 'waiting-customer-proof';
    }

    if (in_array($token, ['transit', 'shipped'], true)) {
        $token = 'in-transit';
    }

    if (in_array($token, ['delivered', 'received-back', 'received', 'done'], true)) {
        $token = 'closed';
    }

    if (!in_array($token, customer_order_delivery_status_tokens_for_leg($normalizedLeg), true)) {
        return '';
    }

    return $token;
}

function normalize_customer_order_delivery_actor($value)
{
    return normalize_customer_order_method($value, ['admin', 'customer', 'system']);
}

function normalize_customer_order_delivery_reference($value)
{
    $reference = trim((string) $value);
    $reference = preg_replace('/\s+/', ' ', $reference) ?? $reference;

    if ($reference === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return trim((string) mb_substr($reference, 0, 120));
    }

    return trim((string) substr($reference, 0, 120));
}

function normalize_customer_order_delivery_notes($value)
{
    $notes = trim((string) $value);
    $notes = preg_replace('/\s+/', ' ', $notes) ?? $notes;

    if ($notes === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return trim((string) mb_substr($notes, 0, 500));
    }

    return trim((string) substr($notes, 0, 500));
}

function normalize_customer_order_delivery_receipt_uploaded_at($value)
{
    return customer_order_normalize_timestamp_value($value);
}

function normalize_customer_order_delivery_closed_at($value)
{
    return customer_order_normalize_timestamp_value($value);
}

function customer_order_open_reservation_end_timestamp()
{
    return 2147483647;
}

function customer_order_returned_early_status_aliases()
{
    return ['returned-early', 'return-early', 'early-return'];
}

function customer_order_is_returned_early_request($value)
{
    $status = strtolower(trim((string) $value));
    $status = preg_replace('/[^a-z0-9-]+/', '-', $status) ?? $status;
    $status = trim((string) $status, '-');

    return in_array($status, customer_order_returned_early_status_aliases(), true);
}

function normalize_customer_order_status_token($value)
{
    $status = strtolower(trim((string) $value));
    $status = preg_replace('/[^a-z0-9-]+/', '-', $status) ?? $status;
    $status = trim((string) $status, '-');

    if ($status === 'confirmed') {
        $status = 'approved';
    } elseif ($status === 'for-return') {
        $status = 'return';
    } elseif ($status === 'past-return') {
        $status = 'return';
    } elseif (in_array($status, customer_order_returned_early_status_aliases(), true)) {
        $status = 'completed';
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
        return 'For Return';
    }

    if ($statusToken === 'awaiting-refund') {
        return 'Awaiting Refund';
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

function customer_order_booking_shop_open_hour()
{
    return 8;
}

function customer_order_booking_shop_close_hour()
{
    return 17;
}

function customer_order_booking_same_day_cutoff_hour()
{
    return 15;
}

function customer_order_booking_lead_hours()
{
    return 2;
}

function customer_order_booking_time_slot_hour($timeValue)
{
    $time = normalize_customer_order_time($timeValue);

    if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)) {
        return null;
    }

    $hour = (int) ($matches[1] ?? -1);
    $minute = (int) ($matches[2] ?? -1);

    if ($hour < 0 || $hour > 23 || $minute !== 0) {
        return null;
    }

    return $hour;
}

function customer_order_is_valid_booking_time_slot($timeValue)
{
    $hour = customer_order_booking_time_slot_hour($timeValue);

    if ($hour === null) {
        return false;
    }

    return $hour >= customer_order_booking_shop_open_hour()
        && $hour <= customer_order_booking_shop_close_hour();
}

function customer_order_min_receiving_date_by_now($nowTimestamp = null)
{
    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();
    $todayDate = customer_order_local_date_key_from_timestamp($currentTimestamp);
    $currentHour = customer_order_local_hour_from_timestamp($currentTimestamp);

    if ($currentHour >= customer_order_booking_same_day_cutoff_hour()) {
        return customer_order_local_date_key_from_timestamp($currentTimestamp + 86400);
    }

    return $todayDate;
}

function customer_order_is_valid_receiving_schedule($receiveDateValue, $receiveTimeValue, $nowTimestamp = null, $receivingMethod = '')
{
    $receiveDate = normalize_customer_order_date($receiveDateValue);
    $receiveTime = normalize_customer_order_time($receiveTimeValue);
    $receivingMethodToken = normalize_customer_order_receiving_method($receivingMethod);

    if ($receiveDate === '' || $receiveTime === '' || !customer_order_is_valid_booking_time_slot($receiveTime)) {
        return false;
    }

    $selectedHour = customer_order_booking_time_slot_hour($receiveTime);
    if ($selectedHour === null) {
        return false;
    }

    if ($receivingMethodToken === 'pickup' && $selectedHour < customer_order_pickup_minimum_hour()) {
        return false;
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();
    $todayDate = customer_order_local_date_key_from_timestamp($currentTimestamp);
    $minimumDate = customer_order_min_receiving_date_by_now($currentTimestamp);

    if ($receiveDate < $minimumDate) {
        return false;
    }

    if ($receiveDate !== $todayDate) {
        return true;
    }

    $currentHour = customer_order_local_hour_from_timestamp($currentTimestamp);

    if ($currentHour >= customer_order_booking_same_day_cutoff_hour()) {
        return false;
    }

    $minimumHour = max(
        customer_order_booking_shop_open_hour(),
        $currentHour + customer_order_booking_lead_hours()
    );

    if ($receivingMethodToken === 'pickup') {
        $minimumHour = max($minimumHour, customer_order_pickup_minimum_hour());
    }

    return $selectedHour >= $minimumHour
        && $selectedHour <= customer_order_booking_shop_close_hour();
}

function customer_order_receiving_timestamp($record)
{
    if (!is_array($record)) {
        return null;
    }

    return customer_order_schedule_timestamp(
        $record['receive_date'] ?? '',
        $record['receive_time'] ?? ''
    );
}

function customer_order_return_schedule_timestamp($record)
{
    if (!is_array($record)) {
        return null;
    }

    return customer_order_schedule_timestamp(
        $record['return_date'] ?? '',
        $record['return_time'] ?? ''
    );
}

function customer_order_for_return_state($record, $nowTimestamp = null)
{
    $defaultState = [
        'active' => false,
        'deadline_ts' => null,
        'remaining_seconds' => 0,
        'overdue_seconds' => 0,
        'penalty_hours' => 0,
        'penalty_amount' => 0,
        'penalty_per_hour' => customer_order_for_return_penalty_per_hour(),
        'grace_seconds' => customer_order_for_return_grace_seconds(),
    ];

    if (!is_array($record)) {
        return $defaultState;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

    if ($statusToken !== 'return') {
        return $defaultState;
    }

    $returnTimestamp = customer_order_return_schedule_timestamp($record);

    if ($returnTimestamp === null) {
        return $defaultState;
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();
    $graceSeconds = customer_order_for_return_grace_seconds();
    $deadlineTimestamp = $returnTimestamp + $graceSeconds;
    $remainingSeconds = max(0, $deadlineTimestamp - $currentTimestamp);
    $overdueSeconds = max(0, $currentTimestamp - $deadlineTimestamp);
    $penaltyHours = $overdueSeconds > 0
        ? (int) ceil($overdueSeconds / 3600)
        : 0;
    $penaltyPerHour = customer_order_for_return_penalty_per_hour();

    return [
        'active' => true,
        'deadline_ts' => $deadlineTimestamp,
        'remaining_seconds' => $remainingSeconds,
        'overdue_seconds' => $overdueSeconds,
        'penalty_hours' => $penaltyHours,
        'penalty_amount' => $penaltyHours * $penaltyPerHour,
        'penalty_per_hour' => $penaltyPerHour,
        'grace_seconds' => $graceSeconds,
    ];
}

function advance_customer_orders_to_ongoing_by_receiving_schedule($orders, &$didAdvance = false, $nowTimestamp = null, &$advancedOrders = null)
{
    $didAdvance = false;

    if (!is_array($advancedOrders)) {
        $advancedOrders = [];
    }

    if (!is_array($orders)) {
        return [];
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        if ($statusToken !== 'approved') {
            continue;
        }

        $receivingTimestamp = customer_order_receiving_timestamp($record);

        if ($receivingTimestamp === null || $receivingTimestamp > $currentTimestamp) {
            continue;
        }

        $record['status'] = 'ongoing';
        $record['cancel_reason'] = '';
        $record['canceled_by'] = '';
        $normalizedRecord = normalize_customer_order_record($record);
        $orders[$index] = $normalizedRecord;
        $advancedOrders[] = $normalizedRecord;
        $didAdvance = true;
    }

    return $orders;
}

function advance_customer_orders_to_for_return_by_schedule($orders, &$didAdvance = false, $nowTimestamp = null, &$advancedOrders = null)
{
    $didAdvance = false;

    if (!is_array($advancedOrders)) {
        $advancedOrders = [];
    }

    if (!is_array($orders)) {
        return [];
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();

    foreach ($orders as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

        if ($statusToken !== 'ongoing') {
            continue;
        }

        $returnTimestamp = customer_order_return_schedule_timestamp($record);

        if ($returnTimestamp === null || $returnTimestamp > $currentTimestamp) {
            continue;
        }

        $record['status'] = 'return';
        $record['cancel_reason'] = '';
        $record['canceled_by'] = '';
        $normalizedRecord = normalize_customer_order_record($record);
        $orders[$index] = $normalizedRecord;
        $advancedOrders[] = $normalizedRecord;
        $didAdvance = true;
    }

    return $orders;
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
    return customer_order_normalize_timestamp_value($value);
}

function normalize_customer_order_identity_uploaded_at($value)
{
    return customer_order_normalize_timestamp_value($value);
}

function customer_order_requires_identity_documents($record)
{
    if (!is_array($record)) {
        return false;
    }

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? $record['receivingMethod'] ?? '');
    $returningMethod = normalize_customer_order_returning_method($record['returning_method'] ?? $record['returningMethod'] ?? '');

    return $receivingMethod === 'delivery' || $returningMethod === 'delivery';
}

function customer_order_requires_receive_delivery($record)
{
    if (!is_array($record)) {
        return false;
    }

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? $record['receivingMethod'] ?? '');

    return $receivingMethod === 'delivery';
}

function customer_order_requires_return_delivery($record)
{
    if (!is_array($record)) {
        return false;
    }

    $returningMethod = normalize_customer_order_returning_method($record['returning_method'] ?? $record['returningMethod'] ?? '');

    return $returningMethod === 'delivery';
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

function customer_order_payment_stage_token($record)
{
    if (!is_array($record)) {
        return '';
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');

    if ($paymentMethod !== 'gcash') {
        return '';
    }

    if ($statusToken === 'pending' && $receiptPath === '') {
        return 'waiting-receipt';
    }

    if ($statusToken === 'pending' && $receiptPath !== '') {
        return 'payment-review';
    }

    if ($statusToken === 'awaiting-refund') {
        return 'refund-processing';
    }

    if ($statusToken === 'refunded') {
        return 'refunded';
    }

    return '';
}

function customer_order_customer_payment_stage_label($record)
{
    $stageToken = customer_order_payment_stage_token($record);

    if ($stageToken === 'waiting-receipt') {
        return 'Waiting for Receipt Upload';
    }

    if ($stageToken === 'payment-review') {
        return 'Receipt Submitted - Awaiting Admin Review';
    }

    return '';
}

function customer_order_admin_payment_stage_label($record)
{
    $stageToken = customer_order_payment_stage_token($record);

    if ($stageToken === 'waiting-receipt') {
        return 'PENDING RECEIPT';
    }

    if ($stageToken === 'payment-review') {
        return 'PAYMENT REVIEW';
    }

    return '';
}

function customer_order_is_for_pickup_state($record, $nowTimestamp = null)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    if ($statusToken !== 'approved') {
        return false;
    }

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? $record['receivingMethod'] ?? '');
    if ($receivingMethod !== 'pickup') {
        return false;
    }

    if (customer_order_is_receive_handover_confirmed($record)) {
        return false;
    }

    $receivingTimestamp = customer_order_receiving_timestamp($record);
    if (!is_int($receivingTimestamp)) {
        return false;
    }

    $currentTimestamp = is_int($nowTimestamp) ? $nowTimestamp : time();
    $windowStartTimestamp = $receivingTimestamp - customer_order_pickup_ready_lead_seconds();

    return $currentTimestamp >= $windowStartTimestamp && $currentTimestamp < $receivingTimestamp;
}

function customer_order_requires_refund_after_cancellation($record)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $receiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? '');

    return in_array($statusToken, ['pending', 'approved'], true)
        && $paymentMethod === 'gcash'
        && $receiptPath !== '';
}

function customer_order_resolve_cancellation_status_token($record)
{
    return customer_order_requires_refund_after_cancellation($record)
        ? 'awaiting-refund'
        : 'canceled';
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

    $createdAt = customer_order_parse_datetime_value($createdAtRaw);

    if ($createdAt instanceof DateTimeImmutable) {
        return (int) $createdAt->getTimestamp() + customer_order_payment_receipt_timeout_seconds();
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

        $itemId = trim((string) ($item['item_id'] ?? $item['itemId'] ?? $item['id'] ?? ''));
        $itemType = normalize_customer_order_item_type($item['item_type'] ?? $item['itemType'] ?? $item['type'] ?? '');
        $productKey = normalize_customer_order_product_key($item['product_key'] ?? $item['productKey'] ?? '');

        if ($productKey === '') {
            $productKey = customer_order_extract_product_key_from_item_id($itemId);
        }

        $normalizedItem = [
            'name' => $name,
            'qty' => $qty,
            'days' => $days,
        ];

        if ($itemId !== '') {
            $normalizedItem['item_id'] = $itemId;
        }

        if ($itemType !== '') {
            $normalizedItem['item_type'] = $itemType;
        }

        if ($productKey !== '') {
            $normalizedItem['product_key'] = $productKey;
        }

        $normalized[] = $normalizedItem;
    }

    return $normalized;
}

function normalize_customer_order_item_type($value)
{
    $type = strtolower(trim((string) $value));
    $type = preg_replace('/[^a-z0-9-]+/', '-', $type) ?? $type;
    $type = trim((string) $type, '-');

    return $type;
}

function normalize_customer_order_product_key($value)
{
    $key = strtolower(trim((string) $value));
    $key = preg_replace('/[^a-z0-9-]+/', '-', $key) ?? $key;
    $key = trim((string) $key, '-');

    return $key;
}

function customer_order_extract_product_key_from_item_id($itemId)
{
    $value = strtolower(trim((string) $itemId));

    if (strpos($value, 'camera-') !== 0) {
        return '';
    }

    return normalize_customer_order_product_key(substr($value, 7));
}

function customer_order_normalize_lookup_label($value)
{
    $label = strtolower(trim((string) $value));
    $label = preg_replace('/\s+/', ' ', $label) ?? $label;

    return trim((string) $label);
}

function customer_order_load_products_for_availability()
{
    if (!function_exists('load_products_repository')) {
        require_once __DIR__ . '/products_repository.php';
    }

    $products = load_products_repository();

    return is_array($products) ? $products : [];
}

function customer_order_load_inventory_for_availability()
{
    if (!function_exists('load_equipment_inventory_repository')) {
        require_once __DIR__ . '/equipment_inventory_repository.php';
    }

    $inventory = load_equipment_inventory_repository();

    return is_array($inventory) ? $inventory : [];
}

function customer_order_product_name_lookup_map($products)
{
    $lookup = [];

    foreach ($products as $productKey => $product) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($product)) {
            continue;
        }

        $normalizedKey = normalize_customer_order_product_key($productKey);
        if ($normalizedKey === '') {
            continue;
        }

        $brand = trim((string) ($product['brand'] ?? ''));
        $name = trim((string) ($product['name'] ?? ''));
        $label = customer_order_normalize_lookup_label($brand . ' ' . $name);

        if ($label !== '' && !isset($lookup[$label])) {
            $lookup[$label] = $normalizedKey;
        }

        $nameOnly = customer_order_normalize_lookup_label($name);

        if ($nameOnly !== '' && !isset($lookup[$nameOnly])) {
            $lookup[$nameOnly] = $normalizedKey;
        }
    }

    return $lookup;
}

function customer_order_resolve_item_product_key($item, $products, $nameLookup = null)
{
    if (!is_array($item)) {
        return '';
    }

    $explicitKey = normalize_customer_order_product_key($item['product_key'] ?? $item['productKey'] ?? '');

    if ($explicitKey !== '' && isset($products[$explicitKey]) && is_array($products[$explicitKey])) {
        return $explicitKey;
    }

    $itemId = trim((string) ($item['item_id'] ?? $item['itemId'] ?? $item['id'] ?? ''));
    $itemType = normalize_customer_order_item_type($item['item_type'] ?? $item['itemType'] ?? $item['type'] ?? '');
    $productKeyFromId = customer_order_extract_product_key_from_item_id($itemId);

    if ($productKeyFromId !== '' && isset($products[$productKeyFromId]) && is_array($products[$productKeyFromId])) {
        return $productKeyFromId;
    }

    if ($itemType !== '' && $itemType !== 'camera') {
        return '';
    }

    $lookup = is_array($nameLookup)
        ? $nameLookup
        : customer_order_product_name_lookup_map($products);

    $nameKey = customer_order_normalize_lookup_label($item['name'] ?? '');

    if ($nameKey === '' || !isset($lookup[$nameKey])) {
        return '';
    }

    return normalize_customer_order_product_key($lookup[$nameKey]);
}

function customer_order_extract_camera_item_requirements($items, $products, $nameLookup = null)
{
    if (!is_array($items) || !is_array($products) || !$products) {
        return [];
    }

    $lookup = is_array($nameLookup)
        ? $nameLookup
        : customer_order_product_name_lookup_map($products);
    $requirements = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $productKey = customer_order_resolve_item_product_key($item, $products, $lookup);

        if ($productKey === '') {
            continue;
        }

        $qty = (int) ($item['qty'] ?? 1);
        $days = (int) ($item['days'] ?? 1);

        if ($qty < 1) {
            $qty = 1;
        }

        if ($days < 1) {
            $days = 1;
        }

        if (!isset($requirements[$productKey])) {
            $requirements[$productKey] = [
                'qty' => 0,
                'days' => 0,
            ];
        }

        $requirements[$productKey]['qty'] += $qty;
        $requirements[$productKey]['days'] = max(
            (int) ($requirements[$productKey]['days'] ?? 0),
            $days
        );
    }

    foreach ($requirements as $productKey => $entry) {
        $requirements[$productKey]['qty'] = max(1, (int) ($entry['qty'] ?? 1));
        $requirements[$productKey]['days'] = max(1, (int) ($entry['days'] ?? 1));
    }

    return $requirements;
}

function customer_order_occupancy_status_tokens()
{
    return ['pending', 'approved', 'ongoing', 'return'];
}

function customer_order_record_occupies_inventory($record)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

    return in_array($statusToken, customer_order_occupancy_status_tokens(), true);
}

function customer_order_normalize_equipment_status_token($value)
{
    if (function_exists('normalize_equipment_status_token')) {
        return normalize_equipment_status_token($value);
    }

    $status = strtolower(trim((string) $value));
    $status = preg_replace('/[^a-z0-9-]+/', '-', $status) ?? $status;
    $status = trim((string) $status, '-');

    return $status;
}

function customer_order_unit_counts_for_capacity($unit)
{
    if (!is_array($unit)) {
        return false;
    }

    $statusToken = customer_order_normalize_equipment_status_token($unit['status'] ?? 'available');

    return in_array($statusToken, ['available', 'in-use'], true);
}

function customer_order_in_use_lock_status_tokens()
{
    return ['ongoing', 'return'];
}

function customer_order_record_requires_in_use_lock($record)
{
    if (!is_array($record)) {
        return false;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

    return in_array($statusToken, customer_order_in_use_lock_status_tokens(), true);
}

function customer_order_collect_in_use_requirements_by_product($orders, $products, $nameLookup = null)
{
    if (!is_array($orders) || !is_array($products) || !$products) {
        return [];
    }

    $lookup = is_array($nameLookup)
        ? $nameLookup
        : customer_order_product_name_lookup_map($products);
    $requirements = [];

    foreach ($orders as $record) {
        if (!is_array($record) || !customer_order_record_requires_in_use_lock($record)) {
            continue;
        }

        $itemRequirements = customer_order_extract_camera_item_requirements($record['items'] ?? [], $products, $lookup);

        foreach ($itemRequirements as $productKey => $entry) {
            $normalizedKey = normalize_customer_order_product_key($productKey);

            if ($normalizedKey === '') {
                continue;
            }

            $qty = max(1, (int) ($entry['qty'] ?? 1));

            if (!isset($requirements[$normalizedKey])) {
                $requirements[$normalizedKey] = 0;
            }

            $requirements[$normalizedKey] += $qty;
        }
    }

    return $requirements;
}

function customer_order_align_inventory_with_in_use_requirements($inventory, $requirementsByProduct, &$didMutate = false)
{
    $didMutate = false;

    if (!is_array($inventory)) {
        return [];
    }

    $requirements = is_array($requirementsByProduct) ? $requirementsByProduct : [];

    foreach ($inventory as $productKey => $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $didMutateProduct = false;

        $normalizedProductKey = normalize_customer_order_product_key($productKey);
        $requiredInUse = max(0, (int) ($requirements[$normalizedProductKey] ?? 0));
        $units = is_array($entry['units'] ?? null)
            ? array_values($entry['units'])
            : [];

        if (!$units) {
            continue;
        }

        $inUseIndexes = [];
        $availableIndexes = [];

        foreach ($units as $index => $unit) {
            if (!is_array($unit)) {
                continue;
            }

            $statusToken = customer_order_normalize_equipment_status_token($unit['status'] ?? 'available');

            if ($statusToken === 'in-use') {
                $inUseIndexes[] = $index;
                continue;
            }

            if ($statusToken === 'available') {
                $availableIndexes[] = $index;
            }
        }

        $currentInUseCount = count($inUseIndexes);

        if ($currentInUseCount > $requiredInUse) {
            $releaseCount = $currentInUseCount - $requiredInUse;

            for ($offset = 0; $offset < $releaseCount; $offset++) {
                $indexToRelease = $inUseIndexes[$currentInUseCount - 1 - $offset] ?? null;

                if ($indexToRelease === null || !isset($units[$indexToRelease]) || !is_array($units[$indexToRelease])) {
                    continue;
                }

                $units[$indexToRelease]['status'] = 'available';
                $didMutateProduct = true;
                $didMutate = true;
            }

            $currentInUseCount = $requiredInUse;
        }

        if ($currentInUseCount < $requiredInUse) {
            $promoteCount = min($requiredInUse - $currentInUseCount, count($availableIndexes));

            for ($offset = 0; $offset < $promoteCount; $offset++) {
                $indexToPromote = $availableIndexes[$offset] ?? null;

                if ($indexToPromote === null || !isset($units[$indexToPromote]) || !is_array($units[$indexToPromote])) {
                    continue;
                }

                $units[$indexToPromote]['status'] = 'in-use';
                $didMutateProduct = true;
                $didMutate = true;
            }
        }

        if ($didMutateProduct) {
            $entry['units'] = array_values($units);
            $inventory[$productKey] = $entry;
        }
    }

    return $inventory;
}

function customer_order_synchronize_equipment_inventory_in_use_statuses($orders, $products = null, $inventory = null, &$didMutate = false)
{
    $didMutate = false;
    $productCollection = is_array($products) ? $products : customer_order_load_products_for_availability();
    $inventoryCollection = is_array($inventory) ? $inventory : customer_order_load_inventory_for_availability();

    if (!is_array($productCollection)) {
        $productCollection = [];
    }

    if (!is_array($inventoryCollection)) {
        $inventoryCollection = [];
    }

    if (!function_exists('sync_equipment_inventory_with_products')) {
        require_once __DIR__ . '/equipment_inventory_repository.php';
    }

    $beforeSyncInventory = $inventoryCollection;
    $syncedInventory = sync_equipment_inventory_with_products($productCollection, $inventoryCollection, 12);
    $didMutate = $syncedInventory != $beforeSyncInventory;

    $lookup = customer_order_product_name_lookup_map($productCollection);
    $requirementsByProduct = customer_order_collect_in_use_requirements_by_product(
        is_array($orders) ? $orders : [],
        $productCollection,
        $lookup
    );

    $didMutateByAlignment = false;
    $alignedInventory = customer_order_align_inventory_with_in_use_requirements(
        $syncedInventory,
        $requirementsByProduct,
        $didMutateByAlignment
    );

    if ($didMutateByAlignment) {
        $didMutate = true;
    }

    return [
        'inventory' => $alignedInventory,
        'requirementsByProduct' => $requirementsByProduct,
        'didMutate' => $didMutate,
    ];
}

function customer_order_product_capacity_map($products, $inventory)
{
    $capacities = [];

    foreach ($products as $productKey => $product) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($product)) {
            continue;
        }

        $normalizedKey = normalize_customer_order_product_key($productKey);
        if ($normalizedKey === '') {
            continue;
        }

        $capacity = 1;

        if (isset($inventory[$normalizedKey]) && is_array($inventory[$normalizedKey])) {
            $units = is_array($inventory[$normalizedKey]['units'] ?? null)
                ? $inventory[$normalizedKey]['units']
                : [];

            $activeUnitCount = 0;

            foreach ($units as $unit) {
                if (!customer_order_unit_counts_for_capacity($unit)) {
                    continue;
                }

                $activeUnitCount++;
            }

            $capacity = $activeUnitCount;
        }

        $capacities[$normalizedKey] = max(0, (int) $capacity);
    }

    return $capacities;
}

function customer_order_schedule_timestamp($receiveDateValue, $receiveTimeValue)
{
    $schedule = customer_order_parse_schedule_datetime($receiveDateValue, $receiveTimeValue);

    if (!$schedule instanceof DateTimeImmutable) {
        return null;
    }

    return (int) $schedule->getTimestamp();
}

function customer_order_build_camera_reservation_intervals($orders, $products, $nameLookup = null)
{
    if (!is_array($orders) || !is_array($products) || !$products) {
        return [];
    }

    $lookup = is_array($nameLookup)
        ? $nameLookup
        : customer_order_product_name_lookup_map($products);
    $intervals = [];

    foreach ($orders as $record) {
        if (!is_array($record) || !customer_order_record_occupies_inventory($record)) {
            continue;
        }

        $startTimestamp = customer_order_schedule_timestamp($record['receive_date'] ?? '', $record['receive_time'] ?? '');

        if ($startTimestamp === null) {
            continue;
        }

        $returnTimestamp = customer_order_schedule_timestamp($record['return_date'] ?? '', $record['return_time'] ?? '');
        $orderDurationDays = null;

        if ($returnTimestamp !== null && $returnTimestamp > $startTimestamp) {
            $orderDurationDays = (int) ceil(($returnTimestamp - $startTimestamp) / 86400);

            if ($orderDurationDays < 1) {
                $orderDurationDays = 1;
            }
        }

        $requirements = customer_order_extract_camera_item_requirements($record['items'] ?? [], $products, $lookup);
        $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

        if ($requirements === []) {
            continue;
        }

        foreach ($requirements as $productKey => $requirement) {
            $qty = max(1, (int) ($requirement['qty'] ?? 1));
            $days = max(1, (int) ($requirement['days'] ?? 1));

            if ($orderDurationDays !== null) {
                $days = max($days, $orderDurationDays);
            }

            $endTimestamp = $startTimestamp + ($days * 86400);

            if ($statusToken === 'return') {
                $endTimestamp = max($endTimestamp, customer_order_open_reservation_end_timestamp());
            }

            $intervals[] = [
                'order_id' => (string) ($record['id'] ?? ''),
                'product_key' => $productKey,
                'qty' => $qty,
                'days' => $days,
                'start_ts' => $startTimestamp,
                'end_ts' => $endTimestamp,
                'status_token' => $statusToken,
            ];
        }
    }

    return $intervals;
}

function customer_order_group_intervals_by_product($intervals)
{
    $grouped = [];

    foreach ((array) $intervals as $interval) {
        if (!is_array($interval)) {
            continue;
        }

        $productKey = normalize_customer_order_product_key($interval['product_key'] ?? '');

        if ($productKey === '') {
            continue;
        }

        if (!isset($grouped[$productKey])) {
            $grouped[$productKey] = [];
        }

        $grouped[$productKey][] = $interval;
    }

    return $grouped;
}

function customer_order_product_label_by_key($products, $productKey)
{
    $normalizedKey = normalize_customer_order_product_key($productKey);

    if ($normalizedKey === '' || !isset($products[$normalizedKey]) || !is_array($products[$normalizedKey])) {
        return '';
    }

    $product = $products[$normalizedKey];
    $brand = trim((string) ($product['brand'] ?? ''));
    $name = trim((string) ($product['name'] ?? ''));

    return trim($brand . ' ' . $name);
}

function customer_order_requirements_fit_schedule($requirements, $scheduleTimestamp, $intervalsByProduct, $capacityMap, $excludeOrderId = '', &$failureDetails = null)
{
    $excludeId = trim((string) $excludeOrderId);
    $failureDetails = [];

    foreach ((array) $requirements as $productKey => $entry) {
        $normalizedKey = normalize_customer_order_product_key($productKey);
        $requiredQty = max(1, (int) ($entry['qty'] ?? 1));
        $requiredDays = max(1, (int) ($entry['days'] ?? 1));
        $capacity = max(0, (int) ($capacityMap[$normalizedKey] ?? 0));

        if ($requiredQty > $capacity) {
            $failureDetails = [
                'product_key' => $normalizedKey,
                'required_qty' => $requiredQty,
                'occupied_qty' => 0,
                'capacity' => $capacity,
            ];

            return false;
        }

        $candidateEnd = $scheduleTimestamp + ($requiredDays * 86400);
        $occupiedQty = 0;
        $activeIntervals = isset($intervalsByProduct[$normalizedKey]) && is_array($intervalsByProduct[$normalizedKey])
            ? $intervalsByProduct[$normalizedKey]
            : [];

        foreach ($activeIntervals as $interval) {
            if (!is_array($interval)) {
                continue;
            }

            $intervalOrderId = trim((string) ($interval['order_id'] ?? ''));

            if ($excludeId !== '' && $intervalOrderId !== '' && $intervalOrderId === $excludeId) {
                continue;
            }

            $existingStart = (int) ($interval['start_ts'] ?? 0);
            $existingEnd = (int) ($interval['end_ts'] ?? 0);

            if ($scheduleTimestamp >= $existingEnd || $candidateEnd <= $existingStart) {
                continue;
            }

            $occupiedQty += max(1, (int) ($interval['qty'] ?? 1));
        }

        if (($occupiedQty + $requiredQty) > $capacity) {
            $failureDetails = [
                'product_key' => $normalizedKey,
                'required_qty' => $requiredQty,
                'occupied_qty' => $occupiedQty,
                'capacity' => $capacity,
            ];

            return false;
        }
    }

    return true;
}

function customer_order_validate_camera_schedule_availability($items, $receiveDate, $receiveTime, $orders = null, &$errorMessage = '', $excludeOrderId = '')
{
    $errorMessage = '';
    $scheduleTimestamp = customer_order_schedule_timestamp($receiveDate, $receiveTime);

    if ($scheduleTimestamp === null) {
        $errorMessage = 'Invalid receiving date/time selected.';
        return false;
    }

    $products = customer_order_load_products_for_availability();
    $nameLookup = customer_order_product_name_lookup_map($products);
    $requirements = customer_order_extract_camera_item_requirements($items, $products, $nameLookup);

    // Non-camera orders are not constrained by camera unit occupancy.
    if ($requirements === []) {
        return true;
    }

    $inventory = customer_order_load_inventory_for_availability();
    $capacityMap = customer_order_product_capacity_map($products, $inventory);
    $orderRecords = is_array($orders) ? $orders : load_customer_orders_repository();
    $intervals = customer_order_build_camera_reservation_intervals($orderRecords, $products, $nameLookup);
    $intervalsByProduct = customer_order_group_intervals_by_product($intervals);
    $failure = [];

    if (!customer_order_requirements_fit_schedule($requirements, $scheduleTimestamp, $intervalsByProduct, $capacityMap, $excludeOrderId, $failure)) {
        $failedProductKey = normalize_customer_order_product_key($failure['product_key'] ?? '');
        $productLabel = customer_order_product_label_by_key($products, $failedProductKey);

        if ($productLabel !== '') {
            $errorMessage = 'Selected receiving schedule is occupied for ' . $productLabel . '.';
        } else {
            $errorMessage = 'Selected receiving schedule is occupied for one or more items.';
        }

        return false;
    }

    return true;
}

function customer_order_build_equipment_availability_payload($options = [])
{
    $settings = is_array($options) ? $options : [];
    $allProducts = customer_order_load_products_for_availability();
    $inventory = customer_order_load_inventory_for_availability();
    $filterSource = $settings['product_key_filter'] ?? [];
    $filteredKeys = [];

    if (is_string($filterSource)) {
        $filterSource = [$filterSource];
    }

    foreach ((array) $filterSource as $candidateKey) {
        $normalizedKey = normalize_customer_order_product_key($candidateKey);

        if ($normalizedKey === '') {
            continue;
        }

        $filteredKeys[$normalizedKey] = true;
    }

    $selectedProducts = [];

    foreach ($allProducts as $productKey => $product) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($product)) {
            continue;
        }

        $normalizedKey = normalize_customer_order_product_key($productKey);

        if ($normalizedKey === '') {
            continue;
        }

        if ($filteredKeys && !isset($filteredKeys[$normalizedKey])) {
            continue;
        }

        $selectedProducts[$normalizedKey] = $product;
    }

    $nameLookup = customer_order_product_name_lookup_map($allProducts);
    $capacityMap = customer_order_product_capacity_map($selectedProducts, $inventory);
    $orders = load_customer_orders_repository();
    $intervals = customer_order_build_camera_reservation_intervals($orders, $allProducts, $nameLookup);
    $reservationsPayload = [];
    $productsPayload = [];
    $horizonDays = max(30, min(1095, (int) ($settings['horizon_days'] ?? 730)));

    foreach ($selectedProducts as $productKey => $product) {
        $productsPayload[$productKey] = [
            'capacity' => max(0, (int) ($capacityMap[$productKey] ?? 0)),
            'label' => customer_order_product_label_by_key($allProducts, $productKey),
        ];
    }

    foreach ($intervals as $interval) {
        if (!is_array($interval)) {
            continue;
        }

        $productKey = normalize_customer_order_product_key($interval['product_key'] ?? '');

        if ($productKey === '' || !isset($productsPayload[$productKey])) {
            continue;
        }

        $startTimestamp = (int) ($interval['start_ts'] ?? 0);
        $endTimestamp = (int) ($interval['end_ts'] ?? 0);

        if ($startTimestamp <= 0) {
            continue;
        }

        $payloadDays = max(1, (int) ($interval['days'] ?? 1));

        if ($endTimestamp > $startTimestamp) {
            $derivedDays = (int) ceil(($endTimestamp - $startTimestamp) / 86400);

            if ($derivedDays > 0) {
                $payloadDays = max($payloadDays, min(36500, $derivedDays));
            }
        }

        $reservationsPayload[] = [
            'orderId' => (string) ($interval['order_id'] ?? ''),
            'productKey' => $productKey,
            'qty' => max(1, (int) ($interval['qty'] ?? 1)),
            'days' => $payloadDays,
            'startDate' => customer_order_local_datetime_format_from_timestamp($startTimestamp, 'Y-m-d'),
            'startTime' => customer_order_local_datetime_format_from_timestamp($startTimestamp, 'H:i'),
            'statusToken' => (string) ($interval['status_token'] ?? ''),
        ];
    }

    return [
        'generatedAt' => customer_order_now_iso8601(),
        'horizonDays' => $horizonDays,
        'booking' => [
            'openHour' => customer_order_booking_shop_open_hour(),
            'closeHour' => customer_order_booking_shop_close_hour(),
            'sameDayCutoffHour' => customer_order_booking_same_day_cutoff_hour(),
            'leadHours' => customer_order_booking_lead_hours(),
        ],
        'products' => $productsPayload,
        'reservations' => $reservationsPayload,
    ];
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

    $createdAt = customer_order_normalize_timestamp_value($record['created_at'] ?? '');
    if ($createdAt === '') {
        $createdAt = customer_order_now_iso8601();
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
    $statusLabel = normalize_customer_order_status($statusToken);

    $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? '');
    $returningMethod = normalize_customer_order_returning_method($record['returning_method'] ?? '');
    $place = trim((string) ($record['place'] ?? ''));
    $courier = normalize_customer_order_courier($record['courier'] ?? '');
    $cancelReason = normalize_customer_order_cancel_reason($record['cancel_reason'] ?? '');
    $canceledBy = normalize_customer_order_canceled_by($record['canceled_by'] ?? '');
    $paymentMethod = normalize_customer_order_payment_method($record['payment_method'] ?? '');
    $paymentReceiptPath = normalize_customer_order_asset_path($record['payment_receipt_path'] ?? $record['paymentReceiptPath'] ?? '');
    $paymentReceiptUploadedAt = normalize_customer_order_receipt_uploaded_at($record['payment_receipt_uploaded_at'] ?? $record['paymentReceiptUploadedAt'] ?? '');
    $receiveDeliveryStatus = normalize_customer_order_delivery_status_token(
        $record['receive_delivery_status'] ?? $record['receiveDeliveryStatus'] ?? '',
        'receive'
    );
    $receiveDeliveryReceiptPath = normalize_customer_order_asset_path(
        $record['receive_delivery_receipt_path'] ?? $record['receiveDeliveryReceiptPath'] ?? ''
    );
    $receiveDeliveryReceiptUploadedAt = normalize_customer_order_delivery_receipt_uploaded_at(
        $record['receive_delivery_receipt_uploaded_at'] ?? $record['receiveDeliveryReceiptUploadedAt'] ?? ''
    );
    $receiveDeliveryReceiptUploadedBy = normalize_customer_order_delivery_actor(
        $record['receive_delivery_receipt_uploaded_by'] ?? $record['receiveDeliveryReceiptUploadedBy'] ?? ''
    );
    $receiveDeliveryReference = normalize_customer_order_delivery_reference(
        $record['receive_delivery_reference'] ?? $record['receiveDeliveryReference'] ?? $record['receive_delivery_tracking_number'] ?? ''
    );
    $receiveDeliveryNotes = normalize_customer_order_delivery_notes(
        $record['receive_delivery_notes'] ?? $record['receiveDeliveryNotes'] ?? ''
    );
    $receiveDeliveryClosedAt = normalize_customer_order_delivery_closed_at(
        $record['receive_delivery_closed_at'] ?? $record['receiveDeliveryClosedAt'] ?? ''
    );
    $receiveDeliveryClosedBy = normalize_customer_order_delivery_actor(
        $record['receive_delivery_closed_by'] ?? $record['receiveDeliveryClosedBy'] ?? ''
    );

    $returnDeliveryStatus = normalize_customer_order_delivery_status_token(
        $record['return_delivery_status'] ?? $record['returnDeliveryStatus'] ?? '',
        'return'
    );
    $returnDeliveryReceiptPath = normalize_customer_order_asset_path(
        $record['return_delivery_receipt_path'] ?? $record['returnDeliveryReceiptPath'] ?? ''
    );
    $returnDeliveryReceiptUploadedAt = normalize_customer_order_delivery_receipt_uploaded_at(
        $record['return_delivery_receipt_uploaded_at'] ?? $record['returnDeliveryReceiptUploadedAt'] ?? ''
    );
    $returnDeliveryReceiptUploadedBy = normalize_customer_order_delivery_actor(
        $record['return_delivery_receipt_uploaded_by'] ?? $record['returnDeliveryReceiptUploadedBy'] ?? ''
    );
    $returnDeliveryReference = normalize_customer_order_delivery_reference(
        $record['return_delivery_reference'] ?? $record['returnDeliveryReference'] ?? $record['return_delivery_tracking_number'] ?? ''
    );
    $returnDeliveryNotes = normalize_customer_order_delivery_notes(
        $record['return_delivery_notes'] ?? $record['returnDeliveryNotes'] ?? ''
    );
    $returnDeliveryClosedAt = normalize_customer_order_delivery_closed_at(
        $record['return_delivery_closed_at'] ?? $record['returnDeliveryClosedAt'] ?? ''
    );
    $returnDeliveryClosedBy = normalize_customer_order_delivery_actor(
        $record['return_delivery_closed_by'] ?? $record['returnDeliveryClosedBy'] ?? ''
    );

    $receiveHandoverConfirmedAt = normalize_customer_order_handover_confirmed_at(
        $record['receive_handover_confirmed_at'] ?? $record['receiveHandoverConfirmedAt'] ?? ''
    );
    $receiveHandoverConfirmedBy = normalize_customer_order_handover_confirmed_by(
        $record['receive_handover_confirmed_by'] ?? $record['receiveHandoverConfirmedBy'] ?? ''
    );
    $refundProofPath = normalize_customer_order_asset_path($record['refund_proof_path'] ?? $record['refundProofPath'] ?? '');
    $refundProofUploadedAt = normalize_customer_order_receipt_uploaded_at($record['refund_proof_uploaded_at'] ?? $record['refundProofUploadedAt'] ?? '');
    $validIdPath = normalize_customer_order_asset_path($record['valid_id_path'] ?? $record['validIdPath'] ?? '');
    $validIdUploadedAt = normalize_customer_order_identity_uploaded_at($record['valid_id_uploaded_at'] ?? $record['validIdUploadedAt'] ?? '');
    $selfieWithIdPath = normalize_customer_order_asset_path($record['selfie_with_id_path'] ?? $record['selfieWithIdPath'] ?? '');
    $selfieWithIdUploadedAt = normalize_customer_order_identity_uploaded_at($record['selfie_with_id_uploaded_at'] ?? $record['selfieWithIdUploadedAt'] ?? '');
    $requiresIdentityDocuments = $receivingMethod === 'delivery' || $returningMethod === 'delivery';

    if ($receivingMethod !== 'delivery' && $returningMethod !== 'delivery') {
        $courier = '';
    }

    if ($receivingMethod !== 'meetup' && $returningMethod !== 'meetup') {
        $place = '';
    }

    if (!customer_order_requires_receive_delivery(['receiving_method' => $receivingMethod])) {
        $receiveDeliveryStatus = 'not-required';
        $receiveDeliveryReceiptPath = '';
        $receiveDeliveryReceiptUploadedAt = '';
        $receiveDeliveryReceiptUploadedBy = '';
        $receiveDeliveryReference = '';
        $receiveDeliveryNotes = '';
        $receiveDeliveryClosedAt = '';
        $receiveDeliveryClosedBy = '';
    } else {
        if ($receiveDeliveryStatus === '' || $receiveDeliveryStatus === 'not-required') {
            $receiveDeliveryStatus = customer_order_delivery_default_status_token_for_leg('receive');
        }

        if ($receiveDeliveryReceiptPath === '') {
            $receiveDeliveryReceiptUploadedAt = '';
            $receiveDeliveryReceiptUploadedBy = '';

            if (in_array($receiveDeliveryStatus, ['in-transit', 'closed'], true)) {
                $receiveDeliveryStatus = customer_order_delivery_default_status_token_for_leg('receive');
            }
        } else {
            if ($receiveDeliveryReceiptUploadedAt === '') {
                $receiveDeliveryReceiptUploadedAt = customer_order_now_iso8601();
            }

            if ($receiveDeliveryReceiptUploadedBy === '') {
                $receiveDeliveryReceiptUploadedBy = 'admin';
            }
        }

        if ($receiveDeliveryStatus === 'closed') {
            if ($receiveDeliveryClosedAt === '') {
                $receiveDeliveryClosedAt = customer_order_now_iso8601();
            }

            if ($receiveDeliveryClosedBy === '') {
                $receiveDeliveryClosedBy = 'admin';
            }
        } else {
            $receiveDeliveryClosedAt = '';
            $receiveDeliveryClosedBy = '';
        }
    }

    if (!customer_order_requires_return_delivery(['returning_method' => $returningMethod])) {
        $returnDeliveryStatus = 'not-required';
        $returnDeliveryReceiptPath = '';
        $returnDeliveryReceiptUploadedAt = '';
        $returnDeliveryReceiptUploadedBy = '';
        $returnDeliveryReference = '';
        $returnDeliveryNotes = '';
        $returnDeliveryClosedAt = '';
        $returnDeliveryClosedBy = '';
    } else {
        if ($returnDeliveryStatus === '' || $returnDeliveryStatus === 'not-required') {
            $returnDeliveryStatus = customer_order_delivery_default_status_token_for_leg('return');
        }

        if ($returnDeliveryReceiptPath === '') {
            $returnDeliveryReceiptUploadedAt = '';
            $returnDeliveryReceiptUploadedBy = '';

            if (in_array($returnDeliveryStatus, ['in-transit', 'closed'], true)) {
                $returnDeliveryStatus = customer_order_delivery_default_status_token_for_leg('return');
            }
        } else {
            if ($returnDeliveryReceiptUploadedAt === '') {
                $returnDeliveryReceiptUploadedAt = customer_order_now_iso8601();
            }

            if ($returnDeliveryReceiptUploadedBy === '') {
                $returnDeliveryReceiptUploadedBy = 'customer';
            }
        }

        if ($returnDeliveryStatus === 'closed') {
            if ($returnDeliveryClosedAt === '') {
                $returnDeliveryClosedAt = customer_order_now_iso8601();
            }

            if ($returnDeliveryClosedBy === '') {
                $returnDeliveryClosedBy = 'admin';
            }
        } else {
            $returnDeliveryClosedAt = '';
            $returnDeliveryClosedBy = '';
        }
    }

    if (
        !in_array($receivingMethod, customer_order_handover_supported_receiving_methods(), true)
        || in_array($statusToken, ['pending', 'canceled', 'awaiting-refund', 'rejected', 'refunded'], true)
    ) {
        $receiveHandoverConfirmedAt = '';
        $receiveHandoverConfirmedBy = '';
    } elseif ($receiveHandoverConfirmedAt === '') {
        $receiveHandoverConfirmedBy = '';
    } elseif ($receiveHandoverConfirmedBy === '') {
        $receiveHandoverConfirmedBy = 'admin';
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
        $paymentReceiptUploadedAt = customer_order_now_iso8601();
    }

    if ($statusToken !== 'refunded') {
        $refundProofPath = '';
        $refundProofUploadedAt = '';
    } elseif ($refundProofPath === '') {
        $refundProofUploadedAt = '';
    } elseif ($refundProofUploadedAt === '') {
        $refundProofUploadedAt = customer_order_now_iso8601();
    }

    if (!$requiresIdentityDocuments) {
        $validIdPath = '';
        $validIdUploadedAt = '';
        $selfieWithIdPath = '';
        $selfieWithIdUploadedAt = '';
    } else {
        if ($validIdPath === '') {
            $validIdUploadedAt = '';
        } elseif ($validIdUploadedAt === '') {
            $validIdUploadedAt = customer_order_now_iso8601();
        }

        if ($selfieWithIdPath === '') {
            $selfieWithIdUploadedAt = '';
        } elseif ($selfieWithIdUploadedAt === '') {
            $selfieWithIdUploadedAt = customer_order_now_iso8601();
        }
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
        'place' => $place,
        'receiving_method' => $receivingMethod,
        'returning_method' => $returningMethod,
        'courier' => $courier,
        'valid_id_path' => $validIdPath,
        'valid_id_uploaded_at' => $validIdUploadedAt,
        'selfie_with_id_path' => $selfieWithIdPath,
        'selfie_with_id_uploaded_at' => $selfieWithIdUploadedAt,
        'cancel_reason' => $cancelReason,
        'canceled_by' => $canceledBy,
        'payment_method' => $paymentMethod,
        'payment_receipt_path' => $paymentReceiptPath,
        'payment_receipt_uploaded_at' => $paymentReceiptUploadedAt,
        'receive_delivery_status' => $receiveDeliveryStatus,
        'receive_delivery_receipt_path' => $receiveDeliveryReceiptPath,
        'receive_delivery_receipt_uploaded_at' => $receiveDeliveryReceiptUploadedAt,
        'receive_delivery_receipt_uploaded_by' => $receiveDeliveryReceiptUploadedBy,
        'receive_delivery_reference' => $receiveDeliveryReference,
        'receive_delivery_notes' => $receiveDeliveryNotes,
        'receive_delivery_closed_at' => $receiveDeliveryClosedAt,
        'receive_delivery_closed_by' => $receiveDeliveryClosedBy,
        'return_delivery_status' => $returnDeliveryStatus,
        'return_delivery_receipt_path' => $returnDeliveryReceiptPath,
        'return_delivery_receipt_uploaded_at' => $returnDeliveryReceiptUploadedAt,
        'return_delivery_receipt_uploaded_by' => $returnDeliveryReceiptUploadedBy,
        'return_delivery_reference' => $returnDeliveryReference,
        'return_delivery_notes' => $returnDeliveryNotes,
        'return_delivery_closed_at' => $returnDeliveryClosedAt,
        'return_delivery_closed_by' => $returnDeliveryClosedBy,
        'receive_handover_confirmed_at' => $receiveHandoverConfirmedAt,
        'receive_handover_confirmed_by' => $receiveHandoverConfirmedBy,
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
    $didNormalizeOrders = false;

    foreach ($decoded as $record) {
        if (!is_array($record)) {
            continue;
        }

        $normalized = normalize_customer_order_record($record);

        if (!$didNormalizeOrders && $normalized != $record) {
            $didNormalizeOrders = true;
        }

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

    $didAdvanceOrders = false;
    $advancedOrders = [];
    $orders = advance_customer_orders_to_ongoing_by_receiving_schedule($orders, $didAdvanceOrders, null, $advancedOrders);

    if ($didAdvanceOrders) {
        save_customer_orders_repository($orders);

        if (!function_exists('append_customer_order_status_notification')) {
            require_once __DIR__ . '/customer_notifications_repository.php';
        }

        if (function_exists('append_customer_order_status_notification')) {
            foreach ($advancedOrders as $advancedOrder) {
                if (!is_array($advancedOrder)) {
                    continue;
                }

                append_customer_order_status_notification(
                    (string) ($advancedOrder['customer_id'] ?? ''),
                    (string) ($advancedOrder['id'] ?? ''),
                    normalize_customer_order_status_token($advancedOrder['status'] ?? 'pending'),
                    (string) ($advancedOrder['status'] ?? ''),
                    ''
                );
            }
        }
    }

    $didAdvanceToReturnOrders = false;
    $advancedToReturnOrders = [];
    $orders = advance_customer_orders_to_for_return_by_schedule(
        $orders,
        $didAdvanceToReturnOrders,
        null,
        $advancedToReturnOrders
    );

    if ($didAdvanceToReturnOrders) {
        save_customer_orders_repository($orders);

        if (!function_exists('append_customer_order_status_notification')) {
            require_once __DIR__ . '/customer_notifications_repository.php';
        }

        if (function_exists('append_customer_order_status_notification')) {
            foreach ($advancedToReturnOrders as $advancedToReturnOrder) {
                if (!is_array($advancedToReturnOrder)) {
                    continue;
                }

                append_customer_order_status_notification(
                    (string) ($advancedToReturnOrder['customer_id'] ?? ''),
                    (string) ($advancedToReturnOrder['id'] ?? ''),
                    normalize_customer_order_status_token($advancedToReturnOrder['status'] ?? 'pending'),
                    (string) ($advancedToReturnOrder['status'] ?? ''),
                    ''
                );
            }
        }
    }

    if ($didNormalizeOrders && !$didExpireOrders && !$didAdvanceOrders && !$didAdvanceToReturnOrders) {
        save_customer_orders_repository($orders);
    }

    $didSyncInventoryStatuses = false;
    $syncInventoryResult = customer_order_synchronize_equipment_inventory_in_use_statuses(
        $orders,
        null,
        null,
        $didSyncInventoryStatuses
    );

    if ($didSyncInventoryStatuses) {
        if (!function_exists('save_equipment_inventory_repository')) {
            require_once __DIR__ . '/equipment_inventory_repository.php';
        }

        $inventoryPayload = is_array($syncInventoryResult['inventory'] ?? null)
            ? $syncInventoryResult['inventory']
            : [];

        save_equipment_inventory_repository($inventoryPayload);
    }

    customer_order_sync_notifications_to_orders($orders);

    return $orders;
}

function customer_order_build_id_lookup($orders)
{
    $lookup = [];

    if (!is_array($orders)) {
        return $lookup;
    }

    foreach ($orders as $record) {
        if (!is_array($record)) {
            continue;
        }

        $orderId = strtoupper(trim((string) ($record['id'] ?? '')));

        if ($orderId === '') {
            continue;
        }

        $lookup[$orderId] = true;
    }

    return $lookup;
}

function customer_order_sync_notifications_to_orders($orders)
{
    $orderIdLookup = customer_order_build_id_lookup($orders);

    if (!function_exists('prune_customer_order_notifications_by_order_ids')) {
        require_once __DIR__ . '/customer_notifications_repository.php';
    }

    if (
        function_exists('prune_customer_order_notifications_by_order_ids')
        && function_exists('load_customer_notifications_repository')
        && function_exists('save_customer_notifications_repository')
    ) {
        $customerNotifications = load_customer_notifications_repository();
        $customerPruneResult = prune_customer_order_notifications_by_order_ids($orderIdLookup, $customerNotifications);

        if (!empty($customerPruneResult['changed']) && is_array($customerPruneResult['notifications'] ?? null)) {
            save_customer_notifications_repository($customerPruneResult['notifications']);
        }
    }

    if (!function_exists('prune_message_order_notifications_by_order_ids')) {
        require_once __DIR__ . '/message_notifications_repository.php';
    }

    if (
        function_exists('prune_message_order_notifications_by_order_ids')
        && function_exists('load_message_notifications_repository')
        && function_exists('save_message_notifications_repository')
    ) {
        $adminNotifications = load_message_notifications_repository();
        $adminPruneResult = prune_message_order_notifications_by_order_ids($orderIdLookup, $adminNotifications);

        if (!empty($adminPruneResult['changed']) && is_array($adminPruneResult['notifications'] ?? null)) {
            save_message_notifications_repository($adminPruneResult['notifications']);
        }
    }
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

    if (file_put_contents(customer_orders_repository_path(), $encoded . PHP_EOL, LOCK_EX) === false) {
        return false;
    }

    customer_order_sync_notifications_to_orders($normalized);

    $didSyncInventoryStatuses = false;
    $syncInventoryResult = customer_order_synchronize_equipment_inventory_in_use_statuses(
        $normalized,
        null,
        null,
        $didSyncInventoryStatuses
    );

    if (!$didSyncInventoryStatuses) {
        return true;
    }

    if (!function_exists('save_equipment_inventory_repository')) {
        require_once __DIR__ . '/equipment_inventory_repository.php';
    }

    $inventoryPayload = is_array($syncInventoryResult['inventory'] ?? null)
        ? $syncInventoryResult['inventory']
        : [];

    return save_equipment_inventory_repository($inventoryPayload);
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
            'receive_delivery_status' => normalize_customer_order_delivery_status_token($record['receive_delivery_status'] ?? '', 'receive'),
            'receive_delivery_receipt_path' => normalize_customer_order_asset_path($record['receive_delivery_receipt_path'] ?? ''),
            'receive_delivery_receipt_uploaded_at' => normalize_customer_order_delivery_receipt_uploaded_at($record['receive_delivery_receipt_uploaded_at'] ?? ''),
            'receive_delivery_receipt_uploaded_by' => normalize_customer_order_delivery_actor($record['receive_delivery_receipt_uploaded_by'] ?? ''),
            'receive_delivery_reference' => normalize_customer_order_delivery_reference($record['receive_delivery_reference'] ?? ''),
            'receive_delivery_notes' => normalize_customer_order_delivery_notes($record['receive_delivery_notes'] ?? ''),
            'receive_delivery_closed_at' => normalize_customer_order_delivery_closed_at($record['receive_delivery_closed_at'] ?? ''),
            'receive_delivery_closed_by' => normalize_customer_order_delivery_actor($record['receive_delivery_closed_by'] ?? ''),
            'return_delivery_status' => normalize_customer_order_delivery_status_token($record['return_delivery_status'] ?? '', 'return'),
            'return_delivery_receipt_path' => normalize_customer_order_asset_path($record['return_delivery_receipt_path'] ?? ''),
            'return_delivery_receipt_uploaded_at' => normalize_customer_order_delivery_receipt_uploaded_at($record['return_delivery_receipt_uploaded_at'] ?? ''),
            'return_delivery_receipt_uploaded_by' => normalize_customer_order_delivery_actor($record['return_delivery_receipt_uploaded_by'] ?? ''),
            'return_delivery_reference' => normalize_customer_order_delivery_reference($record['return_delivery_reference'] ?? ''),
            'return_delivery_notes' => normalize_customer_order_delivery_notes($record['return_delivery_notes'] ?? ''),
            'return_delivery_closed_at' => normalize_customer_order_delivery_closed_at($record['return_delivery_closed_at'] ?? ''),
            'return_delivery_closed_by' => normalize_customer_order_delivery_actor($record['return_delivery_closed_by'] ?? ''),
            'receive_handover_confirmed_at' => normalize_customer_order_handover_confirmed_at($record['receive_handover_confirmed_at'] ?? ''),
            'receive_handover_confirmed_by' => normalize_customer_order_handover_confirmed_by($record['receive_handover_confirmed_by'] ?? ''),
            'refund_proof_path' => normalize_customer_order_asset_path($record['refund_proof_path'] ?? ''),
            'refund_proof_uploaded_at' => normalize_customer_order_receipt_uploaded_at($record['refund_proof_uploaded_at'] ?? ''),
            'valid_id_path' => normalize_customer_order_asset_path($record['valid_id_path'] ?? ''),
            'valid_id_uploaded_at' => normalize_customer_order_identity_uploaded_at($record['valid_id_uploaded_at'] ?? ''),
            'selfie_with_id_path' => normalize_customer_order_asset_path($record['selfie_with_id_path'] ?? ''),
            'selfie_with_id_uploaded_at' => normalize_customer_order_identity_uploaded_at($record['selfie_with_id_uploaded_at'] ?? ''),
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
    $customerPaymentStageLabel = customer_order_customer_payment_stage_label($record);
    $paymentStageToken = customer_order_payment_stage_token($record);
    $isWaitingForPaymentReceipt = customer_order_requires_payment_receipt($record);
    $isWaitingForPaymentReview = customer_order_requires_payment_review($record);
    $isForPickupReady = customer_order_is_for_pickup_state($record);
    $receiveDeliveryStatus = normalize_customer_order_delivery_status_token($record['receive_delivery_status'] ?? '', 'receive');
    $returnDeliveryStatus = normalize_customer_order_delivery_status_token($record['return_delivery_status'] ?? '', 'return');
    $receiveDeliveryReceiptPath = normalize_customer_order_asset_path($record['receive_delivery_receipt_path'] ?? '');
    $returnDeliveryReceiptPath = normalize_customer_order_asset_path($record['return_delivery_receipt_path'] ?? '');
    $receiveHandoverConfirmedAt = normalize_customer_order_handover_confirmed_at(
        $record['receive_handover_confirmed_at'] ?? ''
    );
    $receiveHandoverConfirmedBy = normalize_customer_order_handover_confirmed_by(
        $record['receive_handover_confirmed_by'] ?? ''
    );
    $statusLabel = (string) ($record['status'] ?? 'Pending');

    if ($customerPaymentStageLabel !== '') {
        $statusLabel = $customerPaymentStageLabel;
    } elseif ($isForPickupReady) {
        $statusLabel = 'For Pickup';
    }

    $forReturnState = customer_order_for_return_state($record);
    $forReturnDeadlineTimestamp = is_int($forReturnState['deadline_ts'] ?? null)
        ? (int) $forReturnState['deadline_ts']
        : null;

    return [
        'id' => (string) ($record['id'] ?? ''),
        'status' => $statusLabel,
        'statusToken' => $statusToken,
        'paymentStage' => $paymentStageToken,
        'paymentStageLabel' => $customerPaymentStageLabel,
        'waitingForPaymentReceipt' => $isWaitingForPaymentReceipt,
        'waitingForPaymentReview' => $isWaitingForPaymentReview,
        'forPickupReady' => $isForPickupReady,
        'requiresReceiveDelivery' => customer_order_requires_receive_delivery($record),
        'requiresReturnDelivery' => customer_order_requires_return_delivery($record),
        'receiveDeliveryStatus' => $receiveDeliveryStatus,
        'receiveDeliveryReceiptPath' => $receiveDeliveryReceiptPath,
        'receiveDeliveryReceiptUploadedAt' => normalize_customer_order_delivery_receipt_uploaded_at($record['receive_delivery_receipt_uploaded_at'] ?? ''),
        'receiveDeliveryReceiptUploadedBy' => normalize_customer_order_delivery_actor($record['receive_delivery_receipt_uploaded_by'] ?? ''),
        'receiveDeliveryReference' => normalize_customer_order_delivery_reference($record['receive_delivery_reference'] ?? ''),
        'receiveDeliveryNotes' => normalize_customer_order_delivery_notes($record['receive_delivery_notes'] ?? ''),
        'receiveDeliveryClosedAt' => normalize_customer_order_delivery_closed_at($record['receive_delivery_closed_at'] ?? ''),
        'receiveDeliveryClosedBy' => normalize_customer_order_delivery_actor($record['receive_delivery_closed_by'] ?? ''),
        'returnDeliveryStatus' => $returnDeliveryStatus,
        'returnDeliveryReceiptPath' => $returnDeliveryReceiptPath,
        'returnDeliveryReceiptUploadedAt' => normalize_customer_order_delivery_receipt_uploaded_at($record['return_delivery_receipt_uploaded_at'] ?? ''),
        'returnDeliveryReceiptUploadedBy' => normalize_customer_order_delivery_actor($record['return_delivery_receipt_uploaded_by'] ?? ''),
        'returnDeliveryReference' => normalize_customer_order_delivery_reference($record['return_delivery_reference'] ?? ''),
        'returnDeliveryNotes' => normalize_customer_order_delivery_notes($record['return_delivery_notes'] ?? ''),
        'returnDeliveryClosedAt' => normalize_customer_order_delivery_closed_at($record['return_delivery_closed_at'] ?? ''),
        'returnDeliveryClosedBy' => normalize_customer_order_delivery_actor($record['return_delivery_closed_by'] ?? ''),
        'receiveHandoverConfirmed' => $receiveHandoverConfirmedAt !== '',
        'receiveHandoverConfirmedAt' => $receiveHandoverConfirmedAt,
        'receiveHandoverConfirmedBy' => $receiveHandoverConfirmedBy,
        'items' => is_array($record['items'] ?? null) ? $record['items'] : [],
        'receiveDate' => (string) ($record['receive_date'] ?? ''),
        'receiveTime' => (string) ($record['receive_time'] ?? ''),
        'returnDate' => (string) ($record['return_date'] ?? ''),
        'returnTime' => (string) ($record['return_time'] ?? ''),
        'place' => (string) ($record['place'] ?? ''),
        'receivingMethod' => (string) ($record['receiving_method'] ?? ''),
        'returningMethod' => (string) ($record['returning_method'] ?? ''),
        'courier' => (string) ($record['courier'] ?? ''),
        'validIdPath' => (string) ($record['valid_id_path'] ?? ''),
        'validIdUploadedAt' => (string) ($record['valid_id_uploaded_at'] ?? ''),
        'selfieWithIdPath' => (string) ($record['selfie_with_id_path'] ?? ''),
        'selfieWithIdUploadedAt' => (string) ($record['selfie_with_id_uploaded_at'] ?? ''),
        'cancelReason' => (string) ($record['cancel_reason'] ?? ''),
        'cancelBy' => (string) ($record['canceled_by'] ?? ''),
        'paymentMethod' => (string) ($record['payment_method'] ?? ''),
        'paymentReceiptPath' => (string) ($record['payment_receipt_path'] ?? ''),
        'paymentReceiptUploadedAt' => (string) ($record['payment_receipt_uploaded_at'] ?? ''),
        'refundProofPath' => (string) ($record['refund_proof_path'] ?? ''),
        'refundProofUploadedAt' => (string) ($record['refund_proof_uploaded_at'] ?? ''),
        'paymentReceiptDeadlineAt' => $paymentReceiptDeadlineTimestamp !== null
            ? customer_order_now_iso8601($paymentReceiptDeadlineTimestamp)
            : '',
        'paymentReceiptTimeoutSeconds' => customer_order_payment_receipt_timeout_seconds(),
        'forReturnGraceSeconds' => (int) ($forReturnState['grace_seconds'] ?? customer_order_for_return_grace_seconds()),
        'forReturnPenaltyPerHour' => (int) ($forReturnState['penalty_per_hour'] ?? customer_order_for_return_penalty_per_hour()),
        'forReturnDeadlineAt' => $forReturnDeadlineTimestamp !== null
            ? customer_order_now_iso8601($forReturnDeadlineTimestamp)
            : '',
        'forReturnRemainingSeconds' => max(0, (int) ($forReturnState['remaining_seconds'] ?? 0)),
        'forReturnOverdueSeconds' => max(0, (int) ($forReturnState['overdue_seconds'] ?? 0)),
        'forReturnPenaltyHours' => max(0, (int) ($forReturnState['penalty_hours'] ?? 0)),
        'forReturnPenaltyAmount' => max(0, (int) ($forReturnState['penalty_amount'] ?? 0)),
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

function append_customer_order_for_customer($customerId, $customerName, $customerEmail, $orderPayload, &$errorMessage = '')
{
    $errorMessage = '';
    $targetCustomerId = trim((string) $customerId);

    if ($targetCustomerId === '') {
        $errorMessage = 'Please log in to place a booking.';
        return null;
    }

    $payload = is_array($orderPayload) ? $orderPayload : [];
    $items = normalize_customer_order_items($payload['items'] ?? []);
    $receiveDate = normalize_customer_order_date($payload['receiveDate'] ?? '');
    $receiveTime = normalize_customer_order_time($payload['receiveTime'] ?? '');
    $returnDate = normalize_customer_order_date($payload['returnDate'] ?? '');
    $returnTime = normalize_customer_order_time($payload['returnTime'] ?? '');
    $receivingMethod = normalize_customer_order_receiving_method($payload['receivingMethod'] ?? '');
    $returningMethod = normalize_customer_order_returning_method($payload['returningMethod'] ?? '');
    $requiresIdentityDocuments = $receivingMethod === 'delivery' || $returningMethod === 'delivery';
    $validIdImageDataUrl = trim((string) ($payload['validIdImageDataUrl'] ?? ''));
    $selfieWithIdImageDataUrl = trim((string) ($payload['selfieWithIdImageDataUrl'] ?? ''));

    if ($items === []) {
        $errorMessage = 'At least one item is required to create a booking.';
        return null;
    }

    if (
        $requiresIdentityDocuments
        && (strpos($validIdImageDataUrl, 'data:image/') !== 0 || strpos($selfieWithIdImageDataUrl, 'data:image/') !== 0)
    ) {
        $errorMessage = 'Delivery bookings require a valid ID and a selfie holding the valid ID.';
        return null;
    }

    if (!customer_order_is_valid_receiving_schedule($receiveDate, $receiveTime, null, $receivingMethod)) {
        $errorMessage = 'Selected receiving date/time is invalid.';
        return null;
    }

    $allOrders = load_customer_orders_repository();

    if (!customer_order_validate_camera_schedule_availability($items, $receiveDate, $receiveTime, $allOrders, $availabilityError)) {
        $errorMessage = $availabilityError !== ''
            ? $availabilityError
            : 'Selected receiving schedule is occupied for one or more items.';
        return null;
    }

    $newOrderId = customer_order_generate_id();

    $newOrder = normalize_customer_order_record([
        'id' => $newOrderId,
        'customer_id' => $targetCustomerId,
        'customer_name' => trim((string) $customerName),
        'customer_email' => trim((string) $customerEmail),
        'status' => 'pending',
        'items' => $items,
        'receive_date' => $receiveDate,
        'receive_time' => $receiveTime,
        'return_date' => $returnDate,
        'return_time' => $returnTime,
        'place' => $payload['place'] ?? '',
        'receiving_method' => $receivingMethod,
        'returning_method' => $returningMethod,
        'courier' => $payload['courier'] ?? '',
        'valid_id_path' => '',
        'valid_id_uploaded_at' => '',
        'selfie_with_id_path' => '',
        'selfie_with_id_uploaded_at' => '',
        'cancel_reason' => '',
        'canceled_by' => '',
        'payment_method' => $payload['paymentMethod'] ?? '',
        'payment_receipt_path' => '',
        'payment_receipt_uploaded_at' => '',
        'receive_delivery_status' => $receivingMethod === 'delivery'
            ? customer_order_delivery_default_status_token_for_leg('receive')
            : 'not-required',
        'receive_delivery_receipt_path' => '',
        'receive_delivery_receipt_uploaded_at' => '',
        'receive_delivery_receipt_uploaded_by' => '',
        'receive_delivery_reference' => '',
        'receive_delivery_notes' => '',
        'receive_delivery_closed_at' => '',
        'receive_delivery_closed_by' => '',
        'return_delivery_status' => $returningMethod === 'delivery'
            ? customer_order_delivery_default_status_token_for_leg('return')
            : 'not-required',
        'return_delivery_receipt_path' => '',
        'return_delivery_receipt_uploaded_at' => '',
        'return_delivery_receipt_uploaded_by' => '',
        'return_delivery_reference' => '',
        'return_delivery_notes' => '',
        'return_delivery_closed_at' => '',
        'return_delivery_closed_by' => '',
        'created_at' => customer_order_now_iso8601(),
    ]);

    if ($requiresIdentityDocuments) {
        try {
            $projectRoot = dirname(__DIR__);
            $newOrder['valid_id_path'] = save_customer_order_valid_id_image_from_data_url(
                $validIdImageDataUrl,
                $projectRoot,
                $newOrderId
            );
            $newOrder['valid_id_uploaded_at'] = customer_order_now_iso8601();
            $newOrder['selfie_with_id_path'] = save_customer_order_selfie_with_id_image_from_data_url(
                $selfieWithIdImageDataUrl,
                $projectRoot,
                $newOrderId
            );
            $newOrder['selfie_with_id_uploaded_at'] = customer_order_now_iso8601();
            $newOrder = normalize_customer_order_record($newOrder);
        } catch (Throwable $error) {
            $errorMessage = 'Unable to save your identity images right now.';
            return null;
        }
    }

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

    $handoverMode = customer_order_handover_mode_from_action($nextStatus);
    if ($handoverMode !== '') {
        return confirm_customer_order_receive_handover_by_id($targetOrderId, $handoverMode, 'admin');
    }

    $settings = is_array($options) ? $options : [];
    $isReturnedEarlyRequest = customer_order_is_returned_early_request($nextStatus);
    $requestedStatusToken = normalize_customer_order_status_token($nextStatus);
    $statusNeedsReason = customer_order_status_requires_reason($requestedStatusToken);
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

    // Awaiting-refund is a system transition created through cancellation of an approved paid booking.
    if ($requestedStatusToken === 'awaiting-refund') {
        return null;
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
        $resolvedStatusToken = $requestedStatusToken;

        if ($requestedStatusToken === 'canceled') {
            $resolvedStatusToken = customer_order_resolve_cancellation_status_token($record);
        }

        $resolvedStatusLabel = normalize_customer_order_status($resolvedStatusToken);

        // Terminal bookings cannot transition to any other state.
        if (customer_order_is_terminal_status($currentStatusToken)) {
            return null;
        }

        // Awaiting-refund bookings can only proceed to refunded.
        if ($currentStatusToken === 'awaiting-refund' && $resolvedStatusToken !== 'refunded') {
            return null;
        }

        // While waiting for a GCash receipt, admin can only cancel.
        if ($isWaitingForPaymentReceipt && $requestedStatusToken !== 'canceled') {
            return null;
        }

        // During receipt review, admin can approve, reject, refund, or cancel.
        if ($isWaitingForPaymentReview && !in_array($requestedStatusToken, ['approved', 'rejected', 'refunded', 'canceled'], true)) {
            return null;
        }

        // Pending cash bookings can only be approved or canceled.
        if (
            $currentStatusToken === 'pending'
            && !$isWaitingForPaymentReceipt
            && !$isWaitingForPaymentReview
            && !in_array($resolvedStatusToken, ['approved', 'canceled'], true)
        ) {
            return null;
        }

        // After payment approval, only cancellation is allowed manually.
        // Transition to ongoing is automatic at receiving date/time.
        if ($currentStatusToken === 'approved' && $requestedStatusToken !== 'canceled') {
            return null;
        }

        // During ongoing rental, only Returned Early action is allowed.
        if ($currentStatusToken === 'ongoing' && (!$isReturnedEarlyRequest || $resolvedStatusToken !== 'completed')) {
            return null;
        }

        // For-return bookings can only be completed manually.
        if ($currentStatusToken === 'return' && $resolvedStatusToken !== 'completed') {
            return null;
        }

        // Manual complete is only allowed from For Return, except Returned Early while Ongoing.
        if ($resolvedStatusToken === 'completed') {
            if ($isReturnedEarlyRequest && $currentStatusToken !== 'ongoing') {
                return null;
            }

            if (!$isReturnedEarlyRequest && $currentStatusToken !== 'return') {
                return null;
            }
        }

        if ($requestedStatusToken === 'rejected' && !$isWaitingForPaymentReview) {
            return null;
        }

        if ($resolvedStatusToken === 'refunded') {
            $isRefundFromAwaitingState = $currentStatusToken === 'awaiting-refund';

            if ((!$isWaitingForPaymentReview && !$isRefundFromAwaitingState) || $refundProofDataUrl === '' || $projectRoot === '') {
                return null;
            }

            try {
                $record['refund_proof_path'] = save_customer_order_refund_proof_from_data_url($refundProofDataUrl, $projectRoot, $targetOrderId);
                $record['refund_proof_uploaded_at'] = customer_order_now_iso8601();
            } catch (Throwable $error) {
                return null;
            }
        } else {
            $record['refund_proof_path'] = '';
            $record['refund_proof_uploaded_at'] = '';
        }

        $record['status'] = $resolvedStatusLabel;
        $record['cancel_reason'] = $normalizedCancelReason;
        $record['canceled_by'] = $normalizedCanceledBy;
        $orders[$index] = normalize_customer_order_record($record);
        $updatedOrder = $orders[$index];
        $didStatusChange = $currentStatusToken !== $resolvedStatusToken;
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
                normalize_customer_order_status_token($updatedOrder['status'] ?? $requestedStatusToken),
                (string) ($updatedOrder['status'] ?? normalize_customer_order_status($requestedStatusToken)),
                (string) ($updatedOrder['cancel_reason'] ?? '')
            );
        }
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function confirm_customer_order_receive_handover_by_id($orderId, $mode, $confirmedBy = 'admin')
{
    $targetOrderId = trim((string) $orderId);
    $targetMode = normalize_customer_order_receiving_method($mode);
    $normalizedConfirmedBy = normalize_customer_order_handover_confirmed_by($confirmedBy);

    if ($targetOrderId === '' || !in_array($targetMode, customer_order_handover_supported_receiving_methods(), true)) {
        return null;
    }

    if ($normalizedConfirmedBy === '') {
        $normalizedConfirmedBy = 'admin';
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

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        if (customer_order_is_terminal_status($currentStatusToken) || $currentStatusToken === 'awaiting-refund') {
            return null;
        }

        $receivingMethod = normalize_customer_order_receiving_method($record['receiving_method'] ?? '');
        if ($receivingMethod !== $targetMode) {
            return null;
        }

        if (!in_array($currentStatusToken, ['approved', 'ongoing', 'return'], true)) {
            return null;
        }

        $existingConfirmedAt = normalize_customer_order_handover_confirmed_at($record['receive_handover_confirmed_at'] ?? '');
        if ($existingConfirmedAt !== '') {
            return map_customer_order_for_frontend($record);
        }

        $record['receive_handover_confirmed_at'] = customer_order_now_iso8601();
        $record['receive_handover_confirmed_by'] = $normalizedConfirmedBy;

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

function save_customer_order_delivery_receipt_from_data_url($imageDataUrl, $projectRoot, $orderId, $leg)
{
    $normalizedLeg = normalize_customer_order_delivery_leg($leg);
    if ($normalizedLeg === '') {
        throw new RuntimeException('Invalid delivery leg.');
    }

    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid delivery receipt image payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        throw new RuntimeException('Invalid delivery receipt image data.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'png'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/delivery_receipts/' . $normalizedLeg;
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access delivery receipt directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-' . $normalizedLeg . '-delivery.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save delivery receipt image.');
    }

    return $targetDirRelative . '/' . $filename;
}

function save_customer_order_valid_id_image_from_data_url($imageDataUrl, $projectRoot, $orderId)
{
    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid valid ID image payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        throw new RuntimeException('Invalid valid ID image data.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'png'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/valid_id';
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access valid ID directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-valid-id.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save valid ID image.');
    }

    return $targetDirRelative . '/' . $filename;
}

function save_customer_order_selfie_with_id_image_from_data_url($imageDataUrl, $projectRoot, $orderId)
{
    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid selfie with ID image payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false) {
        throw new RuntimeException('Invalid selfie with ID image data.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'png'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/selfie_with_id';
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access selfie with ID directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-selfie-with-id.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save selfie with ID image.');
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
    $record['payment_receipt_uploaded_at'] = customer_order_now_iso8601();
    $orders[$matchedOrderIndex] = normalize_customer_order_record($record);
    $updatedOrder = $orders[$matchedOrderIndex];

    if (!save_customer_orders_repository($orders)) {
        return null;
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function upload_customer_order_delivery_receipt_for_customer($customerId, $orderId, $imageDataUrl, $projectRoot, $options = [])
{
    $targetCustomerId = trim((string) $customerId);
    $targetOrderId = trim((string) $orderId);
    $settings = is_array($options) ? $options : [];
    $hasDeliveryReference = array_key_exists('delivery_reference', $settings) || array_key_exists('reference', $settings);
    $hasDeliveryNotes = array_key_exists('delivery_notes', $settings) || array_key_exists('notes', $settings);
    $deliveryReference = normalize_customer_order_delivery_reference($settings['delivery_reference'] ?? ($settings['reference'] ?? ''));
    $deliveryNotes = normalize_customer_order_delivery_notes($settings['delivery_notes'] ?? ($settings['notes'] ?? ''));

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
        if ($currentStatusToken !== 'return' || customer_order_is_terminal_status($currentStatusToken)) {
            return null;
        }

        if (!customer_order_requires_return_delivery($record)) {
            return null;
        }

        $returnDeliveryStatus = normalize_customer_order_delivery_status_token($record['return_delivery_status'] ?? '', 'return');
        if (!in_array($returnDeliveryStatus, ['waiting-customer-proof', 'in-transit'], true)) {
            return null;
        }

        $receiptPath = save_customer_order_delivery_receipt_from_data_url($imageDataUrl, $projectRoot, $targetOrderId, 'return');

        $record['return_delivery_receipt_path'] = $receiptPath;
        $record['return_delivery_receipt_uploaded_at'] = customer_order_now_iso8601();
        $record['return_delivery_receipt_uploaded_by'] = 'customer';
        $record['return_delivery_status'] = 'in-transit';

        if ($hasDeliveryReference) {
            $record['return_delivery_reference'] = $deliveryReference;
        }

        if ($hasDeliveryNotes) {
            $record['return_delivery_notes'] = $deliveryNotes;
        }

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

    if (!function_exists('append_order_delivery_notification')) {
        require_once __DIR__ . '/message_notifications_repository.php';
    }

    if (function_exists('append_order_delivery_notification')) {
        $orderLabel = strtoupper((string) ($updatedOrder['id'] ?? $targetOrderId));
        $deliverySummarySegments = [
            'Customer uploaded the return-delivery receipt.',
        ];

        $returnDeliveryReference = normalize_customer_order_delivery_reference($updatedOrder['return_delivery_reference'] ?? '');
        $returnDeliveryNotes = normalize_customer_order_delivery_notes($updatedOrder['return_delivery_notes'] ?? '');

        if ($returnDeliveryReference !== '') {
            $deliverySummarySegments[] = 'Reference: ' . $returnDeliveryReference . '.';
        }

        if ($returnDeliveryNotes !== '') {
            $deliverySummarySegments[] = 'Notes: ' . $returnDeliveryNotes;
        }

        append_order_delivery_notification(
            (string) ($updatedOrder['id'] ?? $targetOrderId),
            'Return delivery receipt uploaded: ' . $orderLabel,
            trim(implode(' ', $deliverySummarySegments))
        );
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function upload_customer_order_delivery_receipt_for_admin($orderId, $imageDataUrl, $projectRoot, $options = [])
{
    $targetOrderId = trim((string) $orderId);
    $settings = is_array($options) ? $options : [];
    $hasDeliveryReference = array_key_exists('delivery_reference', $settings) || array_key_exists('reference', $settings);
    $hasDeliveryNotes = array_key_exists('delivery_notes', $settings) || array_key_exists('notes', $settings);
    $deliveryReference = normalize_customer_order_delivery_reference($settings['delivery_reference'] ?? ($settings['reference'] ?? ''));
    $deliveryNotes = normalize_customer_order_delivery_notes($settings['delivery_notes'] ?? ($settings['notes'] ?? ''));

    if ($targetOrderId === '') {
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

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        if (customer_order_is_terminal_status($currentStatusToken) || $currentStatusToken === 'awaiting-refund') {
            return null;
        }

        if (!in_array($currentStatusToken, ['approved', 'ongoing'], true)) {
            return null;
        }

        if (!customer_order_requires_receive_delivery($record)) {
            return null;
        }

        $receiveDeliveryStatus = normalize_customer_order_delivery_status_token($record['receive_delivery_status'] ?? '', 'receive');
        if (!in_array($receiveDeliveryStatus, ['waiting-proof', 'in-transit'], true)) {
            return null;
        }

        $receiptPath = save_customer_order_delivery_receipt_from_data_url($imageDataUrl, $projectRoot, $targetOrderId, 'receive');

        $record['receive_delivery_receipt_path'] = $receiptPath;
        $record['receive_delivery_receipt_uploaded_at'] = customer_order_now_iso8601();
        $record['receive_delivery_receipt_uploaded_by'] = 'admin';
        $record['receive_delivery_status'] = 'in-transit';

        if ($hasDeliveryReference) {
            $record['receive_delivery_reference'] = $deliveryReference;
        }

        if ($hasDeliveryNotes) {
            $record['receive_delivery_notes'] = $deliveryNotes;
        }

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

    if (!function_exists('append_customer_order_delivery_notification')) {
        require_once __DIR__ . '/customer_notifications_repository.php';
    }

    if (function_exists('append_customer_order_delivery_notification')) {
        $orderLabel = strtoupper((string) ($updatedOrder['id'] ?? $targetOrderId));
        $deliverySummarySegments = [
            'Admin uploaded your receive-delivery receipt. Delivery is now in transit.',
        ];

        $receiveDeliveryReference = normalize_customer_order_delivery_reference($updatedOrder['receive_delivery_reference'] ?? '');
        $receiveDeliveryNotes = normalize_customer_order_delivery_notes($updatedOrder['receive_delivery_notes'] ?? '');

        if ($receiveDeliveryReference !== '') {
            $deliverySummarySegments[] = 'Reference: ' . $receiveDeliveryReference . '.';
        }

        if ($receiveDeliveryNotes !== '') {
            $deliverySummarySegments[] = 'Notes: ' . $receiveDeliveryNotes;
        }

        append_customer_order_delivery_notification(
            (string) ($updatedOrder['customer_id'] ?? ''),
            (string) ($updatedOrder['id'] ?? $targetOrderId),
            'Delivery in transit: ' . $orderLabel,
            trim(implode(' ', $deliverySummarySegments)),
            'receive-delivery-in-transit'
        );
    }

    return map_customer_order_for_frontend($updatedOrder);
}

function close_customer_order_delivery_leg_by_admin($orderId, $leg, $options = [])
{
    $targetOrderId = trim((string) $orderId);
    $targetLeg = normalize_customer_order_delivery_leg($leg);
    $settings = is_array($options) ? $options : [];
    $hasDeliveryReference = array_key_exists('delivery_reference', $settings) || array_key_exists('reference', $settings);
    $hasDeliveryNotes = array_key_exists('delivery_notes', $settings) || array_key_exists('notes', $settings);
    $deliveryReference = normalize_customer_order_delivery_reference($settings['delivery_reference'] ?? ($settings['reference'] ?? ''));
    $deliveryNotes = normalize_customer_order_delivery_notes($settings['delivery_notes'] ?? ($settings['notes'] ?? ''));

    if ($targetOrderId === '' || $targetLeg === '') {
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

        $currentStatusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');
        if (customer_order_is_terminal_status($currentStatusToken) || $currentStatusToken === 'awaiting-refund') {
            return null;
        }

        if ($targetLeg === 'receive') {
            if (!customer_order_requires_receive_delivery($record)) {
                return null;
            }

            $deliveryStatus = normalize_customer_order_delivery_status_token($record['receive_delivery_status'] ?? '', 'receive');
            $deliveryReceiptPath = normalize_customer_order_asset_path($record['receive_delivery_receipt_path'] ?? '');

            if ($deliveryStatus !== 'in-transit' || $deliveryReceiptPath === '') {
                return null;
            }

            $record['receive_delivery_status'] = 'closed';
            $record['receive_delivery_closed_at'] = customer_order_now_iso8601();
            $record['receive_delivery_closed_by'] = 'admin';

            if ($hasDeliveryReference) {
                $record['receive_delivery_reference'] = $deliveryReference;
            }

            if ($hasDeliveryNotes) {
                $record['receive_delivery_notes'] = $deliveryNotes;
            }
        } else {
            if (!customer_order_requires_return_delivery($record)) {
                return null;
            }

            if ($currentStatusToken !== 'return') {
                return null;
            }

            $deliveryStatus = normalize_customer_order_delivery_status_token($record['return_delivery_status'] ?? '', 'return');
            $deliveryReceiptPath = normalize_customer_order_asset_path($record['return_delivery_receipt_path'] ?? '');

            if ($deliveryStatus !== 'in-transit' || $deliveryReceiptPath === '') {
                return null;
            }

            $record['return_delivery_status'] = 'closed';
            $record['return_delivery_closed_at'] = customer_order_now_iso8601();
            $record['return_delivery_closed_by'] = 'admin';

            if ($hasDeliveryReference) {
                $record['return_delivery_reference'] = $deliveryReference;
            }

            if ($hasDeliveryNotes) {
                $record['return_delivery_notes'] = $deliveryNotes;
            }
        }

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

    if (!function_exists('append_customer_order_delivery_notification')) {
        require_once __DIR__ . '/customer_notifications_repository.php';
    }

    if (function_exists('append_customer_order_delivery_notification')) {
        $orderLabel = strtoupper((string) ($updatedOrder['id'] ?? $targetOrderId));

        if ($targetLeg === 'receive') {
            append_customer_order_delivery_notification(
                (string) ($updatedOrder['customer_id'] ?? ''),
                (string) ($updatedOrder['id'] ?? $targetOrderId),
                'Receive delivery closed: ' . $orderLabel,
                'Admin confirmed that your receive-delivery leg has been completed.',
                'receive-delivery-closed'
            );
        } else {
            append_customer_order_delivery_notification(
                (string) ($updatedOrder['customer_id'] ?? ''),
                (string) ($updatedOrder['id'] ?? $targetOrderId),
                'Return delivery closed: ' . $orderLabel,
                'Admin confirmed that your return-delivery leg has been completed.',
                'return-delivery-closed'
            );
        }
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

        $record['status'] = customer_order_resolve_cancellation_status_token($record);
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
