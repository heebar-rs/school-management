<?php
session_start();

require("../connexion.php");

$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}

/* ================== SECURITY (OPTIONNEL) ================== */
if(!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])){
  // header("Location: ../first.php");
  // exit;
}

/* ================== NOTIFICATIONS (messages) ================== */
$notifStmt = $connexion->query("
SELECT 
    m.message,
    u.username,
    m.sender,
    m.created_at
FROM messages m
JOIN conversations c ON m.conversation_id = c.id
JOIN users u ON c.user_id = u.id
ORDER BY m.created_at DESC
");
$messages = [];
while($row = $notifStmt->fetch_assoc()){
    $messages[] = $row;
}

/* ================== MINI CARDS TOTALS ================== */
$resStudents = $connexion->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = (int)($resStudents->fetch_assoc()['total'] ?? 0);

$resAdmins = $connexion->query("SELECT COUNT(*) AS total FROM users WHERE role='admin'");
$totalAdmins = (int)($resAdmins->fetch_assoc()['total'] ?? 0);

$resGroups = $connexion->query("SELECT COUNT(*) AS total FROM groupes");
$totalGroups = (int)($resGroups->fetch_assoc()['total'] ?? 0);

$reslevels = $connexion->query("SELECT COUNT(*) AS total FROM niveaux");
$totallevels = (int)($reslevels->fetch_assoc()['total'] ?? 0);

$resusers = $connexion->query("SELECT COUNT(*) AS total FROM users");
$totalusers = (int)($resusers->fetch_assoc()['total'] ?? 0);

/* ================= STATISTICS DATA (charts) ================= */

/* Étudiants par niveau */
$levels = $connexion->query("
SELECT niveaux.nom, COUNT(students.student_id) total
FROM niveaux
LEFT JOIN groupes ON groupes.niveau_id = niveaux.id
LEFT JOIN students ON students.groupe_id = groupes.id
GROUP BY niveaux.nom
ORDER BY niveaux.nom
")->fetch_all(MYSQLI_ASSOC);

/* Étudiants par groupe */
$groups = $connexion->query("
SELECT CONCAT(groupes.nom,' - ',niveaux.nom) groupe_label,
COUNT(students.student_id) total
FROM groupes
JOIN niveaux ON niveaux.id = groupes.niveau_id
LEFT JOIN students ON students.groupe_id = groupes.id
GROUP BY groupes.id
ORDER BY niveaux.nom, groupes.nom
")->fetch_all(MYSQLI_ASSOC);

/* Genre */
$gender = $connexion->query("
SELECT gender, COUNT(*) total
FROM students
GROUP BY gender
")->fetch_all(MYSQLI_ASSOC);

/* Photos */
$photos = $connexion->query("
SELECT 
SUM(photo IS NOT NULL) avec_photo,
SUM(photo IS NULL) sans_photo
FROM students
")->fetch_assoc();
$avec_photo = (int)($photos['avec_photo'] ?? 0);
$sans_photo = (int)($photos['sans_photo'] ?? 0);

/* Rôles */
$roles = $connexion->query("
SELECT role, COUNT(*) total
FROM users
GROUP BY role
")->fetch_all(MYSQLI_ASSOC);

/* Tranches d'âge */
$ages = $connexion->query("
SELECT 
CASE
WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) < 18 THEN '-18'
WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 22 THEN '18-22'
ELSE '+22'
END age_group,
COUNT(*) total
FROM students
GROUP BY age_group
")->fetch_all(MYSQLI_ASSOC);

/* Années */
$years = $connexion->query("
SELECT YEAR(birth_date) year, COUNT(*) total
FROM students
GROUP BY year
ORDER BY year
")->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="school.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
  --primary: #0d6efd;
  --secondary: #243352;
  --bg: #f4f6fb;
  --white: #fff;
  --card-bg: #fff;
  --text: #333;
}

body {
  margin: 0;
  font-family: "Poppins", sans-serif;
  background: var(--bg);
  color: var(--text);
}

html, body {
  margin:0; 
  padding:0;
  font-family:"Poppins",sans-serif;
  background:var(--bg); 
  color:var(--text);
}

/* Header */
header {
  margin:0;
  background: linear-gradient(135deg, var(--secondary), var(--primary));
  padding: 20px 40px;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 200;
}

header h1 { margin:0; font-size:26px; }
header nav a {
  color: #fff;
  margin-left: 20px;
  text-decoration: none;
  font-weight: 500;
}

header nav a.btn {
  background: #fff;
  color: var(--secondary);
  padding: 6px 20px;
  border-radius: 25px;
  transition: 0.3s;
}
header nav a.btn:hover { opacity: 0.9; }

.loop { margin-left: 50px; }
.halfheader{ margin-right: 60px; }

/* Sidebar */
.sidebar{ 
  position:fixed;left:-240px;top:0;width:240px;height:100vh;
  background:var(--secondary);padding:20px;color:white;
  display:flex;flex-direction:column;gap:25px;
  transition:left 0.3s ease;z-index:300;
  box-shadow:5px 0 15px rgba(0,0,0,0.3);
}
.sidebar.open{left:0;}
.sidebar h2{margin:0 0 20px;font-size:20px;}
.sidebar a{color:#fff;text-decoration:none;padding:8px 0;border-radius:8px;transition:0.3s;}
.sidebar a:hover{background:rgba(255,255,255,0.1);}

/* Burger */
#burger{
  position:fixed;top:15px;left:15px;z-index:400;
  font-size:28px;background-color:#94979bff;border:none;border-radius:6px;
  cursor:pointer;line-height:1;box-shadow:0 4px 8px rgba(0,0,0,0.2);
  transition:left 0.3s ease, background 0.3s;
}
#burger.open{color:#265587ff;}

.site-main{margin-left:0;transition:margin-left 0.3s;}
.sidebar.open ~ .site-main{margin-left:240px;}

/* Hero */
.hero { display:flex; align-items:center; gap:30px; padding:60px; }
.avatar { width:120px; height:120px; border-radius:50%; overflow:hidden; flex-shrink:0; }
.avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.hero-body h2 { font-size:36px; margin:0; color: var(--secondary); }
.hero-body p { margin-top:10px; font-size:18px; color:#555; }

/* Mini cards */
.minicards{
  margin-left:90px;
  margin-right:60px;
  margin-top:30px;
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
  gap:20px;
}
.minicards .card{
  background:#ffffff;
  border-radius:16px;
  padding:20px;
  box-shadow:0 12px 30px rgba(0,0,0,.12);
  transition:transform .3s ease, box-shadow .3s ease;
  position:relative;
  overflow:hidden;
}
.minicards .card:hover{
  transform:translateY(-6px);
  box-shadow:0 18px 40px rgba(0,0,0,.18);
}
.minicards .total{
  margin:8px 0;
  font-size:16px;
  font-weight:600;
  color:#243352;
}
.minicards .card::before{
  content:"";
  position:absolute;
  top:0; left:0;
  width:100%;
  height:5px;
  background:linear-gradient(135deg,#0d6efd,#243352);
}
.minicards .card:nth-child(1)::before{ background:linear-gradient(135deg,#0d6efd,#4dabf7); }
.minicards .card:nth-child(2)::before{ background:linear-gradient(135deg,#198754,#51cf66); }
.minicards .card:nth-child(3)::before{ background:linear-gradient(135deg,#fd7e14,#ffa94d); }

/* Dashboard cards */
.cards { margin-left:50px;margin-right:30px; display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:25px; padding:40px 0; }
.card { background: var(--card-bg); padding:25px; border-radius:20px; text-align:center; box-shadow:0 15px 30px rgba(0,0,0,.1); transition:0.3s; }
.card a { display:block; text-decoration:none; color:var(--secondary); font-weight:600; }
.card:hover { transform:translateY(-8px); box-shadow:0 25px 50px rgba(0,0,0,.15); }

/* Stats section */
.stats-section{
  margin:40px 60px;
}
.stats-section h2{
  color:#243352;
  margin:0 0 18px;
}
.stats-grid{
  display:grid;
  grid-template-columns:repeat(12,1fr);
  gap:20px;
}
.stats-card{
  background:#fff;
  border-radius:20px;
  padding:20px;
  box-shadow:0 15px 30px rgba(0,0,0,.1);
}
.stats-card h3{
  margin:0 0 10px;
  color:#243352;
  font-size:16px;
}

@media(max-width:1000px){
  .stats-card{ grid-column:span 12 !important; }
  .stats-section{ margin:30px 20px; }
}

</style>
</head>

<body>

<button id="burger">☰</button>

<aside id="sidebar" class="sidebar">
  <h2> </h2>
  <a href="admindashboard.php">Dashboard</a>
  <a href="statistics.php">statistics</a>
  <a href="list_students.php">List Students</a>
  <a href="list_admins.php">List Admins</a>
  <a href="classes.php">Classes</a>
  <a href="filieres.php">programms</a>
  <a href="../first.php">Logout</a>
</aside>

<main class="site-main">
<header>
  <h1 class="loop">Loop Academy</h1>
  <nav class="halfheader">
    <a href="admindashboard.php">Dashboard</a>
    <a href="admin_chat.php">inbox
      <span id="notifCount" style="background:red; color:white; border-radius:50%; padding:2px 6px; font-size:12px;">0</span>
    </a>
    <a href="../first.php" class="btn">Logout</a>
  </nav>
</header>

<section class="hero">
  <div class="avatar">
    <svg viewBox="0 0 128 128"><circle cx="64" cy="64" r="64" fill="#e6eef9"/></svg>
  </div>
  <div class="hero-body">
    <h2>Welcome, Admin 👋</h2>
  </div>
</section>

<section class="minicards">
  <div class="card"><p class="total">📘 Total Students : <?= $totalStudents ?></p></div>
  <div class="card"><p class="total">🛡️ Total Admins : <?= $totalAdmins ?></p></div>
  <div class="card"><p class="total">👥 Total Groups : <?= $totalGroups ?></p></div>
  <div class="card"><p class="total">📚 Total Levels : <?= $totallevels ?></p></div>
  <div class="card"><p class="total">👤 Total Users : <?= $totalusers ?></p></div>
</section>
<section class="cards">
  <div class="card">
    <a href="list_students.php"><h3>List Students</h3></a>
  </div>
  <div class="card">
    <a href="list_admins.php"><h3>List Admins</h3></a>
  </div>
  <div class="card">
    <a href="filieres.php"><h3>Gestion des filières</h3></a>
  </div>
  <div class="card">
    <a href="classes.php"><h3>Classes</h3></a>
  </div>
</section>
<!-- ===================== STATISTICS IN DASHBOARD ===================== -->
<section class="stats-section">
  <h2>📊 Statistics</h2>

  <div class="stats-grid">
    <div class="stats-card" style="grid-column:span 8;">
      <h3>Students by Year</h3>
      <canvas id="yearChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 4;">
      <h3>Students by Level</h3>
      <canvas id="levelChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 6;">
      <h3>Students by Group</h3>
      <canvas id="groupChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 3;">
      <h3>Gender</h3>
      <canvas id="genderChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 3;">
      <h3>Photos</h3>
      <canvas id="photoChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 3;">
      <h3>User Roles</h3>
      <canvas id="roleChart"></canvas>
    </div>

    <div class="stats-card" style="grid-column:span 3;">
      <h3>Age Groups</h3>
      <canvas id="ageChart"></canvas>
    </div>
  </div>
</section>



</main>

<script>
/* ================= NOTIFICATIONS COUNT ================= */
const messages = <?= json_encode($messages ?? []) ?>;
const notifCount = document.getElementById('notifCount');

if (notifCount) {
  if(messages.length > 0){
    notifCount.textContent = messages.length;
  } else {
    notifCount.style.display = "none";
  }
}

/* ================= BURGER MENU ================= */
const burger  = document.getElementById('burger');
const sidebar = document.getElementById('sidebar');
if (burger && sidebar) {
  burger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    burger.classList.toggle('open');
  });
}

/* ================= CHARTS ================= */
const opt = { responsive:true, plugins:{ legend:{ display:true } } };

new Chart(document.getElementById('yearChart'),{
  type:'line',
  data:{
    labels: <?= json_encode(array_column($years,'year')) ?>,
    datasets:[{ label:'Students', data: <?= json_encode(array_column($years,'total')) ?>, fill:true }]
  },
  options: opt
});

new Chart(document.getElementById('levelChart'),{
  type:'bar',
  data:{
    labels: <?= json_encode(array_column($levels,'nom')) ?>,
    datasets:[{ label:'Students', data: <?= json_encode(array_column($levels,'total')) ?> }]
  },
  options: opt
});

new Chart(document.getElementById('groupChart'),{
  type:'bar',
  data:{
    labels: <?= json_encode(array_column($groups,'groupe_label')) ?>,
    datasets:[{ label:'Students', data: <?= json_encode(array_column($groups,'total')) ?> }]
  },
  options: opt
});

new Chart(document.getElementById('genderChart'),{
  type:'pie',
  data:{
    labels: <?= json_encode(array_column($gender,'gender')) ?>,
    datasets:[{ label:'Students', data: <?= json_encode(array_column($gender,'total')) ?> }]
  },
  options: opt
});

new Chart(document.getElementById('photoChart'),{
  type:'doughnut',
  data:{
    labels:['Avec photo','Sans photo'],
    datasets:[{ label:'Students', data:[<?= $avec_photo ?>, <?= $sans_photo ?>] }]
  },
  options: opt
});

new Chart(document.getElementById('roleChart'),{
  type:'pie',
  data:{
    labels: <?= json_encode(array_column($roles,'role')) ?>,
    datasets:[{ label:'Users', data: <?= json_encode(array_column($roles,'total')) ?> }]
  },
  options: opt
});

new Chart(document.getElementById('ageChart'),{
  type:'bar',
  data:{
    labels: <?= json_encode(array_column($ages,'age_group')) ?>,
    datasets:[{ label:'Students', data: <?= json_encode(array_column($ages,'total')) ?> }]
  },
  options: opt
});
</script>

</body>
</html>
