<?php
session_start();
include '../config/db.php';

// Get patient_id from URL
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if(!$patient_id){
    die("Invalid patient ID");
}

// Fetch patient profile
// Fetch patient profile (users + caregivers + patient_profile)
$stmt = $conn->prepare("
    SELECT 
        u.name,
        c.relation,
        pp.dob,
        pp.gender,
        pp.blood_group,
        pp.height_cm,
        pp.weight_kg,
        pp.profile_photo
    FROM users u
    LEFT JOIN caregivers c ON c.patient_id = u.id
    LEFT JOIN patient_profile pp ON pp.patient_id = u.id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    die('Patient not found');
}


// Fetch all medicines for patient
$stmt2 = $conn->prepare("SELECT * FROM medicines WHERE patient_id = ? ORDER BY start_date ASC, id ASC");
$stmt2->bind_param("i", $patient_id);
$stmt2->execute();
$medicines = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$profilePhoto = !empty($patient['profile_photo'])
    ? '/Sushrusha/' . ltrim($patient['profile_photo'], '/')
    : '/Sushrusha/uploads/profile/default-avatar.png';


    


?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Medicine History - <?= htmlspecialchars($patient['name']) ?></title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>

  <!-- Tailwind Config (same as dashboard) -->
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#137fec",
            "background-light": "#f6f7f8"
          },
          fontFamily: {
            display: ["Inter"]
          },
          borderRadius: {
            lg: "0.5rem",
            xl: "0.75rem",
            full: "9999px"
          }
        }
      }
    }
  </script>

  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>

<body class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-slate-900 dark:to-slate-800 min-h-screen text-gray-800 dark:text-gray-100">
<div class="max-w-6xl mx-auto p-6">


<!-- Patient Profile -->
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 mb-8">
  <div class="flex flex-col md:flex-row items-center gap-6">
    
 <img src="<?= htmlspecialchars($profilePhoto) ?>" 
     alt="Profile photo of <?= htmlspecialchars($patient['name']) ?>" 
     class="w-28 h-28 rounded-full ring-4 ring-primary object-cover">


    <div class="flex-1 text-center md:text-left">
      <h1 class="text-3xl font-bold tracking-tight">
        <?= htmlspecialchars($patient['name']) ?>
      </h1>
      <p class="text-sm text-indigo-600 dark:text-indigo-400 font-medium mt-1">
        <?= htmlspecialchars($patient['relation']) ?>
      </p>

      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4 text-sm">
        <div><span class="text-gray-500">DOB</span><br><b><?= $patient['dob'] ?></b></div>
        <div><span class="text-gray-500">Gender</span><br><b><?= $patient['gender'] ?></b></div>
        <div><span class="text-gray-500">Blood</span><br><b><?= $patient['blood_group'] ?></b></div>
        <div><span class="text-gray-500">Height</span><br><b><?= $patient['height_cm'] ?> cm</b></div>
        <div><span class="text-gray-500">Weight</span><br><b><?= $patient['weight_kg'] ?> kg</b></div>
      </div>
    </div>

  </div>
</div>


<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
  <h2 class="text-2xl font-bold mb-4">💊 Medicine History</h2>

<?php if(count($medicines) === 0): ?>
  <p class="text-gray-500">No medicines found.</p>
<?php else: ?>

<div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
<table class="w-full text-sm">
<thead class="bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-gray-300">
<tr>
  <th class="px-4 py-3 text-left">Medicine</th>
  <th class="px-4 py-3 text-left">Dosage</th>
  <th class="px-4 py-3 text-left">Form</th>
  <th class="px-4 py-3 text-left">Schedule</th>
  <th class="px-4 py-3 text-left">Instructions</th>
  <th class="px-4 py-3 text-left">Status</th>
</tr>
</thead>

<tbody class="divide-y divide-gray-200 dark:divide-slate-700">

<?php foreach($medicines as $med): ?>
<tr class="hover:bg-indigo-50 dark:hover:bg-slate-700 transition">

<td class="px-4 py-3 font-semibold">
  <?= htmlspecialchars($med['name']) ?>
</td>

<td class="px-4 py-3">
<?php
  echo $med['dosage_value']
    ? htmlspecialchars($med['dosage_value'].' '.$med['dosage_unit'])
    : '-';
?>
</td>

<td class="px-4 py-3">
  <span class="px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs">
    <?= $med['form'] ?>
  </span>
</td>

<td class="px-4 py-3">
  <?= $med['frequency'] ?>
  <?php if($med['interval_hours']): ?>
    <span class="block text-xs text-gray-500">
      Every <?= $med['interval_hours'] ?> hrs
    </span>
  <?php endif; ?>
</td>

<td class="px-4 py-3 text-gray-500">
  <?= htmlspecialchars($med['instructions'] ?: '-') ?>
</td>

<td class="px-4 py-3">
<?php
  $today = date('Y-m-d');
  $active = $med['start_date'] <= $today &&
            (!$med['end_date'] || $med['end_date'] >= $today);
?>
<span class="px-3 py-1 rounded-full text-xs font-semibold
<?= $active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' ?>">
<?= $active ? 'Active' : 'Inactive' ?>
</span>
</td>

</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>
<?php endif; ?>
</div>

                            </div>
</body>
</html>
