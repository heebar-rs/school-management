<?php
session_start();
require('../connexion.php');

$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) die("Erreur : " . $connexion->connect_error);

if(!isset($_SESSION['username'])){
  header("Location: ../first.php");
  exit;
}

/* ===== Récupérer infos étudiant ===== */
$stmt = $connexion->prepare("
    SELECT s.student_id AS student_id, s.first_name, s.last_name, s.photo, u.id AS user_id
    FROM students s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE u.username = ?
");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if(!$student){
  die("Étudiant introuvable.");
}

$fullname = htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES, 'UTF-8');
$photo    = !empty($student['photo']) ? $student['photo'] : null;
$user_id  = (int)$student['user_id'];

/* ===== Vérifier ou créer conversation =====
   ⚠️ IMPORTANT : si ta table conversations a une FK admin_id (NOT NULL),
   il faut insérer admin_id aussi. Ici on garde ton INSERT simple (user_id, last_message).
*/
$res = $connexion->prepare("SELECT id FROM conversations WHERE user_id = ?");
$res->bind_param("i", $user_id);
$res->execute();
$res = $res->get_result();

if($res->num_rows > 0){
    $conv = $res->fetch_assoc();
    $conversation_id = (int)$conv['id'];
} else {
    $stmt2 = $connexion->prepare("INSERT INTO conversations (user_id, last_message) VALUES (?, NOW())");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $conversation_id = (int)$connexion->insert_id;
}

/* ===== Infos de classe ===== */
$stmtClass = $connexion->prepare("
    SELECT g.nom AS group_name, n.nom AS niveau_name
    FROM students s
    LEFT JOIN groupes g ON s.groupe_id = g.id
    LEFT JOIN niveaux n ON g.niveau_id = n.id
    WHERE s.user_id = ?
");
$stmtClass->bind_param("i", $user_id);
$stmtClass->execute();
$resClass  = $stmtClass->get_result();
$classInfo = $resClass->fetch_assoc();

$groupName  = $classInfo['group_name'] ?? "N/A";
$niveauName = $classInfo['niveau_name'] ?? "N/A";
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - <?= $fullname ?></title>
<link rel="stylesheet" href="school.css">

<style>
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --white:#fff;
  --card-bg:#fff;
  --text:#333;
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:"Poppins",sans-serif;background:var(--bg);color:var(--text);}

/* Header */
header{
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:20px 40px;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
}
header h1{margin:0;font-size:26px;}
header nav a{color:#fff;margin-left:20px;text-decoration:none;font-weight:500;}
header nav a.btn{
  background:#fff;color:var(--secondary);
  padding:6px 20px;border-radius:25px;transition:.3s;
}
header nav a.btn:hover{opacity:.9}

/* Sidebar */
.sidebar{
  position:fixed;left:-240px;top:0;width:240px;height:100vh;
  background:var(--secondary);padding:20px;color:white;
  display:flex;flex-direction:column;gap:25px;
  transition:left .3s ease;z-index:200;
  box-shadow:5px 0 15px rgba(0,0,0,0.3);
}
.sidebar.open{left:0;}
.sidebar h2{margin:0 0 20px;font-size:20px;}
.sidebar a{color:#fff;text-decoration:none;padding:8px 0;border-radius:8px;transition:.3s;}
.sidebar a:hover{background:rgba(255,255,255,0.1);}

#burger{
  position:fixed;top:15px;left:15px;z-index:300;font-size:28px;
  background-color:#94979bff;border:none;border-radius:6px;cursor:pointer;
  line-height:1;box-shadow:0 4px 8px rgba(0,0,0,0.2);
  transition:.3s;
}
#burger.open{color:#265587ff;}
.site-main{margin-left:0;transition:margin-left .3s;}
.sidebar.open ~ .site-main{margin-left:240px;}

/* Hero & cards */
.hero{display:flex;align-items:center;gap:30px;padding:60px;}
.avatar{width:120px;height:120px;border-radius:50%;overflow:hidden;flex-shrink:0;}
.avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.hero-body h2{font-size:36px;margin:0;color:var(--secondary);}

.cards{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  gap:25px;padding:40px;
}
.card{
  background:var(--card-bg);padding:25px;border-radius:20px;text-align:center;
  box-shadow:0 15px 30px rgba(0,0,0,.1);transition:.3s;
}
.card a{display:block;text-decoration:none;color:var(--secondary);font-weight:600;}
.card:hover{transform:translateY(-8px);box-shadow:0 25px 50px rgba(0,0,0,.15);}

/* ================= OVERLAYS ================= */
#overlay, #overlayCalendar{
  position:fixed; inset:0;
  background: rgba(10,15,25,.55);
  backdrop-filter: blur(10px);
  opacity:0; pointer-events:none;
  transition:.25s ease;
  z-index:20;
}
#overlay.active, #overlayCalendar.active{opacity:1; pointer-events:auto;}

