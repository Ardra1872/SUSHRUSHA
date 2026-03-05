<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$patient_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['schedule_id']) || !isset($input['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameter']);
    exit;
}

$schedule_id = intval($input['schedule_id']);
$status = $input['status']; // 'TAKEN' or 'MISSED'

if (!in_array($status, ['TAKEN', 'MISSED'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
    exit;
}

// Check if schedule belongs to patient
$checkSql = "
    SELECT m.patient_id 
    FROM medicine_schedule ms 
    JOIN medicines m ON m.id = ms.medicine_id 
    WHERE ms.id = ? AND m.patient_id = ?
";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("ii", $schedule_id, $patient_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid schedule ID']);
    exit;
}

// Check if already logged for today
// Using log_time DATE(log_time) = CURDATE() and schedule_id
$today = date('Y-m-d');
$logCheck = $conn->prepare("SELECT id FROM dose_logs WHERE schedule_id = ? AND DATE(log_time) = ?");
$logCheck->bind_param("is", $schedule_id, $today);
$logCheck->execute();
if ($logCheck->get_result()->num_rows > 0) {
    // Update existing? Or Error? User might want to change status. Let's Allow update.
    $updateSql = "UPDATE dose_logs SET status = ?, log_time = CURRENT_TIMESTAMP WHERE schedule_id = ? AND DATE(log_time) = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("sis", $status, $schedule_id, $today);
    
    if ($updateStmt->execute()) {
        // Sync with doses table
        syncWithDosesTable($conn, $schedule_id, $status, $patient_id);
        echo json_encode(['status' => 'success', 'message' => 'Dose updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    // Insert new
    // We assume logs table has (schedule_id, status, log_time)
    $insertSql = "INSERT INTO dose_logs (schedule_id, status) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("is", $schedule_id, $status);

    if ($insertStmt->execute()) {
        // Sync with doses table
        syncWithDosesTable($conn, $schedule_id, $status, $patient_id);
        echo json_encode(['status' => 'success', 'message' => 'Dose logged']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}

function syncWithDosesTable($conn, $schedule_id, $status, $patient_id) {
    // 1. Get manual_medicine_id and scheduled time from the old system
    $infoSql = "
        SELECT ms.medicine_id, ms.intake_time 
        FROM medicine_schedule ms
        WHERE ms.id = ?
    ";
    $infoStmt = $conn->prepare($infoSql);
    $infoStmt->bind_param("i", $schedule_id);
    $infoStmt->execute();
    $info = $infoStmt->get_result()->fetch_assoc();
    if (!$info) return;

    $manualMedId = $info['medicine_id'];
    $intakeTime = $info['intake_time'];
    $today = date('Y-m-d');
    $scheduledDT = "$today $intakeTime";

    // 2. Map status to the system's enum (lowercase)
    $newStatus = strtolower($status); // 'taken' or 'missed'

    // 3. Find and update the corresponding dose in the unified doses table
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
