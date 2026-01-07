<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$patient_id = $_SESSION['user_id'] ?? null;
if (!$patient_id) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Fetch medicines + intake times
$sql = "
    SELECT m.id, m.name, m.dosage, m.compartment_number, m.start_date, m.end_date, s.intake_time
    FROM medicines m
    JOIN medicine_schedule s ON m.id = s.medicine_id
    WHERE m.patient_id = ?
    ORDER BY s.intake_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while ($row = $result->fetch_assoc()) {
    $medicines[] = $row;
}

echo json_encode($medicines);
?>
