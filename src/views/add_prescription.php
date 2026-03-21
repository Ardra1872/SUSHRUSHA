<?php
session_start();
include __DIR__ . '/../config/db.php';

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

        // Insert medicines and generate doses
        if (isset($_POST['medicines']) && is_array($_POST['medicines'])) {
            $medicineStmt = $conn->prepare("
                INSERT INTO prescription_medicines (
                    prescription_id, medicine_name, dosage, frequency_type, 
                    time_slots, start_date, end_date, before_after_food, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $doseStmt = $conn->prepare("
                INSERT INTO doses (patient_id, prescription_medicine_id, scheduled_datetime, status)
                VALUES (?, ?, ?, 'upcoming')
            ");

            foreach ($_POST['medicines'] as $medicine) {
                $medicineName = $medicine['name'] ?? '';
                $dosage = $medicine['dosage'] ?? '';
                $freqType = $medicine['frequency_type'] ?? 'once';
                $timeSlots = isset($medicine['time_slots']) ? json_encode($medicine['time_slots']) : '[]';
                $startDate = $medicine['start_date'] ?? date('Y-m-d');
                $endDate = $medicine['end_date'] ?? $startDate;
                $foodInstruction = $medicine['before_after_food'] ?? 'After Food';
                $medNotes = $medicine['notes'] ?? '';

                if (!empty($medicineName)) {
                    $medicineStmt->bind_param(
                        "issssssss", 
                        $prescriptionId, $medicineName, $dosage, $freqType, 
                        $timeSlots, $startDate, $endDate, $foodInstruction, $medNotes
                    );
                    $medicineStmt->execute();
                    $pmId = $medicineStmt->insert_id;

                    // Generate Doses
                    $start = new DateTime($startDate);
                    $end = new DateTime($endDate);
                    $end->modify('+1 day'); // inclusive

                    $interval = new DateInterval('P1D');
                    $dateRange = new DatePeriod($start, $interval, $end);

                    $slots = json_decode($timeSlots, true);
                    if (is_array($slots)) {
                        foreach ($dateRange as $date) {
                            $dateStr = $date->format('Y-m-d');
                            foreach ($slots as $time) {
                                $scheduledDT = "$dateStr $time:00";
                                $doseStmt->bind_param("iis", $patientId, $pmId, $scheduledDT);
                                $doseStmt->execute();
                            }
                        }
                    }
                }
            }
            $medicineStmt->close();
            $doseStmt->close();
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
