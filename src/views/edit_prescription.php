<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientId = $_SESSION['user_id'];
    $prescriptionId = $_POST['prescription_id'] ?? 0;
    
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

    $prescriptionDate = $_POST['prescription_date'] ?? date('Y-m-d');
    $diseaseName = $_POST['disease_name'] ?? '';
    $diseaseDescription = $_POST['disease_description'] ?? '';
    $doctorName = $_POST['doctor_name'] ?? '';
    $hospitalName = $_POST['hospital_name'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($diseaseName) || empty($doctorName) || empty($prescriptionDate)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Update prescription
        $stmt = $conn->prepare("
            UPDATE prescriptions 
            SET prescription_date = ?, disease_name = ?, disease_description = ?, 
                doctor_name = ?, hospital_name = ?, notes = ?
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ssssssii", $prescriptionDate, $diseaseName, $diseaseDescription, 
                          $doctorName, $hospitalName, $notes, $prescriptionId, $patientId);
        $stmt->execute();
        $stmt->close();

        // Delete and re-insert medicines
        $deleteStmt = $conn->prepare("DELETE FROM prescription_medicines WHERE prescription_id = ?");
        $deleteStmt->bind_param("i", $prescriptionId);
        $deleteStmt->execute();
        $deleteStmt->close();

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

        // Delete and re-insert tests
        $deleteStmt = $conn->prepare("DELETE FROM prescription_tests WHERE prescription_id = ?");
        $deleteStmt->bind_param("i", $prescriptionId);
        $deleteStmt->execute();
        $deleteStmt->close();

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
                $status = $test['status'] ?? 'Pending';

                if (!empty($testName)) {
                    $testStmt->bind_param("isssss", $prescriptionId, $testName, $testType, $testDescription, $recommendedDate, $status);
                    $testStmt->execute();
                }
            }
            $testStmt->close();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Prescription updated successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error updating prescription: ' . $e->getMessage()]);
    }
    exit;
}

