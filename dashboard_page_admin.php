<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

session_start();

$routeBase = $routeBase ?? 'admin/';
$assetBase = $assetBase ?? '';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase);
    exit;
}

$username = $_SESSION['username'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css">
</head>
<body class="dashboard-page">
    <main class="dashboard-shell">
        <section class="dashboard-card reveal">
            <p class="dashboard-kicker">Admin Dashboard</p>
            <h1>Welcome, <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>.</h1>
            <p class="dashboard-copy">
                You are authenticated and inside the protected admin dashboard. The public customer homepage remains separated from this area.
            </p>

            <div class="dashboard-meta">
                <div class="meta-pill">Signed in as: <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="meta-pill">Entry point: /admin</div>
            </div>

            <div class="dashboard-actions">
                <a class="dashboard-button dashboard-button-primary" href="<?php echo htmlspecialchars($routeBase, ENT_QUOTES, 'UTF-8'); ?>">Back to Login</a>
                <a class="dashboard-button dashboard-button-secondary" href="<?php echo htmlspecialchars($routeBase, ENT_QUOTES, 'UTF-8'); ?>logout.php">Logout</a>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>