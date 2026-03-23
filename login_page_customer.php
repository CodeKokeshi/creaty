<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? '';
$assetBase = $assetBase ?? '';
$customerLoginPath = $customerLoginPath ?? 'customer-login/';
$customerSignupPath = $customerSignupPath ?? 'customer-signup/';
$customerPrivacyPolicyPath = $customerPrivacyPolicyPath ?? 'customer-privacy-policy/';
$adminLoginPath = $adminLoginPath ?? $routeBase . 'admin/';

require_once __DIR__ . '/config/db.php';

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';

$successMessage = '';
$errorMessage = '';
$emailValue = '';
$redirectTarget = trim((string) ($_POST['redirect'] ?? $_GET['redirect'] ?? ''));

$defaultRedirect = $routeBase === '' ? '/' : $routeBase;

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase . 'admin/dashboard/');
    exit;
}

if (isset($_SESSION['customer_id'])) {
    if ($redirectTarget !== '' && preg_match('/^(?:\/|\.\.?\/)/', $redirectTarget) === 1 && strpos($redirectTarget, '://') === false && strpos($redirectTarget, '//') !== 0) {
        header('Location: ' . $redirectTarget);
        exit;
    }

    header('Location: ' . $defaultRedirect);
    exit;
}

if (isset($_GET['verified']) && $_GET['verified'] === '1') {
    $successMessage = 'Email verified. Your account has been created successfully.';
} elseif (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Account created successfully. Please verify your email address first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');
    $passwordValue = $_POST['password'] ?? '';

    if ($emailValue === '' || $passwordValue === '') {
        $errorMessage = 'Please enter both email and password.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $loginStmt = $conn->prepare("SELECT id, first_name, last_name, email, password, email_verified_at FROM {$customerAccountsTable} WHERE email = ? LIMIT 1");
        $loginStmt->bind_param('s', $emailValue);
        $loginStmt->execute();
        $customerResult = $loginStmt->get_result();
        $customer = $customerResult->fetch_assoc();
        $loginStmt->close();

        if (!$customer || !password_verify($passwordValue, $customer['password'])) {
            $errorMessage = 'Invalid email or password.';
        } elseif ($customer['email_verified_at'] === null) {
            $errorMessage = 'Please verify your email address before logging in.';
        } else {
            $_SESSION['customer_id'] = (int) $customer['id'];
            $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $_SESSION['customer_email'] = $customer['email'];

            if (!isset($_SESSION['customer_cart_count'])) {
                $_SESSION['customer_cart_count'] = 0;
            }

            if ($redirectTarget !== '' && preg_match('/^(?:\/|\.\.?\/)/', $redirectTarget) === 1 && strpos($redirectTarget, '://') === false && strpos($redirectTarget, '//') !== 0) {
                header('Location: ' . $redirectTarget);
                exit;
            }

            header('Location: ' . $defaultRedirect);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css">
</head>
<body class="login-page">
    <main class="login-page-shell">
        <a class="auth-switch-link" href="<?php echo htmlspecialchars($adminLoginPath, ENT_QUOTES, 'UTF-8'); ?>" data-auth-switch aria-label="Switch to administrator login">
            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/admin-login-icon.svg" alt="">
        </a>

        <section class="login-card reveal">
            <div class="brand-wrap">
                <img class="brand-logo" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </div>

            <h1 class="login-title">LOG IN</h1>

            <form class="customer-login-form" action="#" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif ($successMessage !== ''): ?>
                    <p class="form-message form-message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="sr-only" for="customer_email">Email address</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="customer_email" name="email" type="email" placeholder="Enter your email address" autocomplete="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="field-icon" aria-hidden="true"></span>
                </div>

                <label class="sr-only" for="customer_password">Password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="customer_password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="customer_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <div class="login-options">
                    <a class="support-link" href="#">Forgot Password?</a>

                    <label class="remember-wrap" for="customer_remember">
                        <span>Remember me</span>
                        <input id="customer_remember" name="remember" type="checkbox">
                    </label>
                </div>

                <button class="login-submit" type="submit">LOGIN</button>

                <button class="google-submit" type="button">
                    <span>Sign In With Google</span>
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/google_icon.svg" alt="Google">
                </button>

                <p class="switch-auth">Not a Member? <a href="<?php echo htmlspecialchars($customerSignupPath, ENT_QUOTES, 'UTF-8'); ?>">Sign up</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>