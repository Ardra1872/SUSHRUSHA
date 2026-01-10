<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$name = $_SESSION['user_name'];

$stmt = $conn->prepare("
  SELECT 
    u.name,
    u.emergency_contact,
    p.dob,
    p.gender,
    p.blood_group,
    p.height_cm,
    p.weight_kg,
    p.profile_photo
  FROM users u
  LEFT JOIN patient_profile p ON u.id = p.patient_id
  WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

?>

<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Sushrusha - Profile Settings</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<script src="../../public/assets/translations.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
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
        /* Custom toggle switch styles */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #137fec;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #137fec;
        }
        
        /* Hide scrollbar for sidebar but keep functionality */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Ensure sticky sidebar works */
        @media (min-width: 768px) {
            .layout-container {
                position: relative;
            }
            aside {
                position: -webkit-sticky;
                position: sticky;
            }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#0d141b] dark:text-white antialiased overflow-x-hidden">
<div class="relative flex flex-col min-h-screen w-full">
<!-- TopNavBar -->
<header class="sticky top-0 z-50 flex items-center justify-between whitespace-nowrap border-b border-solid border-b-[#e7edf3] dark:border-b-[#1e293b] bg-white dark:bg-[#101922] px-6 py-3 shadow-sm lg:px-10">
<div class="flex items-center gap-4">
<div class="size-8 text-primary">
<!-- Custom Logo SVG -->
<svg class="h-full w-full" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_6_330)">
<path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
</g>
<defs>
<clippath id="clip0_6_330"><rect fill="white" height="48" width="48"></rect></clippath>
</defs>
</svg>
</div>
<h2 class="text-[#0d141b] dark:text-white text-xl font-bold leading-tight tracking-[-0.015em]">Sushrusha</h2>
</div>
<!-- Desktop Nav Links -->
<div class="hidden lg:flex flex-1 justify-center gap-8">
<a class="text-[#4c739a] hover:text-primary dark:text-slate-400 dark:hover:text-primary text-sm font-medium leading-normal transition-colors" href="dashboard.php">Dashboard</a>
<a class="text-[#4c739a] hover:text-primary dark:text-slate-400 dark:hover:text-primary text-sm font-medium leading-normal transition-colors" href="#">Medicines</a>
<a class="text-[#4c739a] hover:text-primary dark:text-slate-400 dark:hover:text-primary text-sm font-medium leading-normal transition-colors" href="#">Reminders</a>
<a class="text-primary dark:text-primary text-sm font-medium leading-normal" href="profile.php">Settings</a>
</div>
<!-- Language Selector -->
<div class="hidden lg:flex items-center gap-2 mr-4">
  <button id="langBtn" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
    🌐
  </button>
  <div id="langMenu" class="hidden absolute right-4 top-full mt-2 w-40 bg-white dark:bg-[#101922] border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg z-50">
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors" data-lang="en">English</button>
    <button class="lang-option block w-full text-left px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 transition-colors" data-lang="ml">Malayalam</button>
  </div>
</div>
<!-- Profile Dropdown Trigger -->
<div class="flex items-center gap-4">
<div class="hidden sm:flex flex-col items-end">
<span class="text-sm font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($name ?? 'Patient') ?></span>
<span class="text-xs text-slate-500 dark:text-slate-400">ID: <?= $user_id ?></span>
</div>
<?php 
$navbarPhoto = !empty($data['profile_photo']) ? '../' . htmlspecialchars($data['profile_photo']) : 'https://via.placeholder.com/150';
$userInitials = !empty($name) ? strtoupper(substr($name, 0, 1)) : 'U';
?>
<?php if (!empty($data['profile_photo'])): ?>
<img src="<?= $navbarPhoto ?>" 
     alt="Profile picture" 
     class="rounded-full size-10 border-2 border-white shadow-sm cursor-pointer object-cover">
<?php else: ?>
<div class="rounded-full size-10 border-2 border-white shadow-sm cursor-pointer bg-primary/10 flex items-center justify-center">
  <span class="text-primary font-semibold text-sm"><?= htmlspecialchars($userInitials) ?></span>
