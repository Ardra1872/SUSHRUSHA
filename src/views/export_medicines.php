<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized Access");
}

$user_id = $_SESSION['user_id'];
$filename = "my_medicines_" . date('Y-m-d') . ".csv";

// headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// open output stream
$output = fopen('php://output', 'w');

// toggle column headers
fputcsv($output, ['Medicine Name', 'Dosage', 'Form', 'Frequency', 'Start Date', 'End Date', 'Compartment']);

// fetch medicines
$stmt = $conn->prepare("SELECT name, dosage_value, form, frequency, start_date, end_date, compartment_number FROM medicines WHERE patient_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
