<?php
session_start();

include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role   = $_SESSION['user_role'];
$name = $_SESSION['user_name'];




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
<aside class="w-72 bg-surface border-r border-slate-200 flex flex-col">
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
    <span class="material-symbols-outlined">dashboard</span> Dashboard
  </a>

  <!-- Patient -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="schedule">
    <span class="material-symbols-outlined">calendar_month</span> My Schedule
  </a>
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="prescriptions">
    <span class="material-symbols-outlined">pill</span> Prescriptions
  </a>

  <!-- Caretaker -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="assigned">
    <span class="material-symbols-outlined">people</span> Assign Caretaker
  </a>
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="alerts">
    <span class="material-symbols-outlined">notifications</span> Alerts
  </a>

  <!-- Admin -->
  <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="requests">
    <span class="material-symbols-outlined">inventory_2</span> Medicine Requests
  </a>
  <!-- <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl text-textSub hover:bg-slate-100" data-section="users">
    <span class="material-symbols-outlined">manage_accounts</span> Manage Users
  </a> -->
</nav>

<div class="p-6 space-y-3">
  <a href="add_medicine.html"
     class="w-full bg-gradient-to-r from-primary to-primaryDark text-white py-3 rounded-xl font-bold shadow-lg hover:scale-[1.02] transition text-center block">
    + Add Medicine
  </a>

  <a href="../../public/logout.php"
     class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white py-3 rounded-xl font-bold shadow-lg hover:scale-[1.02] transition text-center block">
    Logout
  </a>
</div>


</aside>

<!-- MAIN -->
<main class="flex-1 flex flex-col overflow-hidden">

<!-- TOPBAR -->
<header class="h-20 bg-white border-b border-slate-200 px-8 flex items-center justify-between">
<p class="text-sm font-bold text-textMain leading-tight">
  <?= htmlspecialchars($name) ?>
</p>


<p class="text-text-sub text-sm">
  <?= ucfirst($role) ?> dashboard • <br>Here's your health overview for today
</p>


  <div class="flex items-center gap-5">
    <input class="hidden md:block w-64 rounded-xl bg-slate-100 px-4 py-2 text-sm focus:ring-2 focus:ring-primary" placeholder="Search medicines…">
    <button class="relative">
      <span class="material-symbols-outlined text-textSub">notifications</span>
      <span class="absolute top-0 right-0 size-2 bg-danger rounded-full"></span>
    </button>
    <div class="flex items-center gap-3">
      <div class="text-right text-sm">
        <p class="text-sm font-bold text-text-main dark:text-white leading-tight">
  <?= htmlspecialchars($name) ?>
</p>
<p class="text-xs text-text-sub capitalize">
  <?= htmlspecialchars($role) ?>
</p>

      </div>
      <div class="w-10 h-10 rounded-full bg-slate-200"></div>

    </div>
  </div>
