<?php
session_start();
require_once '../src/config/env.php'; //load env



$client_id    = $_ENV['GOOGLE_CLIENT_ID'];
$redirect_uri = $_ENV['GOOGLE_REDIRECT_URI'];




$scope = urlencode(
    "https://www.googleapis.com/auth/userinfo.email " .
    "https://www.googleapis.com/auth/userinfo.profile"
);

$auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" .
    "response_type=code" .
    "&client_id=" . $client_id .
    "&redirect_uri=" . urlencode($redirect_uri) .
    "&scope=" . $scope;

header("Location: " . $auth_url);
exit();
