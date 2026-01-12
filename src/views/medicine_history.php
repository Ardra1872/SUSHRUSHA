<?php
session_start();
include '../config/db.php';

// Get patient_id from URL
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if(!$patient_id){
    die("Invalid patient ID");
}

// Fetch patient profile
$stmt = $conn->prepare("SELECT * FROM patient_profile WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if(!$patient){
    die("Patient not found");
}

// Fetch all medicines for patient
$stmt2 = $conn->prepare("SELECT * FROM medicines WHERE patient_id = ? ORDER BY start_date ASC, id ASC");
$stmt2->bind_param("i", $patient_id);
$stmt2->execute();
$medicines = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Medicine History - <?= htmlspecialchars($patient['patient_name']) ?></title>
<link href="https://cdn.tailwindcss.com" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; }
</style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 text-gray-800 dark:text-gray-100 p-6">

<!-- Patient Profile -->
<div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-md mb-6 flex items-center gap-6">
    <div class="w-24 h-24 rounded-full border-4 border-primary bg-center bg-cover" style="background-image: url('<?= $patient['profile_photo'] ?: 'default-avatar.png' ?>')"></div>
    <div class="flex-1">
        <h1 class="text-2xl font-bold"><?= htmlspecialchars($patient['patient_name']) ?></h1>
        <p class="text-sm text-gray-500 dark:text-gray-300">Relation: <?= htmlspecialchars($patient['relation']) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-300">DOB: <?= htmlspecialchars($patient['dob']) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-300">Gender: <?= htmlspecialchars($patient['gender']) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-300">Blood Group: <?= htmlspecialchars($patient['blood_group']) ?></p>
        <p class="text-sm text-gray-500 dark:text-gray-300">Height: <?= htmlspecialchars($patient['height_cm']) ?> cm | Weight: <?= htmlspecialchars($patient['weight_kg']) ?> kg</p>
    </div>
</div>

<!-- Medicines Table -->
<div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-md">
    <h2 class="text-xl font-bold mb-4">Medicine History</h2>
    <?php if(count($medicines) === 0): ?>
        <p class="text-gray-500 dark:text-gray-300">No medicines found for this patient.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-100 dark:bg-slate-700 text-left">
                        <th class="border px-3 py-2">Medicine</th>
                        <th class="border px-3 py-2">Dosage</th>
                        <th class="border px-3 py-2">Form</th>
                        <th class="border px-3 py-2">Schedule</th>
                        <th class="border px-3 py-2">Instructions</th>
                        <th class="border px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($medicines as $med): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                        <td class="border px-3 py-2"><?= htmlspecialchars($med['name']) ?></td>
                        <td class="border px-3 py-2">
                            <?php 
                                if($med['dosage_value'] && $med['dosage_unit']){
                                    echo htmlspecialchars($med['dosage_value'].' '.$med['dosage_unit']);
                                } elseif($med['dosage']){
                                    echo htmlspecialchars($med['dosage']);
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                        <td class="border px-3 py-2"><?= htmlspecialchars($med['form']) ?></td>
                        <td class="border px-3 py-2">
                            <?= htmlspecialchars($med['frequency']) ?>
                            <?php if($med['specific_days']): ?>
                                (<?= htmlspecialchars($med['specific_days']) ?>)
                            <?php endif; ?>
                            <?php if($med['interval_hour']): ?>
                                - Every <?= htmlspecialchars($med['interval_hour']) ?> hour(s)
                            <?php endif; ?>
                        </td>
                        <td class="border px-3 py-2"><?= htmlspecialchars($med['instructions'] ?: '-') ?></td>
                        <td class="border px-3 py-2">
                            <?php
                                $today = date('Y-m-d');
                                if($med['start_date'] <= $today && (!$med['end_date'] || $med['end_date'] >= $today)){
                                    echo '<span class="text-green-600 font-bold">Active</span>';
                                } else {
                                    echo '<span class="text-gray-500">Inactive</span>';
                                }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
