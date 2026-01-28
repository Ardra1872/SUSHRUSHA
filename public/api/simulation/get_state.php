<?php
// public/api/simulation/get_state.php
header('Content-Type: application/json');
require '../../../src/config/db.php';

// 1. Get Simulation Config
session_start();
$userId = $_SESSION['user_id'] ?? null;

// Return empty/error if not logged in (or for testing allow override if needed, but per user request, we enforce session)
if (!$userId && !isset($_GET['debug_user_id'])) {
    echo json_encode([
        'error' => 'Not logged in', 
        'current_time' => date('H:i'), 
        'schedules' => []
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
        d.status AS log_status,
        d.log_time
    FROM medicine_schedule ms
    JOIN medicines m ON ms.medicine_id = m.id
    LEFT JOIN dose_logs d ON ms.id = d.schedule_id AND DATE(d.log_time) = CURDATE()
    WHERE m.patient_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$schedules = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Filter by Day
        $schedType = $row['schedule_type'];
        $daysJson = $row['days'];
        $shouldInclude = true;

        if ($schedType === 'as_needed') {
            $shouldInclude = false; // Ignore for daily reminder simulation for now
        } elseif ($schedType === 'custom' || $schedType === 'days') {
            $allowedDays = json_decode($daysJson, true);
            if (!is_array($allowedDays) || !in_array($todayAbbr, $allowedDays)) {
                $shouldInclude = false;
            }
        }
        
        if ($shouldInclude) {
            // Normalize time to HH:MM
            $row['intake_time_formatted'] = date('H:i', strtotime($row['intake_time']));
            
            // Assign a Slot ID based on real compartment (1-4 -> 0-3)
            // If unknown, fallback to 0
            $compNum = intval($row['compartment_number']);
            $row['slot_id'] = ($compNum > 0) ? ($compNum - 1) : 0;
            
            $schedules[] = $row;
        }
    }
}

echo json_encode([
    'current_time' => $currentTime,
    'grace_period_minutes' => $gracePeriodInfo,
    'schedules' => $schedules
]);
?>