</div>
<?php endif; ?>
</div>
</header>
<div class="layout-container flex flex-col md:flex-row max-w-[1440px] mx-auto w-full">
<!-- SideNavBar (Settings Navigation) -->
<aside class="w-full md:w-72 lg:w-80 flex-shrink-0 bg-white dark:bg-[#101922] border-r border-[#e7edf3] dark:border-[#1e293b] md:sticky md:top-[65px] md:h-[calc(100vh-65px)] md:overflow-y-auto no-scrollbar md:z-10">
<div class="p-6 flex flex-col gap-8">
<!-- Mobile only Title -->
<div class="md:hidden pb-4 border-b border-slate-100 dark:border-slate-800">
<h1 class="text-2xl font-bold text-slate-900 dark:text-white">Settings</h1>
</div>
<div class="flex flex-col gap-2">
<p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 px-3">Account</p>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary transition-all group" href="#general">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">person</span>
<span class="text-sm font-medium leading-normal" data-i18n="general_profile">General Profile</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-all group" href="#medical-details">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">local_hospital</span>
<span class="text-sm font-medium leading-normal" data-i18n="medical_details">Medical Details</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-all group" href="#security">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">shield</span>
<span class="text-sm font-medium leading-normal" data-i18n="security_login">Security &amp; Login</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-all group" href="#notifications">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">notifications</span>
<span class="text-sm font-medium leading-normal" data-i18n="notifications">Notifications</span>
</a>
</div>
<div class="flex flex-col gap-2">
<p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 px-3">Device Management</p>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-all group" href="#devices">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">devices</span>
<span class="text-sm font-medium leading-normal" data-i18n="my_devices">My Devices</span>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-all group" href="#">
<span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">ecg_heart</span>
<span class="text-sm font-medium leading-normal" data-i18n="care_team_access">Care Team Access</span>
</a>
</div>
</div>
</aside>
<!-- Main Content Area -->
<main class="flex-1 px-4 py-8 md:px-10 lg:px-16 md:py-10 max-w-5xl">
<!-- Page Header -->
<div class="flex flex-col gap-2 mb-10">
<h1 class="text-[#0d141b] dark:text-white tracking-tight text-[32px] font-bold leading-tight" data-i18n="general_profile">General Profile</h1>
<p class="text-[#4c739a] dark:text-slate-400 text-base font-normal leading-normal" data-i18n="manage_personal_info">Manage your personal information, medical ID, and emergency contacts.</p>
</div>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
<div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
    <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
</div>
<?php endif; ?>

<!-- Section: Personal Info -->
<form action="update_profile.php" method="POST" enctype="multipart/form-data">

<section class="mb-12 bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden" id="general">

<!-- HEADER -->
<div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4">

  <div class="flex items-center gap-5">
   <div class="relative">
<img id="profilePreview"
 src="<?= !empty($data['profile_photo']) 
        ? '../' . htmlspecialchars($data['profile_photo']) 
        : 'https://via.placeholder.com/150' ?>"
 class="h-20 w-20 rounded-full object-cover ring-4 ring-slate-50 dark:ring-slate-800">

</div>


    <div class="flex flex-col">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white">
        <?= htmlspecialchars($name?? 'Patient') ?>
      </h3>
      <p class="text-sm text-slate-500 dark:text-slate-400">
        Patient ID: <?= $user_id ?>
      </p>
    </div>
  </div>
<input type="file" id="profilePhotoInput" name="profile_photo" accept="image/*" hidden>


  <div class="flex gap-3">
    <?php if (!empty($data['profile_photo'])): ?>
    <a href="remove_profile_photo.php" 
       onclick="return confirm('Are you sure you want to remove your profile photo?');"
       class="px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
      <span data-i18n="remove">Remove</span>
    </a>
    <?php endif; ?>
   <button type="button"
 onclick="document.getElementById('profilePhotoInput').click()"
 class="px-4 py-2 rounded-lg bg-primary text-white text-sm hover:bg-blue-600 transition-colors">
 <span data-i18n="change_photo">Change Photo</span>
</button>



  </div>
</div>

<!-- FORM BODY -->
<div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6">

<!-- Full Name (read-only for now) -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium" data-i18n="full_name">Full Name</span>
  <input type="text" value="<?= htmlspecialchars($data['name'] ?? '') ?>"
         disabled
         class="w-full rounded-lg bg-slate-100 dark:bg-slate-800 px-4 py-2.5">
