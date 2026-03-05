<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');




/* -------------------------------------------------
   1️⃣ AUTH & PATIENT CONTEXT CHECK
------------------------------------------------- */

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$patient_id = $_SESSION['active_patient_id'] ?? $_SESSION['user_id'];

if (!$patient_id) {
    echo json_encode([
        'status'=>'error',
        'message'=>'No active patient selected'
    ]);
    exit;
}


/* 🔍 DEBUG (optional – remove later) */
error_log("ADD_MEDICINE user_id = " . $_SESSION['user_id']);
error_log("ADD_MEDICINE active_patient_id = " . $patient_id);

/* -------------------------------------------------
   2️⃣ COLLECT INPUT (NEW FRONTEND)
------------------------------------------------- */

$name              = trim($_POST['medName'] ?? '');

 $medicine_type = $_POST['form'] ?? 'pill';
$dosage_value  = $_POST['dosage'] ?? '';
$reminder_type = $_POST['reminder_mode'] ?? 'fixed';


$dosage_unit = ''; // if you don’t have separate units


$schedule_type     = $_POST['frequency'] ?? 'Daily'; // Using 'frequency' from form
if ($schedule_type === 'Specific Days') $schedule_type = 'days'; // Map to DB enum if needed, or keep text
// Note: DB likely has 'daily','weekly','custom'. Lets align.
// If form sends 'Daily', 'Weekly', 'Specific Days', 'As Needed'
// and DB expects 'daily', 'weekly', 'custom'? 
// Let's assume text for now or map it safely:
$schedule_map = [
    'Daily' => 'daily',
    'Weekly' => 'weekly',
    'Specific Days' => 'custom',
    'As Needed' => 'as_needed'
];
$schedule_type_db = $schedule_map[$schedule_type] ?? 'daily';

$selected_days = $_POST['specific_days'] ?? [];

$days_json         = !empty($selected_days) ? json_encode($selected_days) : null;

  // fixed | interval

$interval_hours = $_POST['intervalHours'] ?? null;

$start_date        = $_POST['start_date'] ?? null;
$end_date          = $_POST['end_date'] ?? null;

$compartment       = intval($_POST['compartment_number'] ?? 1);
$times             = $_POST['times'] ?? []; // array of HH:MM

/* -------------------------------------------------
   3️⃣ STRICT VALIDATION
------------------------------------------------- */

if ($name === '') {
    echo json_encode(['status'=>'error','message'=>'Medicine name is required']);
    exit;
}

if (!$start_date) {
    echo json_encode(['status'=>'error','message'=>'Start date is required']);
    exit;
}

if ($reminder_type === 'fixed' && empty($times)) {
    echo json_encode(['status'=>'error','message'=>'At least one reminder time is required']);
    exit;
}

if ($reminder_type === 'interval') {
    if (!$interval_hours || $interval_hours <= 0) {
        echo json_encode(['status'=>'error','message'=>'Valid interval hours required']);
        exit;
    }
}

/* -------------------------------------------------
   4️⃣ INSERT INTO MEDICINES
------------------------------------------------- */

