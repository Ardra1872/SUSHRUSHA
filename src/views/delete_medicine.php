<?php
session_start();
include '../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status'=>'error', 'message'=>'Invalid input']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM medicines WHERE id=? AND patient_id=?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
if ($stmt->execute()) echo json_encode(['status'=>'success']);
else echo json_encode(['status'=>'error']);
$stmt->close();
?>
