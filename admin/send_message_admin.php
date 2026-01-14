<?php
session_start();
require('../connexion.php');

if(!isset($_SESSION['admin'])) exit;

$conn = new mysqli($host,$user,$pass,$dbname);

$conv = intval($_POST['conversation_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');

if($conv <= 0 || $msg==='') exit;

// INSERT message admin
$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender, message)
    VALUES (?, 'admin', ?)
");
$stmt->bind_param("is",$conv,$msg);
$stmt->execute();

// Mise à jour du dernier message
$conn->query("UPDATE conversations SET last_message=NOW() WHERE id=$conv");

echo "success";