</label>

<!-- DOB -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium" data-i18n="date_of_birth">Date of Birth</span>
  <input type="date" name="dob"
         value="<?= $data['dob'] ?? '' ?>"
         required
         class="w-full rounded-lg px-4 py-2.5">
</label>

<!-- Emergency Contact -->
<div class="col-span-1 md:col-span-2 pt-4 border-t border-slate-100 dark:border-slate-800">

  <h4 class="text-base font-semibold flex items-center gap-2 mb-4">
    <span class="material-symbols-outlined text-red-500">emergency</span>
    Emergency Contact
  </h4>

  <label class="flex flex-col gap-2">
    <span class="text-sm font-medium" data-i18n="contact_name_phone">Contact Name & Phone</span>
    <input type="text"
           name="emergency_contact"
           value="<?= htmlspecialchars($data['emergency_contact'] ?? '') ?>"
           placeholder="Eg: Michael Jenkins - 9876543210"
           required
           class="w-full rounded-lg px-4 py-2.5">
  </label>

</div>
</div>

<!-- FOOTER -->
<div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t flex justify-end gap-3">
  <button type="reset"
          class="px-5 py-2.5 rounded-lg border">
    <span data-i18n="cancel">Cancel</span>
  </button>

  <button type="submit"
          class="px-5 py-2.5 rounded-lg bg-primary text-white">
    <span data-i18n="save_changes">Save Changes</span>
  </button>
</div>

</section>
</form>

<!-- Section: Medical Details -->
<section class="mb-12 bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden" id="medical-details">
<div class="border-b border-slate-100 dark:border-slate-800 px-6 py-4">
<h2 class="text-xl font-bold text-slate-900 dark:text-white" data-i18n="medical_details">Medical Details</h2>
<p class="text-sm text-slate-500 dark:text-slate-400 mt-1" data-i18n="update_medical_info">Update your basic medical information.</p>
</div>

<form action="update_profile.php" method="POST" enctype="multipart/form-data">
<input type="hidden" name="dob" value="<?= htmlspecialchars($data['dob'] ?? '') ?>">
<input type="hidden" name="emergency_contact" value="<?= htmlspecialchars($data['emergency_contact'] ?? '') ?>">

<div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6">

<!-- Gender -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium" data-i18n="gender">Gender</span>
  <select name="gender" class="w-full rounded-lg px-4 py-2.5 border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary" data-i18n-options>
    <option value="" data-i18n="select_gender">Select Gender</option>
    <option value="Male" <?= ($data['gender'] ?? '') === 'Male' ? 'selected' : '' ?> data-i18n="male">Male</option>
    <option value="Female" <?= ($data['gender'] ?? '') === 'Female' ? 'selected' : '' ?> data-i18n="female">Female</option>
    <option value="Other" <?= ($data['gender'] ?? '') === 'Other' ? 'selected' : '' ?> data-i18n="other">Other</option>
  </select>
</label>

