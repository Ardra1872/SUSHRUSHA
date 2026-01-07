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
    /* ===== CARETAKER DASHBOARD STYLING ===== */

    /* Reset & base */
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f4f8fc;
        color: #1f2d3d;
    }

  /* Layout */
.app-container {
    display: flex;
    background: #eaf6ff;
    min-height: 100vh;
}

.sidebar {
    width: 260px;
    background: #eef9ff;
    padding: 30px 20px;
    margin: 20px;
    border-radius: 20px;
}

.logo {
    color: #2f6bff;
    margin-bottom: 4px;
}

.subtitle {
    font-size: 13px;
    color: #6b7a8a;
    margin-bottom: 30px;
}

.menu {
    list-style: none;
    padding: 0;
}

.menu li {
    padding: 12px 14px;
    border-radius: 10px;
    margin-bottom: 8px;
    cursor: pointer;
    color: #1f2d3d;
}

.menu li.active,
.menu li:hover {
    background: #dff1ff;
    color: #2f6bff;
}

.logout {
    display: block;
    margin-top: 40px;
    color: #2f6bff;
    text-decoration: none;
}

/* Main */
.main-content {
    flex: 1;
    padding: 30px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.top-bar input {
    padding: 10px 16px;
    border-radius: 20px;
    border: none;
    width: 300px;
    background: #f4f8fc;
}

.profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    background: #2f6bff;
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Cards */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.card {
    background: #f0fbff;
    padding: 24px;
    border-radius: 18px;
}

.card.wide {
    grid-column: span 2;
}

.emergency {
    border: 1px solid #5a7bff;
    background: #f6f9ff;
}

.danger-btn {
    background: #ff6b6b;
    border: none;
    padding: 12px 22px;
    color: white;
    border-radius: 20px;
    cursor: pointer;
}


   /* ===== FIRST LOGIN MODAL ===== */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(31, 45, 61, 0.45);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
}

.modal-content {
    background: #ffffff;
    width: 100%;
    max-width: 420px;
    padding: 32px;
    border-radius: 14px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    text-align: center;
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-content h2 {
    font-size: 22px;
    margin-bottom: 8px;
    color: #1f2d3d;
}

.modal-content p {
    font-size: 14px;
    color: #6b7a8a;
    margin-bottom: 22px;
}

.modal-content input[type="password"] {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #dbe7f0;
    font-size: 14px;
    margin-bottom: 16px;
}

.modal-content input:focus {
    outline: none;
    border-color: #3498db;
}

.modal-content button {
    width: 100%;
    padding: 12px;
    background: #3498db;
    color: #ffffff;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
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
#assignedPatientMedicines table th {
    text-align: left;
    padding: 10px;
    color: #2f6bff;
    font-size: 14px;
}

#assignedPatientMedicines table td {
    padding: 10px;
    border-top: 1px solid #e3eef7;
    font-size: 13px;
}

    </style>
</head>
<body>
<div class="app-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <h2 class="logo">SUSHRUSHA</h2>
        <p class="subtitle">Caretaker Dashboard</p>

        <ul class="menu">
            <li class="active">👩‍⚕️ Assigned Patient</li>
            <li>💊 Medicine Status</li>
            <li>🔔 Alerts</li>
            <li>📊 Reports</li>
            <li>🚨 Emergency Access</li>
            <li>⚙️ Profile</li>
        </ul>

        <a class="logout" href="../../public/landing.html">← Logout</a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <div class="top-bar">
            <input type="text" placeholder="Search patient..." />

            <div class="profile">
                <div class="profile-text">
                    <strong><?php echo $_SESSION['user_name']; ?></strong><br>
                    <small>Caretaker</small>
                </div>
                <div class="avatar">
                    <?php echo strtoupper($_SESSION['user_name'][0]); ?>
                </div>
            </div>
        </div>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">

            <!-- WIDE CARD -->
           <div class="card wide" id="assignedPatientMedicines">
    <h3>Assigned Patient – Medicines</h3>
    <div id="medicineList">Loading medicines…</div>
</div>


            <!-- CARD -->
            <div class="card">
                <h3>Patient Adherence</h3>
                <p>✔ 4 doses taken<br>⚠ 1 missed</p>
            </div>

            <!-- CARD -->
            <div class="card">
                <h3>Availability Status</h3>
                <p><strong>Available</strong></p>
            </div>

            <!-- CARD -->
            <div class="card">
                <h3>Alerts</h3>
                <p>⚠ Missed dose<br>🔔 Reminder sent</p>
            </div>

            <!-- EMERGENCY -->
            <div class="card emergency">
                <h3>Emergency Quick Access</h3>
                <button class="danger-btn">Call Emergency</button>
            </div>

        </div>
    </main>
</div>

<?php if ($_SESSION['first_login'] ?? 0 == 1): ?>
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

<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {

  fetch('fetch_patient_medicine.php')
    .then(res => res.json())
    .then(data => {

      const container = document.getElementById('medicineList');

      if (!container) {
        console.error("medicineList container not found");
        return;
      }

      if (data.status === 'empty') {
        container.innerHTML = "<p>No patient assigned yet.</p>";
        return;
      }

      if (data.status !== 'success' || data.medicines.length === 0) {
        container.innerHTML = "<p>No medicines added for this patient.</p>";
        return;
      }

      let html = `
        <table style="width:100%; border-collapse: collapse;">
          <tr>
            <th>Medicine</th>
            <th>Dosage</th>
            <th>Time</th>
            <th>Compartment</th>
          </tr>`;

      data.medicines.forEach(m => {
        html += `
          <tr>
            <td>${m.name}</td>
            <td>${m.dosage || '-'}</td>
            <td>${m.intake_time}</td>
            <td>${m.compartment_number}</td>
          </tr>`;
      });

      html += "</table>";
      container.innerHTML = html;
    })
    .catch(err => {
      console.error(err);
      document.getElementById('medicineList').innerHTML =
        "<p>Error loading medicines.</p>";
    });

});
</script>


</body>
</html>
