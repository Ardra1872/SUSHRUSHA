<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$caretakerName = $_SESSION['user_name']; 

?>


<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Sushrusha - Caretaker Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for better aesthetics */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 font-display transition-colors duration-200">
<div class="flex h-screen w-full overflow-hidden">
<!-- Sidebar Navigation -->
<aside class="w-64 flex-shrink-0 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col justify-between p-4 hidden md:flex">
<div class="flex flex-col gap-6">
<!-- Branding / Profile -->
<div class="flex gap-3 items-center px-2">
<div class="bg-center bg-no-repeat bg-cover rounded-full h-10 w-10 border border-slate-200 dark:border-slate-700" data-alt="Profile picture of a nurse" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCItgVsB6L9oTlgCsJ8uKlfyuXNaBC3_zzplsS5VRlMRMD71PQ-ot_PK5tedVK_TYgrcOGs-DuzCoieo-nh_SOKe8VqtGXzC2GMR6DhU-_0kSNQ6bs5agmYx5GY1neARLGeMxOVc8LW2b8NDTjwmNUaNoW_edEBLAAPYpmzyR3QA1Ly3bQjUTkyIXRcImJEKDudQg3Qa7F7rjhlpeaYahlq7RBSnkIeym8PqLtbmoNNmM6Z2JTreop1nUbAVQPYdXagI8G21wSPFuE");'>
</div>
<div class="flex flex-col">
<h1 class="text-slate-900 dark:text-white text-base font-bold leading-none">Sushrusha</h1>

</div>
</div>
<!-- Nav Links -->
<nav class="flex flex-col gap-2" id="sidebarNav">

  <a data-section="dashboard"
     class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg bg-primary text-white transition-colors group cursor-pointer">
    <span class="material-symbols-outlined">dashboard</span>
    <p class="text-sm font-medium">Dashboard</p>
  </a>

  <a data-section="patients"
     class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
    <span class="material-symbols-outlined group-hover:text-primary">group</span>
    <p class="text-sm font-medium">Patient List</p>
  </a>

  <a data-section="schedule"
     class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
    <span class="material-symbols-outlined group-hover:text-primary">calendar_month</span>
    <p class="text-sm font-medium">Schedule</p>
  </a>

  <a data-section="messages"
     class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
    <span class="material-symbols-outlined group-hover:text-primary">chat</span>
    <p class="text-sm font-medium">Messages</p>
  </a>

  <a data-section="settings"
     class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group cursor-pointer">
    <span class="material-symbols-outlined group-hover:text-primary">settings</span>
    <p class="text-sm font-medium">Settings</p>
  </a>

</nav>

</div>
<!-- Bottom Action -->
<div class="px-2">
    <a href="../../public/logout.php"
   class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
  <span class="material-symbols-outlined">logout</span>
  <p class="text-sm font-medium">Sign Out</p>
</a>


</button>
</div>
</aside>
<!-- Main Content -->
<main class="flex-1 flex flex-col h-full overflow-hidden relative">
<!-- Mobile Header (Visible only on small screens) -->
<div class="md:hidden flex items-center justify-between p-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
<div class="flex items-center gap-2">
<div class="bg-center bg-no-repeat bg-cover rounded-full h-8 w-8" data-alt="App logo placeholder" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCI2aGdFBsFOLf8Ps5ojHofrg1H-3s04T2DkYrxv6gXeIhH97r6z6YcAOrBINIwIeOP0_im3TO1Io66Y2WkaWo1GdGy1HWDg3HDAgrTfC843DFkt_bvXcsKdhpkLBAb2dwQKS3nql84SC9t-ZV5SOo7LZ1bqWADFVSvvkYheUmpj8eA_X_2RyzXCLYTQxDu7H0pdivZF4AFY41_YiXuqJKbvVJw7d6yXLDatm_hvw3LxPDc-0kLoDInMa8wHQixti7vOLAheqJIkGE");'>
</div>
<span class="font-bold text-lg">Sushrusha</span>
</div>
<button class="text-slate-500">
<span class="material-symbols-outlined">menu</span>
</button>
</div>
<div class="flex-1 overflow-y-auto p-4 md:p-8 lg:px-12">
<div class="max-w-7xl mx-auto space-y-8 pb-12">
    <section id="dashboard" class="content-section">

<!-- Page Header & Search -->
<div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
<div class="flex flex-col gap-2">
   <h2 class="text-xl font-bold">
  Welcome, <?php echo htmlspecialchars($caretakerName); ?> 👋
</h2>