</header>
<!-- CONTENT -->
<div class="flex-1 overflow-y-auto p-8">
  <div class="max-w-[1500px] mx-auto space-y-8">

    <!-- Dashboard Section -->
    <div id="dashboard" class="section">
      <h2 class="text-2xl font-bold mb-4">Dashboard Overview</h2>
      <p>Here’s your health overview for today.</p>

      <!-- STATS -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub">Adherence</p>
          <h3 class="text-4xl font-display font-bold mt-2">92%</h3>
          <div class="h-2 bg-slate-100 rounded-full mt-4">
            <div class="h-2 bg-primary rounded-full w-[92%]"></div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub">Missed Doses</p>
          <h3 class="text-4xl font-display font-bold mt-2 text-danger">1</h3>
          <p class="text-xs text-textSub mt-2">This week</p>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-soft">
          <p class="text-sm text-textSub">Next Refill</p>
          <h3 class="text-4xl font-display font-bold mt-2">Oct 24</h3>
          <p class="text-xs text-textSub mt-2">Atorvastatin</p>
        </div>
      </div>

      <!-- SCHEDULE -->
      <div class="bg-white rounded-2xl shadow-soft mt-6">
        <div class="p-6 border-b">
          <h3 class="font-display font-bold text-xl">Today’s Schedule</h3>
          <p class="text-sm text-textSub">Your medicine intake timeline</p>
        </div>

        <div class="p-6 space-y-6">
          <div class="flex items-start gap-6">
            <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center">
              <span class="material-symbols-outlined text-success">check</span>
            </div>
            <div>
              <p class="text-sm text-textSub">10:00 AM</p>
              <h4 class="font-bold line-through">Metformin 500mg</h4>
            </div>
          </div>

          <div class="flex items-start gap-6">
            <div class="size-12 rounded-full bg-primary text-white flex items-center justify-center animate-pulse">
              <span class="material-symbols-outlined">pill</span>
            </div>
            <div class="flex-1 bg-primary/10 p-6 rounded-2xl">
              <p class="text-sm text-primary font-semibold">Next Up • 02:00 PM</p>
              <h4 class="text-xl font-display font-bold">Vitamin D 1000 IU</h4>
              <div class="flex gap-3 mt-4">
                <button class="flex-1 bg-primary text-white py-2 rounded-xl font-bold">Mark Taken</button>
                <button class="flex-1 border py-2 rounded-xl">Skip</button>
              </div>
            </div>
          </div>

          <div class="flex items-start gap-6">
            <div class="size-12 rounded-full bg-slate-100 flex items-center justify-center">
              <span class="material-symbols-outlined text-textSub">bedtime</span>
            </div>
            <div>
              <p class="text-sm text-textSub">08:00 PM</p>
              <h4 class="font-bold">Atorvastatin 10mg</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- My Schedule -->
    <div id="schedule" class="section hidden">
      <h2 class="text-2xl font-bold mb-4">My Schedule</h2>
      <p>Check your medicine intake timeline here.</p>
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
  <h2 class="text-2xl font-bold mb-2">Assign Caretaker</h2>
  <p class="text-textSub mb-6">Add a trusted caretaker who can help manage your medicines.</p>

  <!-- Form -->
  <form id="assignCaretakerForm" class="mb-6 space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1" for="caretakerName">Name</label>
      <input type="text" id="caretakerName" name="name" required
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="caretakerEmail">Email</label>
      <input type="email" id="caretakerEmail" name="email" required
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" for="relation">Relation</label>
      <input type="text" id="relation" name="relation" required
        placeholder="e.g., Father, Sister, Friend"
        class="w-full rounded-xl border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-primary">
    </div>

    <button type="submit"
      class="bg-primary text-white py-2 px-6 rounded-xl font-bold hover:scale-[1.02] transition">
      Assign Caretaker
    </button>
  </form>

  <!-- Assigned Caretakers List -->
  <div id="caretakerList" class="space-y-4">
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
  const navItems = document.querySelectorAll('.nav-item');
  const sections = document.querySelectorAll('.section');

  navItems.forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();

      // Remove active style from all nav items
      navItems.forEach(nav => nav.classList.remove('bg-primary/10', 'text-primary'));
      navItems.forEach(nav => nav.classList.add('text-textSub'));

      // Highlight clicked item
      item.classList.add('bg-primary/10', 'text-primary');
      item.classList.remove('text-textSub');

      const sectionToShow = item.dataset.section;

      // Hide all sections
      sections.forEach(sec => sec.classList.add('hidden'));

      // Show the selected section
      const activeSection = document.getElementById(sectionToShow);
      if (activeSection) activeSection.classList.remove('hidden');
    });
  });
  async function loadSchedule() {
  const container = document.getElementById("scheduleList");
  container.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch("fetch_medicine.php");
    const data = await res.json();

    if (data.status !== "success") {
      container.innerHTML = `<p>${data.message || "Failed to load schedule"}</p>`;
      return;
    }

    if (!data.medicines.length) {
      container.innerHTML = "<p>No medicines scheduled</p>";
      return;
    }

    container.innerHTML = "";

    data.medicines.forEach(med => {
      const days = med.days.length ? med.days.join(", ") : "-";
      const times = med.times.length ? med.times.join(", ") : (med.interval_hours ? med.interval_hours + "h interval" : "-");
      const endDate = med.end_date || "-";

      const card = document.createElement("div");
      card.className = "p-4 bg-gray-50 rounded-xl border border-gray-200 shadow-sm";

      card.innerHTML = `
        <h3 class="text-lg font-bold">${med.name} (${med.dosage_value})</h3>
        <p>Form: ${med.medicine_type}</p>
        <p>Compartment: ${med.compartment_number}</p>
        <p>Schedule: ${med.schedule_type} (${days})</p>
        <p>Reminder: ${med.reminder_type} - ${times}</p>
        <p>Duration: ${med.start_date} → ${endDate}</p>
      `;

      container.appendChild(card);
    });

  } catch (err) {
    console.error(err);
    container.innerHTML = "<p>Server error</p>";
  }
}

// Call when page loads
document.addEventListener("DOMContentLoaded", loadSchedule);

const form = document.getElementById("assignCaretakerForm");
const list = document.getElementById("caretakerList");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const formData = new FormData(form);

  try {
    const res = await fetch("assign_caretaker.php", {
      method: "POST",
      body: formData
    });

    const data = await res.text(); // because your PHP currently does a header redirect
    // You may want to return JSON from PHP instead, but for now we just reload list

    // Reload caretakers list
    loadCaretakers();

    // Clear form
    form.reset();
  } catch (err) {
    console.error(err);
    alert("Error assigning caretaker");
  }
});

// Function to load caretakers from backend
async function loadCaretakers() {
  list.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch("fetch_caretaker.php"); // we'll create this
    const data = await res.json();

    if (data.status !== "success" || !data.caretakers.length) {
      list.innerHTML = "<p>No caretakers assigned yet.</p>";
      return;
    }

    list.innerHTML = "";
    data.caretakers.forEach(cg => {
      const card = document.createElement("div");
      card.className = "p-4 bg-white rounded-2xl shadow-soft border border-gray-200 flex items-center justify-between";

      card.innerHTML = `
        <div>
          <h3 class="font-bold text-lg">${cg.name}</h3>
          <p class="text-sm text-textSub">${cg.email}</p>
          <p class="text-sm text-textSub capitalize">Relation: ${cg.relation}</p>
        </div>
        <div>
          <span class="material-symbols-outlined text-primary">person</span>
        </div>
      `;

      list.appendChild(card);
    });
  } catch (err) {
    console.error(err);
    list.innerHTML = "<p>Server error</p>";
  }
}

// Load caretakers on page load
document.addEventListener("DOMContentLoaded", loadCaretakers);

</script>

</body>
</html>
