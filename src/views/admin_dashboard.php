

<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Sushrusha Admin Dashboard</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="../../public/assets/translations.js"></script>
<script src="../../public/assets/language-selector.js"></script>
<!-- Theme Config -->
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1e293b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "sans": ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style type="text/tailwindcss">
        @layer utilities {
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                font-size: 24px;
            }
            .material-symbols-outlined.fill {
                font-variation-settings: 'FILL' 1;
            }
        }
        
        /* Custom scrollbar for cleaner look */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
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
        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-50 font-display transition-colors duration-200">
<div class="flex h-screen w-full overflow-hidden">
<!-- Side Navigation Bar -->
<aside class="w-64 flex-shrink-0 bg-surface-light dark:bg-surface-dark border-r border-slate-200 dark:border-slate-800 flex flex-col transition-colors duration-200 z-20 hidden md:flex">
<div class="h-16 flex items-center px-6 border-b border-slate-100 dark:border-slate-800">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined fill text-3xl">medication_liquid</span>
<span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Sushrusha</span>
</div>
</div>
<div class="flex-1 overflow-y-auto py-6 px-3 flex flex-col gap-6">
<!-- Main Nav -->
<div class="flex flex-col gap-1">
<p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Platform</p>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary group transition-all" href="#">
<span class="material-symbols-outlined fill text-[22px]">dashboard</span>
<span class="text-sm font-medium">Dashboard</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 transition-all" href="#">
<span class="material-symbols-outlined text-[22px]">group</span>
<span class="text-sm font-medium">Users</span>
</a>

<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 transition-all" href="#">
<span class="material-symbols-outlined text-[22px]">article</span>
<span class="text-sm font-medium">Medicine Requests</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 transition-all" href="#">
<span class="material-symbols-outlined text-[22px]">monitoring</span>
<span class="text-sm font-medium">Analytics</span>
</a>
</div>
<!-- Secondary Nav -->
<div class="flex flex-col gap-1">
<p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Management</p>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 transition-all" href="#">
<span class="material-symbols-outlined text-[22px]">notifications_active</span>
<span class="text-sm font-medium">Alerts</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 transition-all" href="#">
<span class="material-symbols-outlined text-[22px]">settings</span>
<span class="text-sm font-medium">Settings</span>
</a>
</div>
</div>
<div class="p-4 border-t border-slate-100 dark:border-slate-800">
<button class="flex items-center gap-3 w-full p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
<img alt="Admin user avatar" class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-700" data-alt="Close up portrait of a smiling man" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBoDoZ-bpH4vak8vDO1t_SkQK8nkXWy3FWZc-f36ePuXp1doGJAkHwYIAgA-hpun7OT6P_nv2FmH4H2GYgMdSGTQaKt9Vqls2FZFtIvjKrirNkXuP7hm_OjWWjl_wiqy6a7WfYVlz0CGND9flSx3zR4N-CBYU6y_2069C3UxQzyDsWZOsx2UWL0RyPgPMDSYTa5TqOsAjYaBkEq6e30BsyvB_6yQkRd9u8bOltBUoRa9UcRGS_ig-JPFD3vUm9Jsa3WMWYSqvmncRE"/>
<div class="flex flex-col items-start">
<p class="text-sm font-medium text-slate-900 dark:text-white">Admin User</p>
<p class="text-xs text-slate-500">Super Admin</p>
</div>
</button>
</div>
</aside>
<!-- Main Content Wrapper -->
<main class="flex-1 flex flex-col h-full overflow-hidden relative">
<!-- Top Header -->
<header class="h-16 flex items-center justify-between px-6 bg-surface-light/80 dark:bg-surface-dark/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 z-10 sticky top-0">
<!-- Mobile Menu Button -->
<button class="md:hidden p-2 text-slate-600 dark:text-slate-300">
<span class="material-symbols-outlined">menu</span>
</button>
<!-- Search -->
<div class="hidden md:flex flex-1 max-w-md">
<div class="relative w-full group">
<span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">search</span>
</span>
<input class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-primary/50 transition-all" placeholder="Search patients, doctors, or alerts..." type="text"/>
</div>
</div>
<!-- Right Actions -->
<div class="flex items-center gap-3">
<div id="langSelectorPlaceholder"></div>
<button class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-primary/20 bg-primary/5 text-primary text-sm font-semibold hover:bg-primary/10 transition-colors">
<span class="material-symbols-outlined text-[18px]">edit_square</span>
<span class="hidden sm:inline">Edit Layout</span>
</button>
<button class="relative p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white dark:border-slate-800"></span>
</button>
</div>
</header>
<!-- Scrollable Content -->
<div class="flex-1 overflow-y-auto p-4 md:p-8 scroll-smooth">
<div class="max-w-7xl mx-auto flex flex-col gap-8">
<!-- Page Heading -->
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
<div>
<h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Welcome back, Admin</h1>
<p class="text-slate-500 dark:text-slate-400 mt-1">Here is your system overview for today. You have <span class="font-medium text-primary cursor-pointer hover:underline">3 new alerts</span>.</p>
</div>
<div class="flex items-center gap-2 text-sm text-slate-500 bg-white dark:bg-slate-800 px-3 py-1 rounded-full shadow-sm border border-slate-200 dark:border-slate-700">
<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            System Operational
                        </div>
