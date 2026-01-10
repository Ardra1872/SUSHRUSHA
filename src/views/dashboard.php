<?php
session_start();

include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['role'];
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

<body class="bg-bg text-textMain font-body h-screen flex overflow-hidden">

<!-- SIDEBAR -->
<aside id="sidebar"
  class="fixed inset-y-0 left-0 z-40 w-72 bg-surface border-r border-slate-200 flex flex-col
         transform -translate-x-full transition-transform duration-300">

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

  <a href="../../public/logout.php"
     class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white py-3 rounded-xl font-bold shadow-lg hover:scale-[1.02] transition text-center block"
     data-i18n="logout">
    Logout
  </a>
</div>



</aside>
<div id="overlay"
  class="fixed inset-0 bg-black/40 z-30 hidden">
</div>
<!-- MAIN -->
<main class="flex-1 flex flex-col overflow-hidden">

<!-- TOPBAR -->

<header class="h-20 bg-white border-b border-slate-200 px-6 flex items-center">

  <!-- LEFT: Hamburger + Brand -->
  <div class="flex items-center gap-4 min-w-[220px]">

    <button id="menuBtn" class="text-textSub">
      <span class="material-symbols-outlined text-3xl">menu</span>
    </button>

    <div>
      <p class="font-display font-bold leading-tight"data-i18n="brand_name_topbar">SUSHRUSHA</p>
      <p class="text-xs text-textSub leading-tight"data-i18n="brand_tagline_topbar">Smart Medicine Reminder</p>
    </div>

  </div>

  <!-- CENTER: Greeting -->
  <div class="flex-1 text-center hidden md:block">
  <p class="text-lg font-semibold">
    <span id="greeting">Good Morning</span>,

    <span class="text-primary dynamic-text">
      <?= htmlspecialchars($name) ?>
    </span>
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
  <button id="langBtn" class="p-2 rounded-full hover:bg-gray-100">
    🌐
  </button>

  <div id="langMenu"
       class="hidden absolute right-0 mt-2 w-40 bg-white border rounded-lg shadow-md z-50">
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-gray-100"
            data-lang="en">English</button>
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-gray-100"
            data-lang="ml">Malayalam</button>
  </div>
</div>


    <!-- Profile -->
    <a href="profile.php" class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm">
      <?php if (!empty($profilePhoto)): ?>
        <img src="<?= $profilePhoto ?>" 
             alt="Profile picture" 
             class="w-full h-full object-cover">
      <?php else: ?>
        <div class="w-full h-full bg-primary/10 flex items-center justify-center">
          <span class="text-primary font-semibold text-sm"><?= htmlspecialchars($userInitials) ?></span>
        </div>
      <?php endif; ?>
    </a>

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

    <!-- My Schedule -->
    <div id="schedule" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">My Medicines</h2>
      <p>Medicine list </p>
      <div id="scheduleList" class="mt-6 grid gap-4">
    <!-- Medicine cards will be injected here -->
  </div>
    </div>

    <!-- Prescriptions -->
    <div id="prescriptions" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">Prescriptions</h2>
      <p>View all your prescribed medicines.</p>
    </div>


    <!-- Assign Caretaker -->
<div id="assigned" class="section hidden">
  <h2 class="text-2xl font-bold mb-2"data-i18n="assign_caretaker">Assign Caretaker</h2>
  <p class="text-textSub mb-6"data-i18n="assign_caretaker_text">Add a trusted caretaker who can help manage your medicines.</p>

  <!-- Form -->
  <form id="assignCaretakerForm" class="mb-6 space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1" for="caretakerName"data-i18n="caretaker_name">Name</label>
      <input type="text" id="caretakerName" name="name" required
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="caretakerEmail" caretaker_email>Email</label>
      <input type="email" id="caretakerEmail" name="email" required
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="relation"caretaker_relation>Relation</label>
      <input type="text" id="relation" name="relation" required
        placeholder="e.g., Father, Sister, Friend"
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary"data-i18n-placeholder="relation_placeholder">
    </div>

    <button type="submit"
      class="bg-primary text-white py-2 px-6 rounded-xl font-bold hover:scale-[1.02] transition"data-i18n="assign_caretaker_btn">
      Assign Caretaker
    </button>
  </form>

  <!-- Assigned Caretakers List -->
  <div id="caretakerList" class="space-y-4 dynamic-text">
    <!-- Dynamically filled by JS -->
  </div>
</div>


    <!-- Alerts -->
    <div id="alerts" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">Alerts</h2>
      <p>See notifications and alerts.</p>
    </div>

    <!-- Medicine Requests -->
    <div id="requests" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">Medicine Requests</h2>
      <p>View all incoming medicine requests.</p>
    </div>

    <!-- Manage Users -->
    <div id="users" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">Manage Users</h2>
      <p>Admin panel for user management.</p>
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
}

function renderMedicines(medicines) {
  const container = document.getElementById("scheduleList");

  if (!medicines.length) {
    container.innerHTML = "<p>No medicines found</p>";
    return;
  }

  container.innerHTML = "";

  medicines.forEach(med => {
    const times = med.times.length
      ? med.times.join(", ")
      : (med.interval_hours ? med.interval_hours + "h interval" : "-");

    const card = document.createElement("div");
    card.className = "p-4 bg-white rounded-2xl shadow-soft border border-gray-200";

    card.innerHTML = `
      <h3 class="text-lg font-bold">${med.name}</h3>
      <p class="text-sm text-textSub">Dosage: ${med.dosage_value}</p>
      <p class="text-sm text-textSub">Form: ${med.medicine_type}</p>
      <p class="text-sm text-textSub">Schedule: ${med.schedule_type}</p>
      <p class="text-sm text-textSub">Time: ${times}</p>
    `;

    container.appendChild(card);
  });
}

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
    switchSection(item.dataset.section);
    closeSidebar();
  });
});


/* =====================================================
   INIT
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  loadSchedule();
  loadCaretakers();
  initTranslationSystem();
});
</script>


</body>
</html>
