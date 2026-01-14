<?php
session_start();
require('../connexion.php');

$connexion = new mysqli($host,$user,$pass,$dbname);
if ($connexion->connect_error) die("Erreur connexion : ".$connexion->connect_error);
$connexion->set_charset("utf8mb4");

/* ================= TRAITEMENT AJAX (CRUD) ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){

  function out($txt){ echo $txt; exit; }

  /* ADD */
  if($_POST['action']==='add'){
    $nom       = trim($_POST['nom'] ?? '');
    $niveau_id = (int)($_POST['niveau_id'] ?? 0);
    $filiere_id= (int)($_POST['filiere_id'] ?? 0);

    if($nom==='' || $niveau_id<=0 || $filiere_id<=0){
      out("error:missing_fields");
    }

    $stmt = $connexion->prepare("INSERT INTO groupes(nom,niveau_id,filiere_id) VALUES(?,?,?)");
    if(!$stmt) out("error:prepare:".$connexion->error);

    $stmt->bind_param("sii", $nom, $niveau_id, $filiere_id);

    if($stmt->execute()){
      out("success");
    }else{
      out("error:execute:".$stmt->error);
    }
  }

  /* MODIFY */
  if($_POST['action']==='modify'){
    $id        = (int)($_POST['id'] ?? 0);
    $nom       = trim($_POST['nom'] ?? '');
    $niveau_id = (int)($_POST['niveau_id'] ?? 0);
    $filiere_id= (int)($_POST['filiere_id'] ?? 0);

    if($id<=0 || $nom==='' || $niveau_id<=0 || $filiere_id<=0){
      out("error:missing_fields");
    }

    $stmt = $connexion->prepare("UPDATE groupes SET nom=?, niveau_id=?, filiere_id=? WHERE id=?");
    if(!$stmt) out("error:prepare:".$connexion->error);

    $stmt->bind_param("siii", $nom, $niveau_id, $filiere_id, $id);

    if($stmt->execute()){
      out("success");
    }else{
      out("error:execute:".$stmt->error);
    }
  }

  /* DELETE */
  if($_POST['action']==='delete'){
    $id = (int)($_POST['id'] ?? 0);
    if($id<=0) out("error:missing_fields");

    $stmt = $connexion->prepare("DELETE FROM groupes WHERE id=?");
    if(!$stmt) out("error:prepare:".$connexion->error);

    $stmt->bind_param("i",$id);

    if($stmt->execute()){
      out("success");
    }else{
      out("error:execute:".$stmt->error);
    }
  }

  /* ASSIGN STUDENTS */
  if($_POST['action']==='assign'){
    $gid = (int)($_POST['groupe_id'] ?? 0);
    if($gid<=0) out("error:missing_fields");

    // retirer anciens étudiants du groupe
    if(!$connexion->query("UPDATE students SET groupe_id=NULL WHERE groupe_id=".$gid)){
      out("error:execute:".$connexion->error);
    }

    if(!empty($_POST['students']) && is_array($_POST['students'])){
      $stmt = $connexion->prepare("UPDATE students SET groupe_id=? WHERE student_id=?");
      if(!$stmt) out("error:prepare:".$connexion->error);

      foreach($_POST['students'] as $sid){
        $sid = (int)$sid;
        if($sid>0){
          $stmt->bind_param("ii", $gid, $sid);
          if(!$stmt->execute()){
            out("error:execute:".$stmt->error);
          }
        }
      }
    }

    out("success");
  }

  out("error:unknown_action");
}

/* ================= LECTURE ================= */

/* Niveaux (select) */
$niveauxRes = $connexion->query("SELECT id, nom FROM niveaux ORDER BY nom");
$niveaux = [];
while($n = $niveauxRes->fetch_assoc()){
  $niveaux[] = $n;
}

/* Filières (select) */
$filieresRes = $connexion->query("SELECT id, nom_filiere FROM filieres ORDER BY nom_filiere");
$filieres = [];
while($f = $filieresRes->fetch_assoc()){
  $filieres[] = $f;
}

/* Pagination */
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

$totalRes = $connexion->query("SELECT COUNT(*) AS total FROM groupes");
$totalGroups = (int)($totalRes->fetch_assoc()['total'] ?? 0);
$totalPages = (int)ceil($totalGroups / $limit);

