<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? 'admin/';
$assetBase = $assetBase ?? '';

$isAdminSession = isset($_SESSION['user_id']);
$isStaffSession = !$isAdminSession && isset($_SESSION['staff_id']);

if (!$isAdminSession && !$isStaffSession) {
    header('Location: ' . $routeBase);
    exit;
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . $assetBase);
    exit;
}

$username = $isStaffSession
    ? trim((string) ($_SESSION['staff_name'] ?? $_SESSION['staff_email'] ?? 'staff'))
    : ((string) ($_SESSION['username'] ?? 'admin'));
$cartCount = 0;
$accountLabel = $isStaffSession ? 'STAFF' : 'Admin';
$adminHomePath = $routeBase . 'dashboard/';
$logoutPath = $routeBase . 'logout.php';
$notificationsPath = $isStaffSession
    ? ($adminHomePath . '?admin_view=bookings')
    : ($routeBase . 'notifications/');
$liveUpdatesEndpoint = $routeBase . 'notifications/live_updates.php';
$uploadDeliveryReceiptEndpoint = $routeBase . 'dashboard/upload_delivery_receipt.php';
$closeDeliveryLegEndpoint = $routeBase . 'dashboard/close_delivery_leg.php';
$manageBrandsPath = $routeBase . 'brands/';
$manageCategoriesPath = $routeBase . 'categories/';
$setGcashQrPath = $routeBase . 'gcash-qr/';
$editTocPath = $routeBase . 'toc/';

$allowedAdminPanels = $isStaffSession
    ? ['equipments', 'bookings']
    : ['equipments', 'bookings', 'reports', 'users'];

$initialAdminPanel = strtolower(trim((string) ($_GET['admin_view'] ?? '')));
if (!in_array($initialAdminPanel, $allowedAdminPanels, true)) {
    $initialAdminPanel = $isStaffSession ? 'bookings' : '';
}

require_once __DIR__ . '/config/message_notifications_repository.php';

$adminNotifications = load_message_notifications_repository();

if ($initialAdminPanel === 'bookings') {
    $markOrderReadResult = mark_all_message_notifications_as_read_by_type('order', $adminNotifications);

    if (!empty($markOrderReadResult['changed']) && !empty($markOrderReadResult['notifications']) && is_array($markOrderReadResult['notifications'])) {
        save_message_notifications_repository($markOrderReadResult['notifications']);
    }

    if (!empty($markOrderReadResult['notifications']) && is_array($markOrderReadResult['notifications'])) {
        $adminNotifications = $markOrderReadResult['notifications'];
    }
}

$adminNotificationCount = count_unread_message_notifications($adminNotifications);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/customer_orders_repository.php';
require_once __DIR__ . '/config/customer_gcash_profiles_repository.php';

$staffAccountsTable = $staffAccountsTable ?? 'staff_accounts';

function format_admin_local_datetime_label($value, $uppercase = false)
{
    $raw = trim((string) $value);

    if ($raw === '') {
        return '-';
    }

    try {
        $parsed = new DateTimeImmutable($raw);
        $parsed = $parsed->setTimezone(new DateTimeZone('Asia/Manila'));
        $label = $parsed->format('M d, Y h:i A');
    } catch (Throwable $error) {
        $label = $raw;
    }

    return $uppercase ? strtoupper($label) : $label;
}

function admin_local_timezone()
{
    if (function_exists('customer_order_timezone')) {
        $timezone = customer_order_timezone();

        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }
    }

    static $fallbackTimezone = null;

    if ($fallbackTimezone instanceof DateTimeZone) {
        return $fallbackTimezone;
    }

    try {
        $fallbackTimezone = new DateTimeZone('Asia/Manila');
    } catch (Throwable $error) {
        $fallbackTimezone = new DateTimeZone('UTC');
    }

    return $fallbackTimezone;
}

function admin_parse_local_datetime($value)
{
    $raw = trim((string) $value);

    if ($raw === '') {
        return null;
    }

    if (function_exists('customer_order_parse_datetime_value')) {
        $parsed = customer_order_parse_datetime_value($raw);

        if ($parsed instanceof DateTimeImmutable) {
            return $parsed;
        }
    }

    try {
        $parsed = new DateTimeImmutable($raw, admin_local_timezone());
    } catch (Throwable $error) {
        return null;
    }

    return $parsed->setTimezone(admin_local_timezone());
}

function admin_extract_order_usage_datetime($record)
{
    if (!is_array($record)) {
        return null;
    }

    $handoverTimestamp = admin_parse_local_datetime($record['receive_handover_confirmed_at'] ?? '');

    if ($handoverTimestamp instanceof DateTimeImmutable) {
        return $handoverTimestamp;
    }

    $receiveDate = normalize_customer_order_date($record['receive_date'] ?? '');
    $receiveTime = normalize_customer_order_time($record['receive_time'] ?? '');

    if ($receiveDate !== '') {
        if (function_exists('customer_order_parse_schedule_datetime') && $receiveTime !== '') {
            $parsedSchedule = customer_order_parse_schedule_datetime($receiveDate, $receiveTime);

            if ($parsedSchedule instanceof DateTimeImmutable) {
                return $parsedSchedule->setTimezone(admin_local_timezone());
            }
        }

        $scheduleTime = $receiveTime !== '' ? $receiveTime : '00:00';
        $fallbackSchedule = DateTimeImmutable::createFromFormat('Y-m-d H:i', $receiveDate . ' ' . $scheduleTime, admin_local_timezone());
        $fallbackScheduleErrors = DateTimeImmutable::getLastErrors();

        if (
            $fallbackSchedule instanceof DateTimeImmutable
            && ((int) ($fallbackScheduleErrors['warning_count'] ?? 0)) === 0
            && ((int) ($fallbackScheduleErrors['error_count'] ?? 0)) === 0
        ) {
            return $fallbackSchedule;
        }

        $fallbackDateOnly = DateTimeImmutable::createFromFormat('Y-m-d', $receiveDate, admin_local_timezone());
        $fallbackDateErrors = DateTimeImmutable::getLastErrors();

        if (
            $fallbackDateOnly instanceof DateTimeImmutable
            && ((int) ($fallbackDateErrors['warning_count'] ?? 0)) === 0
            && ((int) ($fallbackDateErrors['error_count'] ?? 0)) === 0
        ) {
            return $fallbackDateOnly;
        }
    }

    return admin_parse_local_datetime($record['created_at'] ?? '');
}

function admin_extract_order_report_datetime($record)
{
    if (!is_array($record)) {
        return null;
    }

    $statusToken = normalize_customer_order_status_token($record['status'] ?? 'pending');

    if ($statusToken === 'refunded') {
        $refundTimestamp = admin_parse_local_datetime($record['refund_proof_uploaded_at'] ?? '');

        if ($refundTimestamp instanceof DateTimeImmutable) {
            return $refundTimestamp;
        }
    }

    return admin_parse_local_datetime($record['created_at'] ?? '');
}

function admin_resolve_booking_item_product_key($item, $products, $nameLookup, $archivedProductsByOriginalKey, $archivedProductsByLookupLabel)
{
    if (!is_array($item)) {
        return '';
    }

    $resolvedProductKey = '';

    if (function_exists('customer_order_resolve_item_product_key')) {
        $resolvedProductKey = normalize_customer_order_product_key(
            customer_order_resolve_item_product_key($item, $products, $nameLookup)
        );
    }

    if ($resolvedProductKey === '') {
        $resolvedProductKey = normalize_customer_order_product_key($item['product_key'] ?? $item['productKey'] ?? '');
    }

    if ($resolvedProductKey === '' && function_exists('customer_order_extract_product_key_from_item_id')) {
        $resolvedProductKey = customer_order_extract_product_key_from_item_id($item['item_id'] ?? $item['itemId'] ?? '');
    }

    if ($resolvedProductKey !== '') {
        return $resolvedProductKey;
    }

    $lookupLabel = function_exists('customer_order_normalize_lookup_label')
        ? customer_order_normalize_lookup_label($item['name'] ?? '')
        : strtolower(trim((string) ($item['name'] ?? '')));

    if ($lookupLabel !== '' && isset($nameLookup[$lookupLabel])) {
        return normalize_customer_order_product_key($nameLookup[$lookupLabel]);
    }

    if ($lookupLabel !== '' && isset($archivedProductsByLookupLabel[$lookupLabel]) && is_array($archivedProductsByLookupLabel[$lookupLabel])) {
        return normalize_customer_order_product_key($archivedProductsByLookupLabel[$lookupLabel]['originalKey'] ?? '');
    }

    return '';
}

function admin_resolve_product_payload_by_key($productKey, $item, $products, $archivedProductsByOriginalKey, $archivedProductsByLookupLabel)
{
    $normalizedProductKey = normalize_customer_order_product_key($productKey);

    if ($normalizedProductKey !== '' && isset($products[$normalizedProductKey]) && is_array($products[$normalizedProductKey])) {
        return $products[$normalizedProductKey];
    }

    if (
        $normalizedProductKey !== ''
        && isset($archivedProductsByOriginalKey[$normalizedProductKey])
        && is_array($archivedProductsByOriginalKey[$normalizedProductKey])
        && is_array($archivedProductsByOriginalKey[$normalizedProductKey]['product'] ?? null)
    ) {
        return $archivedProductsByOriginalKey[$normalizedProductKey]['product'];
    }

    $lookupLabel = function_exists('customer_order_normalize_lookup_label')
        ? customer_order_normalize_lookup_label($item['name'] ?? '')
        : strtolower(trim((string) ($item['name'] ?? '')));

    if (
        $lookupLabel !== ''
        && isset($archivedProductsByLookupLabel[$lookupLabel])
        && is_array($archivedProductsByLookupLabel[$lookupLabel])
        && is_array($archivedProductsByLookupLabel[$lookupLabel]['product'] ?? null)
    ) {
        return $archivedProductsByLookupLabel[$lookupLabel]['product'];
    }

    return null;
}

function admin_calculate_booking_amount_without_penalty($record, $products, $nameLookup, $archivedProductsByOriginalKey, $archivedProductsByLookupLabel)
{
    if (!is_array($record)) {
        return 0.0;
    }

    $items = is_array($record['items'] ?? null)
        ? array_values($record['items'])
        : [];
    $amount = 0.0;

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $resolvedProductKey = admin_resolve_booking_item_product_key(
            $item,
            $products,
            $nameLookup,
            $archivedProductsByOriginalKey,
            $archivedProductsByLookupLabel
        );
        $productPayload = admin_resolve_product_payload_by_key(
            $resolvedProductKey,
            $item,
            $products,
            $archivedProductsByOriginalKey,
            $archivedProductsByLookupLabel
        );

        if (!is_array($productPayload)) {
            continue;
        }

        $basePrice = (float) ($productPayload['price'] ?? 0);

        if (!is_finite($basePrice) || $basePrice < 0) {
            $basePrice = 0;
        }

        $discountPercent = (int) ($productPayload['discountPercent'] ?? 0);
        $discountPercent = max(0, min(95, $discountPercent));

        $effectivePrice = $basePrice * (1 - ($discountPercent / 100));

        if (!is_finite($effectivePrice) || $effectivePrice < 0) {
            $effectivePrice = 0;
        }

        $qty = max(1, (int) ($item['qty'] ?? 1));
        $days = max(1, (int) ($item['days'] ?? 1));
        $lineAmount = $effectivePrice * $qty * $days;

        if (is_finite($lineAmount) && $lineAmount > 0) {
            $amount += $lineAmount;
        }
    }

    return $amount;
}

function admin_normalize_media_relative_path($value)
{
    $path = trim((string) $value);
    $path = str_replace('\\', '/', $path);

    if ($path === '' || strpos($path, '..') !== false) {
        return '';
    }

    $path = ltrim($path, '/');

    if (!preg_match('/^[a-zA-Z0-9_%\-\/.() ]+$/', $path)) {
        return '';
    }

    return $path;
}

function admin_resolve_media_absolute_path($relativePath)
{
    $normalizedRelativePath = admin_normalize_media_relative_path($relativePath);

    if ($normalizedRelativePath === '') {
        return '';
    }

    $projectRoot = realpath(__DIR__);
    if ($projectRoot === false) {
        $projectRoot = __DIR__;
    }

    $decodedRelativePath = rawurldecode($normalizedRelativePath);
    $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $decodedRelativePath);
    $absoluteDirectory = realpath(dirname($absolutePath));

    if ($absoluteDirectory === false || strpos($absoluteDirectory, $projectRoot) !== 0) {
        return '';
    }

    return $absolutePath;
}

function admin_order_thumbnail_cache_key($value)
{
    return normalize_customer_order_product_key($value);
}

function admin_order_thumbnail_absolute_path($relativePath)
{
    $normalizedRelativePath = admin_normalize_media_relative_path($relativePath);

    if ($normalizedRelativePath === '') {
        return '';
    }

    return __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($normalizedRelativePath));
}

function admin_find_cached_order_thumbnail_relative_path($thumbnailCacheKey)
{
    $cacheKey = admin_order_thumbnail_cache_key($thumbnailCacheKey);

    if ($cacheKey === '') {
        return '';
    }

    $cacheDir = 'assets/order_item_thumbnails';
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    foreach ($extensions as $extension) {
        $candidateRelativePath = $cacheDir . '/' . $cacheKey . '.' . $extension;
        $candidateAbsolutePath = admin_order_thumbnail_absolute_path($candidateRelativePath);

        if ($candidateAbsolutePath !== '' && is_file($candidateAbsolutePath)) {
            return $candidateRelativePath;
        }
    }

    return '';
}

function admin_render_order_thumbnail_from_source($sourceAbsolutePath, $targetAbsolutePath, $targetExtension)
{
    $sourcePath = trim((string) $sourceAbsolutePath);
    $targetPath = trim((string) $targetAbsolutePath);
    $extension = strtolower(trim((string) $targetExtension));

    if ($sourcePath === '' || $targetPath === '' || !is_file($sourcePath)) {
        return false;
    }

    if (!function_exists('getimagesize') || !function_exists('imagecreatetruecolor')) {
        return @copy($sourcePath, $targetPath);
    }

    $imageInfo = @getimagesize($sourcePath);
    $mimeType = is_array($imageInfo) ? strtolower((string) ($imageInfo['mime'] ?? '')) : '';

    $sourceImage = null;

    if ($mimeType === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $sourceImage = @imagecreatefromjpeg($sourcePath);
    } elseif ($mimeType === 'image/png' && function_exists('imagecreatefrompng')) {
        $sourceImage = @imagecreatefrompng($sourcePath);
    } elseif ($mimeType === 'image/gif' && function_exists('imagecreatefromgif')) {
        $sourceImage = @imagecreatefromgif($sourcePath);
    } elseif ($mimeType === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $sourceImage = @imagecreatefromwebp($sourcePath);
    }

    if (!$sourceImage) {
        return @copy($sourcePath, $targetPath);
    }

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);

    if ($sourceWidth < 1 || $sourceHeight < 1) {
        imagedestroy($sourceImage);
        return @copy($sourcePath, $targetPath);
    }

    $thumbnailSize = 180;
    $sourceSquareSize = min($sourceWidth, $sourceHeight);
    $sourceOffsetX = (int) floor(($sourceWidth - $sourceSquareSize) / 2);
    $sourceOffsetY = (int) floor(($sourceHeight - $sourceSquareSize) / 2);

    $thumbnailImage = imagecreatetruecolor($thumbnailSize, $thumbnailSize);

    if (in_array($extension, ['png', 'webp', 'gif'], true)) {
        imagealphablending($thumbnailImage, false);
        imagesavealpha($thumbnailImage, true);
        $transparent = imagecolorallocatealpha($thumbnailImage, 0, 0, 0, 127);
        imagefilledrectangle($thumbnailImage, 0, 0, $thumbnailSize, $thumbnailSize, $transparent);
    } else {
        $background = imagecolorallocate($thumbnailImage, 17, 17, 22);
        imagefilledrectangle($thumbnailImage, 0, 0, $thumbnailSize, $thumbnailSize, $background);
    }

    imagecopyresampled(
        $thumbnailImage,
        $sourceImage,
        0,
        0,
        $sourceOffsetX,
        $sourceOffsetY,
        $thumbnailSize,
        $thumbnailSize,
        $sourceSquareSize,
        $sourceSquareSize
    );

    $didSave = false;

    if ($extension === 'png' && function_exists('imagepng')) {
        $didSave = @imagepng($thumbnailImage, $targetPath, 6);
    } elseif ($extension === 'webp' && function_exists('imagewebp')) {
        $didSave = @imagewebp($thumbnailImage, $targetPath, 82);
    } elseif ($extension === 'gif' && function_exists('imagegif')) {
        $didSave = @imagegif($thumbnailImage, $targetPath);
    } elseif (function_exists('imagejpeg')) {
        $didSave = @imagejpeg($thumbnailImage, $targetPath, 85);
    }

    imagedestroy($thumbnailImage);
    imagedestroy($sourceImage);

    if (!$didSave) {
        return @copy($sourcePath, $targetPath);
    }

    return true;
}

