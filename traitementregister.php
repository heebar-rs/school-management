<?php
session_start();
require('connexion.php');

// Connexion MySQLi
$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    // on renvoie au lieu de die (même logique que login)
    header("Location: first.php?register=db");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ---------------------------
// Helpers validation
// ---------------------------
function goRegister($code){
    header("Location: first.php?register=" . urlencode($code));
    exit;
}

function isValidName($s){
    $s = trim($s);
    return (bool)preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,}$/u", $s);
}

function isValidUsername($s){
    $s = trim($s);
    return (bool)preg_match("/^[A-Za-z0-9._-]{3,30}$/", $s);
}

function isValidPhoneMA($s){
    $s = trim(preg_replace("/\s+/", "", $s));
    if($s === "") return true; // optionnel
    return (bool)preg_match("/^(0[67]\d{8}|\+212[67]\d{8})$/", $s);
}

function calcAge($dateStr){
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    if(!$d) return null;
    $today = new DateTime('today');
    if($d > $today) return -1;
    return $d->diff($today)->y;
}

// ---------------------------
// Récupération des champs
// ---------------------------
$username   = $_POST['username'] ?? '';
$password   = $_POST['password'] ?? '';

$last_name  = $_POST['last_name'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$gender     = $_POST['gender'] ?? '';
$phone      = $_POST['phone'] ?? '';

// ---------------------------
// Contrôles serveur (TOUT)
// ---------------------------
$usernameT = trim($username);
$passwordT = trim($password);
$firstT    = trim($first_name);
$lastT     = trim($last_name);
$birthT    = trim($birth_date);
$genderT   = trim($gender);
$phoneT    = trim($phone);

if(!isValidName($firstT))   goRegister("first_name");
if(!isValidName($lastT))    goRegister("last_name");
if(!isValidUsername($usernameT)) goRegister("username");
if(strlen($passwordT) < 6)  goRegister("password");

$age = calcAge($birthT);
if($birthT === "" || $age === null) goRegister("birth_date");
if($age < 0) goRegister("birth_future");
if($age < 5) goRegister("birth_age");

if($genderT !== "F" && $genderT !== "M") goRegister("gender");

if(!isValidPhoneMA($phoneT)) goRegister("phone");

// ---------------------------
// Gestion photo (optionnelle) + validation type/taille
// ---------------------------
$photoData = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        goRegister("photo_upload");
    }

    $tmp = $_FILES['photo']['tmp_name'];
    $size = $_FILES['photo']['size'] ?? 0;

    if($size > 2 * 1024 * 1024){
        goRegister("photo_size"); // max 2MB
    }

    $mime = mime_content_type($tmp);
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if(!in_array($mime, $allowed, true)){
        goRegister("photo_type");
    }

    $photoData = file_get_contents($tmp);
}

// ---------------------------
// Vérifier username unique
// ---------------------------
$check = $connexion->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$check->bind_param("s", $usernameT);
$check->execute();
$res = $check->get_result();
if($res && $res->num_rows > 0){
    $check->close();
    goRegister("username_taken");
}
$check->close();

// ---------------------------
// INSERTION USER
// ---------------------------
$hash = password_hash($passwordT, PASSWORD_DEFAULT);
$role = 'student';

$stmtUser = $connexion->prepare("INSERT INTO users(username, password, role) VALUES (?, ?, ?)");
$stmtUser->bind_param("sss", $usernameT, $hash, $role);

if (!$stmtUser->execute()) {
    $stmtUser->close();
    goRegister("db_user");
}

$user_id = $connexion->insert_id;
$stmtUser->close();

if (!$user_id) {
    goRegister("db_userid");
}

// ---------------------------
// INSERTION STUDENT
// ---------------------------
$stmt = $connexion->prepare("
    INSERT INTO students(
        user_id, last_name, first_name, birth_date,
        gender, phone, photo
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssss",
    $user_id, $lastT, $firstT, $birthT,
    $genderT, $phoneT, $photoData
);

if ($photoData !== null) {
    $stmt->send_long_data(6, $photoData);
}

if (!$stmt->execute()) {
    $stmt->close();
    goRegister("db_student");
}

$stmt->close();
$connexion->close();

// ✅ Success
header("Location: first.php?register=success");
exit;
?>