/* Groupes paginés (avec niveau + filière) */
$stmt = $connexion->prepare("
  SELECT
    g.id AS groupe_id, g.nom AS groupe_nom,
    n.id AS niveau_id, n.nom AS niveau_nom,
    f.id AS filiere_id, f.nom_filiere AS filiere_nom
  FROM groupes g
  LEFT JOIN niveaux n  ON g.niveau_id = n.id
  LEFT JOIN filieres f ON g.filiere_id = f.id
  ORDER BY n.nom, f.nom_filiere, g.nom
  LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$groupes = $stmt->get_result();

/* Students (pour assign) */
$students = $connexion->query("
  SELECT student_id,last_name,first_name
  FROM students
  WHERE groupe_id IS NULL
  ORDER BY last_name,first_name
");

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Classes</title>

<style>
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --card-bg:#fff;
  --text:#333;
}

*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{margin:0;font-family:"Poppins",sans-serif;background:var(--bg);color:var(--text);}

/* Header */
header{
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:20px 40px;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
  width:100%;z-index:200;
}
header h1{margin:0;font-size:26px;}
header nav a.btn{
  background:#fff;color:var(--secondary);
  padding:6px 20px;border-radius:25px;text-decoration:none;
}
header nav a.btn:hover{opacity:.9;}

/* Sidebar */
.sidebar{
  position:fixed;left:-240px;top:0;width:240px;height:100vh;
  background:var(--secondary);padding:20px;color:white;
  display:flex;flex-direction:column;gap:25px;
  transition:left .3s ease;z-index:300;
  box-shadow:5px 0 15px rgba(0,0,0,.3);
}
.sidebar.open{left:0;}
.sidebar a{
  color:#fff;text-decoration:none;padding:8px 0;border-radius:8px;transition:.3s;
}
.sidebar a:hover{background:rgba(255,255,255,.1);}

/* Burger */
#burger{
  position:fixed;top:15px;left:15px;z-index:400;
  font-size:28px;background-color:#94979bff;border:none;border-radius:6px;
  cursor:pointer;line-height:1;box-shadow:0 4px 8px rgba(0,0,0,.2);
}
.site-main{margin-left:0;transition:margin-left .3s;}
.sidebar.open ~ .site-main{margin-left:240px;}

/* Top */
.topbar{
  width:70%;
  margin:110px auto 10px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
}
.topbar h2{margin:0}
.open-add{
  background:var(--primary);
  color:#fff;
  border:none;
  padding:10px 14px;
  border-radius:12px;
  font-weight:700;
  cursor:pointer;
}
.open-add:hover{filter:brightness(.96);transform:translateY(-1px);}

