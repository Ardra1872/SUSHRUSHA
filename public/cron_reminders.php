<?php
// cron_reminders.php
// Access this file via browser or cron job to trigger reminder checks.
// Recommended frequency: Every minute.

require '../src/config/db.php';
require '../src/helpers/sendReminderEmail.php';

// Set timezone to match your users' location
date_default_timezone_set('Asia/Kolkata'); 

// Allow manual override for testing: ?time=11:41
if (isset($_GET['time'])) {
    $current_time = $_GET['time'];
    echo "<p style='color:blue;'><strong>Simulating Time:</strong> " . $current_time . "</p>";
} else {
    $current_time = date('H:i');
    echo "<p><strong>Current Server Time (Asia/Kolkata):</strong> " . $current_time . "</p>";
}

// 1. Check if we have any connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Dump all schedules for verification
echo "<h3>All Scheduled Medicines in DB:</h3>";
$dumpSql = "SELECT ms.id, ms.intake_time, m.name, u.email 
            FROM medicine_schedule ms 
            LEFT JOIN medicines m ON ms.medicine_id = m.id 
            LEFT JOIN users u ON m.patient_id = u.id";
$dumpRes = $conn->query($dumpSql);

if ($dumpRes && $dumpRes->num_rows > 0) {
    echo "<table border='1' cellspacing='0' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Intake Time (Raw)</th><th>Formatted (%H:%i)</th><th>Medicine</th><th>User Email</th><th>Match Current?</th></tr>";
    while ($row = $dumpRes->fetch_assoc()) {
        // Formatted time check
        $rawTime = $row['intake_time'];
        $formattedTime = date('H:i', strtotime($rawTime));
        $isMatch = ($formattedTime === $current_time) ? "<span style='color:green; font-weight:bold;'>YES</span>" : "NO";
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$rawTime}</td>";
        echo "<td>{$formattedTime}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$isMatch}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No entries found in <code>medicine_schedule</code> table.</p>";
}

echo "<hr>";

// 3. Run the actual logic
echo "<h3>Processing Reminders...</h3>";

$sql = "
    SELECT 
        u.name AS user_name, 
        u.email AS user_email, 
        m.name AS medicine_name, 
        m.dosage_value, 
        m.dosage_unit, 
        ms.intake_time,
        m.days,
        m.schedule_type
    FROM medicine_schedule ms
    JOIN medicines m ON ms.medicine_id = m.id
    JOIN users u ON m.patient_id = u.id
    WHERE DATE_FORMAT(ms.intake_time, '%H:%i') = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $current_time);
$stmt->execute();
$result = $stmt->get_result();

// DAY MAPPING
// date('D') returns: Mon, Tue, Wed, Thu, Fri, Sat, Sun
// DB stores: M, T, W, Th, F, S, Su ? Or JSON?
// Let's assume frontend sent ["M", "T", "W"] or similar.
// We need to map PHP day to stored day.
$dayMap = [
    'Mon' => 'M', 'Tue' => 'T', 'Wed' => 'W', 'Thu' => 'Th', 'Fri' => 'F', 'Sat' => 'S', 'Sun' => 'Su'
];
$todayAbbr = $dayMap[date('D')];

$count = 0;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $toEmail = $row['user_email'];
        $userName = $row['user_name'];
        $medicineName = $row['medicine_name'];
        $dosage = $row['dosage_value'] . ' ' . $row['dosage_unit'];
        $time = $row['intake_time'];
        
        // --- FREQUENCY CHECK ---
        $schedType = $row['schedule_type']; // daily, weekly, custom, as_needed
        $daysJson = $row['days'];
        
        $shouldSend = true;
        
        if ($schedType === 'as_needed') {
            $shouldSend = false;
        } elseif ($schedType === 'custom' || $schedType === 'days') {
            // Check if today is in the list
            $allowedDays = json_decode($daysJson, true);
            if (!is_array($allowedDays) || !in_array($todayAbbr, $allowedDays)) {
                $shouldSend = false;
            }
        }
        // Daily: always send. Weekly: assume similar logic to custom or handled elsewhere, but for now treating 'daily' as all days.

        if (!$shouldSend) {
             echo "Skipping <strong>$medicineName</strong> (Not scheduled for today: $todayAbbr)... <br>";
             continue;
        }
        // --- END FREQUENCY CHECK ---

        echo "Attempting to send email to <strong>$userName</strong> ($toEmail) for <strong>$medicineName</strong>... ";

        // Temporarily simplified logic to debug
        if (function_exists('sendReminderEmail')) {
             if (sendReminderEmail($toEmail, $userName, $medicineName, $dosage, $time)) {
                echo "<span style='color:green;'>SUCCESS: Email sent.</span><br>";
                $count++;
            } else {
                echo "<span style='color:red;'>FAILED: PHPMailer returned false. Check php error logs.</span><br>";
            }
        } else {
            echo "<span style='color:red;'>FATAL: sendReminderEmail function not found.</span><br>";
        }
    }
} else {
    echo "No medicines match the current time ($current_time).<br>";
}

echo "<br><strong>Done. Processed $count reminders.</strong>";
?>
