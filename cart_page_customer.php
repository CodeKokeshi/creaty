<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: cart/');
    exit;
}

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'login/';
$productListPath = $homePath . '#featured-products-title';

$cartItems = [
    [
        'name' => 'Fuji X A3',
        'copy' => 'APS-C mirrorless camera 24.2MP CMOS sensor',
        'image' => 'assets/cameras/Fujifilm%20XA-3.png',
        'price' => 'P 450.00',
        'qty' => '1',
        'days' => '1'
    ],
    [
        'name' => 'Sony ZV E10',
        'copy' => '24.2MP APS-C sensor with 4K 30p video capture',
        'image' => 'assets/cameras/Sony%20ZV-E10.png',
        'price' => 'P 899.00',
        'qty' => '1',
        'days' => '1'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | Cart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260312-5">
</head>
<body class="cart-page">
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
                <span class="cart-count">2</span>
            </a>

            <a class="topbar-link" href="#">Message us</a>
            <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>">Account</a>
        </div>

        <nav class="section-nav section-nav-disabled" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
            <span class="section-nav-section is-disabled" aria-disabled="true">EVENTS</span>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
        </nav>
    </header>

    <main class="cart-shell">
        <section class="cart-layout reveal">
            <div class="cart-main-column">
                <div class="cart-header-row">
                    <a class="catalog-back" href="<?php echo htmlspecialchars($productListPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to featured products">
                        <span class="catalog-back-icon" aria-hidden="true"></span>
                    </a>
                    <h1>CART</h1>
                </div>

                <div class="cart-items-panel">
                    <?php foreach ($cartItems as $item): ?>
                        <article class="cart-item-card">
                            <div class="cart-item-copy">
                                <h2><?php echo htmlspecialchars(strtoupper($item['name']), ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p><?php echo htmlspecialchars($item['copy'], ENT_QUOTES, 'UTF-8'); ?></p>

                                <label class="cart-mini-field">
                                    <span>Qty</span>
                                    <input type="text" value="<?php echo htmlspecialchars($item['qty'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </label>
                            </div>

                            <div class="cart-item-thumb">
                                <img
                                    class="cart-item-thumb-image"
                                    src="<?php echo htmlspecialchars($assetBase . $item['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                >
                            </div>

                            <div class="cart-item-pricebox">
                                <label class="cart-mini-field">
                                    <span>Days</span>
                                    <input type="text" value="<?php echo htmlspecialchars($item['days'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                </label>

                                <p class="cart-item-price-label">Price:</p>
                                <strong><?php echo htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>

                            <button class="cart-remove-button" type="button" aria-label="Remove item">&#10005;</button>
                        </article>
                    <?php endforeach; ?>
                </div>

                <section class="cart-terms-block">
                    <h2>T&amp;C</h2>
                    <p>1. Acceptance of Terms</p>
                    <p>By creating a reservation through the CREATY system, you enter into a booking agreement with The Nifty Fifty Camera Rentals.</p>
                    <p>2. Definitions</p>
                    <p>Equipment: Camera gear listed for rental.</p>
                    <p>Reservation: A confirmed booking.</p>
                    <p>Rental Period: 22 hours of usage time begins when equipment is received.</p>
                    <p>Grace Period: 2-hour window to initiate the return process.</p>
                    <p>Late Period: Time after the grace period until equipment is returned.</p>
                </section>
            </div>

            <aside class="cart-sidebar">
                <section class="cart-booking-card">
                    <div class="cart-booking-group">
                        <h2>Receiving Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <select>
                                <option selected>Dec 3, 2025</option>
                            </select>
                            <select>
                                <option selected>10:00 AM</option>
                            </select>
                        </div>

                        <label class="cart-form-line">
                            <span>Place:</span>
                            <select>
                                <option selected>emart (Carmona)</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-booking-group">
                        <h2>Returning Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <select>
                                <option selected>Dec 4, 2025</option>
                            </select>
                            <select>
                                <option selected>8:00 AM</option>
                            </select>
                        </div>

                        <p class="cart-late-note">Late returns = P50/hour</p>

                        <label class="cart-form-line">
                            <span>Courier:</span>
                            <select>
                                <option selected>Lalamove</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-methods-row">
                        <section class="cart-method-card">
                            <h3>Receiving Method:</h3>
                            <ul>
                                <li>PICK-UP</li>
                                <li class="is-selected">MEET-UP</li>
                                <li>DELIVERY</li>
                            </ul>
                        </section>

                        <section class="cart-method-card">
                            <h3>Returning Method:</h3>
                            <ul>
                                <li>PICK-UP</li>
                                <li>MEET-UP</li>
                                <li class="is-selected">DELIVERY</li>
                            </ul>
                        </section>
                    </div>

                    <div class="cart-valid-id-block">
                        <p>Valid Id (PhilSys, Tin Drivers license, Etc.)</p>
                        <div class="cart-upload-row">
                            <span>...58_33_Pro.jpg</span>
                            <button type="button" aria-label="Upload valid ID">&#8682;</button>
                        </div>
                        <p>Holding the Valid id near the face</p>
                    </div>

                    <div class="cart-summary-card">
                        <h3>TOTAL:</h3>
                        <strong>P 1349.00</strong>
                    </div>

                    <select class="cart-payment-select">
                        <option selected>Payment Method</option>
                    </select>

                    <button class="cart-confirm-button" type="button">CONFIRM BOOKING</button>

                    <p class="cart-booking-note">Pay within an hour after confirming your booking. (For delivery only)</p>
                </section>
            </aside>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260312-4"></script>
</body>
</html>