<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$role = strtolower($_SESSION['role'] ?? 'resident');
$redirectPage = $role === 'admin' ? 'admin_dashboard.php' : 'resident_dashboard.php';
header('Location: ' . $redirectPage);
exit;
