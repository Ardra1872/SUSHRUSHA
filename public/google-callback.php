<?php
session_start();

require_once '../src/config/db.php';
require_once '../src/config/env.php'; // 👈 load env

$client_id     = $_ENV['GOOGLE_CLIENT_ID'];
$client_secret = $_ENV['GOOGLE_CLIENT_SECRET'];
$redirect_uri  = $_ENV['GOOGLE_REDIRECT_URI'];

if (empty($client_id) || empty($client_secret)) {
    // search common .env locations used by this project
    $candidates = [
        __DIR__ . '/credential.env',
        __DIR__ . '/../public/credential.env',
        __DIR__ . '/../src/.env',
        __DIR__ . '/../.env',
    ];
    foreach ($candidates as $path) {
        if (file_exists($path)) {
        $env = parse_ini_file($path, false);

            if ($env && is_array($env)) {
                if (empty($client_id) && isset($env['GOOGLE_CLIENT_ID'])) {
                    $client_id = $env['GOOGLE_CLIENT_ID'];
                }
                if (empty($client_secret) && isset($env['GOOGLE_CLIENT_SECRET'])) {
                    $client_secret = $env['GOOGLE_CLIENT_SECRET'];
                }
                // Stop searching if we found both
                if (!empty($client_id) && !empty($client_secret)) {
                    break;
                }
            }
        }
    }
}

// If still empty, stop with a friendly message
if (empty($client_id) || empty($client_secret)) {
    $_SESSION['error'] = 'Google OAuth credentials are not configured. Contact the administrator.';
    header('Location: login.php');
    exit();
}

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

// Use curl to POST for token (more robust and exposes HTTP errors)
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
    error_log("Google token request failed: HTTP $httpCode - $curlErr - response: " . var_export($response, true));
    $_SESSION['error'] = 'Failed to contact Google for token. Please try again.';
    header('Location: login.php');
    exit();
}

$token = json_decode($response, true);
if (!is_array($token) || empty($token['access_token'])) {
    error_log('Google token response invalid: ' . var_export($token, true));
    $_SESSION['error'] = 'Invalid response from Google. Please try again.';
    header('Location: login.php');
    exit();
}

$access_token = $token['access_token'];

/* STEP 2: Get user info */
// Get user info via curl
$userinfo_url = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . urlencode($access_token);
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$user_info = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);
if ($user_info === false || $httpCode >= 400) {
    error_log("Google userinfo request failed: HTTP $httpCode - $curlErr - response: " . var_export($user_info, true));
    $_SESSION['error'] = 'Failed to fetch Google user profile. Please try again.';
    header('Location: login.php');
    exit();
}

$user = json_decode($user_info, true);

// validate user fields
$email = $user['email'] ?? null;
$name  = $user['name'] ?? null;
if (empty($email)) {
    error_log('Google OAuth returned no email: ' . var_export($user, true));
    $_SESSION['error'] = 'Could not retrieve your Google email. Please try a different sign-in method.';
    header('Location: login.php');
    exit();
}
if (empty($name)) {
    // fallback to email local-part
    $name = strstr($email, '@', true) ?: $email;
}

/* STEP 3: Check user in DB */
$stmt = $conn->prepare("SELECT id, role FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $role = "patient";
    $stmt = $conn->prepare(
        "INSERT INTO users (name, email, role) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $name, $email, $role);
    if (!$stmt->execute()) {
        error_log('DB insert failed: ' . $stmt->error);
        $_SESSION['error'] = 'Unable to create user account. Try again later.';
        header('Location: login.php');
        exit();
    }
} else {
    $row = $result->fetch_assoc();
    $role = $row['role'];
}

/* STEP 4: Login */
$_SESSION['user_email'] = $email;
$_SESSION['user_name'] = $name;
$_SESSION['user_role'] = $role;
header("Location: /Sushrusha/src/views/dashboard.php");
exit();
?>