</div>
<!-- Draggable Grid Layout (Simulated) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<!-- Metric Card 1 -->
<div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow group relative">
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<div class="flex items-center gap-4 mb-4">
<div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
<span class="material-symbols-outlined">diversity_1</span>
</div>
<p class="text-sm font-medium text-slate-500 dark:text-slate-400">Active Patients</p>
</div>
<div class="flex items-baseline gap-2">
<h3 class="text-3xl font-bold text-slate-900 dark:text-white">1,240</h3>
<span class="text-sm font-medium text-green-600 flex items-center">
<span class="material-symbols-outlined text-[16px]">trending_up</span>
                                    5%
                                </span>
</div>
<p class="text-xs text-slate-400 mt-2">vs. last month</p>
</div>
<!-- Metric Card 2 -->
<div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow group relative">
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<div class="flex items-center gap-4 mb-4">
<div class="p-3 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
<span class="material-symbols-outlined">medication_liquid</span>
</div>
<p class="text-sm font-medium text-slate-500 dark:text-slate-400">Avg Adherence</p>
</div>
<div class="flex items-baseline gap-2">
<h3 class="text-3xl font-bold text-slate-900 dark:text-white">85%</h3>
<span class="text-sm font-medium text-green-600 flex items-center">
<span class="material-symbols-outlined text-[16px]">trending_up</span>
                                    2%
                                </span>
</div>
<p class="text-xs text-slate-400 mt-2">Daily average</p>
</div>
<!-- Metric Card 3 -->
<div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow group relative">
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<div class="flex items-center gap-4 mb-4">
<div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
<span class="material-symbols-outlined">medical_services</span>
</div>
<p class="text-sm font-medium text-slate-500 dark:text-slate-400">Active Caretakers</p>
</div>
<div class="flex items-baseline gap-2">
<h3 class="text-3xl font-bold text-slate-900 dark:text-white">340</h3>
<span class="text-sm font-medium text-green-600 flex items-center">
<span class="material-symbols-outlined text-[16px]">trending_up</span>
                                    12%
                                </span>
</div>
<p class="text-xs text-slate-400 mt-2">Active accounts</p>
</div>
<!-- Metric Card 4 -->
<div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow group relative">
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<div class="flex items-center gap-4 mb-4">
<div class="p-3 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
<span class="material-symbols-outlined">warning</span>
</div>
<p class="text-sm font-medium text-slate-500 dark:text-slate-400">Missed Doses</p>
</div>
<div class="flex items-baseline gap-2">
<h3 class="text-3xl font-bold text-slate-900 dark:text-white">124</h3>
<span class="text-sm font-medium text-red-500 flex items-center">
<span class="material-symbols-outlined text-[16px]">trending_up</span>
                                    8%
                                </span>
