<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: products/');
    exit;
}

session_start();

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'login/';
$productKey = $productKey ?? ($_GET['product'] ?? 'fuji-x-a3');

$isCustomerLoggedIn = isset($_SESSION['customer_id']);

if (isset($_GET['add_to_cart'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'products/');
        $redirectQuery = '?redirect=' . rawurlencode($currentPageUrl);
        header('Location: ' . $loginPath . $redirectQuery);
        exit;
    }

    $currentCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
    $_SESSION['customer_cart_count'] = $currentCount + 1;

    $redirectPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $query = $_GET;
    unset($query['add_to_cart']);
    $queryString = http_build_query($query);
    $cleanUrl = $redirectPath . ($queryString !== '' ? '?' . $queryString : '');

    header('Location: ' . $cleanUrl);
    exit;
}

$cartCount = $isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0;
$accountLabel = $isCustomerLoggedIn ? 'Account' : 'Sign In';
$accountSettingsPath = $assetBase . 'account-settings/';
$logoutPath = $assetBase . 'logout/';
$cartPath = $assetBase . 'cart/';
$eventsPath = $assetBase . 'events/';
$addToCartLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . 'products/?product=' . urlencode($productKey)));

$products = [
    'canon-700d' => [
        'brand' => 'Canon',
        'name' => '700D',
        'price' => '₱ 800.00',
        'tagline' => '18MP APS-C CMOS sensor and 1080p Full HD recording.',
        'cameraImage' => 'assets/cameras/Canon%20700D.png',
        'captureSlides' => ['Street portrait placeholder', 'Indoor sample placeholder', 'Outdoor detail placeholder'],
        'specs' => [
            'Brand' => ['Canon'],
            'Imaging and Performance' => [
                'Sensor: 18MP APS-C CMOS sensor',
                'ISO: 100-12800',
                'Continuous shooting: 5 fps',
                'Autofocus: 9-point AF system'
            ],
            'Video' => [
                'Resolution: Full HD 1080p',
                'Frame rates: 24 fps, 25 fps, 30 fps'
            ],
            'Physical Specifications' => [
                'Weight: 580g with battery and card',
                'Screen: 3-inch vari-angle touchscreen'
            ]
        ],
        'availability' => [
            'month' => 'January',
            'year' => '2026',
            'days' => ['05', '12', '19', '26']
        ],
        'recommendations' => ['nikon-d60', 'sony-zv-e10', 'fuji-x-a3']
    ],
    'canon-1200d' => [
        'brand' => 'Canon',
        'name' => '1200D',
        'price' => '₱ 450.00',
        'tagline' => '18-megapixel APS-C DSLR with straightforward controls.',
        'cameraImage' => 'assets/cameras/Canon%201200D.png',
        'captureSlides' => ['Event capture placeholder', 'Portrait placeholder', 'Backlit scene placeholder'],
        'specs' => [
            'Brand' => ['Canon'],
            'Imaging and Performance' => [
                'Sensor: 18MP APS-C CMOS sensor',
                'ISO: 100-6400 expandable to 12800',
                'Processor: DIGIC 4',
                'Autofocus: 9-point AF'
            ],
            'Video' => [
                'Resolution: Full HD 1080p',
                'Frame rates: 24 fps, 25 fps, 30 fps'
            ],
            'Physical Specifications' => [
                'Weight: 480g body only',
                'Screen: 3-inch LCD'
            ]
        ],
        'availability' => [
            'month' => 'March',
            'year' => '2026',
            'days' => ['03', '12', '18', '27']
        ],
        'recommendations' => ['canon-700d', 'canon-4000d', 'fuji-x-a3']
    ],
    'fuji-x-a3' => [
        'brand' => 'Fuji',
        'name' => 'X A3',
        'price' => '₱ 450.00',
        'tagline' => 'APS-C mirrorless camera with a clean travel-friendly body.',
        'cameraImage' => 'assets/cameras/Fujifilm%20XA-3.png',
        'captureSlides' => ['Captured photo placeholder 1', 'Captured photo placeholder 2', 'Captured photo placeholder 3'],
        'specs' => [
            'Brand' => ['Fuji'],
            'Imaging and Performance' => [
                'Sensor: 24.2MP APS-C CMOS sensor',
                'ISO: 200-6400, expandable to 100-25600',
                'Shutter speed: 30 sec to 1/32000 sec',
                'Continuous shooting: 6 fps',
                'Focus: 77-point contrast-detection AF system'
            ],
            'Video' => [
                'Resolution: Full HD 1920x1080',
                'Frame rates: 60p, 50p, 30p, 25p, 24p'
            ],
            'Physical Specifications' => [
                'Dimensions: 117 x 67 x 40 mm',
                'Weight: 339g with battery and card'
            ]
        ],
        'availability' => [
            'month' => 'September',
            'year' => '2025',
            'days' => ['09', '13', '21', '28']
        ],
        'recommendations' => ['nikon-d60', 'sony-zv-e10', 'canon-700d']
    ],
    'canon-4000d' => [
        'brand' => 'Canon',
        'name' => '4000D',
        'price' => '₱ 600.00',
        'tagline' => 'Starter DSLR with a simple layout for casual rentals.',
        'cameraImage' => 'assets/cameras/Canon%20400D.png',
        'captureSlides' => ['Lifestyle placeholder', 'Daylight placeholder', 'Landscape placeholder'],
        'specs' => [
            'Brand' => ['Canon'],
            'Imaging and Performance' => [
                'Sensor: 18MP APS-C CMOS sensor',
                'ISO: 100-6400 expandable to 12800',
                'Autofocus: 9-point AF',
                'Continuous shooting: 3 fps'
            ],
            'Video' => [
                'Resolution: Full HD 1080p',
                'Frame rates: 24 fps, 25 fps, 30 fps'
            ],
            'Physical Specifications' => [
                'Weight: 436g body only',
                'Screen: 2.7-inch LCD'
            ]
        ],
        'availability' => [
            'month' => 'June',
            'year' => '2024',
            'days' => ['07', '14', '23', '30']
        ],
        'recommendations' => ['canon-1200d', 'canon-700d', 'sony-zv-e10']
    ],
    'nikon-d60' => [
        'brand' => 'Nikon',
        'name' => 'D60',
        'price' => '₱ 250.00',
        'tagline' => 'Compact DSLR with reliable entry-level performance.',
        'cameraImage' => 'assets/cameras/Nikon%20D60.png',
        'captureSlides' => ['Studio placeholder', 'Warm daylight placeholder', 'Night scene placeholder'],
        'specs' => [
            'Brand' => ['Nikon'],
            'Imaging and Performance' => [
                'Sensor: 10.2MP CCD sensor',
                'ISO: 100-1600 expandable to 3200',
                'Autofocus: 3-point AF system',
                'Continuous shooting: 3 fps'
            ],
            'Video' => [
                'Resolution: Photo-focused body, no dedicated video mode',
                'Best use: Basic still photography rentals'
            ],
            'Physical Specifications' => [
                'Weight: 495g body only',
                'Screen: 2.5-inch LCD'
            ]
        ],
        'availability' => [
            'month' => 'January',
            'year' => '2026',
            'days' => ['02', '12', '22', '29']
        ],
        'recommendations' => ['fuji-x-a3', 'sony-zv-e10', 'canon-700d']
    ],
    'sony-zv-e10' => [
        'brand' => 'Sony',
        'name' => 'ZV E10',
        'price' => '₱ 899.00',
        'tagline' => 'Creator-focused APS-C body for hybrid photo and video shoots.',
        'cameraImage' => 'assets/cameras/Sony%20ZV-E10.png',
        'captureSlides' => ['Vlog frame placeholder', 'Product shoot placeholder', 'Outdoor reel placeholder'],
        'specs' => [
            'Brand' => ['Sony'],
            'Imaging and Performance' => [
                'Sensor: 24.2MP APS-C sensor',
                'ISO: 100-32000',
                'Autofocus: Real-time Eye AF and tracking',
                'Continuous shooting: 11 fps'
            ],
            'Video' => [
                'Resolution: 4K 30p and Full HD 120p',
                'Audio: Directional 3-capsule mic'
            ],
            'Physical Specifications' => [
                'Weight: 343g body only',
                'Screen: Side-opening vari-angle touchscreen'
            ]
        ],
        'availability' => [
            'month' => 'March',
            'year' => '2026',
            'days' => ['04', '11', '23', '29']
        ],
        'recommendations' => ['fuji-x-a3', 'canon-1200d', 'canon-700d']
    ]
];

