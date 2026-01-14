<?php
session_start();

// ---------------- Connexion ----------------
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ecole";

$connexion = new mysqli($host,$user,$pass,$dbname);
if($connexion->connect_error){
    die("Erreur de connexion: " . $connexion->connect_error);
}

// ---------------- AJOUT / MODIFICATION / SUPPRESSION ----------------
$message = "";

// ADD
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_admin'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validation
    if(!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)){
        $message = "Username invalide : 4-20 caractères alphanumériques ou _";
    } elseif(!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,20}$/', $password)){
        $message = "Password invalide : 6-20 caractères, au moins 1 majuscule, 1 minuscule et 1 chiffre";
    } else {
        $hash = password_hash($password,PASSWORD_DEFAULT);
        $stmt = $connexion->prepare("INSERT INTO users(username,password,role) VALUES (?,?,?)");
        $role = 'admin';
        $stmt->bind_param("sss",$username,$hash,$role);
        if($stmt->execute()){
            $message = "Admin ajouté avec succès !";
        } else { $message = "Erreur: ".$stmt->error; }
        $stmt->close();
    }
}

// MODIFY
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['modify_admin'])){
    $id = intval($_POST['id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if(!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)){
        $message = "Username invalide : 4-20 caractères alphanumériques ou _";
    } else {
        if($password){ // mettre à jour le mot de passe
            if(!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,20}$/', $password)){
                $message = "Password invalide : 6-20 caractères, au moins 1 majuscule, 1 minuscule et 1 chiffre";
            } else {
                $hash = password_hash($password,PASSWORD_DEFAULT);
                $stmt = $connexion->prepare("UPDATE users SET username=?, password=? WHERE id=? AND role='admin'");
                $stmt->bind_param("ssi",$username,$hash,$id);
                $stmt->execute();
                $stmt->close();
                $message = "Admin modifié avec succès !";
            }
        } else { // juste username
            $stmt = $connexion->prepare("UPDATE users SET username=? WHERE id=? AND role='admin'");
            $stmt->bind_param("si",$username,$id);
            $stmt->execute();
            $stmt->close();
            $message = "Admin modifié avec succès !";
        }
    }
}

// DELETE
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_admin'])){
    $id = intval($_POST['id']); // <-- champ 'id' existe maintenant
    $stmt = $connexion->prepare("DELETE FROM users WHERE id=? AND role='admin'");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
    $message = "Admin supprimé avec succès !";
}


// ---------------- Récupérer liste admins ----------------
// ---------------- PAGINATION ----------------
$limit = 5; //5 lignes par page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

// Total admins
$totalResult = $connexion->query("SELECT COUNT(*) AS total FROM users WHERE role='admin'");
$totalRow = $totalResult->fetch_assoc();
$totalAdmins = $totalRow['total'];
$totalPages = ceil($totalAdmins / $limit);

