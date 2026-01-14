<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>loopAcademy</title>

<style>
/* ================= VARIABLES ================= */
:root{
  --primary:#0d6efd;
  --secondary:#24335;
  --bg:#fff;
  --white:#fff;
}

/* ================= HOMEPAGE SLIDER ================= */
.homepage-slider {
  position: relative;
  overflow: hidden;
  height: 450px;
}
.slider-wrapper {
  display: flex;
  transition: transform 0.6s ease-in-out;
  height: 100%;
}
.slide {
  min-width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
}
.slide .container { width: 100%; }
.hero-text-slide {
  color: #fff;
  max-width: 600px;
  padding-left: 60px;
}
.hero-text-slide.text-center { text-align:center; margin:auto; }
.hero-text-slide.text-right { text-align:right; margin-left:auto; margin-right:60px; }
.hero-text-slide p.subtitle { font-size:18px; margin-bottom:10px; }
.hero-text-slide h1 { font-size:32px; margin-bottom:20px; }
.hero-text-slide .hero-btns a { display:inline-block; margin-right:15px; padding:12px 30px; border-radius:30px; text-decoration:none; font-weight:600; transition:.3s; }
.hero-text-slide .boxed-btn { background:var(--primary); color:#fff; }
.hero-text-slide .boxed-btn:hover { opacity:0.9; }
.hero-text-slide .bordered-btn { border:2px solid #fff; color:#fff; }
.hero-text-slide .bordered-btn:hover { background:rgba(255,255,255,0.2); }
.slider-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background:rgba(0,0,0,0.5);
  color:#fff;
  border:none;
  padding:10px 15px;
  cursor:pointer;
  font-size:24px;
  border-radius:50%;
  z-index:10;
}
.slider-nav.prev { left:20px; }
.slider-nav.next { right:20px; }

/* ================= RESET ================= */
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:"Poppins",sans-serif;
}
body{
  width: 100%;
  background:var(--bg);
  color:#333;
}

/* ================= HEADER ================= */
header{
  width: 100%;
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:20px 60px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  position:sticky;
  background-color: #0048b5ff;
  top:0;
  z-index:10;
}
header h1{color:#fff;font-size:26px;}
header nav a{
  color:#fff;
  margin-left:25px;
  text-decoration:none;
  font-weight:500;
}
header nav a.btn{
  background:#fff;
  color:var(--secondary);
  padding:8px 20px;
  border-radius:30px;
}

/* ================= CONTACT MODAL ================= */
#contactModal {
  background: linear-gradient(135deg, var(--secondary), var(--primary));
  color: #fff;
  padding: 35px;
  border-radius: 20px;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 40px 100px rgba(0,0,0,.3);
}
.contact-item{
  display:flex;
  gap:10px;
  align-items:center;
  margin:10px 0;
}
.contact-item p, .contact-item a { margin:0; }

/* ================= HERO ================= */
.hero{
  width: 100%;
  display:flex;
  justify-content:center;
  align-items:center;
  gap:300px;
  padding:80px 60px;
}
.hero-text{max-width:50%;}
.hero-text h2{
  font-size:46px;
  color:var(--secondary);
}
.hero-text p{
  margin:20px 0;
  line-height:1.7;
}
.hero-text a{
  display:inline-block;
  margin-right:15px;
  padding:14px 34px;
  border-radius:30px;
  text-decoration:none;
  font-weight:600;
  transition:.4s;
}
.primary{
  background:var(--primary);
  color:#fff;
}
.secondary{
  border:2px solid var(--primary);
  color:var(--primary);
}
.primary:hover,.secondary:hover{
  transform:translateY(-6px);
}
.hero img{
  max-width:520px;
  animation:float 4s ease-in-out infinite;
}

/* ================= FEATURES ================= */
.features{
  background:#fff;
  padding:80px 60px;
}
.features h3{
  text-align:center;
  font-size:34px;
  margin-bottom:50px;
}
.features-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
  gap:35px;
}
.feature{
  background:var(--bg);
  background-color: #c3d8ffff;
  padding:35px;
  border-radius:20px;
  text-align:center;
  transition:.4s;
}
.feature:hover{
  transform:translateY(-15px);
  box-shadow:0 25px 50px rgba(0,0,0,.15);
}

