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

<header class="h-20 bg-white border-b border-slate-200 px-6 flex items-center">

  <!-- LEFT: Hamburger + Brand -->
  <div class="flex items-center gap-4 min-w-[220px]">

 <button id="menuBtn" class="text-textSub md:hidden">
  <span class="material-symbols-outlined text-3xl">menu</span>
</button>


    <div>
      <div class="flex-1 text-center hidden md:block">
  <p class="text-lg font-semibold">
    <span id="greeting">Good Morning</span>,

    <span class="text-primary dynamic-text">
      <?= htmlspecialchars($name)  ?> |<br>
    </span>
    </div>

  </div>

  <!-- CENTER: Greeting -->
  
    <span id="liveClock" class="ml-2 text-sm text-gray-500 font-normal"></span>
  </p>
</div>

  <!-- RIGHT: Search + Icons -->
  <div class="flex items-center gap-4 min-w-[300px] justify-end">

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
    <button class="relative">
      <!-- <span class="material-symbols-outlined text-textSub">notifications</span> -->
      <!-- <span class="absolute top-0 right-0 size-2 bg-danger rounded-full"></span> -->
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

      <!-- STATS -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
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
      <h2 class="text-2xl font-bold mb-4">Prescriptions</h2>
      <p>View all your prescribed medicines.</p>
    </div>


 <!-- Assign Caretaker -->
<div id="assigned" class="section hidden p-6 md:p-10 bg-surface-light rounded-3xl shadow-lg">
  <h2 class="text-3xl font-extrabold mb-3 text-textMain" data-i18n="assign_caretaker">
    Assign Caretaker
  </h2>
  <p class="text-textSub mb-6 text-sm md:text-base" data-i18n="assign_caretaker_text">
    Add a trusted caretaker who can help manage your medicines.
  </p>

  <!-- Form -->
  <form id="assignCaretakerForm" class="mb-8 space-y-5 bg-white p-6 md:p-8 rounded-2xl shadow-md border border-gray-100">
    <div>
      <label class="block text-sm font-semibold mb-2 text-textMain" for="caretakerName" data-i18n="caretaker_name">
        Name
      </label>
      <input type="text" id="caretakerName" name="name" required
        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary transition">
    </div>

    <div>
      <label class="block text-sm font-semibold mb-2 text-textMain" for="caretakerEmail" data-i18n="caretaker_email">
        Email
      </label>
      <input type="email" id="caretakerEmail" name="email" required
        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary transition">
    </div>

    <div>
      <label class="block text-sm font-semibold mb-2 text-textMain" for="relation" data-i18n="caretaker_relation">
        Relation
      </label>
      <input type="text" id="relation" name="relation" required
        placeholder="e.g., Father, Sister, Friend"
        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-primary focus:border-primary transition" data-i18n-placeholder="relation_placeholder">
    </div>

    <button type="submit"
      class="w-full md:w-auto bg-primary text-white py-3 px-6 rounded-xl font-bold hover:scale-105 hover:shadow-lg transition transform" data-i18n="assign_caretaker_btn">
      Assign Caretaker
    </button>
  </form>

  <!-- Assigned Caretakers List -->
  <div id="caretakerList" class="space-y-4">
    <!-- Dynamically filled by JS -->
  </div>
</div>


    <!-- Alerts -->
 <section id="alerts" class="section hidden">
  <h2 class="text-xl font-bold mb-4">Admin Alerts</h2>

  <div id="alertsContainer" class="space-y-4">
    <!-- alerts will load here -->
  </div>