/* ================= MODAL BASE ================= */
.modal{
  position:fixed;
  top:50%; left:50%;
  transform: translate(-50%,-55%) scale(.96);
  background: rgba(255,255,255,0.82);
  border: 1px solid rgba(255,255,255,0.35);
  border-radius: 22px;
  box-shadow: 0 40px 110px rgba(0,0,0,.28);
  overflow:hidden;
  opacity:0; pointer-events:none;
  transition:.28s ease;
  z-index:30;
}
.modal.active{
  transform: translate(-50%,-50%) scale(1);
  opacity:1; pointer-events:auto;
}

/* ================= CHAT MODAL (CONTACT) ================= */
#contactModal{
  width: 520px;
  max-width: 92vw;
  height: 70vh;
  max-height: 620px;
}

/* Header chat modal */
#contactModal .modal-head{
  display:flex;align-items:center;justify-content:space-between;
  padding: 16px 18px;
  background: linear-gradient(135deg, rgba(36,51,82,0.92), rgba(13,110,253,0.85));
  color:#fff;
}
#contactModal .modal-title{display:flex;align-items:center;gap:12px;}
#contactModal .modal-icon{
  width:40px;height:40px;border-radius:14px;display:grid;place-items:center;
  background: rgba(255,255,255,0.18);
  border: 1px solid rgba(255,255,255,0.22);
}
#contactModal h2{margin:0;font-size:18px;line-height:1.1;}
#contactModal .modal-subtitle{margin:2px 0 0;font-size:12px;opacity:.85;}
#contactModal .modal-close{
  width:38px;height:38px;border:none;border-radius:12px;cursor:pointer;
  color:#fff;background: rgba(255,255,255,0.16);
  border: 1px solid rgba(255,255,255,0.20);
  transition:.2s;
}
#contactModal .modal-close:hover{background: rgba(255,255,255,0.26);transform: translateY(-1px);}

/* Chat area */
#chatBox{
  height: calc(70vh - 150px);
  max-height: 420px;
  overflow-y:auto;
  padding: 16px 14px;
  background:
    radial-gradient(900px 350px at 20% 0%, rgba(13,110,253,0.14), transparent 60%),
    radial-gradient(800px 300px at 90% 30%, rgba(114,9,183,0.10), transparent 60%),
    linear-gradient(180deg, #ffffff 0%, #f3f5fb 100%);
}
#chatBox::-webkit-scrollbar{ width:6px; }
#chatBox::-webkit-scrollbar-thumb{ background: rgba(0,0,0,0.18); border-radius:999px; }

/* Bubbles */
.msg{display:flex;align-items:flex-end;margin:10px 0;gap:6px;}
.msg.admin{justify-content:flex-start;}
.msg.user{justify-content:flex-end;}

