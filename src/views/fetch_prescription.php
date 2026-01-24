<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$patientId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'list') {
    // Fetch all prescriptions
    $stmt = $conn->prepare("
        SELECT id, prescription_date, disease_name, doctor_name, hospital_name, disease_description, notes, created_at
        FROM prescriptions
        WHERE patient_id = ?
        ORDER BY prescription_date DESC
    ");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
} 
elseif ($action === 'detail') {
    $prescriptionId = $_GET['id'] ?? 0;

    // Fetch prescription details
    $stmt = $conn->prepare("
        SELECT id, prescription_date, disease_name, disease_description, doctor_name, hospital_name, notes, created_at
        FROM prescriptions
        WHERE id = ? AND patient_id = ?
    ");
    $stmt->bind_param("ii", $prescriptionId, $patientId);
    $stmt->execute();
    $prescResult = $stmt->get_result();
    $prescription = $prescResult->fetch_assoc();
    $stmt->close();

    if (!$prescription) {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
        exit;
    }

    // Fetch medicines
    $stmt = $conn->prepare("
        SELECT id, medicine_name, dosage, frequency, duration, instructions
        FROM prescription_medicines
        WHERE prescription_id = ?
    ");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $medResult = $stmt->get_result();
    $medicines = $medResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch tests
    $stmt = $conn->prepare("
        SELECT id, test_name, test_type, test_description, recommended_date, status
        FROM prescription_tests
        WHERE prescription_id = ?
    ");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $testResult = $stmt->get_result();
    $tests = $testResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'prescription' => $prescription,
        'medicines' => $medicines,
        'tests' => $tests
    ]);
}
elseif ($action === 'delete') {
    $prescriptionId = $_GET['id'] ?? 0;

    // Verify ownership
    $stmt = $conn->prepare("SELECT id FROM prescriptions WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $prescriptionId, $patientId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Prescription not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Delete related records first (cascade)
    $stmt = $conn->prepare("DELETE FROM prescription_medicines WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM prescription_tests WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $stmt->close();

    // Delete prescription
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $prescriptionId, $patientId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Prescription deleted successfully']);
}
elseif ($action === 'updateTestStatus') {
    $testId = $_POST['test_id'] ?? 0;
    $status = $_POST['status'] ?? 'Pending';

    // Verify test belongs to patient's prescription
    $stmt = $conn->prepare("
        SELECT pt.id FROM prescription_tests pt
        JOIN prescriptions p ON pt.prescription_id = p.id
        WHERE pt.id = ? AND p.patient_id = ?
    ");
    $stmt->bind_param("ii", $testId, $patientId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Test not found']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Update test status
    $stmt = $conn->prepare("UPDATE prescription_tests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $testId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Test status updated']);
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