function admin_resolve_order_thumbnail_relative_path($productKey, $sourceRelativePath)
{
    $thumbnailCacheKey = admin_order_thumbnail_cache_key($productKey);
    $normalizedSourceRelativePath = admin_normalize_media_relative_path($sourceRelativePath);

    if ($thumbnailCacheKey === '') {
        return $normalizedSourceRelativePath;
    }

    $existingThumbnailRelativePath = admin_find_cached_order_thumbnail_relative_path($thumbnailCacheKey);

    if ($normalizedSourceRelativePath === '') {
        return $existingThumbnailRelativePath;
    }

    $sourceAbsolutePath = admin_resolve_media_absolute_path($normalizedSourceRelativePath);

    if ($sourceAbsolutePath === '' || !is_file($sourceAbsolutePath)) {
        return $existingThumbnailRelativePath !== '' ? $existingThumbnailRelativePath : $normalizedSourceRelativePath;
    }

    $sourceExtension = strtolower((string) pathinfo(rawurldecode($normalizedSourceRelativePath), PATHINFO_EXTENSION));

    if ($sourceExtension === 'jpeg') {
        $sourceExtension = 'jpg';
    }

    if (!in_array($sourceExtension, ['jpg', 'png', 'webp', 'gif'], true)) {
        $sourceExtension = 'jpg';
    }

    $thumbnailDirRelativePath = 'assets/order_item_thumbnails';
    $thumbnailRelativePath = $thumbnailDirRelativePath . '/' . $thumbnailCacheKey . '.' . $sourceExtension;
    $thumbnailAbsolutePath = admin_order_thumbnail_absolute_path($thumbnailRelativePath);

    if ($thumbnailAbsolutePath === '') {
        return $existingThumbnailRelativePath !== '' ? $existingThumbnailRelativePath : $normalizedSourceRelativePath;
    }

    $thumbnailDirectory = dirname($thumbnailAbsolutePath);

    if (!is_dir($thumbnailDirectory) && !mkdir($thumbnailDirectory, 0777, true) && !is_dir($thumbnailDirectory)) {
        return $existingThumbnailRelativePath !== '' ? $existingThumbnailRelativePath : $normalizedSourceRelativePath;
    }

    if ($existingThumbnailRelativePath !== '' && $existingThumbnailRelativePath !== $thumbnailRelativePath) {
        $existingThumbnailAbsolutePath = admin_order_thumbnail_absolute_path($existingThumbnailRelativePath);

        if ($existingThumbnailAbsolutePath !== '' && is_file($existingThumbnailAbsolutePath)) {
            @unlink($existingThumbnailAbsolutePath);
        }
    }

    $sourceModifiedAt = @filemtime($sourceAbsolutePath);
    $thumbnailModifiedAt = is_file($thumbnailAbsolutePath) ? @filemtime($thumbnailAbsolutePath) : false;

    if (is_file($thumbnailAbsolutePath) && is_int($thumbnailModifiedAt) && (!is_int($sourceModifiedAt) || $thumbnailModifiedAt >= $sourceModifiedAt)) {
        return $thumbnailRelativePath;
    }

    $didRender = admin_render_order_thumbnail_from_source($sourceAbsolutePath, $thumbnailAbsolutePath, $sourceExtension);

    if ($didRender && is_file($thumbnailAbsolutePath)) {
        return $thumbnailRelativePath;
    }

    if (is_file($thumbnailAbsolutePath)) {
        return $thumbnailRelativePath;
    }

    return $existingThumbnailRelativePath !== '' ? $existingThumbnailRelativePath : $normalizedSourceRelativePath;
}

$adminUsersFlashMessage = (isset($_GET['created']) && (string) $_GET['created'] === '1')
    ? 'User account created successfully.'
    : '';

$adminCreateUserErrors = [];
$adminCreateUserValues = [
    'role' => 'customer',
    'full_name' => '',
    'email' => '',
    'account_status' => 'active'
];
$openAdminCreateUserModal = false;
$openEquipmentArchiveModal = (string) ($_GET['equipment_archive'] ?? '') === 'open';
$openEquipmentStatusModal = (string) ($_GET['equipment_statuses'] ?? '') === 'open';

if ($isStaffSession) {
    $openEquipmentArchiveModal = false;
    $openEquipmentStatusModal = false;
}

$adminEquipmentFlash = isset($_SESSION['admin_equipment_flash']) && is_array($_SESSION['admin_equipment_flash'])
    ? $_SESSION['admin_equipment_flash']
    : [];

$adminEquipmentFlashType = strtolower(trim((string) ($adminEquipmentFlash['type'] ?? '')));
if (!in_array($adminEquipmentFlashType, ['success', 'warning', 'danger'], true)) {
    $adminEquipmentFlashType = '';
}

$adminEquipmentFlashMessage = trim((string) ($adminEquipmentFlash['message'] ?? ''));
if (isset($_SESSION['admin_equipment_flash'])) {
    unset($_SESSION['admin_equipment_flash']);
}

if (!$isStaffSession && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['admin_action'] ?? '') === 'admin_create_user') {
    $roleValue = strtolower(trim((string) ($_POST['role'] ?? 'customer')));
    $fullNameValue = trim((string) ($_POST['full_name'] ?? ''));
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $accountStatusValue = strtolower(trim((string) ($_POST['account_status'] ?? 'active')));
    $passwordValue = (string) ($_POST['password'] ?? '');
    $confirmPasswordValue = (string) ($_POST['confirm_password'] ?? '');
    $adminCreatableRoles = ['admin', 'customer', 'staff'];

    $adminCreateUserValues = [
        'role' => in_array($roleValue, $adminCreatableRoles, true) ? $roleValue : 'customer',
        'full_name' => $fullNameValue,
        'email' => $emailValue,
        'account_status' => in_array($accountStatusValue, ['active', 'inactive'], true) ? $accountStatusValue : 'active'
    ];

    if (!in_array($adminCreateUserValues['role'], $adminCreatableRoles, true)) {
        $adminCreateUserErrors[] = 'Please select a valid role.';
    }

    if ($fullNameValue === '') {
        if ($adminCreateUserValues['role'] === 'admin') {
            $adminCreateUserErrors[] = 'Employee number is required.';
        } elseif ($adminCreateUserValues['role'] === 'staff') {
            $adminCreateUserErrors[] = 'Name is required.';
        } else {
            $adminCreateUserErrors[] = 'Full name is required.';
        }
    }

    if ($emailValue === '') {
        $adminCreateUserErrors[] = 'Username/Email is required.';
    }

    if (in_array($adminCreateUserValues['role'], ['customer', 'staff'], true) && !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $adminCreateUserErrors[] = 'Please provide a valid email address.';
    }

    if ($passwordValue === '' || $confirmPasswordValue === '') {
        $adminCreateUserErrors[] = 'Password and confirm password are required.';
    } elseif ($passwordValue !== $confirmPasswordValue) {
        $adminCreateUserErrors[] = 'Passwords do not match.';
    }

    if (!$adminCreateUserErrors) {
        $hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);

        if ($adminCreateUserValues['role'] === 'customer') {
            $nameParts = preg_split('/\s+/', $fullNameValue) ?: [];
            $firstName = trim((string) array_shift($nameParts));
            $lastName = trim(implode(' ', $nameParts));

            if ($firstName === '') {
                $adminCreateUserErrors[] = 'Customer first name could not be resolved.';
            } else {
                if ($lastName === '') {
                    $lastName = 'Account';
                }

                $checkCustomerStmt = $conn->prepare("SELECT id FROM {$customerAccountsTable} WHERE email = ? LIMIT 1");
                if ($checkCustomerStmt) {
                    $checkCustomerStmt->bind_param('s', $emailValue);
                    $checkCustomerStmt->execute();
                    $existingCustomerResult = $checkCustomerStmt->get_result();
                    $existingCustomer = $existingCustomerResult ? $existingCustomerResult->fetch_assoc() : null;
                    $checkCustomerStmt->close();

                    if ($existingCustomer) {
                        $adminCreateUserErrors[] = 'Customer email already exists.';
                    }
                } else {
                    $adminCreateUserErrors[] = 'Unable to validate customer email right now.';
                }

                if (!$adminCreateUserErrors) {
                    $emailVerifiedAt = $adminCreateUserValues['account_status'] === 'active' ? date('Y-m-d H:i:s') : null;
                    $insertCustomerStmt = $conn->prepare("INSERT INTO {$customerAccountsTable} (first_name, last_name, email, password, email_verified_at, privacy_policy_accepted_at) VALUES (?, ?, ?, ?, ?, NOW())");

                    if ($insertCustomerStmt) {
                        $insertCustomerStmt->bind_param('sssss', $firstName, $lastName, $emailValue, $hashedPassword, $emailVerifiedAt);

                        if (!$insertCustomerStmt->execute()) {
                            $adminCreateUserErrors[] = 'Unable to create customer account.';
                        }

                        $insertCustomerStmt->close();
                    } else {
                        $adminCreateUserErrors[] = 'Unable to prepare customer account creation.';
                    }
                }
            }
        } elseif ($adminCreateUserValues['role'] === 'staff') {
            $checkStaffStmt = $conn->prepare("SELECT id FROM {$staffAccountsTable} WHERE email = ? LIMIT 1");
            if (!$checkStaffStmt) {
                $adminCreateUserErrors[] = 'Unable to validate staff email right now.';
            } else {
                $checkStaffStmt->bind_param('s', $emailValue);
                $checkStaffStmt->execute();
                $existingStaffResult = $checkStaffStmt->get_result();
                $existingStaff = $existingStaffResult ? $existingStaffResult->fetch_assoc() : null;
                $checkStaffStmt->close();

                if ($existingStaff) {
                    $adminCreateUserErrors[] = 'Staff email already exists.';
                }
            }

            if (!$adminCreateUserErrors) {
                $insertStaffStmt = $conn->prepare("INSERT INTO {$staffAccountsTable} (name, email, password) VALUES (?, ?, ?)");

                if ($insertStaffStmt) {
                    $insertStaffStmt->bind_param('sss', $fullNameValue, $emailValue, $hashedPassword);

                    if (!$insertStaffStmt->execute()) {
                        $adminCreateUserErrors[] = 'Unable to create staff account.';
                    }

                    $insertStaffStmt->close();
                } else {
                    $adminCreateUserErrors[] = 'Unable to prepare staff account creation.';
                }
            }
        } else {
            $checkAdminStmt = $conn->prepare("SELECT id FROM {$adminAccountsTable} WHERE username = ? LIMIT 1");
            if (!$checkAdminStmt) {
                $adminCreateUserErrors[] = 'Unable to validate admin username right now.';
            } else {
                $checkAdminStmt->bind_param('s', $emailValue);
                $checkAdminStmt->execute();
                $existingAdminResult = $checkAdminStmt->get_result();
                $existingAdmin = $existingAdminResult ? $existingAdminResult->fetch_assoc() : null;
                $checkAdminStmt->close();

                if ($existingAdmin) {
                    $adminCreateUserErrors[] = 'Username already exists.';
                }
            }

            if (!$adminCreateUserErrors) {
                $insertAdminStmt = $conn->prepare("INSERT INTO {$adminAccountsTable} (username, employee_number, password) VALUES (?, ?, ?)");

                if ($insertAdminStmt) {
                    $insertAdminStmt->bind_param('sss', $emailValue, $fullNameValue, $hashedPassword);

                    if (!$insertAdminStmt->execute()) {
                        $adminCreateUserErrors[] = 'Unable to create admin account.';
                    }

                    $insertAdminStmt->close();
                } else {
                    $adminCreateUserErrors[] = 'Unable to prepare admin account creation.';
                }
            }
        }
    }

    if (!$adminCreateUserErrors) {
        header('Location: ' . $adminHomePath . '?admin_view=users&created=1');
        exit;
    }

    $openAdminCreateUserModal = true;
    $initialAdminPanel = 'users';
}

require __DIR__ . '/config/products_repository.php';
require __DIR__ . '/config/equipment_inventory_repository.php';
$products = load_products_repository();
$productBrandOptions = load_product_brands_repository();
$productBrandValueMap = product_brand_value_map($productBrandOptions);

$equipmentStatuses = normalize_equipment_statuses_collection(load_equipment_statuses_repository());
$equipmentProtectedStatuses = ['available', 'in-use'];
$didEnsureProtectedEquipmentStatuses = false;

foreach ($equipmentProtectedStatuses as $protectedEquipmentStatus) {
    if (in_array($protectedEquipmentStatus, $equipmentStatuses, true)) {
        continue;
    }

    $equipmentStatuses[] = $protectedEquipmentStatus;
    $didEnsureProtectedEquipmentStatuses = true;
}

if ($didEnsureProtectedEquipmentStatuses) {
    $equipmentStatuses = normalize_equipment_statuses_collection($equipmentStatuses);
    save_equipment_statuses_repository($equipmentStatuses);
}

$equipmentStatusLabels = [];

foreach ($equipmentStatuses as $equipmentStatusValue) {
    $normalizedStatusValue = normalize_equipment_status_token($equipmentStatusValue);

    if (isset($equipmentStatusLabels[$normalizedStatusValue])) {
        continue;
    }

    $equipmentStatusLabels[$normalizedStatusValue] = strtoupper(str_replace('-', ' ', $normalizedStatusValue));
}

$dashboardUsers = [];

$adminUsersResult = $conn->query("SELECT id, username, employee_number FROM {$adminAccountsTable} ORDER BY id ASC");
if ($adminUsersResult instanceof mysqli_result) {
    while ($adminRow = $adminUsersResult->fetch_assoc()) {
        $idValue = (int) ($adminRow['id'] ?? 0);
        if ($idValue < 1) {
            continue;
        }

        $dashboardUsers[] = [
            'prefixedId' => 'ADMIN_' . str_pad((string) $idValue, 3, '0', STR_PAD_LEFT),
            'name' => (string) ($adminRow['employee_number'] ?? ''),
            'email' => (string) ($adminRow['username'] ?? ''),
            'role' => 'ADMIN'
        ];
    }
}

$staffUsersResult = $conn->query("SELECT id, name, email FROM {$staffAccountsTable} ORDER BY id ASC");
if ($staffUsersResult instanceof mysqli_result) {
    while ($staffRow = $staffUsersResult->fetch_assoc()) {
        $idValue = (int) ($staffRow['id'] ?? 0);
        if ($idValue < 1) {
            continue;
        }

        $dashboardUsers[] = [
            'prefixedId' => 'STAFF_' . str_pad((string) $idValue, 3, '0', STR_PAD_LEFT),
            'name' => trim((string) ($staffRow['name'] ?? '')) !== '' ? trim((string) ($staffRow['name'] ?? '')) : 'Staff',
            'email' => (string) ($staffRow['email'] ?? '-'),
            'role' => 'STAFF'
        ];
    }
}

$customerUsersResult = $conn->query("SELECT id, first_name, last_name, email FROM {$customerAccountsTable} ORDER BY id ASC");
if ($customerUsersResult instanceof mysqli_result) {
    while ($customerRow = $customerUsersResult->fetch_assoc()) {
        $idValue = (int) ($customerRow['id'] ?? 0);
        if ($idValue < 1) {
            continue;
        }

        $fullName = trim((string) ($customerRow['first_name'] ?? '') . ' ' . (string) ($customerRow['last_name'] ?? ''));

        $dashboardUsers[] = [
            'prefixedId' => 'CUSTOMER_' . str_pad((string) $idValue, 3, '0', STR_PAD_LEFT),
            'name' => $fullName !== '' ? $fullName : 'Customer',
            'email' => (string) ($customerRow['email'] ?? '-'),
            'role' => 'CUSTOMER'
        ];
    }
}

$dashboardBookings = [];
$adminBookingDetails = [];
$customerBookingsRecords = load_customer_orders_repository();
$customerGcashProfiles = load_customer_gcash_profiles_repository();
$adminBookingsSignature = customer_orders_live_state_signature($customerBookingsRecords);
$priorityCustomerSuccessfulThreshold = 3;
$customerSuccessfulTransactionsById = [];

foreach ($customerBookingsRecords as $bookingRecord) {
    if (!is_array($bookingRecord)) {
        continue;
    }

    $customerId = trim((string) ($bookingRecord['customer_id'] ?? ''));

    if ($customerId === '') {
        continue;
    }

    $statusToken = normalize_customer_order_status_token($bookingRecord['status'] ?? 'pending');

    if ($statusToken !== 'completed') {
        continue;
    }

    if (!isset($customerSuccessfulTransactionsById[$customerId])) {
        $customerSuccessfulTransactionsById[$customerId] = 0;
    }

    $customerSuccessfulTransactionsById[$customerId]++;
}

if (!is_array($products)) {
    $products = [];
}

$productNameLookup = customer_order_product_name_lookup_map($products);
$equipmentInUseRequirementsByProduct = customer_order_collect_in_use_requirements_by_product(
    $customerBookingsRecords,
    $products,
    $productNameLookup
);
$archivedProducts = load_archived_products_repository();
$archivedProductsByOriginalKey = [];
$archivedProductsByLookupLabel = [];

if (is_array($archivedProducts) && $archivedProducts) {
    for ($archivedIndex = count($archivedProducts) - 1; $archivedIndex >= 0; $archivedIndex--) {
        $archivedEntry = $archivedProducts[$archivedIndex] ?? null;

        if (!is_array($archivedEntry)) {
            continue;
        }

        $archivedOriginalKey = normalize_customer_order_product_key($archivedEntry['originalKey'] ?? '');

        if ($archivedOriginalKey === '' || isset($archivedProductsByOriginalKey[$archivedOriginalKey])) {
            continue;
        }

        $archivedProduct = $archivedEntry['product'] ?? null;

        if (!is_array($archivedProduct)) {
            continue;
        }

        $archivedProductsByOriginalKey[$archivedOriginalKey] = [
            'product' => $archivedProduct,
            'originalKey' => $archivedOriginalKey,
        ];

        $archivedBrand = trim((string) ($archivedProduct['brand'] ?? ''));
        $archivedName = trim((string) ($archivedProduct['name'] ?? ''));
        $archivedLabel = customer_order_normalize_lookup_label($archivedBrand . ' ' . $archivedName);

        if ($archivedLabel !== '' && !isset($archivedProductsByLookupLabel[$archivedLabel])) {
            $archivedProductsByLookupLabel[$archivedLabel] = [
                'product' => $archivedProduct,
                'originalKey' => $archivedOriginalKey,
            ];
        }

        $archivedNameOnlyLabel = customer_order_normalize_lookup_label($archivedName);

        if ($archivedNameOnlyLabel !== '' && !isset($archivedProductsByLookupLabel[$archivedNameOnlyLabel])) {
            $archivedProductsByLookupLabel[$archivedNameOnlyLabel] = [
                'product' => $archivedProduct,
                'originalKey' => $archivedOriginalKey,
            ];
        }
    }
}

$timesUsedLast30DaysByProduct = [];
$timesUsedStatuses = ['ongoing', 'return', 'completed'];
$reportsMonthLabels = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];
$reportYearLookup = [];
$reportRowsByMonth = [];
$reportApprovedPaymentStatuses = ['approved', 'ongoing', 'return', 'completed', 'awaiting-refund'];
$reportNowDateTime = function_exists('customer_order_datetime_from_timestamp')
    ? customer_order_datetime_from_timestamp()
    : (new DateTimeImmutable('now', admin_local_timezone()));
$reportTimesUsedWindowStart = $reportNowDateTime->sub(new DateInterval('P30D'));

foreach ($reportsMonthLabels as $monthNumber => $monthLabel) {
    $reportRowsByMonth[$monthNumber] = [
        'monthNumber' => (int) $monthNumber,
        'monthLabel' => $monthLabel,
        'transactions' => 0,
        'revenue' => 0.0,
    ];
}

