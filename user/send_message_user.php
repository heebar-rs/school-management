<?php
session_start();
require('../connexion.php');

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Erreur DB");
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['user_id'])) {
    echo "forbidden";
    exit;
}

$conversation_id = (int)($_POST['conversation_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($conversation_id <= 0 || $message === '') {
    echo "invalid";
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, user_id, sender, message, created_at, is_read)
    VALUES (?, ?, 'user', ?, NOW(), 0)
");
$stmt->bind_param("iis", $conversation_id, $_SESSION['user_id'], $message);
$stmt->execute();

// mettre à jour last_message
$conn->query("UPDATE conversations SET last_message = NOW() WHERE id = $conversation_id");

echo "success";