<p class="text-slate-500 dark:text-slate-400 text-base">Here is your daily overview for <span class="font-semibold text-primary">Ward 4B</span>.</p>
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
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
<!-- Card 1 -->
<div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between h-32 relative overflow-hidden group">
<div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-primary">groups</span>
</div>
<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Total Patients</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-3xl font-bold text-slate-900 dark:text-white">124</p>
<span class="text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded text-xs font-semibold">+2%</span>
</div>
</div>
<!-- Card 2 -->
<div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between h-32 relative overflow-hidden group">
<div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-red-500">medication_liquid</span>
</div>
<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Missed Doses Today</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-3xl font-bold text-slate-900 dark:text-white">3</p>
<span class="text-red-600 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded text-xs font-semibold">+1</span>
</div>
</div>
<!-- Card 3 -->
<div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between h-32 relative overflow-hidden group">
<div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-amber-500">prescriptions</span>
</div>
<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Upcoming Refills</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-3xl font-bold text-slate-900 dark:text-white">8</p>
<span class="text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-1.5 py-0.5 rounded text-xs font-semibold">Due Soon</span>
</div>
</div>
<!-- Card 4 -->
<div class="bg-white dark:bg-slate-900 rounded-xl p-6 shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col justify-between h-32 relative overflow-hidden group">
<div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-primary">analytics</span>
</div>
<p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Avg Adherence</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-3xl font-bold text-slate-900 dark:text-white">92%</p>
<span class="text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded text-xs font-semibold">+5%</span>
</div>
</div>
</div>
<!-- Split Layout: Critical Alerts & Schedule -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<!-- Left Column: Critical Attention (2/3 width) -->
<div class="lg:col-span-2 space-y-6">
<!-- Section Header -->
<div class="flex items-center justify-between">
<h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
<span class="material-symbols-outlined text-red-500">warning</span>
                                    Critical Attention Needed
                                </h3>
<a class="text-primary text-sm font-medium hover:underline" href="#">View All Alerts</a>
</div>
<!-- Alert Card 1 -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-red-100 dark:border-red-900/30 p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center shadow-sm">
<div class="h-12 w-12 rounded-full bg-cover bg-center shrink-0 border-2 border-red-100 dark:border-red-900" data-alt="Portrait of elderly man" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBhO5sMwvsEiUi7coWSi0sXRnqrxWVEiVZQ6nY0j78RfzG_wHuETc1cmG-3E67BEjsSbZnDCt-NMzePtgo_vwXI6AmMI-LsrmTQAC4JOicePzN6G41ELhcWbKx7cbllt2q4a-UX-kRk6Hgitrno47Gdlu6PjYCur9BAvjbzmSQQmm8NA1rJEPmb7FufEznT349BGmtYMPJHnw_5A9PnJGMrkCnWVlOkhyQr_1-XNQK6junmr8_ZG6CAqQQNcNA9GWR3T2dUVSwwJmg");'>
</div>
<div class="flex-1">
<h4 class="text-slate-900 dark:text-white font-bold text-sm">John Doe</h4>
<p class="text-slate-500 dark:text-slate-400 text-sm">Missed 9:00 AM Insulin dose. No response to automated reminder.</p>
</div>
<div class="flex gap-2 w-full sm:w-auto">
<button class="flex-1 sm:flex-none px-3 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium transition-colors">
                                        Log Incident
                                    </button>
<button class="flex-1 sm:flex-none px-3 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors">
                                        Call Patient
                                    </button>
</div>
</div>
<!-- Alert Card 2 -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-amber-100 dark:border-amber-900/30 p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center shadow-sm">
<div class="h-12 w-12 rounded-full bg-cover bg-center shrink-0 border-2 border-amber-100 dark:border-amber-900" data-alt="Portrait of elderly woman" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBPriI-MbxMsh-iLMWzgY41p8IY97XQyc5revjI22zjFmp_6UE4jCwzduHYcpSekDN7ovJ71cP1GK_DHYL3mVm6Zkm9vkwnupxJhVtXUHf7DmSFJZgF3bzqgJCaEiomdwnGXOaxcyqRGOWaaJ1g4K6kJ6So2rIOHp8POt-IqQAleldjFe96AkVz5iCexdhcyUjTyV_Ggm90jv69h9UU6YLYej51nNzhBgaHMKs9VUt0WWk7g7GtbEnGsI5DqkY0Q3LDlR6T1UBzfqg");'>
</div>
<div class="flex-1">
<h4 class="text-slate-900 dark:text-white font-bold text-sm">Martha Stewart</h4>
<p class="text-slate-500 dark:text-slate-400 text-sm">Refill for Atorvastatin overdue by 2 days.</p>
</div>
<div class="flex gap-2 w-full sm:w-auto">
<button class="flex-1 sm:flex-none px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium transition-colors">
                                        Details
                                    </button>
<button class="flex-1 sm:flex-none px-3 py-2 bg-primary hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors">
                                        Reorder
                                    </button>
