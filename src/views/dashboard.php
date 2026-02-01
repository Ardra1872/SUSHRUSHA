<?php
session_start();

include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? null;

$name = $_SESSION['user_name'];

// Fetch profile photo
$stmt = $conn->prepare("SELECT profile_photo FROM patient_profile WHERE patient_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profileResult = $stmt->get_result();
$profileData = $profileResult->fetch_assoc();
$profilePhoto = !empty($profileData['profile_photo']) ? '../' . htmlspecialchars($profileData['profile_photo']) : null;
$userInitials = !empty($name) ? strtoupper(substr($name, 0, 1)) : 'U';
$stmt->close();

// Fetch disease / conditions info (for dashboard display)
// $stmt = $conn->prepare("SELECT conditions FROM medical_details WHERE patient_id = ? LIMIT 1");
// $stmt->bind_param("i", $userId);
// $stmt->execute();
// $medicalResult = $stmt->get_result();
// $medicalData = $medicalResult->fetch_assoc();
// $diseaseInfo = !empty($medicalData['conditions']) ? $medicalData['conditions'] : null;
// $stmt->close();

// NEW: Fetch Diseases from Prescriptions
$prescriptionDiseases = [];
$stmt = $conn->prepare("SELECT DISTINCT disease_name FROM prescriptions WHERE patient_id = ? ORDER BY prescription_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$diseaseResult = $stmt->get_result();
while ($row = $diseaseResult->fetch_assoc()) {
    if (!empty($row['disease_name'])) {
        $prescriptionDiseases[] = $row['disease_name'];
    }
}
$stmt->close();

// NEW: Fetch Assigned Caretaker
$caretakerName = "Not Assigned";
$caretakerRelation = null;
$stmt = $conn->prepare("
    SELECT u.name, c.relation
    FROM caregivers c
    JOIN users u ON c.caregiver_id = u.id
    WHERE c.patient_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$caretakerResult = $stmt->get_result();
if ($caretakerRow = $caretakerResult->fetch_assoc()) {
    $caretakerName = $caretakerRow['name'];
    $caretakerRelation = $caretakerRow['relation'];
}
$stmt->close();



// ---------------------------------------------------------
// ADHERENCE UPDATE QUERY
// ---------------------------------------------------------
// 1. Count Total Medicines
$countMedStmt = $conn->prepare("SELECT COUNT(*) as total_meds FROM medicines WHERE patient_id = ?");
$countMedStmt->bind_param("i", $userId);
$countMedStmt->execute();
$medCountResult = $countMedStmt->get_result();
$totalMeds = $medCountResult->fetch_assoc()['total_meds'];
$countMedStmt->close();

// 2. Calculate Adherence (Based on dose_logs)
// We join with medicines table to ensure we only count logs for this patient's medicines
// logs: status = 'TAKEN' or 'MISSED' (or 'SKIPPED' if you use that)
$adherencePct = 0;
$totalLogs = 0;
$hasLogs = false;

if ($totalMeds > 0) {
    $logQuery = "
        SELECT 
            COUNT(*) as total_attempts,
            SUM(CASE WHEN dl.status = 'TAKEN' THEN 1 ELSE 0 END) as taken_count
        FROM dose_logs dl
        JOIN medicine_schedule ms ON dl.schedule_id = ms.id
        JOIN medicines m ON ms.medicine_id = m.id
        WHERE m.patient_id = ?
    ";
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bind_param("i", $userId);
    $logStmt->execute();
    $logResult = $logStmt->get_result();
    $logData = $logResult->fetch_assoc();
    $logStmt->close();

    $totalLogs = intval($logData['total_attempts']);
    $takenCount = intval($logData['taken_count']);

    if ($totalLogs > 0) {
        $adherencePct = round(($takenCount / $totalLogs) * 100);
        $hasLogs = true;
    }
}

// ---------------------------------------------------------
// FETCH BOX SLOTS DATA (1-4)
// ---------------------------------------------------------
$boxSlots = [1 => null, 2 => null, 3 => null, 4 => null];
$stmt = $conn->prepare("SELECT name, compartment_number FROM medicines WHERE patient_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$medResult = $stmt->get_result();
while ($row = $medResult->fetch_assoc()) {
    $cNum = intval($row['compartment_number']);
    if ($cNum >= 1 && $cNum <= 4) {
        // Default status to 'Scheduled' since 'status' column doesn't exist in medicines table
        $row['status'] = 'Scheduled'; 
        $boxSlots[$cNum] = $row;
    }
}
$stmt->close();

// ---------------------------------------------------------
// 3. Calculate Missed Doses (This Week)
// ---------------------------------------------------------
$missedDosesCount = 0;
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$todayDate   = date('Y-m-d');
$nowTime     = date('H:i:s');

// Fetch all medicines and their fixed schedules for the patient
$medsSql = "
    SELECT 
        m.id as med_id, m.name, m.start_date, m.end_date, m.schedule_type, m.days,
        ms.id as schedule_id, ms.intake_time
    FROM medicines m
    JOIN medicine_schedule ms ON m.id = ms.medicine_id
    WHERE m.patient_id = ?
";
$medsStmt = $conn->prepare($medsSql);
$medsStmt->bind_param("i", $userId);
$medsStmt->execute();
$medsResult = $medsStmt->get_result();

$allMeds = [];
while ($row = $medsResult->fetch_assoc()) {
    $allMeds[] = $row;
}
$medsStmt->close();

// Fetch dose logs for this week
$logsSql = "
    SELECT schedule_id, status, DATE(log_time) as log_date
    FROM dose_logs dl
    JOIN medicine_schedule ms ON dl.schedule_id = ms.id
    JOIN medicines m ON ms.medicine_id = m.id
    WHERE m.patient_id = ? AND DATE(log_time) >= ?
";
$logsStmt = $conn->prepare($logsSql);
$logsStmt->bind_param("is", $userId, $startOfWeek);
$logsStmt->execute();
$logsResult = $logsStmt->get_result();

$logsMap = []; 
while ($row = $logsResult->fetch_assoc()) {
    $key = $row['schedule_id'] . '_' . $row['log_date'];
    $logsMap[$key] = $row['status'];
}
$logsStmt->close();

// Iterate days from Start of Week to Today to count misses
$currentDate = $startOfWeek;
while (strtotime($currentDate) <= strtotime($todayDate)) {
    $dayNameShort = date('D', strtotime($currentDate)); // Mon, Tue...
    $dayChar = substr($dayNameShort, 0, 1); // M, T, W... (Handling potential single-char storage)

    foreach ($allMeds as $med) {
        // 1. Check Date Range
        if ($currentDate < $med['start_date']) continue;
        if (!empty($med['end_date']) && $med['end_date'] !== '0000-00-00' && $currentDate > $med['end_date']) continue;
        
        // 2. Check Schedule Requirement
        $isScheduled = false;
        $sType = strtolower($med['schedule_type'] ?? 'daily');
        
        if ($sType === 'daily') {
            $isScheduled = true;
        } elseif ($sType === 'weekly') {
            // Assume weekly = same day of week as start_date
            if (date('N', strtotime($currentDate)) == date('N', strtotime($med['start_date']))) {
                $isScheduled = true;
            }
        } elseif ($sType === 'custom' || $sType === 'specific days') {
            $daysArr = json_decode($med['days'] ?? '[]', true);
            if (is_array($daysArr)) {
                // Match standard names OR single chars
                if (in_array($dayNameShort, $daysArr) || in_array(date('l', strtotime($currentDate)), $daysArr)) {
                     $isScheduled = true;
                } elseif (in_array($dayChar, $daysArr)) {
                    $isScheduled = true;
                }
            }
        }
        
        if (!$isScheduled) continue;
        
        // 3. Check Status in Logs (PRIORITY)
        $logKey = $med['schedule_id'] . '_' . $currentDate;
        $status = $logsMap[$logKey] ?? null;
        
        if ($status === 'MISSED') {
            $missedDosesCount++; // Explicit miss (even if future)
            continue;
        } elseif ($status === 'TAKEN') {
            continue; // Taken, so not missed
        }

        // 4. Check Time (for implicit misses)
        $sTime = $med['intake_time']; // HH:MM:SS
        if ($currentDate === $todayDate && $sTime > $nowTime) {
            continue; // Hasn't happened yet, and not explicitly logged
        }
        
        // If we reached here: it's scheduled, time passed, and no log exists in map (or not TAKEN/MISSED)
        $missedDosesCount++; // Implicit miss
    }
    
    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
}

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SUSHRUSHA – Patient Dashboard</title>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script src="../../public/assets/translations.js"></script>
<script src="../../public/assets/language-selector.js"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        primary: "#2563EB",
        primaryDark: "#1D4ED8",
        surface: "#FFFFFF",
        bg: "#F3F6FA",
        textMain: "#1E293B",
        textSub: "#64748B",
        success: "#10B981",
        warning: "#F59E0B",
        danger: "#EF4444",
      },
      fontFamily: {
        display: ["Plus Jakarta Sans", "sans-serif"],
        body: ["Inter", "sans-serif"]
      },
      boxShadow: {
        soft: "0 8px 30px rgba(0,0,0,0.05)"
      }
    }
  }
}
</script>
</head>
<body class="bg-bg text-textMain font-body h-screen flex">


<!-- SIDEBAR -->
<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-40 w-72 bg-surface border-r border-slate-200 flex flex-col
         transform -translate-x-full transition-transform duration-300 md:translate-x-0 md:static">


  <div class="p-8 flex items-center gap-4">
    <div class="size-12 rounded-2xl bg-gradient-to-br from-primary to-blue-500 flex items-center justify-center text-white shadow-lg">
      <span class="material-symbols-outlined text-[26px]">local_pharmacy</span>
    </div>
    <div>
      <h1 class="font-display text-xl font-bold">SUSHRUSHA</h1>
      <p class="text-xs text-textSub font-medium">Smart Medicine Reminder</p>
    </div>
  </div>

 <nav class="flex-1 px-4 space-y-1">
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl bg-primary/10 text-primary font-semibold" data-section="dashboard">
    <span class="material-symbols-outlined">dashboard</span><span data-i18n="dashboard">Dashboard</span> 
  </a>

  <!-- Patient -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="schedule">
    <span class="material-symbols-outlined">calendar_month</span> <span data-i18n="my_medicines">My Medicines</span>

  </a>
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="prescriptions">
    <span class="material-symbols-outlined">pill</span> <span data-i18n="prescriptions">Prescriptions</span>

  </a>

  <!-- Caretaker -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="assigned">
    <span class="material-symbols-outlined">people</span> <span data-i18n="assign_caretaker">Assign Caretaker</span>

  </a>
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="alerts">
    <span class="material-symbols-outlined">notifications</span><span data-i18n="alerts">Alerts</span>

  </a>

  <!-- Admin -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="requests">
    <span class="material-symbols-outlined">inventory_2</span> <span  	data-i18n="medicine_requests">Medicine Requests</span>
  </a>

  <!-- Box Settings -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="boxSettings">
    <span class="material-symbols-outlined">devices</span> <span data-i18n="box_settings">Box Settings</span>
  </a>

  <!-- Medicine Logs -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="logs">
    <span class="material-symbols-outlined">history</span> <span>Medicine Logs</span>
  </a>
  <!-- <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="users">
    <span class="material-symbols-outlined">manage_accounts</span> Manage Users
  </a> -->
</nav>

<div class="p-6 space-y-3">
  <a href="add_medicine.html"
     class="w-full bg-gradient-to-r from-primary to-primaryDark text-white py-3 rounded-xl font-bold shadow-lg hover:scale-[1.02] transition text-center block"
     data-i18n="add_medicine">
    + Add Medicine
  </a>

 
</div>



