<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "ecole";

$connexion = new mysqli($host, $user, $pass, $dbname);
if ($connexion->connect_error) {
    die("Erreur de connexion : " . $connexion->connect_error);
}
$connexion->set_charset("utf8mb4");
