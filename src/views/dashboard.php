<!DOCTYPE html>
<html lang="en">
<head>
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
.profile{display:flex;align-items:center;gap:10px;font-size:13px}
.avatar{width:36px;height:36px;border-radius:50%;background:#ddd}

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
        <div>Tuesday, Dec 2025 · 11:14 AM</div>
        <div class="avatar"></div>
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
      <div class="card">
        <h3>Patient Profile</h3>
        <!-- <p>Name, age, conditions & preferences.</p> -->
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


  function logout() {
   
    window.location.href = '../../public/landing.html';
  }

</script>

</body>
</html>