/* ================= CTA ================= */
.cta{
  width: 100%;
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  color:#fff;
  padding:80px 60px;
  text-align:center;
}
.cta a{
  display:inline-block;
  margin-top:30px;
  background:#fff;
  color:var(--secondary);
  padding:14px 38px;
  border-radius:30px;
  text-decoration:none;
  font-weight:600;
}

/* ================= FOOTER ================= */
footer{
  width: 100%;
  background:#0d1117;
  color:#aaa;
  padding:30px;
  text-align:center;
}
footer span{color:#fff;}

/* ================= MODALS ================= */
#overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.55);
  backdrop-filter:blur(8px);
  opacity:0;
  pointer-events:none;
  transition:.4s;
  z-index:20;
}
#overlay.active{
  opacity:1;
  pointer-events:auto;
}

.modal{
  position:fixed;
  top:50%;
  left:50%;
  transform:translate(-50%,-60%) scale(.9);
  background:#fff;
  padding:35px;
  border-radius:20px;
  width:380px;
  box-shadow:0 40px 100px rgba(0,0,0,.3);
  opacity:0;
  pointer-events:none;
  transition:.45s ease;
  z-index:30;
}
.modal.large{width:550px;}
.modal.active{
  opacity:1;
  transform:translate(-50%,-50%) scale(1);
  pointer-events:auto;
}
.modal h2{
  margin-bottom:20px;
  color:var(--secondary);
}

.modal input,
.modal select,
.modal textarea{
  width:100%;
  padding:12px;
  margin-bottom:12px;
  border-radius:10px;
  border:1px solid #ddd;
}
.full{width:100%;}

.tabs{
  display:flex;
  gap:10px;
  margin-bottom:15px;
}
.tabs button{
  flex:1;
  padding:10px;
  border:none;
  border-radius:10px;
  cursor:pointer;
  background:#eee;
}
.tabs button.active{
  background:var(--primary);
  color:#fff;
}

.form-section{display:none;}
.form-section.active{display:block;}

.switch{
  text-align:center;
  margin-top:15px;
}
.switch span{
  color:var(--primary);
  cursor:pointer;
  font-weight:600;
}

.error{color:red;text-align:center;}

@keyframes float{
  0%,100%{transform:translateY(0);}
  50%{transform:translateY(-18px);}
}
</style>
</head>

<body>

<header>
  <h1>loopAcademy</h1>
  <nav>
    <a href="#features">Features</a>
    <a href="#" class="btn" onclick="openModal('loginModal')">Login</a>
  </nav>
</header>

<!-- ================= HOMEPAGE SLIDER ================= -->
<section class="homepage-slider">
  <div class="slider-wrapper">
    <div class="slide active" style="background: linear-gradient(to right, rgba(36,51,82,0.9), rgba(0, 102, 255, 0.6)); box-shadow: inset 0 0 50px rgba(0,0,0,0.4);">
      <div class="container">
        <div class="hero-text-slide text-left">
          <p class="subtitle">Innovative Learning</p>
          <h1>Welcome to loopAcademy</h1>
          <div class="hero-btns">
            <a href="#" class="secondary" onclick="openContactModal()">Contact Us</a>
          </div>
        </div>
      </div>
    </div>

    <div class="slide" style="background: linear-gradient(to right, rgba(23, 51, 107, 0.8), rgba(28, 75, 147, 0.7), rgba(0, 85, 255, 0.8)); box-shadow: inset 0 0 50px rgba(0,0,0,0.3);">
      <div class="container">
        <div class="hero-text-slide text-center">
          <p class="subtitle">Quality Education</p>
          <h1>Supporting Students and Teachers</h1>
          <div class="hero-btns">
            <a href="#" class="primary" onclick="openModal('loginModal')">get started now</a>
          </div>
        </div>
      </div>
    </div>

    <div class="slide" style="background: linear-gradient(to left, rgba(18, 46, 102, 0.9), rgba(0, 102, 255, 0.6)); box-shadow: inset 0 0 50px rgba(0,0,0,0.4);">
      <div class="container">
        <div class="hero-text-slide text-right">
          <h1>Simplified Management</h1>
          <div class="hero-btns">
            <a href="website/aboutus.php" class="boxed-btn">about us</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <button class="slider-nav prev">&#10094;</button>
  <button class="slider-nav next">&#10095;</button>
