<?php
require_once __DIR__ . '/../vendor/autoload.php'; // composer autoload

$client = new Google_Client();
$client->setClientId("76074937310-ipe7fo444sj5ilalu0ckfnbo3967fs0g.apps.googleusercontent.com"); // your Client ID
$client->setClientSecret("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"); // your Client Secret
$client->setRedirectUri("http://localhost/TAHANAN/LOGIN/callback.php"); // must match Google Console redirect URI
$client->addScope("email");
$client->addScope("profile");