</aside>
<div id="overlay"
  class="fixed inset-0 bg-black/40 z-30 hidden">
</div>
<!-- MAIN -->
<main class="flex-1 flex flex-col">


<!-- TOPBAR -->

<header class="h-20 bg-white border-b border-slate-200 px-4 md:px-6 flex items-center justify-between">

  <!-- LEFT: Hamburger + Greeting -->
  <div class="flex items-center gap-4">
    <button id="menuBtn" class="text-textSub md:hidden">
      <span class="material-symbols-outlined text-3xl">menu</span>
    </button>

    <div class="flex flex-col md:flex-row md:items-baseline md:gap-3">
      <p class="text-sm md:text-lg font-semibold leading-snug">
        <span id="greeting">Good Morning</span>,
        <span class="text-primary dynamic-text">
          <?= htmlspecialchars($name) ?>
        </span>
      </p>
      <span id="liveClock" class="text-xs md:text-sm text-gray-500 font-normal"></span>
    </div>
  </div>

  <!-- RIGHT: Search + Icons -->
  <div class="flex items-center gap-4 min-w-[220px] md:min-w-[300px] justify-end">

    <!-- Search -->
   <div class="relative">
  <svg
    class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
  >
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M21 21l-4.35-4.35M16.65 10.5a6.15 6.15 0 11-12.3 0 6.15 6.15 0 0112.3 0z" />
  </svg>

  <input
    id="searchInput"
    type="text"
    placeholder="Search medicines..."
    class="w-72 h-10 rounded-full border border-slate-300 bg-slate-50 pl-9 pr-4 text-sm
           focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary
           transition"data-i18n-placeholder="search_medicines"
  />
</div>

    <!-- Notifications -->
    <button class="relative hidden md:inline-flex items-center justify-center w-10 h-10 rounded-full hover:bg-slate-100">
      <span class="material-symbols-outlined text-textSub">notifications</span>
    </button>
    <div class="relative">

  <div id="langMenu"
       class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-md z-50">
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-gray-100"
            data-lang="en">English</button>
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-gray-100"
            data-lang="ml">Malayalam</button>
  </div>
</div>


    <!-- Profile -->
   <div class="relative">
  <button id="profileBtn" class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm focus:outline-none">
    <?php if (!empty($profilePhoto)): ?>
      <img src="<?= $profilePhoto ?>" alt="Profile picture" class="w-full h-full object-cover">
    <?php else: ?>
      <div class="w-full h-full bg-primary/10 flex items-center justify-center">
        <span class="text-primary font-semibold text-sm"><?= htmlspecialchars($userInitials) ?></span>
      </div>
    <?php endif; ?>
  </button>

  <!-- Dropdown menu -->
  <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-xl shadow-lg z-50">
    <a href="profile.php" class="block px-4 py-2 text-textMain hover:bg-slate-100 rounded-t-xl">Profile</a>
    <a href="../../public/logout.php" class="block px-4 py-2 text-textMain hover:bg-slate-100 rounded-b-xl">Logout</a>
  </div>
</div>

  </div>

</header>



<!-- CONTENT -->
<div class="flex-1 overflow-y-auto p-8">
  <div class="max-w-[1500px] mx-auto space-y-8">

    <!-- Dashboard Section -->
    <div id="dashboard" class="section">
      <h2 class="text-2xl font-bold mb-4"data-i18n="dashboard_overview">Dashboard Overview</h2>
      <p data-i18n="dashboard_overview_text">Here’s your health overview for today.</p>

      <!-- STATS + DISEASE -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-4">
        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub" data-i18n="adherence">Adherence</p>
          
          <?php if ($totalMeds == 0): ?>
            <!-- No Medicines Added -->
            <h3 class="text-xl font-display font-bold mt-2 text-primary">Start Tracking</h3>
            <p class="text-xs text-textSub mt-1">Add medicine to know your adherence</p>
            <div class="mt-3">
               <a href="add_medicine.html" class="text-xs font-semibold text-primary bg-primary/10 px-3 py-2 rounded-lg hover:bg-primary/20 transition">
                 + Add Medicine
               </a>
            </div>

          <?php elseif (!$hasLogs): ?>
            <!-- Medicines exist, but no logs yet -->
            <h3 class="text-4xl font-display font-bold mt-2 text-slate-300">--%</h3>
            <p class="text-xs text-textSub mt-1">No logs recorded yet</p>
            <div class="h-2 bg-slate-100 rounded-full mt-4">
              <div class="h-2 bg-slate-300 rounded-full w-0"></div>
            </div>

          <?php else: ?>
            <!-- Active Tracking -->
            <h3 class="text-4xl font-display font-bold mt-2"><?= $adherencePct ?>%</h3>
            <div class="h-2 bg-slate-100 rounded-full mt-4">
              <div class="h-2 bg-primary rounded-full" style="width: <?= $adherencePct ?>%"></div>
            </div>
          <?php endif; ?>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub" data-i18n="missed_doses">Missed Doses</p>
         <h3 class="text-4xl font-display font-bold mt-2 text-danger dynamic-text"><?= $missedDosesCount ?></h3>
          <p class="text-xs text-textSub mt-2 dynamic-text">This week</p>

        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub" data-i18n="assigned_caretaker">Assigned Caretaker</p>
          <h3 class="text-2xl font-display font-bold mt-3 truncate" title="<?= htmlspecialchars($caretakerName) ?>"><?= htmlspecialchars($caretakerName) ?></h3>
          <p class="text-xs text-textSub mt-2 dynamic-text"><?= isset($caretakerRelation) ? 'Relation: ' . htmlspecialchars($caretakerRelation) : 'No caretaker linked' ?></p>
        </div>
        <!-- Disease / Conditions card -->
        <div class="bg-white p-6 rounded-2xl shadow-soft md:col-span-1">
          <p class="text-sm text-textSub">Disease / Conditions</p>
          <?php if (!empty($prescriptionDiseases)): ?>
            <?php $diseaseCount = count($prescriptionDiseases); ?>
            <div onclick="<?= $diseaseCount > 1 ? 'openDiseaseModal()' : '' ?>" class="<?= $diseaseCount > 1 ? 'cursor-pointer hover:bg-slate-50 transition p-2 -m-2 rounded-lg' : '' ?>">
                <p class="mt-2 text-sm text-textMain font-semibold truncate">
                    <?= htmlspecialchars($prescriptionDiseases[0]) ?>
                </p>
                <?php if ($diseaseCount > 1): ?>
                    <p class="text-xs text-primary font-medium mt-1 flex items-center gap-1">
                        +<?= $diseaseCount - 1 ?> more <span class="material-symbols-outlined text-[14px]">open_in_new</span>
                    </p>
                <?php endif; ?>
            </div>
          <?php else: ?>
            <p class="mt-2 text-xs text-textSub">
              No diseases recorded.
            </p>
          <?php endif; ?>
        </div>
      </div>

      <!-- SCHEDULE -->
      <div class="bg-white rounded-2xl shadow-soft mt-6">
        <div id="todaySchedule" class="p-6 space-y-6">
  <p class="text-sm text-textSub">Loading today’s schedule...</p>
</div>
      </div>

    </div>

    <!-- My Schedule (Medicines) -->
    <div id="schedule" class="section hidden">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-bold mb-2">My Medicines</h2>
          <p class="text-textSub">Manage your daily intake schedule</p>
        </div>
        <a href="export_medicines.php" class="bg-white border border-slate-200 text-textMain hover:bg-slate-50 px-5 py-3 rounded-xl font-semibold shadow-sm flex items-center gap-2 transition">
          <span class="material-symbols-outlined">download</span>
          Export CSV
        </a>
      </div>
      
    <div id="scheduleList" class="mt-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 auto-rows-fr">


    <!-- Medicine cards will be injected here -->
  </div>
    </div>

    <!-- Prescriptions -->
    <div id="prescriptions" class="section hidden">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-3xl font-bold mb-2">My Prescriptions</h2>
          <p class="text-textSub">Track all your medical prescriptions and prescribed tests</p>
        </div>
        <a href="add_prescription.html" class="bg-primary hover:bg-primaryDark text-white px-6 py-3 rounded-xl font-semibold flex items-center gap-2 transition">
          <span class="material-symbols-outlined">add</span>
          Add Prescription
        </a>
      </div>

      <!-- Prescriptions List -->
      <div id="prescriptionsList" class="grid grid-cols-1 gap-4 mt-6">
        <p class="text-textSub text-center py-8">Loading prescriptions...</p>
      </div>
    </div>


<!-- Assign Caretaker -->
<div id="assigned" class="section hidden p-6 md:p-10 bg-white rounded-3xl shadow-soft border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <div>
      <h2 class="text-4xl font-extrabold mb-2 text-textMain bg-gradient-to-r from-primary to-blue-600 bg-clip-text text-transparent" data-i18n="assign_caretaker">
        Assign Caretaker
      </h2>
      <p class="text-textSub text-sm md:text-base leading-relaxed" data-i18n="assign_caretaker_text">
        Add a trusted caretaker who can help manage your medicines and receive your alerts.
      </p>
    </div>
    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 text-emerald-700 text-xs md:text-sm font-medium whitespace-nowrap">
      <span class="material-symbols-outlined text-lg animate-pulse">verified_user</span>
      <span>Secure & Private Access</span>
    </div>
  </div>

  <!-- Two-column layout: form + list -->
  <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.2fr)] gap-8 items-start">
    <!-- Form -->
    <form id="assignCaretakerForm" class="space-y-6 bg-gradient-to-br from-slate-50 to-slate-100 p-7 md:p-8 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow duration-300">
      <div class="flex items-center gap-3 mb-2">
        <span class="material-symbols-outlined text-lg text-primary bg-blue-100 rounded-full p-2 w-10 h-10 flex items-center justify-center">person_add</span>
        <h3 class="text-lg font-bold text-textMain">Add New Caretaker</h3>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-semibold mb-2 text-textMain uppercase tracking-wide" for="caretakerName" data-i18n="caretaker_name">
            Full Name
          </label>
          <input type="text" id="caretakerName" name="name" required
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition placeholder-slate-400"
            placeholder="Enter full name">
        </div>

        <div>
          <label class="block text-sm font-semibold mb-2 text-textMain uppercase tracking-wide" for="caretakerEmail" data-i18n="caretaker_email">
            Email Address
          </label>
          <input type="email" id="caretakerEmail" name="email" required
            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition placeholder-slate-400"
            placeholder="name@example.com">
        </div>
      </div>

      <div>
        <label class="block text-sm font-semibold mb-2 text-textMain uppercase tracking-wide" for="relation" data-i18n="caretaker_relation">
          Relationship
        </label>
        <input type="text" id="relation" name="relation" required
          placeholder="e.g., Father, Sister, Friend"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary focus:bg-white transition placeholder-slate-400"
          data-i18n-placeholder="relation_placeholder">
      </div>

      <button type="submit"
        class="w-full md:w-auto bg-gradient-to-r from-primary to-blue-600 text-white py-3 px-7 rounded-xl text-sm font-bold hover:shadow-lg hover:scale-[1.02] transition-all transform duration-200 flex items-center justify-center gap-2"
        data-i18n="assign_caretaker_btn">
        <span class="material-symbols-outlined text-lg">add</span>
        Assign Caretaker
      </button>
    </form>

    <!-- Assigned Caretakers List -->
    <div class="space-y-5">
      <div class="flex items-center justify-between gap-3 pb-4 border-b-2 border-slate-200">
        <div class="flex items-center gap-3">
          <span class="material-symbols-outlined text-2xl text-primary bg-blue-100 rounded-full p-2">groups</span>
          <div>
            <h3 class="text-lg font-bold text-textMain">Your Caretakers</h3>
            <p class="text-xs text-textSub">Manage trusted contacts</p>
          </div>
        </div>
      </div>

      <div id="caretakerList" class="space-y-4">
        <!-- Dynamically filled by JS -->
      </div>
    </div>
  </div>
