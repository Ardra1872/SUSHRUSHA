<?php
session_start();
include '../config/db.php'; 

//  Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: ../../public/login.php");
    exit;
}

//  Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// Get patient ID from session email
$patient_email = $_SESSION['user_email'];

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $patient_email);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die("Patient not found in DB");
}

$patient = $res->fetch_assoc();
$patient_id = $patient['id'];

//  Get caretaker details from form
$name = $_POST['name'];
$email = $_POST['email'];
$relation = $_POST['relation'];

// Check if caretaker already exists
$stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();

    if ($row['role'] !== 'caretaker') {
        die("Email already belongs to a patient");
    }

    $caretaker_id = $row['id'];
} else {
    // Create caretaker
    $tempPassword = password_hash("caretaker@123", PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare(
        "INSERT INTO users (name, role, email, password)
         VALUES (?, 'caretaker', ?, ?)"
    );
    $stmt2->bind_param("sss", $name, $email, $tempPassword);

    if (!$stmt2->execute()) {
        die("Error creating caretaker: " . $stmt2->error);
    }

    $caretaker_id = $stmt2->insert_id;
}

//  Prevent duplicate patient-caretaker link
$check = $conn->prepare(
    "SELECT id FROM caregivers WHERE patient_id = ? AND caregiver_id = ?"
);
$check->bind_param("ii", $patient_id, $caretaker_id);
$check->execute();
$check_res = $check->get_result();

if ($check_res->num_rows === 0) {
    $link = $conn->prepare(
        "INSERT INTO caregivers (patient_id, caregiver_id, relation)
         VALUES (?, ?, ?)"
    );
    $link->bind_param("iis", $patient_id, $caretaker_id, $relation);

    if (!$link->execute()) {
        die("Error linking caretaker: " . $link->error);
    }
}


header("Location: dashboard.php");
exit;
