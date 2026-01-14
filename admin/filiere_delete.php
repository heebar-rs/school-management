<?php
require('../connexion.php');
// Connexion MySQLi
$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}
$id = (int) $_GET['id'];
$connexion->query("DELETE FROM filieres WHERE id=$id");

header("Location: admindashboard.php");