</div>


    <!-- Alerts -->
 <section id="alerts" class="section hidden p-6 md:p-10 bg-white rounded-3xl shadow-soft border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <div>
      <h2 class="text-4xl font-extrabold mb-2 text-textMain bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">Admin Alerts</h2>
      <p class="text-textSub text-sm md:text-base">Stay updated with important notifications from your administrator</p>
    </div>
    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 text-amber-700 text-xs md:text-sm font-medium">
      <span class="material-symbols-outlined text-lg">notifications_active</span>
      <span>Real-time Updates</span>
    </div>
  </div>

  <div id="alertsContainer" class="space-y-4">
    <!-- alerts will load here -->
  </div>
</section>

    <!-- Medicine Requests -->
    <div id="requests" class="section hidden p-6 md:p-10 bg-white rounded-3xl shadow-soft border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <div>
      <h2 class="text-4xl font-extrabold mb-2 text-textMain bg-gradient-to-r from-violet-600 to-purple-600 bg-clip-text text-transparent">Medicine Requests</h2>
      <p class="text-textSub text-sm md:text-base">Track and manage your medicine requests with status updates</p>
    </div>
    <div class="flex items-center gap-3">
      <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gradient-to-r from-violet-50 to-purple-50 border border-violet-200 text-violet-700 text-xs md:text-sm font-medium">
        <span class="material-symbols-outlined text-lg">inventory_2</span>
        <span id="requestCount" class="font-bold">0 Requests</span>
      </div>
      <a href="#" id="openRequestModal" class="bg-gradient-to-r from-primary to-blue-600 text-white px-5 py-3 rounded-xl font-bold hover:shadow-lg hover:scale-[1.02] transition-all transform duration-200 flex items-center gap-2 text-sm whitespace-nowrap">
        <span class="material-symbols-outlined text-lg">add_circle</span>
        Add Request
      </a>
    </div>
  </div>
  <div id="requestsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>
</div>

<!-- Box Settings Section -->
<div id="boxSettings" class="section hidden p-6 bg-white rounded-3xl shadow-soft border border-slate-100">
  <!-- Minimal Header -->
  <h2 class="text-2xl font-bold text-textMain mb-6">Smart Medicine Box</h2>

  <!-- STRICT MINIMALIST BOX SETTINGS [Refined] -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- LEFT COLUMN (Launch & Status & Settings) - Spans 4 cols -->
    <div class="lg:col-span-4 space-y-6">
      
      <!-- 1. SYSTEM LAUNCH -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h3 class="text-lg font-bold text-textMain mb-4">System Launch</h3>
        <a href="../../public/simulation/index.php" target="_blank" 
           class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-4 px-6 rounded-xl text-center shadow-lg transition-transform active:scale-95">
           <span class="block text-xl font-bold">Start System</span>
           <span class="block text-sm text-blue-100 mt-1 flex items-center justify-center gap-2">
             <span class="size-2 rounded-full bg-blue-300"></span> System Idle
           </span>
        </a>
      </div>

      <!-- 2. ESP32 CONNECTION STATUS -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3">
           <div class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
            </div>
           <div>
             <h4 class="font-bold text-textMain text-sm">ESP32 Connected</h4>
             <p class="text-xs text-textSub">Signal: Good</p>
           </div>
        </div>
        <span class="material-symbols-outlined text-green-500">wifi</span>
      </div>

      <!-- 4. BASIC SETTINGS -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h3 class="text-lg font-bold text-textMain mb-4">Basic Settings</h3>
        <div class="space-y-5">
           <!-- Buzzer -->
           <div class="flex items-center justify-between">
             <span class="text-base font-medium text-textMain">Buzzer Sound</span>
             <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
           </div>
           
           <!-- Reminder Window -->
           <div class="flex items-center justify-between">
             <span class="text-base font-medium text-textMain">Reminder Window</span>
             <select class="form-select text-sm border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 bg-slate-50">
               <option>15 mins</option>
               <option selected>30 mins</option>
               <option>1 hour</option>
             </select>
           </div>
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN (Medicine Slots) - Spans 8 cols -->
    <div class="lg:col-span-8">
      <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm h-full">
         <div class="flex items-center justify-between mb-6">
           <h3 class="text-2xl font-bold text-textMain">Medicine Slots</h3>
         </div>
         
         <!-- SLOTS GRID -->
         <div class="grid grid-cols-2 gap-6 h-full">
           <?php for ($i = 1; $i <= 4; $i++): 
              $slotData = $boxSlots[$i] ?? null;
              $isEmpty = empty($slotData);
           ?>
             <a href="<?php echo $isEmpty ? 'add_medicine.html?slot='.$i : '#'; ?>" 
                class="group relative aspect-[4/3] rounded-2xl border-2 <?php echo $isEmpty ? 'border-dashed border-slate-300 hover:border-blue-400 bg-slate-50' : 'border-slate-100 bg-blue-50/50 hover:border-blue-500'; ?> transition-all flex flex-col items-center justify-center text-center p-4">
                
                <!-- Physical Slot Number -->
                <div class="absolute top-4 left-4 size-8 rounded-full bg-white border border-slate-200 flex items-center justify-center font-bold text-slate-500 shadow-sm">
                  <?php echo $i; ?>
                </div>

                <?php if ($isEmpty): ?>
                  <span class="material-symbols-outlined text-4xl text-slate-300 mb-2 group-hover:text-blue-500 transition-colors">add_circle</span>
                  <span class="text-slate-500 font-medium group-hover:text-blue-600">Empty Slot</span>
                  <span class="text-xs text-slate-400 mt-1">Tap to add medicine</span>
                <?php else: ?>
                  <span class="material-symbols-outlined text-4xl text-blue-600 mb-2">pill</span>
                  <h4 class="text-xl font-bold text-textMain mb-1"><?php echo htmlspecialchars($slotData['name']); ?></h4>
                  
                   <?php 
                      // Simple status badge
                      $status = $slotData['status'] ?? 'Scheduled';
                      $badgeColor = match(strtolower($status)) {
                          'taken' => 'bg-green-100 text-green-700',
                          'missed' => 'bg-red-100 text-red-700',
                          default => 'bg-blue-100 text-blue-700'
                      };
                   ?>
                   <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?php echo $badgeColor; ?>">
                     <?php echo htmlspecialchars($status); ?>
                   </span>
                <?php endif; ?>

             </a>
           <?php endfor; ?>
         </div>
      </div>
    </div>

  </div>
</div>

    <!-- MEDICINE LOGS SECTION -->
    <div id="logs" class="section hidden p-6 md:p-10 bg-white rounded-3xl shadow-soft border border-slate-100">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-4xl font-extrabold mb-2 text-textMain bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Medicine Logs</h2>
          <p class="text-textSub text-sm md:text-base">History of your taken and missed doses</p>
        </div>
        <button onclick="loadMedicineLogs()" class="p-3 bg-slate-100 hover:bg-slate-200 rounded-full transition-colors text-slate-600" title="Refresh Logs">
          <span class="material-symbols-outlined">refresh</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-xs font-bold text-textSub uppercase tracking-wider">Medicine</th>
                <th class="px-6 py-4 text-xs font-bold text-textSub uppercase tracking-wider">Dosage</th>
                <th class="px-6 py-4 text-xs font-bold text-textSub uppercase tracking-wider">Time</th>
                <th class="px-6 py-4 text-xs font-bold text-textSub uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody id="logsTableBody" class="divide-y divide-slate-100 text-sm">
              <tr>
                <td colspan="4" class="px-6 py-8 text-center text-textSub">
                  Loading logs...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Manage Users -->
    <div id="users" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">Manage Users</h2>
      <p>Admin panel for user management.</p>
    </div>

  </div>
</div>
<!-- View Medicine Modal -->
<div id="viewMedicineModal" class="fixed inset-0 bg-black/40 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-xl p-6 md:p-8 relative max-h-screen overflow-y-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-2xl font-bold text-textMain">Medicine Details</h2>
      <button id="closeViewModal" class="text-gray-400 hover:text-gray-600 text-xl">
        ✕
      </button>
    </div>

    <!-- Content -->
    <div id="medicineDetails" class="space-y-4">

      <!-- Filled dynamically by JS -->

    </div>

  </div>
</div>
<!-- Confirm Modal -->
<div id="confirmModal" class="fixed inset-0 flex items-center justify-center bg-black/40 hidden z-50">
  <div id="confirmBox" class="bg-white rounded-3xl shadow-xl p-6 md:p-8 w-96 scale-95 opacity-0 transition-all">
    <h3 id="confirmMessage" class="text-lg font-semibold text-textMain mb-4">Are you sure?</h3>
    <div class="flex justify-end gap-4">
      <button id="confirmNo" class="px-4 py-2 rounded-xl border border-gray-300 hover:bg-gray-100">No</button>
      <button id="confirmYes" class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primaryDark">Yes</button>
    </div>
  </div>
</div>

<!-- Request Medicine Modal -->
<div id="requestMedicineModal" class="fixed inset-0 bg-black/40 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl p-6 md:p-10 relative max-h-screen overflow-y-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
      <div>
        <h2 class="text-3xl font-bold text-textMain">Request New Medicine</h2>
        <p class="text-sm text-textSub mt-1">Submit a medicine that's not in our catalog</p>
      </div>
      <button id="closeRequestModal" class="text-gray-400 hover:text-gray-600 text-2xl transition">
        ✕
      </button>
    </div>

    <!-- Form Content -->
    <form id="requestMedicineForm" class="space-y-6">
      <!-- Medicine Name -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-textMain uppercase tracking-wide" for="requestMedicineName">
          Medicine Name <span class="text-red-500">*</span>
        </label>
        <input 
          type="text" 
          id="requestMedicineName" 
          name="medicine_name" 
          required
          placeholder="e.g., Aspirin, Ibuprofen"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary transition"
        >
        <p class="text-xs text-textSub">Enter the name of the medicine you need</p>
      </div>

      <!-- Dosage -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-textMain uppercase tracking-wide" for="requestDosage">
          Dosage <span class="text-red-500">*</span>
        </label>
        <input 
          type="text" 
          id="requestDosage" 
          name="dosage" 
          placeholder="e.g., 500mg, 2ml, 10 units"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary transition"
        >
        <p class="text-xs text-textSub">Specify the strength or quantity</p>
      </div>

      <!-- Medicine Form/Type -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-textMain uppercase tracking-wide">
          Form <span class="text-red-500">*</span>
        </label>
        <div class="grid grid-cols-3 gap-3">
          <label class="cursor-pointer">
            <input type="radio" name="form" value="Pill" checked class="hidden peer">
            <div class="p-3 rounded-xl border border-slate-300 text-center peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary hover:border-primary transition">
              <span class="material-symbols-outlined block text-2xl mb-1">pill</span>
              <span class="text-xs font-medium">Pill</span>
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="form" value="Liquid" class="hidden peer">
            <div class="p-3 rounded-xl border border-slate-300 text-center peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary hover:border-primary transition">
              <span class="material-symbols-outlined block text-2xl mb-1">water_drop</span>
              <span class="text-xs font-medium">Liquid</span>
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="form" value="Injection" class="hidden peer">
            <div class="p-3 rounded-xl border border-slate-300 text-center peer-checked:bg-primary/10 peer-checked:border-primary peer-checked:text-primary hover:border-primary transition">
              <span class="material-symbols-outlined block text-2xl mb-1">syringe</span>
              <span class="text-xs font-medium">Injection</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Reason/Notes -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-textMain uppercase tracking-wide" for="requestReason">
          Reason (Optional)
        </label>
        <textarea 
          id="requestReason" 
          name="reason" 
          placeholder="e.g., Prescribed by Dr. Smith for hypertension"
          rows="3"
          class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white focus:ring-2 focus:ring-primary focus:border-primary transition resize-none"
        ></textarea>
        <p class="text-xs text-textSub">Help us understand why you need this medicine (optional)</p>
      </div>

      <!-- Buttons -->
      <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
        <button 
          type="submit" 
          class="flex-1 bg-gradient-to-r from-primary to-blue-600 text-white py-3 px-6 rounded-xl text-sm font-bold hover:shadow-lg hover:scale-[1.02] transition-all transform duration-200 flex items-center justify-center gap-2"
        >
          <span class="material-symbols-outlined text-lg">send</span>
          Submit Request
        </button>
        <button 
          type="button" 
          id="cancelRequestModal" 
          class="flex-1 bg-slate-100 text-textMain py-3 px-6 rounded-xl text-sm font-bold hover:bg-slate-200 transition"
        >
          Cancel
        </button>
      </div>
    </form>

    <!-- Success Message -->
    <div id="requestSuccessMessage" class="hidden mt-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
      <p class="text-green-800 font-semibold">✓ Request submitted successfully!</p>
      <p class="text-sm text-green-700 mt-1">The admin team will review your request soon.</p>
    </div>
  </div>
