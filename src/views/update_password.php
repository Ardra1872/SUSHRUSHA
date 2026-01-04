<?php
session_start();
include '../config/db.php'; 


if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $new_password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Update password and first_login flag
    $stmt = $conn->prepare("UPDATE users SET password=?, first_login=0 WHERE id=?");
    $stmt->bind_param("si", $new_password, $user_id);
    $stmt->execute();
    $stmt->close();

    // Update session flag
    $_SESSION['first_login'] = 0;

    $_SESSION['success'] = "Password updated successfully!";
    header("Location: caretaker_dashboard.php");
    exit;
}
?>
