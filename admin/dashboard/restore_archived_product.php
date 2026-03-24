<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

require dirname(__DIR__, 2) . '/config/products_repository.php';

$payloadRaw = file_get_contents('php://input');
$payload = json_decode((string) $payloadRaw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$archiveKey = trim((string) ($payload['archiveKey'] ?? ''));
if ($archiveKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Archive key is required']);
    exit;
}

$products = load_products_repository();
$archivedProducts = load_archived_products_repository();
$projectRoot = dirname(__DIR__, 2);

try {
    $result = restore_archived_product_record($products, $archivedProducts, $archiveKey, $projectRoot);

    if (!save_products_repository($result['products'])) {
        throw new RuntimeException('Unable to save active products.');
    }

    if (!save_archived_products_repository($result['archivedProducts'])) {
        throw new RuntimeException('Unable to save archive data.');
    }

    echo json_encode([
        'ok' => true,
        'restoredKey' => $result['restoredKey'],
        'restoredProduct' => $result['restoredProduct']
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $error->getMessage()
    ]);
}