</div>



<!-- NAVIGATION JS -->
<script>
/* =====================================================
   DOM ELEMENTS
===================================================== */
const navItems = document.querySelectorAll('.nav-item');
const sections = document.querySelectorAll('.section');

const sidebar = document.getElementById("sidebar");
const menuBtn = document.getElementById("menuBtn");
const overlay = document.getElementById("overlay");

const searchInput = document.getElementById("searchInput");

const form = document.getElementById("assignCaretakerForm");
const list = document.getElementById("caretakerList");

const langBtn = document.getElementById("langBtn");
const langMenu = document.getElementById("langMenu");
const langOptions = document.querySelectorAll(".lang-option");


/* =====================================================
   STATE
===================================================== */
let currentSection = "dashboard";
let previousSectionBeforeSearch = null;
let allMedicines = [];

// Translation system is now handled by translations.js
/* =====================================================
   Real Time clock
===================================================== */
function updateClockAndGreeting() {
    const now = new Date();
    const hour = now.getHours();

    let greeting = "Good Morning";
    if (hour >= 12 && hour < 17) {
      greeting = "Good Afternoon";
    } else if (hour >= 17 || hour < 5) {
      greeting = "Good Evening";
    }

    document.getElementById("greeting").textContent = greeting;

    const options = {
      month: 'long',
      year: 'numeric',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      second: '2-digit',
      hour12: true
    };

    const formatted = now.toLocaleString('en-US', options);
    document.getElementById("liveClock").textContent = `• ${formatted}`;
  }

  updateClockAndGreeting();
  setInterval(updateClockAndGreeting, 1000);
/* =====================================================
   SECTION SWITCHING
===================================================== */
function switchSection(sectionId) {
  sections.forEach(sec => sec.classList.add("hidden"));

  const target = document.getElementById(sectionId);
  if (target) target.classList.remove("hidden");

  navItems.forEach(nav => {
    nav.classList.remove("bg-primary/10", "text-primary");
    nav.classList.add("text-textSub");
  });

  const activeNav = document.querySelector(`[data-section="${sectionId}"]`);
  if (activeNav) {
    activeNav.classList.add("bg-primary/10", "text-primary");
    activeNav.classList.remove("text-textSub");
  }

  currentSection = sectionId;
}


/* =====================================================
   SIDEBAR CONTROLS
===================================================== */
function openSidebar() {
  sidebar.classList.remove("-translate-x-full");
  overlay.classList.remove("hidden");
}

function closeSidebar() {
  sidebar.classList.add("-translate-x-full");
  overlay.classList.add("hidden");
}


/* =====================================================
   MEDICINE SCHEDULE
===================================================== */
async function loadSchedule() {
 
  const container = document.getElementById("scheduleList");
  container.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch("fetch_medicine.php?t=" + Date.now());
    const data = await res.json();

    if (data.status !== "success") {
      container.innerHTML = "<p>Failed to load medicines</p>";
      return;
    }

    allMedicines = data.medicines;
    renderMedicines(allMedicines);
  } catch (err) {
    console.error(err);
    container.innerHTML = "<p>Server error</p>";
  }
}function renderMedicines(medicines) {
  const container = document.getElementById("scheduleList");
  container.innerHTML = "";

  if (!medicines.length) {
    container.innerHTML = `
      <div class="bg-surface-light p-6 rounded-xl shadow-sm text-center text-textSub">
        No medicines added yet.
      </div>`;
    return;
  }

  medicines.forEach(med => {
    // Status
    let statusLabel = "Active";
    let statusBg = "bg-green-50";
    let statusText = "text-green-600";

    const today = new Date();
    if (med.end_date && med.end_date !== "0000-00-00") {
      const endDate = new Date(med.end_date);
      const diffDays = Math.ceil((endDate - today) / (1000 * 60 * 60 * 24));

      if (diffDays < 0) {
        statusLabel = "Expired";
        statusBg = "bg-gray-100";
        statusText = "text-gray-600";
      } else if (diffDays <= 7) {
        statusLabel = "Expiring";
        statusBg = "bg-amber-50";
        statusText = "text-amber-600";
      }
    }

    // Start & End date formatting
    const startDate = med.start_date ? new Date(med.start_date).toLocaleDateString() : "-";
    const endDate = med.end_date ? new Date(med.end_date).toLocaleDateString() : "-";

    // Example inventory (optional, can pull from DB)
    const total = med.dosage_value || 60; // default total
    const remaining = Math.floor(total * 0.75); // just for demo
    const percent = Math.min(Math.max((remaining / total) * 100, 0), 100);

    const card = document.createElement("div");
    card.className = "bg-white rounded-2xl p-4 shadow-sm border border-slate-100 hover:shadow-md transition-all group relative overflow-hidden flex flex-col justify-between";

    card.innerHTML = `
      <div class="mb-3">
        <div class="flex justify-between items-start mb-3">
            <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center shadow-sm">
                <span class="material-symbols-outlined text-2xl">pill</span>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800 leading-tight line-clamp-1" title="${med.name}">${med.name}</h3>
                <p class="text-slate-500 text-xs font-medium">${med.dosage_value || 'N/A'} • ${med.medicine_type || 'Pill'}</p>
            </div>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusBg} ${statusText}">
                ${statusLabel}
            </span>
        </div>

        <div class="bg-slate-50/50 rounded-lg p-3 border border-slate-100 mb-3 space-y-1.5">
            <div class="flex justify-between text-xs">
                <span class="text-slate-500 font-medium">Frequency</span>
                <span class="text-slate-700 font-semibold">${med.schedule_type || 'Daily'}</span>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-slate-500 font-medium">End</span>
                <span class="text-slate-700 font-semibold">${endDate}</span>
            </div>
        </div>

        <!-- Inventory Bar -->
        <div class="mb-1">
            <div class="flex justify-between text-[10px] mb-1">
            <span class="text-slate-400 font-medium">Stock</span>
            <span class="text-slate-600 font-bold">${remaining}/${total}</span>
            </div>
            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-500" style="width: ${percent}%"></div>
            </div>
        </div>
      </div>

      <div class="flex gap-2 pt-3 border-t border-slate-100 mt-auto">
        <button onclick="viewMedicine(${med.id})" 
           class="flex-1 py-1.5 rounded-lg bg-slate-50 text-slate-600 font-medium hover:bg-white hover:text-primary hover:shadow-sm border border-slate-200 transition-all text-xs flex items-center justify-center gap-1.5">
           <span class="material-symbols-outlined text-base">visibility</span> View
        </button>
        <button onclick="deleteMedicine(${med.id})" 
           class="flex-1 py-1.5 rounded-lg bg-slate-50 text-slate-600 font-medium hover:bg-red-50 hover:text-red-500 hover:border-red-100 border border-slate-200 transition-all text-xs flex items-center justify-center gap-1.5">
           <span class="material-symbols-outlined text-base">delete</span> Remove
        </button>
      </div>
    `;

    container.appendChild(card);
  });
}



// Delete function
function deleteMedicine(id) {
  // Open the custom confirm modal instead of using confirm()
  openConfirmModal("Are you sure you want to delete this medicine?", async () => {
    try {
      const res = await fetch("delete_medicine.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id, action: 'delete' })
      });

      const data = await res.json();
      if (data.status === "success") {
        showToast('Medicine deleted successfully', false);
        loadSchedule();
      } else {
        showToast("Failed to delete medicine", true);
      }
    } catch (err) {
      console.error(err);
      showToast("Server error", 'error');
    }
  });
}


// Example placeholders
function requestRefill(id) { alert("Refill requested for medicine ID " + id); }
function openEditModal(id) { console.log("Open edit modal for", id); }

  // Attach DELETE button event listeners
 


async function loadTodaySchedule() {
  const container = document.getElementById("todaySchedule");
  container.innerHTML = "<p class='text-sm text-textSub'>Loading...</p>";

  try {
    const res = await fetch("todays_schedule.php");
    const data = await res.json();

    if (data.status !== "success" || !data.schedule.length) {
      container.innerHTML = "<p class='text-sm text-textSub'>No medicines scheduled for today.</p>";
      return;
    }

    container.innerHTML = "";

    data.schedule.forEach(item => {
      let block = "";

      if (item.status === "Taken" || item.status === "Missed") {
        const isMissed = item.status === "Missed";
        block = `
          <div class="flex items-start gap-6 opacity-75">
            <div class="w-12 h-12 rounded-full ${isMissed ? 'bg-red-50 text-red-500' : 'bg-success/10 text-success'} flex items-center justify-center">
              <span class="material-symbols-outlined">${isMissed ? 'close' : 'check'}</span>
            </div>
            <div>
              <p class="text-sm text-textSub">${formatTime(item.intake_time)} • <span class="font-bold ${isMissed ? 'text-red-500' : 'text-green-600'}">${item.status}</span></p>
              <h4 class="font-bold line-through text-slate-500">${item.name} ${item.dosage}</h4>
            </div>
          </div>
        `;
      } else {
        block = `
          <div class="flex items-start gap-6">
            <div class="size-12 rounded-full bg-primary text-white flex items-center justify-center animate-pulse">
              <span class="material-symbols-outlined">pill</span>
            </div>
            <div class="flex-1 bg-primary/10 p-6 rounded-2xl">
              <p class="text-sm text-primary font-semibold">
                ${item.status === "Pending" ? "Upcoming" : item.status} • ${formatTime(item.intake_time)}
              </p>
              <h4 class="text-xl font-display font-bold">
                ${item.name} ${item.dosage}
              </h4>
              <div class="flex gap-3 mt-4">
                <button onclick="markDose(${item.schedule_id}, 'TAKEN')" class="flex-1 bg-primary hover:bg-primaryDark transition text-white py-2 rounded-xl font-bold shadow-sm">
                  Mark Taken
                </button>
                <button onclick="markDose(${item.schedule_id}, 'MISSED')" class="flex-1 border border-slate-300 hover:bg-slate-50 transition py-2 rounded-xl font-semibold text-slate-600">
                  Skip
                </button>
              </div>
            </div>
          </div>
        `;
      }

      container.insertAdjacentHTML("beforeend", block);
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = "<p class='text-sm text-textSub'>Error loading schedule.</p>";
  }
}

function formatTime(timeStr) {
  if (!timeStr) return "-"; // handle null or empty
  const [h, m] = timeStr.split(":");
  const date = new Date();
  date.setHours(h, m);
  // Returns: "Jan 1, 12:00 PM"
  return date.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

document.addEventListener("DOMContentLoaded", loadTodaySchedule);

async function markDose(scheduleId, status) {
  if (!confirm(`Are you sure you want to mark this as ${status.toLowerCase()}?`)) return;

  try {
    // Optimistic Update? No, let's wait for server to be safe
    const res = await fetch("log_dose.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ schedule_id: scheduleId, status: status })
    });

    const data = await res.json();
    if (data.status === "success") {
      showToast(`Medicine marked as ${status.toLowerCase()}`);
      loadTodaySchedule(); // refresh list
    } else {
      showToast(data.message || "Failed to update status", true);
    }
  } catch (err) {
    console.error(err);
    showToast("Server connection failed", true);
  }
}

/* =====================================================
   SEARCH
===================================================== */
searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase().trim();

  if (query && !previousSectionBeforeSearch) {
    previousSectionBeforeSearch = currentSection;
    switchSection("schedule");
    loadSchedule(); // always load before filtering
  }

  if (!query) {
    if (previousSectionBeforeSearch) {
      switchSection(previousSectionBeforeSearch);
      previousSectionBeforeSearch = null;
    }
    renderMedicines(allMedicines);
    return;
  }

  const filtered = allMedicines.filter(med =>
    (med.name || "").toLowerCase().includes(query)
  );

  renderMedicines(filtered);
});



