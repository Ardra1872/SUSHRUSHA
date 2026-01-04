<!DOCTYPE html>
<html lang="en">
<head>
<?php

session_start();

include '../config/db.php'; 

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_name']) || !isset($_SESSION['user_id'])) {

    header('Location: ../../public/login.php');
    exit();
}

// Logged-in user info

$user_id = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'];
$userRole = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'User';

/* ------------------------------
   Patient Profile
------------------------------ */
$profile = [];
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT u.name, u.contact_number, u.emergency_contact,
               p.dob, p.gender, p.blood_group, p.height_cm, p.weight_kg
        FROM users u
        LEFT JOIN patient_profile p ON u.id = p.patient_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
}

/* ------------------------------
   Caretaker Info
------------------------------ */
$caretaker_exists = false;
$caretaker = null;

if ($user_id) {
    // Fetch caretaker details including email
    $stmt2 = $conn->prepare("
        SELECT u.id, u.name, u.email, c.relation
        FROM caregivers c
        JOIN users u ON c.caregiver_id = u.id
        WHERE c.patient_id = ?
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $caretaker_res = $stmt2->get_result();

    if ($caretaker_res && $caretaker_res->num_rows > 0) {
        $caretaker_exists = true;
        $caretaker = $caretaker_res->fetch_assoc(); // assigned caretaker
    }
}




?>

<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SUSHRUSHA – Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel ="stylesheet" href="../../public/assets/dashboard_styles.css">

</head>

<body>
<div class="app">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <h2>SUSHRUSHA</h2>
      <span>Smart Medicine Reminder</span>
    </div>

  <nav class="menu">
  <a class="nav-item active" data-target="schedule">📅 Medicine Schedule</a>
  <a class="nav-item" data-target="reports">📄 Reports</a>
  <a class="nav-item" data-target="settings">🔔 Reminder Settings</a>
  <a class="nav-item" data-target="emergency">📞 Emergency Contacts</a>
  <a class="nav-item" data-target="profile">👤 Patient Profile</a>
</nav>

    <div class="logout">
      <a class="menu" href="#" onclick="logout()">← Logout</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <!-- Topbar -->
    <div class="topbar">
      <input class="search" placeholder="Search..." />
      <div class="profile">
        <div class="time-section">
          <div class="time" id="current-time">12:00 PM</div>
          <div class="name" id="profile-name"><?= htmlspecialchars($userName) ?> | <?= htmlspecialchars($userRole) ?></div>
        </div>
        <div class="avatar" id="avatar-circle"><?= strtoupper(substr($userName,0,1)) ?></div>
      </div>
    </div>

    
    <section id="schedule" class="page active"> <section class="cards"> <div class="card"> <h3>Today's Reminder</h3> <div class="big">27</div> <p>3 medicines · Next in 15 minutes</p> </div> <div class="alerts"> <h3>Medicine Alerts</h3> <div class="alert">🔔 2 missed doses</div> <div class="alert">⚠️ Stock running low</div> </div> </section>
    
    <section class="grid"> <div class="card"> <h3>Medicine Intake</h3> <p>3 medicines today</p> </div> <div class="card"> <h3>Caregiver Status</h3> <p>Available</p> </div> <div class="card emergency"> <h3>Emergency Quick Access</h3> <button>Call Emergency</button> </div> </section> </section>
    <section id="reports" class="page"> <div class="card"> <h3>Reports</h3> <p>Medicine history & analytics will appear here.</p> </div> </section>
    <section id="settings" class="page"> <div class="card"> <h3>Reminder Settings</h3> <p>Manage medicine timing, alerts and frequency.</p> </div> </section>
    <section id="emergency" class="page"> <div class="card emergency"> <h3>Emergency Contacts</h3> <p>Add & manage emergency numbers.</p> <button>Call Emergency</button> </div> </section>
    
    <!-- Profile Section -->
    
    <section id="profile" class="page">
      <div class="profile-grid">

        <!-- BASIC PROFILE -->
        <div class="card">
          <h3>Patient Profile</h3>
          <div class="profile-header">
            <div class="profile-avatar">
              <?= strtoupper(substr($profile['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="profile-info">
              <h4><?= $profile['name'] ?? 'Not Updated' ?></h4>
              <p><?= $profile['gender'] ?? '—' ?> | Blood: <?= $profile['blood_group'] ?? '—' ?></p>
              <p>📞 <?= $profile['contact_number'] ?? '—' ?></p>
            </div>
          </div>
          <button class="edit-btn" onclick="openProfileForm()">Edit Profile</button>

          <!-- Edit Profile Form (hidden) -->
          <div id="profile-form" style="display:none;margin-top:20px;">
            <div class="card">
              <h3>Edit Patient Profile</h3>
              <form method="POST" action="update_profile.php" class="profile-grid">
                <input type="hidden" name="patient_id" value="<?= $user_id ?>">
                <div class="form-group"><label>Date of Birth</label><input type="date" name="dob"></div>
                <div class="form-group"><label>Gender</label>
                  <select name="gender" required>
                    <option value="">Select</option>
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                  </select>
                </div>
                <div class="form-group"><label>Blood Group</label><input type="text" name="blood_group" placeholder="O+, A-" required></div>
                <div class="form-group"><label>Height (cm)</label><input type="number" name="height_cm"></div>
                <div class="form-group"><label>Weight (kg)</label><input type="number" name="weight_kg"></div>
                <div class="form-group full"><label>Emergency Contact</label><input type="text" name="emergency_contact"></div>
                <button type="submit" class="edit-btn full">Save Profile</button>
              </form>
            </div>
          </div>
        </div>

        <!-- MEDICAL & PERSONAL INFO -->
        <div class="card profile-list">
          <h3>Personal & Medical Info</h3>
          <p><b>Date of Birth:</b> <?= $profile['dob'] ?? '—' ?></p>
          <p><b>Height:</b> <?= $profile['height_cm'] ?? '—' ?> cm</p>
          <p><b>Weight:</b> <?= $profile['weight_kg'] ?? '—' ?> kg</p>
          <p><b>Emergency Contact:</b> <?= $profile['emergency_contact'] ?? '—' ?></p>
        </div>

        
        <!-- ASSIGN CARETAKER -->
<div class="card">

  <div class="caretaker-header">
    <h3>Caretaker</h3>

    <?php if ($caretaker_exists): ?>
      <span class="status-badge">Assigned</span>
    <?php endif; ?>
  </div>
   
  <?php if ($caretaker_exists): ?>
    <!-- Assigned caretaker info -->
    <div class="caretaker-info">
      <p><strong>Name:</strong> <?= htmlspecialchars($caretaker['name']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($caretaker['email']) ?></p>
      <p><strong>Relation:</strong> <?= htmlspecialchars($caretaker['relation']) ?></p>
    </div>
    <form method="POST" action="remove_caretaker.php" 
      onsubmit="return confirm('Are you sure you want to remove the caretaker?');">
    <button type="submit" class="remove-btn">
        Remove Caretaker
    </button>
</form>


    <button class="assign-btn" disabled>
      Caretaker Assigned
    </button>

  <?php else: ?>
    <!-- No caretaker yet -->
    <p>No caretaker assigned.</p>

    <button class="assign-btn" id="openCaretakerModal">
      Assign Caretaker
    </button>
  <?php endif; ?>

</div>


        <!-- CARETAKER MODAL -->
        <?php if (!$caretaker_exists): ?>
        <div id="caretakerModal" class="modal">
          <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Assign Caretaker</h3>
            <form method="POST" action="assign_caretaker.php">
              <input type="text" name="name" placeholder="Caretaker Name" required><br><br>
              <input type="email" name="email" placeholder="Caretaker Email" required><br><br>
              <input type="text" name="relation" placeholder="Relation (Eg: Daughter)" required><br><br>
              <button class="edit-btn" type="submit">Assign Caretaker</button>
            </form>
          </div>
        </div>  
        <?php endif; ?>

      </div>
    </section>
  </main>
</div>

<script src="../../public/assets/dashboard_script.js"></script>
<script>
  // Topbar name & avatar
  document.getElementById('profile-name').textContent = "<?= addslashes($userName) ?> | <?= addslashes($userRole) ?>";
  document.getElementById('avatar-circle').textContent = "<?= strtoupper(substr($userName,0,1)) ?>";
  // Open modal
document.getElementById('openCaretakerModal')?.addEventListener('click', function() {
    document.getElementById('caretakerModal').style.display = 'block';
});

// Close modal
document.querySelectorAll('.modal .close').forEach(el => {
    el.addEventListener('click', function() {
        this.closest('.modal').style.display = 'none';
    });
});

</script>
</body>
</html>