// Récupérer admins paginés
$stmt = $connexion->prepare("SELECT id, username FROM users WHERE role='admin' LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result_admins = $stmt->get_result();

?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admins List</title>

<style>
:root { --primary:#0d6efd; --secondary:#243352; --bg:#f4f6fb; --white:#fff; --card-bg:#fff; --text:#333;}
body {margin:0;font-family:"Poppins",sans-serif;background:var(--bg);color:var(--text);}
html,body{margin:0;padding:0;}
header {  margin-top:0px; background:linear-gradient(135deg,var(--secondary),var(--primary));padding:20px 40px;color:#fff;display:flex;justify-content:space-between;align-items:center;;width:100%;z-index:200;}
header h1{margin:0;font-size:26px;}
header nav a.btn{background:#fff;color:var(--secondary);padding:6px 20px;border-radius:25px;text-decoration:none;}
header nav a.btn:hover{opacity:0.9;}
.sidebar{position:fixed;left:-240px;top:0;width:240px;height:100vh;background:var(--secondary);padding:20px;color:white;display:flex;flex-direction:column;gap:25px;transition:left 0.3s ease;z-index:300;box-shadow:5px 0 15px rgba(0,0,0,0.3);}
.sidebar.open{left:0;}
.page-btn{
  padding:6px 12px;
  margin-right:5px;
  border-radius:6px;
  background:#e0e0e0;
  text-decoration:none;
  color:#243352;
  font-weight:600;
  transition:.3s;
}
.page-btn:hover{ background:#0d6efd; color:white; }
.page-btn.active{ background:#0d6efd; color:white; }

.sidebar h2{margin:0 0 20px;font-size:20px;}
.sidebar a{color:#fff;text-decoration:none;padding:8px 0;border-radius:8px;transition:0.3s;}
.sidebar a:hover{background:rgba(255,255,255,0.1);}
#burger{position:fixed;top:15px;left:15px;z-index:400;font-size:28px;background-color:#94979bff;border:none;border-radius:6px;cursor:pointer;line-height:1;box-shadow:0 4px 8px rgba(0,0,0,0.2);transition:left 0.3s ease, background 0.3s;}
#burger.open{color:#265587ff;}
.site-main{margin-top:0px;margin-left:0;transition:margin-left 0.3s;}
.sidebar.open ~ .site-main{margin-left:240px;}
table{margin-left:250px;margin-top:20px;width:70%;border-collapse:collapse;background:var(--card-bg);border-radius:10px;overflow:hidden;box-shadow:0 15px 30px rgba(0,0,0,.1);}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}
th{background:var(--secondary);color:#fff;}
tr:hover{background:rgba(0,0,0,.05);}
button{cursor:pointer;border:none;padding:6px 12px;border-radius:6px;transition:0.3s;}
button:hover{opacity:0.85;}
.modal{position:fixed;top:50%;left:50%;transform:translate(-50%,-60%) scale(.9);background:#fff;padding:30px;border-radius:20px;width:360px;box-shadow:0 40px 100px rgba(0,0,0,.3);opacity:0;pointer-events:none;transition:.45s ease;z-index:600;}
.modal.active{opacity:1;transform:translate(-50%,-50%) scale(1);pointer-events:auto;}
#overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:.4s;z-index:500;}
#overlay.active{opacity:1;pointer-events:auto;}
.modal input{width:100%;padding:12px;margin-bottom:12px;border-radius:10px;border:1px solid #ddd;}
.primary{background:var(--primary);color:#fff;padding:12px;border:none;border-radius:30px;cursor:pointer;}
.full{width:100%;}
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
  <h1 style ="margin-left:60px">Loop Academy</h1>
  <nav style ="margin-right:60px">
    <a href="admindashboard.php" class="btn">Dashboard</a>
    <a href="../first.php" class="btn">Logout</a>
  </nav>
</header>

<h2 style="margin-left:250px;margin-top:100px;">Admins List</h2>

<?php if($message): ?>
<p style="margin-left:250px;color:green;"><?= $message ?></p>
<?php endif; ?>

<!-- MESSAGE JS (ajout visuel) -->
<p id="jsMsg" style="margin-left:250px;"></p>

<button style="margin-left:250px;margin-bottom:10px;" onclick="openModal('addModal')">Add Admin</button>
<button class="export-chat" id="exportChatBtn" style="margin-left:920px">Export</button>

<table>
<tr><th>ID</th><th>Username</th><th>Edit</th><th>Delete</th></tr>
<?php while($row = $result_admins->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= htmlspecialchars($row['username']) ?></td>
<td><button class="open-modify" data-id="<?= $row['id'] ?>" data-username="<?= $row['username'] ?>">Edit</button></td>
<td><button class="open-delete" data-id="<?= $row['id'] ?>">Delete</button></td>
</tr>
<?php endwhile; ?>
</table>

<div style="margin-left:250px;margin-top:20px;">
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


<!-- OVERLAY -->
<div id="overlay" onclick="closeAll()"></div>

<!-- MODAL ADD -->
<div class="modal" id="addModal">
  <h2>Add Admin</h2>
  <!-- IMPORTANT: on garde la même structure, juste on met un id pour JS -->
  <form method="post" id="addAdminForm">
    <input type="hidden" name="add_admin" value="1">
    <input name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button class="primary full">Create Admin</button>
  </form>
</div>

<!-- MODAL MODIFY -->
<div class="modal" id="modifyModal">
<h2>Modify Admin</h2>
<form method="post">
<input type="hidden" name="modify_admin" value="1">
<input type="hidden" name="id" id="mod_id">
<input name="username" id="mod_username" placeholder="Username" required>
<input type="password" name="password" placeholder="New Password (optional)">
<button class="primary full">Save</button>
</form>
</div>

<!-- MODAL DELETE -->
<div class="modal" id="deleteModal">
<h2>Delete Admin</h2>
<form method="post">
    <input type="hidden" name="id" id="del_id_input">
    <input type="hidden" name="delete_admin" value="1">
    <p>Are you sure?</p>
    <button class="primary full">Delete</button>
</form>
</div>

<!-- MODAL EXPORT -->
<div class="modal-overlay" id="exportModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Exporter la liste</h3>
      <button class="close-modal">&times;</button>
    </div>
    <div class="modal-body">
      <p>export as pdf :</p>
      <div style="display: flex; gap: 10px; margin-top: 15px;">
        <button class="btn btn-secondary" id="exportPdf">
          <i class="fas fa-file-pdf"></i> PDF
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
<script>
/* ================= EXPORT LISTE ADMINS PDF ================= */
// On récupère le bouton "Export" et on lui ajoute un écouteur d'événement "click"
// -> quand on clique, la fonction exportAdminsPDF() s’exécute
document.getElementById("exportChatBtn").addEventListener("click", exportAdminsPDF);

// Fonction qui génère et télécharge un PDF contenant la liste des admins
function exportAdminsPDF() {

    // On récupère la classe jsPDF depuis window.jspdf (bibliothèque importée via CDN)
    const { jsPDF } = window.jspdf;

    // On crée un nouveau document PDF :
    // "p" = portrait, "mm" = unité en millimètres, "a4" = format de page
    const pdf = new jsPDF("p", "mm", "a4");

    // On fixe la taille de police pour le titre
    pdf.setFontSize(16);

    // On écrit le titre dans le PDF à la position x=14, y=15
    pdf.text("Liste des administrateurs", 14, 15);

    // On diminue la taille de police pour la date
    pdf.setFontSize(10);

    // On écrit la date actuelle au format français à x=14, y=22
    pdf.text("Exporté le : " + new Date().toLocaleString("fr-FR"), 14, 22);

    // On prépare un tableau JS qui contiendra toutes les lignes à exporter
    const rows = [];

    // On parcourt toutes les lignes (tr) du tableau HTML
    document.querySelectorAll("table tr").forEach((tr, index) => {

        // Si c’est la première ligne (index 0), c’est l'en-tête -> on la saute
        if (index === 0) return;

        // On récupère toutes les cellules (td) de cette ligne
        const td = tr.querySelectorAll("td");

        // Si la ligne n’a pas au moins 2 cellules, on ignore (sécurité)
        if (td.length < 2) return;

        // On ajoute une ligne au tableau rows:
        // - td[0] = ID
        // - td[1] = Username
        rows.push([ td[0].innerText.trim(), td[1].innerText.trim() ]);
    });

    // On crée le tableau dans le PDF via jsPDF-Autotable
    pdf.autoTable({
        // Position de départ en Y (vertical) pour ne pas chevaucher le titre/date
        startY: 30,

        // En-tête du tableau PDF
        head: [["ID", "Username"]],

        // Corps du tableau PDF (les lignes collectées)
        body: rows,

        // Styles généraux du tableau
        styles: { fontSize: 11, cellPadding: 4 },

        // Styles spécifiques à l'en-tête (couleur de fond)
        headStyles: { fillColor: [36, 51, 82] }
    });

    // Télécharge le fichier PDF avec ce nom
    pdf.save("liste_admins.pdf");
}


/* ================= MENU BURGER ================= */

// On récupère le bouton burger (☰)
const burger = document.getElementById('burger');

// On récupère la sidebar
const sidebar = document.getElementById('sidebar');

// Quand on clique sur le burger:
burger.onclick = () => {

  // On ajoute/retire la classe "open" sur la sidebar (ouvre/ferme)
  sidebar.classList.toggle('open');

  // On ajoute/retire la classe "open" sur le burger (style visuel)
  burger.classList.toggle('open');
};


/* ================= MODALS ================= */

// Ouvre une modal + active l'overlay (fond sombre)
function openModal(id){

  // Ajoute la classe "active" à la modal demandée
  document.getElementById(id).classList.add('active');

  // Ajoute la classe "active" à l'overlay
  document.getElementById('overlay').classList.add('active');
}

// Ferme toutes les modals + désactive l'overlay
function closeAll(){

  // Sélectionne toutes les modals et enlève "active" à chacune
  document.querySelectorAll('.modal').forEach(m => m.classList.remove('active'));

  // Enlève "active" à l'overlay
  document.getElementById('overlay').classList.remove('active');
}


/* ================= BOUTONS MODIFY (EXISTANTS) ================= */

// On prend tous les boutons "Edit" existants (ceux venant du PHP)
document.querySelectorAll('.open-modify').forEach(btn => {

    // Quand on clique sur un bouton Edit:
    btn.onclick = () => {

        // On met l'id du bouton dans l'input caché mod_id
        document.getElementById('mod_id').value = btn.dataset.id;

        // On met username du bouton dans l'input mod_username
        document.getElementById('mod_username').value = btn.dataset.username;

        // On ouvre la modal "modifyModal"
        openModal('modifyModal');
    };
});


/* ================= BOUTONS DELETE (EXISTANTS) ================= */

// On prend tous les boutons "Delete" existants (ceux venant du PHP)
document.querySelectorAll('.open-delete').forEach(btn => {

    // Quand on clique sur un bouton Delete:
    btn.onclick = () => {

        // On met l'id du bouton dans l'input caché del_id_input
        document.getElementById('del_id_input').value = btn.dataset.id;

        // On ouvre la modal "deleteModal"
        openModal('deleteModal');
    };
});


/* ================= AJOUT VISUEL  ================= */

// On récupère l’élément HTML où afficher un message (success/error)
const jsMsg = document.getElementById("jsMsg");

// Fonction pour afficher un message en vert (ok=true) ou rouge (ok=false)
function showJsMsg(text, ok=true){

  // On met le texte dans <p id="jsMsg">
  jsMsg.textContent = text;

  // On met la couleur selon le résultat
  jsMsg.style.color = ok ? "green" : "crimson";
}


// Fonction pour échapper le HTML (évite l’injection XSS)
// Exemple: "<script>" devient "&lt;script&gt;"
function escapeHtml(str){
  return String(str).replace(/[&<>"']/g, s => ({
    "&":"&amp;",
    "<":"&lt;",
    ">":"&gt;",
    '"':"&quot;",
    "'":"&#039;"
  }[s]));
}


// Fonction qui ajoute les comportements Edit/Delete à UNE nouvelle ligne ajoutée en JS
function attachRowHandlers(tr){

  // On cherche le bouton Edit dans cette ligne
  const editBtn = tr.querySelector(".open-modify");

  // Si le bouton Edit existe
  if(editBtn){

    // Au clic, on remplit la modal Modify
    editBtn.onclick = () => {

      // Met l'id dans le champ caché
      document.getElementById('mod_id').value = editBtn.dataset.id;

      // Met le username dans le champ username
      document.getElementById('mod_username').value = editBtn.dataset.username;

      // Ouvre la modal
      openModal('modifyModal');
    };
  }

  // On cherche le bouton Delete dans cette ligne
  const delBtn = tr.querySelector(".open-delete");

  // Si le bouton Delete existe
  if(delBtn){

    // Au clic, on remplit la modal Delete
    delBtn.onclick = () => {

      // Met l'id dans le champ caché de delete
      document.getElementById('del_id_input').value = delBtn.dataset.id;

      // Ouvre la modal delete
      openModal('deleteModal');
    };
  }
}


// On récupère le formulaire Add Admin et on écoute son submit
document.getElementById("addAdminForm").addEventListener("submit", function(e){

  // Empêche l’envoi normal du formulaire vers PHP (pas de refresh)
  e.preventDefault();

  // Récupère la valeur du champ username et supprime espaces autour
  const username = this.username.value.trim();

  // Récupère la valeur du champ password et supprime espaces autour
  const password = this.password.value.trim();

  // Validation username (même regex que PHP)
  if(!/^[a-zA-Z0-9_]{4,20}$/.test(username)){
    showJsMsg("Username invalide : 4-20 caractères alphanumériques ou _", false);
    return; // stop
  }

  // Validation password (même regex que PHP)
  if(!/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{6,20}$/.test(password)){
    showJsMsg("Password invalide : 6-20 caractères, au moins 1 majuscule, 1 minuscule et 1 chiffre", false);
    return; // stop
  }

  // Crée un ID "visuel" unique basé sur le temps actuel (en ms)
  // (ce n'est pas l'ID DB, juste pour afficher)
  const visualId = Date.now();

  // On récupère le tableau HTML
  const table = document.querySelector("table");

  // On crée une nouvelle ligne <tr>
  const tr = document.createElement("tr");

  // On injecte les cellules HTML dans la ligne
  // - ID visuel
  // - username échappé
  // - boutons Edit/Delete avec data-id / data-username
  tr.innerHTML = `
    <td>${visualId}</td>
    <td>${escapeHtml(username)}</td>
    <td><button class="open-modify" data-id="${visualId}" data-username="${escapeHtml(username)}">Edit</button></td>
    <td><button class="open-delete" data-id="${visualId}">Delete</button></td>
  `;

  // Ajoute la ligne à la fin du tableau
  table.appendChild(tr);

  // Active les clics Edit/Delete sur cette nouvelle ligne
  attachRowHandlers(tr);

  // Vide le formulaire (remet les champs à vide)
  this.reset();

  // Ferme le modal + overlay
  closeAll();

  // Affiche un message de succès
  showJsMsg("Admin ajouté visuellement (pas enregistré en base).", true);
});


</script>
</body>
</html>
