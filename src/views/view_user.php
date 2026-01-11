<?php
session_start();
include '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Get user ID from query string
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($userId <= 0) {
    die("Invalid user ID.");
}

// Fetch user details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.role, u.emergency_contact, p.dob, p.profile_photo
    FROM users u
    LEFT JOIN patient_profile p ON u.id = p.patient_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Prepare profile photo
if ($user['profile_photo']) {
    // Assuming profile_photo is stored as BLOB in DB
    $profilePhoto = 'data:image/jpeg;base64,' . base64_encode($user['profile_photo']);
} else {
    // Default image
    $profilePhoto = "https://via.placeholder.com/150";
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View User - <?php echo htmlspecialchars($user['name']); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-50 font-sans">
<div class="max-w-3xl mx-auto mt-10 p-6 bg-white dark:bg-slate-800 rounded-xl shadow-md border border-slate-200 dark:border-slate-700">

    <div class="flex items-center gap-6 mb-6">
        <img src="<?php echo $profilePhoto; ?>" alt="Profile Photo" class="w-24 h-24 rounded-full object-cover border border-slate-200 dark:border-slate-700">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Role: <?php echo htmlspecialchars($user['role']); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Email</h3>
            <p class="text-base text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Emergency Contact</h3>
            <p class="text-base text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['emergency_contact'] ?: 'N/A'); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">Date of Birth</h3>
            <p class="text-base text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['dob'] ?: 'N/A'); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400">User ID</h3>
            <p class="text-base text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['id']); ?></p>
        </div>
    </div>

    <div class="mt-6 flex gap-4">
        <a href="admin_dashboard.html" class="px-4 py-2 bg-primary text-white rounded hover:bg-blue-600">Back to Users</a>
        <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete User</button>
    </div>

</div>

<script>
async function deleteUser(id){
    if(!confirm("Are you sure you want to delete this user?")) return;

    try {
        const res = await fetch("admin.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: `action=delete_user&id=${id}`
        });
        const data = await res.json();
        alert(data.message || "User deleted");
        window.location.href = "admin_dashboard.html";
    } catch(err) {
        console.error("Error deleting user:", err);
        alert("Failed to delete user");
    }
}
</script>

</body>
</html>
