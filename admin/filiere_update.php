<?php
require('../connexion.php');
// Connexion MySQLi
$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}
$id  = (int) $_POST['id'];
$nom = htmlspecialchars($_POST['nom_filiere']);

$connexion->query("UPDATE filieres SET nom_filiere='$nom' WHERE id=$id");

header("Location: admindashboard.php");
