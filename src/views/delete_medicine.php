<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$patient_id = $_SESSION['user_id'] ?? null;
$data = json_decode(file_get_contents("php://input"), true);
$medicine_id = $data['medicine_id'] ?? null;

if (!$patient_id || !$medicine_id) {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

// Make sure medicine belongs to logged-in patient
$stmt = $conn->prepare("
    DELETE FROM medicines 
    WHERE id = ? AND patient_id = ?
");
$stmt->bind_param("ii", $medicine_id, $patient_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete"]);
}
