<?php
include '../includes/db.php';
require_once __DIR__ . '/google-config.php';
session_start();

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);
        $google_service = new Google_Service_Oauth2($client);
        $data = $google_service->userinfo->get();

        $email = $data->email;
        $name = $data->name;
        $nameParts = explode(" ", $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : "";

        // --- 1. Check if ADMIN
        $stmt = $conn->prepare("SELECT ID, firstName, lastName FROM admintbl WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $adminResult = $stmt->get_result();
        if ($row = $adminResult->fetch_assoc()) {
            $_SESSION["user_id"] = $row["ID"];
            $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
            $_SESSION["user_type"] = "admin";
            header("Location: admin-dashboard.php");
            exit;
        }

        // --- 2. Check if LANDLORD
        $stmt = $conn->prepare("SELECT ID, firstName, lastName, status FROM landlordtbl WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $landlordResult = $stmt->get_result();
        if ($row = $landlordResult->fetch_assoc()) {
            $_SESSION["user_id"] = $row["ID"];
            $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
            $_SESSION["user_type"] = "landlord";
            $_SESSION["status"] = $row["status"]; // you can use this to check if approved
            header("Location: landlord-dashboard.php");
            exit;
        }

        // --- 3. Check if TENANT
        $stmt = $conn->prepare("SELECT ID, firstName, lastName FROM tenanttbl WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $tenantResult = $stmt->get_result();
        if ($row = $tenantResult->fetch_assoc()) {
            $_SESSION["user_id"] = $row["ID"];
            $_SESSION["user_name"] = $row["firstName"] . " " . $row["lastName"];
            $_SESSION["user_type"] = "tenant";
            header("Location: tenant-dashboard.php");
            exit;
        }

        // --- 4. If NOT found in any table → auto-create as TENANT
        $stmt = $conn->prepare("INSERT INTO tenanttbl (firstName, lastName, email, dateJoin, status) VALUES (?, ?, ?, NOW(), 'active')");
        $stmt->bind_param("sss", $firstName, $lastName, $email);
        $stmt->execute();

        $_SESSION["user_id"] = $conn->insert_id;
        $_SESSION["user_name"] = $firstName . " " . $lastName;
        $_SESSION["user_type"] = "tenant";

        header("Location: tenant-dashboard.php");
        exit;
    } else {
        echo "❌ Error during authentication!";
    }
} else {
    echo "❌ No code parameter!";
}
