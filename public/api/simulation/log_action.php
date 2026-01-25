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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
