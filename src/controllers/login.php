<?php
session_start();
require '../config/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare statement to fetch user
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password);
    $stmt->fetch();

    if($stmt->num_rows > 0){
        if(password_verify($password, $hashed_password)){
            // Login successful
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;

            // Redirect to dashboard or placeholder
            header("Location: ../views/dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Incorrect password!";
            header("Location: ../views/login.html");
            exit;
        }
    } else {
        $_SESSION['error'] = "User not found!";
        header("Location: ../views/login.html");
        exit;
    }

    $stmt->close();
}
$conn->close();
?>