</section>

<section class="hero">
  <div class="hero-text">
    <h2>Modern School Management System</h2>
    <p>Stay connected and access your information in seconds.<br>
    All your academic information, always up to date.</p>
    <a href="#" class="primary" onclick="openModal('loginModal')">log in</a>
    <a href="#" class="secondary" onclick="openModal('registerModal')">sign up</a>
  </div>
  <img src="website/logo.jpeg" alt="loopAcademy">
</section>

<section class="features" id="features">
  <h3>why loop Academy ?</h3>
  <div class="features-grid">
    <div class="feature">Easy Access to Information</div>
    <div class="feature">Better Organization</div>
    <div class="feature">Time-Saving</div>
    <div class="feature">Faster Administrative Processes</div>
  </div>
</section>

<section class="cta">
  <h3>Ready to start ?</h3>
  <a href="#" onclick="openModal('loginModal')">access</a>
</section>

<footer>
  © <?=date('Y')?> <span>loopAcademy</span>
</footer>

<!-- ================= MODALS ================= -->
<div class="modal" id="contactModal">
  <h2>Contact Us</h2>

  <div class="contact-item">
    <span>📧</span>
    <p>contact@loop8academy.com</p>
  </div>

  <div class="contact-item">
    <span>📞</span>
    <p>+212 6 12 34 56 78</p>
  </div>

  <div class="contact-item">
    <span>📸</span>
    <a href="https://instagram.com/loopacademy" target="_blank" style="color: #fff; text-decoration: none;">
      instagram.com/loop_academy
    </a>
  </div>

  <div class="contact-item">
    <span>🐦</span>
    <a href="https://twitter.com/loopacademy" target="_blank" style="color: #fff; text-decoration: none;">
      twitter.com/loop_academy
    </a>
  </div>
</div>

<div id="overlay" onclick="closeAll()"></div>

<div class="modal" id="loginModal">
  <h2>Connexion</h2>

  <form action="traitementlogin.php" method="post" id="loginForm">
    <input name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button class="primary full">Login</button>

    <?php if(isset($_GET['error']) && $_GET['error']==='login'){ ?>
      <p class="error" id="loginServerError">Information incorrects, Try again</p>
    <?php } ?>
  </form>

  <p class="switch">Pas de compte ?
    <span onclick="switchModal('loginModal','registerModal')">Créer un compte</span>
  </p>
</div>

