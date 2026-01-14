<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>About Us | Loop Academy</title>

<link rel="stylesheet" href="school.css">

<style>
/* ================= VARIABLES ================= */
:root{
  --primary:#0d6efd;
  --secondary:#243352;
  --bg:#f4f6fb;
  --white:#fff;
}

/* ================= RESET ================= */
*{
  margin:0;
  padding:0;
  box-sizing:border-box;
  font-family:"Poppins",sans-serif;
}
body{
  width:100%;
  background:var(--bg);
  color:#333;
}

/* ================= HEADER ================= */
header{
  width:100%;
  background:linear-gradient(135deg,var(--secondary),var(--primary));
  padding:20px 60px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  position:sticky;
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

/* ================= ABOUT HERO ================= */
.about-hero{
  padding:100px 60px;
  text-align:center;
  background:linear-gradient(
    135deg,
    rgba(13,110,253,0.85),
    rgba(36,51,82,0.95)
  );
  color:#fff;
}
.about-hero h2{
  font-size:42px;
  margin-bottom:20px;
}
.about-hero p{
  max-width:750px;
  margin:auto;
  line-height:1.8;
  font-size:18px;
}

/* ================= ABOUT CONTENT ================= */
.about-content{
  padding:80px 60px;
  background:#fff;
}
.about-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
  gap:40px;
}
.about-box{
  background:var(--bg);
  padding:35px;
  border-radius:20px;
  transition:.4s;
}
.about-box:hover{
  transform:translateY(-10px);
  box-shadow:0 25px 45px rgba(0,0,0,.15);
}
.about-box h3{
  color:var(--secondary);
  margin-bottom:15px;
}
.about-box p{
  line-height:1.7;
}

/* ================= VALUES ================= */
.values{
  padding:80px 60px;
  background:var(--bg);
  text-align:center;
}
.values h3{
  font-size:34px;
  margin-bottom:50px;
}
.values-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:30px;
}
.value{
  background:#fff;
  padding:30px;
  border-radius:18px;
  font-weight:500;
  transition:.3s;
}
.value:hover{
  background:var(--primary);
  color:#fff;
}

/* ================= FOOTER ================= */
footer{
  width:100%;
  background:#0d1117;
  color:#aaa;
  padding:30px;
  text-align:center;
}
footer span{color:#fff;}
</style>
</head>

<body>

<header>
  <h1>Loop Academy</h1>
  <nav>
    <a href="../first.php">Home</a>
    <a href="aboutus.php">About Us</a>
   
  
  </nav>
</header>

<!-- ================= ABOUT HERO ================= -->
<section class="about-hero">
  <h2>About Loop Academy</h2>
  <p>
    Loop Academy is a modern school management platform designed to simplify
    academic life by providing fast, secure, and centralized access to
    school information.
  </p>
</section>

<!-- ================= ABOUT CONTENT ================= -->
<section class="about-content">
  <div class="about-grid">
    <div class="about-box">
      <h3>Our Mission</h3>
      <p>
        Our mission is to help students and administrators manage academic
        information efficiently through a reliable and user-friendly digital system.
      </p>
    </div>

    <div class="about-box">
      <h3>Our Vision</h3>
      <p>
        We aim to modernize school management by replacing traditional
        paperwork with secure, fast, and accessible digital solutions.
      </p>
    </div>

    <div class="about-box">
      <h3>Who We Serve</h3>
      <p>
        Loop Academy is designed for students, and administrators
        who want a smarter way to manage academic data.
      </p>
    </div>
  </div>
</section>



<footer>
  © <?=date('Y')?> <span>Loop Academy</span>
</footer>

</body>
</html>
