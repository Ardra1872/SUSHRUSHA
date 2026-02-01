<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    // Try to auto-login as user 1 for debugging if no session
    // $_SESSION['user_id'] = 1; 
    die("Not logged in. Please log in as a patient first.");
}

$patientId = $_SESSION['user_id'];
echo "Patient ID: $patientId\n";

require '../config/db.php';

// 1. Check linked caretakers
$stmt = $conn->prepare("SELECT caregiver_id, relation FROM caregivers WHERE patient_id = ?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$res = $stmt->get_result();

$caretakers = [];
echo "Linked Caretakers:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
    $caretakers[] = $row['caregiver_id'];
}

if (empty($caretakers)) {
    die("No caretakers linked. Cannot test sending.\n");
}

$targetCaretaker = $caretakers[0];
echo "Attempting to send message to Caretaker ID: $targetCaretaker\n";

// 2. Test Insert
$message = "Test message from debug script " . date('H:i:s');
$sql = "INSERT INTO messages (patient_id, caretaker_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $conn->error . "\n");
}

$stmt->bind_param("iis", $patientId, $targetCaretaker, $message);

if ($stmt->execute()) {
    echo "SUCCESS: Message inserted. ID: " . $stmt->insert_id . "\n";
} else {
    echo "ERROR: Execute failed: " . $stmt->error . "\n";
}
?>
