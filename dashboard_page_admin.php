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

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase);
    exit;
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . $assetBase);
    exit;
}

$username = $_SESSION['username'] ?? 'admin';
$cartCount = 0;
$accountLabel = 'Admin';
$adminHomePath = $routeBase . 'dashboard/';
$logoutPath = $routeBase . 'logout.php';

require __DIR__ . '/config/products_repository.php';
$products = load_products_repository();

if (!is_array($products)) {
    $products = [];
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
    <title>Admin Home | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css">
</head>
<body class="home-page-customer">
    <header class="site-header">
        <div class="topbar topbar-admin">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search products, events, or services">
            </form>

            <div class="dropdown topbar-account-menu">
                <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                    <li><a class="dropdown-item" href="#">Manage Featured Products</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($assetBase . 'archive/', ENT_QUOTES, 'UTF-8'); ?>">Archived</a></li>
                    <li><a class="dropdown-item" href="#">Manage Discounts</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                </ul>
            </div>
        </div>

        <nav class="section-nav section-nav-interactive section-nav-admin" aria-label="Catalog filters" data-admin-nav data-admin-dashboard-nav>
            <div class="section-nav-item section-nav-item-filter admin-nav-primary" data-admin-nav-item="primary">
                <button class="section-nav-filter filter-toggle" type="button" aria-expanded="false" aria-controls="brands-filter-panel">
                    BRANDS
                </button>

                <div class="filter-panel filter-panel-brands" id="brands-filter-panel" hidden>
                    <button class="filter-option is-selected" type="button" data-filter-group="brand" data-filter-value="all">ALL BRANDS</button>
                    <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="fuji">FUJI</button>
                    <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="sony">SONY</button>
                    <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="canon">CANON</button>
                    <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="nikon">NIKON</button>
                </div>
            </div>

            <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">EVENTS</a>

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

            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="equipments" hidden>EQUIPMENTS</button>
            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="bookings" hidden>BOOKINGS</button>
            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="reports" hidden>REPORTS</button>
            <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="users" hidden>USERS</button>

            <button class="section-nav-swap" type="button" data-admin-nav-swap aria-pressed="false" aria-label="Swap admin navigation" title="Show management bar">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/swap_horizontal_arrows.svg" alt="" aria-hidden="true">
            </button>
        </nav>
    </header>

    <main class="landing-shell">
        <section class="admin-equipments-shell" data-admin-dashboard-panel="equipments" hidden>
            <div class="admin-equipments-table-wrap" role="region" aria-label="Equipments list">
                <table class="admin-equipments-table">
                    <thead>
                        <tr>
                            <th scope="col">UNIT-ID</th>
                            <th scope="col">MODEL</th>
                            <th scope="col">TIMES USED (last 30 days)</th>
                            <th scope="col">STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CAN1200D-01</td>
                            <td>CANON_1200D</td>
                            <td>12</td>
                            <td><span class="admin-equipments-status">AVAILABLE</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

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
                        $brandValue = strtolower($brandLabel);
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
                        <select id="admin-edit-brand" data-admin-edit-brand required>
                            <option value="canon">Canon</option>
                            <option value="fuji">Fuji</option>
                            <option value="nikon">Nikon</option>
                            <option value="sony">Sony</option>
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
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260326-2"></script>
</body>
</html>