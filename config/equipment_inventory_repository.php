<?php

require_once __DIR__ . '/products_repository.php';

function equipment_inventory_repository_path()
{
    return __DIR__ . '/equipment_inventory.json';
}

function archived_equipment_units_repository_path()
{
    return __DIR__ . '/archives/equipment_units_archived.json';
}

function load_equipment_inventory_repository()
{
    $path = equipment_inventory_repository_path();

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : [];
}

function save_equipment_inventory_repository($inventory)
{
    $path = equipment_inventory_repository_path();
    $encoded = json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function load_archived_equipment_units_repository()
{
    $path = archived_equipment_units_repository_path();

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        return [];
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw);
    $decoded = json_decode($raw, true);

    return is_array($decoded) ? array_values($decoded) : [];
}

function save_archived_equipment_units_repository($archivedUnits)
{
    $path = archived_equipment_units_repository_path();
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        return false;
    }

    $encoded = json_encode(array_values((array) $archivedUnits), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function normalize_equipment_token($value)
{
    $upper = strtoupper(trim((string) $value));
    $token = preg_replace('/[^A-Z0-9]+/', '', $upper);

    return $token !== '' ? $token : 'MODEL';
}

function equipment_model_label_from_product($product)
{
    $brand = normalize_product_brand($product['brand'] ?? 'Canon');
    $name = trim((string) ($product['name'] ?? 'Product'));

    return normalize_equipment_token($brand) . '_' . normalize_equipment_token($name);
}

function equipment_unit_identifier($modelLabel, $serial)
{
    $prefix = str_replace('_', '', (string) $modelLabel);
    $serialValue = max(0, (int) $serial);

    return $prefix . '_' . str_pad((string) $serialValue, 3, '0', STR_PAD_LEFT);
}

function allowed_equipment_statuses()
{
    return [
        'available',
        'maintenance',
        'in-use',
        'retired'
    ];
}

function normalize_equipment_status($status)
{
    $value = strtolower(trim((string) $status));

    return in_array($value, allowed_equipment_statuses(), true) ? $value : 'available';
}

function normalize_equipment_inventory_entry($entry, $defaultTimesUsed)
{
    $timesUsed = isset($entry['timesUsed']) ? (int) $entry['timesUsed'] : (int) $defaultTimesUsed;
    $timesUsed = max(0, $timesUsed);

    $nextSerial = isset($entry['nextSerial']) ? (int) $entry['nextSerial'] : 0;
    $nextSerial = max(0, $nextSerial);

    $units = [];
    $rawUnits = isset($entry['units']) && is_array($entry['units']) ? $entry['units'] : [];

    foreach ($rawUnits as $rawUnit) {
        if (!is_array($rawUnit)) {
            continue;
        }

        $serial = isset($rawUnit['serial']) ? (int) $rawUnit['serial'] : -1;
        if ($serial < 0) {
            continue;
        }

        $units[$serial] = [
            'serial' => $serial,
            'status' => normalize_equipment_status($rawUnit['status'] ?? 'available')
        ];
    }

    if (!$units) {
        $units[0] = [
            'serial' => 0,
            'status' => 'available'
        ];
    }

    ksort($units);
    $units = array_values($units);

    $maxSerial = -1;
    foreach ($units as $unit) {
        $maxSerial = max($maxSerial, (int) ($unit['serial'] ?? -1));
    }

    if ($nextSerial <= $maxSerial) {
        $nextSerial = $maxSerial + 1;
    }

    return [
        'timesUsed' => $timesUsed,
        'nextSerial' => $nextSerial,
        'units' => $units
    ];
}

function sync_equipment_inventory_with_products($products, $inventory, $defaultTimesUsed = 12)
{
    $synced = [];

    foreach ($products as $productKey => $product) {
        if (!is_array($product)) {
            continue;
        }

        $existingEntry = isset($inventory[$productKey]) && is_array($inventory[$productKey])
            ? $inventory[$productKey]
            : [];

        $synced[$productKey] = normalize_equipment_inventory_entry($existingEntry, $defaultTimesUsed);
    }

    return $synced;
}

function add_equipment_inventory_units($inventory, $productKey, $count)
{
    if (!isset($inventory[$productKey]) || !is_array($inventory[$productKey])) {
        throw new RuntimeException('Equipment model was not found in inventory.');
    }

    $addCount = max(1, (int) $count);
    $entry = $inventory[$productKey];
    $nextSerial = (int) ($entry['nextSerial'] ?? 0);
    $units = isset($entry['units']) && is_array($entry['units']) ? $entry['units'] : [];

    for ($index = 0; $index < $addCount; $index++) {
        $units[] = [
            'serial' => $nextSerial,
            'status' => 'available'
        ];
        $nextSerial++;
    }

    usort($units, static function ($left, $right) {
        $leftSerial = (int) ($left['serial'] ?? 0);
        $rightSerial = (int) ($right['serial'] ?? 0);

        return $leftSerial <=> $rightSerial;
    });

    $entry['units'] = array_values($units);
    $entry['nextSerial'] = $nextSerial;
    $inventory[$productKey] = $entry;

    return $inventory;
}

function update_equipment_unit_status($inventory, $productKey, $serial, $status)
{
    if (!isset($inventory[$productKey]) || !is_array($inventory[$productKey])) {
        throw new RuntimeException('Equipment model was not found in inventory.');
    }

    $targetSerial = (int) $serial;
    $nextStatus = normalize_equipment_status($status);

    $entry = $inventory[$productKey];
    $units = isset($entry['units']) && is_array($entry['units']) ? $entry['units'] : [];
    $matched = false;

    foreach ($units as &$unit) {
        if ((int) ($unit['serial'] ?? -1) !== $targetSerial) {
            continue;
        }

        $unit['status'] = $nextStatus;
        $matched = true;
        break;
    }
    unset($unit);

    if (!$matched) {
        throw new RuntimeException('Equipment unit was not found.');
    }

    $entry['units'] = array_values($units);
    $inventory[$productKey] = $entry;

    return $inventory;
}

function archive_equipment_unit($inventory, $archivedUnits, $products, $productKey, $serial, $reason)
{
    if (!isset($products[$productKey]) || !is_array($products[$productKey])) {
        throw new RuntimeException('Featured product was not found for this equipment unit.');
    }

    if (!isset($inventory[$productKey]) || !is_array($inventory[$productKey])) {
        throw new RuntimeException('Equipment model was not found in inventory.');
    }

    $targetSerial = (int) $serial;
    $entry = $inventory[$productKey];
    $units = isset($entry['units']) && is_array($entry['units']) ? $entry['units'] : [];
    $foundIndex = null;

    foreach ($units as $index => $unit) {
        if ((int) ($unit['serial'] ?? -1) === $targetSerial) {
            $foundIndex = $index;
            break;
        }
    }

    if ($foundIndex === null) {
        throw new RuntimeException('Equipment unit was not found.');
    }

    $unit = $units[$foundIndex];
    array_splice($units, $foundIndex, 1);

    $entry['units'] = array_values($units);
    $inventory[$productKey] = $entry;

    $model = equipment_model_label_from_product($products[$productKey]);
    $archiveKey = $model . '_' . str_pad((string) $targetSerial, 3, '0', STR_PAD_LEFT) . '_' . date('Ymd-His');

    $archivedEntry = [
        'archiveKey' => $archiveKey,
        'archivedAt' => gmdate('c'),
        'productKey' => (string) $productKey,
        'model' => $model,
        'reason' => trim((string) $reason),
        'unit' => [
            'serial' => $targetSerial,
            'status' => normalize_equipment_status($unit['status'] ?? 'available')
        ]
    ];

    $archivedUnits[] = $archivedEntry;

    return [
        'inventory' => $inventory,
        'archivedUnits' => array_values($archivedUnits),
        'archivedEntry' => $archivedEntry
    ];
}

function restore_archived_equipment_unit($inventory, $archivedUnits, $products, $archiveKey)
{
    $targetArchiveKey = trim((string) $archiveKey);
    if ($targetArchiveKey === '') {
        throw new RuntimeException('Archive key is required.');
    }

    $matchIndex = null;

    foreach ($archivedUnits as $index => $entry) {
        if (!is_array($entry)) {
            continue;
        }

        if (trim((string) ($entry['archiveKey'] ?? '')) === $targetArchiveKey) {
            $matchIndex = $index;
            break;
        }
    }

    if ($matchIndex === null) {
        throw new RuntimeException('Archived equipment unit was not found.');
    }

    $entry = $archivedUnits[$matchIndex];
    $productKey = trim((string) ($entry['productKey'] ?? ''));

    if ($productKey === '' || !isset($products[$productKey]) || !is_array($products[$productKey])) {
        throw new RuntimeException('Cannot restore unit because its featured product is no longer active.');
    }

    if (!isset($inventory[$productKey]) || !is_array($inventory[$productKey])) {
        $inventory[$productKey] = normalize_equipment_inventory_entry([], 12);
    }

    $unitPayload = isset($entry['unit']) && is_array($entry['unit']) ? $entry['unit'] : [];
    $serial = max(0, (int) ($unitPayload['serial'] ?? 0));
    $status = normalize_equipment_status($unitPayload['status'] ?? 'available');

    $modelEntry = $inventory[$productKey];
    $units = isset($modelEntry['units']) && is_array($modelEntry['units']) ? $modelEntry['units'] : [];

    foreach ($units as $existingUnit) {
        if ((int) ($existingUnit['serial'] ?? -1) === $serial) {
            throw new RuntimeException('Cannot restore unit because that serial already exists in active inventory.');
        }
    }

    $units[] = [
        'serial' => $serial,
        'status' => $status
    ];

    usort($units, static function ($left, $right) {
        $leftSerial = (int) ($left['serial'] ?? 0);
        $rightSerial = (int) ($right['serial'] ?? 0);

        return $leftSerial <=> $rightSerial;
    });

    $modelEntry['units'] = array_values($units);
    $modelEntry['nextSerial'] = max((int) ($modelEntry['nextSerial'] ?? 0), $serial + 1);
    $inventory[$productKey] = $modelEntry;

    array_splice($archivedUnits, $matchIndex, 1);

    return [
        'inventory' => $inventory,
        'archivedUnits' => array_values($archivedUnits),
        'restoredEntry' => $entry
    ];
}
