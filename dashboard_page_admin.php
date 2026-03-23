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
        <div class="topbar">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <a class="topbar-link topbar-help" href="#">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/help_icon.svg" alt="">
                <span>Help</span>
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search products, events, or services">
            </form>

            <a class="topbar-cart" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#">Message us</a>

            <div class="dropdown topbar-account-menu">
                <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                    <li><a class="dropdown-item" href="#">Manage Featured Products</a></li>
                    <li><a class="dropdown-item" href="#">Manage Discounts</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                </ul>
            </div>
        </div>

        <nav class="section-nav section-nav-interactive" aria-label="Catalog filters">
            <div class="section-nav-item section-nav-item-filter">
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

            <a class="section-nav-section" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">EVENTS</a>

            <div class="section-nav-item section-nav-item-filter">
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
        </nav>
    </header>

    <main class="landing-shell">
        <section class="promo-banner reveal" aria-label="Promo carousel">
            <button class="promo-arrow promo-arrow-left" type="button" aria-label="Previous promo">&#10094;</button>

            <div class="promo-carousel" aria-live="polite">
                <div class="promo-slide promo-slide-one is-active">
                    <img class="promo-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/promo_images/0001.png" alt="Promo of the week for Fuji X-A3 with 30 percent off">
                </div>

                <div class="promo-slide promo-slide-two">
                    <img class="promo-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/promo_images/0002.png" alt="Promo of the week for Canon 700D with 50 percent off">
                </div>

                <div class="promo-slide promo-slide-three">
                    <img class="promo-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/promo_images/0003.png" alt="Promo of the week for Canon 1200D with 20 percent off">
                </div>
            </div>

            <button class="promo-arrow promo-arrow-right" type="button" aria-label="Next promo">&#10095;</button>
        </section>

        <section class="landing-section reveal" aria-labelledby="how-it-works-title">
            <h2 class="landing-title" id="how-it-works-title">HOW IT WORKS</h2>

            <div class="steps-grid">
                <article class="step-card">
                    <img class="step-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/how_it_works/1.png" alt="Step 1: Pick your camera or service">
                </article>

                <article class="step-card">
                    <img class="step-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/how_it_works/2.png" alt="Step 2: Select your preferred method">
                </article>

                <article class="step-card">
                    <img class="step-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/how_it_works/3.png" alt="Step 3: Confirm your order">
                </article>

                <article class="step-card">
                    <img class="step-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/how_it_works/4.png" alt="Step 4: Wait for order details">
                </article>
            </div>
        </section>

        <section class="landing-section reveal" aria-labelledby="featured-products-title">
            <h2 class="landing-title" id="featured-products-title">FEATURED PRODUCTS</h2>

            <div class="product-grid">
                <article class="product-card product-card-highlight" data-brand="canon" data-month="january" data-day="01" data-year="2026">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Canon 700D featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Canon 700D from featured">&times;</button>
                    <div class="product-ribbon">PROMO 50% OFF!</div>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Canon 700D product page">
                        <div class="product-visual product-visual-canon700d">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Canon%20700D.png" alt="Canon 700D">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Canon 700D</a></h3>
                        <p>18MP APS-C CMOS sensor</p>
                        <p>1080p Full HD video recording at up to 30 fps</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #dde531;">
                            <span style="color: #a1a1aa; text-decoration: line-through; font-size: 0.95rem; font-weight: 600; margin-right: 0.45rem;">&#8369; 800.00</span>
                            <span>&#8369; 400.00</span>
                        </p>
                    </div>
                </article>

                <article class="product-card product-card-highlight" data-brand="canon" data-month="march" data-day="12" data-year="2026">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Canon 1200D featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Canon 1200D from featured">&times;</button>
                    <div class="product-ribbon">PROMO 20% OFF!</div>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Canon 1200D product page">
                        <div class="product-visual product-visual-canon1200d">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Canon%201200D.png" alt="Canon 1200D">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Canon 1200D</a></h3>
                        <p>18-megapixel APS-C CMOS sensor</p>
                        <p>Full HD 1080p video recording</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #dde531;">
                            <span style="color: #a1a1aa; text-decoration: line-through; font-size: 0.95rem; font-weight: 600; margin-right: 0.45rem;">&#8369; 450.00</span>
                            <span>&#8369; 360.00</span>
                        </p>
                    </div>
                </article>

                <article class="product-card product-card-highlight" data-brand="fuji" data-month="march" data-day="12" data-year="2027">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Fuji X A3 featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Fuji X A3 from featured">&times;</button>
                    <div class="product-ribbon">PROMO 30% OFF!</div>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Fuji X A3 product page">
                        <div class="product-visual product-visual-fuji">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Fujifilm%20XA-3.png" alt="Fujifilm XA-3">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Fuji X A3</a></h3>
                        <p>APS-C mirrorless camera</p>
                        <p>24.2MP CMOS sensor, an ISO range of 200-6400</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #dde531;">
                            <span style="color: #a1a1aa; text-decoration: line-through; font-size: 0.95rem; font-weight: 600; margin-right: 0.45rem;">&#8369; 450.00</span>
                            <span>&#8369; 315.00</span>
                        </p>
                    </div>
                </article>

                <article class="product-card" data-brand="canon" data-month="june" data-day="23" data-year="2028">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Canon 4000D featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Canon 4000D from featured">&times;</button>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Canon 4000D product page">
                        <div class="product-visual product-visual-canon4000d">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Canon%20400D.png" alt="Canon 4000D">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Canon 4000D</a></h3>
                        <p>18MP APS-C CMOS sensor</p>
                        <p>ISO range of 100-6400 (expandable to 12800)</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #f4f4f4;">&#8369; 600.00</p>
                    </div>
                </article>

                <article class="product-card" data-brand="nikon" data-month="january" data-day="12" data-year="2026">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Nikon D60 featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Nikon D60 from featured">&times;</button>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Nikon D60 product page">
                        <div class="product-visual product-visual-nikon">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Nikon%20D60.png" alt="Nikon D60">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Nikon D60</a></h3>
                        <p>10.2MP CCD sensor</p>
                        <p>ISO range of 100-1600 (expandable to 3200)</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #f4f4f4;">&#8369; 250.00</p>
                    </div>
                </article>

                <article class="product-card" data-brand="sony" data-month="march" data-day="23" data-year="2026">
                    <button class="product-card-admin-edit" type="button" data-admin-edit-featured aria-label="Edit Sony ZV E10 featured details">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                    </button>
                    <button class="product-card-admin-remove" type="button" data-admin-remove-featured aria-label="Remove Sony ZV E10 from featured">&times;</button>
                    <a class="product-visual-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title" aria-label="View Sony ZV E10 product page">
                        <div class="product-visual product-visual-sony">
                            <img class="product-visual-image" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/cameras/Sony%20ZV-E10.png" alt="Sony ZV-E10">
                        </div>
                    </a>
                    <div class="product-copy">
                        <h3><a class="product-title-link" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>#featured-products-title">Sony ZV E10</a></h3>
                        <p>24.2MP APS-C sensor</p>
                        <p>4K 30p video and Full HD 120p slow-motion</p>
                        <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #f4f4f4;">&#8369; 899.00</p>
                    </div>
                </article>

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

    <div class="admin-edit-modal-backdrop" data-admin-edit-backdrop hidden>
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
                        </div>

                        <input type="file" accept="image/*" data-admin-edit-file hidden>

                        <div class="admin-edit-image-actions">
                            <button type="button" class="admin-edit-secondary" data-admin-edit-browse>Browse Image</button>
                            <button type="button" class="admin-edit-secondary" data-admin-edit-recrop>Edit Crop</button>
                        </div>

                        <div class="admin-crop-workspace" data-admin-crop-workspace hidden>
                            <p class="admin-crop-hint">Drag the image inside the square and use zoom.</p>

                            <div class="admin-crop-controls">
                            <label>Zoom
                                <input type="range" min="1" max="3" step="0.01" value="1" data-admin-edit-zoom>
                            </label>
                            </div>

                            <div class="admin-crop-actions">
                                <button type="button" class="admin-edit-secondary" data-admin-edit-crop-cancel>Cancel Crop</button>
                                <button type="button" class="admin-edit-primary" data-admin-edit-crop-save>Save Crop</button>
                            </div>
                        </div>
                    </div>

                    <div class="admin-edit-fields-column">
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

                <div class="admin-edit-actions">
                    <button type="button" class="admin-edit-secondary" data-admin-edit-cancel>Cancel</button>
                    <button type="submit" class="admin-edit-primary">Save Changes</button>
                </div>
            </form>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260319-6"></script>
</body>
</html>