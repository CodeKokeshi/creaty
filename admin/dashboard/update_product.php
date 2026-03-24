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

$productKey = trim((string) ($payload['productKey'] ?? ''));
if ($productKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Product key is required']);
    exit;
}

$products = load_products_repository();
if (!isset($products[$productKey]) || !is_array($products[$productKey])) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Product not found']);
    exit;
}

$brand = normalize_product_brand($payload['brand'] ?? 'Canon');
$name = trim((string) ($payload['name'] ?? ''));
$spec1 = trim((string) ($payload['spec1'] ?? ''));
$spec2 = trim((string) ($payload['spec2'] ?? ''));
$tagline = trim((string) ($payload['tagline'] ?? ''));
$priceValue = (float) ($payload['price'] ?? 0);
$discount = (int) ($payload['discountPercent'] ?? 0);
$discount = max(0, min(95, $discount));

if ($name === '' || $spec1 === '' || $spec2 === '' || $priceValue < 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

if (has_duplicate_product_display_name($products, $brand, $name, $productKey)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Another product already uses that brand and name.']);
    exit;
}

$product = $products[$productKey];
$product['brand'] = $brand;
$product['name'] = $name;
$product['spec1'] = $spec1;
$product['spec2'] = $spec2;
$product['price'] = number_format($priceValue, 2, '.', '');
$product['discountPercent'] = $discount;
$product['tagline'] = $tagline !== '' ? $tagline : $spec1 . ' ' . $spec2;

$imagingLines = normalize_lines_array($payload['imagingSpecs'] ?? ($product['specs']['Imaging and Performance'] ?? []));
$videoLines = normalize_lines_array($payload['videoSpecs'] ?? ($product['specs']['Video'] ?? []));
$physicalLines = normalize_lines_array($payload['physicalSpecs'] ?? ($product['specs']['Physical Specifications'] ?? []));
$slides = normalize_lines_array($payload['captureSlides'] ?? ($product['captureSlides'] ?? []));

if (count($slides) < 1) {
    $slides = ['Sample capture'];
}

$product['captureSlides'] = array_slice($slides, 0, 5);
$product['specs'] = [
    'Brand' => [$brand],
    'Imaging and Performance' => count($imagingLines) ? $imagingLines : ['Sensor details pending.'],
    'Video' => count($videoLines) ? $videoLines : ['Video details pending.'],
    'Physical Specifications' => count($physicalLines) ? $physicalLines : ['Physical details pending.']
];

unset($product['availability']);
unset($product['featuredDate']);

$imageDataUrl = trim((string) ($payload['imageDataUrl'] ?? ''));
if (strpos($imageDataUrl, 'data:image/') === 0) {
    try {
        $projectRoot = dirname(__DIR__, 2);
        $product['cameraImage'] = save_product_image_from_data_url($imageDataUrl, $brand, $name, $projectRoot);
    } catch (Throwable $error) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
        exit;
    }
}

$products[$productKey] = $product;

if (!save_products_repository($products)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to save product updates.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'productKey' => $productKey,
    'product' => $product
]);
