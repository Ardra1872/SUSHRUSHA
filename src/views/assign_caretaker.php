<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$patient_id = $_SESSION['user_id'];
$name       = trim($_POST['name']);
$email      = trim($_POST['email']);
$relation   = trim($_POST['relation']);

// 1️⃣ Check if caretaker already exists (prevent duplicates)
$stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt_check->bind_param("s", $email);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $caretaker_id = $result_check->fetch_assoc()['id'];
} else {
    // 2️⃣ Create caretaker user
    $password = password_hash("default123", PASSWORD_DEFAULT); // temporary password
    $stmt = $conn->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, 'caretaker', ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
    $caretaker_id = $conn->insert_id;
}

// 3️⃣ Insert into caregivers table
$stmt2 = $conn->prepare("INSERT INTO caregivers (patient_id, caregiver_id, relation) VALUES (?, ?, ?)");
$stmt2->bind_param("iis", $patient_id, $caretaker_id, $relation);
$stmt2->execute();

header("Location: dashboard.php");
exit();
?>