.msg::before{
  content:""; width:28px;height:28px;border-radius:50%;
  flex:0 0 28px; box-shadow: 0 5px 12px rgba(0,0,0,0.10);
}
.msg.admin::before{
  background: linear-gradient(135deg,#f1f3f6,#ffffff);
  border: 1px solid rgba(0,0,0,0.06);
}
.msg.user::before{
  order:2;
  background: linear-gradient(135deg,#0d6efd,#6f42c1);
  border: 1px solid rgba(255,255,255,0.25);
}

.msg span{
  display:inline-block;
  padding: 10px 12px;
  max-width: 60%;
  border-radius: 16px;
  line-height: 1.35;
  font-size: 14px;
  border: 1px solid rgba(0,0,0,0.06);
  box-shadow: 0 12px 26px rgba(0,0,0,0.08);
}
.msg.admin span{
  background: rgba(255,255,255,0.92);
  color:#25324a;
  border-bottom-left-radius: 6px;
}
.msg.user span{
  background: linear-gradient(135deg,#0d6efd,#4c85ff,#6f42c1);
  color:#fff;
  border-bottom-right-radius: 6px;
}
.msg small{display:block;margin-top:4px;font-size:10px;opacity:.85;}
.msg.admin small{color: rgba(0,0,0,0.45);}
.msg.user small{color: rgba(255,255,255,0.85);}

/* Input bottom */
#contactModal .chat-input{
  display:flex;gap:10px;
  padding: 12px 14px;
  background: rgba(255,255,255,0.86);
  border-top: 1px solid rgba(0,0,0,0.06);
}
#contactModal .chat-input input[type="text"]{
  flex:1;
  padding: 12px 14px;
  border-radius: 999px;
  border: 1px solid rgba(0,0,0,0.08);
  background: rgba(248,249,250,0.95);
  outline:none;
  transition:.2s;
}
#contactModal .chat-input input[type="text"]:focus{
  border-color: rgba(13,110,253,0.55);
  box-shadow: 0 0 0 3px rgba(13,110,253,0.16);
  background:#fff;
}
#contactModal .send-btn{
  padding: 12px 18px;
  border:none;
  border-radius: 999px;
  cursor:pointer;
  font-weight:700;
  color:#fff;
  background: linear-gradient(135deg, #0d6efd, #6f42c1);
  box-shadow: 0 12px 22px rgba(13,110,253,0.20);
  transition:.2s;
}
#contactModal .send-btn:hover{
  transform: translateY(-1px);
  box-shadow: 0 18px 30px rgba(13,110,253,0.26);
}

/* ================= CALENDAR MODAL ================= */
#calendarModal{
  width: 520px;
  max-width: 92vw;
  padding: 22px;
}
#calendarModal h2{
  text-align:center;
  margin:0 0 18px;
  color: var(--secondary);
}

