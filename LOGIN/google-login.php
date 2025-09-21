<?php
require_once 'vendor/autoload.php';
session_start();

$client = new Google_Client();
$client->setClientId("76074937310-ipe7fo444sj5ilalu0ckfnbo3967fs0g.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri("http://localhost/TAHANAN/LOGIN/callback.php");
$client->addScope("email");
$client->addScope("profile");

// Redirect to Google OAuth
header('Location: ' . $client->createAuthUrl());
exit;
