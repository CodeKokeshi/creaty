<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-events/');
    exit;
}

session_start();

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);

$eventPackages = [
    'wedding' => [
        'title' => 'WEDDING PACKAGE',
        'price' => 'P 800.00',
        'folder' => 'wedding',
    ],
    'birthdays' => [
        'title' => 'BIRTHDAY PACKAGE',
        'price' => 'P 450.00',
        'folder' => 'birthdays',
    ],
    'debut' => [
        'title' => 'DEBUT PACKAGE',
        'price' => 'P 450.00',
        'folder' => 'debut',
    ],
    'photo-shoot' => [
        'title' => 'PHOTO SHOOT',
        'price' => 'P 600.00',
        'folder' => 'photography-and-videography',
    ],
    'business-shoots' => [
        'title' => 'BUSINESS SHOOTS',
        'price' => 'P 250.00',
        'folder' => 'business_promotion',
    ],
    'photo-video-services' => [
        'title' => 'PHOTOGRAPHY AND VIDEOGRAPHY SERVICES',
        'price' => 'P 899.00',
        'folder' => 'photography-and-videography',
    ],
];

$selectedPackageKey = strtolower(trim((string) ($_GET['package'] ?? 'wedding')));
if (!isset($eventPackages[$selectedPackageKey])) {
    $selectedPackageKey = 'wedding';
}

if (isset($_GET['add_package'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-event/?package=' . urlencode($selectedPackageKey));
        $redirectQuery = '?redirect=' . rawurlencode($currentPageUrl);
        header('Location: ' . $loginPath . $redirectQuery);
        exit;
    }

    $currentCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
    $_SESSION['customer_cart_count'] = $currentCount + 1;

    $redirectPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $query = $_GET;
    unset($query['add_package']);
    $queryString = http_build_query($query);
    $cleanUrl = $redirectPath . ($queryString !== '' ? '?' . $queryString : '');

    header('Location: ' . $cleanUrl);
    exit;
}

$cartCount = $isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0;
$accountLabel = $isCustomerLoggedIn ? 'Account' : 'Sign In';
$accountSettingsPath = $assetBase . 'customer-account-settings/';
$logoutPath = $assetBase . 'customer-logout/';
$cartPath = $assetBase . 'customer-cart/';
$eventsPath = $assetBase . 'customer-events/';
$selectedPackage = $eventPackages[$selectedPackageKey];

function formatEventLabel(string $raw): string
{
    $normalized = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $raw)) ?? '');
    if ($normalized === '') {
        return 'Untitled';
    }

    $lowerWords = ['and', 'or', 'the', 'for', 'to', 'of', 'in', 'on', 'at'];
    $parts = preg_split('/\s+/', $normalized) ?: [];
    $formatted = [];

    foreach ($parts as $index => $part) {
        if (preg_match('/^[A-Z0-9]{2,}$/', $part) === 1) {
            $formatted[] = $part;
            continue;
        }

        $token = ucfirst(strtolower($part));
        if ($index > 0 && in_array(strtolower($token), $lowerWords, true)) {
            $token = strtolower($token);
        }

        $formatted[] = $token;
    }

    return implode(' ', $formatted);
}

function parseEventFolderName(string $folderName): array
{
    $parts = explode('_', $folderName, 2);
    $categoryRaw = $parts[0] ?? $folderName;
    $nameRaw = $parts[1] ?? $categoryRaw;

    return [
        'category' => formatEventLabel($categoryRaw),
        'name' => formatEventLabel($nameRaw),
    ];
}

/**
 * @return string[]
 */
