<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json'); // ✅ IMPORTANT

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized'
    ]);
    exit;
}

$patient_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Medicine ID missing'
    ]);
    exit;
}

$medicine_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT * 
    FROM medicines 
    WHERE id = ? AND patient_id = ?
");
$stmt->bind_param("ii", $medicine_id, $patient_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Medicine not found'
    ]);
    exit;
}

$medicine = $result->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'medicine' => $medicine   
]);
exit;
