<?php
require('../connexion.php');

$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) die("Erreur de connexion");

/* ================= TRAITEMENT AJAX ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['student_id']);
        $stmt = $connexion->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->bind_param("i", $id);
        echo $stmt->execute() ? "success" : "error";
        exit;
    }

    if ($_POST['action'] === 'update') {
        $id     = $_POST['student_id'];
        $fname  = $_POST['first_name'];
        $lname  = $_POST['last_name'];
        $birth  = $_POST['birth_date'];
        $gender = $_POST['gender'];
        $phone  = $_POST['phone'];

        if (!empty($_FILES['photo']['tmp_name'])) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
            $stmt = $connexion->prepare("
                UPDATE students 
                SET first_name=?, last_name=?, birth_date=?, gender=?, phone=?, photo=?
                WHERE student_id=?
            ");
            $stmt->bind_param("ssssssi", $fname,$lname,$birth,$gender,$phone,$photo,$id);
        } else {
            $stmt = $connexion->prepare("
                UPDATE students 
                SET first_name=?, last_name=?, birth_date=?, gender=?, phone=?
                WHERE student_id=?
            ");
            $stmt->bind_param("sssssi", $fname,$lname,$birth,$gender,$phone,$id);
        }

        echo $stmt->execute() ? "success" : "error";
        exit;
    }
}

$limit = 5; // 10 étudiants par page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = ($page < 1) ? 1 : $page;
$offset = ($page - 1) * $limit;

// Total étudiants pour calculer le nombre de pages
$totalRes = $connexion->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = $totalRes->fetch_assoc()['total'];
$totalPages = ceil($totalStudents / $limit);

// Requête paginée
$stmt = $connexion->prepare("
SELECT s.*, u.username
FROM students s
LEFT JOIN users u ON s.user_id = u.id
LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$resultat = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des étudiants</title>

<style>
:root { --primary:#0d6efd; --secondary:#243352; --bg:#f4f6fb; --white:#fff; --card-bg:#fff; --text:#333;}
body {margin:0;font-family:"Poppins",sans-serif;background:var(--bg);color:var(--text);}
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

.page-btn:hover{
  background:#0d6efd;
  color:white;
}

.page-btn.active{
  background:#0d6efd;
  color:white;
}

html,body{margin:0;padding:0;}
header {  margin-top:0px; background:linear-gradient(135deg,var(--secondary),var(--primary));padding:20px 40px;color:#fff;display:flex;justify-content:space-between;align-items:center;;width:100%;z-index:200;}
header h1{margin:0;font-size:26px;}
header nav a.btn{background:#fff;color:var(--secondary);padding:6px 20px;border-radius:25px;text-decoration:none;}
header nav a.btn:hover{opacity:0.9;}
.sidebar{position:fixed;left:-240px;top:0;width:240px;height:100vh;background:var(--secondary);padding:20px;color:white;display:flex;flex-direction:column;gap:25px;transition:left 0.3s ease;z-index:300;box-shadow:5px 0 15px rgba(0,0,0,0.3);}
.sidebar.open{left:0;}
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
  <nav style ="margin-left:60px !important" ><h1 >Loop Academy</h1></nav>
  <nav style ="margin-right:60px">
    <a href="admindashboard.php" class="btn">Dashboard</a>
    <a href="../first.php" class="btn">Logout</a>
  </nav>
  
</header>
<h2 style="margin-left:250px;margin-top:100px;">students List
    <button class="export-chat" id="exportChatBtn"style="margin-left:850px">
           Export 
        </button>
</h2> 
    
<table>
<thead>
<tr>
<th>ID</th><th>name</th><th>last name</th><th>birth day</th>
<th>Gender</th><th>Phone</th><th>Photo</th><th>User</th><th>Actions</th>
</tr>
</thead>
<tbody>
<?php while($l=$resultat->fetch_assoc()): ?>
<tr>
<td><?= $l['student_id'] ?></td>
<td><?= $l['last_name'] ?></td>
<td><?= $l['first_name'] ?></td>
<td><?= $l['birth_date'] ?></td>
<td><?= $l['gender'] ?></td>
<td><?= $l['phone'] ?></td>
<td>
<?php if($l['photo']): ?>
<img src="data:image/jpeg;base64,<?= base64_encode($l['photo']) ?>" width="45">
<?php else: ?>—<?php endif; ?>
</td>
<td><?= $l['username'] ?></td>
<td>
<button class="open-modify"
 data-id="<?= $l['student_id'] ?>"
 data-fname="<?= $l['first_name'] ?>"
 data-lname="<?= $l['last_name'] ?>"
 data-birth="<?= $l['birth_date'] ?>"
 data-gender="<?= $l['gender'] ?>"
 data-phone="<?= $l['phone'] ?>">Modifier</button>

<button class="open-delete" data-id="<?= $l['student_id'] ?>">Delete</button>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<div style="margin-left:250px;margin-top:20px;">

<?php if($totalPages > 1): ?>
  <!-- Bouton précédent -->
  <?php if($page > 1): ?>
    <a href="?page=<?= $page-1 ?>" class="page-btn">« Prev</a>
  <?php endif; ?>

  <!-- Numéros de pages -->
  <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>" 
       class="page-btn <?= ($i==$page)?'active':'' ?>">
       <?= $i ?>
    </a>
  <?php endfor; ?>

  <!-- Bouton suivant -->
  <?php if($page < $totalPages): ?>
    <a href="?page=<?= $page+1 ?>" class="page-btn">Next »</a>
  <?php endif; ?>
<?php endif; ?>

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



<!-- MODAL MODIFY -->
<div id="modalModify" class="modal">
<form id="formModify" enctype="multipart/form-data">
<input type="hidden" id="mod_id" name="student_id">
<input id="mod_fname" name="first_name">
<input id="mod_lname" name="last_name">
<input type="date" id="mod_birth" name="birth_date">
<select id="mod_gender" name="gender">
<option value="M">M</option>
<option value="F">F</option>
</select>
<input id="mod_phone" name="phone">
<input type="file" name="photo">
<button type="submit">Enregistrer</button>
</form>
<button class="close-modal">Fermer</button>
</div>

<!-- MODAL DELETE -->
<div id="modalDelete" class="modal">
<form id="formDelete">
<input type="hidden" id="del_id" name="student_id">
<button type="submit">Supprimer</button>
</form>
<button class="close-modal">Annuler</button>
</div>

<script>
/* ================= FIX CRITIQUE ================= */
const modalModify = document.getElementById("modalModify");
const modalDelete = document.getElementById("modalDelete");
const formModify  = document.getElementById("formModify");
const formDelete  = document.getElementById("formDelete");

