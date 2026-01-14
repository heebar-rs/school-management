<?php
require('../connexion.php');
// Connexion MySQLi
$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}
if (!empty($_POST['nom_filiere'])) {
    $nom = htmlspecialchars($_POST['nom_filiere']);
    $connexion->query("INSERT INTO filieres (nom_filiere) VALUES ('$nom')");
}

header("Location: admindashboard.php");
