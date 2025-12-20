<?php
session_start();

require_once '../src/config/db.php';
require_once '../src/config/env.php'; // 👈 load env

$client_id     = $_ENV['GOOGLE_CLIENT_ID'];
$client_secret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirect_uri  = $_ENV['GOOGLE_REDIRECT_URI'];

if (!isset($_GET['code'])) {
    header("Location: login.php");
    exit();
}

/* STEP 1: Exchange code for access token */
$token_url = "https://oauth2.googleapis.com/token";

$data = [
    "code" => $_GET['code'],
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
];

$options = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/x-www-form-urlencoded",
        "content" => http_build_query($data)
    ]
];

$response = file_get_contents($token_url, false, stream_context_create($options));
$token = json_decode($response, true);

$access_token = $token['access_token'];

/* STEP 2: Get user info */
$user_info = file_get_contents(
    "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $access_token
);

$user = json_decode($user_info, true);

$email = $user['email'];
$name  = $user['name'];

/* STEP 3: Check user in DB */
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $role = "patient";
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, role) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $name, $email, $role);
    $stmt->execute();
}

/* STEP 4: Login */
$_SESSION['user_email'] = $email;
header("Location: /Sushrusha/src/views/dashboard.php");
exit();
