<?php
// test_adherence.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "--- Aherence Logic Verification ---\n";

// Manual connection (proven to work)
$conn = mysqli_connect("localhost", "root", "", "sushrusha");
if (!$conn) {
    die("Connect failed: " . mysqli_connect_error());
}

// 1. Get a test user (we know ID 10 exists)
$userId = 10;
echo "Testing for User ID: $userId\n";

// ---------------------------------------------------------
// REPLICATING DASHBOARD LOGIC
// ---------------------------------------------------------

// 1. Count Total Medicines
$countMedStmt = $conn->prepare("SELECT COUNT(*) as total_meds FROM medicines WHERE patient_id = ?");
if (!$countMedStmt) die("Prepare failed (meds): " . $conn->error);

$countMedStmt->bind_param("i", $userId);
$countMedStmt->execute();
$medCountResult = $countMedStmt->get_result();
$totalMeds = $medCountResult->fetch_assoc()['total_meds'];
$countMedStmt->close();

echo "Total Medicines: $totalMeds\n";

// 2. Calculate Adherence
$adherencePct = 0;
$totalLogs = 0;
$hasLogs = false;

if ($totalMeds > 0) {
    $logQuery = "
        SELECT 
            COUNT(*) as total_attempts,
            SUM(CASE WHEN dl.status = 'TAKEN' THEN 1 ELSE 0 END) as taken_count
        FROM dose_logs dl
        JOIN medicine_schedule ms ON dl.schedule_id = ms.id
        JOIN medicines m ON ms.medicine_id = m.id
        WHERE m.patient_id = ?
    ";
    $logStmt = $conn->prepare($logQuery);
    if (!$logStmt) die("Prepare failed (logs): " . $conn->error);

    $logStmt->bind_param("i", $userId);
    $logStmt->execute();
    $logResult = $logStmt->get_result();
    $logData = $logResult->fetch_assoc();
    $logStmt->close();

    $totalLogs = intval($logData['total_attempts']);
    $takenCount = intval($logData['taken_count']);

    echo "Total Logs: $totalLogs\n";
    echo "Taken Count: $takenCount\n";

    if ($totalLogs > 0) {
        $adherencePct = round(($takenCount / $totalLogs) * 100);
        $hasLogs = true;
    }
}

echo "Adherence Percentage: $adherencePct%\n";
echo "Has Logs: " . ($hasLogs ? "Yes" : "No") . "\n";

// Display Logic Simulation
echo "\n--- Display Simulation ---\n";
if ($totalMeds == 0) {
    echo "State [No Medicines]: Show 'Start Tracking'\n";
} elseif (!$hasLogs) {
    echo "State [No Logs]: Show '--%'\n";
} else {
    echo "State [Active]: Show '$adherencePct%'\n";
}

$conn->close();
?>