/* Calendar component */
.calendar { max-width:600px; margin:0 auto; background:#fff; padding:16px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.1);}
.calendar table { width:100%; border-collapse:collapse; }
.calendar th, .calendar td { width:14.28%; text-align:center; padding:10px; border-radius:8px; }
.calendar th { background:#555; color:#fff; }
.calendar td { background:#f0f0f0; margin:2px; cursor:pointer; transition:0.2s; }
.calendar td:hover { background:#0b5ed7; color:#fff; }
.today { background:#555; color:#fff; font-weight:bold; }
.nav-btn { cursor:pointer; font-weight:bold; padding:5px 10px; border-radius:5px; background:#555; color:#fff; margin:0 5px; }
.nav-btn:hover { background:#0956c9; }

@media (max-width: 520px){
  #contactModal{ height: 78vh; }
  .msg span{ max-width: 72%; }
}

</style>
</head>

<body>

<button id="burger" aria-label="Toggle sidebar">☰</button>
<aside id="sidebar" class="sidebar">
  <h2> </h2>
  <a href="userdashboard.php">Dashboard</a>
  <a href="../first.php">Logout</a>
</aside>

<main class="site-main">
<header>
  <h1 style="margin-left:40px">Loop Academy</h1>
  <nav>
    <a href="../first.php" class="btn">Logout</a>
    <a href="#" class="btn" onclick="openModal('contactModal')">Contact Responsable</a>
  </nav>
</header>

<section class="hero">
  <div class="avatar">
    <?php if($photo): ?>
      <img src="data:image/jpeg;base64,<?= base64_encode($photo) ?>" alt="Avatar <?= $fullname ?>">
    <?php else: ?>
      <svg viewBox="0 0 128 128"><circle cx="64" cy="64" r="64" fill="#e6eef9"/></svg>
    <?php endif; ?>
  </div>
  <div class="hero-body">
    <h2>Welcome, <?= $fullname ?> 👋</h2>
  </div>
</section>

<section class="cards">
  <div class="card">
    <a>
      My Class:<br><br>
      <?= htmlspecialchars($groupName) ?> <?= htmlspecialchars($niveauName) ?>
    </a>
  </div>

  <div class="card"><a href="syllabus.php">My Syllabus 📄</a></div>
<div class="card"><a href="filieres_view.php">My Academy programms</a></div>

  <div class="card">
    <a href="#" class="btn" onclick="openCalendar()">View Calendar 🗓️</a>
  </div>
</section>
</main>

<!-- ================= OVERLAYS ================= -->
<div id="overlay" onclick="closeAll()"></div>
<div id="overlayCalendar" onclick="closeCalendar()"></div>

<!-- ================= CONTACT CHAT MODAL ================= -->
<div class="modal" id="contactModal">
  <div class="modal-head">
    <div class="modal-title">
      <div class="modal-icon">💬</div>
      <div>
        <h2>Contact Responsable</h2>
        <p class="modal-subtitle">Support & messages</p>
      </div>
    </div>
    <button type="button" class="modal-close" onclick="closeAll()">✕</button>
  </div>

  <div id="chatBox"></div>

  <form id="sendFormUser" class="chat-input">
    <input type="hidden" id="conv_id" value="<?= $conversation_id ?>">
    <input type="text" id="message" placeholder="Votre message..." autocomplete="off">
    <button type="submit" class="send-btn">Envoyer</button>
  </form>
</div>

<!-- ================= CALENDAR MODAL ================= -->
<div class="modal" id="calendarModal">
  <h2>My Calendar 🗓️</h2>

  <div class="calendar">
    <div style="text-align:center; margin-bottom:15px;">
      <span class="nav-btn" onclick="prevMonth()">&#8592; Prev</span>
      <span id="monthYear"></span>
      <span class="nav-btn" onclick="nextMonth()">Next &#8594;</span>
    </div>
    <table id="calendarTable">
      <thead>
        <tr>
          <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
          <th>Thu</th><th>Fri</th><th>Sat</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
/* ===== Burger menu ===== */
const burger  = document.getElementById('burger');
const sidebar = document.getElementById('sidebar');
burger.addEventListener('click', ()=>{
  sidebar.classList.toggle('open');
  burger.classList.toggle('open');
});

/* ===== Modals ===== */
function openModal(id){
  document.getElementById(id).classList.add('active');
  document.getElementById('overlay').classList.add('active');
  loadMessages();
}

function closeAll(){
  document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));
  document.getElementById('overlay').classList.remove('active');
}

/* ===== Calendar modal ===== */
function openCalendar(){
  document.getElementById('calendarModal').classList.add('active');//ajoute la classe CSS active.
  document.getElementById('overlayCalendar').classList.add('active');//récupère l’élément HTML qui a :id="calendarModal"

}
function closeCalendar(){
  document.getElementById('calendarModal').classList.remove('active');
  document.getElementById('overlayCalendar').classList.remove('active');
}

let today = new Date();
let currentMonth = today.getMonth();
let currentYear  = today.getFullYear();
const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];

function showCalendar(month, year){
  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const tbl = document.querySelector("#calendarTable tbody");
  tbl.innerHTML = "";

  let date = 1;
  for(let i=0; i<6; i++){
    let row = document.createElement("tr");
    for(let j=0; j<7; j++){
      let cell = document.createElement("td");
      if(i===0 && j<firstDay){
        cell.innerHTML = "";
      } else if(date > daysInMonth){
        break;
      } else {
        cell.innerHTML = date;
        if(date === today.getDate() && month === today.getMonth() && year === today.getFullYear()){
          cell.classList.add("today");
        }
        date++;
      }
      row.appendChild(cell);
    }
    tbl.appendChild(row);
  }
  document.getElementById("monthYear").innerText = monthNames[month] + " " + year;
}
function prevMonth(){ currentMonth--; if(currentMonth < 0){ currentMonth=11; currentYear--; } showCalendar(currentMonth, currentYear); }
function nextMonth(){ currentMonth++; if(currentMonth > 11){ currentMonth=0; currentYear++; } showCalendar(currentMonth, currentYear); }
showCalendar(currentMonth, currentYear);

/* ===== Chat ===== */
const formUser     = document.getElementById('sendFormUser');
const msgInputUser = document.getElementById('message');
const convInput    = document.getElementById('conv_id');
const chatBox      = document.getElementById('chatBox');

function loadMessages(){
  if(!convInput.value) return;
  fetch('load_messages.php?conv=' + encodeURIComponent(convInput.value))
    .then(r => r.text())
    .then(html => {
      chatBox.innerHTML = html;
      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

formUser.addEventListener('submit', (e)=>{
  e.preventDefault();
  const msg = msgInputUser.value.trim();
  if(!msg) return;

  fetch('send_message_user.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `conversation_id=${encodeURIComponent(convInput.value)}&message=${encodeURIComponent(msg)}`
  })
  .then(r => r.text())
  .then(res => {
    if(res.trim() === 'success'){
      msgInputUser.value = '';
      loadMessages();
    } else {
      alert(res);
    }
  });
});
</script>

</body>
</html>
