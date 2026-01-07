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

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
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


$schedule_type     = $_POST['schedule_type'] ?? 'daily';  // daily | weekly | custom
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
    $schedule_type,
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

if ($reminder_type === 'fixed') {

    $timeStmt = $conn->prepare("
        INSERT INTO medicine_schedule (medicine_id, intake_time)
        VALUES (?, ?)
    ");

    foreach ($times as $time) {
        if (!$time) continue;
        $timeStmt->bind_param("is", $medicine_id, $time);
        $timeStmt->execute();
    }
}

/* -------------------------------------------------
   6️⃣ SUCCESS RESPONSE
------------------------------------------------- */

echo json_encode([
    'status'  => 'success',
    'message' => 'Medicine added successfully'
]);
