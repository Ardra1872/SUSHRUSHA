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
  <div id="toast" class="toast"></div>

  <script src="https://unpkg.com/lucide@latest"></script>

<div class="app">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="logo">
      <h2>SUSHRUSHA</h2>
      <span>Smart Medicine Reminder</span><br><br>
    </div>

  <nav class="menu">
  <a class="nav-item active" data-target="schedule">
    <i data-lucide="calendar-check"></i>
    <span>Medicine Schedule</span>
  </a>

  <a class="nav-item" data-target="reports">
    <i data-lucide="file-text"></i>
    <span>Reports</span>
  </a>

  <a class="nav-item" data-target="settings">
    <i data-lucide="bell"></i>
    <span>Reminder Settings</span>
  </a>

  <a class="nav-item" data-target="emergency">
    <i data-lucide="phone-call"></i>
    <span>Emergency Contacts</span>
  </a>

  <a class="nav-item" data-target="profile">
    <i data-lucide="user"></i>
    <span>Patient Profile</span>
  </a>
</nav>

  <div class="logout">
  <a href="#" class="logout-btn" onclick="logout()">
    <!-- Lucide Logout Icon -->
    <i data-lucide="log-out" class="logout-icon"></i>
    <span class="logout-text">Logout</span>
  </a>
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

    
  <section id="schedule" class="page active">

  <section class="cards">
    <div class="card">
      <h3>Today's Reminder</h3>
      <div class="big" id="today-med-count">0</div>
       <p id="today-med-info">Loading medicines...</p>

     <div class="action-buttons">
   <div class="action-buttons">
  <button class="dash-primary-btn"
      onclick="document.getElementById('addMedicineModal').style.display='flex'">
      <i data-lucide="plus"></i>
      Add Medicine
  </button>

  <button class="dash-view-btn" onclick="openViewMedicineModal()">
      <i data-lucide="eye"></i>
      View Medicines
  </button>
</div>


</div>


<div class="modal" id="addMedicineModal">
  <div class="modal-box" onclick="event.stopPropagation()">

    <div class="modal-header">
      <span class="modal-title"><i data-lucide="medicine"></i> Add Medicine</span>
      <span class="close" onclick="closeMedicineModal()">×</span>
    </div>

    <form id="medicineForm" class="modal-body" action="add_medicine.php" method="post">

  <div class="field full">
    <label>Medicine Name</label>
    <input type="text" name="medicine_name" placeholder="Paracetamol" required>
  </div>

  <div class="grid-2">
    <div class="field">
      <label>Dosage</label>
      <input type="text" name="dosage" placeholder="500 mg">
    </div>

    <div class="field">
      <label>Compartment</label>
      <input type="number" name="compartment" placeholder="1" required>
    </div>
  </div>

  <div class="grid-2">
    <div class="field">
      <label>Start Date</label>
      <input type="date" name="start_date" required>
    </div>

    <div class="field">
      <label>End Date</label>
      <input type="date" name="end_date">
    </div>
  </div>

  <div class="field full">
    <label>Intake Time</label>
    <input type="time" name="intake_time" required>
  </div>

  <button type="submit" class="primary-btn">Save Medicine</button>
</form>

  </div>
</div>
<div id="viewMedicineModal" class="modal">
  <div class="modal-box">

    <div class="modal-header">
      <h2>💊 Your Medicines</h2>
      <span class="close" onclick="closeViewMedicineModal()">×</span>
    </div>

    <div class="modal-body">
      <table class="medicine-table">
        <thead>
  <tr>
    <th>Name</th>
    <th>Dosage</th>
    <th>Time</th>
    <th>Action</th>
  </tr>
</thead>

        <tbody id="medicineList">
          <!-- Medicines load here -->
        </tbody>
      </table>
    </div>

  </div>
</div>

    </div>

    <div class="alerts">
      <h3>Medicine Alerts</h3>
      <div class="alert">🔔 2 missed doses</div>
      <div class="alert">⚠️ Stock running low</div>
    </div>
 

</section>
    <!-- <section class="grid"> <div class="card"> <h3>Medicine Intake</h3> <p>3 medicines today</p> </div> <div class="card"> <h3>Caregiver Status</h3> <p>Available</p> </div> <div class="card emergency"> <h3>Emergency Quick Access</h3> <button>Call Emergency</button> </div> </section> </section> -->
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
          <button class="edit-btn" onclick="openProfileEditModal()">Edit Profile</button>


          <!-- EDIT PROFILE MODAL (ISOLATED) -->
