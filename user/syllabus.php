<?php
session_start();
require('../connexion.php');

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Erreur connexion : " . $conn->connect_error);
$conn->set_charset("utf8mb4");

if(!isset($_SESSION['username'])){
  header("Location: ../first.php");
  exit;
}

/* =========================================================
   1) Récupérer l'étudiant + (optionnel) filiere_id dans students
   ========================================================= */
$stmt = $conn->prepare("
  SELECT 
    s.student_id, s.first_name, s.last_name,
    s.groupe_id,
    s.filiere_id,               /* ✅ si ta table students a filiere_id */
    u.id AS user_id
  FROM students s
  JOIN users u ON s.user_id = u.id
  WHERE u.username = ?
  LIMIT 1
");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if(!$student) die("Étudiant introuvable.");

$fullname = htmlspecialchars($student['first_name'].' '.$student['last_name'], ENT_QUOTES, 'UTF-8');
$groupe_id = (int)($student['groupe_id'] ?? 0);
$filiere_id_from_student = (int)($student['filiere_id'] ?? 0);

/* =========================================================
   2) Infos groupe + niveau (pour affichage)
   ========================================================= */
$groupName = "N/A";
$niveauName = "N/A";

if($groupe_id > 0){
  $stmtG = $conn->prepare("
    SELECT g.nom AS group_name, n.nom AS niveau_name
    FROM groupes g
    LEFT JOIN niveaux n ON g.niveau_id = n.id
    WHERE g.id = ?
    LIMIT 1
  ");
  $stmtG->bind_param("i", $groupe_id);
  $stmtG->execute();
  $gi = $stmtG->get_result()->fetch_assoc();
  if($gi){
    $groupName = $gi['group_name'] ?? "N/A";
    $niveauName = $gi['niveau_name'] ?? "N/A";
  }
}

/* =========================================================
   3) Trouver la filière
   - priorité: students.filiere_id
   - sinon: groupes.filiere_id (si tu l'as)
   ========================================================= */
/* =========================================================
   3) Trouver la filière (depuis le groupe)
   ========================================================= */

$filiere_id = 0;
$filiere = null;

/* 1) Si students.filiere_id existe et est rempli (optionnel) */
if (!empty($student['filiere_id'])) {
  $filiere_id = (int)$student['filiere_id'];
}

/* 2) Sinon récupérer la filière depuis groupes (ton cas) */
if ($filiere_id <= 0 && $groupe_id > 0) {

  // Détection colonne filière dans "groupes"
  $col = null;

  $test = $conn->query("SHOW COLUMNS FROM groupes LIKE 'filiere_id'");
  if ($test && $test->num_rows > 0) $col = "filiere_id";

  if (!$col) {
    $test = $conn->query("SHOW COLUMNS FROM groupes LIKE 'id_filiere'");
    if ($test && $test->num_rows > 0) $col = "id_filiere";
  }

  if ($col) {
    $sql = "SELECT $col AS filiere_id FROM groupes WHERE id=? LIMIT 1";
    $stmtGF = $conn->prepare($sql);
    $stmtGF->bind_param("i", $groupe_id);
    $stmtGF->execute();
    $row = $stmtGF->get_result()->fetch_assoc();
    $filiere_id = (int)($row['filiere_id'] ?? 0);
  }
}

/* Charger la filière */
if ($filiere_id > 0) {
  $stmtF = $conn->prepare("SELECT id, nom_filiere, description FROM filieres WHERE id=? LIMIT 1");
  $stmtF->bind_param("i", $filiere_id);
  $stmtF->execute();
  $filiere = $stmtF->get_result()->fetch_assoc();
}

/* =========================================================
   4) Récupérer les modules de la filière
   ========================================================= */
$modules = [];

if ($filiere_id > 0) {

  // Détecter la table modules : "modules" ou "module"
  $tableModules = null;

  $t = $conn->query("SHOW TABLES LIKE 'modules'");
  if ($t && $t->num_rows > 0) $tableModules = "modules";

  if (!$tableModules) {
    $t = $conn->query("SHOW TABLES LIKE 'module'");
    if ($t && $t->num_rows > 0) $tableModules = "module";
  }

  if ($tableModules) {

    // Détecter le nom de la colonne du nom module : nom_module ou nom
    $nameCol = "nom_module";
    $c = $conn->query("SHOW COLUMNS FROM $tableModules LIKE 'nom_module'");
    if (!$c || $c->num_rows === 0) $nameCol = "nom";

    // Détecter coefficient (optionnel)
    $coefExists = false;
    $c2 = $conn->query("SHOW COLUMNS FROM $tableModules LIKE 'coefficient'");
    if ($c2 && $c2->num_rows > 0) $coefExists = true;

    $sqlM = "
      SELECT id, $nameCol AS nom_module, description " . ($coefExists ? ", coefficient" : ", NULL AS coefficient") . "
      FROM $tableModules
      WHERE filiere_id = ?
      ORDER BY id DESC
    ";

    $stmtM = $conn->prepare($sqlM);
    $stmtM->bind_param("i", $filiere_id);
    $stmtM->execute();
    $modules = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
  }
}

/* =========================================================
   4) Récupérer les modules de la filière
   ========================================================= */
$modules = [];
if($filiere_id > 0){
    
  $stmtM = $conn->prepare("
    SELECT id, nom_module, description, coefficient
    FROM modules
    WHERE filiere_id = ?
    ORDER BY id DESC
  ");
  $stmtM->bind_param("i", $filiere_id);
  $stmtM->execute();
  $modules = $stmtM->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Syllabus - <?= $fullname ?></title>

<style>
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --white:#fff;
  --text:#222;
  --muted:#6c757d;
  --border:#e6e9f0;
  --radius:18px;
  --shadow:0 14px 34px rgba(0,0,0,.10);
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:"Poppins",system-ui,-apple-system,Segoe UI,sans-serif;background:var(--bg);color:var(--text)}

header{
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:18px 22px;
  color:#fff;
}
header .wrap{
  max-width:1100px;margin:0 auto;
  display:flex;justify-content:space-between;align-items:center;gap:12px;
}
header h1{margin:0;font-size:22px}
header a{
  color:#fff;text-decoration:none;font-weight:700;
  background:rgba(255,255,255,.15);
  border:1px solid rgba(255,255,255,.22);
  padding:8px 12px;border-radius:999px;
}

.container{
  max-width:1100px;
  margin:22px auto;
  padding:0 18px;
}

.hero{
  background:#fff;
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:18px;
  display:flex;
  justify-content:space-between;
  gap:16px;
  align-items:flex-start;
}
.hero h2{margin:0;color:var(--secondary);font-size:20px}
.hero p{margin:6px 0 0;color:var(--muted);font-size:14px;line-height:1.55}
.badges{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.badge{
  display:inline-flex;gap:8px;align-items:center;
  background:#f2f5ff;border:1px solid #e2e8ff;
  color:#2445b5;
  padding:6px 10px;border-radius:999px;
  font-size:12px;font-weight:700;
}
.badge.gray{background:#f6f7f9;border:1px solid #eceff3;color:#39404d}

.section-title{
  margin:18px 2px 10px;
  color:var(--secondary);
  font-size:16px;
  font-weight:800;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:16px;
}

.card{
  background:#fff;
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.card .head{
  padding:14px 16px;
  border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;align-items:flex-start;gap:10px;
}
.card h3{
  margin:0;
  font-size:15px;
  color:var(--secondary);
  font-weight:800;
}
.pill{
  font-size:12px;
  color:var(--muted);
  background:#f6f7f9;
  border:1px solid #eceff3;
  padding:4px 10px;border-radius:999px;
  white-space:nowrap;
}
.card .body{padding:14px 16px}
.card .body p{margin:0;color:#2a2f3a;font-size:14px;line-height:1.6;white-space:pre-wrap}
.meta{
  margin-top:10px;
  color:var(--muted);
  font-size:13px;
  display:flex;gap:10px;flex-wrap:wrap;
}

.empty{
  background:#fff;
  border:1px dashed #cfd6e4;
  border-radius:var(--radius);
  padding:18px;
  color:var(--muted);
}

@media(max-width:560px){
  header h1{font-size:18px}
}
</style>
</head>

<body>

<header>
  <div class="wrap">
    <h1>Syllabus 📚</h1>
    <a href="userdashboard.php">⬅ Dashboard</a>
  </div>
</header>

<div class="container">

  <div class="hero">
    <div>
      <h2><?= $fullname ?></h2>
      <p>
        <strong>Classe :</strong> <?= htmlspecialchars($groupName) ?> • <?= htmlspecialchars($niveauName) ?><br>
        <strong>Filière :</strong>
        <?php if($filiere): ?>
          <?= htmlspecialchars($filiere['nom_filiere']) ?>
        <?php else: ?>
          <span style="color:var(--muted)">Non définie</span>
        <?php endif; ?>
      </p>

      <div class="badges">
        <span class="badge gray">👥 <?= htmlspecialchars($groupName) ?></span>
        <span class="badge gray">🎓 <?= htmlspecialchars($niveauName) ?></span>
        <?php if($filiere): ?>
          <span class="badge">📘 <?= htmlspecialchars($filiere['nom_filiere']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div style="text-align:right">
      <div class="badge gray">Modules: <strong><?= (int)count($modules) ?></strong></div>
    </div>
  </div>

  <div class="section-title">📘 Ma filière</div>
  <?php if($filiere): ?>
    <div class="card">
      <div class="head">
        <h3><?= htmlspecialchars($filiere['nom_filiere']) ?></h3>
        <span class="pill">ID #<?= (int)$filiere['id'] ?></span>
      </div>
      <div class="body">
        <p><?= htmlspecialchars($filiere['description'] ?? '') ?></p>
      </div>
    </div>
  <?php else: ?>
    <div class="empty">
      Aucune filière n’est associée à ton compte .
    </div>
  <?php endif; ?>

  <div class="section-title">🧩 Mes modules</div>

  <?php if($filiere && count($modules) > 0): ?>
    <div class="grid">
      <?php foreach($modules as $m): ?>
        <div class="card">
          <div class="head">
            <h3><?= htmlspecialchars($m['nom_module']) ?></h3>
            <span class="pill">#<?= (int)$m['id'] ?></span>
          </div>
          <div class="body">
            <p><?= htmlspecialchars($m['description'] ?? '') ?></p>
            <div class="meta">
              <span>⚖️ Coef: <strong><?= ($m['coefficient'] === null || $m['coefficient'] === '') ? "—" : htmlspecialchars((string)$m['coefficient']) ?></strong></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif($filiere): ?>
    <div class="empty">
      Aucun module n’est encore ajouté pour cette filière.
    </div>
  <?php endif; ?>

</div>

</body>
</html>
