<?php
// debug_adherence.php
require '../src/config/db.php';

// Check for a specific patient (Ardra S Nair from screenshot, let's find her ID)
$userRes = $conn->query("SELECT id, name FROM users WHERE name LIKE '%Ardra%'");
while($user = $userRes->fetch_assoc()) {
    $userId = $user['id'];
    $userName = $user['name'];
    echo "<h2>Debug Adherence for $userName (ID: $userId)</h2>";

    // 1. Check medicine_schedule
    echo "<h3>medicine_schedule content:</h3>";
    $res = $conn->query("SELECT ms.*, m.name as mname FROM medicine_schedule ms JOIN medicines m ON ms.medicine_id = m.id WHERE m.patient_id = $userId");
    echo "<table border='1'><tr><th>ID</th><th>Med Name</th><th>Intake Time</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['mname']}</td><td>'{$row['intake_time']}'</td></tr>";
    }
    echo "</table>";

    // 2. Check doses for today
    $today = date('Y-m-d');
    echo "<h3>doses content for today ($today):</h3>";
    $res = $conn->query("SELECT * FROM doses WHERE patient_id = $userId AND DATE(scheduled_datetime) = '$today'");
    echo "<table border='1'><tr><th>ID</th><th>Manual Med ID</th><th>Scheduled DT</th><th>Status</th><th>Taken At</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['manual_medicine_id']}</td><td>'{$row['scheduled_datetime']}'</td><td>{$row['status']}</td><td>{$row['taken_at']}</td></tr>";
    }
    echo "</table>";

    // 3. Check dose_logs
    echo "<h3>dose_logs content for today:</h3>";
    $res = $conn->query("SELECT dl.*, m.name as mname FROM dose_logs dl JOIN medicine_schedule ms ON dl.schedule_id = ms.id JOIN medicines m ON ms.medicine_id = m.id WHERE m.patient_id = $userId AND DATE(dl.log_time) = '$today'");
    echo "<table border='1'><tr><th>ID</th><th>Sched ID</th><th>Med Name</th><th>Status</th><th>Log Time</th></tr>";
    while($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['schedule_id']}</td><td>{$row['mname']}</td><td>{$row['status']}</td><td>{$row['log_time']}</td></tr>";
    }
    echo "</table>";
}

?>
