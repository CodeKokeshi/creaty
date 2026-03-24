<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-products/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdminView = isset($isAdminView) && $isAdminView === true;
$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';
$productKey = $productKey ?? ($_GET['product'] ?? 'fuji-x-a3');

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$isAdminLoggedIn = isset($_SESSION['user_id']) && !isset($_SESSION['customer_id']);

if ($isAdminView && !$isAdminLoggedIn) {
    header('Location: ' . $assetBase . 'admin/');
    exit;
}

if (!$isAdminView && isset($_GET['add_to_cart'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-products/');
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

$cartCount = $isAdminView ? 0 : ($isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0);
$accountLabel = $isAdminView ? 'Admin' : ($isCustomerLoggedIn ? 'Account' : 'Sign In');
$accountSettingsPath = $assetBase . 'customer-account-settings/';
$logoutPath = $assetBase . 'customer-logout/';
$cartPath = $assetBase . 'customer-cart/';
$eventsPath = $assetBase . 'customer-events/';
$addToCartLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-products/?product=' . urlencode($productKey)));

require __DIR__ . '/config/products_repository.php';
$products = load_products_repository();

if (
    $isAdminView
    && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && isset($_POST['admin_edit_scope'])
) {
    $scope = trim((string) ($_POST['admin_edit_scope'] ?? ''));
    $postedKey = trim((string) ($_POST['productKey'] ?? ''));
    $result = 'saved=1';

    if ($postedKey !== '' && isset($products[$postedKey]) && is_array($products[$postedKey])) {
        $productToUpdate = $products[$postedKey];

        if ($scope === 'cover') {
            $coverImageDataUrl = trim((string) ($_POST['coverImageDataUrl'] ?? ''));
            $uploadedFile = $_FILES['coverImage'] ?? null;

            if (strpos($coverImageDataUrl, 'data:image/') === 0) {
                try {
                    $projectRoot = __DIR__;
                    $productToUpdate['cameraImage'] = save_product_image_from_data_url(
                        $coverImageDataUrl,
                        $productToUpdate['brand'] ?? 'Canon',
                        $productToUpdate['name'] ?? 'Product',
                        $projectRoot
                    );
                } catch (Throwable $error) {
                    $result = 'error=invalid-cover-image';
                }
            }

            if ($result === 'saved=1' && is_array($uploadedFile) && ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tmpPath = (string) ($uploadedFile['tmp_name'] ?? '');
                $originalName = (string) ($uploadedFile['name'] ?? 'cover.png');
                $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    $extension = 'png';
                }

                $projectRoot = __DIR__;
                $targetDirRelative = 'assets/cameras';
                $targetDirAbsolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

                if (is_dir($targetDirAbsolute) && is_uploaded_file($tmpPath)) {
                    $baseFilename = sanitize_product_filename(normalize_product_brand($productToUpdate['brand'] ?? 'Canon') . ' ' . ($productToUpdate['name'] ?? 'Product'));
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

                    if (move_uploaded_file($tmpPath, $targetAbsolutePath)) {
                        $productToUpdate['cameraImage'] = $targetRelativePath;
                    }
                }
            }
        }

        if ($scope === 'information') {
            $tagline = trim((string) ($_POST['tagline'] ?? ''));
            $priceValue = (float) ($_POST['price'] ?? ($productToUpdate['price'] ?? 0));
            $discountValue = (int) ($_POST['discountPercent'] ?? ($productToUpdate['discountPercent'] ?? 0));
            $discountValue = max(0, min(95, $discountValue));

            if ($priceValue >= 0) {
                $productToUpdate['price'] = number_format($priceValue, 2, '.', '');
            }

            $productToUpdate['discountPercent'] = $discountValue;

            if ($tagline !== '') {
                $productToUpdate['tagline'] = $tagline;
            }
        }

        if ($scope === 'specifications') {
            $brandValue = normalize_product_brand($_POST['brand'] ?? ($productToUpdate['brand'] ?? 'Canon'));
            $nameValue = trim((string) ($_POST['name'] ?? ($productToUpdate['name'] ?? '')));
            $imagingLines = normalize_lines_array($_POST['imagingSpecs'] ?? []);
            $videoLines = normalize_lines_array($_POST['videoSpecs'] ?? []);
            $physicalLines = normalize_lines_array($_POST['physicalSpecs'] ?? []);

            if ($nameValue !== '' && has_duplicate_product_display_name($products, $brandValue, $nameValue, $postedKey)) {
                $result = 'error=duplicate-name';
            } else {
                if ($nameValue !== '') {
                    $productToUpdate['name'] = $nameValue;
                }

                $productToUpdate['brand'] = $brandValue;

                $specs = is_array($productToUpdate['specs'] ?? null) ? $productToUpdate['specs'] : [];
                $specs['Brand'] = [normalize_product_brand($productToUpdate['brand'] ?? 'Canon')];

                if (count($imagingLines) > 0) {
                    $specs['Imaging and Performance'] = $imagingLines;
                }

                if (count($videoLines) > 0) {
                    $specs['Video'] = $videoLines;
                }

                if (count($physicalLines) > 0) {
                    $specs['Physical Specifications'] = $physicalLines;
                }

                $productToUpdate['specs'] = $specs;
            }
        }

        if ($result === 'saved=1') {
            $products[$postedKey] = $productToUpdate;
            save_products_repository($products);
        }
    }

    $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/products/?product=' . urlencode($postedKey));
    $separator = strpos($redirectTarget, '?') === false ? '?' : '&';
    header('Location: ' . $redirectTarget . $separator . $result);
    exit;
}

if (!is_array($products) || !$products) {
    $products = [];
}

if (!isset($products[$productKey])) {
    $keys = array_keys($products);
    $productKey = isset($keys[0]) ? $keys[0] : null;
}

if ($productKey === null || !isset($products[$productKey])) {
    http_response_code(500);
    echo 'No product data available.';
    exit;
}

$selectedProduct = $products[$productKey];
$selectedBrand = normalize_product_brand($selectedProduct['brand'] ?? 'Canon');
$selectedProductName = trim((string) ($selectedProduct['name'] ?? ''));
$selectedSpecs = is_array($selectedProduct['specs'] ?? null) ? $selectedProduct['specs'] : [];
$selectedImagingSpecs = is_array($selectedSpecs['Imaging and Performance'] ?? null) ? $selectedSpecs['Imaging and Performance'] : [];
$selectedVideoSpecs = is_array($selectedSpecs['Video'] ?? null) ? $selectedSpecs['Video'] : [];
$selectedPhysicalSpecs = is_array($selectedSpecs['Physical Specifications'] ?? null) ? $selectedSpecs['Physical Specifications'] : [];
$productListPath = $homePath . '#featured-products-title';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | <?php echo htmlspecialchars($selectedBrand . ' ' . $selectedProductName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260324-4">
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

            <a class="topbar-cart" href="<?php echo htmlspecialchars($isAdminView ? $homePath : ($assetBase . 'customer-cart/'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#">Message us</a>
            <?php if ($isAdminView): ?>
                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($homePath . '#featured-products-title', ENT_QUOTES, 'UTF-8'); ?>">Manage Featured Products</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($assetBase . 'admin/logout.php', ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            <?php elseif ($isCustomerLoggedIn): ?>
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
                    <?php if ($isAdminView): ?>
                        <button class="admin-pencil-chip" type="button" data-admin-open-cover-modal aria-label="Edit cover image">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                        </button>
                    <?php endif; ?>

                    <div class="product-device-placeholder">
                        <img
                            class="product-device-image"
                            src="<?php echo htmlspecialchars($assetBase . $selectedProduct['cameraImage'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars($selectedBrand . ' ' . $selectedProductName, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                </article>

                <section class="product-recommendations">
                    <h2>Recommendations</h2>

                    <div class="recommendation-list">
                        <?php foreach (($selectedProduct['recommendations'] ?? []) as $recommendedKey): ?>
                            <?php
                                if (!isset($products[$recommendedKey])) {
                                    continue;
                                }
                                $recommended = $products[$recommendedKey];
                                $recommendedBrand = normalize_product_brand($recommended['brand'] ?? 'Canon');
                                $recommendedName = trim((string) ($recommended['name'] ?? ''));
                            ?>
                            <a class="recommendation-card" href="?product=<?php echo urlencode((string) $recommendedKey); ?>" style="display: flex; flex-direction: column; gap: 0.8rem; text-decoration: none; color: inherit;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                                    <p style="margin: 0; font-size: 0.85rem; line-height: 1.4; flex: 1;"><?php echo htmlspecialchars((string) ($recommended['tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="recommendation-thumb" style="width: 80px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                        <img
                                            class="recommendation-thumb-image"
                                            src="<?php echo htmlspecialchars($assetBase . (string) ($recommended['cameraImage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($recommendedBrand . ' ' . $recommendedName, ENT_QUOTES, 'UTF-8'); ?>"
                                            style="width: 100%; height: auto; object-fit: contain; display: block;"
                                        >
                                    </div>
                                </div>
                                <span style="font-weight: 700; color: #dde531; font-size: 1.1rem;">&#8369; <?php echo htmlspecialchars((string) ($recommended['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                            <?php foreach (($selectedProduct['captureSlides'] ?? []) as $index => $slideLabel): ?>
                                <div class="detail-gallery-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" aria-hidden="<?php echo $index === 0 ? 'false' : 'true'; ?>">
                                    <div class="detail-photo-placeholder detail-photo-variant-<?php echo ($index % 3) + 1; ?>">
                                        <span><?php echo htmlspecialchars((string) $slideLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button class="detail-gallery-arrow detail-gallery-arrow-right" type="button" data-gallery-direction="next" aria-label="Next sample image">&#10095;</button>
                    </div>

                    <div class="admin-edit-swap" data-admin-edit-swap="information">
                        <div class="admin-static-view">
                            <div class="product-information-footer" style="justify-content: space-between; align-items: center; padding: 0 1rem; margin-top: 1rem;">
                                <span style="font-size: 1.9rem; font-weight: 800;">&#8369; <?php echo htmlspecialchars((string) ($selectedProduct['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($isAdminView): ?>
                                    <button class="admin-pencil-chip" type="button" data-admin-toggle-edit="information" aria-label="Edit information">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                                    </button>
                                <?php else: ?>
                                    <button
                                        class="product-detail-cart-link btn btn-light btn-sm"
                                        type="button"
                                        data-add-cart
                                        data-item-id="camera-<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-type="camera"
                                        data-item-name="<?php echo htmlspecialchars($selectedBrand . ' ' . $selectedProductName, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-copy="<?php echo htmlspecialchars((string) ($selectedProduct['tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-image="<?php echo htmlspecialchars($assetBase . (string) ($selectedProduct['cameraImage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-price="<?php echo htmlspecialchars((string) ($selectedProduct['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php if (!$isCustomerLoggedIn): ?>
                                            data-login-url="<?php echo htmlspecialchars($addToCartLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php endif; ?>
                                    >
                                        ADD TO CART
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($isAdminView): ?>
                            <form class="admin-edit-swap-panel" method="post" action="">
                                <input type="hidden" name="admin_edit_scope" value="information">
                                <input type="hidden" name="productKey" value="<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>">

                                <label for="admin-info-price">Price</label>
                                <input id="admin-info-price" type="number" min="0" step="0.01" name="price" value="<?php echo htmlspecialchars((string) ($selectedProduct['price'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>" required>

                                <label for="admin-info-discount">Discount Percentage</label>
                                <input id="admin-info-discount" type="number" min="0" max="95" step="1" name="discountPercent" value="<?php echo htmlspecialchars((string) ($selectedProduct['discountPercent'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" required>

                                <label for="admin-info-tagline">Tagline</label>
                                <textarea id="admin-info-tagline" name="tagline" rows="3" required><?php echo htmlspecialchars((string) ($selectedProduct['tagline'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>

                                <div class="admin-inline-edit-actions">
                                    <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-cancel-edit="information" aria-label="Cancel" title="Cancel">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                                    </button>
                                    <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save" title="Save">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="product-calendar-card" data-product-key="<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>">
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
                            <select id="calendar-year-select"></select>
                        </label>
                    </div>

                    <div class="calendar-grid" id="calendar-grid-container"></div>
                </article>
            </section>

            <aside class="product-specs-card">
                <div class="admin-specs-head">
                    <h2>Full Specifications</h2>
                    <?php if ($isAdminView): ?>
                        <button class="admin-pencil-chip" type="button" data-admin-toggle-edit="specifications" aria-label="Edit specifications">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                        </button>
                    <?php endif; ?>
                </div>

                <div class="admin-edit-swap" data-admin-edit-swap="specifications">
                    <div class="admin-static-view">
                        <section class="product-specs-section">
                            <h3>BRAND</h3>
                            <p><?php echo htmlspecialchars($selectedBrand, ENT_QUOTES, 'UTF-8'); ?></p>
                        </section>

                        <section class="product-specs-section">
                            <h3>PRODUCT</h3>
                            <p><?php echo htmlspecialchars($selectedBrand . ' ' . $selectedProductName, ENT_QUOTES, 'UTF-8'); ?></p>
                        </section>

                        <?php foreach ($selectedSpecs as $sectionTitle => $entries): ?>
                            <?php if (strtolower((string) $sectionTitle) === 'brand') { continue; } ?>
                            <section class="product-specs-section">
                                <h3><?php echo htmlspecialchars((string) $sectionTitle, ENT_QUOTES, 'UTF-8'); ?></h3>

                                <?php if (count((array) $entries) === 1): ?>
                                    <p><?php echo htmlspecialchars((string) $entries[0], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ((array) $entries as $entry): ?>
                                            <li><?php echo htmlspecialchars((string) $entry, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($isAdminView): ?>
                        <form class="admin-edit-swap-panel" method="post" action="">
                            <input type="hidden" name="admin_edit_scope" value="specifications">
                            <input type="hidden" name="productKey" value="<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>">

                            <label for="admin-spec-brand">Brand</label>
                            <select id="admin-spec-brand" name="brand" required>
                                <option value="canon"<?php echo strtolower($selectedBrand) === 'canon' ? ' selected' : ''; ?>>Canon</option>
                                <option value="fuji"<?php echo strtolower($selectedBrand) === 'fuji' ? ' selected' : ''; ?>>Fuji</option>
                                <option value="nikon"<?php echo strtolower($selectedBrand) === 'nikon' ? ' selected' : ''; ?>>Nikon</option>
                                <option value="sony"<?php echo strtolower($selectedBrand) === 'sony' ? ' selected' : ''; ?>>Sony</option>
                            </select>

                            <label for="admin-spec-name">Product Name</label>
                            <input id="admin-spec-name" type="text" name="name" value="<?php echo htmlspecialchars($selectedProductName, ENT_QUOTES, 'UTF-8'); ?>" required>

                            <label for="admin-imaging-specs">Imaging and Performance (one per line)</label>
                            <textarea id="admin-imaging-specs" name="imagingSpecs" rows="5" required><?php echo htmlspecialchars(implode("\n", $selectedImagingSpecs), ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <label for="admin-video-specs">Video (one per line)</label>
                            <textarea id="admin-video-specs" name="videoSpecs" rows="4" required><?php echo htmlspecialchars(implode("\n", $selectedVideoSpecs), ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <label for="admin-physical-specs">Physical Specifications (one per line)</label>
                            <textarea id="admin-physical-specs" name="physicalSpecs" rows="4" required><?php echo htmlspecialchars(implode("\n", $selectedPhysicalSpecs), ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <div class="admin-inline-edit-actions">
                                <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-cancel-edit="specifications" aria-label="Cancel" title="Cancel">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                                </button>
                                <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save" title="Save">
                                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </aside>
        </section>
    </main>

    <?php if ($isAdminView): ?>
        <div class="admin-edit-modal-backdrop" data-admin-cover-backdrop hidden>
            <section class="admin-edit-modal admin-cover-modal" role="dialog" aria-modal="true" aria-labelledby="admin-cover-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-cover-title">Edit Cover</h2>
                    <button class="admin-edit-close" type="button" data-admin-cover-close aria-label="Close edit window">&times;</button>
                </div>

                <form class="admin-edit-form" method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="admin_edit_scope" value="cover">
                    <input type="hidden" name="productKey" value="<?php echo htmlspecialchars($productKey, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="coverImageDataUrl" data-admin-cover-dataurl>

                    <div class="admin-edit-image-preview admin-cover-preview" data-admin-cover-preview>
                        <img src="<?php echo htmlspecialchars($assetBase . $selectedProduct['cameraImage'], ENT_QUOTES, 'UTF-8'); ?>" alt="Cover preview" data-admin-cover-preview-img draggable="false">
                        <div class="admin-crop-drag-badge" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/drag_pan.svg" alt="">
                            <span>Drag</span>
                        </div>
                    </div>

                    <input type="file" accept="image/png,image/jpeg,image/webp" name="coverImage" data-admin-cover-file hidden>

                    <div class="admin-crop-workspace" data-admin-cover-crop-workspace hidden>
                        <div class="admin-crop-controls">
                            <label class="admin-crop-zoom-label">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/zoom_in_out.svg" alt="" aria-hidden="true">
                                <span class="sr-only">Zoom</span>
                                <input type="range" min="1" max="3" step="0.01" value="1" data-admin-cover-zoom>
                            </label>
                        </div>

                        <div class="admin-crop-actions">
                            <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-cover-crop-cancel aria-label="Cancel" title="Cancel">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                            </button>
                            <button type="button" class="admin-icon-action admin-icon-action-save" data-admin-cover-crop-save aria-label="Save" title="Save">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                            </button>
                        </div>
                    </div>

                    <div class="admin-edit-actions" data-admin-cover-main-actions>
                        <button type="button" class="admin-icon-action admin-icon-action-browse" data-admin-cover-browse aria-label="Browse" title="Browse">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/folder_open.svg" alt="">
                        </button>
                        <button type="button" class="admin-icon-action admin-icon-action-edit" data-admin-cover-recrop aria-label="Edit" title="Edit">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/crop.svg" alt="">
                        </button>
                        <button type="button" class="admin-icon-action admin-icon-action-cancel" data-admin-cover-cancel aria-label="Cancel" title="Cancel">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cancel.svg" alt="">
                        </button>
                        <button type="submit" class="admin-icon-action admin-icon-action-save" aria-label="Save" title="Save">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/check.svg" alt="">
                        </button>
                    </div>
                </form>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($isAdminView && isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
        <div class="admin-inline-save-toast" role="status" aria-live="polite">Changes saved.</div>
    <?php endif; ?>

    <?php if ($isAdminView && isset($_GET['error']) && $_GET['error'] === 'duplicate-name'): ?>
        <div class="admin-inline-save-toast admin-inline-save-toast-error" role="alert" aria-live="assertive">Brand + product name already exists.</div>
    <?php endif; ?>

    <?php if ($isAdminView): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function setEditMode(scope, isEditing) {
                    var wraps = document.querySelectorAll('[data-admin-edit-swap]');

                    wraps.forEach(function (item) {
                        item.classList.remove('is-editing');
                    });

                    if (!isEditing) {
                        return;
                    }

                    var wrap = document.querySelector('[data-admin-edit-swap="' + scope + '"]');
                    if (wrap) {
                        wrap.classList.add('is-editing');
                    }
                }

                document.querySelectorAll('[data-admin-toggle-edit]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var scope = button.getAttribute('data-admin-toggle-edit');
                        setEditMode(scope, true);
                    });
                });

                document.querySelectorAll('[data-admin-cancel-edit]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var scope = button.getAttribute('data-admin-cancel-edit');
                        setEditMode(scope, false);
                    });
                });

                var coverBackdrop = document.querySelector('[data-admin-cover-backdrop]');
                var coverOpenButton = document.querySelector('[data-admin-open-cover-modal]');
                var coverCloseButton = document.querySelector('[data-admin-cover-close]');
                var coverCancelButton = document.querySelector('[data-admin-cover-cancel]');
                var coverBrowseButton = document.querySelector('[data-admin-cover-browse]');
                var coverRecropButton = document.querySelector('[data-admin-cover-recrop]');
                var coverFileInput = document.querySelector('[data-admin-cover-file]');
                var coverPreviewImage = document.querySelector('[data-admin-cover-preview-img]');
                var coverPreviewWrap = document.querySelector('[data-admin-cover-preview]');
                var coverCropWorkspace = document.querySelector('[data-admin-cover-crop-workspace]');
                var coverMainActions = document.querySelector('[data-admin-cover-main-actions]');
                var coverCropCancelButton = document.querySelector('[data-admin-cover-crop-cancel]');
                var coverCropSaveButton = document.querySelector('[data-admin-cover-crop-save]');
                var coverZoomInput = document.querySelector('[data-admin-cover-zoom]');
                var coverDataUrlInput = document.querySelector('[data-admin-cover-dataurl]');

                var coverCropState = {
                    zoom: 1,
                    offsetX: 0,
                    offsetY: 0,
                    isCropping: false,
                    isDragging: false,
                    dragPointerId: null,
                    dragStartClientX: 0,
                    dragStartClientY: 0,
                    dragStartOffsetX: 0,
                    dragStartOffsetY: 0,
                    previewBeforeCrop: ''
                };
                var coverAspect = {
                    width: 5,
                    height: 4
                };

                function clampCoverOffsets(nextX, nextY) {
                    if (!coverPreviewWrap) {
                        return { x: nextX, y: nextY };
                    }

                    var rect = coverPreviewWrap.getBoundingClientRect();
                    var zoom = Math.max(1, coverCropState.zoom);
                    var maxShiftX = Math.max(0, ((rect.width * zoom) - rect.width) / 2);
                    var maxShiftY = Math.max(0, ((rect.height * zoom) - rect.height) / 2);

                    return {
                        x: Math.min(maxShiftX, Math.max(-maxShiftX, nextX)),
                        y: Math.min(maxShiftY, Math.max(-maxShiftY, nextY))
                    };
                }

                function syncCoverPreviewTransform() {
                    if (!coverPreviewWrap) {
                        return;
                    }

                    coverPreviewWrap.style.setProperty('--admin-crop-zoom', String(coverCropState.zoom));
                    coverPreviewWrap.style.setProperty('--admin-crop-x', String(coverCropState.offsetX) + 'px');
                    coverPreviewWrap.style.setProperty('--admin-crop-y', String(coverCropState.offsetY) + 'px');
                    coverPreviewWrap.classList.toggle('is-crop-active', coverCropState.isCropping);
                }

                function setCoverCropWorkspaceVisible(isVisible) {
                    coverCropState.isCropping = isVisible;

                    if (coverCropWorkspace) {
                        coverCropWorkspace.hidden = !isVisible;
                    }

                    if (coverMainActions) {
                        coverMainActions.hidden = isVisible;
                    }

                    syncCoverPreviewTransform();
                }

                function resetCoverCropState() {
                    coverCropState.zoom = 1;
                    coverCropState.offsetX = 0;
                    coverCropState.offsetY = 0;
                    coverCropState.isDragging = false;
                    coverCropState.dragPointerId = null;

                    if (coverZoomInput) {
                        coverZoomInput.value = '1';
                    }

                    syncCoverPreviewTransform();
                }

                function buildCoverCropDataUrl() {
                    if (!coverPreviewImage || !coverPreviewImage.src || !coverPreviewImage.naturalWidth || !coverPreviewImage.naturalHeight) {
                        return null;
                    }

                    var outputWidth = 900;
                    var outputHeight = Math.round(outputWidth * (coverAspect.height / coverAspect.width));
                    var canvas = document.createElement('canvas');
                    canvas.width = outputWidth;
                    canvas.height = outputHeight;

                    var context = canvas.getContext('2d');
                    if (!context) {
                        return null;
                    }

                    var zoomValue = Math.max(1, Number(coverCropState.zoom || 1));
                    var scaleToCover = Math.max(outputWidth / coverPreviewImage.naturalWidth, outputHeight / coverPreviewImage.naturalHeight);
                    var scale = scaleToCover * zoomValue;
                    var drawWidth = coverPreviewImage.naturalWidth * scale;
                    var drawHeight = coverPreviewImage.naturalHeight * scale;
                    var drawX = ((outputWidth - drawWidth) / 2) + coverCropState.offsetX;
                    var drawY = ((outputHeight - drawHeight) / 2) + coverCropState.offsetY;

                    context.clearRect(0, 0, outputWidth, outputHeight);
                    context.drawImage(coverPreviewImage, drawX, drawY, drawWidth, drawHeight);

                    return canvas.toDataURL('image/png');
                }

                function closeCoverModal() {
                    if (!coverBackdrop) {
                        return;
                    }

                    setCoverCropWorkspaceVisible(false);
                    resetCoverCropState();
                    coverBackdrop.hidden = true;
                    document.body.classList.remove('admin-modal-open');
                }

                if (coverOpenButton && coverBackdrop) {
                    coverOpenButton.addEventListener('click', function () {
                        coverBackdrop.hidden = false;
                        document.body.classList.add('admin-modal-open');
                    });
                }

                if (coverCloseButton) {
                    coverCloseButton.addEventListener('click', closeCoverModal);
                }

                if (coverCancelButton) {
                    coverCancelButton.addEventListener('click', closeCoverModal);
                }

                if (coverBackdrop) {
                    coverBackdrop.addEventListener('click', function (event) {
                        if (event.target === coverBackdrop) {
                            closeCoverModal();
                        }
                    });
                }

                if (coverBrowseButton && coverFileInput) {
                    coverBrowseButton.addEventListener('click', function () {
                        coverFileInput.click();
                    });
                }

                if (coverRecropButton && coverPreviewImage) {
                    coverRecropButton.addEventListener('click', function () {
                        if (!coverPreviewImage.src) {
                            return;
                        }

                        coverCropState.previewBeforeCrop = coverPreviewImage.src;
                        resetCoverCropState();
                        setCoverCropWorkspaceVisible(true);
                    });
                }

                if (coverZoomInput) {
                    coverZoomInput.addEventListener('input', function () {
                        coverCropState.zoom = Number.parseFloat(coverZoomInput.value) || 1;
                        var clamped = clampCoverOffsets(coverCropState.offsetX, coverCropState.offsetY);
                        coverCropState.offsetX = clamped.x;
                        coverCropState.offsetY = clamped.y;
                        syncCoverPreviewTransform();
                    });
                }

                if (coverPreviewWrap) {
                    coverPreviewWrap.addEventListener('wheel', function (event) {
                        if (!coverCropState.isCropping || !coverZoomInput) {
                            return;
                        }

                        event.preventDefault();

                        var minZoom = Number.parseFloat(coverZoomInput.min || '1');
                        var maxZoom = Number.parseFloat(coverZoomInput.max || '3');
                        var stepZoom = Number.parseFloat(coverZoomInput.step || '0.01');
                        var direction = event.deltaY < 0 ? 1 : -1;
                        var nextZoom = coverCropState.zoom + (direction * (stepZoom * 5));

                        nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
                        nextZoom = Math.round(nextZoom * 100) / 100;

                        coverCropState.zoom = nextZoom;
                        coverZoomInput.value = String(nextZoom);

                        var clamped = clampCoverOffsets(coverCropState.offsetX, coverCropState.offsetY);
                        coverCropState.offsetX = clamped.x;
                        coverCropState.offsetY = clamped.y;
                        syncCoverPreviewTransform();
                    }, { passive: false });

                    coverPreviewWrap.addEventListener('pointerdown', function (event) {
                        if (!coverCropState.isCropping || event.button !== 0) {
                            return;
                        }

                        event.preventDefault();

                        coverCropState.isDragging = true;
                        coverCropState.dragPointerId = event.pointerId;
                        coverCropState.dragStartClientX = event.clientX;
                        coverCropState.dragStartClientY = event.clientY;
                        coverCropState.dragStartOffsetX = coverCropState.offsetX;
                        coverCropState.dragStartOffsetY = coverCropState.offsetY;
                        coverPreviewWrap.setPointerCapture(event.pointerId);
                    });

                    coverPreviewWrap.addEventListener('pointermove', function (event) {
                        if (!coverCropState.isCropping || !coverCropState.isDragging || coverCropState.dragPointerId !== event.pointerId) {
                            return;
                        }

                        var nextX = coverCropState.dragStartOffsetX + (event.clientX - coverCropState.dragStartClientX);
                        var nextY = coverCropState.dragStartOffsetY + (event.clientY - coverCropState.dragStartClientY);
                        var clamped = clampCoverOffsets(nextX, nextY);

                        coverCropState.offsetX = clamped.x;
                        coverCropState.offsetY = clamped.y;
                        syncCoverPreviewTransform();
                    });

                    function stopCoverDrag(event) {
                        if (!coverCropState.isDragging || coverCropState.dragPointerId !== event.pointerId) {
                            return;
                        }

                        coverCropState.isDragging = false;
                        coverCropState.dragPointerId = null;
                        coverPreviewWrap.releasePointerCapture(event.pointerId);
                    }

                    coverPreviewWrap.addEventListener('pointerup', stopCoverDrag);
                    coverPreviewWrap.addEventListener('pointercancel', stopCoverDrag);
                }

                if (coverCropCancelButton && coverPreviewImage) {
                    coverCropCancelButton.addEventListener('click', function () {
                        coverPreviewImage.src = coverCropState.previewBeforeCrop || coverPreviewImage.src;
                        setCoverCropWorkspaceVisible(false);
                        resetCoverCropState();

                        if (coverDataUrlInput) {
                            coverDataUrlInput.value = coverPreviewImage.src.indexOf('data:image/') === 0 ? coverPreviewImage.src : '';
                        }
                    });
                }

                if (coverCropSaveButton && coverPreviewImage) {
                    coverCropSaveButton.addEventListener('click', function () {
                        var croppedDataUrl = buildCoverCropDataUrl();
                        if (!croppedDataUrl) {
                            return;
                        }

                        coverPreviewImage.src = croppedDataUrl;
                        coverCropState.previewBeforeCrop = croppedDataUrl;

                        if (coverDataUrlInput) {
                            coverDataUrlInput.value = croppedDataUrl;
                        }

                        setCoverCropWorkspaceVisible(false);
                        resetCoverCropState();
                    });
                }

                if (coverFileInput && coverPreviewImage) {
                    coverFileInput.addEventListener('change', function () {
                        var file = coverFileInput.files && coverFileInput.files[0] ? coverFileInput.files[0] : null;
                        if (!file) {
                            return;
                        }

                        coverCropState.previewBeforeCrop = coverPreviewImage.src || '';
                        setCoverCropWorkspaceVisible(false);
                        resetCoverCropState();

                        var reader = new FileReader();
                        reader.onload = function (event) {
                            coverPreviewImage.src = String(event.target && event.target.result ? event.target.result : coverPreviewImage.src);

                            if (coverDataUrlInput) {
                                coverDataUrlInput.value = '';
                            }

                            coverCropState.previewBeforeCrop = coverPreviewImage.src;
                            setCoverCropWorkspaceVisible(true);
                        };
                        reader.readAsDataURL(file);
                    });
                }
            });
        </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260324-4"></script>
</body>
</html>
