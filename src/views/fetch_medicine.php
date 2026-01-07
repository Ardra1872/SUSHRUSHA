<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['active_patient_id'] ?? $_SESSION['user_id'];

// Fetch medicines
$stmt = $conn->prepare("
    SELECT m.id, m.name, m.medicine_type, m.dosage_value, m.schedule_type, m.days, m.reminder_type, 
           m.interval_hours, m.compartment_number, m.start_date, m.end_date,
           GROUP_CONCAT(ms.intake_time ORDER BY ms.intake_time SEPARATOR ', ') as times
    FROM medicines m
    LEFT JOIN medicine_schedule ms ON m.id = ms.medicine_id
    WHERE m.patient_id = ?
    GROUP BY m.id
    ORDER BY m.start_date ASC
");

$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$medicines = [];
while($row = $result->fetch_assoc()) {
    $row['days'] = $row['days'] ? json_decode($row['days']) : [];
    $row['times'] = $row['times'] ? explode(', ', $row['times']) : [];
    $medicines[] = $row;
}

echo json_encode([
    'status' => 'success',
    'medicines' => $medicines
]);
