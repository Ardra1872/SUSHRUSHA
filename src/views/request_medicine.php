<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$name   = trim($_POST['name']);
$dosage = trim($_POST['dosage']);
$form   = $_POST['form'];
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO medicine_requests (name, dosage, form, requested_by)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("sssi", $name, $dosage, $form, $userId);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Already requested or error']);
}
