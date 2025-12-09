<?php
session_start();
require '../src/config/db.php'; // UPDATE IF NEEDED

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password);
    $stmt->fetch();

    if($stmt->num_rows > 0){
        if(password_verify($password, $hashed_password)){
            
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;

            header("Location:../src/views/dashboard.php");
            exit;

        } else {
            $_SESSION['error'] = "Incorrect password!";
            header("Location: login.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "User not found!";
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUSHRUSHA – Login</title>
    <link rel="stylesheet" href="assets/loginRegister.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
<div class="auth-wrapper">

    <div class="auth-image">
        <img src="assets/images/loginimage.jpg" alt="Illustration">
    </div>

    <div class="auth-form">
        <div class="auth-card">

            <h2>Welcome Back!</h2>
            <p>Login to access your medicine reminders and care dashboard</p>

            <!-- Show errors -->
            <?php if(isset($_SESSION['error'])): ?>
                <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn-primary">Login</button>
            </form>

            <p class="switch">Don't have an account?
                <a href="/Sushrusha/public/register.php">Register</a>
            </p>

        </div>
    </div>

</div>
</body>
</html>
