<?php
session_start();
require '../src/config/db.php';

if(!isset($_SESSION['otp_verified'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare(
        "UPDATE users 
         SET password=?, reset_code=NULL, reset_expiry=NULL 
         WHERE email=?"
    );
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();

    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/auth.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="auth-card">
    <h2>Reset Password</h2>

    <form class="auth-form" method="POST">
        <input type="password" name="password" placeholder="New password" required>
        <button type="submit">Reset Password</button>
    </form>

    <div class="auth-foot">Choose a strong password you haven't used elsewhere.</div>
</div>

</body>
</html>