</div>
<p class="text-xs text-slate-400 mt-2">Critical attention needed</p>
</div>
</div>
<!-- Complex Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<!-- Main Line Chart -->
<div class="lg:col-span-2 bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm relative group">
<div class="flex justify-between items-center mb-6">
<div>
<h3 class="text-lg font-bold text-slate-900 dark:text-white">Adherence Over Time</h3>
<p class="text-sm text-slate-500">Weekly medication compliance overview</p>
</div>
<div class="flex bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
<button class="px-3 py-1 text-xs font-medium bg-white dark:bg-slate-700 text-slate-900 dark:text-white rounded shadow-sm">7 Days</button>
<button class="px-3 py-1 text-xs font-medium text-slate-500 hover:text-slate-900 dark:hover:text-white">30 Days</button>
</div>
</div>
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<!-- CSS Chart Simulation -->
<div class="h-64 w-full flex items-end justify-between gap-2 px-2 pb-2 relative">
<!-- Background Grid Lines -->
<div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-8">
<div class="border-b border-slate-100 dark:border-slate-800 border-dashed w-full h-px"></div>
<div class="border-b border-slate-100 dark:border-slate-800 border-dashed w-full h-px"></div>
<div class="border-b border-slate-100 dark:border-slate-800 border-dashed w-full h-px"></div>
<div class="border-b border-slate-100 dark:border-slate-800 border-dashed w-full h-px"></div>
<div class="border-b border-slate-200 dark:border-slate-700 w-full h-px"></div>
</div>
<!-- SVG Line Path -->
<svg class="absolute inset-0 w-full h-full pb-8" preserveaspectratio="none">
<defs>
<lineargradient id="gradient" x1="0%" x2="0%" y1="0%" y2="100%">
<stop offset="0%" style="stop-color:#137fec; stop-opacity:0.2"></stop>
<stop offset="100%" style="stop-color:#137fec; stop-opacity:0"></stop>
</lineargradient>
</defs>
<path class="text-primary" d="M0,150 C50,140 100,60 150,80 C200,100 250,40 300,50 C350,60 400,20 450,30 C500,40 550,10 600,20 L600,200 L0,200 Z" fill="url(#gradient)"></path>
<path d="M0,150 C50,140 100,60 150,80 C200,100 250,40 300,50 C350,60 400,20 450,30 C500,40 550,10 600,20" fill="none" stroke="#137fec" stroke-width="3" vector-effect="non-scaling-stroke"></path>
</svg>
<!-- X Axis Labels -->
<div class="absolute bottom-0 w-full flex justify-between text-xs text-slate-400 px-2">
<span>Mon</span>
<span>Tue</span>
<span>Wed</span>
<span>Thu</span>
<span>Fri</span>
<span>Sat</span>
<span>Sun</span>
</div>
</div>
</div>
<!-- Donut Chart -->
<div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm relative group flex flex-col">
<div class="mb-4">
<h3 class="text-lg font-bold text-slate-900 dark:text-white">User Distribution</h3>
<p class="text-sm text-slate-500">Patients vs Caretakers</p>
</div>
<span class="material-symbols-outlined absolute top-4 right-4 text-slate-300 cursor-grab opacity-0 group-hover:opacity-100 transition-opacity">drag_indicator</span>
<div class="flex-1 flex items-center justify-center relative">
<!-- Donut using Conic Gradient -->
<div class="w-48 h-48 rounded-full relative" style="background: conic-gradient(#137fec 0% 75%, #e2e8f0 75% 100%);">
<div class="absolute inset-4 bg-white dark:bg-slate-800 rounded-full flex flex-col items-center justify-center">
<span class="text-3xl font-bold text-slate-900 dark:text-white">1,580</span>
<span class="text-xs text-slate-500">Total Users</span>
</div>
</div>
</div>
<div class="flex justify-center gap-6 mt-6">
<div class="flex items-center gap-2">
<span class="w-3 h-3 rounded-full bg-primary"></span>
<span class="text-sm text-slate-600 dark:text-slate-300">Patients (75%)</span>
</div>
<div class="flex items-center gap-2">
<span class="w-3 h-3 rounded-full bg-slate-200 dark:bg-slate-600"></span>
<span class="text-sm text-slate-600 dark:text-slate-300">Caretakers (25%)</span>
</div>
</div>
</div>
</div>
<!-- Medicine Requests -->
<div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm">
  <div class="p-6 border-b border-slate-200 dark:border-slate-700">
    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
      Medicine Requests
    </h3>
    <p class="text-sm text-slate-500">
      Pending medicine approval requests from users
    </p>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800 text-slate-500 uppercase text-xs">
        <tr>
          <th class="p-4 text-left">Medicine</th>
          <th class="p-4">Dosage</th>
          <th class="p-4">Form</th>
          <th class="p-4">Requested By</th>
          <th class="p-4 text-right">Action</th>
        </tr>
      </thead>
      <tbody id="medicineRequestsTable"
        class="divide-y divide-slate-100 dark:divide-slate-800">
        <!-- populated by JS -->
      </tbody>
    </table>
  </div>
