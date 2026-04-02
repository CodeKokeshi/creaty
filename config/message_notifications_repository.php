<?php

function message_notifications_repository_path()
{
    return __DIR__ . '/message_notifications.json';
}

function message_notification_generate_id()
{
    try {
        return 'msg-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));
    } catch (Throwable $error) {
        return 'msg-' . gmdate('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8);
    }
}

function normalize_message_notification_attachment_path($path)
{
    $normalized = trim(str_replace('\\', '/', rawurldecode((string) $path)));
    $normalized = ltrim($normalized, '/');
    $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

    if ($normalized === '' || strpos($normalized, '..') !== false) {
        return '';
    }

    return $normalized;
}

function normalize_message_notification_record($record)
{
    if (!is_array($record)) {
        $record = [];
    }

    $id = trim((string) ($record['id'] ?? ''));
    if ($id === '') {
        $id = message_notification_generate_id();
    }

    $senderName = trim((string) ($record['sender_name'] ?? 'Unknown customer'));
    if ($senderName === '') {
        $senderName = 'Unknown customer';
    }

    $senderEmail = trim((string) ($record['sender_email'] ?? ''));

    $subject = trim((string) ($record['subject'] ?? 'Untitled message'));
    if ($subject === '') {
        $subject = 'Untitled message';
    }

    $message = trim((string) ($record['message'] ?? ''));

    $attachments = [];
    $attachmentsInput = $record['attachments'] ?? [];

    if (is_array($attachmentsInput)) {
        foreach ($attachmentsInput as $attachmentPath) {
            $normalizedPath = normalize_message_notification_attachment_path($attachmentPath);

            if ($normalizedPath === '' || isset($attachments[$normalizedPath])) {
                continue;
            }

            $attachments[$normalizedPath] = $normalizedPath;
        }
    }

    $isRead = (bool) ($record['is_read'] ?? false);

    $createdAt = trim((string) ($record['created_at'] ?? ''));
    if ($createdAt === '') {
        $createdAt = gmdate('c');
    }

    $readAt = trim((string) ($record['read_at'] ?? ''));
    if (!$isRead) {
        $readAt = '';
    }

    return [
        'id' => $id,
        'sender_name' => $senderName,
        'sender_email' => $senderEmail,
        'subject' => $subject,
        'message' => $message,
        'attachments' => array_values($attachments),
        'is_read' => $isRead,
        'created_at' => $createdAt,
        'read_at' => $readAt,
    ];
}

function load_message_notifications_repository()
{
    $path = message_notifications_repository_path();

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

    $notifications = [];

    foreach ($decoded as $record) {
        if (!is_array($record)) {
            continue;
        }

        $notifications[] = normalize_message_notification_record($record);
    }

    return $notifications;
}

function save_message_notifications_repository($notifications)
{
    if (!is_array($notifications)) {
        return false;
    }

    $normalized = [];

    foreach ($notifications as $record) {
        if (!is_array($record)) {
            continue;
        }

        $normalized[] = normalize_message_notification_record($record);
    }

    $encoded = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents(message_notifications_repository_path(), $encoded . PHP_EOL, LOCK_EX) !== false;
}

function append_message_notification($senderName, $senderEmail, $subject, $message, $attachments)
{
    $notifications = load_message_notifications_repository();

    $newRecord = normalize_message_notification_record([
        'id' => message_notification_generate_id(),
        'sender_name' => $senderName,
        'sender_email' => $senderEmail,
        'subject' => $subject,
        'message' => $message,
        'attachments' => is_array($attachments) ? $attachments : [],
        'is_read' => false,
        'created_at' => gmdate('c'),
        'read_at' => '',
    ]);

    array_unshift($notifications, $newRecord);

    if (!save_message_notifications_repository($notifications)) {
        return null;
    }

    return $newRecord;
}

function count_unread_message_notifications($notifications = null)
{
    $source = is_array($notifications) ? $notifications : load_message_notifications_repository();
    $count = 0;

    foreach ($source as $record) {
        if (!is_array($record)) {
            continue;
        }

        if (!($record['is_read'] ?? false)) {
            $count++;
        }
    }

    return $count;
}

function mark_all_message_notifications_as_read($notifications = null)
{
    $source = is_array($notifications) ? $notifications : load_message_notifications_repository();
    $changed = false;
    $readTimestamp = gmdate('c');

    foreach ($source as $index => $record) {
        if (!is_array($record)) {
            continue;
        }

        $normalized = normalize_message_notification_record($record);

        if (!$normalized['is_read']) {
            $normalized['is_read'] = true;
            $normalized['read_at'] = $readTimestamp;
            $changed = true;
        }

        $source[$index] = $normalized;
    }

    return [
        'notifications' => array_values($source),
        'changed' => $changed,
    ];
}
