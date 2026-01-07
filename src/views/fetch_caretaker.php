<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}

$patient_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT u.name, u.email, c.relation
    FROM caregivers c
    JOIN users u ON c.caregiver_id = u.id
    WHERE c.patient_id = ?
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$caretakers = [];
while ($row = $result->fetch_assoc()) {
    $caretakers[] = $row;
}

echo json_encode(['status'=>'success','caretakers'=>$caretakers]);
