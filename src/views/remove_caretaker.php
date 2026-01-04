<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../public/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

$patient_id = $_SESSION['user_id'];

// 1️⃣ Fetch caretaker_id before deletion
$stmt = $conn->prepare("SELECT caregiver_id FROM caregivers WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $caretaker_id = $res->fetch_assoc()['caregiver_id'];

    // 2️⃣ Delete from caregivers table
    $del = $conn->prepare("DELETE FROM caregivers WHERE patient_id = ?");
    $del->bind_param("i", $patient_id);
    $del->execute();

    // Optional: delete caretaker user
    $del_user = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del_user->bind_param("i", $caretaker_id);
    $del_user->execute();
}

header("Location: dashboard.php");
exit;
?>
