<?php
session_start();

require("../connexion.php");

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}

/* Étudiants par niveau */
$levels = $conn->query("
SELECT niveaux.nom, COUNT(students.student_id) total
FROM niveaux
LEFT JOIN groupes ON groupes.niveau_id = niveaux.id
LEFT JOIN students ON students.groupe_id = groupes.id
GROUP BY niveaux.nom
")->fetch_all(MYSQLI_ASSOC);

/* Étudiants par groupe */
$groups = $conn->query("
SELECT CONCAT(groupes.nom,' - ',niveaux.nom) groupe_label,
COUNT(students.student_id) total
FROM groupes
JOIN niveaux ON niveaux.id = groupes.niveau_id
LEFT JOIN students ON students.groupe_id = groupes.id
GROUP BY groupes.id
ORDER BY niveaux.nom, groupes.nom
")->fetch_all(MYSQLI_ASSOC);

/* Genre */
$gender = $conn->query("
SELECT gender, COUNT(*) total
FROM students
GROUP BY gender
")->fetch_all(MYSQLI_ASSOC);

/* Photos */
$photos = $conn->query("
SELECT 
SUM(photo IS NOT NULL) avec_photo,
SUM(photo IS NULL) sans_photo
FROM students
")->fetch_assoc();

/* Rôles */
$roles = $conn->query("
SELECT role, COUNT(*) total
FROM users
GROUP BY role
")->fetch_all(MYSQLI_ASSOC);

/* Tranches d'âge */
$ages = $conn->query("
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
$years = $conn->query("
SELECT YEAR(birth_date) year, COUNT(*) total
FROM students
GROUP BY year
ORDER BY year
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Statistics</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root{
 --primary:#0d6efd;
 --secondary:#243352;
 --bg:#f4f6fb;
 --card:#fff;
}
body{
    
 margin:0;
 font-family:Poppins,sans-serif;
 background:var(--bg);
}

/* HEADER */
header{
 height:70px;
 padding:0 40px;
 background:linear-gradient(135deg,var(--secondary),var(--primary));
 color:#fff;
 display:flex;
 align-items:center;
 justify-content:space-between;
 position:fixed;
 top:0;
 width:100%;
 z-index:200;
}
header .title{
 font-size:24px;
 font-weight:600;
 margin-left:80px;
}
.logout{
    margin-right: 60px;
}
header .logout{
 color:#fff;
 text-decoration:none;
 padding:6px 18px;
 border-radius:20px;
 background:rgba(255,255,255,.15);
 font-weight:600;
}
header .logout:hover{background:rgba(255,255,255,.25);}

/* SIDEBAR */
.sidebar{
 position:fixed;
 left:-240px;
 top:0;
 width:240px;
 height:100vh;
 background:var(--secondary);
 padding:20px;
 color:white;
 display:flex;
 flex-direction:column;
 gap:25px;
 transition:.3s;
 z-index:300;
}
.sidebar.open{left:0;}
.sidebar a{color:#fff;text-decoration:none;padding:8px;border-radius:8px;}
.sidebar a:hover{background:rgba(255,255,255,.1);}

/* BURGER */
#burger{
 position:fixed;
 top:15px;
 left:15px;
 z-index:400;
 font-size:26px;
 background:#94979b;
 border:none;
 border-radius:6px;
 cursor:pointer;
}

/* MAIN */
.site-main{
 padding-top:90px;
 transition:.3s;
}
.sidebar.open ~ .site-main{margin-left:240px;}

/* CARDS */
.cards{
 display:grid;
 grid-template-columns:repeat(12,1fr);
 grid-auto-rows:280px;
 gap:40px;
 margin:0 50px;
}
.card{
 background:#fff;
 border-radius:20px;
 padding:20px;
 box-shadow:0 15px 30px rgba(0,0,0,.1);
}
.card h3{margin:0 0 10px;color:var(--secondary);}
.big{grid-column:span 8;grid-row:span 2;}
.medium{grid-column:span 4;}
.small{grid-column:span 3;}

@media(max-width:1000px){
 .big,.medium,.small{grid-column:span 12;}
}
</style>
</head>

<body>

<button id="burger">☰</button>

<aside id="sidebar" class="sidebar">


 <h2>  </h2>
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
 <h2 class="title">Loop Academy — Statistics</h2>
 <a href="../first.php" class="logout">Logout</a>
</header>

<div class="cards">

<div class="card big">
 <h3>Students by Year</h3>
 <canvas id="yearChart"></canvas>
</div>

<div class="card medium">
 <h3>Students by Level</h3>
 <canvas id="levelChart"></canvas>
</div>

<div class="card medium">
 <h3>Students by Group</h3>
 <canvas id="groupChart"></canvas>
</div>

<div class="card small">
 <h3>Gender</h3>
 <canvas id="genderChart"></canvas>
</div>

<div class="card small">
 <h3>Photos</h3>
 <canvas id="photoChart"></canvas>
</div>

<div class="card small">
 <h3>User Roles</h3>
 <canvas id="roleChart"></canvas>
</div>

<div class="card small">
 <h3>Age Groups</h3>
 <canvas id="ageChart"></canvas>
</div>

</div>
</main>

<script>
const burger=document.getElementById('burger');//Récupère l’élément HTML qui a : id= burger Stocké dans la variable burger
const sidebar=document.getElementById('sidebar');
burger.onclick=()=>sidebar.classList.toggle('open');//toggle = ajoute ou enlève la classe open si open existe → elle est retirée, sinon elle est ajoutée.

const opt={
 responsive:true,
 plugins:{legend:{display:true}}//legend=students,users..
};
//students par année
new Chart(yearChart,{//new Chart(yearChart,{ 
  // Crée un nouveau graphique Chart.js
  // yearChart est l’élément <canvas> où le graphique sera affiché

 type:'line',//  // Définit le type du graphique
  // 'line' = graphique en courbe (évolution dans le temps)

 data:{// // Objet qui contient toutes les données du graphique
  labels:<?=json_encode(array_column($years,'year'))?>,
  //  // labels = valeurs affichées sur l’axe horizontal (X)
    // PHP :
    // - $years : résultat SQL
    // - array_column(...,'year') : extrait la colonne "year"
    // - json_encode(...) : transforme le tableau PHP en tableau JavaScript
    // Exemple final JS : [2020, 2021, 2022, 2023]

  datasets:[{label:'Students',data:<?=json_encode(array_column($years,'total'))?>,fill:true}]//xtrait la colonne "total" (nombre d’étudiants)
 // datasets = liste des séries de données à afficher

     
 },options:opt
});
// Étudiants par niveau
new Chart(levelChart,{
 type:'bar',
 data:{
  labels:<?=json_encode(array_column($levels,'nom'))?>,
  datasets:[{label:'Students ',data:<?=json_encode(array_column($levels,'total'))?>}]
 },options:opt
});
// Étudiants par groupe
new Chart(groupChart,{
 type:'bar',
 data:{
  labels:<?=json_encode(array_column($groups,'groupe_label'))?>,
  datasets:[{label:'Students',data:<?=json_encode(array_column($groups,'total'))?>}]
 },options:opt
});
// Genre
new Chart(genderChart,{
 type:'pie',
 data:{
  labels:<?=json_encode(array_column($gender,'gender'))?>,
  datasets:[{label:'Students',data:<?=json_encode(array_column($gender,'total'))?>}]
 },options:opt
});
// Photos ou non
new Chart(photoChart,{
 type:'doughnut',
 data:{
  labels:['Avec photo','Sans photo'],
  datasets:[{label:'Students',data:[<?=$photos['avec_photo']?>,<?=$photos['sans_photo']?>]}]
 },options:opt
});
// Rôles
new Chart(roleChart,{
 type:'pie',
 data:{
  labels:<?=json_encode(array_column($roles,'role'))?>,
  datasets:[{label:'Users',data:<?=json_encode(array_column($roles,'total'))?>}]
 },options:opt
});
// Tranches d'âge
new Chart(ageChart,{
 type:'bar',
 data:{
  labels:<?=json_encode(array_column($ages,'age_group'))?>,
  datasets:[{label:'Students',data:<?=json_encode(array_column($ages,'total'))?>}]
 },options:opt
});
//📈 line → évolution annuelle

//📊 bar → niveaux / groupes / âges

//🥧 pie → genre / rôles

//🍩 doughnut → photos
</script>

</body>
</html> 