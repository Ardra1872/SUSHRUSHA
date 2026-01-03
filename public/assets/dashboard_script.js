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
}const modal = document.getElementById("caretakerModal");
const btn = document.getElementById("openCaretakerBtn");
const span = document.querySelector(".modal .close");

// Open modal
btn.onclick = function() {
  modal.style.display = "block";
}

// Close modal when user clicks ×
span.onclick = function() {
  modal.style.display = "none";
}

// Close modal when clicking outside content
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
