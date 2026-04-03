<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-cart/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';
$productListPath = $homePath . '#featured-products-title';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$cartCount = $isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0;
$accountLabel = $isCustomerLoggedIn ? 'Account' : 'Sign In';
$accountSettingsPath = $assetBase . 'customer-account-settings/';
$logoutPath = $assetBase . 'customer-logout/';
$cartPath = $assetBase . 'customer-cart/';
$eventsPath = $assetBase . 'customer-events/';

require_once __DIR__ . '/config/products_repository.php';
require_once __DIR__ . '/config/event_packages_repository.php';

$productsRepository = load_products_repository();
$eventPackagesRepository = load_event_packages_repository();
$availableCartItemIds = [];

if (is_array($productsRepository)) {
    foreach ($productsRepository as $productKey => $productRecord) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($productRecord)) {
            continue;
        }

        $availableCartItemIds[] = 'camera-' . trim($productKey);
    }
}

if (is_array($eventPackagesRepository)) {
    foreach ($eventPackagesRepository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord)) {
            continue;
        }

        if (!empty($packageRecord['archived'])) {
            continue;
        }

        $availableCartItemIds[] = 'event-' . trim($packageKey);
    }
}

$availableCartItemIds = array_values(array_unique($availableCartItemIds));

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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260319-3">
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

            <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>customer-cart/" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#" data-message-us-open>Message us</a>
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

    <main class="cart-shell">
        <section class="cart-layout reveal">
            <div class="cart-main-column">
                <div class="cart-header-row">
                    <a class="catalog-back" href="<?php echo htmlspecialchars($productListPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to featured products">
                        <span class="catalog-back-icon" aria-hidden="true"></span>
                    </a>
                    <h1>CART</h1>
                </div>

                <div class="cart-items-panel" data-cart-items-panel>
                    <p class="cart-items-empty" data-cart-empty-message>Your cart is empty. Add event packages or camera rentals to continue.</p>
                </div>

                <section class="cart-terms-block">
                    <div class="cart-terms-header">
                        <h2>Terms and Conditions</h2>
                        <button
                            class="cart-terms-toggle"
                            type="button"
                            data-terms-toggle
                            data-label-show="Show Full Terms and Conditions"
                            data-label-hide="Hide Full Terms and Conditions"
                            aria-expanded="false"
                            aria-controls="cart-terms-content"
                        >
                            Show Full Terms and Conditions
                        </button>
                    </div>

                    <p class="cart-terms-intro">Please review and accept before confirming your reservation.</p>

                    <div class="cart-terms-content" id="cart-terms-content" hidden>
                        <article class="cart-terms-markdown" aria-label="Full Terms and Conditions">
                            <section class="cart-terms-highlights" aria-label="Key rental rules">
                                <article class="cart-terms-highlight-card">
                                    <p class="cart-terms-highlight-label">Rental Window</p>
                                    <strong>22 Hours</strong>
                                </article>
                                <article class="cart-terms-highlight-card">
                                    <p class="cart-terms-highlight-label">Grace Period</p>
                                    <strong>2 Hours</strong>
                                </article>
                                <article class="cart-terms-highlight-card is-warning">
                                    <p class="cart-terms-highlight-label">Late Fee</p>
                                    <strong>&#8369;50 / Hour</strong>
                                </article>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">1</span>
                                    <h3>Acceptance of Terms</h3>
                                </header>
                                <p>By creating a reservation through the CREATY system, you (&ldquo;Customer&rdquo;) enter into a legally binding contract with <strong>Nifty Fifty Camera Rentals</strong> and agree to all terms below.</p>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">2</span>
                                    <h3>Definitions</h3>
                                </header>
                                <div class="cart-terms-bullet-cards" role="list" aria-label="Definitions list">
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Equipment</strong></p>
                                        <p>Camera gear listed for rental.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Reservation</strong></p>
                                        <p>A confirmed booking.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Rental Period</strong></p>
                                        <p>22 hours of usage time begins when equipment is received.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Grace Period</strong></p>
                                        <p>2-hour window to initiate the return process.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Late Period</strong></p>
                                        <p>Time after the Grace Period until equipment is returned.</p>
                                    </article>
                                </div>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">3</span>
                                    <h3>Reservation and Equipment Assignment</h3>
                                </header>
                                <div class="cart-terms-list-cards" role="list" aria-label="Reservation and assignment rules">
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>Reservations are requests until confirmed by Nifty Fifty staff via the system.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p><strong>Equipment assignment is fully automated</strong> by the CREATY system based on availability, event suitability, and fair usage rotation. Staff validate but do not manually assign gear.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>Cancellations must be made via official channels; late cancellations may incur a fee.</p>
                                    </article>
                                </div>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">4</span>
                                    <h3>Claiming Equipment: Methods and Requirements</h3>
                                </header>
                                <p>You must choose one claiming method:</p>
                                <div class="cart-terms-method-grid" role="list" aria-label="Claiming methods">
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Pick-up</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Pick-up requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Collect at Nifty Fifty&#39;s location during business hours with valid ID.</p>
                                            </article>
                                        </div>
                                    </article>
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Meet-up</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Meet-up requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Time and location require prior staff confirmation. Being late may forfeit the reservation.</p>
                                            </article>
                                        </div>
                                    </article>
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Delivery</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Delivery requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p><strong>Mandatory Verification:</strong> You must upload (a) a clear photo of a valid government ID and (b) a clear photo of yourself holding that ID.</p>
                                            </article>
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p><strong>Delivery Fees:</strong> All delivery costs are borne by the Customer.</p>
                                            </article>
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p><strong>Liability:</strong> Nifty Fifty is not liable for delays caused by traffic, courier issues, or incorrect address details. Equipment responsibility transfers to you upon handover.</p>
                                            </article>
                                        </div>
                                    </article>
                                </div>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">5</span>
                                    <h3>Returning Equipment: Methods, Grace Period and Penalties</h3>
                                </header>
                                <p>You must choose one return method:</p>
                                <div class="cart-terms-method-grid" role="list" aria-label="Returning methods">
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Return to Store</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Return to store requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Return anytime within the agreed return window.</p>
                                            </article>
                                        </div>
                                    </article>
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Meet-up Return</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Meet-up return requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Time and location must be pre-arranged with staff.</p>
                                            </article>
                                        </div>
                                    </article>
                                    <article class="cart-terms-method-card" role="listitem">
                                        <h4>Delivery Return</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Delivery return requirements">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>You must book and pay for the courier. Return shipment must be initiated within the 2-hour Grace Period.</p>
                                            </article>
                                        </div>
                                    </article>
                                    <article class="cart-terms-method-card is-warning" role="listitem">
                                        <h4>Late Returns and Penalties</h4>
                                        <div class="cart-terms-list-cards compact" role="list" aria-label="Late return penalties">
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>The 2-hour Grace Period is for initiating the return process, not for extended usage.</p>
                                            </article>
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Returns completed after the Grace Period incur a late penalty of &#8369;50 for every hour (or partial hour) of delay.</p>
                                            </article>
                                            <article class="cart-terms-point-card" role="listitem">
                                                <p>Failure to return equipment within 24 hours after the Grace Period ends may be treated as theft or conversion, and legal action will be pursued. All accrued late fees will still apply.</p>
                                            </article>
                                        </div>
                                    </article>
                                </div>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">6</span>
                                    <h3>Care, Liability, and Fees</h3>
                                </header>
                                <div class="cart-terms-list-cards" role="list" aria-label="Care and liability rules">
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>You are responsible for the equipment from receipt until its verified return.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>You are fully liable for all damage, loss, or theft and will be charged appropriate repair or replacement fees.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>All rental fees, delivery charges, and late penalties are your responsibility.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p>Nifty Fifty is not liable for any indirect damages (e.g., missed shooting opportunities, data loss).</p>
                                    </article>
                                </div>
                            </section>

                            <section class="cart-terms-section">
                                <header class="cart-terms-section-head">
                                    <span class="cart-terms-number">7</span>
                                    <h3>General Provisions</h3>
                                </header>
                                <div class="cart-terms-bullet-cards" role="list" aria-label="General provisions list">
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Account Integrity</strong></p>
                                        <p>You must provide accurate information. Misuse of the CREATY system may result in account suspension.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Privacy</strong></p>
                                        <p>ID photos are collected solely for verification and dealt with per our Privacy Policy.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Limitation of Liability</strong></p>
                                        <p>Nifty Fifty&#39;s maximum liability is limited to the total rental fees paid for the reservation.</p>
                                    </article>
                                    <article class="cart-terms-point-card" role="listitem">
                                        <p class="cart-terms-point-title"><strong>Changes to Terms</strong></p>
                                        <p>We may update these Terms. Your continued use of CREATY constitutes acceptance.</p>
                                    </article>
                                </div>
                            </section>
                        </article>
                    </div>
                </section>
            </div>

            <aside class="cart-sidebar">
                <section class="cart-booking-card" data-cart-booking>
                    <div class="cart-booking-group">
                        <h2>Receiving Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <input type="date" data-booking-field="receiveDate">
                            <select data-booking-field="receiveTime">
                                <option value="08:00">08:00 AM</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00" selected>10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="13:00">01:00 PM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:00">04:00 PM</option>
                                <option value="17:00">05:00 PM</option>
                            </select>
                        </div>

                        <label class="cart-form-line">
                            <span>Meeting Place:</span>
                            <select data-booking-field="place">
                                <option value="Walter Mart Entrance, Carmona" selected>Walter Mart Entrance, Carmona</option>
                                <option value="Cabilang Baybay (Arko), Carmona">Cabilang Baybay (Arko), Carmona</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-booking-group">
                        <h2>Returning Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <input type="date" data-booking-field="returnDate">
                            <select data-booking-field="returnTime">
                                <option value="08:00" selected>08:00 AM</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="13:00">01:00 PM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:00">04:00 PM</option>
                                <option value="17:00">05:00 PM</option>
                            </select>
                        </div>

                        <p class="cart-late-note">Late returns = P50/hour</p>

                        <label class="cart-form-line">
                            <span>Courier:</span>
                            <select data-booking-field="courier">
                                <option value="lalamove" selected>Lalamove</option>
                                <option value="grab-express">GrabExpress</option>
                                <option value="lbc">LBC</option>
                                <option value="j-and-t">J&T Express</option>
                                <option value="self-booked">Self-booked Courier</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-methods-row">
                        <section class="cart-method-card">
                            <h3>Receiving Method:</h3>
                            <div class="cart-method-options" data-booking-method-group="receivingMethod">
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="pickup" checked>
                                    <span>PICK-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="meetup">
                                    <span>MEET-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="delivery">
                                    <span>DELIVERY</span>
                                </label>
                            </div>
                        </section>

                        <section class="cart-method-card">
                            <h3>Returning Method:</h3>
                            <div class="cart-method-options" data-booking-method-group="returningMethod">
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="pickup">
                                    <span>PICK-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="meetup" checked>
                                    <span>MEET-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="delivery">
                                    <span>DELIVERY</span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <div class="cart-valid-id-block" data-delivery-only-block hidden>
                        <p>Valid Id (PhilSys, Tin Drivers license, Etc.)</p>
                        <div class="cart-upload-row">
                            <span data-upload-label="validId">No file selected</span>
                            <label class="cart-upload-button" aria-label="Upload valid ID">
                                <input type="file" accept="image/*" data-booking-field="validIdImage" hidden>
                                <span>&#8682;</span>
                            </label>
                        </div>
                        <p>Holding the Valid id near the face</p>
                        <div class="cart-upload-row">
                            <span data-upload-label="selfieId">No file selected</span>
                            <label class="cart-upload-button" aria-label="Upload selfie with valid ID">
                                <input type="file" accept="image/*" data-booking-field="selfieWithId" hidden>
                                <span>&#8682;</span>
                            </label>
                        </div>
                    </div>

                    <div class="cart-summary-card">
                        <h3>TOTAL:</h3>
                        <strong data-cart-total>P 0.00</strong>
                        <p class="cart-summary-breakdown" data-cart-breakdown>Subtotal P 0.00 + Service fee P 0.00</p>
                    </div>

                    <select class="cart-payment-select" data-booking-field="paymentMethod">
                        <option value="">Payment Method</option>
                        <option value="gcash">GCash (Demo)</option>
                        <option value="bank-transfer">Bank Transfer (Demo)</option>
                        <option value="cash-pickup">Cash on Pick-up</option>
                        <option value="cash-meetup">Cash on Meet-up</option>
                    </select>

                    <button class="cart-confirm-button" type="button">CONFIRM BOOKING</button>

                    <p class="cart-booking-note" data-cart-booking-note>Demo flow only: no real booking or payment will be processed.</p>
                </section>
            </aside>
        </section>
    </main>

    <?php require __DIR__ . '/customer_message_modal.php'; ?>

    <section class="cart-unavailable-modal" data-cart-unavailable-modal hidden>
        <div class="cart-unavailable-modal-backdrop" data-cart-unavailable-close></div>
        <div class="cart-unavailable-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-unavailable-title">
            <h3 id="cart-unavailable-title">Unavailable items found</h3>
            <p data-cart-unavailable-message>Some items are no longer available and will be removed from your cart.</p>
            <div class="cart-unavailable-modal-actions">
                <button type="button" class="cart-unavailable-modal-cancel" data-cart-unavailable-close>Cancel</button>
                <button type="button" class="cart-unavailable-modal-confirm" data-cart-unavailable-confirm>Remove and Continue</button>
            </div>
        </div>
    </section>

    <script>
        window.__creatyCartAvailableItemIds = <?php echo json_encode($availableCartItemIds, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260402-5"></script>
</body>
</html>