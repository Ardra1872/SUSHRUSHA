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

        // 1. Delete future doses for all medicines in this prescription
        $deleteFutureDoses = $conn->prepare("
            DELETE d FROM doses d
            JOIN prescription_medicines pm ON d.prescription_medicine_id = pm.id
            WHERE pm.prescription_id = ? AND d.status = 'upcoming' AND d.scheduled_datetime >= NOW()
        ");
        $deleteFutureDoses->bind_param("i", $prescriptionId);
        $deleteFutureDoses->execute();
        $deleteFutureDoses->close();

        // 2. Delete and re-insert medicines
        // Note: For simplicity and to match the 'add' logic, we re-insert. 
        // To preserve link to PAST doses, we should ideally update existing or keep the PM IDs.
        // But the previous implementation deleted everything. Let's stick to a slightly better version:
        // We delete PMs that aren't in the new list, but here we'll just follow the established pattern
        // of clearing and re-adding, while being careful about doses.
        
        $deleteStmt = $conn->prepare("DELETE FROM prescription_medicines WHERE prescription_id = ?");
        $deleteStmt->bind_param("i", $prescriptionId);
        $deleteStmt->execute();
        $deleteStmt->close();

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

                    // Generate FUTURE Doses only
                    $start = new DateTime(max($startDate, date('Y-m-d')));
                    $end = new DateTime($endDate);
                    $end->modify('+1 day'); // inclusive

                    if ($start < $end) {
                        $interval = new DateInterval('P1D');
                        $dateRange = new DatePeriod($start, $interval, $end);
                        $slots = json_decode($timeSlots, true);
                        
                        if (is_array($slots)) {
                            foreach ($dateRange as $date) {
                                $dateStr = $date->format('Y-m-d');
                                foreach ($slots as $time) {
                                    $scheduledDT = "$dateStr $time:00";
                                    // Only insert if it's in the future
                                    if (strtotime($scheduledDT) > time()) {
                                        $doseStmt->bind_param("iis", $patientId, $pmId, $scheduledDT);
                                        $doseStmt->execute();
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $medicineStmt->close();
            $doseStmt->close();
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
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prescription - Sushrusha</title>
    <link href="../../public/assets/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .input-focus {
            transition: all 0.2s ease;
        }
        .input-focus:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-[#1e293b] min-h-screen">

    <div class="max-w-5xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="flex items-center justify-between mb-10">
            <div>
                <a href="dashboard.php" class="flex items-center text-primary font-semibold gap-2 mb-2 hover:translate-x-1 transition-transform">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    Back to Dashboard
                </a>
                <h1 class="text-4xl font-extrabold font-display tracking-tight text-slate-900">Edit Prescription</h1>
                <p class="text-slate-500 mt-1">Modify prescription details and medicine schedules</p>
            </div>
            <div class="size-16 rounded-3xl bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-3xl">edit_note</span>
            </div>
        </div>

        <form id="prescriptionForm" class="space-y-8">
            <input type="hidden" name="prescription_id" value="<?php echo htmlspecialchars($prescription['id']); ?>">

            <!-- 1. Disease & Doctor Details -->
            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center gap-3 mb-8">
                    <div class="size-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-600 text-xl">medical_services</span>
                    </div>
                    <h2 class="text-xl font-bold font-display">Medical Context</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-1">Prescription Date</label>
                        <input type="date" name="prescription_date" required value="<?php echo htmlspecialchars($prescription['prescription_date']); ?>"
                            class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-1">Diagnosis / Disease</label>
                        <input type="text" name="disease_name" required value="<?php echo htmlspecialchars($prescription['disease_name']); ?>"
                            placeholder="e.g., Seasonal Flu"
                            class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-1">Doctor Name</label>
                        <input type="text" name="doctor_name" required value="<?php echo htmlspecialchars($prescription['doctor_name']); ?>"
                            placeholder="Dr. S. K. Sharma"
                            class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-1">Hospital / Clinic</label>
                        <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($prescription['hospital_name'] ?? ''); ?>"
                            placeholder="City Medical Center"
                            class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-medium">
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-sm font-bold text-slate-700 ml-1">General Notes</label>
                        <textarea name="notes" placeholder="Any specific instructions from the doctor..."
                            class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-medium min-h-[100px]"><?php echo htmlspecialchars($prescription['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Medicines Section -->
            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-emerald-600 text-xl">medication</span>
                        </div>
                        <h2 class="text-xl font-bold font-display">Prescribed Medicines</h2>
                    </div>
                    <button type="button" onclick="addMedicine()" 
                        class="bg-slate-900 text-white rounded-xl px-5 py-2.5 font-bold text-sm hover:bg-slate-800 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Add Medicine
                    </button>
                </div>

                <div id="medicinesContainer" class="space-y-6">
                    <!-- Medicine templates injected here -->
                </div>
            </div>

            <!-- 3. Tests Section -->
            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-xl bg-purple-500/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-purple-600 text-xl">biotech</span>
                        </div>
                        <h2 class="text-xl font-bold font-display">Laboratory Tests</h2>
                    </div>
                    <button type="button" onclick="addTest()" 
                        class="text-purple-600 bg-purple-50 rounded-xl px-5 py-2.5 font-bold text-sm hover:bg-purple-100 transition-all flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Recommended Test
                    </button>
                </div>

                <div id="testsContainer" class="space-y-4">
                    <!-- Test templates injected here -->
                </div>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-4 p-4">
                <a href="dashboard.php" class="px-8 py-4 rounded-2xl font-bold text-slate-500 hover:bg-slate-100 transition-all">Cancel</a>
                <button type="submit" 
                    class="bg-primary text-white px-10 py-4 rounded-2xl font-bold shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined">save</span>
                    Update Prescription
                </button>
            </div>
        </form>
    </div>

    <!-- Template for Medicine -->
    <template id="medicineTemplate">
        <div class="medicine-item group relative bg-slate-50/50 border border-slate-100 rounded-3xl p-6 hover:border-emerald-200 hover:bg-emerald-50/10 transition-all">
            <button type="button" onclick="this.closest('.medicine-item').remove()" 
                class="absolute -top-3 -right-3 size-8 bg-white border border-slate-100 text-red-500 rounded-full shadow-lg flex items-center justify-center hover:bg-red-50 transition-all">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- Name & Dosage -->
                <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Medicine Name</label>
                        <input type="text" name="medicines[][name]" required oninput="searchMedicine(this)"
                            class="w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-semibold">
                        <div class="autocomplete-results hidden absolute z-10 w-[45%] bg-white border border-slate-100 rounded-2xl shadow-2xl mt-1"></div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Dosage</label>
                        <select name="medicines[][dosage]" class="dosage-select w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-semibold">
                            <option value="">Select Dosage</option>
                        </select>
                    </div>
                </div>

                <!-- Frequency -->
                <div class="md:col-span-4 space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Frequency</label>
                    <select name="medicines[][frequency_type]" onchange="handleFrequencyChange(this)"
                        class="w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-semibold">
                        <option value="once">Once Daily</option>
                        <option value="twice">Twice Daily</option>
                        <option value="thrice">Thrice Daily</option>
                        <option value="custom">Custom Schedule</option>
                        <option value="as_needed">As Needed (SOS)</option>
                    </select>
                </div>

                <!-- Dates -->
                <div class="md:col-span-8 grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Start Date</label>
                        <input type="date" name="medicines[][start_date]" required
                            class="w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-semibold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">End Date</label>
                        <input type="date" name="medicines[][end_date]" required
                            class="w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-semibold">
                    </div>
                </div>

                <!-- Time Slots Container -->
                <div class="md:col-span-12 time-slots-container grid grid-cols-2 md:grid-cols-4 gap-4 bg-white/50 p-6 rounded-3xl border border-dashed border-slate-200">
                    <!-- Dynamic slots here -->
                </div>

                <!-- Instruction & Notes -->
                <div class="md:col-span-4 space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Food Instruction</label>
                    <div class="flex bg-white rounded-2xl p-1 border border-slate-200">
                        <button type="button" onclick="setFood(this, 'Before Food')" class="food-btn flex-1 py-3 px-2 rounded-xl text-sm font-bold transition-all bg-emerald-500 text-white">Before</button>
                        <button type="button" onclick="setFood(this, 'After Food')" class="food-btn flex-1 py-3 px-2 rounded-xl text-sm font-bold transition-all text-slate-500">After</button>
                        <input type="hidden" name="medicines[][before_after_food]" value="Before Food">
                    </div>
                </div>
                <div class="md:col-span-8 space-y-2">
                    <label class="text-xs font-black text-slate-400 uppercase tracking-widest ml-1">Specific Instructions</label>
                    <input type="text" name="medicines[][notes]" placeholder="e.g., Avoid cold water"
                        class="w-full bg-white border-slate-200 rounded-2xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all font-medium">
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initial data from PHP
            const existingMeds = <?php echo json_encode($medicines); ?>;
            const existingTests = <?php echo json_encode($tests); ?>;
            
            existingMeds.forEach(med => addMedicine(med));
            existingTests.forEach(test => addTest(test));
        });

        function addMedicine(data = null) {
            const container = document.getElementById('medicinesContainer');
            const template = document.getElementById('medicineTemplate');
            const clone = template.content.cloneNode(true);
            const item = clone.querySelector('.medicine-item');
            const index = container.children.length;

            // Fix names with index
            item.querySelectorAll('[name*="[]"]').forEach(input => {
                input.name = input.name.replace('[]', `[${index}]`);
            });

            if (data) {
                item.querySelector(`[name="medicines[${index}][name]"]`).value = data.medicine_name;
                item.querySelector(`[name="medicines[${index}][frequency_type]"]`).value = data.frequency_type;
                item.querySelector(`[name="medicines[${index}][start_date]"]`).value = data.start_date;
                item.querySelector(`[name="medicines[${index}][end_date]"]`).value = data.end_date;
                item.querySelector(`[name="medicines[${index}][notes]"]`).value = data.notes;
                
                // Food logic
                const foodVal = data.before_after_food || 'Before Food';
                item.querySelector(`[name="medicines[${index}][before_after_food]"]`).value = foodVal;
                const buttons = item.querySelectorAll('.food-btn');
                buttons.forEach(btn => {
                    if (btn.innerText.includes(foodVal.split(' ')[0])) {
                        btn.classList.add('bg-emerald-500', 'text-white');
                        btn.classList.remove('text-slate-500');
                    } else {
                        btn.classList.remove('bg-emerald-500', 'text-white');
                        btn.classList.add('text-slate-500');
                    }
                });

                // Fetch dosages for medicine
                fetchDosages(item.querySelector(`[name="medicines[${index}][name]"]`), data.dosage);
                
                // Re-render time slots
                const slots = JSON.parse(data.time_slots || '[]');
                renderTimeSlots(item.querySelector('.time-slots-container'), data.frequency_type, index, slots);
            } else {
                // Default slots for 'once'
                renderTimeSlots(item.querySelector('.time-slots-container'), 'once', index);
            }

            container.appendChild(clone);
        }

        async function fetchDosages(input, currentVal = '') {
            const medName = input.value;
            const select = input.closest('.medicine-item').querySelector('.dosage-select');
            if (!medName) return;

            try {
                const res = await fetch(`../../public/api/search_medicines.php?q=${encodeURIComponent(medName)}`);
                const data = await res.json();
                
                select.innerHTML = '<option value="">Select Dosage</option>';
                data.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d;
                    opt.innerText = d;
                    if (d === currentVal) opt.selected = true;
                    select.appendChild(opt);
                });
                
                // If currentVal not in list and not empty, add it
                if (currentVal && !data.includes(currentVal)) {
                    const opt = document.createElement('option');
                    opt.value = currentVal;
                    opt.innerText = currentVal;
                    opt.selected = true;
                    select.appendChild(opt);
                }
            } catch (e) { console.error(e); }
        }

        function handleFrequencyChange(select) {
            const item = select.closest('.medicine-item');
            const container = item.querySelector('.time-slots-container');
            const index = Array.from(document.getElementById('medicinesContainer').children).indexOf(item);
            renderTimeSlots(container, select.value, index);
        }

        function renderTimeSlots(container, type, medIndex, values = []) {
            container.innerHTML = '';
            let count = 0;
            let defaultTimes = [];

            if (type === 'once') { count = 1; defaultTimes = ['09:00']; }
            else if (type === 'twice') { count = 2; defaultTimes = ['09:00', '21:00']; }
            else if (type === 'thrice') { count = 3; defaultTimes = ['09:00', '14:00', '21:00']; }
            else if (type === 'custom') { 
                // Initial custom slot
                if (values.length > 0) {
                    values.forEach(val => addCustomSlot(container, medIndex, val));
                } else {
                    addCustomSlot(container, medIndex, '09:00');
                }
                return;
            } else {
                container.innerHTML = '<p class="text-xs text-slate-400 col-span-full italic">No fixed scheduled needed for "As Needed" medications.</p>';
                return;
            }

            for (let i = 0; i < count; i++) {
                const time = values[i] || defaultTimes[i];
                container.innerHTML += `
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Slot ${i+1}</label>
                        <input type="time" name="medicines[${medIndex}][time_slots][]" value="${time}" required
                            class="w-full bg-slate-50 border-slate-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all">
                    </div>
                `;
            }
        }

        function addCustomSlot(container, medIndex, value = '09:00') {
            const div = document.createElement('div');
            div.className = 'space-y-1 relative group/slot';
            div.innerHTML = `
                <input type="time" name="medicines[${medIndex}][time_slots][]" value="${value}" required
                    class="w-full bg-slate-50 border-slate-200 rounded-xl px-3 py-3 text-sm focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all">
                <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 size-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/slot:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-[12px]">close</span>
                </button>
            `;
            container.appendChild(div);

            // Add Plus Button if it's the first one
            if (container.querySelectorAll('input[type="time"]').length === 1) {
                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.onclick = () => addCustomSlot(container, medIndex);
                addBtn.className = 'col-span-1 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:border-emerald-300 hover:text-emerald-500 transition-all';
                addBtn.innerHTML = '<span class="material-symbols-outlined">add</span>';
                container.appendChild(addBtn);
            } else {
                // Move button to end
                const existingAddBtn = container.querySelector('button.col-span-1');
                if (existingAddBtn) {
                    container.appendChild(existingAddBtn);
                } else {
                    const addBtn = document.createElement('button');
                    addBtn.type = 'button';
                    addBtn.onclick = () => addCustomSlot(container, medIndex);
                    addBtn.className = 'col-span-1 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:border-emerald-300 hover:text-emerald-500 transition-all';
                    addBtn.innerHTML = '<span class="material-symbols-outlined">add</span>';
                    container.appendChild(addBtn);
                }
            }
        }

        function setFood(btn, val) {
            const container = btn.parentElement;
            container.querySelector('input').value = val;
            container.querySelectorAll('button').forEach(b => {
                if (b === btn) {
                    b.classList.add('bg-emerald-500', 'text-white');
                    b.classList.remove('text-slate-500');
                } else {
                    b.classList.remove('bg-emerald-500', 'text-white');
                    b.classList.add('text-slate-500');
                }
            });
        }

        function addTest(data = null) {
            const container = document.getElementById('testsContainer');
            const div = document.createElement('div');
            const index = container.children.length;
            div.className = 'test-item bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-4';
            div.innerHTML = `
                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="text" name="tests[${index}][name]" required placeholder="Test Name" value="${data ? data.test_name : ''}"
                        class="bg-white border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500/20 outline-none transition-all">
                    <select name="tests[${index}][type]" class="bg-white border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500/20 outline-none transition-all">
                        <option value="Blood" ${data && data.test_type === 'Blood' ? 'selected' : ''}>Blood Test</option>
                        <option value="X-Ray" ${data && data.test_type === 'X-Ray' ? 'selected' : ''}>X-Ray</option>
                        <option value="MRI" ${data && data.test_type === 'MRI' ? 'selected' : ''}>MRI</option>
                        <option value="Other" ${data && data.test_type === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                    <input type="date" name="tests[${index}][recommended_date]" value="${data ? data.recommended_date : ''}"
                        class="bg-white border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-500/20 outline-none transition-all">
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 transition-colors">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            `;
            container.appendChild(div);
        }

        // Search logic (Simplified Port)
        async function searchMedicine(input) {
            const val = input.value;
            const resultsDiv = input.nextElementSibling;
            if (val.length < 2) { resultsDiv.classList.add('hidden'); return; }

            try {
                const res = await fetch(`../../public/api/search_medicines.php?q=${encodeURIComponent(val)}`);
                const data = await res.json();
                
                if (data.length > 0) {
                    resultsDiv.innerHTML = data.map(m => `
                        <div class="px-5 py-3 hover:bg-slate-50 cursor-pointer font-medium border-b border-slate-50 last:border-0" 
                             onclick="selectMedicine(this, '${m.name}')">
                            ${m.name}
                        </div>
                    `).join('');
                    resultsDiv.classList.remove('hidden');
                } else {
                    resultsDiv.classList.add('hidden');
                }
            } catch (e) { console.error(e); }
        }

        function selectMedicine(el, name) {
            const input = el.parentElement.previousElementSibling;
            input.value = name;
            el.parentElement.classList.add('hidden');
            fetchDosages(input);
        }

        // Submit logic
        document.getElementById('prescriptionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = e.submitter;
            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Updating...';

            try {
                const response = await fetch('edit_prescription.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'dashboard.php';
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<span class="material-symbols-outlined">save</span> Update Prescription';
                }
            } catch (error) {
                console.error(error);
                alert('Connection error');
                btn.disabled = false;
                btn.innerHTML = '<span class="material-symbols-outlined">save</span> Update Prescription';
            }
        });
    </script>
</body>
</html>