/* =====================================================
   CARETAKER
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  const assignForm = document.getElementById("assignCaretakerForm");
  
  if (assignForm) {
    assignForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(assignForm);

      try {
        const res = await fetch("assign_caretaker.php", {
          method: "POST",
          body: formData
        });
        
        // Handle potential JSON parsing error if PHP returns HTML warning/error
        let data;
        const text = await res.text();
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Invalid JSON:", text);
            showToast("Server returned invalid response", true);
            return;
        }

        if (data.status === 'success') {
             showToast("Caretaker assigned successfully");
             loadCaretakers();
             assignForm.reset();
        } else {
             showToast(data.message || "Failed to assign caretaker", true);
        }
      } catch (err) {
        console.error(err);
        showToast("Error assigning caretaker", true);
      }
    });
  }
});
async function loadCaretakers() {
  const list = document.getElementById("caretakerList");
  list.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch("fetch_caretaker.php");
    const data = await res.json();

    const caretakers = data.caretakers || [];
    const submitBtn = form.querySelector('button[type="submit"]');

    if (caretakers.length >= 1) {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="material-symbols-outlined text-lg">lock</span> Upgrade to add more';
        submitBtn.className = "w-full bg-slate-300 text-slate-500 py-3 rounded-xl font-bold cursor-not-allowed flex items-center justify-center gap-2";
        submitBtn.title = "Upgrade to premium to add more caretakers";
      }
    } else {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Assign Caretaker';
        submitBtn.className = "w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-primaryDark transition shadow-lg shadow-primary/30";
        submitBtn.removeAttribute('title');
      }
    }

    if (caretakers.length === 0) {
      list.innerHTML = "<p>No caretakers assigned yet.</p>";
      return;
    }

    list.innerHTML = "";
    caretakers.forEach(cg => {
      const card = document.createElement("div");
  card.className = "p-5 bg-white rounded-2xl shadow-soft border border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4";

  card.innerHTML = `
    <div class="flex items-start gap-3 flex-1">
      <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
        <span class="material-symbols-outlined">person</span>
      </div>
      <div class="space-y-1 w-full">
        <h3 class="font-bold text-base md:text-lg">${cg.name}</h3>
        <p class="text-sm text-textSub">${cg.email}</p>
        <p class="text-xs text-textSub uppercase tracking-wide">Relation: <span class="capitalize">${cg.relation}</span></p>
        <div class="mt-3 flex flex-col sm:flex-row gap-2">
          <input type="text" placeholder="Type message..." class="messageInput flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
          <button class="sendBtn px-4 py-2 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primaryDark transition">
            Send
          </button>
        </div>
      </div>
    </div>
    <button onclick="removeCaretaker(${cg.id})" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition self-start" title="Remove Caretaker">
       <span class="material-symbols-outlined">delete</span>
    </button>
  `;

  list.appendChild(card);

  const sendBtn = card.querySelector(".sendBtn");
  const messageInput = card.querySelector(".messageInput");

  sendBtn.addEventListener("click", async () => {
    const message = messageInput.value.trim();
    if (!message) {
      showToast("Please enter a message", true);
      return;
    }

    try {
      const res = await fetch("send_message.php?action=send", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ caretaker_id: cg.id, message })
      });
      
      const text = await res.text();
      let data;
      try {
          data = JSON.parse(text);
      } catch (e) {
          console.error("Server invalid response:", text);
          showToast("Server error: " + text.substring(0, 50), true);
          return;
      }
      if (data.status === "success") {
        showToast("Message sent to caretaker");
        messageInput.value = "";
      } else {
        showToast(data.msg || "Failed to send message", true);
      }
    } catch (err) {
      console.error(err);
      showToast("Server error while sending message", true);
    }
  });
});

  } catch (err) {
    console.error(err);
    list.innerHTML = "<p>Server error</p>";
  }
}

async function removeCaretaker(id) {
    if (!confirm("Are you sure you want to remove this caretaker?")) return;

    try {
        const res = await fetch("remove_caretaker.php", { 
            method: "POST",
            headers: { "Content-Type": "application/json" }
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            showToast("Caretaker removed successfully", false);
            loadCaretakers(); // Reloads list and re-enables Assign button
        } else {
            showToast("Failed to remove caretaker", true);
        }
    } catch (err) {
        console.error(err);
        showToast("Error removing caretaker", true);
    }
}

// Simple toast notification for patient actions (e.g., messages to caretaker)
function showToast(message, isError = false) {
  let toast = document.getElementById("globalToast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "globalToast";
    toast.className = "fixed top-4 right-4 z-[9999] px-4 py-2 rounded-xl shadow-lg text-sm font-medium text-white transition transform translate-y-[-10px] opacity-0";
    document.body.appendChild(toast);
  }

  toast.textContent = message;
  toast.classList.remove("bg-green-500", "bg-red-500");
  toast.classList.add(isError ? "bg-red-500" : "bg-green-500");

  requestAnimationFrame(() => {
    toast.classList.remove("translate-y-[-10px]", "opacity-0");
    toast.classList.add("translate-y-0", "opacity-100");
  });

  setTimeout(() => {
    toast.classList.remove("translate-y-0", "opacity-100");
    toast.classList.add("translate-y-[-10px]", "opacity-0");
  }, 2500);
}



/* =====================================================
   LANGUAGE TRANSLATION (using new translation system)
===================================================== */
// Language selector is initialized by language-selector.js
// Translation system is handled by translations.js


/* =====================================================
   NAV EVENTS
===================================================== */
menuBtn.addEventListener("click", () => {
  sidebar.classList.contains("-translate-x-full")
    ? openSidebar()
    : closeSidebar();
});

overlay.addEventListener("click", closeSidebar);
sidebar.addEventListener("click", e => e.stopPropagation());

navItems.forEach(item => {
  item.addEventListener("click", e => {
    e.preventDefault();

    const section = item.dataset.section;
    switchSection(section);

    if (section === "schedule") {
      loadSchedule(); 
    }

    closeSidebar();
  });
});

//edit my medicines
// Modal elements




document.querySelector('[data-section="alerts"]').addEventListener('click', () => {
  loadAlerts();
});

async function loadAlerts() {
  const container = document.getElementById('alertsContainer');
  container.innerHTML = '<div class="py-12 text-center"><span class="material-symbols-outlined text-5xl text-slate-300 block mb-4 animate-spin">settings</span><p class="text-slate-600 font-medium">Loading alerts...</p></div>';

  try {
    const res = await fetch('get_alerts.php');
    const data = await res.json();

    container.innerHTML = '';

    if (data.status !== 'success' || data.alerts.length === 0) {
      container.innerHTML = `
        <div class="py-16 text-center">
          <span class="material-symbols-outlined text-6xl text-slate-300 block mb-4">notifications_none</span>
          <p class="text-slate-500 text-lg font-medium">
            No alerts from admin
          </p>
          <p class="text-slate-400 text-sm mt-2">Check back later for updates</p>
        </div>`;
      return;
    }

    data.alerts.forEach((alert, index) => {
      const alertTime = new Date(alert.created_at);
      const isRecent = (Date.now() - alertTime.getTime()) < 3600000;
      
      container.innerHTML += `
        <div class="group relative bg-white p-6 rounded-2xl shadow-soft border border-slate-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
          <!-- Decorative accent -->
          <div class="absolute left-0 top-6 bottom-6 w-1.5 bg-gradient-to-b from-primary to-blue-400 rounded-r-full"></div>
          
          <div class="pl-5 relative z-10">
            <div class="flex items-start justify-between gap-4 mb-3">
              <div class="flex items-center gap-3">
                 <div class="p-2 bg-blue-50 text-primary rounded-xl">
                    <span class="material-symbols-outlined text-2xl">admin_panel_settings</span>
                 </div>
                 <h4 class="font-bold text-textMain text-sm uppercase tracking-wider">Admin Announcement</h4>
              </div>
              ${isRecent ? `
              <span class="relative inline-flex h-6 items-center justify-center px-3 rounded-full bg-blue-600 text-[10px] font-bold text-white tracking-wide shadow-md shadow-blue-200">
                NEW
                <span class="absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-20 animate-ping"></span>
              </span>` : ''}
            </div>

            <p class="text-slate-700 text-base font-medium leading-relaxed mb-4">
              ${alert.message}
            </p>

            <div class="flex items-center gap-2 text-xs text-textSub font-medium pt-3 border-t border-slate-100">
              <span class="material-symbols-outlined text-base">schedule</span>
              <span>${alertTime.toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
            </div>
          </div>
        </div>
      `;
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = `
      <div class="py-12 text-center">
        <span class="material-symbols-outlined text-6xl text-red-300 block mb-4">error_outline</span>
        <p class="text-red-600 text-lg font-medium">
          Failed to load alerts
        </p>
        <p class="text-red-500 text-sm mt-2">Please try again later</p>
      </div>`;
  }
}
const profileBtn = document.getElementById("profileBtn");
const profileDropdown = document.getElementById("profileDropdown");

// Toggle dropdown
profileBtn.addEventListener("click", (e) => {
  e.stopPropagation();
  profileDropdown.classList.toggle("hidden");
});

// Close dropdown if clicked outside
document.addEventListener("click", () => {
  profileDropdown.classList.add("hidden");
});

// Stop propagation inside dropdown so it doesn't close immediately
profileDropdown.addEventListener("click", (e) => e.stopPropagation());

// Request Medicine Modal Handler
const requestModal = document.getElementById("requestMedicineModal");
const openRequestModalBtn = document.getElementById("openRequestModal");
const closeRequestModalBtn = document.getElementById("closeRequestModal");
const cancelRequestModalBtn = document.getElementById("cancelRequestModal");
const requestMedicineForm = document.getElementById("requestMedicineForm");

// Open modal
openRequestModalBtn.addEventListener("click", (e) => {
  e.preventDefault();
  requestModal.classList.remove("hidden");
  requestMedicineForm.reset();
  document.getElementById("requestSuccessMessage").classList.add("hidden");
});

// Close modal
function closeRequestModal() {
  requestModal.classList.add("hidden");
}

closeRequestModalBtn.addEventListener("click", closeRequestModal);
cancelRequestModalBtn.addEventListener("click", closeRequestModal);

// Close modal when clicking outside
requestModal.addEventListener("click", (e) => {
  if (e.target === requestModal) {
    closeRequestModal();
  }
});

// Handle form submission
requestMedicineForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  
  const formData = new FormData(requestMedicineForm);
  const data = {
    name: formData.get("medicine_name"),
    dosage: formData.get("dosage"),
    form: formData.get("form"),
    reason: formData.get("reason")
  };

  try {
    const response = await fetch("request_new_medicine.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(data)
    });

    const result = await response.json();

    if (result.status === "success") {
      // Show success message
      document.getElementById("requestSuccessMessage").classList.remove("hidden");
      requestMedicineForm.style.display = "none";
      
      // Reload requests after 2 seconds
      setTimeout(() => {
        closeRequestModal();
        loadMedicineRequests();
      }, 2000);
    } else {
      alert("Error: " + result.message);
    }
  } catch (error) {
    console.error("Error:", error);
    alert("Failed to submit request. Please try again.");
  }
});

