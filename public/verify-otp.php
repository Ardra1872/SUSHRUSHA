<?php
session_start();
require '../src/config/db.php';

if(!isset($_SESSION['reset_email'])){
    header("Location: login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $otp = trim($_POST['otp']);
    $otp = strval($otp);

    $email = $_SESSION['reset_email'];

    // Verify OTP only
    $query = "SELECT id FROM users WHERE email='" . $conn->real_escape_string($email) . "' AND reset_code='" . $conn->real_escape_string($otp) . "'";
    
    $result = $conn->query($query);
    
    if($result && $result->num_rows > 0){
        $_SESSION['otp_verified'] = true;
        header("Location: reset-password.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid or expired OTP!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
    /* Inline auth styles for verify-otp page */
    :root{--card-bg:#fff;--accent:#3498db;--text:#2c3e50}
    *{box-sizing:border-box}
    body{font-family:Segoe UI, Arial, sans-serif;background:linear-gradient(180deg,#f3f8fc,#eaf6ff);margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
    .auth-card{width:100%;max-width:420px;padding:28px;border-radius:10px;background:var(--card-bg);box-shadow:0 8px 30px rgba(50,70,90,0.08)}
    .auth-card h2{margin:0 0 14px;color:var(--text);font-size:22px}
    .auth-form{display:flex;flex-direction:column;gap:12px}
    .auth-form input[type=text]{padding:12px;border:1px solid #dbe7f0;border-radius:6px;font-size:15px}
    .auth-form button{padding:12px;border:0;background:var(--accent);color:white;border-radius:6px;font-weight:600;cursor:pointer}
    .auth-form button:hover{background:#2575b8}
    .auth-error{color:#c0392b;margin:8px 0}
    .auth-foot{margin-top:12px;font-size:13px;color:#6b7a8a;text-align:center}
    @media (max-width:480px){.auth-card{margin:20px}}
    </style>
</head>
<body>

<div class="auth-card">
    <h2>Verify OTP</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <p class="auth-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form class="auth-form" method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit">Verify</button>
    </form>

    <div class="auth-foot">Enter the 6-digit code sent to your email.</div>
</div>

</body>
</html>
