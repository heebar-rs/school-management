<?php
session_start();
require('../connexion.php');

if(!isset($_SESSION['username'])){
  header("Location: ../first.php");
  exit;
}

$conn = new mysqli($host,$user,$pass,$dbname);
if($conn->connect_error) die("Erreur connexion");

$filieres = $conn->query("SELECT id, nom_filiere, description FROM filieres ORDER BY id DESC");
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Programmes - Loop Academy</title>
<link rel="stylesheet" href="../student/school.css">

<style>
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --card:#fff;
  --text:#222;
  --muted:#6c757d;
  --border:rgba(0,0,0,.07);
  --shadow:0 14px 34px rgba(0,0,0,.10);
  --radius:20px;
}

*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{
  font-family:Poppins,sans-serif;
  color:var(--text);
  background:
    radial-gradient(1100px 500px at 15% 0%, rgba(13,110,253,.10), transparent 55%),
    radial-gradient(900px 450px at 100% 20%, rgba(36,51,82,.10), transparent 60%),
    linear-gradient(180deg, #f7f9ff 0%, #f4f6fb 100%);
}

/* Sidebar student */
.sidebar{
  position:fixed;left:-240px;top:0;width:240px;height:100vh;
  background:var(--secondary);padding:20px;color:white;
  display:flex;flex-direction:column;gap:14px;
  transition:left .25s ease;z-index:200;
  box-shadow:5px 0 15px rgba(0,0,0,0.3);
}
.sidebar.open{left:0;}
.sidebar a{color:#fff;text-decoration:none;padding:10px 12px;border-radius:10px;transition:.2s;}
.sidebar a:hover{background:rgba(255,255,255,0.1);}

#burger{
  position:fixed;top:15px;left:15px;z-index:300;font-size:28px;
  background:#94979bff;border:none;border-radius:8px;cursor:pointer;
  line-height:1;padding:6px 10px;box-shadow:0 4px 10px rgba(0,0,0,.15);
}
.site-main{transition:margin-left .25s;padding-top:90px;}
.sidebar.open ~ .site-main{margin-left:240px;}

/* Header */
header{
  position:fixed;top:0;left:0;width:100%;
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:18px 40px;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  z-index:150;
}
header h1{margin:0;font-size:22px;margin-left:55px;}
header a.btn{
  background:#fff;color:var(--secondary);
  padding:7px 18px;border-radius:999px;text-decoration:none;font-weight:600;
}

/* Container */
.container{max-width:1200px;margin:0 auto;padding:0 18px;}
.top{
  display:flex;justify-content:space-between;align-items:end;gap:12px;
  margin:20px 0;
}
.top h2{margin:0;color:var(--secondary);font-size:22px;}
.top p{margin:6px 0 0;color:var(--muted);}

/* Cards */
.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:18px;
  padding-bottom:40px;
}
.card{
  background:rgba(255,255,255,.92);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:18px 18px 16px;
  box-shadow:var(--shadow);
  transition:.22s ease;
  overflow:hidden;
}
.card:hover{transform:translateY(-6px);box-shadow:0 22px 50px rgba(0,0,0,.14);}
.card .title{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
  margin-bottom:10px;
}

.card h3{margin:0;font-size:16px;color:var(--secondary);}
.card p{margin:0;color:#445;line-height:1.55;font-size:14px;}
.empty{
  background:#fff;border:1px dashed rgba(0,0,0,.15);
  border-radius:var(--radius);padding:28px;text-align:center;color:var(--muted);
}
</style>
</head>
<body>

<button id="burger" aria-label="Toggle sidebar">☰</button>

<aside id="sidebar" class="sidebar">
  <h2> </h2>
  <a href="userdashboard.php">Dashboard</a>
  <a href="syllabus.php">Syllabus</a>
  <a href="filieres_view.php">Academy programms</a>
  <a href="../first.php">Logout</a>
</aside>

<main class="site-main">
  <header>
    <h1>Loop Academy • Programmes</h1>
    <a class="btn" href="userdashboard.php">← Retour</a>
  </header>

  <div class="container">
    <div class="top">
      <div>
        <h2>programms</h2>
     
      </div>
    </div>

    <?php if($filieres && $filieres->num_rows > 0): ?>
      <div class="grid">
        <?php while($f = $filieres->fetch_assoc()): ?>
          <div class="card">
            <div class="title">
              <h3><?= htmlspecialchars($f['nom_filiere']) ?></h3>
             
            </div>
            <p><?= nl2br(htmlspecialchars($f['description'])) ?></p>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty">Aucune filière pour le moment.</div>
    <?php endif; ?>
  </div>
</main>

<script>
const burger = document.getElementById('burger');
const sidebar = document.getElementById('sidebar');
burger.addEventListener('click', ()=>{
  sidebar.classList.toggle('open');
});
</script>
</body>
</html>
