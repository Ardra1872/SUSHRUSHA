<?php
// public/api/simulation/report_intake.php
header('Content-Type: application/json');
require '../../../src/config/db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;

if ($user_id <= 0 || $slot_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid parameters"]);
    exit;
}

// 1. Map slot_id to compartment_number (1:1)
// 2. Find the medicine_id and its schedule for today
$today = date('Y-m-d');
$currentTime = date('H:i');

$sql = "
    SELECT ms.id as schedule_id, m.name
    FROM medicines m
    JOIN medicine_schedule ms ON m.id = ms.medicine_id
    WHERE m.patient_id = ? AND m.compartment_number = ?
    AND (m.start_date <= ? OR m.start_date IS NULL)
    AND (m.end_date >= ? OR m.end_date IS NULL OR m.end_date = '0000-00-00')
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $user_id, $slot_id, $today, $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "error" => "No scheduled medicine found for Slot $slot_id today"]);
    exit;
}

$schedule_id = $row['schedule_id'];
$med_name = $row['name'];

// 3. Check if already logged today
$checkSql = "SELECT id FROM dose_logs WHERE schedule_id = ? AND DATE(log_time) = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $schedule_id, $today);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows > 0) {
    echo json_encode(["success" => true, "message" => "Already taken", "slot_id" => $slot_id]);
    exit;
}

// 4. Insert log
$insertSql = "INSERT INTO dose_logs (schedule_id, status, log_time) VALUES (?, 'Taken', CURRENT_TIMESTAMP)";
$insertStmt = $conn->prepare($insertSql);
$insertStmt->bind_param("i", $schedule_id);

if ($insertStmt->execute()) {
    echo json_encode([
        "success" => true, 
        "slot_id" => $slot_id, 
        "medicine" => $med_name,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Database error: " . $conn->error]);
}
?>
