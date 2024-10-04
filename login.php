<?php
// Ersetze diese Werte durch deine tatsächlichen Client ID und Redirect URI
$clientId = '';
$redirectUri = 'http://ventus-test.de/callback.php';

// URL zur Weiterleitung
$authUrl = "https://discord.com/api/oauth2/authorize?client_id=$clientId&redirect_uri=" . urlencode($redirectUri) . "&response_type=code&scope=identify%20email";

// Benutzer zu Discord weiterleiten
header('Location: ' . $authUrl);
exit;
?>
