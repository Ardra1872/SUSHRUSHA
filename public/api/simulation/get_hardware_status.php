<?php
// public/api/simulation/get_hardware_status.php
header('Content-Type: application/json');
require '../../../src/config/db.php';

// Accept user_id directly for hardware simplicity
$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['error' => 'Missing user_id', 'active_slots' => []]);
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$now = date('H:i');
$todayDate = date('Y-m-d');

// 1. Get Grace Period
$configRes = $conn->query("SELECT config_value FROM simulation_config WHERE config_key = 'grace_period_minutes'");
$graceRow = $configRes->fetch_assoc();
$graceMinutes = intval($graceRow['config_value'] ?? 5);

// 2. Fetch Todays Schedules
// We join with dose_logs to check if already taken today
$sql = "
    SELECT 
        ms.id AS schedule_id,
        ms.intake_time,
        m.compartment_number,
        d.status AS log_status
    FROM medicine_schedule ms
    JOIN medicines m ON ms.medicine_id = m.id
    LEFT JOIN dose_logs d ON ms.id = d.schedule_id AND DATE(d.log_time) = ?
    WHERE m.patient_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $todayDate, $userId);
$stmt->execute();
$result = $stmt->get_result();

$activeSlots = [];

while ($row = $result->fetch_assoc()) {
    // If already TAKEN or MISSED, skip
    if ($row['log_status']) continue;

    $intakeTime = date('H:i', strtotime($row['intake_time']));
    $endTime = date('H:i', strtotime($row['intake_time'] . " + $graceMinutes minutes"));

    // Check if current time is within window
    // Note: Simple string comparison works for HH:MM format
    if ($now >= $intakeTime && $now <= $endTime) {
        $activeSlots[] = intval($row['compartment_number']);
    }
}

echo json_encode([
    'current_time' => $now,
    'active_slots' => array_unique($activeSlots)
]);
?>
