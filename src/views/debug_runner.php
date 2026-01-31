<?php
// Mock Session
session_start();
$_SESSION['user_id'] = 4; // Assuming 4 is caretaker ID, based on logs or guess. 
// Wait, I don't know the caretaker ID. The user's files don't show it directly.
// But I can query the DB.
// Let's assume the user IS the caretaker.
// I'll try to find a valid caretaker-patient pair.

include '../config/db.php';

// Find a caretaker
$res = $conn->query("SELECT caregiver_id, patient_id FROM caregivers LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $_SESSION['user_id'] = $row['caregiver_id'];
    echo "Simulating Caretaker ID: " . $row['caregiver_id'] . "\n";
} else {
    echo "No caregivers found.\n";
    exit;
}

$_GET['action'] = 'getTodaysSchedule';

// Include the file (it will run the logic)
// Note: caretaker.php has session_start() at top, might warn.
// And it includes db.php which I already included. 
// I should just adapt the logic here.
// But to test the ACTUAL file, I should include it.
// I'll suppress warnings.
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require 'caretaker.php';
?>
