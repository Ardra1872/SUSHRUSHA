<?php
session_start();
require '../src/config/db.php';   

// ---------- FORM SUBMISSION ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $role = 'patient';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Email already registered!";
        header("Location: login.php"); 
        exit;
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, role, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $role, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful!";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUSHRUSHA – Register</title>

    <!-- FIX CSS PATH -->
    <link rel="stylesheet" href="assets/loginRegister.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="auth-wrapper">

    <!-- LEFT SIDE – REGISTER FORM -->
    <div class="auth-form">
        <div class="auth-card">

            <h2>Create Account</h2>
            <p>Start managing your medicine schedule today</p>

            <!-- Display success/error messages -->
            <?php if(isset($_SESSION['error'])): ?>
                <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>
                <p style="color:green;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn-primary">Register</button>
            </form>

            <p class="switch">Already have an account? 
                <a href="login.php">Login</a>
            </p>

        </div>
    </div>

    <!-- RIGHT SIDE – IMAGE -->
    <div class="auth-image">
        <img src="assets/images/loginimage.jpg" alt="Medical illustration">
    </div>

</div>

</body>
</html>