/* Table */
table{
  margin:20px auto;width:70%;
  border-collapse:collapse;background:var(--card-bg);
  border-radius:12px;overflow:hidden;
  box-shadow:0 15px 30px rgba(0,0,0,.1);
}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;vertical-align:top;}
th{background:var(--secondary);color:#fff;}
tr:hover{background:rgba(0,0,0,.05);}
td .pill{
  display:inline-block;
  padding:4px 10px;
  border-radius:999px;
  background:#eef2ff;
  border:1px solid #dbe5ff;
  color:#213a9a;
  font-size:12px;
  font-weight:700;
}

/* Pagination */
.page-btn{
  padding:6px 12px;margin-right:5px;border-radius:6px;background:#e0e0e0;
  text-decoration:none;color:#243352;font-weight:600;transition:.3s;
}
.page-btn:hover{background:#0d6efd;color:white;}
.page-btn.active{background:#0d6efd;color:white;}

/* Buttons in table */
button{
  cursor:pointer;border:none;padding:8px 12px;border-radius:10px;
  transition:.2s;font-weight:700;
}
button:hover{opacity:.92; transform:translateY(-1px);}
.btn-soft{background:#eef1f7;color:#1f2b3e;}
.btn-edit{background:#f1f5ff;border:1px solid #dbe5ff;color:#2445b5;}
.btn-del{background:#ffecef;border:1px solid #ffd0d8;color:#b00020;}
.btn-primary{
  background:linear-gradient(135deg,var(--primary),#6f42c1);
  color:#fff;
}
.actions{display:flex;gap:10px;flex-wrap:wrap;}

/* ===== MODALS (STYLE HOMOGÈNE) ===== */
#overlay{
  position:fixed; inset:0;
  background: rgba(10,15,25,.55);
  backdrop-filter: blur(8px);
  opacity:0; pointer-events:none;
  transition:.25s ease;
  z-index:500;
}
#overlay.active{opacity:1; pointer-events:auto;}

.modal{
  position:fixed;
  top:50%; left:50%;
  transform: translate(-50%,-55%) scale(.98);
  width: 520px;
  max-width: 94vw;
  background: rgba(255,255,255,.92);
  border: 1px solid rgba(255,255,255,.35);
  border-radius: 22px;
  box-shadow: 0 40px 110px rgba(0,0,0,.28);
  overflow:hidden;
  opacity:0; pointer-events:none;
  transition: .22s ease;
  z-index:600;
}
.modal.active{
  opacity:1; pointer-events:auto;
  transform: translate(-50%,-50%) scale(1);
}

.modal-head{
  padding: 14px 16px;
  background: linear-gradient(135deg, rgba(36,51,82,.96), rgba(13,110,253,.86));
  color:#fff;
  display:flex; align-items:center; justify-content:space-between;
}
.modal-head h3{
  margin:0;font-size:16px;font-weight:800;
  display:flex; align-items:center; gap:10px;
}
.modal-close{
  width:38px;height:38px;border:none;border-radius:12px;cursor:pointer;color:#fff;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.20);
  transition:.15s;
}
.modal-close:hover{background: rgba(255,255,255,.26); transform: translateY(-1px);}

.modal-body{ padding: 16px; }
.modal .hint{
  font-size:12px;color: rgba(0,0,0,.55);margin: 0 0 12px;
}
.modal label{
  display:block;font-weight:800;font-size:13px;color:var(--secondary);
  margin: 10px 0 6px;
}
.modal input, .modal select{
  width:100%;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(0,0,0,.10);
  background: rgba(248,249,252,.95);
  outline:none;
  transition:.15s;
}
.modal input:focus, .modal select:focus{
  background:#fff;
  border-color: rgba(13,110,253,.50);
  box-shadow: 0 0 0 3px rgba(13,110,253,.14);
}
.modal-foot{
  padding: 14px 16px;
  background: rgba(255,255,255,.85);
  border-top: 1px solid rgba(0,0,0,.06);
  display:flex; gap:10px; justify-content:flex-end;
}
.btn{
  border:none; cursor:pointer; border-radius:999px;
  padding:10px 16px; font-weight:900; transition:.15s;
}
.btn:hover{ transform: translateY(-1px); }
.btn-light{ background:#eef1f7; color:#1f2b3e; }
.btn-danger{
  background: linear-gradient(135deg, #dc3545, #ff6b6b);
  color:#fff;
  box-shadow: 0 12px 22px rgba(220,53,69,.18);
}
.btn-action{
  background: linear-gradient(135deg, var(--primary), #6f42c1);
  color:#fff;
  box-shadow: 0 12px 22px rgba(13,110,253,.18);
}

/* Assign list */
.assign-box{
  max-height: 260px;
  overflow:auto;
  padding: 10px;
  border-radius: 14px;
  border: 1px solid rgba(0,0,0,.08);
  background: rgba(248,249,252,.95);
}
.assign-box::-webkit-scrollbar{ width:6px; }
.assign-box::-webkit-scrollbar-thumb{ background: rgba(0,0,0,.18); border-radius:999px; }
.assign-item{
  display:flex; gap:10px; align-items:center;
  padding: 8px 10px;
  border-radius: 12px;
}
.assign-item:hover{ background: rgba(13,110,253,.06); }
.assign-item input{ width:auto; margin:0; }

@media (max-width: 900px){
  table, .topbar{ width:92%; }
}
</style>
</head>

<body>

<button id="burger">☰</button>

<aside id="sidebar" class="sidebar">
  <h2></h2>
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
  <nav style="margin-left:60px"><h1>Loop Academy</h1></nav>
  <nav style="margin-right:60px">
    <a href="admindashboard.php" class="btn">Dashboard</a>
    <a href="../first.php" class="btn">Logout</a>
  </nav>
</header>

<div class="topbar">
  <h2>Classes</h2>
  <button class="open-add" type="button">➕ Add Group</button>
</div>

<table>
  <tr>
    <th style="width:170px">Year</th>
    <th>Group</th>
    <th style="width:260px">Actions</th>
  </tr>

  <?php while($g=$groupes->fetch_assoc()): ?>
  <tr>
    <td>
      <?= htmlspecialchars($g['niveau_nom'] ?? 'N/A') ?><br>
      <span class="pill"><?= htmlspecialchars($g['filiere_nom'] ?? 'No filiere') ?></span>
    </td>

    <td>
      <strong><?= htmlspecialchars($g['groupe_nom']) ?></strong><br><br>

      <!-- ✅ NE PAS ENLEVER -->
      <button class="btn-primary open-assign"
        data-id="<?= (int)$g['groupe_id'] ?>"
        data-nom="<?= htmlspecialchars($g['groupe_nom'], ENT_QUOTES) ?>">
        Affect students
      </button>

      <!-- ✅ NE PAS ENLEVER -->
      <button class="btn-soft"
        onclick="window.location.href='liste_par_group.php?groupe_id=<?= (int)$g['groupe_id'] ?>'">
        List of students
      </button>
    </td>

    <td>
      <div class="actions">
        <button class="btn-edit open-modify"
          data-id="<?= (int)$g['groupe_id'] ?>"
          data-nom="<?= htmlspecialchars($g['groupe_nom'], ENT_QUOTES) ?>"
          data-niveau="<?= (int)($g['niveau_id'] ?? 0) ?>"
          data-filiere="<?= (int)($g['filiere_id'] ?? 0) ?>">
          Modify
        </button>

        <button class="btn-del open-delete" data-id="<?= (int)$g['groupe_id'] ?>">
          Delete
        </button>
      </div>
    </td>
  </tr>
  <?php endwhile; ?>
</table>

<div style="width:70%;margin:20px auto;">
<?php if($totalPages > 1): ?>
  <?php if($page > 1): ?>
    <a href="?page=<?= $page-1 ?>" class="page-btn">« Prev</a>
  <?php endif; ?>

  <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>" class="page-btn <?= ($i==$page)?'active':'' ?>"><?= $i ?></a>
  <?php endfor; ?>

  <?php if($page < $totalPages): ?>
    <a href="?page=<?= $page+1 ?>" class="page-btn">Next »</a>
  <?php endif; ?>
<?php endif; ?>
</div>

</main>

<div id="overlay"></div>

<!-- ===================== ADD MODAL ===================== -->
<div id="modalAdd" class="modal">
  <div class="modal-head">
    <h3>➕ Add Group</h3>
    <button type="button" class="modal-close close-modal">✕</button>
  </div>

  <div class="modal-body">
   
    <form id="formAdd">
      <label>Group name</label>
      <input name="nom" placeholder="Ex: G2" required>

      <label>Level</label>
      <select name="niveau_id" required>
        <option value="">-- Select level --</option>
        <?php foreach($niveaux as $n): ?>
          <option value="<?= (int)$n['id'] ?>"><?= htmlspecialchars($n['nom']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Filière</label>
      <select name="filiere_id" required>
        <option value="">-- Select filiere --</option>
        <?php foreach($filieres as $f): ?>
          <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nom_filiere']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="modal-foot">
        <button type="button" class="btn btn-light close-modal">Close</button>
        <button type="submit" class="btn btn-action">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- ===================== MODIFY MODAL ===================== -->
<div id="modalModify" class="modal">
  <div class="modal-head">
    <h3>✏️ Modify Group</h3>
    <button type="button" class="modal-close close-modal">✕</button>
  </div>

  <div class="modal-body">
    <form id="formModify">
      <input type="hidden" name="id" id="mod_id">

      <label>Group name</label>
      <input name="nom" id="mod_nom" required>

      <label>Level</label>
      <select name="niveau_id" id="mod_niveau" required>
        <option value="">-- Select level --</option>
        <?php foreach($niveaux as $n): ?>
          <option value="<?= (int)$n['id'] ?>"><?= htmlspecialchars($n['nom']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Filière</label>
      <select name="filiere_id" id="mod_filiere" required>
        <option value="">-- Select filiere --</option>
        <?php foreach($filieres as $f): ?>
          <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nom_filiere']) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="modal-foot">
        <button type="button" class="btn btn-light close-modal">Close</button>
        <button type="submit" class="btn btn-action">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- ===================== DELETE MODAL ===================== -->
<div id="modalDelete" class="modal">
  <div class="modal-head">
    <h3>🗑️ Delete Group</h3>
    <button type="button" class="modal-close close-modal">✕</button>
  </div>

  <div class="modal-body">
    <form id="formDelete">
      <input type="hidden" name="id" id="del_id">
      <p class="hint">Cette action est irréversible.</p>

      <div class="modal-foot">
        <button type="button" class="btn btn-light close-modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<!-- ===================== ASSIGN MODAL ===================== -->
<div id="modalAssign" class="modal">
  <div class="modal-head">
    <h3>👥 Affect students</h3>
    <button type="button" class="modal-close close-modal">✕</button>
  </div>

  <div class="modal-body">
    <form id="formAssign">
      <input type="hidden" name="groupe_id" id="assign_id">

      <div class="assign-box">
        <?php while($s=$students->fetch_assoc()): ?>
          <label class="assign-item">
            <input type="checkbox" name="students[]" value="<?= (int)$s['student_id'] ?>">
            <?= htmlspecialchars($s['last_name']." ".$s['first_name']) ?>
          </label>
        <?php endwhile; ?>
      </div>

      <div class="modal-foot">
        <button type="button" class="btn btn-light close-modal">Close</button>
        <button type="submit" class="btn btn-action">Affect</button>
      </div>
    </form>
  </div>
</div>

<script>
/* ================= BURGER ================= */
const burger = document.getElementById('burger');
const sidebar = document.getElementById('sidebar');
burger.onclick = () => sidebar.classList.toggle('open');

/* ================= MODALS ================= */
const overlay = document.getElementById('overlay');

const modalAdd    = document.getElementById("modalAdd");
const modalModify = document.getElementById("modalModify");
const modalDelete = document.getElementById("modalDelete");
const modalAssign = document.getElementById("modalAssign");

function openModal(modal){
  modal.classList.add("active");
  overlay.classList.add("active");
}
function closeAll(){
  document.querySelectorAll(".modal.active").forEach(m => m.classList.remove("active"));
  overlay.classList.remove("active");
}
overlay.onclick = closeAll;
document.querySelectorAll(".close-modal").forEach(b=> b.onclick = closeAll);

document.addEventListener("keydown",(e)=>{
  if(e.key === "Escape") closeAll();
});

/* ================= FORMS ================= */
const formAdd    = document.getElementById("formAdd");
const formModify = document.getElementById("formModify");
const formDelete = document.getElementById("formDelete");
const formAssign = document.getElementById("formAssign");

const mod_id      = document.getElementById("mod_id");
const mod_nom     = document.getElementById("mod_nom");
const mod_niveau  = document.getElementById("mod_niveau");
const mod_filiere = document.getElementById("mod_filiere");

const del_id    = document.getElementById("del_id");
const assign_id = document.getElementById("assign_id");

/* Open add */
document.querySelector(".open-add").onclick = () => openModal(modalAdd);

/* Open modify */
document.querySelectorAll(".open-modify").forEach(b=>{
  b.onclick = ()=>{
    mod_id.value = b.dataset.id;
    mod_nom.value = b.dataset.nom;
    mod_niveau.value = b.dataset.niveau || "";
    mod_filiere.value = b.dataset.filiere || "";
    openModal(modalModify);
  };
});

/* Open delete */
document.querySelectorAll(".open-delete").forEach(b=>{
  b.onclick = ()=>{
    del_id.value = b.dataset.id;
    openModal(modalDelete);
  };
});

/* Open assign (NE PAS ENLEVER) */
document.querySelectorAll(".open-assign").forEach(b=>{
  b.onclick = ()=>{
    assign_id.value = b.dataset.id;
    openModal(modalAssign);
  };
});

/* ================= AJAX HELPER ================= */
function send(form, action){
  const fd = new FormData(form);
  fd.append("action", action);

  fetch("", { method:"POST", body: fd })
    .then(r => r.text())
    .then(res => {
      res = res.trim();
      if(res === "success"){
        location.reload();
      }else{
        alert("Erreur serveur:\n" + res);
      }
    })
    .catch(err => alert("Erreur réseau:\n" + err));
}

/* submits */
formAdd.onsubmit    = e=>{ e.preventDefault(); send(formAdd, "add"); };
formModify.onsubmit = e=>{ e.preventDefault(); send(formModify, "modify"); };
formDelete.onsubmit = e=>{ e.preventDefault(); send(formDelete, "delete"); };
formAssign.onsubmit = e=>{ e.preventDefault(); send(formAssign, "assign"); };
</script>

</body>
</html>
