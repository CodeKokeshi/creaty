<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
	header('Location: ../');
	exit;
}

if (isset($_SESSION['user_id'])) {
	header('Location: dashboard/');
	exit;
}

if (isset($_SESSION['staff_id'])) {
	header('Location: dashboard/?admin_view=bookings');
	exit;
}

$routeBase = '';
$assetBase = '../';
require dirname(__DIR__) . '/login_page_admin.php';