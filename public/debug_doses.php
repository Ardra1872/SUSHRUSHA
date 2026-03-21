<?php
// /tmp/debug_doses.php
require '../src/config/db.php';

session_start();
$userId = $_SESSION['user_id'] ?? 4; // Use a default for CLI if needed, or assume browser execution

echo "<h2>Debug Doses for User ID: $userId</h2>";

// 1. Check medicine_schedule
echo "<h3>medicine_schedule content:</h3>";
$res = $conn->query("SELECT ms.*, m.name FROM medicine_schedule ms JOIN medicines m ON ms.medicine_id = m.id WHERE m.patient_id = $userId");
echo "<table border='1'><tr><th>ID</th><th>Med ID</th><th>Med Name</th><th>Intake Time</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['medicine_id']}</td><td>{$row['name']}</td><td>'{$row['intake_time']}'</td></tr>";
}
echo "</table>";

// 2. Check doses for today
$today = date('Y-m-d');
echo "<h3>doses content for today ($today):</h3>";
$res = $conn->query("SELECT * FROM doses WHERE patient_id = $userId AND DATE(scheduled_datetime) = '$today'");
echo "<table border='1'><tr><th>ID</th><th>Manual Med ID</th><th>Prescription Med ID</th><th>Scheduled DT</th><th>Status</th></tr>";
while($row = $res->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['manual_medicine_id']}</td><td>{$row['prescription_medicine_id']}</td><td>'{$row['scheduled_datetime']}'</td><td>{$row['status']}</td></tr>";
}
echo "</table>";

// 3. Check adherence query calculation
$adherenceSql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END) as taken
    FROM doses
    WHERE patient_id = $userId AND DATE(scheduled_datetime) <= CURDATE()
";
$adResult = $conn->query($adherenceSql)->fetch_assoc();
echo "<h3>Adherence Query Result:</h3>";
echo "Total: " . $adResult['total'] . "<br>";
echo "Taken: " . $adResult['taken'] . "<br>";

?>