foreach ($customerBookingsRecords as $bookingRecord) {
    if (!is_array($bookingRecord)) {
        continue;
    }

    $createdAtDateTime = admin_extract_order_report_datetime($bookingRecord);

    if ($createdAtDateTime instanceof DateTimeImmutable) {
        $reportYearLookup[(int) $createdAtDateTime->format('Y')] = true;
    }

    $statusToken = normalize_customer_order_status_token($bookingRecord['status'] ?? 'pending');

    if (!in_array($statusToken, $timesUsedStatuses, true)) {
        continue;
    }

    $usageDateTime = admin_extract_order_usage_datetime($bookingRecord);

    if (!$usageDateTime instanceof DateTimeImmutable) {
        continue;
    }

    if ($usageDateTime < $reportTimesUsedWindowStart || $usageDateTime > $reportNowDateTime) {
        continue;
    }

    $usageItems = is_array($bookingRecord['items'] ?? null)
        ? array_values($bookingRecord['items'])
        : [];

    foreach ($usageItems as $usageItem) {
        if (!is_array($usageItem)) {
            continue;
        }

        $resolvedProductKey = admin_resolve_booking_item_product_key(
            $usageItem,
            $products,
            $productNameLookup,
            $archivedProductsByOriginalKey,
            $archivedProductsByLookupLabel
        );

        if ($resolvedProductKey === '') {
            continue;
        }

        if (!isset($timesUsedLast30DaysByProduct[$resolvedProductKey])) {
            $timesUsedLast30DaysByProduct[$resolvedProductKey] = 0;
        }

        $timesUsedLast30DaysByProduct[$resolvedProductKey] += max(1, (int) ($usageItem['qty'] ?? 1));
    }
}

$currentReportYear = (int) $reportNowDateTime->format('Y');
$reportYearLookup[$currentReportYear] = true;
$reportYearOptions = array_map('intval', array_keys($reportYearLookup));
rsort($reportYearOptions, SORT_NUMERIC);

if (!$reportYearOptions) {
    $reportYearOptions = [$currentReportYear];
}

$reportsSortColumn = strtolower(trim((string) ($_GET['reports_sort'] ?? 'month')));

if (!in_array($reportsSortColumn, ['month', 'transactions', 'revenue'], true)) {
    $reportsSortColumn = 'month';
}

$reportsDefaultSortDirection = $reportsSortColumn === 'month' ? 'asc' : 'desc';
$reportsSortDirection = strtolower(trim((string) ($_GET['reports_dir'] ?? $reportsDefaultSortDirection)));

if (!in_array($reportsSortDirection, ['asc', 'desc'], true)) {
    $reportsSortDirection = $reportsDefaultSortDirection;
}

$reportsSelectedYearRaw = trim((string) ($_GET['reports_year'] ?? ''));
$reportsSelectedYear = ctype_digit($reportsSelectedYearRaw)
    ? (int) $reportsSelectedYearRaw
    : (int) ($reportYearOptions[0] ?? $currentReportYear);

if (!in_array($reportsSelectedYear, $reportYearOptions, true)) {
    $reportsSelectedYear = (int) ($reportYearOptions[0] ?? $currentReportYear);
}

foreach ($customerBookingsRecords as $bookingRecord) {
    if (!is_array($bookingRecord)) {
        continue;
    }

    $createdAtDateTime = admin_extract_order_report_datetime($bookingRecord);

    if (!$createdAtDateTime instanceof DateTimeImmutable) {
        continue;
    }

    if ((int) $createdAtDateTime->format('Y') !== $reportsSelectedYear) {
        continue;
    }

    $monthNumber = (int) $createdAtDateTime->format('n');

    if (!isset($reportRowsByMonth[$monthNumber])) {
        continue;
    }

    $statusToken = normalize_customer_order_status_token($bookingRecord['status'] ?? 'pending');
    $bookingAmount = admin_calculate_booking_amount_without_penalty(
        $bookingRecord,
        $products,
        $productNameLookup,
        $archivedProductsByOriginalKey,
        $archivedProductsByLookupLabel
    );

    if (in_array($statusToken, $reportApprovedPaymentStatuses, true)) {
        $reportRowsByMonth[$monthNumber]['transactions']++;
        $reportRowsByMonth[$monthNumber]['revenue'] += $bookingAmount;
    }

    if ($statusToken === 'refunded') {
        $reportRowsByMonth[$monthNumber]['transactions']--;
        $reportRowsByMonth[$monthNumber]['revenue'] -= $bookingAmount;
    }
}

$reportRows = array_values($reportRowsByMonth);

usort($reportRows, static function ($left, $right) use ($reportsSortColumn, $reportsSortDirection) {
    $leftMonth = (int) ($left['monthNumber'] ?? 0);
    $rightMonth = (int) ($right['monthNumber'] ?? 0);

    if ($reportsSortColumn === 'transactions') {
        $comparison = ((int) ($left['transactions'] ?? 0)) <=> ((int) ($right['transactions'] ?? 0));

        if ($comparison === 0) {
            $comparison = $leftMonth <=> $rightMonth;
        }

        return $reportsSortDirection === 'asc' ? $comparison : -$comparison;
    }

    if ($reportsSortColumn === 'revenue') {
        $comparison = ((float) ($left['revenue'] ?? 0)) <=> ((float) ($right['revenue'] ?? 0));

        if ($comparison === 0) {
            $comparison = $leftMonth <=> $rightMonth;
        }

        return $reportsSortDirection === 'asc' ? $comparison : -$comparison;
    }

    $comparison = $leftMonth <=> $rightMonth;

    return $reportsSortDirection === 'asc' ? $comparison : -$comparison;
});

$reportsMonthNextDirection = ($reportsSortColumn === 'month' && $reportsSortDirection === 'asc')
    ? 'desc'
    : 'asc';
$reportsTransactionsNextDirection = ($reportsSortColumn === 'transactions' && $reportsSortDirection === 'asc')
    ? 'desc'
    : 'asc';
$reportsRevenueNextDirection = ($reportsSortColumn === 'revenue' && $reportsSortDirection === 'asc')
    ? 'desc'
    : 'asc';

$reportsMonthSortUrl = $adminHomePath . '?' . http_build_query([
    'admin_view' => 'reports',
    'reports_year' => (string) $reportsSelectedYear,
    'reports_sort' => 'month',
    'reports_dir' => $reportsMonthNextDirection,
]);
$reportsTransactionsSortUrl = $adminHomePath . '?' . http_build_query([
    'admin_view' => 'reports',
    'reports_year' => (string) $reportsSelectedYear,
    'reports_sort' => 'transactions',
    'reports_dir' => $reportsTransactionsNextDirection,
]);
$reportsRevenueSortUrl = $adminHomePath . '?' . http_build_query([
    'admin_view' => 'reports',
    'reports_year' => (string) $reportsSelectedYear,
    'reports_sort' => 'revenue',
    'reports_dir' => $reportsRevenueNextDirection,
]);

foreach ($customerBookingsRecords as $bookingRecord) {
    if (!is_array($bookingRecord)) {
        continue;
    }

    $bookingId = trim((string) ($bookingRecord['id'] ?? ''));
    if ($bookingId === '') {
        continue;
    }

    $bookingStatusToken = normalize_customer_order_status_token($bookingRecord['status'] ?? 'pending');
    $forReturnState = customer_order_for_return_state($bookingRecord);
    $forReturnDeadlineTimestamp = is_int($forReturnState['deadline_ts'] ?? null)
        ? (int) $forReturnState['deadline_ts']
        : null;
    $paymentMethodToken = normalize_customer_order_payment_method($bookingRecord['payment_method'] ?? '');
    $receivingMethodToken = normalize_customer_order_receiving_method($bookingRecord['receiving_method'] ?? '');
    $paymentReceiptPath = normalize_customer_order_asset_path($bookingRecord['payment_receipt_path'] ?? '');
    $paymentReceiptUploadedAt = trim((string) ($bookingRecord['payment_receipt_uploaded_at'] ?? ''));
    $receiveHandoverConfirmedAt = normalize_customer_order_handover_confirmed_at($bookingRecord['receive_handover_confirmed_at'] ?? '');
    $receiveHandoverConfirmedBy = normalize_customer_order_handover_confirmed_by($bookingRecord['receive_handover_confirmed_by'] ?? '');
    $isReceiveHandoverConfirmed = $receiveHandoverConfirmedAt !== '';
    $isForPickupReady = customer_order_is_for_pickup_state($bookingRecord);
    $refundProofPath = normalize_customer_order_asset_path($bookingRecord['refund_proof_path'] ?? '');
    $refundProofUploadedAt = trim((string) ($bookingRecord['refund_proof_uploaded_at'] ?? ''));
    $validIdPath = normalize_customer_order_asset_path($bookingRecord['valid_id_path'] ?? '');
    $validIdUploadedAt = trim((string) ($bookingRecord['valid_id_uploaded_at'] ?? ''));
    $selfieWithIdPath = normalize_customer_order_asset_path($bookingRecord['selfie_with_id_path'] ?? '');
    $selfieWithIdUploadedAt = trim((string) ($bookingRecord['selfie_with_id_uploaded_at'] ?? ''));
    $requiresReceiveDelivery = customer_order_requires_receive_delivery($bookingRecord);
    $requiresReturnDelivery = customer_order_requires_return_delivery($bookingRecord);
    $receiveDeliveryStatus = normalize_customer_order_delivery_status_token($bookingRecord['receive_delivery_status'] ?? '', 'receive');
    $receiveDeliveryReceiptPath = normalize_customer_order_asset_path($bookingRecord['receive_delivery_receipt_path'] ?? '');
    $receiveDeliveryReceiptUploadedAt = trim((string) ($bookingRecord['receive_delivery_receipt_uploaded_at'] ?? ''));
    $receiveDeliveryReceiptUploadedBy = normalize_customer_order_delivery_actor($bookingRecord['receive_delivery_receipt_uploaded_by'] ?? '');
    $receiveDeliveryReference = normalize_customer_order_delivery_reference($bookingRecord['receive_delivery_reference'] ?? '');
    $receiveDeliveryNotes = normalize_customer_order_delivery_notes($bookingRecord['receive_delivery_notes'] ?? '');
    $receiveDeliveryClosedAt = normalize_customer_order_delivery_closed_at($bookingRecord['receive_delivery_closed_at'] ?? '');
    $receiveDeliveryClosedBy = normalize_customer_order_delivery_actor($bookingRecord['receive_delivery_closed_by'] ?? '');
    $returnDeliveryStatus = normalize_customer_order_delivery_status_token($bookingRecord['return_delivery_status'] ?? '', 'return');
    $returnDeliveryReceiptPath = normalize_customer_order_asset_path($bookingRecord['return_delivery_receipt_path'] ?? '');
    $returnDeliveryReceiptUploadedAt = trim((string) ($bookingRecord['return_delivery_receipt_uploaded_at'] ?? ''));
    $returnDeliveryReceiptUploadedBy = normalize_customer_order_delivery_actor($bookingRecord['return_delivery_receipt_uploaded_by'] ?? '');
    $returnDeliveryReference = normalize_customer_order_delivery_reference($bookingRecord['return_delivery_reference'] ?? '');
    $returnDeliveryNotes = normalize_customer_order_delivery_notes($bookingRecord['return_delivery_notes'] ?? '');
    $returnDeliveryClosedAt = normalize_customer_order_delivery_closed_at($bookingRecord['return_delivery_closed_at'] ?? '');
    $returnDeliveryClosedBy = normalize_customer_order_delivery_actor($bookingRecord['return_delivery_closed_by'] ?? '');
    $requiresIdentityDocuments = customer_order_requires_identity_documents($bookingRecord);
    $customerGcashProfile = find_customer_gcash_profile_for_customer(
        (string) ($bookingRecord['customer_id'] ?? ''),
        $customerGcashProfiles
    );
    $customerGcashName = trim((string) ($customerGcashProfile['gcash_name'] ?? ''));
    $customerGcashNumber = trim((string) ($customerGcashProfile['gcash_number'] ?? ''));
    $isWaitingForPaymentReceipt = $bookingStatusToken === 'pending'
        && $paymentMethodToken === 'gcash'
        && $paymentReceiptPath === '';
    $isWaitingForPaymentReview = $bookingStatusToken === 'pending'
        && $paymentMethodToken === 'gcash'
        && $paymentReceiptPath !== '';
    $adminPaymentStageLabel = customer_order_admin_payment_stage_label($bookingRecord);
    $bookingStatusLabel = strtoupper(str_replace('-', ' ', $bookingStatusToken));

    if ($bookingStatusToken === 'return') {
        $bookingStatusLabel = 'FOR RETURN';
    }

    if ($isForPickupReady) {
        $bookingStatusLabel = 'FOR PICKUP';
    }

    if ($isWaitingForPaymentReview) {
        $bookingStatusLabel = 'PAYMENT REVIEW';
    }

    if ($isWaitingForPaymentReceipt) {
        $bookingStatusLabel = 'PENDING RECEIPT';
    }

    if ($adminPaymentStageLabel !== '') {
        $bookingStatusLabel = $adminPaymentStageLabel;
    }
    $bookingStatusClass = 'status-' . $bookingStatusToken;

    if ($isForPickupReady) {
        $bookingStatusClass = 'status-approved';
    }

    if ($isWaitingForPaymentReview) {
        $bookingStatusClass = 'status-review';
    }

    if ($isWaitingForPaymentReceipt) {
        $bookingStatusClass = 'status-waiting-receipt';
    }
    $paymentReceiptUrl = $paymentReceiptPath !== ''
        ? $assetBase . ltrim($paymentReceiptPath, '/')
        : '';
    $refundProofUrl = $refundProofPath !== ''
        ? $assetBase . ltrim($refundProofPath, '/')
        : '';
    $validIdUrl = $validIdPath !== ''
        ? $assetBase . ltrim($validIdPath, '/')
        : '';
    $selfieWithIdUrl = $selfieWithIdPath !== ''
        ? $assetBase . ltrim($selfieWithIdPath, '/')
        : '';
    $receiveDeliveryReceiptUrl = $receiveDeliveryReceiptPath !== ''
        ? $assetBase . ltrim($receiveDeliveryReceiptPath, '/')
        : '';
    $returnDeliveryReceiptUrl = $returnDeliveryReceiptPath !== ''
        ? $assetBase . ltrim($returnDeliveryReceiptPath, '/')
        : '';
    $bookingTimestampRaw = trim((string) ($bookingRecord['created_at'] ?? ''));
    $bookingTimestampLabel = format_admin_local_datetime_label($bookingTimestampRaw, true);
    $bookingCustomerId = trim((string) ($bookingRecord['customer_id'] ?? ''));
    $successfulTransactionCount = max(0, (int) ($customerSuccessfulTransactionsById[$bookingCustomerId] ?? 0));
    $isPriorityCustomer = $bookingCustomerId !== '' && $successfulTransactionCount >= $priorityCustomerSuccessfulThreshold;
    $isPendingRequest = $bookingStatusToken === 'pending';

    $customerName = trim((string) ($bookingRecord['customer_name'] ?? ''));
    if ($customerName === '') {
        $customerName = 'Customer #' . trim((string) ($bookingRecord['customer_id'] ?? ''));
    }

    $orderNumberLabel = strtoupper($bookingId);

    $dashboardBookings[] = [
        'id' => $bookingId,
        'name' => $customerName,
        'order' => $orderNumberLabel,
        'time' => $bookingTimestampLabel,
        'status' => $bookingStatusLabel,
        'statusClass' => $bookingStatusClass,
        'isPendingRequest' => $isPendingRequest,
        'isPriorityCustomer' => $isPriorityCustomer,
        'showPriorityStar' => $isPendingRequest && $isPriorityCustomer,
    ];

    $bookingItemsWithImages = [];
    $bookingItems = is_array($bookingRecord['items'] ?? null) ? array_values($bookingRecord['items']) : [];

    foreach ($bookingItems as $bookingItem) {
        if (!is_array($bookingItem)) {
            continue;
        }

        $resolvedProductKey = normalize_customer_order_product_key($bookingItem['product_key'] ?? $bookingItem['productKey'] ?? '');
        $itemLookupLabel = customer_order_normalize_lookup_label($bookingItem['name'] ?? '');

        if ($resolvedProductKey === '') {
            $resolvedProductKey = customer_order_extract_product_key_from_item_id($bookingItem['item_id'] ?? $bookingItem['itemId'] ?? '');
        }

        if ($resolvedProductKey === '' && $itemLookupLabel !== '' && isset($productNameLookup[$itemLookupLabel])) {
            $resolvedProductKey = normalize_customer_order_product_key($productNameLookup[$itemLookupLabel]);
        }

        $itemImagePath = admin_normalize_media_relative_path($bookingItem['image_path'] ?? $bookingItem['imagePath'] ?? '');

        if ($itemImagePath === '' && $resolvedProductKey !== '') {
            if (isset($products[$resolvedProductKey]) && is_array($products[$resolvedProductKey])) {
                $itemImagePath = admin_normalize_media_relative_path($products[$resolvedProductKey]['cameraImage'] ?? '');
            }

            if ($itemImagePath === '' && isset($archivedProductsByOriginalKey[$resolvedProductKey]) && is_array($archivedProductsByOriginalKey[$resolvedProductKey])) {
                $archivedProductPayload = $archivedProductsByOriginalKey[$resolvedProductKey]['product'] ?? null;

                if (is_array($archivedProductPayload)) {
                    $itemImagePath = admin_normalize_media_relative_path($archivedProductPayload['cameraImage'] ?? '');
                }
            }
        }

        if ($itemImagePath === '' && $itemLookupLabel !== '' && isset($archivedProductsByLookupLabel[$itemLookupLabel])) {
            $archivedProductPayload = $archivedProductsByLookupLabel[$itemLookupLabel]['product'] ?? null;

            if (is_array($archivedProductPayload)) {
                $itemImagePath = admin_normalize_media_relative_path($archivedProductPayload['cameraImage'] ?? '');
            }

            if ($resolvedProductKey === '') {
                $resolvedProductKey = normalize_customer_order_product_key($archivedProductsByLookupLabel[$itemLookupLabel]['originalKey'] ?? '');
            }
        }

        $thumbnailCacheKey = $resolvedProductKey !== ''
            ? $resolvedProductKey
            : normalize_customer_order_product_key($bookingItem['name'] ?? '');
        $itemThumbnailPath = admin_resolve_order_thumbnail_relative_path($thumbnailCacheKey, $itemImagePath);
        $itemDisplayImagePath = $itemThumbnailPath !== '' ? $itemThumbnailPath : $itemImagePath;

        $normalizedBookingItem = $bookingItem;

        if ($resolvedProductKey !== '') {
            $normalizedBookingItem['product_key'] = $resolvedProductKey;
        }

        $normalizedBookingItem['imageSourcePath'] = $itemImagePath;
        $normalizedBookingItem['thumbnailPath'] = $itemThumbnailPath;
        $normalizedBookingItem['imagePath'] = $itemDisplayImagePath;
        $normalizedBookingItem['imageUrl'] = $itemDisplayImagePath !== ''
            ? $assetBase . ltrim($itemDisplayImagePath, '/')
            : '';

        $bookingItemsWithImages[] = $normalizedBookingItem;
    }

    $adminBookingDetails[] = [
        'id' => $bookingId,
        'name' => $customerName,
        'email' => trim((string) ($bookingRecord['customer_email'] ?? '')),
        'orderNumber' => $orderNumberLabel,
        'timestamp' => $bookingTimestampLabel,
        'status' => $bookingStatusLabel,
        'statusClass' => $bookingStatusClass,
        'statusToken' => $bookingStatusToken,
        'isPriorityCustomer' => $isPriorityCustomer,
        'successfulTransactions' => $successfulTransactionCount,
        'items' => $bookingItemsWithImages,
        'receiveDate' => (string) ($bookingRecord['receive_date'] ?? ''),
        'receiveTime' => (string) ($bookingRecord['receive_time'] ?? ''),
        'returnDate' => (string) ($bookingRecord['return_date'] ?? ''),
        'returnTime' => (string) ($bookingRecord['return_time'] ?? ''),
        'place' => (string) ($bookingRecord['place'] ?? ''),
        'receivingMethod' => $receivingMethodToken,
        'returningMethod' => (string) ($bookingRecord['returning_method'] ?? ''),
        'courier' => (string) ($bookingRecord['courier'] ?? ''),
        'cancelReason' => (string) ($bookingRecord['cancel_reason'] ?? ''),
        'cancelBy' => (string) ($bookingRecord['canceled_by'] ?? ''),
        'paymentMethod' => $paymentMethodToken,
        'forPickupReady' => $isForPickupReady,
        'receiveHandoverConfirmed' => $isReceiveHandoverConfirmed,
        'receiveHandoverConfirmedAt' => $receiveHandoverConfirmedAt,
        'receiveHandoverConfirmedBy' => $receiveHandoverConfirmedBy,
        'customerGcashName' => $customerGcashName,
        'customerGcashNumber' => $customerGcashNumber,
        'paymentReceiptPath' => $paymentReceiptPath,
        'paymentReceiptUrl' => $paymentReceiptUrl,
        'paymentReceiptUploadedAt' => $paymentReceiptUploadedAt,
        'refundProofPath' => $refundProofPath,
        'refundProofUrl' => $refundProofUrl,
        'refundProofUploadedAt' => $refundProofUploadedAt,
        'validIdPath' => $validIdPath,
        'validIdUrl' => $validIdUrl,
        'validIdUploadedAt' => $validIdUploadedAt,
        'selfieWithIdPath' => $selfieWithIdPath,
        'selfieWithIdUrl' => $selfieWithIdUrl,
        'selfieWithIdUploadedAt' => $selfieWithIdUploadedAt,
        'requiresReceiveDelivery' => $requiresReceiveDelivery,
        'requiresReturnDelivery' => $requiresReturnDelivery,
        'receiveDeliveryStatus' => $receiveDeliveryStatus,
        'receiveDeliveryReceiptPath' => $receiveDeliveryReceiptPath,
        'receiveDeliveryReceiptUrl' => $receiveDeliveryReceiptUrl,
        'receiveDeliveryReceiptUploadedAt' => $receiveDeliveryReceiptUploadedAt,
        'receiveDeliveryReceiptUploadedBy' => $receiveDeliveryReceiptUploadedBy,
        'receiveDeliveryReference' => $receiveDeliveryReference,
        'receiveDeliveryNotes' => $receiveDeliveryNotes,
        'receiveDeliveryClosedAt' => $receiveDeliveryClosedAt,
        'receiveDeliveryClosedBy' => $receiveDeliveryClosedBy,
        'returnDeliveryStatus' => $returnDeliveryStatus,
        'returnDeliveryReceiptPath' => $returnDeliveryReceiptPath,
        'returnDeliveryReceiptUrl' => $returnDeliveryReceiptUrl,
        'returnDeliveryReceiptUploadedAt' => $returnDeliveryReceiptUploadedAt,
        'returnDeliveryReceiptUploadedBy' => $returnDeliveryReceiptUploadedBy,
        'returnDeliveryReference' => $returnDeliveryReference,
        'returnDeliveryNotes' => $returnDeliveryNotes,
        'returnDeliveryClosedAt' => $returnDeliveryClosedAt,
        'returnDeliveryClosedBy' => $returnDeliveryClosedBy,
        'requiresIdentityDocuments' => $requiresIdentityDocuments,
        'waitingForPaymentReceipt' => $isWaitingForPaymentReceipt,
        'waitingForPaymentReview' => $isWaitingForPaymentReview,
        'forReturnGraceSeconds' => (int) ($forReturnState['grace_seconds'] ?? customer_order_for_return_grace_seconds()),
        'forReturnPenaltyPerHour' => (int) ($forReturnState['penalty_per_hour'] ?? customer_order_for_return_penalty_per_hour()),
        'forReturnDeadlineAt' => $forReturnDeadlineTimestamp !== null
            ? customer_order_now_iso8601($forReturnDeadlineTimestamp)
            : '',
        'forReturnRemainingSeconds' => max(0, (int) ($forReturnState['remaining_seconds'] ?? 0)),
        'forReturnOverdueSeconds' => max(0, (int) ($forReturnState['overdue_seconds'] ?? 0)),
        'forReturnPenaltyHours' => max(0, (int) ($forReturnState['penalty_hours'] ?? 0)),
        'forReturnPenaltyAmount' => max(0, (int) ($forReturnState['penalty_amount'] ?? 0)),
    ];
}

