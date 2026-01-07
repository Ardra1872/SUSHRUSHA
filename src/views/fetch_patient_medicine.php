<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

include '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caretaker') {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized"
    ]);
    exit;
}

$caretaker_id = $_SESSION['user_id'];

/* 1️⃣ Get assigned patient */
$sql = "
    SELECT patient_id 
    FROM caregivers 
    WHERE caregiver_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "empty",
        "message" => "No patient assigned"
    ]);
    exit;
}

$patient = $result->fetch_assoc();
$patient_id = $patient['patient_id'];

/* 2️⃣ Fetch medicines */
$sqlMeds = "
    SELECT 
        m.id,
        m.name,
        m.dosage,
        m.compartment_number,
        m.start_date,
        m.end_date,
        s.intake_time
    FROM medicines m
    LEFT JOIN medicine_schedule s ON m.id = s.medicine_id
    WHERE m.patient_id = ?
    ORDER BY s.intake_time ASC
";

$stmt = $conn->prepare($sqlMeds);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$res = $stmt->get_result();

$medicines = [];
while ($row = $res->fetch_assoc()) {
    $medicines[] = $row;
}
error_log(print_r($medicines, true));

echo json_encode([
    "status" => "success",
    "patient_id" => $patient_id,
    "medicines" => $medicines
]);