<div class="modal large" id="registerModal">
  <h2>Inscription</h2>

  <div class="tabs">
    <button class="tab active" data-target="student">Student</button>
  </div>

  <!-- STUDENT -->
  <form id="student" class="form-section active"
        action="traitementregister.php"
        method="post"
        enctype="multipart/form-data">
    <input type="hidden" name="role" value="student">

    <input name="first_name" placeholder="First name" required>
    <input name="last_name" placeholder="Last name" required>
    <input name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="date" name="birth_date" placeholder="Birth date" required>

    <select name="gender" required>
      <option value="">-- Choisir --</option>
      <option value="F">Femme</option>
      <option value="M">Homme</option>
    </select>

    <input name="phone" placeholder="Phone">

    <p>Entrez votre photo:</p>
    <input type="file" name="photo"><br><br>

    <!-- ✅ erreurs serveur register -->
    <?php if(isset($_GET['register']) && $_GET['register'] !== 'success'){ ?>
      <p class="error" id="registerServerError">
        <?php
          $r = $_GET['register'];
          if($r==='first_name') echo "First name invalide.";
          else if($r==='last_name') echo "Last name invalide.";
          else if($r==='username') echo "Username invalide (3-30, lettres/chiffres . _ -).";
          else if($r==='username_taken') echo "Ce username existe déjà.";
          else if($r==='password') echo "Password invalide (min 6 caractères).";
          else if($r==='birth_date') echo "Birth date invalide.";
          else if($r==='birth_future') echo "Birth date ne peut pas être dans le futur.";
          else if($r==='birth_age') echo "Âge invalide (minimum 5 ans).";
          else if($r==='gender') echo "Veuillez choisir le gender.";
          else if($r==='phone') echo "Phone invalide (0612345678 ou +212612345678).";
          else if($r==='photo_type') echo "Photo invalide (JPG/PNG/WEBP/GIF).";
          else if($r==='photo_size') echo "Photo trop grande (max 2MB).";
          else if($r==='photo_upload') echo "Erreur upload photo.";
          else if($r==='db' || $r==='db_user' || $r==='db_student' || $r==='db_userid') echo "Erreur serveur / base de données.";
          else echo "Erreur d'inscription.";
        ?>
      </p>
    <?php } ?>

    <?php if(isset($_GET['register']) && $_GET['register'] === 'success'){ ?>
      <p style="color:green;text-align:center;" id="registerSuccessMsg">
        Inscription réussie ✅ Vous pouvez vous connecter.
      </p>
    <?php } ?>

    <button class="primary full">Register Student</button>
  </form>

  <p class="switch">Déjà un compte ?
    <span onclick="switchModal('registerModal','loginModal')">Se connecter</span>
  </p>
</div>

<script>
/* =========================
   MODALS
   ========================= */
function openModal(id){
  document.getElementById(id).classList.add('active');
  document.getElementById('overlay').classList.add('active');
}
function closeAll(){
  document.querySelectorAll('.modal').forEach(m=>m.classList.remove('active'));
  document.getElementById('overlay').classList.remove('active');
}
function switchModal(a,b){
  document.getElementById(a).classList.remove('active');
  document.getElementById(b).classList.add('active');
  document.getElementById('overlay').classList.add('active');
}
function openContactModal(){
  openModal('contactModal');
}

/* =========================
   SLIDER
   ========================= */
const slides = document.querySelectorAll('.slide');
const wrapper = document.querySelector('.slider-wrapper');
let currentIndex = 0;

function showSlide(index){
  if(index<0) index = slides.length-1;
  if(index>=slides.length) index = 0;
  currentIndex = index;
  wrapper.style.transform = `translateX(-${index*100}%)`;
}
document.querySelector('.slider-nav.prev').onclick = ()=>showSlide(currentIndex-1);
document.querySelector('.slider-nav.next').onclick = ()=>showSlide(currentIndex+1);
setInterval(()=>showSlide(currentIndex+1), 5000);

/* =========================
   CONTROLE DE SAISIE (JS)
   ========================= */
function showFormError(form, message) {
  let box = form.querySelector(".js-error");
  if (!box) {
    box = document.createElement("p");
    box.className = "error js-error";
    box.style.marginTop = "10px";
    form.appendChild(box);
  }
  box.textContent = message;
}
function clearFormError(form) {
  const box = form.querySelector(".js-error");
  if (box) box.textContent = "";
}
function isValidName(str) {
  return /^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,}$/.test((str||"").trim());
}
function isValidUsername(str) {
  return /^[A-Za-z0-9._-]{3,30}$/.test((str||"").trim());
}
function isValidPhoneMA(str) {
  const s = (str||"").trim().replace(/\s+/g, "");
  if (s === "") return true;
  return /^(0[67]\d{8}|\+212[67]\d{8})$/.test(s);
}
function calcAge(dateStr) {
  const d = new Date(dateStr);
  if (Number.isNaN(d.getTime())) return null;
  const now = new Date();
  let age = now.getFullYear() - d.getFullYear();
  const m = now.getMonth() - d.getMonth();
  if (m < 0 || (m === 0 && now.getDate() < d.getDate())) age--;
  return age;
}
function isFutureDate(dateStr) {
  const d = new Date(dateStr);
  if (Number.isNaN(d.getTime())) return false;
  const today = new Date();
  d.setHours(0,0,0,0);
  today.setHours(0,0,0,0);
  return d > today;
}

