<?php
require('../connexion.php');

$connexion = new mysqli($host,$user,$pass,$dbname);
if ($connexion->connect_error) die("Erreur connexion");

/* ================= AJAX CRUD ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){

    /* UPDATE STUDENT */
    if($_POST['action']==='update'){
        $stmt=$connexion->prepare("
            UPDATE students
            SET first_name=?, last_name=?, birth_date=?, gender=?, phone=?
            WHERE student_id=?
        ");
        $stmt->bind_param(
            "sssssi",
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['birth_date'],
            $_POST['gender'],
            $_POST['phone'],
            $_POST['student_id']
        );
        echo $stmt->execute() ? "success" : "error";
        exit;
    }

    /* DELETE STUDENT */
    if($_POST['action']==='delete'){
        $stmt=$connexion->prepare("DELETE FROM students WHERE student_id=?");
        $stmt->bind_param("i",$_POST['student_id']);
        echo $stmt->execute() ? "success" : "error";
        exit;
    }
}

/* ================= PAGE DATA ================= */
$groupe_id = intval($_GET['groupe_id'] ?? 0);
$page = intval($_GET['page'] ?? 1);
$limit = 5; // 5 étudiants par page
$offset = ($page - 1) * $limit;

// Nom du groupe
$stmt = $connexion->prepare("SELECT nom FROM groupes WHERE id=?");
$stmt->bind_param("i",$groupe_id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows===0) die("<h2>Groupe introuvable</h2>");
$group = $res->fetch_assoc();

// Nombre total d'étudiants pour calculer les pages
$stmtCount = $connexion->prepare("SELECT COUNT(*) as total FROM students WHERE groupe_id=?");
$stmtCount->bind_param("i",$groupe_id);
$stmtCount->execute();
$totalStudents = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalStudents / $limit);

