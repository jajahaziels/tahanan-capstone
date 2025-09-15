<?php
session_start();

$current_page = basename($_SERVER['SCRIPT_NAME']);

if (!isset($_SESSION['email']) && $current_page !== 'login.php') {
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['email']) && $current_page === 'login.php') {
    header("Location: index.php");
    exit;
}
