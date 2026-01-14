<?php
session_start();
require 'connexion.php';

$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error){
    die("Erreur connexion : " . $conn->connect_error);
}

if(isset($_POST['username']) && isset($_POST['password'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // contrôle serveur
    if($username === "" || strlen($username) < 3 || strlen($password) < 6){
        header("Location: first.php?login=invalid");
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if($row['role'] === 'admin'){
                header("Location: admin/admindashboard.php");
            } else {
                header("Location: user/userdashboard.php");
            }
            exit;
        }
    }
}

// Si erreur login -> retourne au même first.php + modal s’ouvre
header("Location: first.php?login=fail");
exit;
?>
