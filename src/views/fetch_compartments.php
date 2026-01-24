<?php
session_start();
include '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$patient_id = $_SESSION['user_id'];

// Fetch all compartments with their assigned medicines
$query = "SELECT 
    m.id,
    m.name,
    m.dosage_value,
    m.dosage_unit,
    m.compartment_number,
    m.frequency,
    m.start_date,
    m.end_date,
    m.medicine_type
FROM medicines m
WHERE m.patient_id = ?
ORDER BY m.compartment_number ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

$compartments = [
    1 => ['number' => 1, 'medicine' => null, 'occupied' => false],
    2 => ['number' => 2, 'medicine' => null, 'occupied' => false],
    3 => ['number' => 3, 'medicine' => null, 'occupied' => false],
    4 => ['number' => 4, 'medicine' => null, 'occupied' => false],
];

while ($row = $result->fetch_assoc()) {
    $compartment_num = intval($row['compartment_number']);
    if (isset($compartments[$compartment_num])) {
        $compartments[$compartment_num] = [
            'number' => $compartment_num,
            'occupied' => true,
            'medicine' => [
                'id' => $row['id'],
                'name' => $row['name'],
                'dosage' => $row['dosage_value'] . ($row['dosage_unit'] ? ' ' . $row['dosage_unit'] : ''),
                'frequency' => $row['frequency'],
                'type' => $row['medicine_type'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date']
            ]
        ];
    }
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'compartments' => array_values($compartments)
]);
?>
