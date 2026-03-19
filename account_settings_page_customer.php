<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: account-settings/');
    exit;
}

session_start();

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'login/';
$accountSettingsPath = $accountSettingsPath ?? $assetBase . 'account-settings/';
$logoutPath = $logoutPath ?? $assetBase . 'logout/';
$cartPath = $cartPath ?? $assetBase . 'cart/';
$eventsPath = $eventsPath ?? $assetBase . 'events/';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
if (!$isCustomerLoggedIn) {
    $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'account-settings/');
    header('Location: ' . $loginPath . '?redirect=' . rawurlencode($currentPageUrl));
    exit;
}

$cartCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
$accountLabel = 'Account';

$fullName = trim((string) ($_SESSION['customer_name'] ?? ''));
$nameParts = preg_split('/\s+/', $fullName) ?: [];
$firstNameDefault = $nameParts[0] ?? '';
$lastNameDefault = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
$emailDefault = (string) ($_SESSION['customer_email'] ?? '');

$firstNameValue = $firstNameDefault;
$lastNameValue = $lastNameDefault;
$phoneValue = '';
$addressOneValue = '';
$addressTwoValue = '';
$cityValue = '';
$provinceValue = '';
$postalValue = '';
$infoMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstNameValue = trim($_POST['first_name'] ?? '');
    $lastNameValue = trim($_POST['last_name'] ?? '');
    $phoneValue = trim($_POST['phone'] ?? '');
    $addressOneValue = trim($_POST['address_line_one'] ?? '');
    $addressTwoValue = trim($_POST['address_line_two'] ?? '');
    $cityValue = trim($_POST['city'] ?? '');
    $provinceValue = trim($_POST['province'] ?? '');
    $postalValue = trim($_POST['postal_code'] ?? '');

    $infoMessage = 'Changes are for preview only. Profile saving will be added in a future update.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | Account Settings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260319-2">
</head>
<body class="account-settings-page">
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

            <a class="topbar-cart" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Cart">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#">Message us</a>
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
        </div>

        <nav class="section-nav section-nav-disabled" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">PROFILE</span>
            <span class="section-nav-section is-disabled" aria-disabled="true">ACCOUNT SETTINGS</span>
            <span class="section-nav-filter is-disabled" aria-disabled="true">SECURITY</span>
        </nav>
    </header>

    <main class="account-settings-shell">
        <section class="account-settings-card reveal">
            <div class="account-settings-head">
                <h1>Account Settings</h1>
                <p>Update your profile details below. Saving to database is not enabled yet.</p>
            </div>

            <?php if ($infoMessage !== ''): ?>
                <p class="form-message form-message-success"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form class="account-settings-form" action="" method="post" novalidate>
                <div class="account-settings-grid">
                    <label>
                        <span>First Name</span>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter first name">
                    </label>

                    <label>
                        <span>Last Name</span>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter last name">
                    </label>

                    <label>
                        <span>Email Address</span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($emailDefault, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email address" readonly>
                    </label>

                    <label>
                        <span>Mobile Number</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xx xxx xxxx">
                    </label>

                    <label class="account-field-wide">
                        <span>Address Line 1</span>
                        <input type="text" name="address_line_one" value="<?php echo htmlspecialchars($addressOneValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="House number and street">
                    </label>

                    <label class="account-field-wide">
                        <span>Address Line 2</span>
                        <input type="text" name="address_line_two" value="<?php echo htmlspecialchars($addressTwoValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Barangay, subdivision, or landmark">
                    </label>

                    <label>
                        <span>City / Municipality</span>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="City">
                    </label>

                    <label>
                        <span>Province</span>
                        <input type="text" name="province" value="<?php echo htmlspecialchars($provinceValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Province">
                    </label>

                    <label>
                        <span>Postal Code</span>
                        <input type="text" name="postal_code" value="<?php echo htmlspecialchars($postalValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Postal code">
                    </label>
                </div>

                <div class="account-settings-actions">
                    <button type="submit">Update Preview</button>
                </div>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
