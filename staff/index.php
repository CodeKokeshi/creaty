<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ../');
    exit;
}

if (isset($_SESSION['user_id']) || isset($_SESSION['staff_id'])) {
    header('Location: ../admin/dashboard/?admin_view=bookings');
    exit;
}

$routeBase = '../';
$assetBase = '../';
$customerLoginPath = '../customer-login/';
$adminLoginPath = '../admin/';
$staffDashboardPath = '../admin/dashboard/?admin_view=bookings';

require dirname(__DIR__) . '/login_page_staff.php';
