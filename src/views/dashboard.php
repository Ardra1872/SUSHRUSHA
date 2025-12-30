<!DOCTYPE html>
<html lang="en">
<head>
<?php
session_start();
include '../config/db.php'; 

$user_id = $_SESSION['user_id'] ?? null;

/* Patient Profile */
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
// Redirect to login if user is not logged in
if (!isset($_SESSION['user_name'])) {
    header('Location: ../../public/login.php');
    exit();
}
$userName = $_SESSION['user_name'];
$userRole = isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'User';
?>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SUSHRUSHA – Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
*{box-sizing:border-box}
body{
  margin:0;
  font-family:'Poppins',sans-serif;
  background:#cfe9f8;
  padding:20px;
}
.app{
  background:#fff;
  border-radius:28px;
  padding:20px;
  display:grid;
  grid-template-columns:240px 1fr;
  gap:20px;
  min-height:90vh;
}

/* Sidebar */
.sidebar{
  background:#e9f6fb;
  border-radius:22px;
  padding:24px;
  display:flex;
  flex-direction:column;
  gap:22px;
}
.logo h2{margin:0;color:#3b6fdc;font-weight:600}
.logo span{font-size:12px;color:#5a8fd8}

.menu a{
  display:block;
  padding:10px 14px;
  border-radius:14px;
  color:#5b6b7a;
  text-decoration:none;
  margin-bottom:6px;
  font-weight:500;
  cursor:pointer;
}
.menu a.active{
  background:#fff;
  color:#2a79ff;
}

.logout{margin-top:auto}

/* Main */
.main{
  display:flex;
  flex-direction:column;
  gap:18px;
}

.topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:20px;
}
.search{
  flex:1;
  max-width:380px;
  background:#f3f5f7;
  padding:12px 16px;
  border-radius:20px;
  border:none;
}
.profile{display:flex;align-items:center;gap:15px;font-size:13px}
.time-section{display:flex;flex-direction:column;gap:4px}
.time{font-weight:600;color:#2a79ff;font-size:14px}
.name{color:#5b6b7a;font-size:12px}
.avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;cursor:pointer}

/* Pages */
.page{
  display:none;
  animation:fade 0.3s ease;
}
.page.active{
  display:block;
}

@keyframes fade{
  from{opacity:0;transform:translateY(6px)}
  to{opacity:1;transform:translateY(0)}
}

/* Cards */
.cards{
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:18px;
}
.card{
  background:#eaf7fb;
  border-radius:22px;
  padding:20px;
}
.card h3{margin:0 0 10px;font-size:14px;color:#4b5d6b}
.big{font-size:32px;font-weight:600;color:#2a79ff}

.alerts{
  background:#f5fbff;
  border-radius:22px;
  padding:16px;
}
.alert{
  background:#fff;
  border-radius:14px;
  padding:10px 12px;
  margin-bottom:10px;
  font-size:12px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:16px;
}

.emergency{
  border:1.5px solid #8aa9ff;
  background:#f9fbff;
  text-align:center;
}
.emergency button{
  margin-top:10px;
  padding:10px 16px;
  border:none;
  border-radius:14px;
  background:#ff6b6b;
  color:#fff;
  font-weight:600;
  cursor:pointer;
}

@media(max-width:1000px){
  .app{grid-template-columns:1fr}
  .grid{grid-template-columns:1fr 1fr}
}
/* Patient Profile */
.profile-grid{
  display:grid;
  grid-template-columns:1.5fr 1fr;
  gap:18px;
}

.profile-header{
  display:flex;
  align-items:center;
  gap:16px;
}

.profile-avatar{
  width:70px;
  height:70px;
  border-radius:50%;
  background:linear-gradient(135deg,#667eea,#764ba2);
  color:#fff;
  font-size:26px;
  font-weight:600;
  display:flex;
  align-items:center;
  justify-content:center;
}

.profile-info h4{
  margin:0;
  font-size:16px;
  color:#2a79ff;
}

.profile-info p{
  margin:2px 0;
  font-size:12px;
  color:#5b6b7a;
}

.profile-list p{
  font-size:13px;
  margin:6px 0;
}

.edit-btn{
  margin-top:12px;
  padding:8px 14px;
  border:none;
  border-radius:14px;
  background:#2a79ff;
  color:#fff;
  font-size:12px;
  cursor:pointer;
}
/* Edit Profile Form Styling */
#profile-form .card {
  background: #f5fbff;
}

#profile-form form {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px 18px;
}

#profile-form label {
  font-size: 12px;
  font-weight: 500;
  color: #4b5d6b;
}

#profile-form input,
#profile-form select {
  width: 100%;
  padding: 10px 14px;
  border-radius: 14px;
  border: 1px solid #dbe7f1;
  background: #ffffff;
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  outline: none;
}

#profile-form input:focus,
#profile-form select:focus {
  border-color: #2a79ff;
  box-shadow: 0 0 0 2px rgba(42,121,255,0.12);
}

