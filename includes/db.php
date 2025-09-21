<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$db = "tahanandb";

try {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    // ✅ You can remove this in production
    error_log("Database connected successfully!");
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?>