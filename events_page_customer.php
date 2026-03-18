<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'login/';

$eventPackages = [
    [
        'title' => 'WEDDING PACKAGE',
        'price' => 'P 800.00',
        'thumbnail_folder' => 'weddings',
    ],
    [
        'title' => 'BIRTHDAY PACKAGE',
        'price' => 'P 450.00',
        'thumbnail_folder' => 'birthdays',
    ],
    [
        'title' => 'DEBUT PACKAGE',
        'price' => 'P 450.00',
        'thumbnail_folder' => 'debut',
    ],
    [
        'title' => 'PHOTO SHOOT',
        'price' => 'P 600.00',
        'thumbnail_folder' => 'photography-and-videography',
    ],
    [
        'title' => 'BUSINESS SHOOTS',
        'price' => 'P 250.00',
        'thumbnail_folder' => 'business',
    ],
    [
        'title' => 'PHOTOGRAPHY AND VIDEOGRAPHY SERVICES',
        'price' => 'P 899.00',
        'thumbnail_folder' => 'photography-and-videography',
    ],
];

/**
 * @return string[]
 */
function collectEventThumbnailPaths(string $projectRoot, string $folder): array
{
    $eventRoot = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'event_packages' . DIRECTORY_SEPARATOR . '_thumbnails';
    $targetDirectory = $eventRoot . DIRECTORY_SEPARATOR . $folder;

    if (!is_dir($targetDirectory)) {
        return [];
    }

    $images = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($targetDirectory, FilesystemIterator::SKIP_DOTS)
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

function buildAssetUrl(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

$projectRoot = __DIR__;
foreach ($eventPackages as &$eventPackage) {
    $eventPackage['images'] = collectEventThumbnailPaths($projectRoot, $eventPackage['thumbnail_folder']);
}
unset($eventPackage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | Events</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260312-4">
</head>
<body class="events-page">
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

            <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>cart/" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count">1</span>
            </a>

            <a class="topbar-link" href="#">Message us</a>
            <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">Account</a>
        </div>

        <nav class="section-nav section-nav-disabled" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
            <a class="section-nav-section is-active" href="#" aria-current="page">EVENTS</a>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
        </nav>
    </header>

    <main class="events-shell">
        <section class="catalog-section reveal">
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to home">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <h1>EVENTS</h1>
            </div>

            <div class="package-grid">
                <?php foreach ($eventPackages as $index => $eventPackage): ?>
                    <?php
                    $images = $eventPackage['images'];
                    $title = $eventPackage['title'];
                    $price = $eventPackage['price'];
                    ?>
                    <article class="package-card">
                        <div
                            class="package-thumb<?php echo $images !== [] ? ' package-slideshow' : ''; ?>"
                            <?php if ($images !== []): ?>
                                data-package-slideshow
                                data-autoplay-ms="6200"
                            <?php endif; ?>
                            aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> sample photos"
                        >
                            <?php if ($images !== []): ?>
                                <div class="package-slides">
                                    <?php foreach ($images as $imageIndex => $imagePath): ?>
                                        <img
                                            class="package-slide<?php echo $imageIndex === 0 ? ' is-active' : ''; ?>"
                                            src="<?php echo htmlspecialchars(buildAssetUrl($assetBase, $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($title . ' sample ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="<?php echo $index === 0 && $imageIndex === 0 ? 'eager' : 'lazy'; ?>"
                                            aria-hidden="<?php echo $imageIndex === 0 ? 'false' : 'true'; ?>"
                                        >
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="package-empty-state">Thumbnail coming soon.</div>
                            <?php endif; ?>
                        </div>

                        <div class="package-body">
                            <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <div class="package-footer">
                                <span><?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?></span>
                                <button type="button">ADD TO CART</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>