</div>
</div>
<!-- Patient List Section -->
<div class="pt-4">
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
<th class="px-6 py-3 text-right">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-100 dark:divide-slate-800">
<!-- Row 1 -->
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
<div class="h-8 w-8 rounded-full bg-cover bg-center" data-alt="Portrait of patient Robert" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB3Znx6aD_zWPQg67Si8aqNnn8rClzcmd9sykuZEPE2zgpikLxiO0D-BWEJcDuzseyG4fu8bleoI-N0L6yhWNWtaEwSTwHMbajdpRqCwWI3mgWuQm73vcYRFlhxp-CwF-ILd3krouvewBDyMGxNRa5yLGxHKla_aAsN_c4212WYr3GEoZFqOtHEBB-RykO2KcCAdIzYcccOIrCwTTBrW8bS1gVJA9Q2pdDNPaeDmtE6rFDGWXZyM-QV4AuQgYVXXyBzmjdqnmwVWyY");'>
</div>
                                                    Robert Fox
                                                </td>
<td class="px-6 py-4 text-slate-500 dark:text-slate-400">
<div class="flex flex-col">
<span class="font-medium text-slate-700 dark:text-slate-300">2:00 PM</span>
<span class="text-xs">Metformin</span>
</div>
</td>
<td class="px-6 py-4">
<div class="flex items-center gap-2">
<div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 95%"></div>
</div>
<span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">95%</span>
</div>
</td>
<td class="px-6 py-4">
<span class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded text-xs font-medium border border-emerald-100 dark:border-emerald-900/30">On Track</span>
</td>
<td class="px-6 py-4 text-right">
<button class="text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
<!-- Row 2 -->
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
<div class="h-8 w-8 rounded-full bg-cover bg-center" data-alt="Portrait of patient Esther" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBIBreMTSzZKw_moXtov4_Yl3kI05gFJFKRVBthzZIYaOMLsc8QyP30Gjua0ZnLyPr9paVmsLKoy3dfo0srVCtNr4NGGMW2hfk9GsayVCROYkx2n9wUgokpgiqR9pJDOQ4rKsLvqYXNrxkY3ec71oycqUVRouOwtiH35BZUD_5oJ99v5gtpR5j_IDitr258H8ZAk5StYl6K-A5KNZEGqNs8yeanD-5Wp2wF557OJwiGYgYOoK1qcbuqIyv9H-jrgW9gdCJeUNIPglY");'>
</div>
                                                    Esther Howard
                                                </td>
<td class="px-6 py-4 text-slate-500 dark:text-slate-400">
<div class="flex flex-col">
<span class="font-medium text-slate-700 dark:text-slate-300">4:30 PM</span>
<span class="text-xs">Lisinopril</span>
</div>
</td>
<td class="px-6 py-4">
<div class="flex items-center gap-2">
<div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
<div class="bg-amber-500 h-1.5 rounded-full" style="width: 78%"></div>
</div>
<span class="text-xs font-medium text-amber-600 dark:text-amber-400">78%</span>
</div>
</td>
<td class="px-6 py-4">
<span class="bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 px-2 py-1 rounded text-xs font-medium border border-amber-100 dark:border-amber-900/30">Review</span>
</td>
<td class="px-6 py-4 text-right">
<button class="text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
<!-- Row 3 -->
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
<div class="h-8 w-8 rounded-full bg-cover bg-center" data-alt="Portrait of patient Cameron" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDXJ-bfTcpbfGdGRel8gF0HX0RBwf2CQzLhdi9Op50tytuj87gxzv4J80dPQ317D_Utm-OgCqLiMod5WUdGe_PqrWCA5yqBn7qkoVHzW9WgLhnxiN2xcb-VhLKqrerLJ-9ZYVCnXkvaDG7hNC1kCAe4qChyvMs4wHRT7ddnQz8qeJnnGK3GT8TKQsigUYZ5EVZmAFvcab_LGTaY3wmgCQhklN2gsV5Ofc1UyEL_Hpib0LTsCtIy_9sKvV3EgBTdQx1JAyAzxTnSoEU");'>
</div>
                                                    Cameron Williamson
                                                </td>