$priorityPendingBookings = [];
$regularPendingBookings = [];
$nonPendingBookings = [];

foreach ($dashboardBookings as $dashboardBookingRow) {
    $isPendingRequest = !empty($dashboardBookingRow['isPendingRequest']);
    $isPriorityCustomer = !empty($dashboardBookingRow['isPriorityCustomer']);

    if (!$isPendingRequest) {
        $nonPendingBookings[] = $dashboardBookingRow;
        continue;
    }

    if ($isPriorityCustomer) {
        $priorityPendingBookings[] = $dashboardBookingRow;
        continue;
    }

    $regularPendingBookings[] = $dashboardBookingRow;
}

$dashboardBookings = array_merge($priorityPendingBookings, $regularPendingBookings, $nonPendingBookings);

if (!is_array($products)) {
    $products = [];
}

$equipmentInventory = sync_equipment_inventory_with_products(
    $products,
    load_equipment_inventory_repository(),
    12
);

$archivedEquipmentUnits = load_archived_equipment_units_repository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminAction = (string) ($_POST['admin_action'] ?? '');

    if (strpos($adminAction, 'equipment_') === 0) {
        $flashType = 'success';
        $flashMessage = '';
        $openArchiveOnRedirect = false;
        $openStatusOnRedirect = false;

        $staffRestrictedEquipmentActions = [
            'equipment_add_status',
            'equipment_rename_status',
            'equipment_delete_status',
            'equipment_add_quantity',
            'equipment_remove_unit',
            'equipment_restore_unit'
        ];

        try {
            if ($isStaffSession && in_array($adminAction, $staffRestrictedEquipmentActions, true)) {
                throw new RuntimeException('Staff can modify equipment status only.');
            }

            if ($adminAction === 'equipment_add_status') {
                $openStatusOnRedirect = true;
                $statusLabel = trim((string) ($_POST['status_label'] ?? ''));
                $statusToken = normalize_equipment_status_token($statusLabel);

                if ($statusLabel === '') {
                    throw new RuntimeException('Status name is required.');
                }

                if (in_array($statusToken, $equipmentStatuses, true)) {
                    throw new RuntimeException('Status already exists.');
                }

                $equipmentStatuses[] = $statusToken;
                $equipmentStatuses = normalize_equipment_statuses_collection($equipmentStatuses);

                if (!save_equipment_statuses_repository($equipmentStatuses)) {
                    throw new RuntimeException('Unable to save equipment statuses.');
                }

                $flashMessage = 'Status added: ' . strtoupper(str_replace('-', ' ', $statusToken)) . '.';
            } elseif ($adminAction === 'equipment_rename_status') {
                $openStatusOnRedirect = true;
                $oldStatus = normalize_equipment_status_token($_POST['old_status'] ?? '');
                $statusLabel = trim((string) ($_POST['status_label'] ?? ''));
                $newStatus = normalize_equipment_status_token($statusLabel);

                if ($statusLabel === '') {
                    throw new RuntimeException('Status name is required.');
                }

                if (!in_array($oldStatus, $equipmentStatuses, true)) {
                    throw new RuntimeException('Original status was not found.');
                }

                if (in_array($oldStatus, $equipmentProtectedStatuses, true)) {
                    throw new RuntimeException('AVAILABLE and IN USE are protected statuses and cannot be renamed.');
                }

                if ($newStatus !== $oldStatus && in_array($newStatus, $equipmentStatuses, true)) {
                    throw new RuntimeException('A status with that name already exists.');
                }

                foreach ($equipmentStatuses as &$equipmentStatusRef) {
                    if ($equipmentStatusRef === $oldStatus) {
                        $equipmentStatusRef = $newStatus;
                    }
                }
                unset($equipmentStatusRef);

                $equipmentStatuses = normalize_equipment_statuses_collection($equipmentStatuses);
                $equipmentInventory = remap_equipment_status_in_inventory($equipmentInventory, $oldStatus, $newStatus);
                $archivedEquipmentUnits = remap_equipment_status_in_archived_units($archivedEquipmentUnits, $oldStatus, $newStatus);

                if (!save_equipment_statuses_repository($equipmentStatuses)) {
                    throw new RuntimeException('Unable to save equipment statuses.');
                }

                if (!save_equipment_inventory_repository($equipmentInventory)) {
                    throw new RuntimeException('Unable to save equipment inventory.');
                }

                if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                    throw new RuntimeException('Unable to save removed equipment list.');
                }

                $flashMessage = 'Status renamed to ' . strtoupper(str_replace('-', ' ', $newStatus)) . '.';
            } elseif ($adminAction === 'equipment_delete_status') {
                $openStatusOnRedirect = true;
                $statusToDelete = normalize_equipment_status_token($_POST['status'] ?? '');

                if (!in_array($statusToDelete, $equipmentStatuses, true)) {
                    throw new RuntimeException('Status was not found.');
                }

                if (in_array($statusToDelete, $equipmentProtectedStatuses, true)) {
                    throw new RuntimeException('AVAILABLE and IN USE are protected statuses and cannot be removed.');
                }

                if (count($equipmentStatuses) <= 1) {
                    throw new RuntimeException('At least one status must remain.');
                }

                $remainingStatuses = array_values(array_filter($equipmentStatuses, static function ($statusValue) use ($statusToDelete) {
                    return $statusValue !== $statusToDelete;
                }));

                $replacementStatus = in_array('available', $remainingStatuses, true)
                    ? 'available'
                    : ($remainingStatuses[0] ?? 'available');

                $equipmentStatuses = normalize_equipment_statuses_collection($remainingStatuses);
                $equipmentInventory = remap_equipment_status_in_inventory($equipmentInventory, $statusToDelete, $replacementStatus);
                $archivedEquipmentUnits = remap_equipment_status_in_archived_units($archivedEquipmentUnits, $statusToDelete, $replacementStatus);

                if (!save_equipment_statuses_repository($equipmentStatuses)) {
                    throw new RuntimeException('Unable to save equipment statuses.');
                }

                if (!save_equipment_inventory_repository($equipmentInventory)) {
                    throw new RuntimeException('Unable to save equipment inventory.');
                }

                if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                    throw new RuntimeException('Unable to save removed equipment list.');
                }

                $flashType = 'warning';
                $flashMessage = 'Status removed and reassigned to ' . strtoupper(str_replace('-', ' ', $replacementStatus)) . '.';
            } elseif ($adminAction === 'equipment_add_quantity') {
                $productKey = trim((string) ($_POST['product_key'] ?? ''));
                $requestedCount = max(1, min(200, (int) ($_POST['quantity'] ?? 1)));

                if ($productKey === '' || !isset($products[$productKey]) || !is_array($products[$productKey])) {
                    throw new RuntimeException('Featured product was not found.');
                }

                $restoreCandidates = [];

                foreach ($archivedEquipmentUnits as $archivedUnitEntry) {
                    if (!is_array($archivedUnitEntry)) {
                        continue;
                    }

                    $archivedProductKey = trim((string) ($archivedUnitEntry['productKey'] ?? ''));
                    $archivedKey = trim((string) ($archivedUnitEntry['archiveKey'] ?? ''));

                    if ($archivedProductKey !== $productKey || $archivedKey === '') {
                        continue;
                    }

                    $restoreCandidates[] = [
                        'archiveKey' => $archivedKey,
                        'serial' => max(0, (int) (($archivedUnitEntry['unit']['serial'] ?? 0))),
                        'archivedAt' => trim((string) ($archivedUnitEntry['archivedAt'] ?? ''))
                    ];
                }

                usort($restoreCandidates, static function ($left, $right) {
                    $serialCompare = ((int) ($left['serial'] ?? 0)) <=> ((int) ($right['serial'] ?? 0));
                    if ($serialCompare !== 0) {
                        return $serialCompare;
                    }

                    return strcmp((string) ($left['archivedAt'] ?? ''), (string) ($right['archivedAt'] ?? ''));
                });

                $restoredCount = 0;

                foreach ($restoreCandidates as $candidate) {
                    if ($restoredCount >= $requestedCount) {
                        break;
                    }

                    try {
                        $restoreResult = restore_archived_equipment_unit(
                            $equipmentInventory,
                            $archivedEquipmentUnits,
                            $products,
                            (string) ($candidate['archiveKey'] ?? '')
                        );

                        $equipmentInventory = $restoreResult['inventory'];
                        $archivedEquipmentUnits = $restoreResult['archivedUnits'];
                        $restoredCount++;
                    } catch (Throwable $restoreError) {
                        continue;
                    }
                }

                $newlyCreatedCount = max(0, $requestedCount - $restoredCount);

                if ($newlyCreatedCount > 0) {
                    $equipmentInventory = add_equipment_inventory_units($equipmentInventory, $productKey, $newlyCreatedCount);
                }

                if ($restoredCount > 0) {
                    if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                        throw new RuntimeException('Unable to save removed equipment list.');
                    }

                    if ($newlyCreatedCount > 0) {
                        $flashMessage = 'Added ' . $requestedCount . ' unit(s): ' . $restoredCount . ' restored ID(s), ' . $newlyCreatedCount . ' new ID(s).';
                    } else {
                        $flashMessage = 'Added ' . $requestedCount . ' unit(s) by restoring removed ID(s).';
                    }
                } else {
                    $flashMessage = 'Added ' . $requestedCount . ' unit(s) with new ID(s).';
                }

                $equipmentInventory = sync_equipment_inventory_with_products($products, $equipmentInventory, 12);

                if (!save_equipment_inventory_repository($equipmentInventory)) {
                    throw new RuntimeException('Unable to save equipment inventory.');
                }
            } elseif ($adminAction === 'equipment_remove_unit') {
                $productKey = trim((string) ($_POST['product_key'] ?? ''));
                $serial = (int) ($_POST['serial'] ?? -1);

                if ($productKey === '' || !isset($products[$productKey]) || !is_array($products[$productKey])) {
                    throw new RuntimeException('Featured product was not found.');
                }

                if (!isset($equipmentInventory[$productKey]) || !is_array($equipmentInventory[$productKey])) {
                    throw new RuntimeException('Equipment model was not found in inventory.');
                }

                $activeUnits = isset($equipmentInventory[$productKey]['units']) && is_array($equipmentInventory[$productKey]['units'])
                    ? array_values($equipmentInventory[$productKey]['units'])
                    : [];

                if (!$activeUnits) {
                    throw new RuntimeException('No active equipment units found for this product.');
                }

                if (count($activeUnits) <= 1) {
                    $archiveProductResult = archive_product_record($products, $productKey, __DIR__);
                    $archiveProductKey = trim((string) ($archiveProductResult['archivedEntry']['archiveKey'] ?? ''));

                    $archiveInventoryResult = archive_all_equipment_units_for_product(
                        $equipmentInventory,
                        $archivedEquipmentUnits,
                        $products,
                        $productKey,
                        'Archived because last active quantity was removed.',
                        $archiveProductKey
                    );

                    $equipmentInventory = $archiveInventoryResult['inventory'];
                    $archivedEquipmentUnits = $archiveInventoryResult['archivedUnits'];
                    $products = $archiveProductResult['products'];

                    if (!save_archived_products_repository($archiveProductResult['archivedProducts'])) {
                        throw new RuntimeException('Unable to save archived products.');
                    }

                    if (!save_products_repository($products)) {
                        throw new RuntimeException('Unable to save active products.');
                    }

                    if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                        throw new RuntimeException('Unable to save removed equipment list.');
                    }

                    $equipmentInventory = sync_equipment_inventory_with_products($products, $equipmentInventory, 12);

                    if (!save_equipment_inventory_repository($equipmentInventory)) {
                        throw new RuntimeException('Unable to save equipment inventory.');
                    }

                    $flashType = 'warning';
                    $flashMessage = 'Last quantity removed. Featured product was archived together with all equipment units.';
                } else {
                    $archiveUnitResult = archive_equipment_unit(
                        $equipmentInventory,
                        $archivedEquipmentUnits,
                        $products,
                        $productKey,
                        $serial,
                        'Removed from active inventory'
                    );

                    $equipmentInventory = $archiveUnitResult['inventory'];
                    $archivedEquipmentUnits = $archiveUnitResult['archivedUnits'];

                    if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                        throw new RuntimeException('Unable to save removed equipment list.');
                    }

                    $equipmentInventory = sync_equipment_inventory_with_products($products, $equipmentInventory, 12);

                    if (!save_equipment_inventory_repository($equipmentInventory)) {
                        throw new RuntimeException('Unable to save equipment inventory.');
                    }

                    $flashMessage = 'Equipment unit removed from active inventory.';
                }
            } elseif ($adminAction === 'equipment_restore_unit') {
                $openArchiveOnRedirect = true;
                $archiveKey = trim((string) ($_POST['archive_key'] ?? ''));

                if ($archiveKey === '') {
                    throw new RuntimeException('Archive key is required.');
                }

                $targetArchivedEntry = null;

                foreach ($archivedEquipmentUnits as $archivedUnitEntry) {
                    if (!is_array($archivedUnitEntry)) {
                        continue;
                    }

                    if (trim((string) ($archivedUnitEntry['archiveKey'] ?? '')) === $archiveKey) {
                        $targetArchivedEntry = $archivedUnitEntry;
                        break;
                    }
                }

                if (!is_array($targetArchivedEntry)) {
                    throw new RuntimeException('Archived equipment unit was not found.');
                }

                $targetProductKey = trim((string) ($targetArchivedEntry['productKey'] ?? ''));
                $productWasRestored = false;

                if ($targetProductKey === '') {
                    throw new RuntimeException('Archived equipment record is missing the product key.');
                }

                if (!isset($products[$targetProductKey]) || !is_array($products[$targetProductKey])) {
                    $archivedProducts = load_archived_products_repository();
                    $matchingProductArchiveKey = '';
                    $matchingArchivedAt = '';

                    foreach ($archivedProducts as $archivedProductEntry) {
                        if (!is_array($archivedProductEntry)) {
                            continue;
                        }

                        $originalKey = trim((string) ($archivedProductEntry['originalKey'] ?? ''));
                        $candidateArchiveKey = trim((string) ($archivedProductEntry['archiveKey'] ?? ''));
                        $candidateArchivedAt = trim((string) ($archivedProductEntry['archivedAt'] ?? ''));

                        if ($originalKey !== $targetProductKey || $candidateArchiveKey === '') {
                            continue;
                        }

                        if ($matchingArchivedAt === '' || strcmp($candidateArchivedAt, $matchingArchivedAt) > 0) {
                            $matchingArchivedAt = $candidateArchivedAt;
                            $matchingProductArchiveKey = $candidateArchiveKey;
                        }
                    }

                    if ($matchingProductArchiveKey === '') {
                        throw new RuntimeException('Cannot restore this unit because its featured product is no longer available.');
                    }

                    $restoreProductResult = restore_archived_product_record(
                        $products,
                        $archivedProducts,
                        $matchingProductArchiveKey,
                        __DIR__
                    );

                    $products = $restoreProductResult['products'];
                    $archivedProducts = $restoreProductResult['archivedProducts'];
                    $restoredProductKey = trim((string) ($restoreProductResult['restoredKey'] ?? ''));

                    if ($restoredProductKey !== '' && $restoredProductKey !== $targetProductKey) {
                        foreach ($archivedEquipmentUnits as &$archivedUnitRef) {
                            if (!is_array($archivedUnitRef)) {
                                continue;
                            }

                            if (trim((string) ($archivedUnitRef['productKey'] ?? '')) !== $targetProductKey) {
                                continue;
                            }

                            $archivedUnitRef['productKey'] = $restoredProductKey;
                        }
                        unset($archivedUnitRef);

                        $targetProductKey = $restoredProductKey;
                    }

                    if (!save_products_repository($products)) {
                        throw new RuntimeException('Unable to save active products.');
                    }

                    if (!save_archived_products_repository($archivedProducts)) {
                        throw new RuntimeException('Unable to save archived products.');
                    }

                    $productWasRestored = true;
                }

                $restoreUnitResult = restore_archived_equipment_unit(
                    $equipmentInventory,
                    $archivedEquipmentUnits,
                    $products,
                    $archiveKey
                );

                $equipmentInventory = $restoreUnitResult['inventory'];
                $archivedEquipmentUnits = $restoreUnitResult['archivedUnits'];

                if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                    throw new RuntimeException('Unable to save removed equipment list.');
                }

                $equipmentInventory = sync_equipment_inventory_with_products($products, $equipmentInventory, 12);

                if (!save_equipment_inventory_repository($equipmentInventory)) {
                    throw new RuntimeException('Unable to save equipment inventory.');
                }

                $flashMessage = $productWasRestored
                    ? 'Equipment unit restored. Featured product was restored automatically.'
                    : 'Equipment unit restored successfully.';
            } elseif ($adminAction === 'equipment_update_status') {
                $productKey = trim((string) ($_POST['product_key'] ?? ''));
                $normalizedProductKey = normalize_customer_order_product_key($productKey);
                $serial = (int) ($_POST['serial'] ?? -1);
                $status = normalize_equipment_status_token($_POST['status'] ?? 'available');
                $currentUnitStatus = '';

                if (isset($equipmentInventory[$productKey]) && is_array($equipmentInventory[$productKey])) {
                    $productUnits = isset($equipmentInventory[$productKey]['units']) && is_array($equipmentInventory[$productKey]['units'])
                        ? $equipmentInventory[$productKey]['units']
                        : [];

                    foreach ($productUnits as $productUnit) {
                        if (!is_array($productUnit)) {
                            continue;
                        }

                        if ((int) ($productUnit['serial'] ?? -1) !== $serial) {
                            continue;
                        }

                        $currentUnitStatus = normalize_equipment_status_token($productUnit['status'] ?? 'available');
                        break;
                    }
                }

                if (!in_array($status, $equipmentStatuses, true)) {
                    throw new RuntimeException('Selected status is no longer available.');
                }

                if ($status === 'in-use' && $currentUnitStatus !== 'in-use') {
                    throw new RuntimeException('IN USE is adaptive and cannot be set manually.');
                }

                $requiredInUseForProduct = max(0, (int) ($equipmentInUseRequirementsByProduct[$normalizedProductKey] ?? 0));

                if ($requiredInUseForProduct > 0 && $currentUnitStatus === 'in-use' && $status !== 'in-use') {
                    throw new RuntimeException('This unit is currently locked to IN USE by an ongoing booking.');
                }

                $equipmentInventory = update_equipment_unit_status(
                    $equipmentInventory,
                    $productKey,
                    $serial,
                    $status
                );

                $equipmentInventory = sync_equipment_inventory_with_products($products, $equipmentInventory, 12);

                if (!save_equipment_inventory_repository($equipmentInventory)) {
                    throw new RuntimeException('Unable to save equipment inventory.');
                }

                $flashMessage = 'Equipment status updated.';
            }
        } catch (Throwable $equipmentError) {
            $flashType = 'danger';
            $flashMessage = $equipmentError->getMessage();
        }

        $_SESSION['admin_equipment_flash'] = [
            'type' => $flashType,
            'message' => $flashMessage
        ];

        $redirectParams = [
            'admin_view' => 'equipments'
        ];

        if ($openArchiveOnRedirect) {
            $redirectParams['equipment_archive'] = 'open';
        }

        if ($openStatusOnRedirect) {
            $redirectParams['equipment_statuses'] = 'open';
        }

        header('Location: ' . $adminHomePath . '?' . http_build_query($redirectParams));
        exit;
    }
}

