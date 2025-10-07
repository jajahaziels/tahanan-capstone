<?php
session_start();

$current_page = basename($_SERVER['SCRIPT_NAME']);

// If not logged in and not on login page → redirect to login
if (!isset($_SESSION['user_type'])) {
    if ($current_page !== 'login.php') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}

// If already logged in and trying to open login.php → redirect to dashboard
if (isset($_SESSION['user_type']) && $current_page === 'login.php') {
    if ($_SESSION['user_type'] === 'landlord') {
        header("Location: ../LANDLORD/landlord.php");
    } elseif ($_SESSION['user_type'] === 'tenant') {
        header("Location: ../TENANT/tenant.php");
    } elseif ($_SESSION['user_type'] === 'admin') {
        header("Location: ../ADMIN/admin.php");
    }
    exit;
}

// 🔒 Restrict tenant pages
if (strpos($_SERVER['SCRIPT_NAME'], '/TENANT/') !== false) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'tenant') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}

// 🔒 Restrict landlord pages
if (strpos($_SERVER['SCRIPT_NAME'], '/LANDLORD/') !== false) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'landlord') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}

// 🔒 Restrict admin pages
if (strpos($_SERVER['SCRIPT_NAME'], '/ADMIN/') !== false) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header("Location: ../LOGIN/login.php");
        exit;
    }
}
?>