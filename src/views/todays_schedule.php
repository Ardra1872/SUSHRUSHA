<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$patient_id = $_SESSION['user_id'] ?? null;
if (!$patient_id) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$today = date('Y-m-d');
$now = date('H:i:00'); // truncate seconds

// SQL: fetch medicines scheduled today from NOW onwards
$sql = "
SELECT 
    ms.id AS schedule_id,
    m.id AS medicine_id,
    m.name,
    m.dosage_value AS dosage,
    ms.intake_time,
    IFNULL(
        CASE 
            WHEN dl.status = 'TAKEN' THEN 'Taken'
            WHEN dl.status = 'MISSED' THEN 'Missed'
            ELSE dl.status 
        END, 
    'Pending') AS status
FROM medicines m
JOIN medicine_schedule ms ON ms.medicine_id = m.id
LEFT JOIN dose_logs dl 
    ON dl.schedule_id = ms.id
    AND DATE(dl.log_time) = ?
WHERE m.patient_id = ?
  AND m.start_date <= ?
  AND (m.end_date IS NULL OR m.end_date = '0000-00-00' OR m.end_date >= ?)
  AND TIME(ms.intake_time) >= ? -- Show all upcoming for today
ORDER BY ms.intake_time ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'SQL Prepare Failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param("sssss", $today, $patient_id, $today, $today, $now);
$stmt->execute();

$result = $stmt->get_result();
$schedule = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'schedule' => $schedule
]);
?>