async function loadMedicineRequests() {
  const container = document.getElementById("requestsContainer");
  const countBadge = document.getElementById("requestCount");
  container.innerHTML = '<div class="col-span-full py-16 text-center"><span class="material-symbols-outlined text-5xl text-slate-300 block mb-4 animate-spin">settings</span><p class="text-slate-600 font-medium">Loading requests...</p></div>';

  try {
    const res = await fetch("fetch_req.php");
    const data = await res.json();

    if (data.status !== "success" || !data.requests.length) {
      container.innerHTML = '<div class="col-span-full py-16 text-center"><span class="material-symbols-outlined text-6xl text-slate-300 block mb-4">inbox</span><p class="text-slate-500 text-lg font-medium">No medicine requests yet</p><p class="text-slate-400 text-sm mt-2">Start by creating a new medicine request</p></div>';
      countBadge.textContent = "0 Requests";
      return;
    }

    countBadge.textContent = `${data.requests.length} Request${data.requests.length !== 1 ? 's' : ''}`;
    container.innerHTML = '';

    data.requests.forEach(req => {
      let statusConfig = {
        pending: { 
          bg: 'bg-white', 
          border: 'border-blue-500', 
          accent: 'bg-blue-500',
          badge: 'bg-blue-100 text-blue-700', 
          label: 'Pending Review', 
          iconColor: 'text-blue-600',
          iconBg: 'bg-blue-50'
        },
        approved: { 
          bg: 'bg-white', 
          border: 'border-emerald-500', 
          accent: 'bg-emerald-500',
          badge: 'bg-emerald-100 text-emerald-700', 
          label: 'Approved', 
          iconColor: 'text-emerald-600',
          iconBg: 'bg-emerald-50'
        },
        rejected: { 
          bg: 'bg-white', 
          border: 'border-rose-500', 
          accent: 'bg-rose-500',
          badge: 'bg-rose-100 text-rose-700', 
          label: 'Rejected', 
          iconColor: 'text-rose-600',
          iconBg: 'bg-rose-50'
        }
      };
      
      let config = statusConfig[req.status.toLowerCase()] || statusConfig.pending;
      const requestDate = new Date(req.created_at);
      const daysAgo = Math.floor((Date.now() - requestDate.getTime()) / (1000 * 60 * 60 * 24));
      let timeText = daysAgo === 0 ? 'Today' : daysAgo === 1 ? 'Yesterday' : `${daysAgo} days ago`;

      let html = `
        <div class="group relative bg-white p-6 rounded-2xl shadow-soft border border-slate-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 overflow-hidden">
          <!-- Left Accent Strip -->
          <div class="absolute left-0 top-0 bottom-0 w-1 ${config.accent}"></div>
          
          <div class="relative z-10 pl-2">
            <div class="flex items-start justify-between gap-4 mb-3">
              <div class="flex items-start gap-4 flex-1">
                <!-- Icon -->
                <div class="p-3 rounded-2xl ${config.iconBg} ${config.iconColor} shadow-sm group-hover:scale-110 transition-transform duration-300">
                   <span class="material-symbols-outlined text-2xl">local_pharmacy</span>
                </div>
                
                <div class="flex-1 min-w-0 pt-1">
                  <h3 class="text-lg font-bold text-slate-800 truncate tracking-tight">${req.name}</h3>
                  <div class="flex flex-wrap gap-2 mt-2">
                    <span class="inline-flex items-center gap-1 bg-slate-50 border border-slate-100 px-2 py-1 rounded-md text-xs font-semibold text-slate-600">
                      <span class="material-symbols-outlined text-[14px]">medication</span>
                      ${req.dosage}
                    </span>
                    <span class="inline-flex items-center gap-1 bg-slate-50 border border-slate-100 px-2 py-1 rounded-md text-xs font-semibold text-slate-600">
                      <span class="material-symbols-outlined text-[14px]">category</span>
                      ${req.form}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Badge -->
              <span class="px-3 py-1 ${config.badge} text-[11px] uppercase tracking-wider font-bold rounded-full shadow-sm flex-shrink-0">
                ${config.label}
              </span>
            </div>
            
            <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
              <div class="flex items-center gap-2 text-xs text-textSub font-medium">
                <span class="material-symbols-outlined text-sm text-slate-400">schedule</span>
                <span>Requested ${timeText}</span>
              </div>
              
              <button class="deleteBtn text-xs font-bold text-slate-400 hover:text-red-600 transition-colors flex items-center gap-1 group/btn" data-id="${req.id}">
                <span class="material-symbols-outlined text-lg group-hover/btn:animate-pulse">delete</span>
                Remove
              </button>
            </div>
          </div>
        </div>
      `;
      
      container.innerHTML += html;
    });

    // Attach delete handlers
    document.querySelectorAll('.deleteBtn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const requestId = this.dataset.id;
        const card = this.closest('div');
        
        if (!confirm("Are you sure you want to delete this request?")) return;

        try {
          card.style.opacity = '0.5';
          card.style.pointerEvents = 'none';
          
          const res = await fetch("fetch_req.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=delete_request&request_id=${requestId}`
          });
          const result = await res.json();
          if (result.status === "success") {
            card.style.animation = 'slideOut 0.3s ease-in-out';
            setTimeout(() => loadMedicineRequests(), 300);
          } else {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
            alert("Failed to delete request");
          }
        } catch (err) {
          console.error(err);
          card.style.opacity = '1';
          card.style.pointerEvents = 'auto';
          alert("Server error");
        }
      });
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = '<div class="col-span-full py-16 text-center"><span class="material-symbols-outlined text-6xl text-red-300 block mb-4">error_outline</span><p class="text-red-600 text-lg font-medium">Failed to load requests</p><p class="text-red-500 text-sm mt-2">Please try again later</p></div>';
  }
}

// Automatically load requests when user clicks "requests" nav
document.querySelector('[data-section="requests"]')?.addEventListener("click", () => {
  loadMedicineRequests();
});

// Load compartments when user clicks "Box Settings" nav
document.querySelector('[data-section="boxSettings"]')?.addEventListener("click", () => {
  loadCompartments();
});

