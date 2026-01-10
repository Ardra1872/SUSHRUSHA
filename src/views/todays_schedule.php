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
$one_hour_later = date('H:i:00', strtotime('+1 hour'));

// SQL: fetch medicines scheduled today AND within next 1 hour
$sql = "
SELECT 
    m.id AS medicine_id,
    m.name,
    m.dosage_value AS dosage,
    ms.intake_time,
    IFNULL(dl.status, 'Pending') AS status
FROM medicines m
JOIN medicine_schedule ms ON ms.medicine_id = m.id
LEFT JOIN dose_logs dl 
    ON dl.medicine_id = m.id
    AND dl.patient_id = m.patient_id
    AND DATE(dl.intake_datetime) = ?
WHERE m.patient_id = ?
  AND m.start_date <= ?
  AND (m.end_date IS NULL OR m.end_date = '0000-00-00' OR m.end_date >= ?)
  AND TIME(ms.intake_time) BETWEEN ? AND ?
ORDER BY ms.intake_time ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $today, $patient_id, $today, $today, $now, $one_hour_later);
$stmt->execute();

$result = $stmt->get_result();
$schedule = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'schedule' => $schedule
]);
?>