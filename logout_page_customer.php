<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: logout/');
    exit;
}

session_start();

$routeBase = $routeBase ?? '';
$targetPath = $routeBase === '' ? './' : $routeBase;

unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email'], $_SESSION['customer_cart_count']);

header('Location: ' . $targetPath);
exit;
