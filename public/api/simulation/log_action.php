<?php
// public/api/simulation/log_action.php
header('Content-Type: application/json');
require '../../../src/config/db.php';
session_start();

// Expect JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

$scheduleId = $input['schedule_id'] ?? null;
$status = $input['status'] ?? null; // TAKEN or MISSED

if (!$scheduleId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing schedule_id or status']);
    exit;
}

// Check if already logged for today
$checkSql = "SELECT id FROM dose_logs WHERE schedule_id = ? AND DATE(log_time) = CURDATE()";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $scheduleId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already logged for today']);
    exit;
}

// Insert Log
$sql = "INSERT INTO dose_logs (schedule_id, status, log_time, simulated_action_time) VALUES (?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $scheduleId, $status);

if ($stmt->execute()) {
    // 🔍 Sync with unified 'doses' table
    $patient_id = $_SESSION['user_id'];
    syncWithDosesTable($conn, $scheduleId, $status, $patient_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

function syncWithDosesTable($conn, $schedule_id, $status, $patient_id) {
    // 1. Get manual_medicine_id and scheduled time
    $infoSql = "SELECT medicine_id, intake_time FROM medicine_schedule WHERE id = ?";
    $infoStmt = $conn->prepare($infoSql);
    $infoStmt->bind_param("i", $schedule_id);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    if (!$info) return;

    $manualMedId = $info['medicine_id'];
    $intakeTime = $info['intake_time'];
    $today = date('Y-m-d');
    $scheduledDT = "$today $intakeTime";

    $newStatus = strtolower($status); // 'taken' or 'missed'

    // 2. Update the unified doses table
    $syncSql = "
        UPDATE doses 
        SET status = ?, taken_at = CASE WHEN ? = 'taken' THEN CURRENT_TIMESTAMP ELSE taken_at END
        WHERE patient_id = ? 
          AND manual_medicine_id = ? 
          AND scheduled_datetime = ?
    ";
    $syncStmt = $conn->prepare($syncSql);
    $syncStmt->bind_param("ssiis", $newStatus, $newStatus, $patient_id, $manualMedId, $scheduledDT);
    $syncStmt->execute();
    $syncStmt->close();
}
?>
