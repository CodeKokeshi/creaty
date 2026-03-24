<?php

function products_repository_path()
{
    return __DIR__ . '/products.json';
}

function load_products_repository()
{
    $path = products_repository_path();

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    // Some editors/write paths may prepend UTF-8 BOM; strip it so json_decode works reliably.
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function save_products_repository($products)
{
    $path = products_repository_path();
    $encoded = json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function normalize_product_brand($brand)
{
    $map = [
        'canon' => 'Canon',
        'fuji' => 'Fuji',
        'nikon' => 'Nikon',
        'sony' => 'Sony'
    ];

    $normalized = strtolower(trim((string) $brand));

    return isset($map[$normalized]) ? $map[$normalized] : 'Canon';
}

function product_display_name($product)
{
    $brand = normalize_product_brand(isset($product['brand']) ? $product['brand'] : 'Canon');
    $name = trim((string) (isset($product['name']) ? $product['name'] : ''));

    return trim($brand . ' ' . $name);
}

function product_slug($brand, $name)
{
    $raw = strtolower(trim($brand . ' ' . $name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $raw);
    $slug = trim((string) $slug, '-');

    return $slug !== '' ? $slug : 'product';
}

function unique_product_name($products, $brand, $baseName)
{
    $brandLabel = normalize_product_brand($brand);
    $trimmedBase = trim((string) $baseName);
    $existing = [];

    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }

        $existing[strtolower(product_display_name($product))] = true;
    }

    $copyCandidate = $trimmedBase . ' (Copy)';
    if (!isset($existing[strtolower($brandLabel . ' ' . $copyCandidate)])) {
        return $copyCandidate;
    }

    for ($i = 1; $i < 1000; $i++) {
        $candidate = $trimmedBase . ' (' . $i . ')';

        if (!isset($existing[strtolower($brandLabel . ' ' . $candidate)])) {
            return $candidate;
        }
    }

    return $trimmedBase . ' (' . time() . ')';
}

function unique_product_key($products, $brand, $name)
{
    $base = product_slug($brand, $name);

    if (!isset($products[$base])) {
        return $base;
    }

    for ($i = 1; $i < 1000; $i++) {
        $candidate = $base . '-' . $i;

        if (!isset($products[$candidate])) {
            return $candidate;
        }
    }

    return $base . '-' . time();
}

function sanitize_product_filename($value)
{
    $cleaned = preg_replace('/[\\\\\/:*?"<>|]+/', ' ', (string) $value);
    $cleaned = preg_replace('/\s+/', ' ', (string) $cleaned);

    return trim((string) $cleaned);
}

function copy_product_image_for_duplicate($sourceRelativePath, $brand, $name, $projectRoot)
{
    $relativeSource = ltrim((string) $sourceRelativePath, '/');
    if ($relativeSource === '') {
        throw new RuntimeException('Source image path is missing.');
    }

    $sourceAbsolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rawurldecode($relativeSource));

    if (!is_file($sourceAbsolutePath)) {
        throw new RuntimeException('Source image file not found: ' . $relativeSource);
    }

    $extension = strtolower((string) pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION));
    if ($extension === '') {
        $extension = 'png';
    }

    $targetDirRelative = 'assets/cameras';
    $targetDirAbsolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDirAbsolute)) {
        throw new RuntimeException('Target image directory is missing.');
    }

    $baseFilename = sanitize_product_filename(normalize_product_brand($brand) . ' ' . $name);
    if ($baseFilename === '') {
        $baseFilename = 'Product Copy';
    }

    $counter = 0;
    do {
        $suffix = $counter === 0 ? '' : ' ' . $counter;
        $rawName = $baseFilename . $suffix . '.' . $extension;
        $targetRelativePath = $targetDirRelative . '/' . rawurlencode($rawName);
        $targetAbsolutePath = $targetDirAbsolute . DIRECTORY_SEPARATOR . $rawName;
        $counter++;
    } while (file_exists($targetAbsolutePath) && $counter < 1000);

    if (!copy($sourceAbsolutePath, $targetAbsolutePath)) {
        throw new RuntimeException('Unable to copy image asset.');
    }

    return $targetRelativePath;
}

function duplicate_product_record($products, $sourceKey, $projectRoot)
{
    if (!isset($products[$sourceKey]) || !is_array($products[$sourceKey])) {
        throw new RuntimeException('Product to duplicate was not found.');
    }

    $source = $products[$sourceKey];
    $brand = normalize_product_brand(isset($source['brand']) ? $source['brand'] : 'Canon');
    $sourceName = trim((string) (isset($source['name']) ? $source['name'] : 'Product'));

    if ($sourceName === '') {
        $sourceName = 'Product';
    }

    $newName = unique_product_name($products, $brand, $sourceName);
    $newKey = unique_product_key($products, $brand, $newName);

    $duplicate = $source;
    $duplicate['brand'] = $brand;
    $duplicate['name'] = $newName;
    unset($duplicate['availability']);
    unset($duplicate['featuredDate']);
    $duplicate['cameraImage'] = copy_product_image_for_duplicate(
        isset($source['cameraImage']) ? $source['cameraImage'] : '',
        $brand,
        $newName,
        $projectRoot
    );

    $products[$newKey] = $duplicate;

    return [
        'products' => $products,
        'newKey' => $newKey,
        'newProduct' => $duplicate
    ];
}

function normalize_lines_array($value)
{
    if (is_array($value)) {
        $lines = $value;
    } else {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value);
    }

    $result = [];

    foreach ($lines as $line) {
        $trimmed = trim((string) $line);

        if ($trimmed !== '') {
            $result[] = $trimmed;
        }
    }

    return array_values($result);
}

function product_full_name($brand, $name)
{
    return trim(normalize_product_brand($brand) . ' ' . trim((string) $name));
}

function has_duplicate_product_display_name($products, $brand, $name, $exceptKey)
{
    $target = strtolower(product_full_name($brand, $name));

    foreach ($products as $key => $product) {
        if ($key === $exceptKey || !is_array($product)) {
            continue;
        }

        if (strtolower(product_display_name($product)) === $target) {
            return true;
        }
    }

    return false;
}

function save_product_image_from_data_url($dataUrl, $brand, $name, $projectRoot)
{
    $value = (string) $dataUrl;

    if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,(.+)$/i', $value, $matches)) {
        throw new RuntimeException('Invalid image payload.');
    }

    $extension = strtolower($matches[1]);
    if ($extension === 'jpg') {
        $extension = 'jpeg';
    }

    $binary = base64_decode($matches[2], true);
    if ($binary === false) {
        throw new RuntimeException('Invalid base64 image payload.');
    }

    $targetDirRelative = 'assets/cameras';
    $targetDirAbsolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDirAbsolute)) {
        throw new RuntimeException('Target image directory is missing.');
    }

    $baseFilename = sanitize_product_filename(normalize_product_brand($brand) . ' ' . $name);
    if ($baseFilename === '') {
        $baseFilename = 'Product';
    }

    $counter = 0;
    do {
        $suffix = $counter === 0 ? '' : ' ' . $counter;
        $rawName = $baseFilename . $suffix . '.' . $extension;
        $targetRelativePath = $targetDirRelative . '/' . rawurlencode($rawName);
        $targetAbsolutePath = $targetDirAbsolute . DIRECTORY_SEPARATOR . $rawName;
        $counter++;
    } while (file_exists($targetAbsolutePath) && $counter < 1000);

    if (file_put_contents($targetAbsolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save edited image asset.');
    }

    return $targetRelativePath;
}
