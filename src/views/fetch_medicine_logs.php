<?php
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

session_start();
include __DIR__ . '/../config/db.php';

try {
    $patient_id = $_SESSION['user_id'] ?? null;
    if (!$patient_id) {
        throw new Exception('Unauthorized');
    }

    // Fetch logs: Join dose_logs -> medicine_schedule -> medicines
    // We want logs for ALL medicines belonging to this patient
    $query = "
        SELECT 
            dl.id,
            m.name AS medicine_name,
            m.dosage_value AS dosage,
            dl.status,
            dl.log_time AS time,
            DATE_FORMAT(dl.log_time, '%Y-%m-%d %H:%i') AS formatted_time
        FROM dose_logs dl
        JOIN medicine_schedule ms ON dl.schedule_id = ms.id
        JOIN medicines m ON ms.medicine_id = m.id
        WHERE m.patient_id = ?
        ORDER BY dl.log_time DESC
        LIMIT 100
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception('SQL Prepare Error: ' . $conn->error);
    
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }

    ob_end_clean();
    echo json_encode(['status' => 'success', 'logs' => $logs]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
