<?php
session_start();
require '../src/config/db.php';   

// ---------- FORM SUBMISSION ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Sanitize and trim inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $role = 'patient';
    
    // --- SERVER-SIDE VALIDATION ---
    $errors = [];

    // 1. Validate Name (at least 3 characters)
    if (strlen($name) < 3) {
        $errors[] = "Full Name must be at least 3 characters long.";
    }

    // 2. Validate Email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // 3. Validate Password complexity 
    // Matches the client-side pattern: minimum 6 characters, one uppercase, one lowercase, one digit.
    if (strlen($password) < 6 || 
        !preg_match('/[a-z]/', $password) || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/\d/', $password)
    ) {
        $errors[] = "Password must be at least 6 characters, including an uppercase letter, a lowercase letter, and a number.";
    }

    // If any validation errors exist, store them and redirect
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors); 
        header("Location: register.php");
        exit;
    }
    // --- END SERVER-SIDE VALIDATION ---
    
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
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit;
    } else {
        $_SESSION['error'] = "Database Error: " . $stmt->error;
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

    <link rel="stylesheet" href="assets/loginRegister.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<div class="auth-wrapper">

    <div class="auth-form">
        <div class="auth-card">

            <h2>Create Account</h2>
            <p>Start managing your medicine schedule today</p>

            <?php if(isset($_SESSION['error'])): ?>
                <p class="message error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>

            <?php if(isset($_SESSION['success'])): ?>
                <p class="message success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            <?php endif; ?>

           <form id="registerForm" action="register.php" method="POST" novalidate>
                <input type="text" id="name" name="name" placeholder="Full Name" required>
                <input type="email" id="email" name="email" placeholder="Email" required>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <button type="submit" class="btn-primary">Register</button>
            </form>

            <p class="switch">Already have an account? 
                <a href="login.php">Login</a>
            </p>

        </div>
    </div>

    <div class="auth-image">
        <img src="assets/images/loginimage.jpg" alt="Medical illustration">
    </div>

</div>

<script>
    console.log("Validation script loaded!");

    document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const form = document.getElementById('registerForm');

    function validateName() {
        if(nameInput.value.trim().length < 3) {
            nameInput.classList.add('invalid');
            nameInput.classList.remove('valid');
            return false;
        } else {
            nameInput.classList.add('valid');
            nameInput.classList.remove('invalid');
            return true;
        }
    }

    function validateEmail() {
        let pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!pattern.test(emailInput.value.trim())) {
            emailInput.classList.add('invalid');
            emailInput.classList.remove('valid');
            return false;
        } else {
            emailInput.classList.add('valid');
            emailInput.classList.remove('invalid');
            return true;
        }
    }

    function validatePassword() {
        let pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/;
        if(!pattern.test(passwordInput.value)) {
            passwordInput.classList.add('invalid');
            passwordInput.classList.remove('valid');
            return false;
        } else {
            passwordInput.classList.add('valid');
            passwordInput.classList.remove('invalid');
            return true;
        }
    }

    // Live validation on input
    nameInput.addEventListener('input', validateName);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', validatePassword);

    // Validation on blur (when user leaves the field)
    nameInput.addEventListener('blur', validateName);
    emailInput.addEventListener('blur', validateEmail);
    passwordInput.addEventListener('blur', validatePassword);

    // Validate on form submit
    form.addEventListener('submit', function(e) {
        if(!validateName() || !validateEmail() || !validatePassword()) {
            e.preventDefault();
            alert("Please fix the highlighted fields before submitting.");
        }
    });
});

    </script>
</body>
</html>