/* Full-width fields */
#profile-form .full {
  grid-column: span 2;
}

/* Save button alignment */
#profile-form button {
  grid-column: span 2;
  justify-self: flex-end;
  padding: 10px 20px;
  border-radius: 16px;
}

</style>
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
      <a class="nav-item active" data-target="schedule">Medicine Schedule</a>
      <a class="nav-item" data-target="reports">Reports</a>
      <a class="nav-item" data-target="settings">Reminder Settings</a>
      <a class="nav-item" data-target="emergency">Emergency Contacts</a>
      <a class="nav-item" data-target="profile">Patient Profile</a>
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
          <div class="name" id="profile-name">John Doe</div>
        </div>
        <div class="avatar" id="avatar-circle" title="Profile">👤</div>
      </div>
    </div>

    <!-- Medicine Schedule -->
    <section id="schedule" class="page active">
      <section class="cards">
        <div class="card">
          <h3>Today's Reminder</h3>
          <div class="big">27</div>
          <p>3 medicines · Next in 15 minutes</p>
        </div>
        <div class="alerts">
          <h3>Medicine Alerts</h3>
          <div class="alert">🔔 2 missed doses</div>
          <div class="alert">⚠️ Stock running low</div>
        </div>
      </section>

      <section class="grid">
        <div class="card">
          <h3>Medicine Intake</h3>
          <p>3 medicines today</p>
        </div>
        <div class="card">
          <h3>Caregiver Status</h3>
          <p>Available</p>
        </div>
        <div class="card emergency">
          <h3>Emergency Quick Access</h3>
          <button>Call Emergency</button>
        </div>
      </section>
    </section>

    <!-- Reports -->
    <section id="reports" class="page">
      <div class="card">
        <h3>Reports</h3>
        <p>Medicine history & analytics will appear here.</p>
      </div>
    </section>

    <!-- Settings -->
    <section id="settings" class="page">
      <div class="card">
        <h3>Reminder Settings</h3>
        <p>Manage medicine timing, alerts and frequency.</p>
      </div>
    </section>

    <!-- Emergency -->
    <section id="emergency" class="page">
      <div class="card emergency">
        <h3>Emergency Contacts</h3>
        <p>Add & manage emergency numbers.</p>
        <button>Call Emergency</button>
      </div>
    </section>

    <!-- Profile -->
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
       <div id="profile-form" style="display:none;margin-top:20px;">
  <div class="card">
    <h3>Edit Patient Profile</h3>

   <form method="POST" action="update_profile.php" class="profile-grid">

  <input type="hidden" name="patient_id" value="<?= $_SESSION['user_id'] ?>">

  <div class="form-group">
    <label>Date of Birth</label>
    <input type="date" name="dob">
  </div>

  <div class="form-group">
    <label>Gender</label>
    <select name="gender" required>
      <option value="">Select</option>
      <option>Male</option>
      <option>Female</option>
      <option>Other</option>
    </select>
  </div>

  <div class="form-group">
    <label>Blood Group</label>
    <input type="text" name="blood_group" placeholder="O+, A-" required>
  </div>

  <div class="form-group">
    <label>Height (cm)</label>
    <input type="number" name="height_cm">
  </div>

  <div class="form-group">
    <label>Weight (kg)</label>
    <input type="number" name="weight_kg">
  </div>

  <div class="form-group full">
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

  </div>
</section>


  </main>
</div>

<!-- JS -->
<script>
const navItems = document.querySelectorAll('.nav-item');
const pages = document.querySelectorAll('.page');

navItems.forEach(item=>{
  item.addEventListener('click',()=>{
    navItems.forEach(i=>i.classList.remove('active'));
    pages.forEach(p=>p.classList.remove('active'));

    item.classList.add('active');
    document.getElementById(item.dataset.target).classList.add('active');
  });
});

// Real-time clock
function updateTime() {
  const now = new Date();
  const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  
  const month = monthNames[now.getMonth()];
  const date = now.getDate();
  const day = dayNames[now.getDay()];
  const year = now.getFullYear();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  const ampm = now.getHours() >= 12 ? 'AM' : 'PM';
  const displayHours = now.getHours() % 12 || 12;
  
  document.getElementById('current-time').textContent = `${month} ${date} ${day} ${year} | ${displayHours}:${minutes} ${ampm}`;
}

// Update time every second
setInterval(updateTime, 1000);
updateTime();

// Load profile name from session
function loadProfileName() {
  const profileName = '<?php echo htmlspecialchars($userName); ?>';
  const userRole = '<?php echo htmlspecialchars($userRole); ?>';
  const firstLetter = profileName.charAt(0).toUpperCase();
  document.getElementById('profile-name').textContent = profileName + ' | ' + userRole;
  document.getElementById('avatar-circle').textContent = firstLetter;
}

loadProfileName();

function logout() {
  window.location.href = '../../public/landing.html';
}
function openProfileForm() {
  const form = document.getElementById('profile-form');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

</script>

</body>
</html>
