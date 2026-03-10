<?php
// public/api/simulation/get_state.php
header('Content-Type: application/json');
require '../../../src/config/db.php';

// 1. Get Simulation Config
session_start();

// Allow ESP32 via user_id OR browser via session
if (isset($_GET['user_id'])) {
    // ESP32 request
    $user_id = intval($_GET['user_id']);
} elseif (isset($_SESSION['user_id'])) {
    // Browser request - use active patient context for caretakers
    $user_id = $_SESSION['active_patient_id'] ?? $_SESSION['user_id'];
} else {
    echo json_encode([
        "error" => "Not logged in",
        "schedules" => []
    ]);
    exit;
}


if (isset($_GET['debug_user_id'])) $userId = $_GET['debug_user_id'];

$config = [];
$configRes = $conn->query("SELECT * FROM simulation_config");
while ($row = $configRes->fetch_assoc()) {
    $config[$row['config_key']] = $row['config_value'];
}
$gracePeriodInfo = intval($config['grace_period_minutes'] ?? 5);

// 2. Get Current Server Time (Simulatable)
date_default_timezone_set('Asia/Kolkata');
$currentTime = date('H:i');
// Allow override for simulation purposes via GET param ?time=HH:MM
if (isset($_GET['time'])) {
    $currentTime = $_GET['time'];
}

// 3. Get Scheduled Medicines for Today
// Logic similar to cron_reminders.php but we want ALL relevant schedules to show on the box
// For simplicity in this v1 simulation, we fetch EVERYTHING and filter in PHP or frontend
// Ideally, we filter by Day = Today.

$dayMap = [
    'Mon' => 'M', 'Tue' => 'T', 'Wed' => 'W', 'Thu' => 'Th', 'Fri' => 'F', 'Sat' => 'S', 'Sun' => 'Su'
];
$todayAbbr = $dayMap[date('D')];

$sql = "
    SELECT 
        ms.id AS schedule_id,
        ms.intake_time,
        m.name AS medicine_name,
        m.compartment_number,
        m.days,
        m.schedule_type,
        m.start_date,
        m.end_date,
        d.status AS log_status,
        d.log_time
    FROM medicines m
    LEFT JOIN medicine_schedule ms ON m.id = ms.medicine_id
    LEFT JOIN dose_logs d ON ms.id = d.schedule_id AND DATE(d.log_time) = CURDATE()
    WHERE m.patient_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

$stmt->execute();
$result = $stmt->get_result();
$schedules = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $shouldIncludeToday = true;
        
        // Schedule Check
        if ($row['schedule_id']) {
            $schedType = $row['schedule_type'];
            $daysJson = $row['days'];
            $todayDate = date('Y-m-d');
            $startDate = $row['start_date'];
            $endDate = $row['end_date'];

            if ($schedType === 'as_needed') {
                $shouldIncludeToday = false;
            } elseif ($schedType === 'custom' || $schedType === 'days') {
                $allowedDays = json_decode($daysJson, true);
                if (!is_array($allowedDays) || !in_array($todayAbbr, $allowedDays)) {
                    $shouldIncludeToday = false;
                }
            }
            
            if ($startDate && $todayDate < $startDate) $shouldIncludeToday = false;
            if ($endDate && $todayDate > $endDate) $shouldIncludeToday = false;
        } else {
            $shouldIncludeToday = false;
        }
        
        // Always include the medicine record so the slot isn't "Empty"
        $row['slot_id'] = intval($row['compartment_number']);

$row['intake_time_formatted'] = $row['intake_time'] 
    ? date('H:i', strtotime($row['intake_time'])) 
    : null;

// 🔥 NEW TIME-BASED DUE LOGIC
$isDueNow = false;

if ($shouldIncludeToday && $row['intake_time']) {

    $intakeTimestamp = strtotime(date('Y-m-d') . ' ' . $row['intake_time']);
    $currentTimestamp = strtotime(date('Y-m-d') . ' ' . $currentTime);
    $graceTimestamp = $intakeTimestamp + ($gracePeriodInfo * 60);

    if (
        $currentTimestamp >= $intakeTimestamp &&
        $currentTimestamp <= $graceTimestamp &&
        $row['log_status'] === null
    ) {
        $isDueNow = true;
    }
}

$row['is_due_today'] = $isDueNow;

        $schedules[] = $row;
    }
}

// 4. Get Buzzer State
$buzzerFile = __DIR__ . '/buzzer_state.json';
$buzzerState = 'off';
if (file_exists($buzzerFile)) {
    $stateData = json_decode(file_get_contents($buzzerFile), true);
    $buzzerState = $stateData['buzzer'] ?? 'off';
}

echo json_encode([
    'current_time' => $currentTime,
    'grace_period_minutes' => $gracePeriodInfo,
    'buzzer_state' => $buzzerState,
    'schedules' => $schedules
]);
?>