$equipmentRows = [];
$equipmentUnitCountsByProduct = [];

foreach ($products as $productKey => $product) {
    if (!is_array($product) || !isset($equipmentInventory[$productKey])) {
        continue;
    }

    $normalizedProductKey = normalize_customer_order_product_key($productKey);

    $modelLabel = equipment_model_label_from_product($product);
    $inventoryEntry = normalize_equipment_inventory_entry($equipmentInventory[$productKey], 12, false);
    $equipmentInventory[$productKey] = $inventoryEntry;
    $units = is_array($inventoryEntry['units']) ? $inventoryEntry['units'] : [];

    $equipmentUnitCountsByProduct[$productKey] = count($units);

    foreach ($units as $unit) {
        $serial = max(0, (int) ($unit['serial'] ?? 0));
        $statusValue = normalize_equipment_status($unit['status'] ?? 'available');
        $inUseRequired = max(0, (int) ($equipmentInUseRequirementsByProduct[$normalizedProductKey] ?? 0));

        $equipmentRows[] = [
            'productKey' => (string) $productKey,
            'model' => $modelLabel,
            'serial' => $serial,
            'unitId' => equipment_unit_identifier($modelLabel, $serial),
            'status' => $statusValue,
            'lockedInUse' => $statusValue === 'in-use' && $inUseRequired > 0,
            'timesUsed' => (int) ($timesUsedLast30DaysByProduct[$normalizedProductKey] ?? 0)
        ];
    }
}

usort($equipmentRows, static function ($left, $right) {
    $modelCompare = strcmp((string) ($left['model'] ?? ''), (string) ($right['model'] ?? ''));
    if ($modelCompare !== 0) {
        return $modelCompare;
    }

    return ((int) ($left['serial'] ?? 0)) <=> ((int) ($right['serial'] ?? 0));
});

$archivedEquipmentRows = [];

foreach ($archivedEquipmentUnits as $archivedEquipmentEntry) {
    if (!is_array($archivedEquipmentEntry)) {
        continue;
    }

    $archiveKey = trim((string) ($archivedEquipmentEntry['archiveKey'] ?? ''));
    $productKey = trim((string) ($archivedEquipmentEntry['productKey'] ?? ''));
    $modelLabel = trim((string) ($archivedEquipmentEntry['model'] ?? ''));
    $archivedAt = trim((string) ($archivedEquipmentEntry['archivedAt'] ?? ''));
    $reason = trim((string) ($archivedEquipmentEntry['reason'] ?? 'Removed from active inventory'));
    $unitData = isset($archivedEquipmentEntry['unit']) && is_array($archivedEquipmentEntry['unit'])
        ? $archivedEquipmentEntry['unit']
        : [];
    $serial = max(0, (int) ($unitData['serial'] ?? 0));
    $status = normalize_equipment_status($unitData['status'] ?? 'available');

    if ($archiveKey === '' || $productKey === '') {
        continue;
    }

    if ($modelLabel === '' && isset($products[$productKey]) && is_array($products[$productKey])) {
        $modelLabel = equipment_model_label_from_product($products[$productKey]);
    }

    if ($modelLabel === '') {
        $modelLabel = 'MODEL';
    }

    $archivedEquipmentRows[] = [
        'archiveKey' => $archiveKey,
        'productKey' => $productKey,
        'model' => $modelLabel,
        'unitId' => equipment_unit_identifier($modelLabel, $serial),
        'status' => $status,
        'reason' => $reason,
        'archivedAt' => $archivedAt,
        'hasActiveProduct' => isset($products[$productKey]) && is_array($products[$productKey])
    ];
}

usort($archivedEquipmentRows, static function ($left, $right) {
    return strcmp((string) ($right['archivedAt'] ?? ''), (string) ($left['archivedAt'] ?? ''));
});

$archivedEquipmentCount = count($archivedEquipmentRows);

if (!save_equipment_inventory_repository($equipmentInventory)) {
    // Silent fail, just keep going
}

$howItWorksSlots = [];
for ($slot = 1; $slot <= 4; $slot++) {
    $relativePath = 'assets/how_it_works/' . $slot . '.png';
    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    $howItWorksSlots[] = [
        'slot' => $slot,
        'relativePath' => $relativePath,
        'exists' => is_file($absolutePath)
    ];
}

$promoBannerSlots = [];
$promoBannerDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'promo_images';

if (is_dir($promoBannerDir)) {
    $promoBannerEntries = scandir($promoBannerDir);

    if (is_array($promoBannerEntries)) {
        foreach ($promoBannerEntries as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            if (!preg_match('/^(\d+)\.png$/i', $entry, $matches)) {
                continue;
            }

            $slot = (int) ($matches[1] ?? 0);
            if ($slot < 1) {
                continue;
            }

            $promoBannerSlots[] = [
                'slot' => $slot,
                'relativePath' => 'assets/promo_images/' . $entry,
                'exists' => true
            ];
        }
    }
}

usort($promoBannerSlots, static function ($left, $right) {
    $leftSlot = (int) ($left['slot'] ?? 0);
    $rightSlot = (int) ($right['slot'] ?? 0);

    return $leftSlot <=> $rightSlot;
});

