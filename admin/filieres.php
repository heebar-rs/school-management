<?php
session_start();

if(!isset($_SESSION['admin_id']) && !isset($_SESSION['admin'])){
  // header("Location: ../first.php");
  // exit;
}

/* ---------- CONNEXION ---------- */
$conn = new mysqli("localhost","root","","ecole");
if($conn->connect_error) die("Erreur connexion");

/* ---------- ADD ---------- */
if(isset($_POST['add'])){
    $nom  = trim($_POST['nom_filiere'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if($nom !== ''){
        $stmt = $conn->prepare("INSERT INTO filieres(nom_filiere,description) VALUES(?,?)");
        $stmt->bind_param("ss", $nom, $desc);
        $stmt->execute();
    }
}

/* ---------- UPDATE ---------- */
if(isset($_POST['update'])){
    $id   = (int)($_POST['id'] ?? 0);
    $nom  = trim($_POST['nom_filiere'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if($id > 0 && $nom !== ''){
        $stmt = $conn->prepare("UPDATE filieres SET nom_filiere=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $nom, $desc, $id);
        $stmt->execute();
    }
}

/* ---------- DELETE ---------- */
if(isset($_POST['delete'])){
    $id = (int)($_POST['id'] ?? 0);
    if($id > 0){
        $stmt = $conn->prepare("DELETE FROM filieres WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
}

/* ---------- READ ---------- */
$filieres = $conn->query("SELECT * FROM filieres ORDER BY id DESC");
$count = $filieres ? $filieres->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des Filières</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --white:#fff;
  --text:#222;
  --muted:#6c757d;
  --border:#e6e9f0;
  --radius:16px;
  --shadow:0 12px 28px rgba(0,0,0,.08);
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:"Poppins",system-ui,-apple-system,Segoe UI,sans-serif;background:var(--bg);color:var(--text)}

/* ===== Sidebar ===== */
#burger{
  position:fixed;top:15px;left:15px;z-index:300;font-size:28px;
  background:#94979bff;border:none;border-radius:8px;cursor:pointer;
  line-height:1;padding:6px 10px;
  box-shadow:0 4px 10px rgba(0,0,0,.15);
}
#burger.open{background:#e9eefb;color:#0d6efd}

.sidebar{
  position:fixed;left:-240px;top:0;width:240px;height:100vh;
  background:var(--secondary);padding:20px;color:#fff;
  display:flex;flex-direction:column;gap:14px;
  transition:left .25s ease;z-index:200;
  box-shadow:5px 0 18px rgba(0,0,0,.22);
}
.sidebar.open{left:0}
.sidebar h2{margin:10px 0 12px;font-size:18px;font-weight:600}
.sidebar a{
  color:#fff;text-decoration:none;padding:10px 12px;border-radius:10px;
  transition:.2s;display:flex;align-items:center;gap:10px;
}
.sidebar a:hover{background:rgba(255,255,255,.10)}
.sidebar a.active{background:rgba(255,255,255,.16)}

.site-main{margin-left:0;transition:margin-left .25s}
.sidebar.open ~ .site-main{margin-left:240px}

/* ===== Header simple ===== */
header{
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:18px 40px;color:#fff;
  display:flex;justify-content:space-between;align-items:center;
}
header h1{margin:0;font-size:24px}
header .right{display:flex;gap:10px;align-items:center;}
header .badge{
  background:rgba(255,255,255,.18);
  border:1px solid rgba(255,255,255,.20);
  padding:6px 12px;border-radius:999px;font-size:13px;
}
header a.logout-link{color:#fff;text-decoration:none;font-weight:600;}
header .btn-light{
  background:rgba(255,255,255,.18);
  border:1px solid rgba(255,255,255,.22);
  color:#fff;
  padding:8px 12px;border-radius:999px;cursor:pointer;
}

/* ===== Container ===== */
.container{max-width:1200px;margin:26px auto;padding:0 18px;}

/* ===== Top actions ===== */
.topbar{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:16px;gap:12px;
}
.topbar h2{margin:0;font-size:20px;color:var(--secondary)}
.btn{
  border:none;cursor:pointer;border-radius:999px;
  padding:10px 16px;font-weight:600;
  display:inline-flex;align-items:center;gap:8px;
  transition:.2s;text-decoration:none;
}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{filter:brightness(.95);transform:translateY(-1px)}
.btn-light{background:#fff;border:1px solid var(--border);color:var(--secondary)}
.btn-light:hover{background:#f8fbff}

/* ===== Cards ===== */
.cards{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
  gap:16px;
}
.card{
  background:#fff;
  border:1px solid var(--border);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}
.card-head{
  padding:14px 16px;
  border-bottom:1px solid var(--border);
  display:flex;justify-content:space-between;gap:10px;align-items:flex-start;
}
.card-title{
  margin:0;
  font-size:16px;
  font-weight:700;
  color:var(--secondary);
  display:flex;gap:10px;align-items:center;
}
.card-id{
  font-size:12px;
  color:var(--muted);
  background:#f2f5ff;
  border:1px solid #e2e8ff;
  padding:4px 10px;border-radius:999px;
}
.card-body{padding:14px 16px;}
.card-body p{margin:0;color:#2a2f3a;font-size:14px;line-height:1.6;white-space:pre-wrap;}
.card-actions{
  padding:14px 16px;
  border-top:1px solid var(--border);
  display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;
}
.btn-edit{background:#f1f5ff;border:1px solid #dbe5ff;color:#2445b5}
.btn-edit:hover{background:#eaf1ff}
.btn-del{background:#ffecef;border:1px solid #ffd0d8;color:#b00020}
.btn-del:hover{background:#ffe2e8}

/* ===== Modal simple ===== */
.modal{
  position:fixed;inset:0;background:rgba(10,15,25,.55);
  display:none;justify-content:center;align-items:center;
  padding:16px;z-index:1000;
}
.modal.active{display:flex}
.modal-card{
  width:520px;max-width:95vw;background:#fff;
  border-radius:18px;box-shadow:0 30px 90px rgba(0,0,0,.25);
  overflow:hidden;
}
.modal-head{
  padding:14px 16px;
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  color:#fff;display:flex;justify-content:space-between;align-items:center;
}
.modal-head h3{margin:0;font-size:16px;display:flex;gap:10px;align-items:center}
.modal-close{
  border:none;background:rgba(255,255,255,.18);
  border:1px solid rgba(255,255,255,.20);
  width:38px;height:38px;border-radius:12px;color:#fff;cursor:pointer;
}
.modal-body{padding:16px}
.form-group{margin-bottom:12px}
label{display:block;font-weight:600;color:var(--secondary);margin-bottom:6px;font-size:13px}
input,textarea{
  width:100%;padding:12px 12px;border-radius:14px;
  border:1px solid var(--border);background:#f7f9fc;
  font-family:inherit;font-size:14px;outline:none;
}
input:focus,textarea:focus{
  background:#fff;border-color:rgba(13,110,253,.45);
  box-shadow:0 0 0 3px rgba(13,110,253,.12);
}
textarea{min-height:120px;resize:vertical}
.modal-foot{
  padding:14px 16px;border-top:1px solid var(--border);
  display:flex;justify-content:flex-end;gap:10px;
}

@media(max-width:768px){
  header{padding:16px 18px}
  .cards{grid-template-columns:1fr}
}
</style>
</head>

<body>

<button id="burger" aria-label="Toggle sidebar">☰</button>

<aside id="sidebar" class="sidebar">
  <h2> </h2>
  <a href="admindashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
  <a href="statistics.php"><i class="fa-solid fa-chart-line"></i> statistics</a>
  <a href="list_students.php"><i class="fa-solid fa-users"></i> List Students</a>
  <a href="list_admins.php"><i class="fa-solid fa-user-shield"></i> List Admins</a>
  <a href="classes.php"><i class="fa-solid fa-chalkboard"></i> Classes</a>
  <a href="filieres.php" class="active"><i class="fa-solid fa-layer-group"></i> programms</a>
  <a href="../first.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</aside>

<main class="site-main">

<header>
  <h1 style="margin-left:70px">Loop Academy • Filières</h1>
  <div class="right">
    <span class="badge"><i class="fa-solid fa-database"></i> <?= (int)$count ?> filières</span>
    <button class="btn-light" type="button">
      <a class="logout-link" href="../first.php">Logout</a>
    </button>
  </div>
</header>

<div class="container">

  <div class="topbar">
    <h2>Liste des Filières</h2>
    <button class="btn btn-primary" type="button" onclick="openAdd()">
      <i class="fa-solid fa-plus-circle"></i> Nouvelle filière
    </button>
  </div>

  <div class="cards">
    <?php if($count > 0): ?>
      <?php while($f = $filieres->fetch_assoc()): ?>
        <div class="card">
          <div class="card-head">
            <div>
              <h3 class="card-title">
                <i class="fa-solid fa-book-open"></i>
                <?= htmlspecialchars($f['nom_filiere']) ?>
              </h3>
            </div>
            <span class="card-id">#<?= (int)$f['id'] ?></span>
          </div>

          <div class="card-body">
            <p><?= htmlspecialchars($f['description']) ?></p>
          </div>

          <div class="card-actions">
            <a class="btn btn-light" href="modules.php?filiere_id=<?= (int)$f['id'] ?>">
  <i class="fa-solid fa-list"></i> Modules
</a>

            <!-- ✅ Modifier (robuste) : data-* au lieu de onclick avec texte -->
            <button
              class="btn btn-edit js-edit"
              type="button"
              data-id="<?= (int)$f['id'] ?>"
              data-nom="<?= htmlspecialchars($f['nom_filiere'], ENT_QUOTES, 'UTF-8') ?>"
              data-desc="<?= htmlspecialchars($f['description'], ENT_QUOTES, 'UTF-8') ?>"
            >
              <i class="fa-solid fa-pen"></i> Modifier
            </button>

            <form method="post" onsubmit="return confirmDelete();" style="margin:0">
              <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
              <button class="btn btn-del" type="submit" name="delete">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="card" style="grid-column:1/-1;text-align:center;padding:22px;color:var(--muted)">
        Aucune filière pour le moment.
      </div>
    <?php endif; ?>
  </div>

</div>

</main>

<!-- ===== ADD MODAL ===== -->
<div class="modal" id="addModal" onclick="closeOnOutside(event,'addModal')">
  <div class="modal-card" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h3><i class="fa-solid fa-plus-circle"></i> Ajouter une filière</h3>
      <button class="modal-close" type="button" onclick="closeAdd()">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" id="addForm">
        <div class="form-group">
          <label>Nom de la filière</label>
          <input type="text" name="nom_filiere" id="add_nom" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="add_desc" required></textarea>
        </div>
        <div class="modal-foot">
          <button class="btn btn-light" type="button" onclick="closeAdd()">Annuler</button>
          <button class="btn btn-primary" type="submit" name="add">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== EDIT MODAL ===== -->
<div class="modal" id="editModal" onclick="closeOnOutside(event,'editModal')">
  <div class="modal-card" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h3><i class="fa-solid fa-pen"></i> Modifier la filière</h3>
      <button class="modal-close" type="button" onclick="closeEdit()">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" id="editForm">
        <input type="hidden" name="id" id="edit_id">
        <div class="form-group">
          <label>Nom de la filière</label>
          <input type="text" name="nom_filiere" id="edit_nom" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="edit_desc" required></textarea>
        </div>
        <div class="modal-foot">
          <button class="btn btn-light" type="button" onclick="closeEdit()">Annuler</button>
          <button class="btn btn-primary" type="submit" name="update">
            <i class="fa-solid fa-arrows-rotate"></i> Mettre à jour
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
/* ===== Sidebar burger ===== */
const burger = document.getElementById('burger');
const sidebar = document.getElementById('sidebar');
burger.addEventListener('click', ()=>{
  sidebar.classList.toggle('open');
  burger.classList.toggle('open');
});

/* ===== Modals ===== */
function openAdd(){
  document.getElementById('addModal').classList.add('active');
  setTimeout(()=>document.getElementById('add_nom').focus(), 30);
}
function closeAdd(){
  document.getElementById('addModal').classList.remove('active');
  document.getElementById('addForm').reset();
}

function openEdit(id, nom, desc){
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_nom').value = nom || '';
  document.getElementById('edit_desc').value = desc || '';
  document.getElementById('editModal').classList.add('active');
  setTimeout(()=>document.getElementById('edit_nom').focus(), 30);
}
function closeEdit(){
  document.getElementById('editModal').classList.remove('active');
}

function confirmDelete(){
  return confirm("Supprimer cette filière ? (Action irréversible)");
}

function closeOnOutside(e, id){
  if(e.target.id === id){
    document.getElementById(id).classList.remove('active');
    if(id === 'addModal') document.getElementById('addForm').reset();
  }
}

/* ✅ Buttons "Modifier" robustes (data-*) */
document.querySelectorAll('.js-edit').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    openEdit(btn.dataset.id, btn.dataset.nom, btn.dataset.desc);
  });
});

/* Fermer avec ESC */
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape'){
    closeAdd();
    closeEdit();
  }
});
</script>

</body>
</html>
