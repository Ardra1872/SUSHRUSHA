<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$caretakerName = $_SESSION['user_name']; 
$caretaker_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT u.id, u.name
    FROM caregivers c
    JOIN users u ON c.patient_id = u.id
    WHERE c.caregiver_id = ?
");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$patients = $stmt->get_result();

function getAllMedicines($conn, $patient_id) {
    $stmt = $conn->prepare("
        SELECT m.name, m.dosage, ms.intake_time
        FROM medicines m
        LEFT JOIN medicine_schedule ms ON m.id = ms.medicine_id
        WHERE m.patient_id = ?
        ORDER BY ms.intake_time ASC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sushrusha - Caretaker Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "primary": "#137fec",
        "background-light": "#f6f7f8",
        "background-dark": "#101922",
      },
      fontFamily: { "display": ["Inter", "sans-serif"] },
      borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
    }
  }
}
</script>
<style>
body { font-family: 'Inter', sans-serif; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
table td, table th { vertical-align: middle; }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display transition-colors duration-200">

<div class="flex h-screen w-full overflow-hidden">

<!-- Sidebar Navigation -->
<aside class="w-64 flex-shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col justify-between p-4 hidden md:flex">
<div class="flex flex-col gap-6">
  <!-- Profile -->
  <div class="flex gap-3 items-center px-2">
    <div class="bg-center bg-no-repeat bg-cover rounded-full h-10 w-10 border border-slate-200 dark:border-slate-700" 
         style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCItgVsB6L9oTlgCsJ8uKlfyuXNaBC3_zzplsS5VRlMRMD71PQ-ot_PK5tedVK_TYgrcOGs-DuzCoieo-nh_SOKe8VqtGXzC2GMR6DhU-_0kSNQ6bs5agmYx5GY1neARLGeMxOVc8LW2b8NDTjwmNUaNoW_edEBLAAPYpmzyR3QA1Ly3bQjUTkyIXRcImJEKDudQg3Qa7F7rjhlpeaYahlq7RBSnkIeym8PqLtbmoNNmM6Z2JTreop1nUbAVQPYdXagI8G21wSPFuE");'>
    </div>
    <div class="flex flex-col">
      <h1 class="text-slate-900 dark:text-white text-base font-bold leading-none">Sushrusha</h1>
    </div>
  </div>
  <!-- Nav Links -->
  <nav class="flex flex-col gap-2" id="sidebarNav">
    <a data-section="dashboard" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg bg-primary text-white transition-colors group cursor-pointer">
      <span class="material-symbols-outlined">dashboard</span>
      <p class="text-sm font-medium">Dashboard</p>
    </a>
    <a data-section="patients" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
      <span class="material-symbols-outlined group-hover:text-primary">group</span>
      <p class="text-sm font-medium">Patient List</p>
    </a>
    <a data-section="schedule" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
      <span class="material-symbols-outlined group-hover:text-primary">calendar_month</span>
      <p class="text-sm font-medium">Schedule</p>
    </a>
    <a data-section="messages" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
      <span class="material-symbols-outlined group-hover:text-primary">chat</span>
      <p class="text-sm font-medium">Messages</p>
    </a>
    <a data-section="settings" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
      <span class="material-symbols-outlined group-hover:text-primary">settings</span>
      <p class="text-sm font-medium">Settings</p>
    </a>
  </nav>
</div>
<!-- Bottom Action -->
<div class="px-2">
  <a href="../../public/logout.php" class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
    <span class="material-symbols-outlined">logout</span>
    <p class="text-sm font-medium">Sign Out</p>
  </a>
</div>
</aside>

<!-- Main Content -->
<main class="flex-1 flex flex-col h-full overflow-hidden relative">
<div class="md:hidden flex items-center justify-between p-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
  <div class="flex items-center gap-2">
    <div class="bg-center bg-no-repeat bg-cover rounded-full h-8 w-8" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCI2aGdFBsFOLf8Ps5ojHofrg1H-3s04T2DkYrxv6gXeIhH97r6z6YcAOrBINIwIeOP0_im3TO1Io66Y2WkaWo1GdGy1HWDg3HDAgrTfC843DFkt_bvXcsKdhpkLBAb2dwQKS3nql84SC9t-ZV5SOo7LZ1bqWADFVSvvkYheUmpj8eA_X_2RyzXCLYTQxDu7H0pdivZF4AFY41_YiXuqJKbvVJw7d6yXLDatm_hvw3LxPDc-0kLoDInMa8wHQixti7vOLAheqJIkGE");'></div>
    <span class="font-bold text-lg">Sushrusha</span>
  </div>
  <button class="text-slate-500"><span class="material-symbols-outlined">menu</span></button>
</div>

<div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-12">
<div class="max-w-7xl mx-auto space-y-8 pb-12">

<!-- Dashboard Section -->
<section id="dashboard" class="content-section">

<!-- Header & Search -->
<div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
  <div class="flex flex-col gap-2">
    <h2 class="text-xl font-bold">Welcome, <?php echo htmlspecialchars($caretakerName); ?> 👋</h2>
     </div>
  <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto items-stretch sm:items-center">
    <div class="relative group w-full md:w-80">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <span class="material-symbols-outlined text-slate-400">search</span>
      </div>
      <input class="block w-full pl-10 pr-3 py-2.5 border-none rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary shadow-sm text-sm" placeholder="Search patients by name or ID..." type="text"/>
    </div>
    <button class="bg-primary hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
      <span class="material-symbols-outlined text-[20px]">add</span>
      Add Patient
    </button>
  </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-start">
  <?php
  $cards = [
    ["Total Patients","groups","124","+2%","text-emerald-600","bg-emerald-50"],
    ["Missed Doses Today","medication_liquid","3","+1","text-red-600","bg-red-50"],
    ["Upcoming Refills","prescriptions","8","Due Soon","text-amber-600","bg-amber-50"],
    ["Avg Adherence","analytics","92%","+5%","text-emerald-600","bg-emerald-50"]
  ];
  foreach($cards as $c): ?>
  <div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between h-32 relative overflow-hidden items-start">
    <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
      <span class="material-symbols-outlined text-6xl text-primary"><?= $c[1] ?></span>
    </div>
    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium"><?= $c[0] ?></p>
    <div class="flex items-baseline gap-2 mt-2">
      <p class="text-3xl font-bold text-slate-900 dark:text-white"><?= $c[2] ?></p>
      <span class="<?= $c[4] ?> dark:bg-slate-900/20 px-1.5 py-0.5 rounded text-xs font-semibold"><?= $c[3] ?></span>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Patient Table -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start mt-6">
<div class="lg:col-span-2">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Active Patients Status</h3>
    <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors">
      <span class="material-symbols-outlined text-slate-500">filter_list</span>
    </button>
  </div>
  <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
      <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase font-medium text-xs">
        <tr>
          <th class="px-6 py-3">Patient</th>
          <th class="px-6 py-3">Next Dose</th>
          <th class="px-6 py-3">Adherence</th>
          <th class="px-6 py-3">Status</th>
          <!-- <th class="px-6 py-3 text-right">Action</th> -->
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
      <?php if ($patients->num_rows === 0): ?>
        <tr><td colspan="5" class="px-6 py-6 text-center text-slate-500">No patients assigned yet.</td></tr>
      <?php endif; ?>
      <?php while ($patient = $patients->fetch_assoc()): 
    $allMeds = getAllMedicines($conn, $patient['id']); 
?>
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
    <!-- Patient Name -->
    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white truncate">
        <?= htmlspecialchars($patient['name']) ?>
    </td>

    <!-- Medicines / Next Dose -->
    <td class="px-6 py-4 text-slate-500 dark:text-slate-400 align-middle">
        <?php if ($allMeds): ?>
            <div class="flex flex-col gap-1">
                <?php foreach ($allMeds as $med): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                            <?= $med['intake_time'] ? htmlspecialchars($med['intake_time']) : "No schedule" ?>
                        </span>
                        <span class="text-xs">
                            <?= htmlspecialchars($med['name']) ?><?= $med['dosage'] ? " ({$med['dosage']})" : "" ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <span class="text-xs italic">No medicines</span>
        <?php endif; ?>
    </td>

    <!-- Adherence -->
    <td class="px-6 py-4 text-slate-500 dark:text-slate-400">—</td>

    <!-- Status -->
    <td class="px-6 py-4">
        <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-1 rounded text-xs">Active</span>
    </td>

    <!-- Action -->
    <!-- <td class="px-6 py-4 text-right">
        <a href="patient_medicines.php?id=<?= $patient['id'] ?>" class="text-primary text-sm font-medium hover:underline">View</a>
    </td> -->
</tr>
<?php endwhile; ?>

      </tbody>
    </table>
  </div>
</div>

<!-- Right Column Widgets -->
<!-- <div class="flex flex-col gap-6">
  <div class="bg-primary text-white rounded-xl shadow-lg p-5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10"></div>
    <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-5 rounded-full -ml-10 -mb-10"></div>
    <h3 class="text-lg font-bold mb-4 relative z-10">Shift Schedule</h3>
    <div class="space-y-4 relative z-10">
      <div class="flex gap-3 items-center">
        <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
          <span class="material-symbols-outlined text-white text-xl">watch_later</span>
        </div>
        <div>
          <p class="text-xs text-blue-100 opacity-80">Current Shift</p>
          <p class="text-sm font-semibold">07:00 AM - 03:00 PM</p>
        </div>
      </div>
      <div class="h-px bg-white/20 w-full"></div>
      <div class="flex flex-col gap-3 pl-1">
        <p class="text-xs font-medium text-blue-100 uppercase tracking-wider">Next Tasks</p>
        <div class="flex items-start gap-3">
          <span class="text-xs font-mono opacity-80 mt-0.5">14:00</span>
          <div>
            <p class="text-sm font-medium leading-none">Vitals Check - Room 302</p>
            <p class="text-xs opacity-70 mt-1">Robert Fox</p>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <span class="text-xs font-mono opacity-80 mt-0.5">14:30</span>
          <div>
            <p class="text-sm font-medium leading-none">Afternoon Meds</p>
            <p class="text-xs opacity-70 mt-1">Ward 4B</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div> -->

</section>

<!-- Other Sections -->
<section id="patients" class="content-section hidden"><h2>Patient List</h2></section>
<section id="schedule" class="content-section hidden"><h2>Schedule</h2></section>
<section id="messages" class="content-section hidden"><h2>Messages</h2></section>
<section id="settings" class="content-section hidden"><h2>Settings</h2></section>

</div>
</main>
</div>

<script>
// Section Navigation
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    document.querySelectorAll('.content-section').forEach(s => s.classList.add('hidden'));
    const sec = document.getElementById(link.dataset.section);
    if(sec) sec.classList.remove('hidden');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('bg-primary','text-white'));
    link.classList.add('bg-primary','text-white');
  });
});
</script>
</body>
</html>
