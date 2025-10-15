<?php
require_once 'google-config.php';
session_start();

// Tenant or landlord role
$role = $_GET['role'] ?? 'tenant';
$mode = $_GET['mode'] ?? 'login';     // login or signup

// Encode state to pass through OAuth
$state = base64_encode(json_encode([
    'mode' => $mode,
    'role' => $role
]));

// Set the state in Google client
$client->setState($state);

// Redirect user to Google OAuth consent page
header('Location: ' . $client->createAuthUrl());
exit;
?>