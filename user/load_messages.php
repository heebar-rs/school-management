<?php
session_start();
require('../connexion.php');

$conn = new mysqli($host,$user,$pass,$dbname);
if ($conn->connect_error) die("Erreur connexion : ".$conn->connect_error);

$conv = intval($_GET['conv'] ?? 0);

$res = $conn->query("
  SELECT id, sender, message, created_at
  FROM messages
  WHERE conversation_id = $conv
  ORDER BY created_at ASC
");

while($m = $res->fetch_assoc()) {
    // Ici sender doit être 'admin' ou 'user'
    $cls = ($m['sender'] === 'admin') ? 'admin' : 'user';
?>
  <div class="msg <?= $cls ?>" data-msg-id="<?= (int)$m['id'] ?>">
    <div class="msg-content">
      <?= nl2br(htmlspecialchars($m['message'])) ?>
      <div class="msg-time"><?= date('H:i', strtotime($m['created_at'])) ?></div>

     
    </div>
  </div>
<?php } ?>