// GET - fetch prescription data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $patientId = $_SESSION['user_id'];
    $prescriptionId = $_GET['id'] ?? 0;

    $stmt = $conn->prepare("
        SELECT * FROM prescriptions 
        WHERE id = ? AND patient_id = ?
    ");
    $stmt->bind_param("ii", $prescriptionId, $patientId);
    $stmt->execute();
    $prescResult = $stmt->get_result();
    $prescription = $prescResult->fetch_assoc();
    $stmt->close();

    if (!$prescription) {
        header("Location: prescription_history.php");
        exit;
    }

    // Fetch medicines and tests
    $stmt = $conn->prepare("SELECT * FROM prescription_medicines WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("SELECT * FROM prescription_tests WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescriptionId);
    $stmt->execute();
    $tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prescription</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <style>
        .medicine-item, .test-item {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="min-h-screen p-4 md:p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="prescription_history.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-4">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Prescriptions
                </a>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <span class="material-symbols-outlined text-4xl text-blue-600">edit</span>
                    Edit Prescription
                </h1>
            </div>

            <form id="prescriptionForm" class="space-y-6">
                <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['id']); ?>">

                <!-- Disease & Doctor Information Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                    <h2 class="text-xl font-semibold text-slate-900 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined">medical_information</span>
                        Disease & Doctor Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="text-red-500">*</span> Prescription Date
                            </label>
                            <input type="date" name="prescription_date" required
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?php echo htmlspecialchars($prescription['prescription_date']); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="text-red-500">*</span> Disease/Condition Name
                            </label>
                            <input type="text" name="disease_name" required placeholder="e.g., Diabetes, Hypertension"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?php echo htmlspecialchars($prescription['disease_name']); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                <span class="text-red-500">*</span> Doctor Name
                            </label>
                            <input type="text" name="doctor_name" required placeholder="e.g., Dr. Rajesh Kumar"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?php echo htmlspecialchars($prescription['doctor_name']); ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Hospital/Clinic Name</label>
                            <input type="text" name="hospital_name" placeholder="e.g., City Hospital"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="<?php echo htmlspecialchars($prescription['hospital_name'] ?? ''); ?>">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Disease Description</label>
                            <textarea name="disease_description" placeholder="Describe symptoms, severity, and any relevant details..."
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="3"><?php echo htmlspecialchars($prescription['disease_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Additional Notes</label>
                            <textarea name="notes" placeholder="Any additional information or follow-up instructions..."
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="2"><?php echo htmlspecialchars($prescription['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Medicines Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined">medication</span>
                            Prescribed Medicines
                        </h2>
                        <button type="button" onclick="addMedicine()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">add</span>
                            Add Medicine
                        </button>
                    </div>

                    <div id="medicinesContainer" class="space-y-4">
                        <?php foreach ($medicines as $index => $med): ?>
                        <div class="medicine-item bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text" name="medicines[<?php echo $index; ?>][name]" placeholder="Medicine Name" required
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($med['medicine_name']); ?>">
                                <input type="text" name="medicines[<?php echo $index; ?>][dosage]" placeholder="Dosage (e.g., 500mg)"
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($med['dosage'] ?? ''); ?>">
                                <input type="text" name="medicines[<?php echo $index; ?>][frequency]" placeholder="Frequency (e.g., 3 times daily)"
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($med['frequency'] ?? ''); ?>">
                                <input type="text" name="medicines[<?php echo $index; ?>][duration]" placeholder="Duration (e.g., 7 days)"
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($med['duration'] ?? ''); ?>">
                                <textarea name="medicines[<?php echo $index; ?>][instructions]" placeholder="Instructions (e.g., Take with food)" rows="2"
                                    class="md:col-span-2 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($med['instructions'] ?? ''); ?></textarea>
                            </div>
                            <button type="button" onclick="removeMedicine(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">delete</span>
                                Remove
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tests Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 border border-slate-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined">science</span>
                            Prescribed Tests
                        </h2>
                        <button type="button" onclick="addTest()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">add</span>
                            Add Test
                        </button>
                    </div>

                    <div id="testsContainer" class="space-y-4">
                        <?php foreach ($tests as $index => $test): ?>
                        <div class="test-item bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <input type="text" name="tests[<?php echo $index; ?>][name]" placeholder="Test Name (e.g., Blood Test)" required
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($test['test_name']); ?>">
                                <select name="tests[<?php echo $index; ?>][type]" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="Blood" <?php echo $test['test_type'] === 'Blood' ? 'selected' : ''; ?>>Blood Test</option>
                                    <option value="X-Ray" <?php echo $test['test_type'] === 'X-Ray' ? 'selected' : ''; ?>>X-Ray</option>
                                    <option value="Ultrasound" <?php echo $test['test_type'] === 'Ultrasound' ? 'selected' : ''; ?>>Ultrasound</option>
                                    <option value="CT Scan" <?php echo $test['test_type'] === 'CT Scan' ? 'selected' : ''; ?>>CT Scan</option>
                                    <option value="MRI" <?php echo $test['test_type'] === 'MRI' ? 'selected' : ''; ?>>MRI</option>
                                    <option value="ECG" <?php echo $test['test_type'] === 'ECG' ? 'selected' : ''; ?>>ECG</option>
                                    <option value="EEG" <?php echo $test['test_type'] === 'EEG' ? 'selected' : ''; ?>>EEG</option>
                                    <option value="Pathology" <?php echo $test['test_type'] === 'Pathology' ? 'selected' : ''; ?>>Pathology</option>
                                    <option value="Other" <?php echo $test['test_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <input type="date" name="tests[<?php echo $index; ?>][recommended_date]" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    value="<?php echo htmlspecialchars($test['recommended_date'] ?? ''); ?>">
                                <textarea name="tests[<?php echo $index; ?>][description]" placeholder="Test description/notes" rows="2"
                                    class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($test['test_description'] ?? ''); ?></textarea>
                            </div>
                            <button type="button" onclick="removeTest(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                                <span class="material-symbols-outlined text-base">delete</span>
                                Remove
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end">
                    <a href="prescription_history.php" class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 text-slate-700 font-medium">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
                        <span class="material-symbols-outlined">save</span>
                        Update Prescription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let medicineCount = <?php echo count($medicines); ?>;
        let testCount = <?php echo count($tests); ?>;

        function addMedicine() {
            const container = document.getElementById('medicinesContainer');
            const medicineItem = document.createElement('div');
            medicineItem.className = 'medicine-item bg-slate-50 p-4 rounded-lg border border-slate-200';
            medicineItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input type="text" name="medicines[${medicineCount}][name]" placeholder="Medicine Name" required
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="medicines[${medicineCount}][dosage]" placeholder="Dosage (e.g., 500mg)"
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="medicines[${medicineCount}][frequency]" placeholder="Frequency (e.g., 3 times daily)"
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="medicines[${medicineCount}][duration]" placeholder="Duration (e.g., 7 days)"
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <textarea name="medicines[${medicineCount}][instructions]" placeholder="Instructions (e.g., Take with food)" rows="2"
                        class="md:col-span-2 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <button type="button" onclick="removeMedicine(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Remove
                </button>
            `;
            container.appendChild(medicineItem);
            medicineCount++;
        }

        function removeMedicine(btn) {
            btn.closest('.medicine-item').remove();
        }

        function addTest() {
            const container = document.getElementById('testsContainer');
            const testItem = document.createElement('div');
            testItem.className = 'test-item bg-slate-50 p-4 rounded-lg border border-slate-200';
            testItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input type="text" name="tests[${testCount}][name]" placeholder="Test Name (e.g., Blood Test)" required
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select name="tests[${testCount}][type]" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="Blood">Blood Test</option>
                        <option value="X-Ray">X-Ray</option>
                        <option value="Ultrasound">Ultrasound</option>
                        <option value="CT Scan">CT Scan</option>
                        <option value="MRI">MRI</option>
                        <option value="ECG">ECG</option>
                        <option value="EEE">EEE</option>
                        <option value="Pathology">Pathology</option>
                        <option value="Other">Other</option>
                    </select>
                    <input type="date" name="tests[${testCount}][recommended_date]" class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <textarea name="tests[${testCount}][description]" placeholder="Test description/notes" rows="2"
                        class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <button type="button" onclick="removeTest(this)" class="mt-2 text-red-600 hover:text-red-800 text-sm flex items-center gap-1">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Remove
                </button>
            `;
            container.appendChild(testItem);
            testCount++;
        }

        function removeTest(btn) {
            btn.closest('.test-item').remove();
        }

        document.getElementById('prescriptionForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('edit_prescription.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('Prescription updated successfully!');
                    window.location.href = 'prescription_history.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to update prescription'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the prescription');
            }
        });
    </script>
</body>
</html>
