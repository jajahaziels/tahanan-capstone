<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../includes/db.php';
require_once __DIR__ . '/google-config.php';

use Google\Service\Oauth2;

// --- Decode state (mode + intended role) ---
if (!isset($_GET['state'])) {
    $_SESSION['error'] = "Missing state parameter.";
    header("Location: signup.php");
    exit();
}

$state = json_decode(base64_decode($_GET['state']), true);
$mode = $state['mode'] ?? 'login';         // login or signup
$intendedRole = $state['role'] ?? 'tenant'; // tenant or landlord

// --- Exchange Google code for token ---
if (!isset($_GET['code'])) {
    $_SESSION['error'] = "Invalid request. No code returned.";
    header("Location: signup.php");
    exit();
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    $_SESSION['error'] = "Google authentication failed.";
    header("Location: signup.php");
    exit();
}

$client->setAccessToken($token['access_token']);
$google_service = new Google_Service_Oauth2($client);
$data = $google_service->userinfo->get();

// --- Extract user info ---
$email = strtolower(trim($data['email']));
$firstName = $data['given_name'];
$lastName = $data['family_name'];
$dummyPassword = password_hash("google_oauth", PASSWORD_DEFAULT);

// --- Check if user exists ---
$role = $intendedRole; // default role if new
$userExists = false;

$stmtLandlord = $conn->prepare("SELECT ID FROM landlordtbl WHERE email=?");
$stmtLandlord->bind_param("s", $email);
$stmtLandlord->execute();
$resLandlord = $stmtLandlord->get_result();

$stmtTenant = $conn->prepare("SELECT ID FROM tenanttbl WHERE email=?");
$stmtTenant->bind_param("s", $email);
$stmtTenant->execute();
$resTenant = $stmtTenant->get_result();

if ($resLandlord->num_rows > 0) {
    $role = 'landlord';
    $user = $resLandlord->fetch_assoc();
    $userExists = true;
} elseif ($resTenant->num_rows > 0) {
    $role = 'tenant';
    $user = $resTenant->fetch_assoc();
    $userExists = true;
}

// --- Determine redirect target ---
$redirect = ($role === 'landlord') ? '../LANDLORD/landlord-properties.php' : '../TENANT/tenant.php';

// --- LOGIN ---
if ($mode === 'login') {
    if ($userExists) {
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['username'] = $firstName . ' ' . $lastName;
        $_SESSION['user_type'] = $role;

        // ✅ Add these lines to unify sessions with your message pages
        if ($role === 'tenant') {
            $_SESSION['tenant_id'] = $user['ID'];
        } elseif ($role === 'landlord') {
            $_SESSION['landlord_id'] = $user['ID'];
        }

        header("Location: $redirect");
        exit();
    } else {
        $_SESSION['error'] = "No account found. Please signup first.";
        header("Location: signup.php");
        exit();
    }
}


// --- SIGNUP ---
if ($mode === 'signup') {
    if ($userExists) {
        $_SESSION['error'] = "⚠️ Email already registered. Please login.";
        header("Location: login.php?clear=1");
        exit();
    } else {
        if ($role === 'landlord') {
            // landlordtbl has created_at
            $insert = $conn->prepare("INSERT INTO landlordtbl (firstName, lastName, email, password, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
        } else {
            // tenanttbl has created_at
            $insert = $conn->prepare("INSERT INTO tenanttbl (firstName, lastName, email, password, created_at, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
        }

        $insert->bind_param("ssss", $firstName, $lastName, $email, $dummyPassword);
        if (!$insert->execute()) {
            die("Error creating account: " . $insert->error);
        }
        $insert->close();

        // After successful signup, redirect to login page
        $_SESSION['success'] = "🎉 Account created successfully. Please login.";
        header("Location: login.php");
        exit();
    }
}


// --- fallback ---
$_SESSION['error'] = "Invalid operation.";
header("Location: signup.php");
exit();
?>