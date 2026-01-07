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

    <link rel="stylesheet" href="assets/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>



<body class="register-page">

<div class="auth-wrapper">

    <div class="auth-card">

        <h2>Create Account</h2>
        <p class="subtitle">Start managing your medicine schedule today</p>

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

        <p class="switch">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    </div>

</div>

<script>
const registerForm = document.getElementById('registerForm');
const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');

// Optional: add error spans dynamically
const nameError = document.createElement('span');
nameError.className = 'error';
nameInput.after(nameError);

const emailError = document.createElement('span');
emailError.className = 'error';
emailInput.after(emailError);

const passwordError = document.createElement('span');
passwordError.className = 'error';
passwordInput.after(passwordError);

// Validation patterns
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const passwordPattern = /^(?=.*[A-Za-z])(?=.*[\W_]).{6,}$/;

// Validation functions
function validateName() {
    if (nameInput.value.trim() === "") {
        nameInput.classList.add('invalid');
        nameInput.classList.remove('valid');
        nameError.textContent = "Name cannot be empty";
        return false;
    } else {
        nameInput.classList.add('valid');
        nameInput.classList.remove('invalid');
        nameError.textContent = "";
        return true;
    }
}

function validateEmail() {
    if (emailInput.value.trim() === "") {
        emailInput.classList.remove('valid', 'invalid');
        emailError.textContent = "";
        return false;
    } else if (emailPattern.test(emailInput.value)) {
        emailInput.classList.add('valid');
        emailInput.classList.remove('invalid');
        emailError.textContent = "";
        return true;
    } else {
        emailInput.classList.add('invalid');
        emailInput.classList.remove('valid');
        emailError.textContent = "Enter a valid email";
        return false;
    }
}

function validatePassword() {
    if (passwordInput.value.trim() === "") {
        passwordInput.classList.remove('valid', 'invalid');
        passwordError.textContent = "";
        return false;
    } else if (passwordPattern.test(passwordInput.value)) {
        passwordInput.classList.add('valid');
        passwordInput.classList.remove('invalid');
        passwordError.textContent = "";
        return true;
    } else {
        passwordInput.classList.add('invalid');
        passwordInput.classList.remove('valid');
        passwordError.textContent = "Password must be 6+ characters, with letters and a special character";
        return false;
    }
}

// Event listeners for live validation
nameInput.addEventListener('input', validateName);
emailInput.addEventListener('input', validateEmail);
passwordInput.addEventListener('input', validatePassword);

// Form submission
registerForm.addEventListener('submit', (e) => {
    const isNameValid = validateName();
    const isEmailValid = validateEmail();
    const isPasswordValid = validatePassword();

    if (!isNameValid || !isEmailValid || !isPasswordValid) {
        e.preventDefault();
        alert('Please fix the errors before submitting.');
    }
});
</script>



   
</body>
</html>