const mod_id     = document.getElementById("mod_id");
const mod_fname  = document.getElementById("mod_fname");
const mod_lname  = document.getElementById("mod_lname");
const mod_birth  = document.getElementById("mod_birth");
const mod_gender = document.getElementById("mod_gender");
const mod_phone  = document.getElementById("mod_phone");
const del_id     = document.getElementById("del_id");














/* ================= EXPORT LISTE ÉTUDIANTS PDF ================= */

// Ajoute un écouteur d’événement sur le bouton avec l’ID "exportChatBtn"
// Au clic, la fonction exportStudentsPDF sera exécutée
document.getElementById("exportChatBtn").addEventListener("click", exportStudentsPDF);

// Déclaration de la fonction qui exporte la liste des étudiants en PDF
function exportStudentsPDF() {

    // Récupération de la classe jsPDF depuis la librairie jspdf
    const { jsPDF } = window.jspdf;

    // Création d’un nouveau document PDF
    // "l" = landscape (paysage)
    // "mm" = millimètres
    // "a4" = format A4
    const pdf = new jsPDF("l", "mm", "a4");

    // Définition de la taille de la police pour le titre
    pdf.setFontSize(16);

    // Ajout du titre du document PDF à la position (x=14, y=15)
    pdf.text("Liste des étudiants", 14, 15);

    // Changement de la taille de la police pour le texte secondaire
    pdf.setFontSize(10);

    // Ajout de la date et l’heure d’export au format français
    pdf.text(
        "Exporté le : " + new Date().toLocaleString("fr-FR"),
        14,
        22
    );

    // Tableau qui contiendra toutes les lignes du tableau HTML
    const rows = [];

    // Parcours de chaque ligne (<tr>) du corps du tableau HTML
    document.querySelectorAll("table tbody tr").forEach(tr => {

        // Récupération de toutes les cellules (<td>) de la ligne
        const td = tr.querySelectorAll("td");

        // Ajout d’une ligne au tableau rows avec les valeurs souhaitées
        rows.push([
            td[0].innerText.trim(), // ID de l’étudiant
            td[1].innerText.trim(), // Nom
            td[2].innerText.trim(), // Prénom
            td[3].innerText.trim(), // Date de naissance
            td[4].innerText.trim(), // Genre
            td[5].innerText.trim(), // Téléphone
            td[7].innerText.trim()  // Nom d’utilisateur (username)
        ]);
    });

    // Génération automatique du tableau dans le PDF
    pdf.autoTable({

        // Position verticale de départ du tableau
        startY: 28,

        // En-têtes du tableau
        head: [[
            "ID",
            "Nom",
            "Prénom",
            "Naissance",
            "Genre",
            "Téléphone",
            "Utilisateur"
        ]],

        // Corps du tableau (données récupérées)
        body: rows,

        // Styles généraux du tableau
        styles: {
            fontSize: 9,     // Taille de la police
            cellPadding: 3  // Espacement intérieur des cellules
        },

        // Styles spécifiques à l’en-tête
        headStyles: {
            fillColor: [13, 110, 253] // Couleur bleue (Bootstrap primary)
        }
    });

    // Téléchargement automatique du fichier PDF
    pdf.save("liste_etudiants.pdf");
}












    // Burger toggle