// Load compartments function
function loadCompartments() {
  const container = document.getElementById("compartmentsList");
  
  fetch("fetch_compartments.php")
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        container.innerHTML = `
          <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <p class="text-red-600 font-semibold">Error loading compartments</p>
          </div>
        `;
        return;
      }

      const colorMap = {
        1: { bg: 'bg-blue-50', border: 'border-blue-200', circle: 'bg-blue-600', text: 'text-blue-600', icon: 'bg-blue-100 text-blue-600' },
        2: { bg: 'bg-purple-50', border: 'border-purple-200', circle: 'bg-purple-600', text: 'text-purple-600', icon: 'bg-purple-100 text-purple-600' },
        3: { bg: 'bg-pink-50', border: 'border-pink-200', circle: 'bg-pink-600', text: 'text-pink-600', icon: 'bg-pink-100 text-pink-600' },
        4: { bg: 'bg-emerald-50', border: 'border-emerald-200', circle: 'bg-emerald-600', text: 'text-emerald-600', icon: 'bg-emerald-100 text-emerald-600' }
      };

      container.innerHTML = data.compartments.map(comp => {
        const colors = colorMap[comp.number];
        const medicine = comp.medicine;

        if (medicine) {
          // Occupied compartment - Show full medicine details
          return `
            <div class="rounded-2xl border-2 ${colors.border} overflow-hidden hover:shadow-lg transition-shadow group">
              <!-- Header with Compartment Number -->
              <div class="${colors.bg} ${colors.border} border-b-2 px-4 py-3 flex items-center justify-between bg-gradient-to-r">
                <div class="flex items-center gap-3">
                  <span class="w-10 h-10 rounded-full ${colors.circle} text-white flex items-center justify-center text-sm font-bold shadow-md">
                    ${comp.number}
                  </span>
                  <div>
                    <p class="font-bold text-sm text-textMain">Slot ${comp.number}</p>
                    <p class="text-xs text-textSub">Assigned Medicine</p>
                  </div>
                </div>
              </div>
              
              <!-- Medicine Details -->
              <div class="p-4 space-y-3 bg-white">
                <!-- Medicine Name -->
                <div class="flex items-start gap-3">
                  <span class="${colors.icon} p-2 rounded-lg flex-shrink-0">
                    <span class="material-symbols-outlined text-lg">medication</span>
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-textSub font-semibold uppercase">Medicine</p>
                    <p class="text-sm font-bold text-textMain break-words">${escapeHtml(medicine.name)}</p>
                  </div>
                </div>

                <!-- Dosage & Frequency -->
                <div class="grid grid-cols-2 gap-3">
                  <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs text-textSub font-semibold uppercase mb-1">Dosage</p>
                    <p class="text-sm font-bold text-textMain">${medicine.dosage}</p>
                  </div>
                  <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs text-textSub font-semibold uppercase mb-1">Frequency</p>
                    <p class="text-sm font-bold text-textMain">${medicine.frequency}</p>
                  </div>
                </div>

                <!-- Type Badge -->
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold ${
                    medicine.type === 'pill' ? 'bg-blue-100 text-blue-700' :
                    medicine.type === 'liquid' ? 'bg-cyan-100 text-cyan-700' :
                    'bg-purple-100 text-purple-700'
                  }">
                    <span class="material-symbols-outlined text-sm">
                      ${medicine.type === 'pill' ? 'capsule' : medicine.type === 'liquid' ? 'water' : 'syringe'}
                    </span>
                    ${medicine.type.charAt(0).toUpperCase() + medicine.type.slice(1)}
                  </span>
                </div>

                <!-- Start/End Dates -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div class="flex items-center gap-1 text-textSub">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    <span>${medicine.startDate}</span>
                  </div>
                  ${medicine.endDate ? `
                    <div class="flex items-center gap-1 text-textSub">
                      <span class="material-symbols-outlined text-sm">event_busy</span>
                      <span>${medicine.endDate}</span>
                    </div>
                  ` : ''}
                </div>

                <!-- Delete Button -->
                <button 
                  class="w-full mt-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-lg text-sm transition flex items-center justify-center gap-2 group-hover:shadow-md"
                  onclick="deleteCompartmentMedicine(${medicine.id}, ${comp.number})"
                  title="Remove medicine from this compartment">
                  <span class="material-symbols-outlined text-lg">delete_sweep</span>
                  Remove from Compartment
                </button>
              </div>
            </div>
          `;
        } else {
          // Empty compartment
          return `
            <div class="rounded-2xl border-2 border-dashed ${colors.border} ${colors.bg} p-6 flex flex-col items-center justify-center text-center opacity-60 hover:opacity-100 transition-opacity cursor-pointer hover:shadow-lg hover:shadow-${colors.color}-300/50" onclick="window.location.href='add_medicine.html?compartment=${comp.number}'">
              <div class="w-12 h-12 rounded-full ${colors.circle} text-white flex items-center justify-center text-xl font-bold mb-2">
                ${comp.number}
              </div>
              <p class="text-sm font-semibold text-textMain mb-1">Empty Slot</p>
              <p class="text-xs text-textSub">Click to add medicine</p>
              <div class="flex items-center gap-2 mt-3 text-textSub">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                <span class="text-xs">Add medicine now</span>
              </div>
            </div>
          `;
        }
      }).join('');
    })
    .catch(err => {
      console.error("Error loading compartments:", err);
      container.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
          <p class="text-red-600 font-semibold">Failed to load compartments</p>
          <p class="text-sm text-red-500 mt-2">${err.message}</p>
        </div>
      `;
    });
}

// Delete medicine from compartment
function deleteCompartmentMedicine(medicineId, compartmentNumber) {
  if (!confirm('Are you sure you want to remove this medicine from the compartment?')) return;

  fetch('delete_medicine.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: medicineId, action: 'remove_compartment' })
  })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        showNotification('Medicine removed successfully', 'success');
        // Add cache busting parameter to ensure fresh data
        loadCompartmentsWithRefresh();
      } else {
        showNotification(data.message || 'Failed to delete', 'error');
      }
    })
    .catch(err => {
      console.error('Error:', err);
      showNotification('Error removing medicine: ' + err.message, 'error');
    });
}

// Load compartments with cache busting
function loadCompartmentsWithRefresh() {
  const container = document.getElementById("compartmentsList");
  const timestamp = new Date().getTime();
  
  fetch(`fetch_compartments.php?t=${timestamp}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        container.innerHTML = `
          <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <p class="text-red-600 font-semibold">Error loading compartments</p>
          </div>
        `;
        return;
      }

      const colorMap = {
        1: { bg: 'bg-blue-50', border: 'border-blue-200', circle: 'bg-blue-600', text: 'text-blue-600', icon: 'bg-blue-100 text-blue-600' },
        2: { bg: 'bg-purple-50', border: 'border-purple-200', circle: 'bg-purple-600', text: 'text-purple-600', icon: 'bg-purple-100 text-purple-600' },
        3: { bg: 'bg-pink-50', border: 'border-pink-200', circle: 'bg-pink-600', text: 'text-pink-600', icon: 'bg-pink-100 text-pink-600' },
        4: { bg: 'bg-emerald-50', border: 'border-emerald-200', circle: 'bg-emerald-600', text: 'text-emerald-600', icon: 'bg-emerald-100 text-emerald-600' }
      };

      container.innerHTML = data.compartments.map(comp => {
        const colors = colorMap[comp.number];
        const medicine = comp.medicine;

        if (medicine) {
          // Occupied compartment - Show full medicine details
          return `
            <div class="rounded-2xl border-2 ${colors.border} overflow-hidden hover:shadow-lg transition-shadow group">
              <!-- Header with Compartment Number -->
              <div class="${colors.bg} ${colors.border} border-b-2 px-4 py-3 flex items-center justify-between bg-gradient-to-r">
                <div class="flex items-center gap-3">
                  <span class="w-10 h-10 rounded-full ${colors.circle} text-white flex items-center justify-center text-sm font-bold shadow-md">
                    ${comp.number}
                  </span>
                  <div>
                    <p class="font-bold text-sm text-textMain">Slot ${comp.number}</p>
                    <p class="text-xs text-textSub">Assigned Medicine</p>
                  </div>
                </div>
              </div>
              
              <!-- Medicine Details -->
              <div class="p-4 space-y-3 bg-white">
                <!-- Medicine Name -->
                <div class="flex items-start gap-3">
                  <span class="${colors.icon} p-2 rounded-lg flex-shrink-0">
                    <span class="material-symbols-outlined text-lg">medication</span>
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs text-textSub font-semibold uppercase">Medicine</p>
                    <p class="text-sm font-bold text-textMain break-words">${escapeHtml(medicine.name)}</p>
                  </div>
                </div>

                <!-- Dosage & Frequency -->
                <div class="grid grid-cols-2 gap-3">
                  <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs text-textSub font-semibold uppercase mb-1">Dosage</p>
                    <p class="text-sm font-bold text-textMain">${medicine.dosage}</p>
                  </div>
                  <div class="bg-slate-50 p-3 rounded-lg">
                    <p class="text-xs text-textSub font-semibold uppercase mb-1">Frequency</p>
                    <p class="text-sm font-bold text-textMain">${medicine.frequency}</p>
                  </div>
                </div>

                <!-- Type Badge -->
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold ${
                    medicine.type === 'pill' ? 'bg-blue-100 text-blue-700' :
                    medicine.type === 'liquid' ? 'bg-cyan-100 text-cyan-700' :
                    'bg-purple-100 text-purple-700'
                  }">
                    <span class="material-symbols-outlined text-sm">
                      ${medicine.type === 'pill' ? 'capsule' : medicine.type === 'liquid' ? 'water' : 'syringe'}
                    </span>
                    ${medicine.type.charAt(0).toUpperCase() + medicine.type.slice(1)}
                  </span>
                </div>

                <!-- Start/End Dates -->
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div class="flex items-center gap-1 text-textSub">
                    <span class="material-symbols-outlined text-sm">calendar_today</span>
                    <span>${medicine.startDate}</span>
                  </div>
                  ${medicine.endDate ? `
                    <div class="flex items-center gap-1 text-textSub">
                      <span class="material-symbols-outlined text-sm">event_busy</span>
                      <span>${medicine.endDate}</span>
                    </div>
                  ` : ''}
                </div>

                <!-- Delete Button -->
                <button 
                  class="w-full mt-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-lg text-sm transition flex items-center justify-center gap-2 group-hover:shadow-md"
                  onclick="deleteCompartmentMedicine(${medicine.id}, ${comp.number})"
                  title="Remove medicine from this compartment">
                  <span class="material-symbols-outlined text-lg">delete_sweep</span>
                  Remove from Compartment
                </button>
              </div>
            </div>
          `;
        } else {
          // Empty compartment
          return `
            <div class="rounded-2xl border-2 border-dashed ${colors.border} ${colors.bg} p-6 flex flex-col items-center justify-center text-center opacity-60 hover:opacity-100 transition-opacity cursor-pointer hover:shadow-lg hover:shadow-${colors.color}-300/50" onclick="window.location.href='add_medicine.html?compartment=${comp.number}'">
              <div class="w-12 h-12 rounded-full ${colors.circle} text-white flex items-center justify-center text-xl font-bold mb-2">
                ${comp.number}
              </div>
              <p class="text-sm font-semibold text-textMain mb-1">Empty Slot</p>
              <p class="text-xs text-textSub">Click to add medicine</p>
              <div class="flex items-center gap-2 mt-3 text-textSub">
                <span class="material-symbols-outlined text-sm">add_circle</span>
                <span class="text-xs">Add medicine now</span>
              </div>
            </div>
          `;
        }
      }).join('');
    })
    .catch(err => {
      console.error("Error loading compartments:", err);
      container.innerHTML = `
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
          <p class="text-red-600 font-semibold">Failed to load compartments</p>
          <p class="text-sm text-red-500 mt-2">${err.message}</p>
        </div>
      `;
    });
}

const viewModal = document.getElementById("viewMedicineModal");
const viewContainer = document.getElementById("medicineDetails");
const closeViewModal = document.getElementById("closeViewModal");

function viewMedicine(id) {
  viewContainer.innerHTML = "<p class='text-textSub'>Loading details...</p>";
  viewModal.classList.remove("hidden");

  fetch(`fetch_single_medicine.php?id=${id}`)
    .then(res => res.json())
    .then(data => {
      if (data.status !== "success") {
        viewContainer.innerHTML = "<p class='text-red-500'>Failed to load medicine details</p>";
        return;
      }

      const med = data.medicine;

      viewContainer.innerHTML = `
        <div class="grid grid-cols-2 gap-4 text-sm">

          <div>
            <p class="text-textSub">Medicine Name</p>
            <p class="font-semibold">${med.name}</p>
          </div>

          <div>
            <p class="text-textSub">Type</p>
            <p class="font-semibold capitalize">${med.medicine_type}</p>
          </div>

          <div>
            <p class="text-textSub">Dosage</p>
            <p class="font-semibold">${med.dosage_value}</p>
          </div>

          <div>
            <p class="text-textSub">Reminder Mode</p>
            <p class="font-semibold capitalize">${med.reminder_type}</p>
          </div>

          <div>
            <p class="text-textSub">Schedule</p>
            <p class="font-semibold capitalize">${med.schedule_type}</p>
          </div>

          <div>
            <p class="text-textSub">Interval</p>
            <p class="font-semibold">${med.interval_hours || "—"}</p>
          </div>

          <div class="col-span-2">
            <p class="text-textSub">Times</p>
            <p class="font-semibold">${(med.times || []).join(", ") || "—"}</p>
          </div>

          <div class="col-span-2">
            <p class="text-textSub">Selected Days</p>
            <p class="font-semibold">${(med.selected_days || []).join(", ") || "—"}</p>
          </div>

          <div>
            <p class="text-textSub">Start Date</p>
            <p class="font-semibold">${med.start_date}</p>
          </div>

          <div>
            <p class="text-textSub">End Date</p>
            <p class="font-semibold">${med.end_date || "—"}</p>
          </div>

          <div>
            <p class="text-textSub">Compartment</p>
            <p class="font-semibold">${med.compartment_number}</p>
          </div>

        </div>
      `;
    })
    .catch(err => {
      console.error(err);
      viewContainer.innerHTML = "<p class='text-red-500'>Server error</p>";
    });
}

closeViewModal.addEventListener("click", () => {
  viewModal.classList.add("hidden");
});

/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
 
  loadCaretakers();
  initTranslationSystem();
});
let pendingAction = null; // stores the callback to run on confirm

function openConfirmModal(message, actionCallback) {
    pendingAction = actionCallback;
    document.getElementById("confirmMessage").innerText = message;
    const modal = document.getElementById("confirmModal");
    modal.classList.remove("hidden");
    setTimeout(() => {
        document.getElementById("confirmBox").classList.remove("scale-95", "opacity-0");
    }, 10);
}

function closeConfirmModal() {
    const modal = document.getElementById("confirmModal");
    document.getElementById("confirmBox").classList.add("scale-95", "opacity-0");
    setTimeout(() => modal.classList.add("hidden"), 300);
    pendingAction = null;
}

document.getElementById("confirmYes").addEventListener("click", () => {
    if (pendingAction) pendingAction();
    closeConfirmModal();
});

document.getElementById("confirmNo").addEventListener("click", closeConfirmModal);

