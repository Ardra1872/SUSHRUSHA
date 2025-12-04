<?php
session_start();
require '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Default role = patient
    $role = 'patient';

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
    $_SESSION['error'] = "Email already registered!";
    header("Location: login.html"); 
    exit;
} else {
    $stmt = $conn->prepare("INSERT INTO users (name, role, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $role, $email, $hashed_password);

    if($stmt->execute()){
        $_SESSION['success'] = "Registration successful!";
        header("Location: login.html"); // redirect to login page
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: register.html"); 
    }
    $stmt->close();
}

    
    $check->close();
}
$conn->close();
?>