const burger=document.getElementById('burger');
const sidebar=document.getElementById('sidebar');
burger.onclick=()=>{sidebar.classList.toggle('open');burger.classList.toggle('open');};

/* ================= OPEN MODALS ================= */
document.querySelectorAll(".open-modify").forEach(btn=>{
    btn.onclick = () => {
        mod_id.value    = btn.dataset.id;
        mod_fname.value = btn.dataset.fname;
        mod_lname.value = btn.dataset.lname;
        mod_birth.value = btn.dataset.birth;
        mod_gender.value= btn.dataset.gender;
        mod_phone.value = btn.dataset.phone;

        modalModify.style.opacity = "1";
        modalModify.style.pointerEvents = "auto";
    };
});

document.querySelectorAll(".open-delete").forEach(btn=>{
    btn.onclick = () => {
        del_id.value = btn.dataset.id;
        modalDelete.style.opacity = "1";
        modalDelete.style.pointerEvents = "auto";
    };
});

document.querySelectorAll(".close-modal").forEach(btn=>{
    btn.onclick = () => {
        btn.closest(".modal").style.opacity = "0";
        btn.closest(".modal").style.pointerEvents = "none";
    };
});


/* ================= AJAX UPDATE ================= */
formModify.onsubmit = e => {
    e.preventDefault();
    const fd = new FormData(formModify);
    fd.append("action","update");

    fetch("", { method:"POST", body:fd })
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()==="success"){ alert("Modifié"); location.reload(); }
        else alert("Erreur UPDATE");
    });
};


























/* ================= AJAX DELETE ================= */
formDelete.onsubmit = e => {
    e.preventDefault();
    const fd = new FormData(formDelete);
    fd.append("action","delete");

    fetch("", { method:"POST", body:fd })
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()==="success"){ alert("Supprimé"); location.reload(); }
        else alert("Erreur DELETE");
    });
};
</script>

</body>
</html>
