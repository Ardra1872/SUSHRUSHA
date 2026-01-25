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
$stmt = $conn->prepare("SELECT conditions FROM medical_details WHERE patient_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$medicalResult = $stmt->get_result();
$medicalData = $medicalResult->fetch_assoc();
$diseaseInfo = !empty($medicalData['conditions']) ? $medicalData['conditions'] : null;
$stmt->close();




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

    <div class="flex flex-col">
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
          <p class="text-sm text-textSub"data-i18n="adherence">Adherence</p>
          <h3 class="text-4xl font-display font-bold mt-2">92%</h3>
          <div class="h-2 bg-slate-100 rounded-full mt-4">
            <div class="h-2 bg-primary rounded-full w-[92%]"></div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub" data-i18n="missed_doses">Missed Doses</p>
         <h3 class="text-4xl font-display font-bold mt-2 text-danger dynamic-text">1</h3>
          <p class="text-xs text-textSub mt-2 dynamic-text">This week</p>

        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub"data-i18n="next_refill">Next Refill</p>
          <h3 class="text-4xl font-display font-bold mt-2">Oct 24</h3>
          <p class="text-xs text-textSub mt-2"dynamic-text>Atorvastatin</p>
        </div>
        <!-- Disease / Conditions card -->
        <div class="bg-white p-6 rounded-2xl shadow-soft md:col-span-1">
          <p class="text-sm text-textSub">Disease / Conditions</p>
          <?php if (!empty($diseaseInfo)): ?>
            <p class="mt-2 text-sm text-textMain break-words">
              <?= nl2br(htmlspecialchars($diseaseInfo)) ?>
            </p>
          <?php else: ?>
            <p class="mt-2 text-xs text-textSub">
              No disease information added yet.
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

    <!-- My Schedule -->
    <div id="schedule" class="section hidden">
      
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
<div id="boxSettings" class="section hidden p-6 md:p-10 bg-white rounded-3xl shadow-soft border border-slate-100">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
    <div>
      <h2 class="text-4xl font-extrabold mb-2 text-textMain bg-gradient-to-r from-cyan-600 to-teal-600 bg-clip-text text-transparent">Smart Medicine Box</h2>
      <p class="text-textSub text-sm md:text-base">Manage your connected medicine compartments and settings</p>
    </div>
    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-gradient-to-r from-cyan-50 to-teal-50 border border-cyan-200 text-cyan-700 text-xs md:text-sm font-medium">
      <span class="material-symbols-outlined text-lg animate-pulse">router</span>
      <span id="boxConnectionStatus">ESP32 Connected</span>
    </div>
  </div>

  <!-- Grid Layout -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left: Box Visualization - REDESIGNED -->
    <div class="lg:col-span-1">
      <div class="bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 rounded-3xl p-8 shadow-2xl border border-slate-700/50 relative overflow-hidden">
        <!-- Decorative background -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-40 h-40 bg-cyan-500 rounded-full blur-3xl"></div>
          <div class="absolute bottom-0 left-0 w-40 h-40 bg-purple-500 rounded-full blur-3xl"></div>
        </div>

        <!-- Box Title -->
        <h3 class="text-white font-bold text-lg mb-6 flex items-center gap-2 relative z-10">
          <span class="material-symbols-outlined text-cyan-400 text-2xl">medication</span>
          <span>Medicine Box</span>
        </h3>

        <!-- 3D-Like Box Container with Isometric View -->
        <div class="relative z-10 mb-8">
          <!-- Box Shadow/Depth -->
          <div class="absolute inset-0 bg-gradient-to-b from-transparent via-slate-900/50 to-black/50 rounded-2xl transform skew-y-3"></div>
          
          <!-- Main Box Grid -->
          <div class="grid grid-cols-2 gap-3 p-4 bg-gradient-to-br from-slate-800/60 to-slate-900/60 rounded-2xl border border-slate-600/30 backdrop-blur-sm relative z-20">
            <!-- Compartment 1 -->
            <div class="compartment-slot group relative" data-compartment="1">
              <div class="aspect-square bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:shadow-2xl hover:shadow-blue-500/50 transition-all duration-300 border border-blue-400/30 overflow-hidden relative">
                <!-- Shine effect -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                
                <!-- LED indicator -->
                <div class="absolute top-2 right-2 w-3 h-3 bg-cyan-400 rounded-full animate-pulse shadow-lg shadow-cyan-400/60"></div>
                
                <!-- Compartment content -->
                <div class="relative z-10 text-center">
                  <span class="text-white font-bold text-2xl block mb-1">1</span>
                  <p class="text-xs text-blue-200 font-semibold">Slot</p>
                </div>
              </div>
            </div>

            <!-- Compartment 2 -->
            <div class="compartment-slot group relative" data-compartment="2">
              <div class="aspect-square bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:shadow-2xl hover:shadow-purple-500/50 transition-all duration-300 border border-purple-400/30 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute top-2 right-2 w-3 h-3 bg-cyan-400 rounded-full animate-pulse shadow-lg shadow-cyan-400/60"></div>
                <div class="relative z-10 text-center">
                  <span class="text-white font-bold text-2xl block mb-1">2</span>
                  <p class="text-xs text-purple-200 font-semibold">Slot</p>
                </div>
              </div>
            </div>

            <!-- Compartment 3 -->
            <div class="compartment-slot group relative" data-compartment="3">
              <div class="aspect-square bg-gradient-to-br from-pink-600 to-pink-800 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:shadow-2xl hover:shadow-pink-500/50 transition-all duration-300 border border-pink-400/30 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute top-2 right-2 w-3 h-3 bg-cyan-400 rounded-full animate-pulse shadow-lg shadow-cyan-400/60"></div>
                <div class="relative z-10 text-center">
                  <span class="text-white font-bold text-2xl block mb-1">3</span>
                  <p class="text-xs text-pink-200 font-semibold">Slot</p>
                </div>
              </div>
            </div>

            <!-- Compartment 4 -->
            <div class="compartment-slot group relative" data-compartment="4">
              <div class="aspect-square bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-xl flex flex-col items-center justify-center cursor-pointer hover:shadow-2xl hover:shadow-emerald-500/50 transition-all duration-300 border border-emerald-400/30 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="absolute top-2 right-2 w-3 h-3 bg-cyan-400 rounded-full animate-pulse shadow-lg shadow-cyan-400/60"></div>
                <div class="relative z-10 text-center">
                  <span class="text-white font-bold text-2xl block mb-1">4</span>
                  <p class="text-xs text-emerald-200 font-semibold">Slot</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Box Status Info -->
        <div class="bg-slate-700/40 rounded-xl p-4 border border-slate-600/30 backdrop-blur-sm relative z-10">
          <p class="text-slate-300 text-xs font-bold uppercase tracking-wide mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">info</span>
            Status
          </p>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">battery_full</span>
                Battery
              </span>
              <div class="flex items-center gap-2">
                <div class="w-12 h-2 bg-slate-600/60 rounded-full overflow-hidden">
                  <div class="h-full bg-gradient-to-r from-green-500 to-green-400 w-[75%]"></div>
                </div>
                <span class="text-slate-200 text-xs font-bold">75%</span>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400 text-sm flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">signal_cellular_alt</span>
                Signal
              </span>
              <span class="text-cyan-400 text-xs font-bold">Strong</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: Settings & Info -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Device Connection Card -->
      <div class="bg-gradient-to-br from-cyan-50 to-teal-50 rounded-2xl p-6 border border-cyan-200">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl text-cyan-600 bg-cyan-100 rounded-full p-2">devices</span>
            <div>
              <h3 class="text-lg font-bold text-textMain">Device Connection</h3>
              <p class="text-xs text-textSub">Manage your smart box connection</p>
            </div>
          </div>
          <span id="connectionBadge" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">Disconnected</span>
        </div>
        <div class="space-y-3">
          <div class="bg-white p-4 rounded-xl border border-cyan-200">
            <p class="text-sm text-textSub mb-2">Device ID</p>
            <p class="font-mono text-sm text-textMain font-semibold">MED-BOX-2024-001</p>
          </div>
          <div class="flex gap-3">
            <button class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white py-2 px-4 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-lg">router</span>
              Connect ESP32
            </button>
            <button class="flex-1 bg-white border border-cyan-300 text-cyan-600 hover:bg-cyan-50 py-2 px-4 rounded-xl text-sm font-bold transition">
              Forget Device
            </button>
          </div>
        </div>
          </div>
          
          <div class="mt-4 pt-4 border-t border-cyan-100">
             <a href="../../public/simulation/index.php" target="_blank" class="block w-full bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-xl text-center text-sm font-bold transition flex items-center justify-center gap-2">
               <span class="material-symbols-outlined">model_training</span>
               Launch Simulation
             </a>
             <p class="text-xs text-center text-gray-500 mt-1">Dev Tool: Test hardware alerts virtually</p>
          </div>

      </div>

      <!-- ESP32 WiFi Connection Info -->
      <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-6 border border-emerald-200">
        <div class="flex items-center gap-3 mb-4">
          <span class="material-symbols-outlined text-2xl text-emerald-600 bg-emerald-100 rounded-full p-2">wifi</span>
          <div>
            <h3 class="text-lg font-bold text-textMain">ESP32 Connection Details</h3>
            <p class="text-xs text-textSub">WiFi-based smart medicine box</p>
          </div>
        </div>
        <div class="space-y-3">
          <div class="bg-white p-4 rounded-xl border border-emerald-200 space-y-2">
            <div class="flex items-center justify-between text-sm">
              <span class="text-textSub">Connection Type:</span>
              <span class="font-semibold text-textMain">WiFi (ESP32 Module)</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-textSub">Network Protocol:</span>
              <span class="font-semibold text-textMain">HTTP/REST API</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-textSub">Range:</span>
              <span class="font-semibold text-textMain">Up to 100m (in ideal conditions)</span>
            </div>
          </div>
          <p class="text-xs text-emerald-700 bg-emerald-50 p-3 rounded-lg">
            <strong>Note:</strong> The medicine box uses an ESP32 microcontroller connected to your WiFi network for real-time synchronization and remote monitoring. No Bluetooth pairing required - simply connect to the same WiFi network.
          </p>
        </div>
      </div>

      <!-- Compartment Assignment -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
          <span class="material-symbols-outlined text-2xl text-violet-600 bg-violet-100 rounded-full p-2">assignment</span>
          <div>
            <h3 class="text-lg font-bold text-textMain">Compartment Details</h3>
            <p class="text-xs text-textSub">View and manage medicines in each slot</p>
          </div>
        </div>

        <div id="compartmentsList" class="space-y-3">
          <!-- Compartments will be loaded here dynamically -->
          <div class="flex items-center justify-center py-8">
            <p class="text-sm text-textSub animate-pulse">Loading compartments...</p>
          </div>
        </div>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
          <span class="material-symbols-outlined text-2xl text-orange-600 bg-orange-100 rounded-full p-2">tune</span>
          <div>
            <h3 class="text-lg font-bold text-textMain">Settings</h3>
            <p class="text-xs text-textSub">Customize your box behavior</p>
          </div>
        </div>

        <div class="space-y-4">
          <!-- LED Brightness -->
          <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg text-orange-600">brightness_4</span>
              <div>
                <p class="text-sm font-semibold text-textMain">LED Brightness</p>
                <p class="text-xs text-textSub">Adjust LED intensity</p>
              </div>
            </div>
            <input type="range" min="10" max="100" value="75" class="w-24">
          </div>

          <!-- Sound Toggle -->
          <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg text-blue-600">volume_up</span>
              <div>
                <p class="text-sm font-semibold text-textMain">Sound Alerts</p>
                <p class="text-xs text-textSub">Beep on medication time</p>
              </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>

          <!-- Vibration Toggle -->
          <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg text-green-600">vibration</span>
              <div>
                <p class="text-sm font-semibold text-textMain">Vibration</p>
                <p class="text-xs text-textSub">Haptic feedback alerts</p>
              </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" checked class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>

          <!-- Reminder Duration -->
          <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-lg text-purple-600">schedule</span>
              <div>
                <p class="text-sm font-semibold text-textMain">Alert Duration</p>
                <p class="text-xs text-textSub">How long the box alerts</p>
              </div>
            </div>
            <select class="px-3 py-1 rounded-lg border border-slate-300 text-sm font-medium text-textMain">
              <option>30 sec</option>
              <option selected>1 min</option>
              <option>2 min</option>
              <option>5 min</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Activity Log -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200">
        <div class="flex items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-200">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-2xl text-indigo-600 bg-indigo-100 rounded-full p-2">history</span>
            <div>
              <h3 class="text-lg font-bold text-textMain">Recent Activity</h3>
              <p class="text-xs text-textSub">Last synced activities</p>
            </div>
          </div>
          <span class="text-xs text-textSub font-medium">Last 5 events</span>
        </div>

        <div class="space-y-2">
          <div class="flex items-center gap-3 p-2 text-sm border-l-4 border-green-500 hover:bg-green-50 rounded transition">
            <span class="material-symbols-outlined text-lg text-green-600">check_circle</span>
            <div class="flex-1 min-w-0">
              <p class="text-textMain font-medium">Slot 1 Accessed</p>
              <p class="text-xs text-textSub">Today, 8:30 AM</p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-2 text-sm border-l-4 border-blue-500 hover:bg-blue-50 rounded transition">
            <span class="material-symbols-outlined text-lg text-blue-600">sync</span>
            <div class="flex-1 min-w-0">
              <p class="text-textMain font-medium">Device Synced</p>
              <p class="text-xs text-textSub">Today, 7:15 AM</p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-2 text-sm border-l-4 border-purple-500 hover:bg-purple-50 rounded transition">
            <span class="material-symbols-outlined text-lg text-purple-600">notifications_active</span>
            <div class="flex-1 min-w-0">
              <p class="text-textMain font-medium">Reminder Alert - Slot 2</p>
              <p class="text-xs text-textSub">Yesterday, 2:00 PM</p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-2 text-sm border-l-4 border-orange-500 hover:bg-orange-50 rounded transition">
            <span class="material-symbols-outlined text-lg text-orange-600">battery_low</span>
            <div class="flex-1 min-w-0">
              <p class="text-textMain font-medium">Low Battery Alert</p>
              <p class="text-xs text-textSub">Jan 17, 3:45 PM</p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-2 text-sm border-l-4 border-cyan-500 hover:bg-cyan-50 rounded transition">
            <span class="material-symbols-outlined text-lg text-cyan-600">router</span>
            <div class="flex-1 min-w-0">
              <p class="text-textMain font-medium">ESP32 WiFi Connected</p>
              <p class="text-xs text-textSub">Jan 15, 10:20 AM</p>
            </div>
          </div>
        </div>
      </div>
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
    const res = await fetch("fetch_medicine.php");
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
card.className = "bg-white rounded-2xl shadow-lg p-5 flex flex-col gap-3 hover:shadow-xl transition aspect-[1/1]";




    card.innerHTML = `
      <div class="flex justify-between items-start">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary text-2xl">
            <span class="material-symbols-outlined">pill</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-textMain">${med.name}</h3>
            <p class="text-textSub text-sm">${med.dosage_value || ""} • ${med.medicine_type || ""}</p>
          </div>
        </div>
        <span class="text-xs px-2 py-1 rounded-full ${statusBg} ${statusText}">${statusLabel}</span>
      </div>

      <div class="grid grid-cols-2 gap-3 text-sm text-textSub">
        <div><span class="font-semibold">Start:</span> ${startDate}</div>
        <div><span class="font-semibold">End:</span> ${endDate}</div>
      </div>

      <div>
        <span class="text-xs text-textSub font-semibold">Inventory Status</span>
        <div class="w-full h-2 bg-gray-200 rounded-full mt-1">
          <div class="h-2 rounded-full bg-primary" style="width:${percent}%"></div>
        </div>
        <div class="text-xs text-textSub mt-1">${remaining} / ${total} Pills</div>
      </div>

      <div class="flex gap-2 mt-3">
      <button class="bg-primary text-white px-4 py-2 rounded-xl"
        onclick="viewMedicine(${med.id})">
  View
</button>

        <button class="bg-red-600 text-white px-4 py-2 rounded-xl hover:scale-105 transition" onclick="deleteMedicine(${med.id})">Delete</button>
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
        showNotification('Medicine deleted successfully', 'success');
        loadSchedule();
      } else {
        showNotification("Failed to delete medicine", 'error');
      }
    } catch (err) {
      console.error(err);
      showNotification("Server error", 'error');
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

      if (item.status === "Taken") {
        block = `
          <div class="flex items-start gap-6">
            <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-success">check</span>
            </div>
            <div>
              <p class="text-sm text-textSub">${formatTime(item.intake_time)}</p>
              <h4 class="font-bold line-through">${item.name} ${item.dosage}</h4>
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
                <button class="flex-1 bg-primary text-white py-2 rounded-xl font-bold">
                  Mark Taken
                </button>
                <button class="flex-1 border py-2 rounded-xl">
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
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

document.addEventListener("DOMContentLoaded", loadTodaySchedule);

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
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const formData = new FormData(form);

  try {
    await fetch("assign_caretaker.php", {
      method: "POST",
      body: formData
    });

    loadCaretakers();
    form.reset();
  } catch (err) {
    console.error(err);
    alert("Error assigning caretaker");
  }
});
async function loadCaretakers() {
  const list = document.getElementById("caretakerList");
  list.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch("fetch_caretaker.php");
    const data = await res.json();

    if (data.status !== "success" || !data.caretakers.length) {
      list.innerHTML = "<p>No caretakers assigned yet.</p>";
      return;
    }

    list.innerHTML = "";
  data.caretakers.forEach(cg => {
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
      const data = await res.json();
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

// Simple toast notification for patient actions (e.g., messages to caretaker)
function showToast(message, isError = false) {
  let toast = document.getElementById("globalToast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "globalToast";
    toast.className = "fixed top-4 right-4 z-50 px-4 py-2 rounded-xl shadow-lg text-sm font-medium text-white transition transform translate-y-[-10px] opacity-0";
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
        <div class="group relative p-6 md:p-7 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border-l-4 border-amber-500 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:scale-[1.01] overflow-hidden">
          <!-- Background decoration -->
          <div class="absolute top-0 right-0 w-24 h-24 bg-amber-100 opacity-10 rounded-full -mr-12 -mt-12 group-hover:scale-150 transition-transform duration-300"></div>
          
          <div class="relative z-10">
            <div class="flex items-start gap-4 mb-4">
              <span class="material-symbols-outlined text-3xl text-amber-600 bg-amber-100 rounded-full p-2 flex-shrink-0">warning</span>
              <div class="flex-1 min-w-0">
                <p class="text-slate-800 font-semibold text-sm md:text-base leading-relaxed">${alert.message}</p>
              </div>
              ${isRecent ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-200 text-amber-700 rounded-full text-xs font-bold flex-shrink-0 whitespace-nowrap">New</span>' : ''}
            </div>
            <div class="flex items-center gap-4 pt-4 border-t border-amber-200">
              <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">schedule</span>
                ${alertTime.toLocaleString()}
              </span>
              ${isRecent ? '<span class="text-xs text-amber-600 font-semibold">Just arrived</span>' : ''}
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
        pending: { bg: 'from-yellow-50 to-amber-50', border: 'border-yellow-300', icon: 'schedule', badge: 'bg-yellow-100 text-yellow-800', label: 'Pending', bgIcon: 'bg-yellow-100' },
        approved: { bg: 'from-green-50 to-emerald-50', border: 'border-green-300', icon: 'check_circle', badge: 'bg-green-100 text-green-800', label: 'Approved', bgIcon: 'bg-green-100' },
        rejected: { bg: 'from-red-50 to-rose-50', border: 'border-red-300', icon: 'cancel', badge: 'bg-red-100 text-red-800', label: 'Rejected', bgIcon: 'bg-red-100' }
      };
      
      let config = statusConfig[req.status] || statusConfig.pending;
      const requestDate = new Date(req.created_at);
      const daysAgo = Math.floor((Date.now() - requestDate.getTime()) / (1000 * 60 * 60 * 24));
      let timeText = daysAgo === 0 ? 'Today' : daysAgo === 1 ? 'Yesterday' : `${daysAgo} days ago`;

      let html = `
        <div class="group relative bg-gradient-to-br ${config.bg} p-6 rounded-2xl border-l-4 ${config.border} shadow-sm hover:shadow-lg transition-all duration-300 transform hover:scale-[1.01] overflow-hidden">
          <!-- Background decoration -->
          <div class="absolute top-0 right-0 w-20 h-20 ${config.bgIcon} opacity-10 rounded-full -mr-8 -mt-8 group-hover:scale-150 transition-transform duration-300"></div>
          
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-4 mb-4">
              <div class="flex items-start gap-4 flex-1">
                <span class="material-symbols-outlined text-3xl text-violet-600 bg-violet-100 rounded-full p-2.5 flex-shrink-0">local_pharmacy</span>
                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-bold text-slate-800 truncate">${req.name}</h3>
                  <p class="text-slate-600 text-sm mt-1 flex flex-wrap gap-3 mt-2">
                    <span class="inline-flex items-center gap-1 bg-white px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600">
                      <span class="material-symbols-outlined text-xs">medication</span>
                      ${req.dosage}
                    </span>
                    <span class="inline-flex items-center gap-1 bg-white px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600">
                      <span class="material-symbols-outlined text-xs">category</span>
                      ${req.form}
                    </span>
                  </p>
                </div>
              </div>
              <span class="px-3 py-1.5 ${config.badge} text-xs font-bold rounded-full flex-shrink-0 whitespace-nowrap shadow-sm">
                ${config.label}
              </span>
            </div>
            
            <div class="flex items-center justify-between pt-4 border-t border-slate-300/40">
              <div class="flex items-center gap-4">
                <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">schedule</span>
                  ${timeText}
                </span>
              </div>
              <button class="deleteBtn px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-xs font-bold transition-all duration-200 transform hover:scale-105 flex items-center gap-2 shadow-sm hover:shadow-md" data-id="${req.id}">
                <span class="material-symbols-outlined text-sm">delete</span>
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


</body>
</html>