</div>

<!-- Quick Actions -->
<div>
<h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Quick Actions</h2>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
<button class="flex flex-col gap-3 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-surface-light dark:bg-surface-dark hover:border-primary hover:shadow-md transition-all text-left group">
<div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-primary flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">person_add</span>
</div>
<div>
<h3 class="font-bold text-slate-900 dark:text-white">Add Patient</h3>
<p class="text-sm text-slate-500 mt-1">Register a new patient profile</p>
</div>
</button>
<button class="flex flex-col gap-3 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-surface-light dark:bg-surface-dark hover:border-primary hover:shadow-md transition-all text-left group">
<div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">broadcast_on_personal</span>
</div>
<div>
<h3 class="font-bold text-slate-900 dark:text-white">Broadcast Alert</h3>
<p class="text-sm text-slate-500 mt-1">Send system-wide notification</p>
</div>
</button>
<button class="flex flex-col gap-3 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-surface-light dark:bg-surface-dark hover:border-primary hover:shadow-md transition-all text-left group">
<div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined">flag</span>
</div>
<div>
<h3 class="font-bold text-slate-900 dark:text-white">Review Flags</h3>
<p class="text-sm text-slate-500 mt-1">Check flagged accounts &amp; reports</p>
</div>
</button>
</div>
</div>
<!-- Recent Users Table -->
<div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
<div class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-center gap-4">
<div>
<h3 class="text-lg font-bold text-slate-900 dark:text-white">Recent Registrations</h3>
<p class="text-sm text-slate-500">New users added to the system today</p>
</div>
<div class="flex gap-2">
<button class="px-3 py-1.5 text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">Filter</button>
<button class="px-3 py-1.5 text-sm font-medium text-white bg-primary rounded-lg hover:bg-blue-600 shadow-sm shadow-blue-500/30">Export CSV</button>
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider">
<th class="p-4 font-semibold border-b border-slate-200 dark:border-slate-700">User</th>
<th class="p-4 font-semibold border-b border-slate-200 dark:border-slate-700">Role</th>
<th class="p-4 font-semibold border-b border-slate-200 dark:border-slate-700">Status</th>
<th class="p-4 font-semibold border-b border-slate-200 dark:border-slate-700">Adherence</th>
<th class="p-4 font-semibold border-b border-slate-200 dark:border-slate-700 text-right">Actions</th>
</tr>
</thead>
<tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="p-4">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-xs">JD</div>
<div>
<p class="font-medium text-slate-900 dark:text-white">John Doe</p>
<p class="text-slate-500 text-xs">john.doe@example.com</p>
</div>
</div>
</td>
<td class="p-4 text-slate-600 dark:text-slate-300">Patient</td>
<td class="p-4">
<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
</td>
<td class="p-4">
<div class="flex items-center gap-2">
<div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
<div class="h-full bg-green-500 w-[92%]"></div>
</div>
<span class="text-xs font-medium text-slate-600 dark:text-slate-300">92%</span>
</div>
</td>
<td class="p-4 text-right">
<button class="p-1 text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="p-4">
<div class="flex items-center gap-3">
<img alt="Sarah Smith" class="w-8 h-8 rounded-full object-cover" data-alt="Portrait of a woman" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCQXb9nW2RWQu3h5AlPm0sRObUjKhGSM8L9uc3wzgsws5VokQpaUDixb_aMcFKFFX4RYau0j-UfZtshgvKOZsQWr-jfxbh46lwY8NtJKJQJO3UwKkGgRpd0_OyAysF1gx3yL5HEcFy1f0Keh54LiztzFtzyBtEJLdmBypR8Lsq8zWxFoS9SZsaeYcIghm9KWjI384b3Z62g3KbljHwYj-YCZzx60eAUIe_c7s5DbehnNF0SKVa8Ie6Glh3XM8zdajn5RUlbcTQQRSs"/>
<div>
<p class="font-medium text-slate-900 dark:text-white">Sarah Smith</p>
<p class="text-slate-500 text-xs">s.smith@care.com</p>
</div>
</div>
</td>
<td class="p-4 text-slate-600 dark:text-slate-300">Caretaker</td>
<td class="p-4">
<span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Active</span>
</td>
<td class="p-4 text-slate-400 text-xs">N/A</td>
<td class="p-4 text-right">
<button class="p-1 text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
<tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
<td class="p-4">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 flex items-center justify-center font-bold text-xs">RJ</div>
<div>
<p class="font-medium text-slate-900 dark:text-white">Robert Johnson</p>
<p class="text-slate-500 text-xs">bob.j@example.com</p>
</div>
</div>
</td>
<td class="p-4 text-slate-600 dark:text-slate-300">Patient</td>
<td class="p-4">
<span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Review</span>
</td>
<td class="p-4">
<div class="flex items-center gap-2">
<div class="w-16 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
<div class="h-full bg-amber-500 w-[65%]"></div>
</div>
<span class="text-xs font-medium text-slate-600 dark:text-slate-300">65%</span>
</div>
</td>
<td class="p-4 text-right">
<button class="p-1 text-slate-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-[20px]">more_vert</span>
</button>
</td>
</tr>
</tbody>
</table>
</div>
<div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-center">
<button class="text-sm font-medium text-primary hover:text-blue-700 dark:hover:text-blue-400 flex items-center gap-1 transition-colors">
                                View All Users
                                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</button>
