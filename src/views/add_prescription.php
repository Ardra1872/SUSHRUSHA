<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = $_SESSION['user_id'];
    $prescriptionDate = $_POST['prescription_date'] ?? date('Y-m-d');
    $diseaseName = $_POST['disease_name'] ?? '';
    $diseaseDescription = $_POST['disease_description'] ?? '';
    $doctorName = $_POST['doctor_name'] ?? '';
    $hospitalName = $_POST['hospital_name'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (empty($diseaseName) || empty($doctorName) || empty($prescriptionDate)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Insert prescription
        $stmt = $conn->prepare("
            INSERT INTO prescriptions (patient_id, prescription_date, disease_name, disease_description, doctor_name, hospital_name, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssss", $patientId, $prescriptionDate, $diseaseName, $diseaseDescription, $doctorName, $hospitalName, $notes);
        $stmt->execute();
        $prescriptionId = $stmt->insert_id;
        $stmt->close();

        // Insert medicines
        if (isset($_POST['medicines']) && is_array($_POST['medicines'])) {
            $medicineStmt = $conn->prepare("
                INSERT INTO prescription_medicines (prescription_id, medicine_name, dosage, frequency, duration, instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($_POST['medicines'] as $medicine) {
                $medicineName = $medicine['name'] ?? '';
                $dosage = $medicine['dosage'] ?? '';
                $frequency = $medicine['frequency'] ?? '';
                $duration = $medicine['duration'] ?? '';
                $instructions = $medicine['instructions'] ?? '';

                if (!empty($medicineName)) {
                    $medicineStmt->bind_param("isssss", $prescriptionId, $medicineName, $dosage, $frequency, $duration, $instructions);
                    $medicineStmt->execute();
                }
            }
            $medicineStmt->close();
        }

        // Insert tests
        if (isset($_POST['tests']) && is_array($_POST['tests'])) {
            $testStmt = $conn->prepare("
                INSERT INTO prescription_tests (prescription_id, test_name, test_type, test_description, recommended_date, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($_POST['tests'] as $test) {
                $testName = $test['name'] ?? '';
                $testType = $test['type'] ?? 'Other';
                $testDescription = $test['description'] ?? '';
                $recommendedDate = !empty($test['recommended_date']) ? $test['recommended_date'] : null;
                $status = 'Pending';

                if (!empty($testName)) {
                    $testStmt->bind_param("isssss", $prescriptionId, $testName, $testType, $testDescription, $recommendedDate, $status);
                    $testStmt->execute();
                }
            }
            $testStmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Prescription added successfully', 'prescription_id' => $prescriptionId]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error adding prescription: ' . $e->getMessage()]);
    }
    exit;
}
?>