<div id="profileEditModal" class="profile-modal">
  <div class="profile-modal-box">
    <span class="profile-modal-close" onclick="closeProfileEditModal()">×</span>

    <h3>Edit Patient Profile</h3>

    <form method="POST" action="update_profile.php" class="profile-modal-grid">
      <input type="hidden" name="patient_id" value="<?= $user_id ?>">

      <div>
        <label>Date of Birth</label>
        <input type="date" name="dob">
      </div>

      <div>
        <label>Gender</label>
        <select name="gender">
          <option value="">Select</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
      </div>

      <div>
        <label>Blood Group</label>
        <input type="text" name="blood_group">
      </div>

      <div>
        <label>Height (cm)</label>
        <input type="number" name="height_cm">
      </div>

      <div>
        <label>Weight (kg)</label>
        <input type="number" name="weight_kg">
      </div>

      <div class="full">
        <label>Emergency Contact</label>
        <input type="text" name="emergency_contact">
      </div>

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
<div class="caretaker-card">

  <div class="caretaker-header">
    <h3>Caretaker</h3>
    <?php if ($caretaker_exists): ?>
      <span class="status-badge">Assigned</span>
    <?php endif; ?>
  </div>

  <?php if ($caretaker_exists): ?>
    <div class="caretaker-box">
      
      <div class="caretaker-avatar">
        <?= strtoupper(substr($caretaker['name'], 0, 1)) ?>
      </div>

      <div class="caretaker-details">
        <h4><?= htmlspecialchars($caretaker['name']) ?></h4>
        <p class="email"><?= htmlspecialchars($caretaker['email']) ?></p>
        <span class="relation"><?= htmlspecialchars($caretaker['relation']) ?></span>
      </div>
    </div>

    <div class="caretaker-actions">
      <form method="POST" action="remove_caretaker.php"
        onsubmit="return confirm('Remove caretaker?');">
        <button type="submit" class="remove-btn">Remove Caretaker</button>
      </form>

      <button class="assign-btn" disabled>Caretaker Assigned</button>
    </div>

  <?php else: ?>
    <p>No caretaker assigned.</p>
    <button class="assign-btn" id="openCaretakerModal">Assign Caretaker</button>
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

function openProfileEditModal() {
  document.getElementById('profileEditModal').style.display = 'flex';
}

function closeProfileEditModal() {
  document.getElementById('profileEditModal').style.display = 'none';
}

document.getElementById('profileEditModal').addEventListener('click', function(e) {
  if (e.target === this) closeProfileEditModal();
});




document.getElementById("medicineForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch("add_medicine.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "success") {
    showToast("Medicine added successfully 💊");

      document.getElementById("addMedicineModal").style.display = "none";
      this.reset();
    } else {
      showToast(data.message || "Failed to add medicine", "error");

    }
  });
});function openViewMedicineModal() {
  const modal = document.getElementById('viewMedicineModal');
  modal.style.display = 'flex';

  // Fetch medicines from the server
  fetch('fetch_medicine.php')
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('medicineList');
      list.innerHTML = '';

      if (!data || data.length === 0) {
        list.innerHTML = '<tr><td colspan="4">No medicines added yet.</td></tr>';
        return;
      }

      data.forEach(med => {
        const row = document.createElement('tr');
        row.innerHTML = `
  <td>${med.name}</td>
  <td>${med.dosage || '-'}</td>
  <td>${med.intake_time}</td>
  <td>
    <button class="delete-btn" onclick="deleteMedicine(${med.id})">
      🗑 Delete
    </button>
  </td>
`;

        list.appendChild(row);
      });
    })
    .catch(err => console.error('Error fetching medicines:', err));
}

function closeViewMedicineModal() {
  document.getElementById('viewMedicineModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('viewMedicineModal').addEventListener('click', function(e) {
  if (e.target === this) closeViewMedicineModal();
});

function updateTodayMedicines() {
    fetch('fetch_medicine.php')
        .then(res => res.json())
        .then(data => {
            const today = new Date().toISOString().split('T')[0];
            // Filter medicines valid for today
            const todaysMeds = data.filter(med => {
                return (!med.start_date || med.start_date <= today) &&
                       (!med.end_date || med.end_date >= today);
            });

            document.getElementById('today-med-count').textContent = todaysMeds.length;

            if (todaysMeds.length === 0) {
                document.getElementById('today-med-info').textContent = "No medicines for today";
                return;
            }

            const nextIntake = calculateNextIntake(todaysMeds);
            document.getElementById('today-med-info').textContent =
                `${todaysMeds.length} medicines · Next in ${nextIntake} minutes`;
        })
        .catch(err => console.error(err));
}

// Calculate next intake in minutes
function calculateNextIntake(meds) {
    const now = new Date();
    let minDiff = Infinity;

    meds.forEach(med => {
        if (!med.intake_time) return;
        const [hours, minutes] = med.intake_time.split(':').map(Number);
        const intakeTime = new Date();
        intakeTime.setHours(hours, minutes, 0, 0);

        let diff = (intakeTime - now) / 60000; // difference in minutes
        if (diff < 0) diff += 24 * 60; // next day if already passed
        if (diff < minDiff) minDiff = diff;
    });

    return Math.round(minDiff);
}

// Call it on page load
updateTodayMedicines();
setInterval(updateTodayMedicines, 60 * 1000); // refresh every minute
function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.className = "toast show";

  if (type === "error") {
    toast.classList.add("error");
  }

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

function deleteMedicine(medicineId) {
  if (!confirm("Delete this medicine?")) return;

  fetch("delete_medicine.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ medicine_id: medicineId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "success") {
      showToast("Medicine deleted 🗑");
      openViewMedicineModal(); // refresh list
      updateTodayMedicines();  // update dashboard count
    } else {
      showToast(data.message || "Delete failed", "error");
    }
  });
}

</script>
<script>
  lucide.createIcons();
</script>

</body>
</html>