/* ===== Login validation ===== */
(function(){
  const loginForm = document.getElementById("loginForm");
  if (!loginForm) return;

  loginForm.addEventListener("submit", function(e){
    clearFormError(loginForm);

    const username = loginForm.querySelector('input[name="username"]').value;
    const password = loginForm.querySelector('input[name="password"]').value;

    if (!isValidUsername(username)) {
      e.preventDefault();
      showFormError(loginForm, "Username invalide (3-30 caractères, lettres/chiffres et . _ - uniquement).");
      return;
    }
    if ((password||"").trim().length < 6) {
      e.preventDefault();
      showFormError(loginForm, "Password invalide (minimum 6 caractères).");
      return;
    }
  });
})();

/* ===== Register validation ===== */
(function(){
  const registerForm = document.getElementById("student");
  if (!registerForm) return;

  registerForm.addEventListener("submit", function(e){
    clearFormError(registerForm);

    const firstName = registerForm.querySelector('input[name="first_name"]').value;
    const lastName  = registerForm.querySelector('input[name="last_name"]').value;
    const username  = registerForm.querySelector('input[name="username"]').value;
    const password  = registerForm.querySelector('input[name="password"]').value;
    const birthDate = registerForm.querySelector('input[name="birth_date"]').value;
    const gender    = registerForm.querySelector('select[name="gender"]').value;
    const phone     = registerForm.querySelector('input[name="phone"]').value;
    const photoInp  = registerForm.querySelector('input[name="photo"]');

    if (!isValidName(firstName)) {
      e.preventDefault();
      showFormError(registerForm, "First name invalide (au moins 2 lettres).");
      return;
    }
    if (!isValidName(lastName)) {
      e.preventDefault();
      showFormError(registerForm, "Last name invalide (au moins 2 lettres).");
      return;
    }
    if (!isValidUsername(username)) {
      e.preventDefault();
      showFormError(registerForm, "Username invalide (3-30 caractères, lettres/chiffres et . _ - uniquement).");
      return;
    }
    if ((password||"").trim().length < 6) {
      e.preventDefault();
      showFormError(registerForm, "Password invalide (minimum 6 caractères).");
      return;
    }
    if (!birthDate) {
      e.preventDefault();
      showFormError(registerForm, "Birth date obligatoire.");
      return;
    }
    if (isFutureDate(birthDate)) {
      e.preventDefault();
      showFormError(registerForm, "Birth date invalide (ne peut pas être dans le futur).");
      return;
    }
    const age = calcAge(birthDate);
    if (age === null) {
      e.preventDefault();
      showFormError(registerForm, "Birth date invalide.");
      return;
    }
    if (age < 5) {
      e.preventDefault();
      showFormError(registerForm, "Âge invalide (minimum 5 ans).");
      return;
    }
    if (!gender) {
      e.preventDefault();
      showFormError(registerForm, "Veuillez choisir le gender.");
      return;
    }
    if (!isValidPhoneMA(phone)) {
      e.preventDefault();
      showFormError(registerForm, "Phone invalide. Exemple: 0612345678 ou +212612345678");
      return;
    }

    if (photoInp && photoInp.files && photoInp.files.length > 0) {
      const f = photoInp.files[0];
      const allowed = ["image/jpeg","image/png","image/webp","image/gif"];
      if (!allowed.includes(f.type)) {
        e.preventDefault();
        showFormError(registerForm, "Photo invalide (formats acceptés: JPG, PNG, WEBP, GIF).");
        return;
      }
      const maxSize = 2 * 1024 * 1024;
      if (f.size > maxSize) {
        e.preventDefault();
        showFormError(registerForm, "Photo trop grande (max 2MB).");
        return;
      }
    }
  });
})();

/* =========================
   AUTO OPEN MODAL (URL)
   ========================= */
(function(){
  const p = new URLSearchParams(window.location.search);

  // open login on error=login
  if(p.get("error")==="login"){
    openModal("loginModal");
  }

  // open register on ?register=...
  if(p.has("register")){
    openModal("registerModal");
  }
})();
</script>

</body>
</html>