function collectEventPhotos(string $projectRoot, string $eventDirectory): array
{
    if (!is_dir($eventDirectory)) {
        return [];
    }

    $images = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($eventDirectory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $extension = strtolower((string) pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = substr($absolutePath, strlen($projectRoot) + 1);
        if ($relativePath === false || $relativePath === '') {
            continue;
        }

        $images[] = str_replace('\\', '/', $relativePath);
    }

    natcasesort($images);

    return array_values($images);
}

/**
 * @return array<int, array<string, mixed>>
 */
function collectPackageEvents(string $projectRoot, string $packageFolder): array
{
    $packageDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'event_packages' . DIRECTORY_SEPARATOR . $packageFolder;
    if (!is_dir($packageDirectory)) {
        return [];
    }

    $events = [];
    $iterator = new DirectoryIterator($packageDirectory);

    foreach ($iterator as $entry) {
        if (!$entry->isDir() || $entry->isDot()) {
            continue;
        }

        $folderName = $entry->getFilename();
        $labels = parseEventFolderName($folderName);

        $events[] = [
            'folder_name' => $folderName,
            'category_label' => $labels['category'],
            'event_label' => $labels['name'],
            'images' => collectEventPhotos($projectRoot, $entry->getPathname()),
        ];
    }

    usort(
        $events,
        static function (array $left, array $right): int {
            return strnatcasecmp($left['folder_name'], $right['folder_name']);
        }
    );

    return $events;
}

function buildAssetUrl(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

$projectRoot = __DIR__;
$packageEvents = collectPackageEvents($projectRoot, $selectedPackage['folder']);
$categoryGroups = [];

foreach ($packageEvents as $event) {
    $category = $event['category_label'];
    if (!isset($categoryGroups[$category])) {
        $categoryGroups[$category] = [];
    }

    $categoryGroups[$category][] = $event;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | <?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260319-2">
</head>
<body class="events-page event-detail-page">
    <header class="site-header">
        <div class="topbar">
            <a class="brand-badge" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <a class="topbar-link topbar-help" href="#">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/help_icon.svg" alt="">
                <span>Help</span>
            </a>

            <form class="topbar-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search packages, events, or services">
            </form>

            <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>customer-cart/" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#">Message us</a>
            <?php if ($isCustomerLoggedIn): ?>
                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Account Settings</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>">My Cart</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Events</a></li>
                        <li><a class="dropdown-item" href="#">Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <nav class="section-nav section-nav-disabled" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
            <a class="section-nav-section is-active" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">EVENTS</a>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
        </nav>
    </header>

    <main class="events-shell event-detail-shell">
        <section class="catalog-section reveal">
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to event packages">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <div class="event-detail-header-copy">
                    <h1><?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Event folders below are categorized sample galleries for this package.</p>
                </div>
            </div>

            <article class="event-package-cta">
                <div class="event-package-cta-copy">
                    <p class="event-package-cta-label">Selected Package</p>
                    <h2><?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>

                <div class="event-package-cta-actions">
                    <span><?php echo htmlspecialchars($selectedPackage['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php
                    $packagePreview = $assetBase . 'assets/images/main_logo.png';
                    if ($packageEvents !== [] && !empty($packageEvents[0]['images'])) {
                        $packagePreview = buildAssetUrl($assetBase, $packageEvents[0]['images'][0]);
                    }
                    $eventDetailLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-event/?package=' . urlencode($selectedPackageKey)));
                    ?>
                    <button
                        type="button"
                        data-add-cart
                        data-item-id="event-<?php echo htmlspecialchars($selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                        data-item-type="event-package"
                        data-item-name="<?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-item-copy="Package booking for <?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?> sample galleries."
                        data-item-image="<?php echo htmlspecialchars($packagePreview, ENT_QUOTES, 'UTF-8'); ?>"
                        data-item-price="<?php echo htmlspecialchars($selectedPackage['price'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php if (!$isCustomerLoggedIn): ?>
                            data-login-url="<?php echo htmlspecialchars($eventDetailLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php endif; ?>
                    >
                        ADD TO CART
                    </button>
                </div>
            </article>

            <?php if ($categoryGroups === []): ?>
                <article class="event-gallery-empty-state">
                    <h2>No event photos found yet.</h2>
                    <p>This package has no uploaded event galleries at the moment.</p>
                </article>
            <?php endif; ?>

            <?php foreach ($categoryGroups as $categoryLabel => $events): ?>
                <section class="event-category-section" aria-labelledby="category-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $categoryLabel)), ENT_QUOTES, 'UTF-8'); ?>">
                    <header class="event-category-head">
                        <h2 id="category-<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $categoryLabel)), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span><?php echo count($events); ?> event<?php echo count($events) === 1 ? '' : 's'; ?></span>
                    </header>

                    <div class="event-card-grid">
                        <?php foreach ($events as $event): ?>
                            <article class="event-gallery-card">
                                <header class="event-gallery-meta">
                                    <h3><?php echo htmlspecialchars($event['event_label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                </header>

                                <?php if ($event['images'] !== []): ?>
                                    <div class="event-photo-masonry" aria-label="<?php echo htmlspecialchars($event['event_label'], ENT_QUOTES, 'UTF-8'); ?> gallery">
                                        <?php foreach ($event['images'] as $imageIndex => $imagePath): ?>
                                            <figure class="event-photo-item">
                                                <img
                                                    src="<?php echo htmlspecialchars(buildAssetUrl($assetBase, $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                                    alt="<?php echo htmlspecialchars($event['event_label'] . ' photo ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                                    loading="lazy"
                                                >
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="event-card-empty">No photos were found for this event yet.</div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260319-1"></script>
</body>
</html>