</section>

    <!-- Medicine Requests -->
    <div id="requests" class="section hidden">
  <h2 class="text-2xl font-bold mb-4">Medicine Requests</h2>
  <p>View all your medicine requests.</p> 
  <div id="requestsContainer" class="mt-4"></div>
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
        body: JSON.stringify({ id })
      });

      const data = await res.json();
      if (data.status === "success") loadSchedule();
      else alert("Failed to delete medicine");
    } catch (err) {
      console.error(err);
      alert("Server error");
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
      card.className =
        "p-4 bg-white rounded-2xl shadow-soft border border-gray-200 flex items-center justify-between";

      card.innerHTML = `
        <div>
          <h3 class="font-bold text-lg">${cg.name}</h3>
          <p class="text-sm text-textSub">${cg.email}</p>
          <p class="text-sm text-textSub capitalize">Relation: ${cg.relation}</p>
        </div>
        <span class="material-symbols-outlined text-primary">person</span>
      `;

      list.appendChild(card);
    });
  } catch (err) {
    console.error(err);
    list.innerHTML = "<p>Server error</p>";
  }
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
  container.innerHTML = '<p class="text-gray-500">Loading alerts...</p>';

  try {
    const res = await fetch('get_alerts.php');
    const data = await res.json();

    container.innerHTML = '';

    if (data.status !== 'success' || data.alerts.length === 0) {
      container.innerHTML = `
        <p class="text-slate-500 text-center">
          No alerts from admin
        </p>`;
      return;
    }

    data.alerts.forEach(alert => {
      container.innerHTML += `
        <div class="p-4 bg-white rounded-xl border border-slate-200 shadow-sm">
          <div class="flex items-start gap-3">
            <span class="material-symbols-outlined text-amber-600">
              notifications
            </span>
            <div>
              <p class="text-slate-800">${alert.message}</p>
              <p class="text-xs text-slate-400 mt-1">
                ${new Date(alert.created_at).toLocaleString()}
              </p>
            </div>
          </div>
        </div>
      `;
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = `
      <p class="text-red-500 text-center">
        Failed to load alerts
      </p>`;
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
async function loadMedicineRequests() {
  const container = document.getElementById("requestsContainer");
  container.innerHTML = "<p class='text-textSub'>Loading requests...</p>";

  try {
    const res = await fetch("fetch_req.php"); // same file can handle fetching/deleting
    const data = await res.json();

    if (data.status !== "success" || !data.requests.length) {
      container.innerHTML = "<p class='text-textSub'>No medicine requests found.</p>";
      return;
    }

    let html = `<table class="w-full border border-slate-300 text-left rounded-lg overflow-hidden">
      <thead class="bg-slate-100">
        <tr>
          <th class="px-4 py-2 border">Name</th>
          <th class="px-4 py-2 border">Dosage</th>
          <th class="px-4 py-2 border">Form</th>
          <th class="px-4 py-2 border">Status</th>
          <th class="px-4 py-2 border">Requested At</th>
          <th class="px-4 py-2 border">Action</th>
        </tr>
      </thead>
      <tbody>`;

    data.requests.forEach(req => {
      let statusClass = "text-gray-600";
      if (req.status === "pending") statusClass = "text-yellow-600 font-semibold";
      if (req.status === "approved") statusClass = "text-green-600 font-semibold";
      if (req.status === "rejected") statusClass = "text-red-600 font-semibold";

      html += `<tr>
        <td class="px-4 py-2 border">${req.name}</td>
        <td class="px-4 py-2 border">${req.dosage}</td>
        <td class="px-4 py-2 border">${req.form}</td>
        <td class="px-4 py-2 border"><span class="${statusClass}">${req.status.charAt(0).toUpperCase() + req.status.slice(1)}</span></td>
        <td class="px-4 py-2 border">${new Date(req.created_at).toLocaleString()}</td>
        <td class="px-4 py-2 border">
          <button class="deleteBtn px-2 py-1 rounded bg-red-500 text-white text-sm" data-id="${req.id}">Delete</button>
        </td>
      </tr>`;
    });

    html += `</tbody></table>`;
    container.innerHTML = html;

    // Attach delete handlers
    document.querySelectorAll('.deleteBtn').forEach(btn => {
      btn.addEventListener('click', async function() {
        const requestId = this.dataset.id;
        if (!confirm("Are you sure you want to delete this request?")) return;

        try {
          const res = await fetch("fetch_req.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=delete_request&request_id=${requestId}`
          });
          const result = await res.json();
          if (result.status === "success") loadMedicineRequests();
          else alert("Failed to delete request");
        } catch (err) {
          console.error(err);
          alert("Server error");
        }
      });
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = "<p class='text-red-500'>Failed to load requests.</p>";
  }
}

// Automatically load requests when user clicks "requests" nav
document.querySelector('[data-section="requests"]')?.addEventListener("click", () => {
  loadMedicineRequests();
});
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

</script>


</body>
</html>