</div>
</div>
<!-- Footer -->
<footer class="flex flex-col md:flex-row justify-between items-center text-xs text-slate-400 gap-4 pb-8 mt-4 border-t border-slate-200 dark:border-slate-800 pt-8">
<p>© 2024 Sushrusha Health Systems. All rights reserved.</p>
<div class="flex gap-4">
<a class="hover:text-primary" href="#">Privacy Policy</a>
<a class="hover:text-primary" href="#">Terms of Service</a>
<a class="hover:text-primary" href="#">Support</a>
</div>
</footer>
</div>
</div>
</main>
</div>

<script>
async function loadMedicineRequests() {
  const res = await fetch("admin.php?action=medicine_requests");
  const data = await res.json();

  if (data.status !== 'success') return;

  const table = document.getElementById("medicineRequestsTable");
  table.innerHTML = "";

  if (data.requests.length === 0) {
    table.innerHTML = `
      <tr>
        <td colspan="5" class="p-6 text-center text-slate-400">
          No pending requests 🎉
        </td>
      </tr>`;
    return;
  }

  data.requests.forEach(req => {
    table.innerHTML += `
      <tr>
        <td class="p-4 font-medium">${req.name}</td>
        <td class="p-4 text-center">${req.dosage}</td>
        <td class="p-4 text-center">${req.form}</td>
        <td class="p-4 text-center">${req.requester}</td>
        <td class="p-4 text-right space-x-2">
          <button onclick="approveRequest(${req.id})"
            class="px-3 py-1 bg-green-500 text-white rounded text-xs">
            Approve
          </button>
          <button onclick="rejectRequest(${req.id})"
            class="px-3 py-1 bg-red-500 text-white rounded text-xs">
            Reject
          </button>
        </td>
      </tr>`;
  });
}

async function approveRequest(id) {
  if (!confirm("Approve this medicine?")) return;

  const res = await fetch("admin.php", {
    method: "POST",
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=approve_medicine&id=${id}`
  });

  const data = await res.json();
  alert(data.message);
  loadMedicineRequests();
}

async function rejectRequest(id) {
  if (!confirm("Reject this medicine request?")) return;

  const res = await fetch("admin.php", {
    method: "POST",
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=reject_medicine&id=${id}`
  });

  const data = await res.json();
  alert(data.message);
  loadMedicineRequests();
}

// Load on page start
document.addEventListener("DOMContentLoaded", loadMedicineRequests);
</script>

</body></html>