<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
$patient_id = $_SESSION['user_id'] ?? null;
if (!$patient_id) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Get POST data
$name = trim($_POST['medicine_name'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$compartment_raw = $_POST['compartment'] ?? '';
$compartment = intval($compartment_raw);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? null;
$intake_time = $_POST['intake_time'] ?? '';

// Validate required fields
if ($name === '' || $compartment_raw === '' || $start_date === '' || $intake_time === '') {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}


// Insert into medicines table
$sql = "INSERT INTO medicines (patient_id, name, dosage, compartment_number, start_date, end_date) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ississ", $patient_id, $name, $dosage, $compartment, $start_date, $end_date);

if ($stmt->execute()) {
    $medicine_id = $stmt->insert_id;

    // Insert into medicine_schedule
    $sql2 = "INSERT INTO medicine_schedule (medicine_id, intake_time) VALUES (?, ?)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("is", $medicine_id, $intake_time);

    if ($stmt2->execute()) {
        echo json_encode(["status" => "success"]);
        exit;
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save schedule"]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save medicine"]);
    exit;
}
?>