$stmt = $conn->prepare("
    INSERT INTO medicines (
        patient_id,
        name,
        medicine_type,
        dosage_value,
        dosage_unit,
        schedule_type,
        days,
        reminder_type,
        interval_hours,
        compartment_number,
        start_date,
        end_date
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "isssssssiiss",
    $patient_id,
    $name,
    $medicine_type,
    $dosage_value,
    $dosage_unit,
    $schedule_type_db,
    $days_json,
    $reminder_type,
    $interval_hours,
    $compartment,
    $start_date,
    $end_date
);

if (!$stmt->execute()) {
    echo json_encode([
        'status'=>'error',
        'message'=>'Medicine insert failed: '.$stmt->error
    ]);
    exit;
}

$medicine_id = $stmt->insert_id;

/* -------------------------------------------------
   5️⃣ INSERT REMINDER TIMES (FIXED TYPE ONLY)
------------------------------------------------- */

if ($schedule_type === 'As Needed') {
    // DO NOT INSERT into medicine_schedule
    // Logic: As Needed medicines have no fixed reminder times.
} elseif ($reminder_type === 'fixed') {

    $timeStmt = $conn->prepare("
        INSERT INTO medicine_schedule (medicine_id, intake_time)
        VALUES (?, ?)
    ");
    
    $doseStmt = $conn->prepare("
        INSERT INTO doses (patient_id, manual_medicine_id, scheduled_datetime, status)
        VALUES (?, ?, ?, 'upcoming')
    ");

    foreach ($times as $time) {
        if (!$time) continue;
        $timeStmt->bind_param("is", $medicine_id, $time);
        $timeStmt->execute();

        // Generate Doses for the next 30 days (as a start)
        // Or until end_date if specified
        $start = new DateTime($start_date);
        $end = (!empty($end_date) && $end_date !== '0000-00-00') ? new DateTime($end_date) : (clone $start)->modify('+30 days');
        $end->modify('+1 day'); // inclusive

        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $scheduledDT = "$dateStr $time:00";

            // Only if it's correct day of week (if weekly/specific days)
            $isScheduled = false;
            if ($schedule_type_db === 'daily') {
                $isScheduled = true;
            } elseif ($schedule_type_db === 'weekly') {
                if ($date->format('N') === (new DateTime($start_date))->format('N')) {
                    $isScheduled = true;
                }
            } elseif ($schedule_type_db === 'custom') {
                $daysArr = json_decode($days_json ?? '[]', true);
                $dayName = $date->format('D'); // Mon, Tue...
                if (in_array($dayName, $daysArr) || in_array($date->format('l'), $daysArr)) {
                    $isScheduled = true;
                }
            }

            if ($isScheduled) {
                $doseStmt->bind_param("iis", $patient_id, $medicine_id, $scheduledDT);
                $doseStmt->execute();
            }
        }
    }
    $timeStmt->close();
    $doseStmt->close();
} elseif ($reminder_type === 'interval') {
    // Generate times server-side
    // inputs: intervalStart, intervalHours
    $start_time_str = $_POST['intervalStart'] ?? '08:00';
    $interval_h = intval($_POST['intervalHours'] ?? 8);
    
    if ($interval_h < 1) $interval_h = 1;

    $generated_times = [];
    $current = strtotime("2000-01-01 " . $start_time_str);
    $end_of_day = strtotime("2000-01-01 23:59:00");
    
    // Simple logic: fill the day starting from start time
    // If you want it to wrap around 24h, logic is more complex. 
    // Standard expectation: Wake up -> Sleep cycle.
    
    while ($current <= $end_of_day) {
        $generated_times[] = date('H:i', $current);
        $current = strtotime("+$interval_h hours", $current);
    }
    
    $timeStmt = $conn->prepare("
        INSERT INTO medicine_schedule (medicine_id, intake_time)
        VALUES (?, ?)
    ");
    
    $doseStmt = $conn->prepare("
        INSERT INTO doses (patient_id, manual_medicine_id, scheduled_datetime, status)
        VALUES (?, ?, ?, 'upcoming')
    ");

    foreach ($generated_times as $time) {
        $timeStmt->bind_param("is", $medicine_id, $time);
        $timeStmt->execute();
        
        // Generate Doses for the next 30 days (as a start)
        // Or until end_date if specified
        $start = new DateTime($start_date);
        $end = (!empty($end_date) && $end_date !== '0000-00-00') ? new DateTime($end_date) : (clone $start)->modify('+30 days');
        $end->modify('+1 day'); // inclusive

        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $scheduledDT = "$dateStr $time:00";
            
            // Only if it's correct day of week (if weekly/specific days)
            $isScheduled = false;
            if ($schedule_type_db === 'daily') {
                $isScheduled = true;
            } elseif ($schedule_type_db === 'weekly') {
                if ($date->format('N') === (new DateTime($start_date))->format('N')) {
                    $isScheduled = true;
                }
            } elseif ($schedule_type_db === 'custom') {
                $daysArr = json_decode($days_json ?? '[]', true);
                $dayName = $date->format('D'); // Mon, Tue...
                if (in_array($dayName, $daysArr) || in_array($date->format('l'), $daysArr)) {
                    $isScheduled = true;
                }
            }

            if ($isScheduled) {
                $doseStmt->bind_param("iis", $patient_id, $medicine_id, $scheduledDT);
                $doseStmt->execute();
            }
        }
    }
    $timeStmt->close();
    $doseStmt->close();
}

/* -------------------------------------------------
   6️⃣ SUCCESS RESPONSE
------------------------------------------------- */

echo json_encode([
    'status'  => 'success',
    'message' => 'Medicine added successfully'
]);