// Récupération des étudiants pour la page en cours
$stmt2 = $connexion->prepare("
SELECT s.student_id,s.last_name,s.first_name,s.birth_date,
       s.gender,s.phone,s.photo,u.username
FROM students s
LEFT JOIN users u ON s.user_id=u.id
WHERE s.groupe_id=?
ORDER BY s.last_name,s.first_name
LIMIT $limit OFFSET $offset
");
$stmt2->bind_param("i",$groupe_id);
$stmt2->execute();
$students = $stmt2->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Étudiants du groupe</title>

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
.sidebar h2{margin:0 0 20px;font-size:20px;}
.sidebar a{color:#fff;text-decoration:none;padding:8px 0;border-radius:8px;transition:0.3s;}
.sidebar a:hover{background:rgba(255,255,255,0.1);}
#burger{position:fixed;top:15px;left:15px;z-index:400;font-size:28px;background-color:#94979bff;border:none;border-radius:6px;cursor:pointer;line-height:1;box-shadow:0 4px 8px rgba(0,0,0,0.2);transition:left 0.3s ease, background 0.3s;}
#burger.open{color:#265587ff;}
.site-main{margin-top:0px;margin-left:0;transition:margin-left 0.3s;}
.sidebar.open ~ .site-main{margin-left:240px;}
table{margin-left:250px;margin-top:20px;width:70%;border-collapse:collapse;background:var(--card-bg);border-radius:10px;overflow:hidden;box-shadow:0 15px 30px rgba(0,0,0,.1);}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}
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
<header>
<h1 style="margin-left: 60px;">  <?= htmlspecialchars($group['nom']) ?> 's students</h1>
</header>

<table>
<tr>
<th>ID</th><th>Nom</th><th>Prénom</th><th>Naissance</th>
<th>Genre</th><th>Téléphone</th><th>Username</th><th>Photo</th><th>Actions</th>
</tr>

<?php while($s=$students->fetch_assoc()): ?>
<tr>
<td><?= $s['student_id'] ?></td>
<td><?= $s['last_name'] ?></td>
<td><?= $s['first_name'] ?></td>
<td><?= $s['birth_date'] ?></td>
<td><?= $s['gender'] ?></td>
<td><?= $s['phone'] ?></td>
<td><?= $s['username'] ?></td>
<td>
<?php if($s['photo']): ?>
<img src="data:image/jpeg;base64,<?= base64_encode($s['photo']) ?>" width="40">
<?php else: ?>—<?php endif; ?>
</td>
<td>
<button class="open-modify"
 data-id="<?= $s['student_id'] ?>"
 data-fname="<?= $s['first_name'] ?>"
 data-lname="<?= $s['last_name'] ?>"
 data-birth="<?= $s['birth_date'] ?>"
 data-gender="<?= $s['gender'] ?>"
 data-phone="<?= $s['phone'] ?>">Modify</button>

<button class="open-delete" data-id="<?= $s['student_id'] ?>">Delete</button>
</td>
</tr>
<?php endwhile; ?>
</table>
<div style="width:70%;margin:20px auto;">
<?php if($totalPages > 1): ?>
  <?php if($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&groupe_id=<?= $groupe_id ?>" class="page-btn">« Prev</a>
  <?php endif; ?>

  <?php for($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>&groupe_id=<?= $groupe_id ?>" class="page-btn <?= ($i==$page)?'active':'' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if($page < $totalPages): ?>
    <a href="?page=<?= $page+1 ?>&groupe_id=<?= $groupe_id ?>" class="page-btn">Next »</a>
  <?php endif; ?>
<?php endif; ?>

</div>

<!-- MODIFY -->
<div id="modalModify" class="modal">
<div class="modal-box">
<form id="formModify">
<input type="hidden" name="student_id" id="mod_id">
<input name="first_name" id="mod_fname"><br><br>
<input name="last_name" id="mod_lname"><br><br>
<input type="date" name="birth_date" id="mod_birth"><br><br>
<select name="gender" id="mod_gender">
<option value="M">M</option>
<option value="F">F</option>
</select><br><br>
<input name="phone" id="mod_phone"><br><br>
<button>Save</button>
</form>
<button class="close-modal">Close</button>
</div>
</div>

<!-- DELETE -->
<div id="modalDelete" class="modal">
<div class="modal-box">
<form id="formDelete">
<input type="hidden" name="student_id" id="del_id">
<p>Confirm delete?</p>
<button>Delete</button>
</form>
<button class="close-modal">Cancel</button>
</div>
</div>

<script>

/* ================= DECLARATIONS OBLIGATOIRES ================= */
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

const del_id = document.getElementById("del_id");

/* ================= BURGER ================= */
const burger=document.getElementById('burger');
const sidebar=document.getElementById('sidebar');
burger.onclick=()=>{
    sidebar.classList.toggle('open');
    burger.classList.toggle('open');
};

/* ================= OPEN MODALS ================= */
document.querySelectorAll(".open-modify").forEach(b=>{
    b.onclick = ()=>{
        mod_id.value     = b.dataset.id;
        mod_fname.value  = b.dataset.fname;
        mod_lname.value  = b.dataset.lname;
        mod_birth.value  = b.dataset.birth;
        mod_gender.value = b.dataset.gender;
        mod_phone.value  = b.dataset.phone;
        modalModify.classList.add("active");
    };
});

document.querySelectorAll(".open-delete").forEach(b=>{
    b.onclick = ()=>{
        del_id.value = b.dataset.id;
        modalDelete.classList.add("active");
    };
});

/* ================= CLOSE MODALS ================= */
document.querySelectorAll(".close-modal").forEach(b=>{
    b.onclick = ()=>{
        b.closest(".modal").classList.remove("active");
    };
});

/* ================= AJAX ================= */
function send(form,action){
    const fd = new FormData(form);//ts les champs
    fd.append("action",action);//ajouter type hidden action

    fetch("",{method:"POST",body:fd})//les données arrivent dans $_POST côté PHP
    .then(r=>r.text())
    .then(res=>{
        if(res.trim()==="success"){
            location.reload();//Recharge la page
        }else{
            alert("Error");
        }
    });
}

formModify.onsubmit = e=>{
    e.preventDefault();
    send(formModify,"update");
};

formDelete.onsubmit = e=>{
    e.preventDefault();
    send(formDelete,"delete");
};
</script>



</body>
</html>  