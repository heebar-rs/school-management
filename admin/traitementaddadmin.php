<?php
session_start();
require __DIR__ . '/../connexion.php';

// Sécurité: seulement admin
if(($_SESSION['role'] ?? '') !== 'admin'){
    header('Location: ../index.php?error=forbidden');
    exit;
}



$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if($username === '' || $password === ''){
    header('Location: list_admins.php?error=missing');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'admin';

$stmtUser = $connexion->prepare("INSERT INTO users(username, password, role) VALUES (?, ?, ?)");
$stmtUser->bind_param("sss", $username, $hash, $role);
$stmtUser->execute();
$user_id = $connexion->insert_id;


// IMPORTANT: créer la ligne dans admins
$stmtAdmin = $connexion->prepare("INSERT INTO admins (user_id) VALUES (?)");
$stmtAdmin->bind_param("i", $user_id);
$stmtAdmin->execute();
$stmtAdmin->close();



// créer entrée admins (schéma: admins.id, admins.user_id, admins.created_at)
$stmtAdmin = $connexion->prepare("INSERT INTO admins(user_id, created_at) VALUES (?, NOW())");
$stmtAdmin->bind_param("i", $user_id);
$stmtAdmin->execute();

header("Location: list_admins.php?success=1");
exit;
