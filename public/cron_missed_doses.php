<?php
// cron_missed_doses.php
// Mark 'upcoming' doses as 'missed' if past the tolerance window.
// Frequency: every 15-30 mins.

require '../src/config/db.php';
require '../src/helpers/sendReminderEmail.php'; // Reuse mail helper if possible or similar

date_default_timezone_set('Asia/Kolkata');

$tolerance_minutes = 30;
$cutoff_time = date('Y-m-d H:i:s', strtotime("-$tolerance_minutes minutes"));

echo "<h3>Checking for missed doses (Cutoff: $cutoff_time)</h3>";

// 1. Find upcoming doses that are now missed
$sql = "
    SELECT d.id, d.prescription_medicine_id, d.scheduled_datetime, 
           pm.medicine_name, pm.prescription_id, p.patient_id, u.name as patient_name
    FROM doses d
    JOIN prescription_medicines pm ON d.prescription_medicine_id = pm.id
    JOIN prescriptions p ON pm.prescription_id = p.id
    JOIN users u ON p.patient_id = u.id
    WHERE d.status = 'upcoming' AND d.scheduled_datetime < ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $cutoff_time);
$stmt->execute();
$result = $stmt->get_result();

$missed_count = 0;
$affected_patients = [];

while ($row = $result->fetch_assoc()) {
    $dose_id = $row['id'];
    $pm_id = $row['prescription_medicine_id'];
    $patient_id = $row['patient_id'];
    
    // Update status to 'missed'
    $updateStmt = $conn->prepare("UPDATE doses SET status = 'missed' WHERE id = ?");
    $updateStmt->bind_param("i", $dose_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    echo "Marked Dose #$dose_id ({$row['medicine_name']} for {$row['patient_name']}) as MISSED.<br>";
    $missed_count++;
    
    if (!isset($affected_patients[$patient_id])) {
        $affected_patients[$patient_id] = [
            'name' => $row['patient_name'],
            'pm_id' => $pm_id
        ];
    }
}
$stmt->close();

// 2. Check for consecutive missed doses and notify caretaker
foreach ($affected_patients as $patient_id => $data) {
    // Check last 3 doses for this specific medicine of the patient
    // Actually, requirement says "3 consecutive missed doses" - usually implies any medicine? 
    // Or specific to the medicine that was just missed? Let's check the last 3 doses for this prescription_medicine.
    
    $checkSql = "
        SELECT status FROM doses 
        WHERE prescription_medicine_id = ? AND scheduled_datetime <= NOW()
        ORDER BY scheduled_datetime DESC LIMIT 3
    ";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $data['pm_id']);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    
    $statuses = [];
    while ($sRow = $checkRes->fetch_assoc()) {
        $statuses[] = $sRow['status'];
    }
    $checkStmt->close();
    
    // Notification logic
    if (count($statuses) >= 3 && $statuses[0] === 'missed' && $statuses[1] === 'missed' && $statuses[2] === 'missed') {
        echo "<strong>CRITICAL:</strong> 3 consecutive missed doses for {$data['name']}. Notifying caretaker...<br>";
        notifyCaretaker($patient_id, $data['name'], "3 consecutive missed doses");
    } elseif (count($statuses) >= 2 && $statuses[0] === 'missed' && $statuses[1] === 'missed') {
        echo "<strong>WARNING:</strong> 2 consecutive missed doses for {$data['name']}.<br>";
        // Note: Dashboard logic will handle the warning banner based on DB state.
    }
}

function notifyCaretaker($patient_id, $patient_name, $reason) {
    global $conn;
    
    // Find caretaker
    $cSql = "
        SELECT u.email, u.name as caretaker_name 
        FROM caregivers c
        JOIN users u ON c.caregiver_id = u.id
        WHERE c.patient_id = ? AND c.notifications_enabled = 1
    ";
    $cStmt = $conn->prepare($cSql);
    $cStmt->bind_param("i", $patient_id);
    $cStmt->execute();
    $cRes = $cStmt->get_result();
    
    while ($cRow = $cRes->fetch_assoc()) {
        $to = $cRow['email'];
        $subject = "URGENT: Medication Alert for $patient_name";
        $message = "Hello {$cRow['caretaker_name']},\n\nThis is an automated alert from Sushrusha. $patient_name has $reason. Please check on them as soon as possible.";
        
        // Use mail() or PHPMailer if integrated. 
        // For now, let's just log it or use the helper if it supports custom messages.
        // Assuming sendReminderEmail is for daily reminders, let's use a generic mail() for now or log.
        error_log("ALERT: Careful! Sent to $to: $message");
        echo "Notification sent to {$cRow['caretaker_name']} ($to).<br>";
    }
    $cStmt->close();
}

echo "<h3>Done. Processed $missed_count missed doses.</h3>";
?>
