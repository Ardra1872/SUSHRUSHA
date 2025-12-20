<?php
session_start();
require '../src/config/db.php';
require '../src/helpers/sendOtp.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = trim($_POST['email']); // trim to remove extra spaces

    // Check if the email exists in DB
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if($stmt->num_rows > 0){
        // 1️⃣ Generate OTP and expiry
        $otp = strval(rand(100000, 999999)); // 6-digit OTP as string
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes")); // valid for 10 min

        // 2️⃣ Store OTP and expiry in DB
        $update = $conn->prepare(
            "UPDATE users SET reset_code=?, reset_expiry=? WHERE email=?"
        );
        $update->bind_param("sss", $otp, $expiry, $email);
        $update->execute();


        // Optional: debug
        // echo "OTP stored in DB: $otp, expires at $expiry";

        // 3️⃣ Send OTP email
        if(sendOTP($email, $otp)){
            $_SESSION['reset_email'] = $email;
            header("Location: verify-otp.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to send OTP email. Please try again.";
        }

    } else {
        $_SESSION['error'] = "Email not registered!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/auth.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<div class="auth-card">
    <h2>Forgot Password</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <p class="error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </p>
    <?php endif; ?>

    <form class="auth-form" method="POST">
        <input type="email" name="email" placeholder="Enter registered email" required>
        <button type="submit">Send OTP</button>
    </form>

    <div class="auth-foot">Enter your account email to receive a verification code.</div>
</div>

</body>
</html>