if (!isset($products[$productKey])) {
    $productKey = 'fuji-x-a3';
}

$selectedProduct = $products[$productKey];
$productListPath = $homePath . '#featured-products-title';
$calendarDays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
$calendarRows = [
    ['30', '31', '1', '2', '3', '4', '5'],
    ['6', '7', '8', '9', '10', '11', '12'],
    ['13', '14', '15', '16', '17', '18', '19'],
    ['20', '21', '22', '23', '24', '25', '26'],
    ['27', '28', '29', '30', '1', '2', '3']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | <?php echo htmlspecialchars($selectedProduct['brand'] . ' ' . $selectedProduct['name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260312-4">
</head>
<body class="product-page">
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
                <input type="search" name="q" placeholder="Search cameras, services, or rentals">
            </form>

            <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>cart/" aria-label="Cart">
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
            <span class="section-nav-section is-disabled" aria-disabled="true">EVENTS</span>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
        </nav>
    </header>

    <main class="product-detail-shell">
        <section class="product-detail-layout reveal">
            <aside class="product-sidebar">
                <div class="product-sidebar-header">
                    <a class="catalog-back" href="<?php echo htmlspecialchars($productListPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to featured products">
                        <span class="catalog-back-icon" aria-hidden="true"></span>
                    </a>
                    <h1>Product</h1>
                </div>

                <article class="product-primary-card">
                    <div class="product-device-placeholder">
                        <img
                            class="product-device-image"
                            src="<?php echo htmlspecialchars($assetBase . $selectedProduct['cameraImage'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($selectedProduct['brand'] . ' ' . $selectedProduct['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </article>

                <section class="product-recommendations">
                    <h2>Recommendations</h2>

                    <div class="recommendation-list">
                        <?php foreach ($selectedProduct['recommendations'] as $recommendedKey): ?>
                            <?php $recommended = $products[$recommendedKey]; ?>
                            <a class="recommendation-card" href="?product=<?php echo urlencode($recommendedKey); ?>" style="display: flex; flex-direction: column; gap: 0.8rem; text-decoration: none; color: inherit;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                                    <p style="margin: 0; font-size: 0.85rem; line-height: 1.4; flex: 1;"><?php echo htmlspecialchars($recommended['tagline'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="recommendation-thumb" style="width: 80px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                        <img
                                            class="recommendation-thumb-image"
                                            src="<?php echo htmlspecialchars($assetBase . $recommended['cameraImage'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($recommended['brand'] . ' ' . $recommended['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                            style="width: 100%; height: auto; object-fit: contain; display: block;"
                                        >
                                    </div>
                                </div>
                                <span style="font-weight: 700; color: #dde531; font-size: 1.1rem;"><?php echo htmlspecialchars($recommended['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </aside>

            <section class="product-main-column">
                <article class="product-information-card">
                    <div class="product-panel-label">Informations</div>

                    <div class="detail-gallery" data-gallery>
                        <button class="detail-gallery-arrow detail-gallery-arrow-left" type="button" data-gallery-direction="prev" aria-label="Previous sample image">&#10094;</button>

                        <div class="detail-gallery-track">
                            <?php foreach ($selectedProduct['captureSlides'] as $index => $slideLabel): ?>
                                <div class="detail-gallery-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                                    <div class="detail-photo-placeholder detail-photo-variant-<?php echo ($index % 3) + 1; ?>">
                                        <span><?php echo htmlspecialchars($slideLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="detail-gallery-arrow detail-gallery-arrow-right" type="button" data-gallery-direction="next" aria-label="Next sample image">&#10095;</button>
                    </div>

                    <div class="product-information-footer" style="justify-content: space-between; align-items: center; padding: 0 1rem; margin-top: 1rem;">
                        <span style="font-size: 1.9rem; font-weight: 800;"><?php echo htmlspecialchars($selectedProduct['price'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <button
                            class="product-detail-cart-link btn btn-light btn-sm"
                            type="button"
                            data-add-cart
                            data-item-id="camera-<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-type="camera"
                            data-item-name="<?php echo htmlspecialchars($selectedProduct['brand'] . ' ' . $selectedProduct['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-copy="<?php echo htmlspecialchars($selectedProduct['tagline'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-image="<?php echo htmlspecialchars($assetBase . $selectedProduct['cameraImage'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-price="<?php echo htmlspecialchars($selectedProduct['price'], ENT_QUOTES, 'UTF-8'); ?>"
                            <?php if (!$isCustomerLoggedIn): ?>
                                data-login-url="<?php echo htmlspecialchars($addToCartLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php endif; ?>
                        >
                            ADD TO CART
                        </button>
                    </div>
                </article>

                <article class="product-calendar-card" data-available-month="<?php echo htmlspecialchars($selectedProduct['availability']['month'], ENT_QUOTES, 'UTF-8'); ?>" data-available-year="<?php echo htmlspecialchars($selectedProduct['availability']['year'], ENT_QUOTES, 'UTF-8'); ?>" data-available-days='<?php echo json_encode($selectedProduct['availability']['days']); ?>'>
                    <h2>Available Dates</h2>

                    <div class="calendar-toolbar">
                        <label>
                            <span class="sr-only">Month</span>
                            <select id="calendar-month-select">
                                <option value="0">January</option>
                                <option value="1">February</option>
                                <option value="2">March</option>
                                <option value="3">April</option>
                                <option value="4">May</option>
                                <option value="5">June</option>
                                <option value="6">July</option>
                                <option value="7">August</option>
                                <option value="8">September</option>
                                <option value="9">October</option>
                                <option value="10">November</option>
                                <option value="11">December</option>
                            </select>
                        </label>

                        <label>
                            <span class="sr-only">Year</span>
                            <select id="calendar-year-select">
                                <?php 
                                $currentYear = (int)date('Y');
                                if ($currentYear < 2026) $currentYear = 2026;
                                for ($y = $currentYear; $y <= $currentYear + 2; $y++): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>

                    <div class="calendar-grid" id="calendar-grid-container">
                        <!-- Rendered via JS -->
                    </div>
                </article>
            </section>

            <aside class="product-specs-card">
                <h2>Full Specifications</h2>

                <?php foreach ($selectedProduct['specs'] as $sectionTitle => $entries): ?>
                    <section class="product-specs-section">
                        <h3><?php echo htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8'); ?></h3>

                        <?php if (count($entries) === 1): ?>
                            <p><?php echo htmlspecialchars($entries[0], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($entries as $entry): ?>
                                    <li><?php echo htmlspecialchars($entry, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </aside>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260319-1"></script>
</body>
</html>