<!-- Blood Group -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium">Blood Group</span>
  <select name="blood_group" class="w-full rounded-lg px-4 py-2.5 border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary">
    <option value="">Select Blood Group</option>
    <option value="A+" <?= ($data['blood_group'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
    <option value="A-" <?= ($data['blood_group'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
    <option value="B+" <?= ($data['blood_group'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
    <option value="B-" <?= ($data['blood_group'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
    <option value="AB+" <?= ($data['blood_group'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
    <option value="AB-" <?= ($data['blood_group'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
    <option value="O+" <?= ($data['blood_group'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
    <option value="O-" <?= ($data['blood_group'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
  </select>
</label>

<!-- Height -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium">Height (cm)</span>
  <input type="number" 
         name="height_cm" 
         value="<?= htmlspecialchars($data['height_cm'] ?? '') ?>"
         placeholder="e.g., 170"
         min="50"
         max="250"
         class="w-full rounded-lg px-4 py-2.5 border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary">
</label>

<!-- Weight -->
<label class="flex flex-col gap-2">
  <span class="text-sm font-medium">Weight (kg)</span>
  <input type="number" 
         name="weight_kg" 
         value="<?= htmlspecialchars($data['weight_kg'] ?? '') ?>"
         placeholder="e.g., 70"
         min="10"
         max="300"
         step="0.1"
         class="w-full rounded-lg px-4 py-2.5 border border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary">
</label>

</div>

<!-- FOOTER -->
<div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t flex justify-end gap-3">
  <button type="reset"
          class="px-5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
    Cancel
  </button>

  <button type="submit"
          class="px-5 py-2.5 rounded-lg bg-primary text-white hover:bg-blue-600 transition-colors">
    <span data-i18n="save_medical_details">Save Medical Details</span>
  </button>
</div>
</form>
</section>

<!-- Section: Security -->
<section class="mb-12" id="security">
<div class="flex items-center justify-between mb-6">
<div>
<h2 class="text-xl font-bold text-slate-900 dark:text-white" data-i18n="security_login">Security &amp; Login</h2>
<p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="update_password_secure">Update your password and secure your account.</p>
</div>
</div>
<div class="bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 md:p-8">
<form action="update_password.php" method="POST" id="passwordForm">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<label class="flex flex-col gap-2">
<span class="text-sm font-medium text-slate-700 dark:text-slate-300" data-i18n="new_password">New Password</span>
<div class="relative">
<input id="newPassword" name="new_password" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary px-4 py-2.5 text-base pr-10" placeholder="Min 8 characters" type="password" required minlength="8" autocomplete="new-password"/>
<span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 cursor-pointer hover:text-slate-600 toggle-password" data-target="newPassword">visibility_off</span>
</div>
</label>
<label class="flex flex-col gap-2">
<span class="text-sm font-medium text-slate-700 dark:text-slate-300" data-i18n="confirm_password">Confirm Password</span>
<div class="relative">
<input id="confirmPassword" name="confirm_password" class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-primary focus:border-primary px-4 py-2.5 text-base pr-10" type="password" required minlength="8" autocomplete="new-password"/>
<span class="material-symbols-outlined absolute right-3 top-2.5 text-slate-400 cursor-pointer hover:text-slate-600 toggle-password" data-target="confirmPassword">visibility_off</span>
</div>
</label>
</div>
<div id="passwordError" class="mt-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg hidden"></div>
<div class="mt-6 flex justify-end">
<button type="submit" class="px-5 py-2.5 rounded-lg bg-primary text-white font-medium hover:bg-blue-600 dark:hover:bg-blue-700 transition-colors"><span data-i18n="update_password">Update Password</span></button>
</div>
</form>
</div>
</section>
<!-- Section: Notifications -->
<section class="mb-12" id="notifications">
<div class="flex items-center justify-between mb-6">
<div>
<h2 class="text-xl font-bold text-slate-900 dark:text-white" data-i18n="notifications">Notifications</h2>
<p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="choose_alerts">Choose how you want to be alerted for medications.</p>
</div>
</div>
<div class="bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
<!-- Item 1 -->
<div class="p-5 flex items-center justify-between">
<div class="flex gap-4">
<div class="size-10 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-primary">
<span class="material-symbols-outlined">medication</span>
</div>
<div class="flex flex-col">
<span class="text-base font-semibold text-slate-900 dark:text-white">Medication Reminders</span>
<span class="text-sm text-slate-500 dark:text-slate-400">Daily alerts when it's time to take your pills</span>
</div>
</div>
<!-- Toggle -->
<div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
<input checked="" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-slate-300 appearance-none cursor-pointer transition-all duration-300" id="toggle1" name="toggle" type="checkbox"/>
<label class="toggle-label block overflow-hidden h-6 rounded-full bg-slate-300 cursor-pointer transition-colors duration-300" for="toggle1"></label>
</div>
</div>
<!-- Item 2 -->
<div class="p-5 flex items-center justify-between">
<div class="flex gap-4">
<div class="size-10 rounded-full bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-500">
<span class="material-symbols-outlined">inventory_2</span>
</div>
<div class="flex flex-col">
<span class="text-base font-semibold text-slate-900 dark:text-white">Refill Alerts</span>
<span class="text-sm text-slate-500 dark:text-slate-400">Get notified when inventory is low (below 10%)</span>
</div>
</div>
<div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
<input checked="" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-slate-300 appearance-none cursor-pointer transition-all duration-300" id="toggle2" name="toggle" type="checkbox"/>
<label class="toggle-label block overflow-hidden h-6 rounded-full bg-slate-300 cursor-pointer transition-colors duration-300" for="toggle2"></label>
</div>
</div>
<!-- Item 3 -->
<div class="p-5 flex items-center justify-between">
<div class="flex gap-4">
<div class="size-10 rounded-full bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-500">
<span class="material-symbols-outlined">mail</span>
</div>
<div class="flex flex-col">
<span class="text-base font-semibold text-slate-900 dark:text-white">Email Digest</span>
<span class="text-sm text-slate-500 dark:text-slate-400">Weekly adherence report sent to sarah.j@sushrusha.com</span>
</div>
</div>
<div class="relative inline-block w-12 mr-2 align-middle select-none transition duration-200 ease-in">
<input class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 border-slate-300 appearance-none cursor-pointer transition-all duration-300" id="toggle3" name="toggle" type="checkbox"/>
<label class="toggle-label block overflow-hidden h-6 rounded-full bg-slate-300 cursor-pointer transition-colors duration-300" for="toggle3"></label>
</div>
</div>
</div>
</section>
<!-- Section: Devices -->
<section id="devices">
<div class="flex items-center justify-between mb-6">
<div>
<h2 class="text-xl font-bold text-slate-900 dark:text-white">Connected Devices</h2>
<p class="text-sm text-slate-500 dark:text-slate-400">Manage your smart dispensers and wearables.</p>
</div>
<button class="flex items-center gap-2 text-primary font-semibold hover:underline">
<span class="material-symbols-outlined text-[20px]">add_circle</span>
                            Add Device
                        </button>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<!-- Device Card 1 -->
<div class="bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div class="flex gap-4">
<div class="bg-center bg-no-repeat bg-cover rounded-lg size-14 bg-slate-100 dark:bg-slate-800" data-alt="Image of a smart pill dispenser device" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuDFY3GXKLOlOJK_9zIMiAqzmdCt00JXZhA24rVuYf-D5xNGzvVGd011i3c1T0qKw-EYXp68Zvr2xtbpSUCV2Z7sgqe3pLYenfOY_I0WTQF6LcWsygE1QPnqoC7VSGlm_zXglkd5dHqSXZSzVZIvFFIhZBCewwKUMSEi7ABdq23DG7LiGYcdgHdYH_IIMDtsA9ZeLI65z6b5pscSVOegQTGY_4_TDBcxHqBkce2YJo3ssv2-Q-hZpHyDvI60sA_puDTjnEZh-giP448");'>
</div>
<div class="flex flex-col">
<h3 class="text-base font-bold text-slate-900 dark:text-white">Smart Dispenser Pro</h3>
<p class="text-xs text-slate-500 dark:text-slate-400">Serial: SD-4492-X</p>
<div class="flex items-center gap-1.5 mt-1">
<span class="size-2 rounded-full bg-green-500"></span>
<span class="text-xs font-medium text-green-600 dark:text-green-400">Online</span>
</div>
</div>
</div>
<button class="text-slate-400 hover:text-red-500 transition-colors">
<span class="material-symbols-outlined">delete</span>
</button>
</div>
<div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
<div class="flex flex-col">
<span class="text-xs text-slate-400">Last Synced</span>
<span class="text-sm font-medium text-slate-700 dark:text-slate-300">Just now</span>
</div>
<button class="text-sm font-medium text-primary hover:text-blue-700">Configure</button>
</div>
</div>
<!-- Device Card 2 -->
<div class="bg-white dark:bg-[#101922] rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5 flex flex-col gap-4">
<div class="flex justify-between items-start">
<div class="flex gap-4">
<div class="bg-center bg-no-repeat bg-cover rounded-lg size-14 bg-slate-100 dark:bg-slate-800" data-alt="Image of a smart watch health tracker" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuC8qCjfF00WoTEykRfUBLd1pjmDsBDn_Qp6ID1-6wgHk2qAFlIyclPbEuBRho7_puTY_b0VxvaJHyUmpEpPFMQ0Q192j3JSdgJpA1iBSLvv_YIn0l15E2tVRZI9mBSufvdgkpxqeOJgjrDNmeUG_XAcfMtUlVY88yoOtdTA6KoEP4sJPway6dnoNykcToRF7C4fcawhGkyi9Z55Hc9CaaCoOGz5B1kxaLcBdyV4WZYQKAF10avjKXoK6YMlr7w6iO35klqEMYd6mio");'>
</div>
<div class="flex flex-col">
<h3 class="text-base font-bold text-slate-900 dark:text-white">MediWatch Series 4</h3>
<p class="text-xs text-slate-500 dark:text-slate-400">Serial: MW-9921-Z</p>
<div class="flex items-center gap-1.5 mt-1">
<span class="size-2 rounded-full bg-slate-400"></span>
<span class="text-xs font-medium text-slate-500 dark:text-slate-400">Last active 2d ago</span>
</div>
</div>
</div>
<button class="text-slate-400 hover:text-red-500 transition-colors">
<span class="material-symbols-outlined">delete</span>
</button>
</div>
<div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
<div class="flex flex-col">
<span class="text-xs text-slate-400">Battery</span>
<span class="text-sm font-medium text-slate-700 dark:text-slate-300">12% (Low)</span>
</div>
<button class="text-sm font-medium text-primary hover:text-blue-700">Configure</button>
</div>
</div>
</div>
</section>
<footer class="mt-20 py-6 border-t border-slate-200 dark:border-slate-800 text-center">
<p class="text-sm text-slate-400 dark:text-slate-600">© 2024 Sushrusha Medical Systems. All rights reserved.</p>
</footer>
</main>
</div>
</div>

<script>
//profile photo
document.getElementById('profilePhotoInput').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
   
    const reader = new FileReader();
    reader.onload = () => {
        const preview = document.getElementById('profilePreview');
        preview.src = reader.result;
        
        // Update navbar profile picture if it exists
        const navbarPhoto = document.querySelector('header img[alt="Profile picture"]');
        if (navbarPhoto) {
            navbarPhoto.src = reader.result;
        } else {
            // If navbar has a placeholder div, convert it to img
            const navbarPlaceholder = document.querySelector('header .rounded-full.size-10.border-2');
            if (navbarPlaceholder && navbarPlaceholder.tagName === 'DIV') {
                const newImg = document.createElement('img');
                newImg.src = reader.result;
                newImg.alt = 'Profile picture';
                newImg.className = 'rounded-full size-10 border-2 border-white shadow-sm cursor-pointer object-cover';
                navbarPlaceholder.parentNode.replaceChild(newImg, navbarPlaceholder);
            }
        }
    };
    reader.readAsDataURL(file);
});

// Password visibility toggle
document.querySelectorAll('.toggle-password').forEach(toggle => {
    toggle.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this;
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    });
});

// Password form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const errorDiv = document.getElementById('passwordError');
    
    // Clear previous errors
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';
    
    // Check if passwords match
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        errorDiv.textContent = 'New password and confirm password do not match.';
        errorDiv.classList.remove('hidden');
        return false;
    }
    
    // Check password length
    if (newPassword.length < 8) {
        e.preventDefault();
        errorDiv.textContent = 'Password must be at least 8 characters long.';
        errorDiv.classList.remove('hidden');
        return false;
    }
    
    return true;
});

// Language Translation System
const langBtn = document.getElementById("langBtn");
const langMenu = document.getElementById("langMenu");
const langOptions = document.querySelectorAll(".lang-option");

if (langBtn && langMenu) {
    langBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        langMenu.classList.toggle("hidden");
    });

    document.addEventListener("click", (e) => {
        if (!langMenu.contains(e.target) && !langBtn.contains(e.target)) {
            langMenu.classList.add("hidden");
        }
    });

    langOptions.forEach(btn => {
        btn.addEventListener("click", async () => {
            langMenu.classList.add("hidden");
            await translationSystem.translatePage(btn.dataset.lang);
        });
    });

    // Initialize translation on page load
    document.addEventListener("DOMContentLoaded", () => {
        translationSystem.init();
    });
}
</script>
</body></html>