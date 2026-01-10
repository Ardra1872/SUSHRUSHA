<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get form data
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

// Validate new password fields
if (empty($new_password) || empty($confirm_password)) {
    $_SESSION['error'] = "New password and confirm password are required.";
    header("Location: profile.php");
    exit();
}

// Check password length
if (strlen($new_password) < 8) {
    $_SESSION['error'] = "New password must be at least 8 characters long.";
    header("Location: profile.php");
    exit();
}

// Check if passwords match
if ($new_password !== $confirm_password) {
    $_SESSION['error'] = "New password and confirm password do not match.";
    header("Location: profile.php");
    exit();
}

// Get current password from database to check if new password is different
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: profile.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if new password is the same as current password (verify new password against stored hash)
if (password_verify($new_password, $user['password'])) {
    $_SESSION['error'] = "New password must be different from your current password.";
    header("Location: profile.php");
    exit();
}

// Hash new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Password updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update password. Please try again.";
}

$stmt->close();
$conn->close();

header("Location: profile.php");
exit();
?>