/* =====================================================
   PRESCRIPTIONS FUNCTIONALITY
===================================================== */
async function loadPrescriptions() {
  const container = document.getElementById('prescriptionsList');
  container.innerHTML = '<p class="text-textSub text-center py-8">Loading prescriptions...</p>';

  try {
    const response = await fetch('fetch_prescription.php?action=list');
    const data = await response.json();

    if (!data.success) {
      container.innerHTML = '<p class="text-danger text-center">Failed to load prescriptions</p>';
      return;
    }

    const prescriptions = data.prescriptions;
    
    if (prescriptions.length === 0) {
      container.innerHTML = `
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center">
          <span class="material-symbols-outlined text-4xl text-blue-400 block mb-2">receipt_long</span>
          <p class="text-textSub mb-4">No prescriptions yet</p>
          <a href="add_prescription.html" class="bg-primary text-white px-4 py-2 rounded-lg text-sm inline-block">
            Add Your First Prescription
          </a>
        </div>
      `;
      return;
    }

    container.innerHTML = prescriptions.map(prescription => `
      <div class="bg-white rounded-xl shadow-soft border border-slate-200 overflow-hidden hover:shadow-lg transition">
        <div class="p-4 md:p-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            <!-- Left: Disease & Doctor Info -->
            <div class="md:col-span-2">
              <div class="mb-4">
                <h3 class="text-xl font-bold text-textMain mb-1">${escapeHtml(prescription.disease_name)}</h3>
                <p class="text-sm text-textSub">${escapeHtml(prescription.disease_description || 'No description')}</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="bg-slate-50 p-3 rounded-lg">
                  <p class="text-textSub text-xs font-semibold mb-1">DOCTOR</p>
                  <p class="font-semibold text-textMain">${escapeHtml(prescription.doctor_name)}</p>
                  ${prescription.hospital_name ? `<p class="text-xs text-textSub mt-1">${escapeHtml(prescription.hospital_name)}</p>` : ''}
                </div>
                <div class="bg-slate-50 p-3 rounded-lg">
                  <p class="text-textSub text-xs font-semibold mb-1">DATE</p>
                  <p class="font-semibold text-textMain">${formatDate(prescription.prescription_date)}</p>
                </div>
              </div>
            </div>

            <!-- Right: Actions -->
            <div class="flex flex-col gap-2">
              <button onclick="viewPrescriptionDetails(${prescription.id})" class="w-full bg-primary hover:bg-primaryDark text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">visibility</span>
                View Details
              </button>
              <button onclick="deletePrescription(${prescription.id})" class="w-full border border-danger text-danger hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-base">delete</span>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  } catch (error) {
    console.error('Error loading prescriptions:', error);
    container.innerHTML = '<p class="text-danger text-center">Error loading prescriptions</p>';
  }
}

async function viewPrescriptionDetails(prescriptionId) {
  try {
    const response = await fetch(`fetch_prescription.php?action=detail&id=${prescriptionId}`);
    const data = await response.json();

    if (!data.success) {
      alert('Failed to load prescription details');
      return;
    }

    const prescription = data.prescription;
    const medicines = data.medicines || [];
    const tests = data.tests || [];

    const modalHTML = `
      <div id="prescriptionModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-slate-200 p-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-textMain">${escapeHtml(prescription.disease_name)}</h2>
            <button onclick="document.getElementById('prescriptionModal').remove()" class="text-textSub hover:text-textMain text-2xl">✕</button>
          </div>

          <!-- Content -->
          <div class="p-6 space-y-6">
            <!-- Disease & Doctor Info -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-blue-50 p-4 rounded-xl">
                <p class="text-xs text-textSub font-semibold mb-1">DOCTOR</p>
                <p class="text-lg font-bold text-textMain">${escapeHtml(prescription.doctor_name)}</p>
                ${prescription.hospital_name ? `<p class="text-sm text-textSub mt-1">${escapeHtml(prescription.hospital_name)}</p>` : ''}
              </div>
              <div class="bg-green-50 p-4 rounded-xl">
                <p class="text-xs text-textSub font-semibold mb-1">PRESCRIPTION DATE</p>
                <p class="text-lg font-bold text-textMain">${formatDate(prescription.prescription_date)}</p>
              </div>
            </div>

            <!-- Disease Description -->
            ${prescription.disease_description ? `
              <div class="bg-slate-50 p-4 rounded-xl">
                <p class="text-xs text-textSub font-semibold mb-2">DISEASE DESCRIPTION</p>
                <p class="text-textMain">${escapeHtml(prescription.disease_description)}</p>
              </div>
            ` : ''}

            <!-- Medicines -->
            <div>
              <h3 class="text-lg font-bold text-textMain mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">medication</span>
                Prescribed Medicines (${medicines.length})
              </h3>
              ${medicines.length > 0 ? `
                <div class="space-y-3">
                  ${medicines.map(med => `
                    <div class="border border-slate-200 p-4 rounded-lg">
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">MEDICINE</p>
                          <p class="font-semibold text-textMain">${escapeHtml(med.medicine_name)}</p>
                        </div>
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">DOSAGE</p>
                          <p class="font-semibold text-textMain">${escapeHtml(med.dosage || 'N/A')}</p>
                        </div>
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">FREQUENCY</p>
                          <p class="font-semibold text-textMain">${escapeHtml(med.frequency || 'N/A')}</p>
                        </div>
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">DURATION</p>
                          <p class="font-semibold text-textMain">${escapeHtml(med.duration || 'N/A')}</p>
                        </div>
                      </div>
                      ${med.instructions ? `
                        <div class="mt-3 bg-yellow-50 border border-yellow-200 p-3 rounded text-sm text-textMain">
                          <strong>Instructions:</strong> ${escapeHtml(med.instructions)}
                        </div>
                      ` : ''}
                    </div>
                  `).join('')}
                </div>
              ` : '<p class="text-textSub">No medicines prescribed</p>'}
            </div>

            <!-- Tests -->
            <div>
              <h3 class="text-lg font-bold text-textMain mb-3 flex items-center gap-2">
                <span class="material-symbols-outlined">science</span>
                Prescribed Tests (${tests.length})
              </h3>
              ${tests.length > 0 ? `
                <div class="space-y-3">
                  ${tests.map(test => `
                    <div class="border border-slate-200 p-4 rounded-lg">
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">TEST NAME</p>
                          <p class="font-semibold text-textMain">${escapeHtml(test.test_name)}</p>
                        </div>
                        <div>
                          <p class="text-xs text-textSub font-semibold mb-1">TEST TYPE</p>
                          <span class="inline-block bg-primary/10 text-primary px-3 py-1 rounded-full text-xs font-semibold">${escapeHtml(test.test_type)}</span>
                        </div>
                      </div>
                      ${test.test_description ? `
                        <p class="text-sm text-textMain mt-2">${escapeHtml(test.test_description)}</p>
                      ` : ''}
                      <div class="flex items-center justify-between mt-3">
                        <div>
                          ${test.recommended_date ? `
                            <p class="text-xs text-textSub">Recommended by: ${formatDate(test.recommended_date)}</p>
                          ` : ''}
                        </div>
                        <div class="flex items-center gap-2">
                          <span class="text-xs font-semibold text-textSub">Status:</span>
                          <select onchange="updateTestStatus(${test.id}, this.value)" class="text-sm px-3 py-1 border border-slate-300 rounded-lg">
                            <option value="Pending" ${test.status === 'Pending' ? 'selected' : ''}>Pending</option>
                            <option value="Completed" ${test.status === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Cancelled" ${test.status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  `).join('')}
                </div>
              ` : '<p class="text-textSub">No tests prescribed</p>'}
            </div>

            <!-- Notes -->
            ${prescription.notes ? `
              <div class="bg-slate-50 p-4 rounded-xl">
                <p class="text-xs text-textSub font-semibold mb-2">ADDITIONAL NOTES</p>
                <p class="text-textMain">${escapeHtml(prescription.notes)}</p>
              </div>
            ` : ''}
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
  } catch (error) {
    console.error('Error loading prescription details:', error);
    alert('Error loading prescription details');
  }
}

async function deletePrescription(prescriptionId) {
  openConfirmModal('Are you sure you want to delete this prescription?', async () => {
    try {
      const response = await fetch(`fetch_prescription.php?action=delete&id=${prescriptionId}`);
      const data = await response.json();

      if (data.success) {
        alert('Prescription deleted successfully');
        loadPrescriptions();
      } else {
        alert('Error deleting prescription: ' + (data.message || 'Unknown error'));
      }
    } catch (error) {
      console.error('Error:', error);
      alert('Error deleting prescription');
    }
  });
}

async function updateTestStatus(testId, status) {
  try {
    const response = await fetch('fetch_prescription.php?action=updateTestStatus', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `test_id=${testId}&status=${encodeURIComponent(status)}`
    });

    const data = await response.json();
    if (data.success) {
      console.log('Test status updated');
    } else {
      alert('Error updating test status');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Error updating test status');
  }
}

function escapeHtml(text) {
  if (!text) return '';
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

function formatDate(dateString) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('en-US', options);
}

// Load prescriptions when switching to prescriptions section
const originalSwitchSection = switchSection;
switchSection = function(sectionId) {
  originalSwitchSection(sectionId);
  if (sectionId === 'prescriptions') {
    loadPrescriptions();
  }
};

// Add CSS animations for medicine requests
const styleTag = document.createElement('style');
styleTag.textContent = `
  @keyframes slideOut {
    from {
      opacity: 1;
      transform: translateX(0);
    }
    to {
      opacity: 0;
      transform: translateX(100%);
    }
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .slide-out {
    animation: slideOut 0.3s ease-in-out forwards;
  }
  
  .request-card {
    animation: slideIn 0.3s ease-out;
  }
`;
document.head.appendChild(styleTag);

</script>



<script>
async function loadMedicineLogs() {
  const container = document.getElementById("logsTableBody");
  container.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-textSub">Loading logs...</td></tr>';
  
  try {
    const res = await fetch("fetch_medicine_logs.php");
    const data = await res.json();
    
    if (data.status !== "success" || !data.logs.length) {
      container.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-textSub">No logs found</td></tr>';
      return;
    }
    
    container.innerHTML = data.logs.map(log => {
      let statusClass = "bg-slate-100 text-slate-700";
      let icon = "schedule";
      
      if (log.status === 'TAKEN' || log.status === 'Taken') {
        statusClass = "bg-green-100 text-green-700";
        icon = "check_circle";
      } else if (log.status === 'MISSED' || log.status === 'Missed') {
        statusClass = "bg-red-100 text-red-700";
        icon = "cancel";
      } else if (log.status === 'SKIPPED' || log.status === 'Skipped') {
        statusClass = "bg-yellow-100 text-yellow-700"; 
        icon = "skip_next";
      }

      return `
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center gap-3">
              <span class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                <span class="material-symbols-outlined text-lg">medication</span>
              </span>
              <span class="font-bold text-textMain">${log.medicine_name}</span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-textSub font-medium">
            ${log.dosage}
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-textSub font-medium">
            ${log.formatted_time}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold ${statusClass}">
              <span class="material-symbols-outlined text-sm">${icon}</span>
              ${log.status}
            </span>
          </td>
        </tr>
      `;
    }).join('');
    
  } catch (err) {
    console.error(err);
    container.innerHTML = '<tr><td colspan="4" class="text-center text-red-500 py-4">Failed to load logs</td></tr>';
  }
}

// Hook into navigation
const prevSwitchLogs = switchSection;
switchSection = function(sectionId) {
    if (prevSwitchLogs) prevSwitchLogs(sectionId);
    if (sectionId === 'logs') {
        loadMedicineLogs();
    }
};
</script>

<!-- Disease List Modal -->
<div id="diseaseModal" class="fixed inset-0 bg-black/40 hidden z-50 flex items-center justify-center">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative mx-4 animate-[fadeIn_0.2s_ease-out]">
    <div class="flex justify-between items-center mb-4 border-b border-slate-100 pb-3">
        <h3 class="text-lg font-bold text-textMain">Conditions</h3>
        <button onclick="closeDiseaseModal()" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>
    <ul class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
        <?php if (!empty($prescriptionDiseases)): ?>
            <?php foreach ($prescriptionDiseases as $disease): ?>
                <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="material-symbols-outlined text-primary text-xl mt-0.5">medication</span>
                    <span class="text-sm font-medium text-textMain leading-tight"><?= htmlspecialchars($disease) ?></span>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
  </div>
</div>

<script>
function openDiseaseModal() {
    document.getElementById('diseaseModal').classList.remove('hidden');
}
function closeDiseaseModal() {
    document.getElementById('diseaseModal').classList.add('hidden');
}
// Close on outside click
document.getElementById('diseaseModal').addEventListener('click', function(e) {
    if (e.target === this) closeDiseaseModal();
});
</script>
</body>
</html>
