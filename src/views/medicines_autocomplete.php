<?php
session_start();
include '../config/db.php'; // your DB connection

header('Content-Type: application/json');

// If fetching medicine suggestions
if (isset($_GET['query'])) {
    $query = $_GET['query'];
    $search = "%$query%";
    $sql = "SELECT DISTINCT name FROM medicine_catalog WHERE name LIKE ? ORDER BY name ASC LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    echo json_encode($medicines);
    exit;
}

// If fetching dosages & forms for a selected medicine
if (isset($_GET['medicine'])) {
    $medicine = $_GET['medicine'];
    if (!$medicine) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT DISTINCT dosage, form FROM medicine_catalog WHERE name = ? ORDER BY dosage ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $medicine);
    $stmt->execute();
    $result = $stmt->get_result();

    $dosages = [];
    while ($row = $result->fetch_assoc()) {
        $dosages[] = $row; // row has ['dosage' => '500mg', 'form' => 'Pill']
    }
    echo json_encode($dosages);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medName = $_POST['medName'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $form = $_POST['form'] ?? '';
    // ... handle other fields, insert into DB
    echo json_encode(["status" => "success", "message" => "Medicine added!"]);
    exit;
}
