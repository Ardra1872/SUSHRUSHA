<?php
session_start();
include '../config/db.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caretaker') {
    header("Location: ../../public/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_login = $_SESSION['first_login'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Caretaker Dashboard</title>
    <link rel="stylesheet" href="assets/dashboard.css">
    <style>
    /* Simple modal styling */
    .modal {display:none; position:fixed; top:0; left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;}
    .modal-content {background:#fff;padding:20px;border-radius:8px;max-width:400px;width:90%;text-align:center;}


    /* ===== CARETAKER DASHBOARD STYLING ===== */

/* Reset & base */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f8fc;
    color: #1f2d3d;
}

/* Wrapper */
.dashboard-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.dashboard-header h1 {
    font-size: 28px;
    font-weight: 600;
}

.dashboard-header .logout-btn {
    padding: 8px 16px;
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.dashboard-header .logout-btn:hover {
    background: #2575b8;
}

/* Cards section */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

.card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(50, 70, 90, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(50, 70, 90, 0.15);
}

.card h3 {
    margin-top: 0;
    font-size: 20px;
    color: #1f2d3d;
}

.card p {
    font-size: 14px;
    color: #6b7a8a;
}

/* Buttons */
.btn-primary {
    display: inline-block;
    padding: 10px 20px;
    background: #3498db;
    color: #fff;
    font-weight: 600;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s ease;
}

.btn-primary:hover {
    background: #2575b8;
}

/* Modal for first login */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    text-align: center;
}

.modal-content h2 {
    margin: 0 0 12px;
    font-size: 22px;
}

.modal-content p {
    font-size: 14px;
    color: #6b7a8a;
    margin-bottom: 20px;
}

.modal-content input[type="password"] {
    padding: 10px;
    width: 100%;
    margin-bottom: 12px;
    border: 1px solid #dbe7f0;
    border-radius: 6px;
}

.modal-content button {
    padding: 10px 20px;
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.modal-content button:hover {
    background: #2575b8;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
        <button class="logout-btn" onclick="window.location.href='../../public/landing.html'">Logout</button>
    </div>

    <div class="dashboard-cards">
        <div class="card">
            <h3>Assigned Patient</h3>
            <p>Details about your patient here.</p>
        </div>
        <div class="card">
            <h3>Medicine Reminders</h3>
            <p>Upcoming medicine alerts for your patient.</p>
        </div>
        <div class="card">
            <h3>Notifications</h3>
            <p>Important notifications will appear here.</p>
        </div>
    </div>
</div>


<?php if ($first_login == 1): ?>
<div id="firstLoginModal" class="modal">
    <div class="modal-content">
        <h2>Change Your Password</h2>
        <p>This is your first login. For security, please update your password.</p>
        <form method="POST" action="update_password.php">
            <input type="password" name="password" placeholder="New password" required>
            <button type="submit">Update Password</button>
        </form>
    </div>
</div>
<script>
document.getElementById('firstLoginModal').style.display = 'flex';
</script>
<?php endif; ?>

</body>
</html>
