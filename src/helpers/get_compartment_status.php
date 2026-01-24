<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['user_id'];

// Fetch all occupied compartments for this patient
$query = "SELECT DISTINCT compartment_number FROM medicines WHERE patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$occupied_compartments = [];
while ($row = $result->fetch_assoc()) {
    $occupied_compartments[] = intval($row['compartment_number']);
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'occupied_compartments' => $occupied_compartments,
    'available_compartments' => array_diff([1, 2, 3, 4], $occupied_compartments)
]);
?>
