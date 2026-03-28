<?php

function event_packages_repository_path()
{
    return __DIR__ . '/event_packages.json';
}

function event_packages_repository_defaults()
{
    return [
        'wedding' => [
            'title' => 'WEDDING PACKAGE',
            'price' => '800.00',
            'discountPercent' => 0,
            'folder' => 'wedding',
            'thumbnail_images' => [],
        ],
        'birthdays' => [
            'title' => 'BIRTHDAY PACKAGE',
            'price' => '450.00',
            'discountPercent' => 0,
            'folder' => 'birthdays',
            'thumbnail_images' => [],
        ],
        'debut' => [
            'title' => 'DEBUT PACKAGE',
            'price' => '450.00',
            'discountPercent' => 0,
            'folder' => 'debut',
            'thumbnail_images' => [],
        ],
        'photo-shoot' => [
            'title' => 'PHOTO SHOOT',
            'price' => '600.00',
            'discountPercent' => 0,
            'folder' => 'photography-and-videography',
            'thumbnail_images' => [],
        ],
        'business-shoots' => [
            'title' => 'BUSINESS SHOOTS',
            'price' => '250.00',
            'discountPercent' => 0,
            'folder' => 'business_promotion',
            'thumbnail_images' => [],
        ],
        'photo-video-services' => [
            'title' => 'PHOTOGRAPHY AND VIDEOGRAPHY SERVICES',
            'price' => '899.00',
            'discountPercent' => 0,
            'folder' => 'photography-and-videography',
            'thumbnail_images' => [],
        ],
    ];
}

function normalize_event_package_key($value)
{
    $normalized = strtolower(trim((string) $value));
    $normalized = preg_replace('/[^a-z0-9-]+/', '-', $normalized);
    $normalized = trim((string) $normalized, '-');

    return $normalized;
}

function normalize_event_package_record($key, $record, $defaults)
{
    $fallback = isset($defaults[$key]) && is_array($defaults[$key])
        ? $defaults[$key]
        : [
            'title' => strtoupper(str_replace('-', ' ', $key)),
            'price' => '0.00',
            'discountPercent' => 0,
            'folder' => '',
            'thumbnail_images' => [],
        ];

    if (!is_array($record)) {
        $record = [];
    }

    $title = trim((string) ($record['title'] ?? $fallback['title']));
    if ($title === '') {
        $title = (string) $fallback['title'];
    }

    $priceValue = (float) ($record['price'] ?? $fallback['price']);
    $priceValue = max(0, $priceValue);

    $discountValue = (int) ($record['discountPercent'] ?? $fallback['discountPercent']);
    $discountValue = max(0, min(95, $discountValue));

    $folder = trim((string) ($record['folder'] ?? $fallback['folder']));
    $thumbnailImagesInput = $record['thumbnail_images'] ?? $fallback['thumbnail_images'];
    $thumbnailImages = [];

    if (is_array($thumbnailImagesInput)) {
        foreach ($thumbnailImagesInput as $thumbnailImagePath) {
            $normalizedPath = trim(str_replace('\\', '/', (string) $thumbnailImagePath));
            $normalizedPath = ltrim($normalizedPath, '/');

            if ($normalizedPath === '' || isset($thumbnailImages[$normalizedPath])) {
                continue;
            }

            $thumbnailImages[$normalizedPath] = $normalizedPath;
        }
    }

    return [
        'title' => $title,
        'price' => number_format($priceValue, 2, '.', ''),
        'discountPercent' => $discountValue,
        'folder' => $folder,
        'thumbnail_images' => array_values($thumbnailImages),
    ];
}

function load_event_packages_repository()
{
    $defaults = event_packages_repository_defaults();
    $path = event_packages_repository_path();

    if (!is_file($path)) {
        return $defaults;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return $defaults;
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return $defaults;
    }

    $normalized = [];

    foreach ($decoded as $recordKey => $record) {
        $nextKey = is_string($recordKey) ? normalize_event_package_key($recordKey) : '';

        if ($nextKey === '' && is_array($record)) {
            $nextKey = normalize_event_package_key($record['key'] ?? '');
        }

        if ($nextKey === '' || isset($normalized[$nextKey])) {
            continue;
        }

        $normalized[$nextKey] = normalize_event_package_record($nextKey, $record, $defaults);
    }

    if ($normalized === []) {
        return $defaults;
    }

    foreach ($defaults as $defaultKey => $defaultRecord) {
        if (!isset($normalized[$defaultKey])) {
            $normalized[$defaultKey] = normalize_event_package_record($defaultKey, $defaultRecord, $defaults);
        }
    }

    return $normalized;
}

function save_event_packages_repository($eventPackages)
{
    if (!is_array($eventPackages) || $eventPackages === []) {
        return false;
    }

    $defaults = event_packages_repository_defaults();
    $normalized = [];

    foreach ($eventPackages as $key => $record) {
        $nextKey = normalize_event_package_key($key);

        if ($nextKey === '' || isset($normalized[$nextKey])) {
            continue;
        }

        $normalized[$nextKey] = normalize_event_package_record($nextKey, $record, $defaults);
    }

    if ($normalized === []) {
        return false;
    }

    $encoded = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents(event_packages_repository_path(), $encoded . PHP_EOL, LOCK_EX) !== false;
}
