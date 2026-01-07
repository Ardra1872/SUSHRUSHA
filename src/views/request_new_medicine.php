<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

$patient_id = $_SESSION['user_id'] ?? null;
if (!$patient_id) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

// Get JSON body
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');

if (!$name) {
    echo json_encode(['status'=>'error','message'=>'Medicine name required']);
    exit;
}

// Check if already exists
$stmt = $conn->prepare("SELECT id FROM medicine_catalog WHERE name=?");
$stmt->bind_param("s", $name);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows > 0){
    echo json_encode(['status'=>'error','message'=>'Medicine already exists']);
    exit;
}

// Insert into medicine_requests table
$stmt2 = $conn->prepare("INSERT INTO medicine_requests (patient_id, name, requested_at) VALUES (?, ?, NOW())");
$stmt2->bind_param("is", $patient_id, $name);
if($stmt2->execute()){
    echo json_encode(['status'=>'success','message'=>'Medicine request submitted successfully!']);
}else{
    echo json_encode(['status'=>'error','message'=>'Database error: '.$stmt2->error]);
}
?>
