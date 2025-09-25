<?php
session_start();

$current_page = basename($_SERVER['SCRIPT_NAME']);

if (!isset($_SESSION['user_type'])) {
    if ($current_page !== 'login.php') {
        header("Location: ../index.php");
        exit;
    }
}

// If already logged in and trying to open login.php → redirect
if (isset($_SESSION['user_type']) && $current_page === 'login.php') {
    if ($_SESSION['user_type'] === 'landlord') {
        header("Location: ../LANDLORD/landlord.php");
    } elseif ($_SESSION['user_type'] === 'tenant') {
        header("Location: ../TENANT/tenant.php");
    }
    exit;
}

// 🔒 Restrict tenant pages
if (strpos($_SERVER['SCRIPT_NAME'], '/tenant/') !== false) {
    if ($_SESSION['user_type'] !== 'tenant') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}

// 🔒 Restrict landlord pages
if (strpos($_SERVER['SCRIPT_NAME'], '/landlord/') !== false) {
    if ($_SESSION['user_type'] !== 'landlord') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}
