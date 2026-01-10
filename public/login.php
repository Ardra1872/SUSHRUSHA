<?php
session_start();
require '../src/config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

   $email = strtolower(trim($_POST['email']));


    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, name, password, role, first_login FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password, $role, $first_login);
    $stmt->fetch();

    if ($stmt->num_rows > 0) {
        if (password_verify($password, $hashed_password)) {

            $_SESSION['user_id'] = $id;
$_SESSION['user_name'] = $name;
$_SESSION['role'] = $role;
$_SESSION['first_login'] = $first_login;

            // Redirect based on role
            if ($role === 'patient') {
                 $_SESSION['active_patient_id'] = $id; 
                header("Location: ../src/views/dashboard.php");
                exit;
            } elseif ($role === 'caretaker') {
                $_SESSION['active_patient_id'] = $selected_patient_id;
                header("Location: ../src/views/caretaker_dashboard.php");
                exit;
            } else {
                header("Location: ../src/views/admin_dashboard.php");
                exit;
            }

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

    <link rel="stylesheet" href="assets/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="assets/translations.js"></script>
    <script src="assets/language-selector.js"></script>
</head>
<body>
    <div class="auth-wrapper">
        <!-- Language Selector -->
        <div id="langSelectorPlaceholder" style="position: absolute; top: 20px; right: 20px; z-index: 100;"></div>
        <!-- LOGIN FORM CARD -->
        <div class="auth-card">
            <h2 data-i18n="welcome_back">Welcome Back!</h2>
            <p class="subtitle" data-i18n="login_subtitle">
                Login to access your medicine reminders and care dashboard
            </p>

            <!-- Error Message -->
            <?php if(isset($_SESSION['error'])): ?>
                <p class="message error-message">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </p>
            <?php endif; ?>

          <form id="loginForm" action="login.php" method="POST">
    <input type="email" id="email" name="email" placeholder="Enter your email..." data-i18n-placeholder="enter_email" required>
    <span id="emailError" class="error"></span>

    <input type="password" id="password" name="password" placeholder="Enter your password" data-i18n-placeholder="enter_password" required>
    <span id="passwordError" class="error"></span>

    <a href="forgot-password.php" class="forgot" data-i18n="forgot_password">Forgot your password?</a>
    <button type="submit" class="btn-primary" data-i18n="login">Login</button>
</form>


          <button class="google-btn" onclick="window.location.href='google-login.php'">
    <img src="assets/images/google-icon.svg" alt="Google">
    <span data-i18n="continue_with_google">Continue with Google</span>
</button>

            <p class="switch">
                <span data-i18n="dont_have_account">Don't have an account?</span>
                <a href="/Sushrusha/public/register.php" data-i18n="create_account">Create Account</a>
            </p>
        </div>
    </div>
<script>
const email = document.getElementById('email');
const password = document.getElementById('password');
const emailError = document.getElementById('emailError');
const passwordError = document.getElementById('passwordError');
const form = document.getElementById('loginForm');

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const passwordPattern = /^(?=.*[A-Za-z])(?=.*[\W_]).{6,}$/;
 // at least 6 characters

function validateEmail() {
    if (email.value === "") {
        email.classList.remove('valid', 'invalid');
        emailError.textContent = "";
    } else if (emailPattern.test(email.value)) {
        email.classList.add('valid');
        email.classList.remove('invalid');
        emailError.textContent = "";
    } else {
        email.classList.add('invalid');
        email.classList.remove('valid');
        emailError.textContent = "Enter a valid email";
    }
}

// function validatePassword() {
//     if (password.value === "") {
//         password.classList.remove('valid', 'invalid');
//         passwordError.textContent = "";
//     } else if (passwordPattern.test(password.value)) {
//         password.classList.add('valid');
//         password.classList.remove('invalid');
//         passwordError.textContent = "";
//     } else {
//         password.classList.add('invalid');
//         password.classList.remove('valid');
//         passwordError.textContent = "Password must be 6+ characters, with letters and a special character";
//     }
// }

email.addEventListener('input', validateEmail);
password.addEventListener('input', validatePassword);

form.addEventListener('submit', (e) => {
    validateEmail();
    validatePassword();
    if (!emailPattern.test(email.value) || !passwordPattern.test(password.value)) {
        e.preventDefault();
        alert('Please fix the errors before submitting.');
    }
});

// Initialize Language Selector and Translation System
document.addEventListener('DOMContentLoaded', () => {
    initLanguageSelector('#langSelectorPlaceholder');
    initTranslationSystem();
});
</script>

</body>
</html>