$activePromoBannerSlots = $promoBannerSlots;
$lastPromoSlot = $activePromoBannerSlots ? (int) ($activePromoBannerSlots[count($activePromoBannerSlots) - 1]['slot'] ?? 0) : 0;
$nextPromoBannerSlot = max(1, $lastPromoSlot + 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($isStaffSession ? 'Staff Dashboard | Creaty' : 'Admin Home | Creaty', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260415-8">
</head>
<body
    class="home-page-customer"
    <?php if ($initialAdminPanel !== ''): ?>data-admin-initial-panel="<?php echo htmlspecialchars($initialAdminPanel, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
    <?php if ($openAdminCreateUserModal): ?>data-admin-open-create-user-modal="true"<?php endif; ?>
    <?php if ($openEquipmentArchiveModal): ?>data-admin-open-equipment-archive-modal="true"<?php endif; ?>
    <?php if ($openEquipmentStatusModal): ?>data-admin-open-equipment-status-modal="true"<?php endif; ?>
>
    <header class="site-header">
        <div class="topbar topbar-admin">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search products, events, or services">
            </form>

            <div class="topbar-admin-actions">
                <a
                    class="topbar-notification-button"
                    href="<?php echo htmlspecialchars($notificationsPath, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="Notifications"
                    title="Notifications"
                    data-admin-notification-trigger
                    data-notification-count="<?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <span class="topbar-notification-text">Notifications</span>
                    <span class="topbar-notification-icon-wrap" aria-hidden="true">
                        <img class="topbar-notification-icon" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/notifications.svg" alt="">
                        <span class="cart-count topbar-notification-count" aria-hidden="true"><?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>

                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <?php if (!$isStaffSession): ?>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBrandsPath, ENT_QUOTES, 'UTF-8'); ?>">Manage Brands</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageCategoriesPath, ENT_QUOTES, 'UTF-8'); ?>">Manage Categories</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($assetBase . 'archive/', ENT_QUOTES, 'UTF-8'); ?>">Archived</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($setGcashQrPath, ENT_QUOTES, 'UTF-8'); ?>">Set GCash QR</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($editTocPath, ENT_QUOTES, 'UTF-8'); ?>">Edit TOC</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <nav class="section-nav section-nav-interactive section-nav-admin" aria-label="Catalog filters" data-admin-nav data-admin-dashboard-nav>
            <?php if (!$isStaffSession): ?>
                <div class="section-nav-item section-nav-item-filter admin-nav-primary" data-admin-nav-item="primary">
                    <button class="section-nav-filter filter-toggle" type="button" aria-expanded="false" aria-controls="brands-filter-panel">
                        BRANDS
                    </button>

                    <div class="filter-panel filter-panel-brands" id="brands-filter-panel" hidden>
                        <button class="filter-option is-selected" type="button" data-filter-group="brand" data-filter-value="all">ALL BRANDS</button>
                        <?php foreach ($productBrandValueMap as $brandValue => $brandLabel): ?>
                            <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($brandLabel), ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($routeBase . 'services/', ENT_QUOTES, 'UTF-8'); ?>">SERVICES</a>

                <div class="section-nav-item section-nav-item-filter admin-nav-primary" data-admin-nav-item="primary">
                    <button class="section-nav-filter filter-toggle" type="button" aria-expanded="false" aria-controls="date-filter-panel">
                        DATE
                    </button>

                    <div class="filter-panel filter-panel-date" id="date-filter-panel" hidden>
                        <div class="date-picker-tabs" role="tablist" aria-label="Date filter groups">
                            <button class="date-picker-tab is-active" type="button" data-date-tab="month" aria-selected="true">Month</button>
                            <button class="date-picker-tab" type="button" data-date-tab="day" aria-selected="false">Day</button>
                            <button class="date-picker-tab" type="button" data-date-tab="year" aria-selected="false">Year</button>
                        </div>

                        <div class="date-picker-content">
                            <section class="date-picker-view is-active" data-date-view="month" aria-label="Choose month">
                                <div class="filter-group-options date-filter-options-grid" data-date-month-options></div>
                            </section>

                            <section class="date-picker-view" data-date-view="day" aria-label="Choose day">
                                <div class="date-calendar-panel">
                                    <div class="date-calendar-header">
                                        <p class="date-calendar-title" data-calendar-title>Calendar</p>
                                    </div>
                                    <div class="date-calendar-weekdays" aria-hidden="true">
                                        <span>Sun</span>
                                        <span>Mon</span>
                                        <span>Tue</span>
                                        <span>Wed</span>
                                        <span>Thu</span>
                                        <span>Fri</span>
                                        <span>Sat</span>
                                    </div>
                                    <div class="date-calendar-grid" data-date-calendar-grid></div>
                                    <button class="filter-option date-clear-option" type="button" data-filter-group="day" data-filter-value="all">All Days</button>
                                </div>
                            </section>

                            <section class="date-picker-view" data-date-view="year" aria-label="Choose year">
                                <div class="filter-group-options date-filter-options-grid" data-date-year-options></div>
                            </section>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="equipments" hidden>EQUIPMENTS</button>
            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="bookings" hidden>BOOKINGS</button>
            <?php if (!$isStaffSession): ?>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="reports" hidden>REPORTS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="users" hidden>USERS</button>
            <?php endif; ?>

            <button class="section-nav-swap" type="button" data-admin-nav-swap aria-pressed="false" aria-label="Swap admin navigation" title="Show management bar"<?php echo $isStaffSession ? ' hidden' : ''; ?>>
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/swap_horizontal_arrows.svg" alt="" aria-hidden="true">
            </button>
        </nav>
    </header>

    <main class="landing-shell">
        <section class="admin-equipments-shell" data-admin-dashboard-panel="equipments" hidden>
            <div class="admin-equipments-head">
                <h2>Equipment Inventory</h2>
                <?php if (!$isStaffSession): ?>
                    <button class="admin-equipments-archive-open" type="button" data-admin-equipment-archive-open>
                        Removed Items (<?php echo htmlspecialchars((string) $archivedEquipmentCount, ENT_QUOTES, 'UTF-8'); ?>)
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($adminEquipmentFlashMessage !== ''): ?>
                <p class="admin-equipments-flash-message<?php echo $adminEquipmentFlashType !== '' ? ' is-' . htmlspecialchars($adminEquipmentFlashType, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <?php echo htmlspecialchars($adminEquipmentFlashMessage, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <div class="admin-equipments-table-wrap" role="region" aria-label="Equipments list">
                <table class="admin-equipments-table">
                    <thead>
                        <tr>
                            <th scope="col">UNIT-ID</th>
                            <th scope="col">MODEL</th>
                            <th scope="col">TIMES USED (last 30 days)</th>
                            <th scope="col">
                                <span class="admin-equipments-status-head">
                                    <span>STATUS</span>
                                    <?php if (!$isStaffSession): ?>
                                        <button class="admin-equipments-status-manage" type="button" data-admin-equipment-status-open aria-label="Manage equipment statuses" title="Manage statuses">&#9881;</button>
                                    <?php endif; ?>
                                </span>
                            </th>
                            <th scope="col">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($equipmentRows): ?>
                            <?php foreach ($equipmentRows as $equipmentRow): ?>
                                <?php
                                    $equipmentProductKey = (string) ($equipmentRow['productKey'] ?? '');
                                    $equipmentSerial = (int) ($equipmentRow['serial'] ?? 0);
                                    $equipmentUnitCount = (int) ($equipmentUnitCountsByProduct[$equipmentProductKey] ?? 0);
                                    $equipmentStatusLocked = !empty($equipmentRow['lockedInUse']);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($equipmentRow['unitId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($equipmentRow['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ((int) ($equipmentRow['timesUsed'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form class="admin-equipments-status-form" method="post" action="">
                                            <input type="hidden" name="admin_action" value="equipment_update_status">
                                            <input type="hidden" name="product_key" value="<?php echo htmlspecialchars($equipmentProductKey, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="serial" value="<?php echo htmlspecialchars((string) $equipmentSerial, ENT_QUOTES, 'UTF-8'); ?>">

                                            <label class="sr-only" for="equipment-status-<?php echo htmlspecialchars($equipmentProductKey, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars((string) $equipmentSerial, ENT_QUOTES, 'UTF-8'); ?>">Status</label>
                                            <select class="admin-equipments-status" id="equipment-status-<?php echo htmlspecialchars($equipmentProductKey, ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars((string) $equipmentSerial, ENT_QUOTES, 'UTF-8'); ?>" name="status" onchange="this.form.submit()" <?php echo $equipmentStatusLocked ? 'disabled' : ''; ?> aria-label="<?php echo $equipmentStatusLocked ? 'Status locked to In Use while booking is ongoing' : 'Status'; ?>">
                                                <?php foreach ($equipmentStatusLabels as $statusValue => $statusLabel): ?>
                                                    <?php
                                                        $isInUseStatusOption = (string) $statusValue === 'in-use';
                                                        $isCurrentEquipmentStatus = (string) ($equipmentRow['status'] ?? '') === (string) $statusValue;
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars((string) $statusValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isCurrentEquipmentStatus ? 'selected' : ''; ?> <?php echo $isInUseStatusOption ? 'disabled' : ''; ?>><?php echo htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8'); ?><?php echo $isInUseStatusOption ? ' (AUTO)' : ''; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($isStaffSession): ?>
                                            <span class="admin-equipments-actions-disabled">Status only</span>
                                        <?php else: ?>
                                            <div class="admin-equipments-actions">
                                                <form method="post" action="" class="admin-equipments-action-form">
                                                    <input type="hidden" name="admin_action" value="equipment_add_quantity">
                                                    <input type="hidden" name="product_key" value="<?php echo htmlspecialchars($equipmentProductKey, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button
                                                        class="admin-equipments-action admin-equipments-action-add"
                                                        type="submit"
                                                        data-admin-equipment-add
                                                        data-model="<?php echo htmlspecialchars((string) ($equipmentRow['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="Add quantity"
                                                        aria-label="Add quantity to <?php echo htmlspecialchars((string) ($equipmentRow['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    >+</button>
                                                </form>

                                                <form method="post" action="" class="admin-equipments-action-form">
                                                    <input type="hidden" name="admin_action" value="equipment_remove_unit">
                                                    <input type="hidden" name="product_key" value="<?php echo htmlspecialchars($equipmentProductKey, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="serial" value="<?php echo htmlspecialchars((string) $equipmentSerial, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button
                                                        class="admin-equipments-action admin-equipments-action-remove"
                                                        type="submit"
                                                        data-admin-equipment-remove
                                                        data-will-archive="<?php echo $equipmentUnitCount <= 1 ? 'true' : 'false'; ?>"
                                                        data-model="<?php echo htmlspecialchars((string) ($equipmentRow['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-unit-id="<?php echo htmlspecialchars((string) ($equipmentRow['unitId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                        title="<?php echo $equipmentUnitCount <= 1 ? 'Remove last quantity and archive featured product' : 'Remove quantity'; ?>"
                                                        aria-label="<?php echo $equipmentUnitCount <= 1 ? 'Remove last quantity and archive featured product' : 'Remove quantity'; ?>"
                                                    >&times;</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No active equipment units.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-bookings-shell" data-admin-dashboard-panel="bookings" hidden>
            <div class="admin-bookings-table-wrap" role="region" aria-label="Bookings list">
                <table class="admin-bookings-table">
                    <thead>
                        <tr>
                            <th scope="col">NAME</th>
                            <th scope="col">ORDER NUMBER</th>
                            <th scope="col">TIME STAMP</th>
                            <th scope="col">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dashboardBookings): ?>
                            <?php foreach ($dashboardBookings as $booking): ?>
                                <tr class="admin-bookings-row" data-admin-booking-row data-admin-booking-id="<?php echo htmlspecialchars((string) ($booking['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" tabindex="0">
                                    <td class="admin-bookings-name-col">
                                        <span class="admin-bookings-name-wrap">
                                            <?php if (!empty($booking['showPriorityStar'])): ?>
                                                <img class="admin-bookings-priority-star" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/star.svg" alt="Priority customer" title="Priority customer">
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars((string) ($booking['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($booking['order'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($booking['time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="admin-bookings-status <?php echo htmlspecialchars((string) ($booking['statusClass'] ?? 'status-pending'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars((string) ($booking['status'] ?? 'PENDING'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No bookings yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-booking-detail-backdrop" data-admin-booking-detail-backdrop hidden>
            <section class="admin-booking-detail-modal" role="dialog" aria-modal="true" aria-labelledby="admin-booking-detail-title">
                <div class="admin-booking-detail-head">
                    <h2 id="admin-booking-detail-title">Booking Details</h2>
                    <button class="admin-booking-detail-close" type="button" data-admin-booking-detail-close aria-label="Close booking details">&times;</button>
                </div>

                <div class="admin-booking-detail-pages-nav" role="tablist" aria-label="Booking detail sections">
                    <button type="button" class="admin-booking-detail-page-tab is-active" data-admin-booking-page-tab="items" role="tab" aria-selected="true">Items &amp; Total</button>
                    <button type="button" class="admin-booking-detail-page-tab" data-admin-booking-page-tab="images" role="tab" aria-selected="false" tabindex="-1">Images</button>
                    <button type="button" class="admin-booking-detail-page-tab" data-admin-booking-page-tab="details" role="tab" aria-selected="false" tabindex="-1">Order Details</button>
                </div>

                <section class="admin-booking-detail-page is-active" data-admin-booking-page="items">
                    <div class="admin-booking-detail-items-wrap">
                        <div class="admin-booking-detail-section-head">
                            <h3>Ordered Items</h3>
                            <p class="admin-booking-detail-items-total" data-admin-booking-detail-items-total>Total quantity: 0 items</p>
                        </div>
                        <ul class="admin-booking-detail-items" data-admin-booking-detail-items></ul>
                    </div>
                </section>

                <section class="admin-booking-detail-page" data-admin-booking-page="images" hidden>
                    <div class="admin-booking-detail-media-grid">
                        <div class="admin-booking-detail-receipt-wrap" data-admin-booking-detail-receipt-wrap hidden>
                            <h3>Payment Receipt</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-receipt-state>-</span></p>
                            <a class="admin-booking-detail-receipt-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-receipt-link hidden>
                                <img src="" alt="Payment receipt" data-admin-booking-detail-receipt-image>
                            </a>
                            <p class="admin-booking-detail-receipt-empty" data-admin-booking-detail-receipt-empty hidden>No payment receipt uploaded yet.</p>
                            <p class="admin-booking-detail-receipt-meta" data-admin-booking-detail-receipt-meta hidden></p>
                        </div>

                        <div class="admin-booking-detail-refund-wrap" data-admin-booking-detail-refund-wrap hidden>
                            <h3>Refund Proof</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-refund-proof-state>-</span></p>
                            <a class="admin-booking-detail-refund-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-refund-link hidden>
                                <img src="" alt="Refund proof" data-admin-booking-detail-refund-image>
                            </a>
                            <p class="admin-booking-detail-refund-empty" data-admin-booking-detail-refund-empty hidden>No refund proof uploaded.</p>
                            <p class="admin-booking-detail-refund-meta" data-admin-booking-detail-refund-meta hidden></p>
                        </div>

                        <div class="admin-booking-detail-receive-delivery-wrap" data-admin-booking-detail-receive-delivery-wrap hidden>
                            <h3>Receive Delivery Receipt</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-receive-delivery-state>-</span></p>
                            <a class="admin-booking-detail-receive-delivery-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-receive-delivery-link hidden>
                                <img src="" alt="Receive delivery receipt" data-admin-booking-detail-receive-delivery-image>
                            </a>
                            <p class="admin-booking-detail-receive-delivery-empty" data-admin-booking-detail-receive-delivery-empty hidden>No receive-delivery receipt uploaded yet.</p>
                            <p class="admin-booking-detail-receive-delivery-meta" data-admin-booking-detail-receive-delivery-meta hidden></p>
                        </div>

                        <div class="admin-booking-detail-return-delivery-wrap" data-admin-booking-detail-return-delivery-wrap hidden>
                            <h3>Return Delivery Receipt</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-return-delivery-state>-</span></p>
                            <a class="admin-booking-detail-return-delivery-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-return-delivery-link hidden>
                                <img src="" alt="Return delivery receipt" data-admin-booking-detail-return-delivery-image>
                            </a>
                            <p class="admin-booking-detail-return-delivery-empty" data-admin-booking-detail-return-delivery-empty hidden>No return-delivery receipt uploaded yet.</p>
                            <p class="admin-booking-detail-return-delivery-meta" data-admin-booking-detail-return-delivery-meta hidden></p>
                        </div>

                        <div class="admin-booking-detail-valid-id-wrap" data-admin-booking-detail-valid-id-wrap hidden>
                            <h3>Valid ID</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-valid-id-state>-</span></p>
                            <a class="admin-booking-detail-valid-id-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-valid-id-link hidden>
                                <img src="" alt="Uploaded valid ID" data-admin-booking-detail-valid-id-image>
                            </a>
                            <p class="admin-booking-detail-valid-id-empty" data-admin-booking-detail-valid-id-empty hidden>Valid ID is required for delivery bookings.</p>
                            <p class="admin-booking-detail-valid-id-meta" data-admin-booking-detail-valid-id-meta hidden></p>
                        </div>

                        <div class="admin-booking-detail-selfie-with-id-wrap" data-admin-booking-detail-selfie-with-id-wrap hidden>
                            <h3>Selfie with ID</h3>
                            <p class="admin-booking-detail-media-state"><strong>Status:</strong> <span data-admin-booking-detail-selfie-with-id-state>-</span></p>
                            <a class="admin-booking-detail-selfie-with-id-link" href="#" target="_blank" rel="noopener" data-admin-booking-detail-selfie-with-id-link hidden>
                                <img src="" alt="Uploaded selfie with ID" data-admin-booking-detail-selfie-with-id-image>
                            </a>
                            <p class="admin-booking-detail-selfie-with-id-empty" data-admin-booking-detail-selfie-with-id-empty hidden>Selfie with ID is required for delivery bookings.</p>
                            <p class="admin-booking-detail-selfie-with-id-meta" data-admin-booking-detail-selfie-with-id-meta hidden></p>
                        </div>
                    </div>
                </section>

                <section class="admin-booking-detail-page" data-admin-booking-page="details" hidden>
                    <div class="admin-booking-detail-grid">
                        <p><strong>Name:</strong> <span data-admin-booking-detail-name>-</span></p>
                        <p><strong>Email:</strong> <span data-admin-booking-detail-email>-</span></p>
                        <p><strong>Order Number:</strong> <span data-admin-booking-detail-order-number>-</span></p>
                        <p><strong>Timestamp:</strong> <span data-admin-booking-detail-timestamp>-</span></p>
                        <p>
                            <strong>Status:</strong>
                            <span class="admin-bookings-status status-pending" data-admin-booking-detail-status>-</span>
                        </p>
                        <p><strong>Booking Duration:</strong> <span data-admin-booking-detail-duration>-</span></p>
                        <p><strong>Meeting Place:</strong> <span data-admin-booking-detail-place>-</span></p>
                        <p><strong>Receiving:</strong> <span data-admin-booking-detail-receive>-</span></p>
                        <p><strong>Returning:</strong> <span data-admin-booking-detail-return>-</span></p>
                        <p><strong>Receiving Method:</strong> <span data-admin-booking-detail-receiving-method>-</span></p>
                        <p><strong>Returning Method:</strong> <span data-admin-booking-detail-returning-method>-</span></p>
                        <p><strong>Courier:</strong> <span data-admin-booking-detail-courier>-</span></p>
                        <p><strong>Payment Method:</strong> <span data-admin-booking-detail-payment-method>-</span></p>
                        <p><strong>Customer GCash Name:</strong> <span data-admin-booking-detail-customer-gcash-name>-</span></p>
                        <p><strong>Customer GCash Number:</strong> <span data-admin-booking-detail-customer-gcash-number>-</span></p>
                        <p><strong>Reason:</strong> <span data-admin-booking-detail-cancel-reason>-</span></p>
                    </div>

                    <p class="admin-booking-detail-status-note" data-admin-booking-detail-status-note hidden></p>
                </section>

                <form class="admin-booking-detail-actions" method="post" action="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_booking_status.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-booking-status-form>
                    <input type="hidden" name="order_id" value="" data-admin-booking-status-order-id>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($adminHomePath . '?admin_view=bookings', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="next_status" value="" data-admin-booking-next-status>
                    <input type="hidden" name="cancel_reason" value="" data-admin-booking-cancel-reason-input>
                    <input type="hidden" name="refund_proof_data_url" value="" data-admin-booking-refund-proof-input>
                    <button type="submit" name="next_status" value="approved" class="admin-booking-action is-approve" data-admin-booking-status-submit>Approve</button>
                    <button type="button" class="admin-booking-action is-reject" data-admin-booking-review-open data-admin-booking-review-mode="rejected">Reject</button>
                    <button type="button" class="admin-booking-action is-refund" data-admin-booking-review-open data-admin-booking-review-mode="refunded">Refund</button>
                    <button type="submit" name="next_status" value="confirm-pickup-handover" class="admin-booking-action is-ongoing" data-admin-booking-status-submit>Confirm Pickup Handover</button>
                    <button type="submit" name="next_status" value="confirm-meetup-handover" class="admin-booking-action is-ongoing" data-admin-booking-status-submit>Confirm Meet-up Handover</button>
                    <button type="button" class="admin-booking-action is-delivery-upload" data-admin-booking-delivery-open data-admin-booking-delivery-mode="upload-receive">Upload Receive Delivery Receipt</button>
                    <button type="button" class="admin-booking-action is-delivery-close" data-admin-booking-delivery-open data-admin-booking-delivery-mode="close-receive">Close Receive Delivery Leg</button>
                    <button type="button" class="admin-booking-action is-delivery-close" data-admin-booking-delivery-open data-admin-booking-delivery-mode="close-return">Close Return Delivery Leg</button>
                    <button type="submit" name="next_status" value="returned-early" class="admin-booking-action is-early-return" data-admin-booking-status-submit>Returned Early</button>
                    <button type="submit" name="next_status" value="completed" class="admin-booking-action is-complete" data-admin-booking-status-submit>Complete</button>
                    <button type="button" class="admin-booking-action is-cancel" data-admin-booking-cancel-open>Cancel Order</button>
                </form>
            </section>
        </div>

        <div class="admin-booking-review-backdrop" data-admin-booking-review-backdrop hidden>
            <section class="admin-booking-review-modal" role="dialog" aria-modal="true" aria-labelledby="admin-booking-review-title">
                <div class="admin-booking-review-head">
                    <h3 id="admin-booking-review-title" data-admin-booking-review-title>Booking Decision</h3>
                    <button class="admin-booking-review-close" type="button" data-admin-booking-review-close aria-label="Close decision dialog">&times;</button>
                </div>

                <p class="admin-booking-review-copy" data-admin-booking-review-copy>Provide a reason for this decision. This will be visible to the customer in Order Status.</p>

                <div class="admin-booking-review-customer-gcash" data-admin-booking-review-customer-gcash hidden>
                    <p><strong>Customer GCash Name:</strong> <span data-admin-booking-review-customer-gcash-name>-</span></p>
                    <p><strong>Customer GCash Number:</strong> <span data-admin-booking-review-customer-gcash-number>-</span></p>
                </div>

                <textarea class="admin-booking-review-textarea" data-admin-booking-review-reason maxlength="500" placeholder="Enter decision reason"></textarea>

                <div class="admin-booking-review-proof-wrap" data-admin-booking-review-proof-wrap hidden>
                    <input type="file" accept="image/*" data-admin-booking-review-proof-file hidden>
                    <div class="admin-booking-review-proof-row">
                        <span class="admin-booking-review-proof-filename" data-admin-booking-review-proof-filename>No file selected</span>
                        <button type="button" class="admin-booking-review-proof-select" data-admin-booking-review-proof-select>Select Proof Image</button>
                    </div>
                    <p class="admin-booking-review-proof-note">Upload the refund receipt screenshot as proof for the customer.</p>
                </div>

                <p class="admin-booking-review-error" data-admin-booking-review-error hidden></p>

                <div class="admin-booking-review-actions">
                    <button type="button" class="admin-booking-review-action" data-admin-booking-review-close>Back</button>
                    <button type="button" class="admin-booking-review-action is-confirm" data-admin-booking-review-confirm>Confirm</button>
                </div>
            </section>
        </div>

        <div class="admin-booking-cancel-backdrop" data-admin-booking-cancel-backdrop hidden>
            <section class="admin-booking-cancel-modal" role="dialog" aria-modal="true" aria-labelledby="admin-booking-cancel-title">
                <div class="admin-booking-cancel-head">
                    <h3 id="admin-booking-cancel-title">Cancel Booking</h3>
                    <button class="admin-booking-cancel-close" type="button" data-admin-booking-cancel-close aria-label="Close cancel dialog">&times;</button>
                </div>

                <p class="admin-booking-cancel-copy">Provide a cancellation reason. This will be visible to the customer in Order Status.</p>
                <textarea class="admin-booking-cancel-textarea" data-admin-booking-cancel-reason maxlength="500" placeholder="Enter cancellation reason"></textarea>
                <p class="admin-booking-cancel-error" data-admin-booking-cancel-error hidden></p>

                <div class="admin-booking-cancel-actions">
                    <button type="button" class="admin-booking-cancel-action" data-admin-booking-cancel-close>Back</button>
                    <button type="button" class="admin-booking-cancel-action is-confirm" data-admin-booking-cancel-confirm>Confirm Cancel</button>
                </div>
            </section>
        </div>

        <div class="admin-booking-delivery-backdrop" data-admin-booking-delivery-backdrop hidden>
            <section class="admin-booking-delivery-modal" role="dialog" aria-modal="true" aria-labelledby="admin-booking-delivery-title">
                <div class="admin-booking-delivery-head">
                    <h3 id="admin-booking-delivery-title" data-admin-booking-delivery-title>Delivery Action</h3>
                    <button class="admin-booking-delivery-close" type="button" data-admin-booking-delivery-close aria-label="Close delivery dialog">&times;</button>
                </div>

                <p class="admin-booking-delivery-copy" data-admin-booking-delivery-copy>Provide delivery details before confirming this action.</p>

                <div class="admin-booking-delivery-proof-wrap" data-admin-booking-delivery-proof-wrap hidden>
                    <input type="file" accept="image/*" data-admin-booking-delivery-proof-file hidden>
                    <div class="admin-booking-delivery-proof-row">
                        <span class="admin-booking-delivery-proof-filename" data-admin-booking-delivery-proof-filename>No file selected</span>
                        <button type="button" class="admin-booking-delivery-proof-select" data-admin-booking-delivery-proof-select>Select Receipt Image</button>
                    </div>
                    <p class="admin-booking-delivery-proof-note">Upload the courier proof screenshot to mark this delivery leg as in transit.</p>
                </div>

                <label class="admin-booking-delivery-field" for="admin-booking-delivery-reference">
                    <span>Delivery Reference (optional)</span>
                    <input id="admin-booking-delivery-reference" class="admin-booking-delivery-input" type="text" maxlength="120" data-admin-booking-delivery-reference placeholder="Tracking number or courier booking reference">
                </label>

                <label class="admin-booking-delivery-field" for="admin-booking-delivery-notes">
                    <span>Delivery Notes (optional)</span>
                    <textarea id="admin-booking-delivery-notes" class="admin-booking-delivery-textarea" data-admin-booking-delivery-notes maxlength="500" placeholder="Courier details, rider contact, or completion notes"></textarea>
                </label>

                <p class="admin-booking-delivery-error" data-admin-booking-delivery-error hidden></p>

                <div class="admin-booking-delivery-actions">
                    <button type="button" class="admin-booking-delivery-action" data-admin-booking-delivery-close>Back</button>
                    <button type="button" class="admin-booking-delivery-action is-confirm" data-admin-booking-delivery-confirm>Confirm</button>
                </div>
            </section>
        </div>

        <?php if (!$isStaffSession): ?>
        <section class="admin-reports-shell" data-admin-dashboard-panel="reports" hidden>
            <div class="admin-reports-head" role="group" aria-label="Report breakdown">
                <div class="admin-reports-breakdown-group">
                    <p>Breakdown by:</p>
                    <span class="admin-reports-breakdown-value"><?php echo htmlspecialchars((string) $reportsSelectedYear, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <form class="admin-reports-year-filter" method="get" action="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="admin_view" value="reports">
                    <input type="hidden" name="reports_sort" value="<?php echo htmlspecialchars($reportsSortColumn, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="reports_dir" value="<?php echo htmlspecialchars($reportsSortDirection, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="admin-reports-year">Year:</label>
                    <select id="admin-reports-year" name="reports_year" onchange="this.form.submit()">
                        <?php foreach ($reportYearOptions as $reportYearOption): ?>
                            <option value="<?php echo htmlspecialchars((string) $reportYearOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (int) $reportYearOption === $reportsSelectedYear ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $reportYearOption, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Apply</button>
                </form>
            </div>

            <div class="admin-reports-table-wrap" role="region" aria-label="Reports list">
                <table class="admin-reports-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <a class="admin-reports-sort-link<?php echo $reportsSortColumn === 'month' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($reportsMonthSortUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span>MONTH</span>
                                    <span class="admin-reports-sort-indicator<?php echo $reportsSortColumn !== 'month' ? ' is-inactive' : ''; ?><?php echo $reportsSortColumn === 'month' && $reportsSortDirection === 'desc' ? ' is-desc' : ''; ?>" aria-hidden="true">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/arrow_up.svg" alt="">
                                    </span>
                                </a>
                            </th>
                            <th scope="col">
                                <a class="admin-reports-sort-link<?php echo $reportsSortColumn === 'transactions' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($reportsTransactionsSortUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span>TRANSACTIONS</span>
                                    <span class="admin-reports-sort-indicator<?php echo $reportsSortColumn !== 'transactions' ? ' is-inactive' : ''; ?><?php echo $reportsSortColumn === 'transactions' && $reportsSortDirection === 'desc' ? ' is-desc' : ''; ?>" aria-hidden="true">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/arrow_up.svg" alt="">
                                    </span>
                                </a>
                            </th>
                            <th scope="col">
                                <a class="admin-reports-sort-link<?php echo $reportsSortColumn === 'revenue' ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($reportsRevenueSortUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span>REVENUE</span>
                                    <span class="admin-reports-sort-indicator<?php echo $reportsSortColumn !== 'revenue' ? ' is-inactive' : ''; ?><?php echo $reportsSortColumn === 'revenue' && $reportsSortDirection === 'desc' ? ' is-desc' : ''; ?>" aria-hidden="true">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/arrow_up.svg" alt="">
                                    </span>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportRows as $reportRow): ?>
                            <?php
                                $reportRevenue = (float) ($reportRow['revenue'] ?? 0);
                                $reportRevenueAbsolute = abs($reportRevenue);
                                $reportRevenuePrefix = $reportRevenue < 0 ? '-&#8369; ' : '&#8369; ';
                            ?>
                            <tr>
                                <td><span class="admin-reports-month-pill"><?php echo htmlspecialchars((string) ($reportRow['monthLabel'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars((string) ((int) ($reportRow['transactions'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $reportRevenuePrefix; ?><?php echo htmlspecialchars(number_format($reportRevenueAbsolute, 2), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-users-shell" data-admin-dashboard-panel="users" hidden>
            <div class="admin-users-head" role="group" aria-label="Users controls">
                <div class="admin-users-head-left">
                    <h2>USERS</h2>
                    <button class="admin-users-add-button" type="button" data-admin-users-open-modal>+ ADD NEW USER</button>
                </div>

                <label class="admin-users-filter-wrap" for="admin-users-role-filter">
                    <span>FILTER:</span>
                    <select id="admin-users-role-filter" class="admin-users-filter" data-admin-users-filter>
                        <option value="all" selected>All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                        <option value="customer">Customer</option>
                    </select>
                </label>
            </div>

            <?php if ($adminUsersFlashMessage !== ''): ?>
                <p class="admin-users-flash-message"><?php echo htmlspecialchars($adminUsersFlashMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="admin-users-table-wrap" role="region" aria-label="Users list">
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">FULL NAME/EMPLOYEE NUMBER</th>
                            <th scope="col">EMAIL/USERNAME</th>
                            <th scope="col">ROLE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboardUsers as $dashboardUser): ?>
                            <tr data-admin-user-row data-role="<?php echo strtolower((string) ($dashboardUser['role'] ?? '')); ?>">
                                <td><?php echo htmlspecialchars((string) ($dashboardUser['prefixedId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($dashboardUser['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($dashboardUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($dashboardUser['role'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <section class="promo-banner promo-banner-admin reveal" data-admin-dashboard-default aria-label="Promo carousel" data-admin-promo-banner data-admin-promo-archive-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/archive_promo_banner.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-promo-restore-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/restore_archived_promo_banner.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-promo-update-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_promo_banner.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-promo-image-base="<?php echo htmlspecialchars($assetBase . 'assets/promo_images/', ENT_QUOTES, 'UTF-8'); ?>">
            <button class="step-card-admin-remove promo-banner-admin-remove" type="button" data-admin-promo-remove aria-label="Archive active promo banner">&times;</button>
            <button class="promo-arrow promo-arrow-left" type="button" aria-label="Previous promo">&#10094;</button>

            <div class="promo-carousel" aria-live="polite">
                <?php if ($activePromoBannerSlots): ?>
                    <?php foreach ($activePromoBannerSlots as $index => $promoSlot): ?>
                        <?php
                            $slotNumber = (int) ($promoSlot['slot'] ?? 0);
                            $slotPath = (string) ($promoSlot['relativePath'] ?? '');
                            $slotClass = $slotNumber === 1
                                ? 'promo-slide-one'
                                : ($slotNumber === 2 ? 'promo-slide-two' : 'promo-slide-three');
                        ?>
                        <div class="promo-slide <?php echo htmlspecialchars($slotClass, ENT_QUOTES, 'UTF-8'); ?><?php echo $index === 0 ? ' is-active' : ''; ?>" data-promo-slot="<?php echo htmlspecialchars((string) $slotNumber, ENT_QUOTES, 'UTF-8'); ?>">
                            <img class="promo-image" src="<?php echo htmlspecialchars($assetBase . $slotPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Promo banner slot <?php echo htmlspecialchars((string) $slotNumber, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="promo-slide promo-slide-admin-add<?php echo !$activePromoBannerSlots ? ' is-active' : ''; ?>" data-admin-promo-add-slide data-admin-promo-slot="<?php echo htmlspecialchars((string) $nextPromoBannerSlot, ENT_QUOTES, 'UTF-8'); ?>">
                    <button class="promo-admin-add-trigger" type="button" data-admin-promo-add-trigger aria-label="Add promotion banner">
                        <span class="promo-admin-add-plus">+</span>
                        <span>Add Promotion Banner</span>
                    </button>
                </div>
            </div>

            <button class="promo-arrow promo-arrow-right" type="button" aria-label="Next promo">&#10095;</button>
        </section>

        <section class="landing-section reveal" data-admin-dashboard-default aria-labelledby="how-it-works-title">
            <h2 class="landing-title" id="how-it-works-title">HOW IT WORKS</h2>

            <div class="steps-grid" data-admin-how-grid data-admin-how-update-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_how_it_works.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-how-delete-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/delete_how_it_works.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-how-restore-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/restore_archived_how_it_works.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-how-image-base="<?php echo htmlspecialchars($assetBase . 'assets/how_it_works/', ENT_QUOTES, 'UTF-8'); ?>">
                <?php foreach ($howItWorksSlots as $step): ?>
                    <?php
                        $slot = (int) $step['slot'];
                        $hasImage = (bool) $step['exists'];
                        $stepPath = (string) $step['relativePath'];
                    ?>
                    <article class="step-card step-card-admin<?php echo $hasImage ? '' : ' step-card-admin-add'; ?>" data-admin-how-slot="<?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>" data-admin-how-has-image="<?php echo $hasImage ? 'true' : 'false'; ?>" data-admin-how-image-src="<?php echo $hasImage ? htmlspecialchars($assetBase . $stepPath, ENT_QUOTES, 'UTF-8') : ''; ?>">
                        <?php if ($hasImage): ?>
                            <button class="step-card-admin-remove" type="button" data-admin-how-remove aria-label="Delete how it works image slot <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
                            <button class="step-card-admin-image-button" type="button" data-admin-how-edit aria-label="Edit how it works image slot <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>">
                                <img class="step-image" src="<?php echo htmlspecialchars($assetBase . $stepPath, ENT_QUOTES, 'UTF-8'); ?>" alt="How it works step <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>">
                            </button>
                        <?php else: ?>
                            <button class="step-card-admin-add-trigger" type="button" data-admin-how-edit aria-label="Add how it works image slot <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="step-card-admin-add-plus">+</span>
                                <span>Add Photo</span>
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="landing-section reveal" data-admin-dashboard-default aria-labelledby="featured-products-title">
            <h2 class="landing-title" id="featured-products-title">FEATURED PRODUCTS</h2>

            <div class="product-grid">
                <?php foreach ($products as $productKey => $product): ?>
                    <?php
                        if (!is_array($product)) {
                            continue;
                        }

                        $brandLabel = normalize_product_brand($product['brand'] ?? 'Canon');
                        $brandValue = product_brand_slug($brandLabel);
                        $productName = trim((string) ($product['name'] ?? ''));
                        $displayName = trim($brandLabel . ' ' . $productName);
                        $specOne = trim((string) ($product['spec1'] ?? ''));
                        $specTwo = trim((string) ($product['spec2'] ?? ''));
                        $price = (float) ($product['price'] ?? 0);
                        $discount = (int) ($product['discountPercent'] ?? 0);
                        $discount = max(0, min(95, $discount));
                        $isPromo = $discount > 0;
                        $discounted = $price * (1 - ($discount / 100));
                        $imagePath = trim((string) ($product['cameraImage'] ?? ''));
                    ?>
                    <article class="product-card<?php echo $isPromo ? ' product-card-highlight' : ''; ?>" data-product-key="<?php echo htmlspecialchars((string) $productKey, ENT_QUOTES, 'UTF-8'); ?>" data-product-name="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>" data-brand="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?> featured details">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                        </button>
                        <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?> from featured">&times;</button>
                        <?php if ($isPromo): ?>
                            <div class="product-ribbon">PROMO <?php echo htmlspecialchars((string) $discount, ENT_QUOTES, 'UTF-8'); ?>% OFF!</div>
                        <?php endif; ?>
                        <a class="product-visual-link" href="<?php echo htmlspecialchars($routeBase . 'products/?product=' . urlencode((string) $productKey), ENT_QUOTES, 'UTF-8'); ?>" aria-label="View <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?> product page">
                            <div class="product-visual">
                                <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase . $imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </a>
                        <div class="product-copy">
                            <h3><a class="product-title-link" href="<?php echo htmlspecialchars($routeBase . 'products/?product=' . urlencode((string) $productKey), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                            <p><?php echo htmlspecialchars($specOne, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><?php echo htmlspecialchars($specTwo, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if ($isPromo): ?>
                                <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #dde531;">
                                    <span style="color: #a1a1aa; text-decoration: line-through; font-size: 0.95rem; font-weight: 600; margin-right: 0.45rem;">&#8369; <?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span>&#8369; <?php echo htmlspecialchars(number_format($discounted, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </p>
                            <?php else: ?>
                                <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #f4f4f4;">&#8369; <?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <article class="product-card product-card-admin-add" data-admin-add-card="true">
                    <div class="admin-add-product-box" aria-label="Add products placeholder">
                        <div>
                            <span>+</span>
                            Add Products
                        </div>
                    </div>
                </article>
            </div>

            <p class="product-grid-empty" hidden>No featured products match the selected filters.</p>
        </section>
    </main>

    <?php if (!$isStaffSession): ?>
    <div class="admin-equipments-archive-backdrop" data-admin-equipment-archive-backdrop hidden>
        <section class="admin-equipments-archive-modal" role="dialog" aria-modal="true" aria-labelledby="admin-equipment-archive-title">
            <div class="admin-equipments-archive-head">
                <h2 id="admin-equipment-archive-title">Removed Equipment Units</h2>
                <button class="admin-equipments-archive-close" type="button" data-admin-equipment-archive-close aria-label="Close removed equipment list">&times;</button>
            </div>

            <p class="admin-equipments-archive-meta"><?php echo htmlspecialchars((string) $archivedEquipmentCount, ENT_QUOTES, 'UTF-8'); ?> unit(s) currently removed from active inventory.</p>

            <?php if ($archivedEquipmentRows): ?>
                <div class="admin-equipments-archive-table-wrap" role="region" aria-label="Removed equipment list">
                    <table class="admin-equipments-archive-table">
                        <thead>
                            <tr>
                                <th scope="col">UNIT-ID</th>
                                <th scope="col">MODEL</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">FEATURED PRODUCT</th>
                                <th scope="col">REMOVED AT</th>
                                <th scope="col">REASON</th>
                                <th scope="col">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedEquipmentRows as $archivedEquipmentRow): ?>
                                <?php
                                    $archivedAtLabel = format_admin_local_datetime_label((string) ($archivedEquipmentRow['archivedAt'] ?? ''));
                                    $archivedStatus = (string) ($archivedEquipmentRow['status'] ?? 'available');
                                    $archivedStatusLabel = (string) ($equipmentStatusLabels[$archivedStatus] ?? strtoupper(str_replace('-', ' ', $archivedStatus)));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($archivedEquipmentRow['unitId'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($archivedEquipmentRow['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($archivedStatusLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo !empty($archivedEquipmentRow['hasActiveProduct']) ? 'Active' : 'Archived'; ?></td>
                                    <td><?php echo htmlspecialchars($archivedAtLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($archivedEquipmentRow['reason'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="post" action="">
                                            <input type="hidden" name="admin_action" value="equipment_restore_unit">
                                            <input type="hidden" name="archive_key" value="<?php echo htmlspecialchars((string) ($archivedEquipmentRow['archiveKey'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="admin-equipments-restore-button" type="submit">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="admin-equipments-archive-empty">No removed units yet.</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="admin-equipments-status-backdrop" data-admin-equipment-status-backdrop hidden>
        <section class="admin-equipments-status-modal" role="dialog" aria-modal="true" aria-labelledby="admin-equipment-status-title">
            <div class="admin-equipments-status-head-wrap">
                <h2 id="admin-equipment-status-title">Manage Equipment Statuses</h2>
                <button class="admin-equipments-status-close" type="button" data-admin-equipment-status-close aria-label="Close status management">&times;</button>
            </div>

            <p class="admin-equipments-status-meta">Edit, remove, or add statuses used by equipment dropdowns. AVAILABLE and IN USE are protected.</p>

            <div class="admin-equipments-status-table-wrap" role="region" aria-label="Status list">
                <table class="admin-equipments-status-table">
                    <thead>
                        <tr>
                            <th scope="col">STATUS NAME</th>
                            <th scope="col">REMOVE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipmentStatuses as $equipmentStatusValue): ?>
                            <?php
                                $statusDisplay = ucwords(str_replace('-', ' ', (string) $equipmentStatusValue));
                                $isProtectedEquipmentStatus = in_array((string) $equipmentStatusValue, $equipmentProtectedStatuses, true);
                            ?>
                            <tr>
                                <td>
                                    <form class="admin-equipments-status-row-form" method="post" action="">
                                        <input type="hidden" name="admin_action" value="equipment_rename_status">
                                        <input type="hidden" name="old_status" value="<?php echo htmlspecialchars((string) $equipmentStatusValue, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="text" name="status_label" value="<?php echo htmlspecialchars($statusDisplay, ENT_QUOTES, 'UTF-8'); ?>" maxlength="40" required <?php echo $isProtectedEquipmentStatus ? 'readonly' : ''; ?> <?php echo $isProtectedEquipmentStatus ? 'aria-readonly="true"' : ''; ?>>
                                        <button type="submit" <?php echo $isProtectedEquipmentStatus ? 'disabled' : ''; ?>>Save</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="admin_action" value="equipment_delete_status">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) $equipmentStatusValue, ENT_QUOTES, 'UTF-8'); ?>">
                                        <button
                                            class="admin-equipments-status-remove"
                                            type="submit"
                                            data-admin-equipment-status-delete
                                            data-status-label="<?php echo htmlspecialchars($statusDisplay, ENT_QUOTES, 'UTF-8'); ?>"
                                            <?php echo (count($equipmentStatuses) <= 1 || $isProtectedEquipmentStatus) ? 'disabled' : ''; ?>
                                        >Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <form class="admin-equipments-status-add-form" method="post" action="">
                <input type="hidden" name="admin_action" value="equipment_add_status">
                <label for="admin-equipment-status-add-input">Add New Status</label>
                <div class="admin-equipments-status-add-row">
                    <input id="admin-equipment-status-add-input" type="text" name="status_label" placeholder="ex. Cleaning" maxlength="40" required>
                    <button type="submit">Add</button>
                </div>
            </form>
        </section>
    </div>
    <?php endif; ?>

    <div class="admin-action-modal-backdrop" data-admin-action-modal-backdrop hidden>
        <section class="admin-action-modal" role="dialog" aria-modal="true" aria-labelledby="admin-action-modal-title">
            <div class="admin-action-modal-head">
                <h2 id="admin-action-modal-title" data-admin-action-modal-title>Confirm Action</h2>
                <button class="admin-action-modal-close" type="button" data-admin-action-modal-close aria-label="Close confirmation modal">&times;</button>
            </div>

            <p class="admin-action-modal-copy" data-admin-action-modal-message>Please confirm this action.</p>

            <label class="admin-action-modal-quantity" data-admin-action-modal-quantity-wrap hidden>
                <span>Quantity</span>
                <input type="number" min="1" max="200" step="1" value="1" data-admin-action-modal-quantity-input>
            </label>

            <div class="admin-action-modal-actions">
                <button class="admin-action-modal-cancel" type="button" data-admin-action-modal-cancel>Cancel</button>
                <button class="admin-action-modal-confirm" type="button" data-admin-action-modal-confirm>Confirm</button>
            </div>
        </section>
    </div>

    <?php if (!$isStaffSession): ?>
    <div class="admin-users-create-backdrop" data-admin-users-create-backdrop hidden>
        <section class="admin-users-create-modal" role="dialog" aria-modal="true" aria-labelledby="admin-users-create-title">
            <div class="admin-users-create-head">
                <h2 id="admin-users-create-title">Create User</h2>
                <button class="admin-users-create-close" type="button" data-admin-users-close-modal aria-label="Close create user form">&times;</button>
            </div>

            <?php if ($adminCreateUserErrors): ?>
                <div class="admin-users-create-errors" role="alert">
                    <?php foreach ($adminCreateUserErrors as $adminCreateUserError): ?>
                        <p><?php echo htmlspecialchars((string) $adminCreateUserError, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="admin-users-create-form" method="post" action="">
                <input type="hidden" name="admin_action" value="admin_create_user">

                <fieldset class="admin-users-create-role-fieldset">
                    <legend>Role</legend>
                    <label>
                        <input type="radio" name="role" value="customer" <?php echo $adminCreateUserValues['role'] === 'customer' ? 'checked' : ''; ?> onchange="updateFieldLabels()">
                        <span>Customer</span>
                    </label>
                    <label>
                        <input type="radio" name="role" value="staff" <?php echo $adminCreateUserValues['role'] === 'staff' ? 'checked' : ''; ?> onchange="updateFieldLabels()">
                        <span>Staff</span>
                    </label>
                    <label>
                        <input type="radio" name="role" value="admin" <?php echo $adminCreateUserValues['role'] === 'admin' ? 'checked' : ''; ?> onchange="updateFieldLabels()">
                        <span>Admin</span>
                    </label>
                </fieldset>

                <label for="admin-create-user-full-name" id="full-name-label"><?php echo $adminCreateUserValues['role'] === 'admin' ? 'Employee Number' : ($adminCreateUserValues['role'] === 'staff' ? 'Name' : 'Full Name'); ?></label>
                <input id="admin-create-user-full-name" name="full_name" type="text" value="<?php echo htmlspecialchars($adminCreateUserValues['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label for="admin-create-user-email" id="email-label"><?php echo $adminCreateUserValues['role'] === 'admin' ? 'Username' : 'Email'; ?></label>
                <input id="admin-create-user-email" name="email" type="<?php echo $adminCreateUserValues['role'] === 'admin' ? 'text' : 'email'; ?>" value="<?php echo htmlspecialchars($adminCreateUserValues['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <fieldset class="admin-users-create-status-fieldset" id="admin-create-user-status-fieldset"<?php echo $adminCreateUserValues['role'] === 'customer' ? '' : ' style="display:none;"'; ?>>
                    <legend>Account Status (Customer)</legend>
                    <label>
                        <input type="radio" name="account_status" value="active" <?php echo $adminCreateUserValues['account_status'] === 'active' ? 'checked' : ''; ?>>
                        <span>Active</span>
                    </label>
                    <label>
                        <input type="radio" name="account_status" value="inactive" <?php echo $adminCreateUserValues['account_status'] === 'inactive' ? 'checked' : ''; ?>>
                        <span>Inactive</span>
                    </label>
                </fieldset>

                <label for="admin-create-user-password">Password</label>
                <input id="admin-create-user-password" name="password" type="password" required>

                <label for="admin-create-user-confirm-password">Confirm Password</label>
                <input id="admin-create-user-confirm-password" name="confirm_password" type="password" required>

                <p class="admin-users-create-note"<?php echo $adminCreateUserValues['role'] === 'customer' ? '' : ' style="display:none;"'; ?>>Customer accounts created here omit the normal signup verification flow when marked Active.</p>

                <div class="admin-users-create-actions">
                    <button class="admin-users-create-cancel" type="button" data-admin-users-close-modal>Cancel</button>
                    <button class="admin-users-create-submit" type="submit">Create</button>
                </div>
            </form>
        </section>
    </div>
    <?php endif; ?>

    <div class="admin-edit-modal-backdrop" data-admin-edit-backdrop data-admin-duplicate-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/duplicate_product.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-update-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_product.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-archive-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/archive_product.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-restore-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/restore_archived_product.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-create-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/create_product.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-product-base-url="<?php echo htmlspecialchars($routeBase . 'products/?product=', ENT_QUOTES, 'UTF-8'); ?>" hidden>
        <section class="admin-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-edit-title">
            <div class="admin-edit-modal-head">
                <h2 id="admin-edit-title">Edit Featured Product</h2>
                <button class="admin-edit-close" type="button" data-admin-edit-close aria-label="Close edit window">&times;</button>
            </div>

            <form class="admin-edit-form" data-admin-edit-form>
                <div class="admin-edit-grid">
                    <div class="admin-edit-image-column">
                        <div class="admin-edit-image-preview" data-admin-edit-image-preview>
                            <img src="" alt="Product preview" data-admin-edit-preview-img draggable="false">
                            <div class="admin-crop-drag-badge" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/drag_pan.svg" alt="">
                                <span>Drag</span>
                            </div>
                        </div>

                        <input type="file" accept="image/*" data-admin-edit-file hidden>

                        <div class="admin-edit-image-actions" data-admin-edit-image-actions>
                            <button type="button" class="admin-icon-action admin-icon-action-browse" data-admin-edit-browse aria-label="Browse" title="Browse">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/folder_open.svg" alt="">
                            </button>
                            <button type="button" class="admin-icon-action admin-icon-action-edit" data-admin-edit-recrop aria-label="Edit Crop" title="Edit Crop">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/crop.svg" alt="">
                            </button>
                        </div>

                        <div class="admin-crop-workspace" data-admin-crop-workspace hidden>
                            <div class="admin-crop-controls">
                                <label class="admin-crop-zoom-label">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/zoom_in_out.svg" alt="" aria-hidden="true">
                                    <span class="sr-only">Zoom</span>
                                    <input type="range" min="1" max="3" step="0.01" value="1" data-admin-edit-zoom>
                                </label>
                            </div>

                            <div class="admin-crop-actions">
                                <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-edit-crop-cancel aria-label="Cancel" title="Cancel">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                                </button>
                                <button type="button" class="admin-icon-action admin-icon-action-save" data-admin-edit-crop-save aria-label="Save" title="Save">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="admin-edit-fields-column">
                        <label class="admin-edit-label" for="admin-edit-brand">Brand</label>
                        <select id="admin-edit-brand" data-admin-edit-brand data-admin-manage-brands-url="<?php echo htmlspecialchars($manageBrandsPath, ENT_QUOTES, 'UTF-8'); ?>" required>
                            <?php foreach ($productBrandValueMap as $brandValue => $brandLabel): ?>
                                <option value="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($brandLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                            <option value="__manage_brands__">Manage Brands</option>
                        </select>

                        <label class="admin-edit-label" for="admin-edit-name">Product Name</label>
                        <input id="admin-edit-name" type="text" data-admin-edit-name required>

                        <label class="admin-edit-label" for="admin-edit-spec1">Specs 1</label>
                        <input id="admin-edit-spec1" type="text" data-admin-edit-spec1 required>

                        <label class="admin-edit-label" for="admin-edit-spec2">Specs 2</label>
                        <input id="admin-edit-spec2" type="text" data-admin-edit-spec2 required>

                        <label class="admin-edit-label" for="admin-edit-price">Price</label>
                        <div class="admin-edit-money-field">
                            <span class="admin-edit-currency" aria-hidden="true">&#8369;</span>
                            <input id="admin-edit-price" type="number" min="0" step="0.01" data-admin-edit-price required>
                        </div>

                        <label class="admin-edit-label" for="admin-edit-discount">Discount Percentage</label>
                        <input id="admin-edit-discount" type="number" min="0" max="95" step="1" data-admin-edit-discount>
                    </div>
                </div>

                <div class="admin-edit-actions" data-admin-edit-main-actions>
                    <button type="button" class="admin-edit-secondary admin-edit-duplicate" data-admin-edit-duplicate>Duplicate Product</button>
                    <div class="admin-edit-icon-actions">
                        <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-edit-cancel aria-label="Cancel" title="Cancel">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                        </button>
                        <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save Changes" title="Save Changes">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <aside class="admin-undo-toast" data-admin-undo-toast hidden aria-live="polite" aria-atomic="true">
        <p class="admin-undo-toast-message" data-admin-undo-message>Product archived.</p>
        <button class="admin-undo-toast-button" type="button" data-admin-undo-action>Undo</button>
    </aside>

    <div class="admin-edit-modal-backdrop" data-admin-how-edit-backdrop hidden>
        <section class="admin-edit-modal admin-how-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-how-edit-title">
            <div class="admin-edit-modal-head">
                <h2 id="admin-how-edit-title">Edit How It Works Image</h2>
                <button class="admin-edit-close" type="button" data-admin-how-close aria-label="Close edit window">&times;</button>
            </div>

            <form class="admin-edit-form" data-admin-how-form>
                <div class="admin-edit-grid">
                    <div class="admin-edit-image-column">
                        <div class="admin-edit-image-preview" data-admin-how-preview-wrap>
                            <img src="" alt="How it works preview" data-admin-how-preview-img draggable="false" hidden>
                            <div class="admin-crop-drag-badge" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/drag_pan.svg" alt="">
                                <span>Drag</span>
                            </div>
                        </div>

                        <input type="file" accept="image/*" data-admin-how-file hidden>

                        <div class="admin-edit-image-actions" data-admin-how-image-actions>
                            <button type="button" class="admin-icon-action admin-icon-action-browse" data-admin-how-browse aria-label="Browse" title="Browse">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/folder_open.svg" alt="">
                            </button>
                            <button type="button" class="admin-icon-action admin-icon-action-edit" data-admin-how-recrop aria-label="Edit Crop" title="Edit Crop">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/crop.svg" alt="">
                            </button>
                        </div>

                        <div class="admin-crop-workspace" data-admin-how-crop-workspace hidden>
                            <div class="admin-crop-controls">
                                <label class="admin-crop-zoom-label">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/zoom_in_out.svg" alt="" aria-hidden="true">
                                    <span class="sr-only">Zoom</span>
                                    <input type="range" min="1" max="3" step="0.01" value="1" data-admin-how-zoom>
                                </label>
                            </div>

                            <div class="admin-crop-actions">
                                <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-how-crop-cancel aria-label="Cancel" title="Cancel">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                                </button>
                                <button type="button" class="admin-icon-action admin-icon-action-save" data-admin-how-crop-save aria-label="Save" title="Save">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="admin-edit-fields-column">
                        <p class="admin-how-slot-note" data-admin-how-slot-note>Slot 1 image (3:2)</p>
                        <p class="admin-how-slot-help">Files are saved as <strong>1.png</strong>, <strong>2.png</strong>, <strong>3.png</strong>, and <strong>4.png</strong>.</p>
                    </div>
                </div>

                <div class="admin-edit-actions" data-admin-how-main-actions>
                    <div class="admin-edit-icon-actions">
                        <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-how-cancel aria-label="Cancel" title="Cancel">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                        </button>
                        <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save Changes" title="Save Changes">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <div class="admin-edit-modal-backdrop" data-admin-promo-edit-backdrop hidden>
        <section class="admin-edit-modal admin-promo-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-promo-edit-title">
            <div class="admin-edit-modal-head">
                <h2 id="admin-promo-edit-title">Add Promotion Banner</h2>
                <button class="admin-edit-close" type="button" data-admin-promo-close aria-label="Close edit window">&times;</button>
            </div>

            <form class="admin-edit-form" data-admin-promo-form>
                <div class="admin-edit-grid">
                    <div class="admin-edit-image-column">
                        <div class="admin-edit-image-preview" data-admin-promo-preview-wrap>
                            <img src="" alt="Promotion banner preview" data-admin-promo-preview-img draggable="false" hidden>
                            <div class="admin-crop-drag-badge" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/drag_pan.svg" alt="">
                                <span>Drag</span>
                            </div>
                        </div>

                        <input type="file" accept="image/*" data-admin-promo-file hidden>

                        <div class="admin-edit-image-actions" data-admin-promo-image-actions>
                            <button type="button" class="admin-icon-action admin-icon-action-browse" data-admin-promo-browse aria-label="Browse" title="Browse">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/folder_open.svg" alt="">
                            </button>
                            <button type="button" class="admin-icon-action admin-icon-action-edit" data-admin-promo-recrop aria-label="Edit Crop" title="Edit Crop">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/crop.svg" alt="">
                            </button>
                        </div>

                        <div class="admin-crop-workspace" data-admin-promo-crop-workspace hidden>
                            <div class="admin-crop-controls">
                                <label class="admin-crop-zoom-label">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/zoom_in_out.svg" alt="" aria-hidden="true">
                                    <span class="sr-only">Zoom</span>
                                    <input type="range" min="1" max="3" step="0.01" value="1" data-admin-promo-zoom>
                                </label>
                            </div>

                            <div class="admin-crop-actions">
                                <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-promo-crop-cancel aria-label="Cancel" title="Cancel">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                                </button>
                                <button type="button" class="admin-icon-action admin-icon-action-save" data-admin-promo-crop-save aria-label="Save" title="Save">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="admin-edit-fields-column">
                        <p class="admin-how-slot-note" data-admin-promo-slot-note>Promo slot 1 image (3:1)</p>
                        <p class="admin-how-slot-help">Files are saved as <strong>0001.png</strong>, <strong>0002.png</strong>, and <strong>0003.png</strong>.</p>
                    </div>
                </div>

                <div class="admin-edit-actions" data-admin-promo-main-actions>
                    <div class="admin-edit-icon-actions">
                        <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-promo-cancel aria-label="Cancel" title="Cancel">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                        </button>
                        <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save Changes" title="Save Changes">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        window.__creatyAdminBookings = <?php echo json_encode($adminBookingDetails, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyAdminBookingsSignature = <?php echo json_encode($adminBookingsSignature, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyAdminNotificationLiveEndpoint = <?php echo json_encode($liveUpdatesEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyAdminUploadDeliveryReceiptEndpoint = <?php echo json_encode($uploadDeliveryReceiptEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyAdminCloseDeliveryLegEndpoint = <?php echo json_encode($closeDeliveryLegEndpoint, JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script>
        function updateFieldLabels() {
            const roleRadios = document.querySelectorAll('input[name="role"]'); 
            if (!roleRadios.length) {
                return;
            }

            const selectedRole = Array.from(roleRadios).find(r => r.checked)?.value || 'customer';

            const fullNameLabel = document.getElementById('full-name-label');   
            const emailLabel = document.getElementById('email-label');
            const emailInput = document.getElementById('admin-create-user-email');
            const statusFieldset = document.getElementById('admin-create-user-status-fieldset');
            const noteText = document.querySelector('.admin-users-create-note');

            if (!fullNameLabel || !emailLabel || !emailInput) {
                return;
            }

            if (selectedRole === 'admin') {
                fullNameLabel.textContent = 'Employee Number';
                emailLabel.textContent = 'Username';
                emailInput.type = 'text';
                if (statusFieldset) statusFieldset.style.display = 'none';
                if (noteText) noteText.style.display = 'none';
            } else if (selectedRole === 'staff') {
                fullNameLabel.textContent = 'Name';
                emailLabel.textContent = 'Email';
                emailInput.type = 'email';
                if (statusFieldset) statusFieldset.style.display = 'none';
                if (noteText) noteText.style.display = 'none';
            } else {
                fullNameLabel.textContent = 'Full Name';
                emailLabel.textContent = 'Email';
                emailInput.type = 'email';
                if (statusFieldset) statusFieldset.style.display = 'block';
                if (noteText) noteText.style.display = 'block';
            }
        }
        document.addEventListener('DOMContentLoaded', updateFieldLabels);
    </script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260415-6"></script>
</body>
</html>