<td class="px-6 py-4 text-slate-500 dark:text-slate-400">
<div class="flex flex-col">
<span class="font-medium text-slate-700 dark:text-slate-300">6:00 PM</span>
<span class="text-xs">Aspirin</span>
</div>
</td>
<td class="px-6 py-4">
<div class="flex items-center gap-2">
<div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
<div class="bg-emerald-500 h-1.5 rounded-full" style="width: 98%"></div>
</div>
<span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">98%</span>
</div>
</td>
<td class="px-6 py-4">
<span class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 rounded text-xs font-medium border border-emerald-100 dark:border-emerald-900/30">On Track</span>
</td>
<td class="px-6 py-4 text-right">
<button class="text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<!-- Right Column: Timeline / Updates (1/3 width) -->
<div class="flex flex-col gap-6">
<!-- Messages Widget -->
<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800 shadow-sm p-5">
<div class="flex justify-between items-center mb-4">
<h3 class="text-lg font-bold text-slate-900 dark:text-white">Recent Messages</h3>
<a class="text-xs font-medium text-primary hover:underline" href="#">View All</a>
</div>
<div class="flex flex-col gap-4">
<!-- Msg 1 -->
<div class="flex gap-3 items-start p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
<div class="relative">
<div class="h-10 w-10 rounded-full bg-cover bg-center" data-alt="Portrait of Dr. Smith" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBmkPVqQfp3vdu5XJXYr5K83FF_Qjj1pb8FgReP8zbRYhGC50FQ1OGO1mES2TZIGB0AKzrUZWEzgfYiKxjWlfptuGkktw_8g3vCZjikzS9r5ZYQvbPgEYFkUQ1mW40CnsqeYeLEXa566Zp37zcFmo88AOESGEyeA1eOKGqx4dJ0BA1OYladr-L3JdJC4q57ZXuJCQetqRrlBNhHkrYSpJrJMMFn_B2KuPKh-Uf2SZgqDQh3DsIoPe3aVwza5srvQYj_wGNjXxCg9os");'>
</div>
<span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
</div>
<div class="flex-1 min-w-0">
<div class="flex justify-between items-baseline">
<p class="text-sm font-semibold text-slate-900 dark:text-white truncate">Dr. Smith</p>
<p class="text-[10px] text-slate-400">10m</p>
</div>
<p class="text-xs text-slate-500 truncate">RE: John's insulin dosage adjustment...</p>
</div>
</div>
<!-- Msg 2 -->
<div class="flex gap-3 items-start p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
<div class="relative">
<div class="h-10 w-10 rounded-full bg-cover bg-center" data-alt="Portrait of Sarah" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDWpHvD5K3xxRPbvqklTSh3PxhPEtyZoe1jTgWRepRrJmypN2oocKx17dZ6DwVPMxIN2XyJPxPZ6hU-FebES0krJXcbMiisf0xcD8oNaSma6GjKheiKHmwjCvDb5pOh3qsr8S6Q5wR42x-ESz6ql1mv8CJfNZQV71LUR4kNt65he0nWSX_e66kNiDbZdSVZGgl_SmjCq7FbBSOA4_D9PapsefILvfr8j-ZBmyDK36QpQc_xDvaxDpW3FTOpFne5Gjrkfw2JnzxTUYE");'>
</div>
<span class="absolute bottom-0 right-0 w-3 h-3 bg-gray-400 border-2 border-white dark:border-slate-900 rounded-full"></span>
</div>
<div class="flex-1 min-w-0">
<div class="flex justify-between items-baseline">
<p class="text-sm font-semibold text-slate-900 dark:text-white truncate">Sarah (Family)</p>
<p class="text-[10px] text-slate-400">1h</p>
</div>
<p class="text-xs text-slate-500 truncate">Will pick up meds tomorrow.</p>
</div>
</div>
</div>
<button class="mt-4 w-full py-2 text-sm text-primary font-medium bg-primary/10 rounded-lg hover:bg-primary/20 transition-colors">
                                    Compose Message
                                </button>
</div>
<!-- Upcoming Schedule Mini-View -->
<div class="bg-primary text-white rounded-xl shadow-lg p-5 relative overflow-hidden">
<!-- Background Pattern -->
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
<div class="flex flex-col gap-3">
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
</div>
</div>
</div>
</section>
<section id="patients" class="content-section hidden">
  <h2>Patient List</h2>
  <p>Manage assigned patients here.</p>
</section>

<section id="schedule" class="content-section hidden">
  <h2>Schedule</h2>
  <p>Medicine schedules and reminders.</p>
</section>

<section id="messages" class="content-section hidden">
  <h2>Messages</h2>
  <p>Caretaker ↔ Patient communication.</p>
</section>

<section id="settings" class="content-section hidden">
  <h2>Settings</h2>
  <p>Profile and preferences.</p>
</section>

</main>
</div>
<script>


const navLinks = document.querySelectorAll(".nav-link");
const sections = document.querySelectorAll(".content-section");

navLinks.forEach(link => {
  link.addEventListener("click", () => {

    // Reset nav styles
    navLinks.forEach(l => {
      l.classList.remove("bg-primary", "text-white");
      l.classList.add("text-slate-700", "dark:text-slate-300");
    });

    // Activate clicked nav
    link.classList.add("bg-primary", "text-white");
    link.classList.remove("text-slate-700");

    const target = link.dataset.section;

    sections.forEach(section => {
      section.classList.toggle("hidden", section.id !== target);
    });
  });
});

</script>
</body></html>