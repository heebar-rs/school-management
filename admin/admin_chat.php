<?php
session_start();
require('../connexion.php');

$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error) die("Erreur connexion : ".$conn->connect_error);

/* ================= GESTION AJAX ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    $admin_id = $_SESSION['admin_id'] ?? 0;

    /* ENVOYER MESSAGE */
    if($_POST['action']==='send'){
        $conv_id = intval($_POST['conversation_id']);
        $msg = trim($_POST['message']);

        if(empty($msg)){
            echo json_encode(['status' => 'error', 'message' => 'Le message ne peut pas être vide']);
            exit;
        }

      
        $stmt = $conn->prepare("INSERT INTO messages(conversation_id, sender, message, created_at) VALUES(?, 'admin', ?, NOW())");
        $stmt->bind_param("is", $conv_id, $msg);

        if($stmt->execute()){
            $conn->query("UPDATE conversations SET last_message = NOW() WHERE id = $conv_id");
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'envoi']);
        }
        exit;
    }

    /* SUPPRIMER CONVERSATION */
    if($_POST['action']==='delete_conversation'){
        $conv_id = intval($_POST['conversation_id']);
        $stmt = $conn->prepare("DELETE FROM conversations WHERE id=?");
        $stmt->bind_param("i",$conv_id);
        echo $stmt->execute() ? json_encode(['status' => 'success']) : json_encode(['status' => 'error']);
        exit;
    }

    /* SUPPRIMER MESSAGE */
    if($_POST['action']==='delete_message'){
        $msg_id = intval($_POST['message_id']);
        $stmt = $conn->prepare("DELETE FROM messages WHERE id=?");
        $stmt->bind_param("i",$msg_id);
        echo $stmt->execute() ? json_encode(['status' => 'success']) : json_encode(['status' => 'error']);
        exit;
    }

    /* MODIFIER MESSAGE */
    if($_POST['action']==='update_message'){
        $msg_id = intval($_POST['message_id']);
        $content = trim($_POST['message']);
        if(empty($content)){
            echo json_encode(['status' => 'error', 'message' => 'Le message ne peut pas être vide']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE messages SET message=? WHERE id=?");
        $stmt->bind_param("si",$content,$msg_id);
        echo $stmt->execute() ? json_encode(['status' => 'success']) : json_encode(['status' => 'error']);
        exit;
    }

    /* MARQUER COMME LU */
    if($_POST['action']==='mark_read'){
        $conv_id = intval($_POST['conversation_id']);
        // ✅ sender != 'admin' (pas l'ID)
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender != 'admin'");
        $stmt->bind_param("i", $conv_id);
        echo $stmt->execute() ? json_encode(['status' => 'success']) : json_encode(['status' => 'error']);
        exit;
    }

    /* RECHERCHER CONVERSATIONS */
    if($_POST['action']==='search'){
        $search = $conn->real_escape_string($_POST['search']);
        $query = "SELECT c.id, u.username, COUNT(m.id) as msg_count
                  FROM conversations c
                  JOIN users u ON c.user_id = u.id
                  LEFT JOIN messages m ON c.id = m.conversation_id
                  WHERE u.username LIKE '%$search%'
                  GROUP BY c.id
                  ORDER BY c.last_message DESC";
        $result = $conn->query($query);
        $html = '';
        while($c = $result->fetch_assoc()){
            $html .= '<a href="#" class="chat-user" data-id="'.$c['id'].'">
                        <div>
                            <span class="username">'.htmlspecialchars($c['username']).'</span>
                            <span class="msg-count">'.$c['msg_count'].' messages</span>
                        </div>
                        <button class="delete-conv" data-id="'.$c['id'].'" title="Supprimer">🗑️</button>
                      </a>';
        }
        echo json_encode(['status' => 'success', 'html' => $html]);
        exit;
    }
}

$admin_id = $_SESSION['admin_id'] ?? 0;

$conversations = $conn->query("
    SELECT
        c.id,
        u.username,
        COUNT(m.id) AS msg_count,
        SUM(CASE
            WHEN m.is_read = 0 AND m.sender != 'admin'
            THEN 1 ELSE 0
        END) AS unread
    FROM conversations c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN messages m ON c.id = m.conversation_id
    GROUP BY c.id, u.username
    ORDER BY c.last_message DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Chat - Tableau de bord</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #4361ee;
    --primary-dark: #3a56d4;
    --secondary: #7209b7;
    --danger: #f72585;
    --warning: #f8961e;
    --success: #4cc9f0;
    --dark: #212529;
    --light: #f8f9fa;
    --gray: #6c757d;
    --border: #dee2e6;
    --shadow: 0 4px 20px rgba(0,0,0,0.08);
    --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
    --radius: 12px;
    --radius-sm: 8px;
    --transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }

.chat-container {
    display: flex; width: 100%; max-width: 1400px; height: 90vh;
    background: white; border-radius: var(--radius); overflow: hidden;
    box-shadow: var(--shadow-lg); animation: fadeIn 0.6s ease-out;
}

.chat-list {
    width: 35%; background: var(--light); border-right: 1px solid var(--border);
    display: flex; flex-direction: column; transition: var(--transition);
}

.list-header {
    padding: 20px; background: white; border-bottom: 1px solid var(--border);
}

.list-header h2 { color: var(--dark); font-size: 1.5rem; font-weight: 600; }

.search-container { position: relative; width: 100%; margin-top: 15px; }
.search-container input {
    width: 100%; padding: 12px 45px 12px 15px; border-radius: 30px;
    border: 1px solid var(--border); background: #f8f9fa;
    transition: var(--transition); font-size: 14px;
}
.search-container input:focus {
    outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}
.search-container i {
    position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
    color: var(--gray); font-size: 16px;
}

.conversations { flex: 1; overflow-y: auto; padding: 10px; }

.chat-user {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 18px; border-radius: var(--radius-sm); margin-bottom: 8px;
    background: white; border-left: 4px solid transparent; cursor: pointer;
    transition: var(--transition); border: 1px solid transparent;
}
.chat-user:hover { background: #e8f0fe; border-color: var(--primary); transform: translateX(5px); }
.chat-user.active {
    background: #e8f0fe; border-left-color: var(--primary);
    box-shadow: 0 3px 10px rgba(67, 97, 238, 0.15);
}
.username { display: block; font-weight: 600; color: var(--dark); margin-bottom: 4px; }
.msg-count { font-size: 12px; color: var(--gray); }

.unread-badge {
    background: var(--danger); color: white; font-size: 11px;
    font-weight: 600; padding: 2px 8px; border-radius: 10px;
    margin-right: 10px; animation: pulse 2s infinite;
}
.delete-conv {
    background: transparent; border: none; color: var(--gray);
    cursor: pointer; padding: 8px; border-radius: 50%;
    transition: var(--transition); font-size: 16px;
}
.delete-conv:hover { background: #ffeaea; color: var(--danger); transform: rotate(90deg); }

.chat-box {
    width: 65%; display: flex; flex-direction: column;
    position: relative; background: #ffffff;
}

.chat-header {
    padding: 18px 20px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
    position: sticky; top: 0; z-index: 10;
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.78);
}

.chat-header h3 { color: var(--dark); font-size: 1.2rem; }

.chat-actions button {
    background: transparent; border: 1px solid var(--border);
    color: var(--gray); padding: 8px 14px; border-radius: var(--radius-sm);
    margin-left: 8px; cursor: pointer; transition: var(--transition);
    font-size: 14px; font-weight: 500;
}
.chat-actions button:hover { background: var(--light); color: var(--dark); }
.chat-actions button.mark-read:hover { background: var(--success); color: white; border-color: var(--success); }

/* === Messages zone premium === */
.messages-container {
    flex: 1; position: relative; overflow: hidden;
    background: radial-gradient(1200px 600px at 20% 0%, rgba(67,97,238,0.10), transparent 60%),
                radial-gradient(1200px 600px at 100% 30%, rgba(114,9,183,0.10), transparent 55%),
                linear-gradient(180deg, #fbfcff 0%, #f6f7fb 100%);
}

.messages {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    padding: 24px; overflow-y: auto; display: flex; flex-direction: column;
}

/* Scrollbar clean */
.conversations::-webkit-scrollbar,
.messages::-webkit-scrollbar { width: 10px; }
.conversations::-webkit-scrollbar-thumb,
.messages::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.12);
  border-radius: 999px;
  border: 3px solid rgba(255,255,255,0.7);
}
.conversations::-webkit-scrollbar-thumb:hover,
.messages::-webkit-scrollbar-thumb:hover { background: rgba(0,0,0,0.20); }

.msg { display: flex; margin-bottom: 16px; align-items: flex-end; gap: 10px; }
.msg.user { justify-content: flex-start; }
.msg.admin { justify-content: flex-end; }

.msg::before{
  content:"";
  width: 34px; height: 34px; border-radius: 50%;
  flex: 0 0 34px;
  box-shadow: 0 6px 14px rgba(0,0,0,0.10);
  background-size: cover; background-position: center;
}
.msg.user::before{
  background: radial-gradient(circle at 30% 30%, rgba(67,97,238,.25), rgba(0,0,0,0) 60%),
              linear-gradient(135deg, #ffffff, #e9eefc);
  border: 1px solid rgba(0,0,0,0.06);
}
.msg.admin::before{
  order: 2;
  background: linear-gradient(135deg, #4361ee, #7209b7);
  border: 1px solid rgba(255,255,255,0.25);
}

.msg-content {
    max-width: 70%;
    padding: 14px 18px;
    border-radius: 20px;
    word-wrap: break-word;
    position: relative;
    border: 1px solid rgba(0,0,0,0.06);
    line-height: 1.45;
    letter-spacing: .1px;
    transition: var(--transition);
}

.msg-content:hover{
  transform: translateY(-1px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.10);
}

.msg.user .msg-content {
    background: rgba(255,255,255,0.85);
    box-shadow: 0 8px 18px rgba(0,0,0,0.06);
    border-bottom-left-radius: 8px;
}

.msg.admin .msg-content {
    background: linear-gradient(135deg, rgba(67,97,238,1) 0%, rgba(114,9,183,1) 100%);
    color: white;
    box-shadow: 0 10px 22px rgba(67,97,238,0.22);
    border-bottom-right-radius: 8px;
}

/* tails */
.msg.user .msg-content::after{
  content:"";
  position:absolute;
  left:-6px; bottom:10px;
  width:12px; height:12px;
  background: rgba(255,255,255,0.85);
  border-left: 1px solid rgba(0,0,0,0.06);
  border-bottom: 1px solid rgba(0,0,0,0.06);
  transform: rotate(45deg);
  border-bottom-left-radius: 3px;
}

.msg.admin .msg-content::after{
  content:"";
  position:absolute;
  right:-6px; bottom:10px;
  width:12px; height:12px;
  background: linear-gradient(135deg, rgba(67,97,238,1) 0%, rgba(114,9,183,1) 100%);
  transform: rotate(45deg);
  border-bottom-right-radius: 3px;
  opacity: .95;
}

.msg-time { font-size: 11px; margin-top: 6px; text-align: right; opacity: .9; }
.msg.user  .msg-time { color: rgba(0,0,0,0.45); }
.msg.admin .msg-time { color: rgba(255,255,255,0.85); }

/* actions */
.msg-actions { display: flex; gap: 6px; margin-top: 8px; opacity: 0; transition: var(--transition); }
.msg-content:hover .msg-actions { opacity: 1; }

.msg-actions button{
  border: none; cursor: pointer; border-radius: 8px;
  padding: 6px 10px; font-size: 12px; transition: var(--transition);
  background: rgba(255,255,255,0.90);
}
.msg.admin .msg-actions button{ background: rgba(255,255,255,0.18); color: white; }
.msg-actions button:hover{ transform: translateY(-2px); }

.send-form {
    padding: 18px 20px;
    border-top: 1px solid var(--border);
    display: none; align-items: center; gap: 14px;
    position: sticky; bottom: 0; z-index: 10;
    backdrop-filter: blur(10px);
    background: rgba(255,255,255,0.85);
}
.send-form.active { display: flex; }

#message {
    flex: 1; padding: 14px 18px; border-radius: 999px;
    border: 1px solid rgba(0,0,0,0.07);
    background: rgba(248,249,250,0.9);
    font-size: 15px; transition: var(--transition);
}
#message:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    background: rgba(255,255,255,0.95);
}

.send-btn {
    background: var(--primary); color: white; border: none;
    padding: 14px 26px; border-radius: 999px; cursor: pointer;
    font-weight: 700; transition: var(--transition);
    display: flex; align-items: center; gap: 8px;
    box-shadow: 0 12px 22px rgba(67,97,238,0.22);
}
.send-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
.send-btn:active { transform: translateY(0) scale(0.98); }

/* animations */
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(247, 37, 133, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(247, 37, 133, 0); }
    100% { box-shadow: 0 0 0 0 rgba(247, 37, 133, 0); }
}

@media (max-width: 992px) {
    .chat-container { flex-direction: column; height: 95vh; }
    .chat-list, .chat-box { width: 100%; }
    .chat-list { height: 40%; }
    .chat-box { height: 60%; }
}
@media (max-width: 576px) {
    .chat-container { height: 100vh; border-radius: 0; }
    .msg-content { max-width: 85%; }
}
/* ===== Sidebar Admin ===== */
.burger{
  position: fixed;
  top: 18px; left: 18px;
  z-index: 2500;
  width: 44px; height: 44px;
  border: 0;
  border-radius: 12px;
  cursor: pointer;
  font-size: 22px;
  color: #fff;
  background: rgba(255,255,255,0.18);
  border: 1px solid rgba(255,255,255,0.25);
  backdrop-filter: blur(10px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
  transition: .25s ease;
}
.burger:hover{ transform: translateY(-1px); background: rgba(255,255,255,0.25); }

.sidebar{
  position: fixed;
  top: 0; left: -260px;
  width: 260px;
  height: 100vh;
  z-index: 2400;
  padding: 18px;
  color: #fff;
  background: rgba(20, 24, 40, 0.92);
  backdrop-filter: blur(14px);
  border-right: 1px solid rgba(255,255,255,0.12);
  box-shadow: 10px 0 35px rgba(0,0,0,0.25);
  transition: left .28s ease;
}
.sidebar.open{ left: 0; }

.sideOverlay{
  position: fixed;
  inset: 0;
  z-index: 2300;
  background: rgba(0,0,0,0.45);
  opacity: 0;
  pointer-events: none;
  transition: .25s ease;
}
.sideOverlay.active{
  opacity: 1;
  pointer-events: auto;
}

.side-head{
  padding: 10px 8px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.12);
  margin-bottom: 12px;
}
.brand{
  font-weight: 800;
  letter-spacing: .2px;
  font-size: 18px;
}
.role{
  margin-top: 4px;
  font-size: 12px;
  opacity: .8;
}

.side-nav{
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-top: 10px;
}
.side-nav a{
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 12px;
  border-radius: 12px;
  text-decoration: none;
  color: rgba(255,255,255,0.92);
  transition: .2s ease;
}
.side-nav a:hover{
  background: rgba(255,255,255,0.10);
  transform: translateX(3px);
}
.side-nav a.active{
  background: rgba(67,97,238,0.25);
  border: 1px solid rgba(67,97,238,0.35);
}

body.sidebar-open{
  padding-left: 260px;
  transition: padding-left .28s ease;
}

/* mobile: pas de push */
@media (max-width: 992px){
  body.sidebar-open{ padding-left: 0; }
}

</style>
</head>
<body>
  <!-- ===== Burger + Sidebar (Admin) ===== -->
<button id="burger" class="burger" aria-label="Toggle sidebar">☰</button>

<aside id="sidebar" class="sidebar">


  <nav class="side-nav">
   <h2>  </h2>
      <h2>  </h2>
         <h2>  </h2>
            <h2>  </h2>
<a href="admindashboard.php">Dashboard</a>
<a href="statistics.php">statistics</a>

<a href="list_students.php">List Students</a>
<a href="list_admins.php">List Admins</a>
<a href="classes.php">Classes</a>
<a href="filieres.php">programms</a>
<a href="../first.php">Logout</a>
  </nav>
</aside>

<div id="sideOverlay" class="sideOverlay"></div>


<div class="chat-container">

  <div class="chat-list">
    <div class="list-header">
      <h2><i class="fas fa-comments" style="margin-right: 10px; color: var(--primary);"></i> Conversations</h2>
      <div class="search-container">
        <input type="text" id="searchConversations" placeholder="Rechercher un utilisateur...">
        <i class="fas fa-search"></i>
      </div>
    </div>

    <div class="conversations" id="conversationsList">
      <?php while($c = $conversations->fetch_assoc()): ?>
        <a href="#" class="chat-user" data-id="<?= $c['id'] ?>">
          <div>
            <span class="username"><?= htmlspecialchars($c['username']) ?></span>
            <span class="msg-count"><?= $c['msg_count'] ?> messages</span>
          </div>
          <div style="display:flex; align-items:center; gap:8px;">
            <?php if($c['unread'] > 0): ?>
              <span class="unread-badge"><?= $c['unread'] ?> non lu</span>
            <?php endif; ?>
            <button class="delete-conv" data-id="<?= $c['id'] ?>" title="Supprimer">
              <i class="fas fa-trash-alt"></i>
            </button>
          </div>
        </a>
      <?php endwhile; ?>
    </div>
  </div>

  <div class="chat-box">
    <div class="chat-header">
      <h3 id="currentChatTitle">Sélectionnez une conversation</h3>
      <div class="chat-actions" id="chatActions" style="display: none;">
        <button class="mark-read" id="markReadBtn">
          <i class="fas fa-check-double"></i> Marquer comme lu
        </button>
      </div>
    </div>

    <div class="messages-container">
      <div class="messages" id="chatBox">
        <div class="welcome-screen" style="text-align:center; padding: 60px 20px; opacity:.9;">
          <i class="fas fa-comments" style="font-size: 60px; color: var(--primary); margin-bottom: 20px; opacity: 0.5;"></i>
          <h3 style="color: var(--gray); margin-bottom: 10px;">Bienvenue sur le chat admin</h3>
          <p style="color: var(--gray); max-width: 420px; margin: 0 auto;">
            Sélectionnez une conversation pour commencer à discuter.
          </p>
        </div>
      </div>
    </div>

    <form class="send-form" id="sendForm">
      <input type="hidden" id="conv_id">
      <input type="text" id="message" placeholder="Tapez votre message ici..." autocomplete="off">
      <button type="submit" class="send-btn">
        <i class="fas fa-paper-plane"></i> Envoyer
      </button>
    </form>
  </div>

</div>

<!-- MODAL SUPPRESSION CONVERSATION -->
<div class="modal-overlay" id="deleteConvModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center; z-index:1000;">
  <div class="modal" style="background:white; border-radius: 12px; width:90%; max-width: 520px; overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
    <div class="modal-header" style="padding:18px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
      <h3>Confirmer la suppression</h3>
      <button class="close-modal" style="border:none; background:transparent; font-size:26px; cursor:pointer;">&times;</button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <p>Supprimer cette conversation ? Cette action supprimera aussi les messages associés.</p>
    </div>
    <div class="modal-footer" style="padding:18px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px;">
      <button class="close-modal" style="padding:10px 18px; border-radius:10px; border:0; background:#6c757d; color:white; cursor:pointer;">Annuler</button>
      <button id="confirmDeleteConv" style="padding:10px 18px; border-radius:10px; border:0; background:#f72585; color:white; cursor:pointer;">Supprimer</button>
    </div>
  </div>
</div>

<!-- MODAL SUPPRESSION MESSAGE -->
<div class="modal-overlay" id="deleteMsgModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center; z-index:1000;">
  <div class="modal" style="background:white; border-radius: 12px; width:90%; max-width: 520px; overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
    <div class="modal-header" style="padding:18px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
      <h3>Supprimer le message</h3>
      <button class="close-modal" style="border:none; background:transparent; font-size:26px; cursor:pointer;">&times;</button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <p>Confirmer la suppression de ce message ?</p>
    </div>
    <div class="modal-footer" style="padding:18px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px;">
      <button class="close-modal" style="padding:10px 18px; border-radius:10px; border:0; background:#6c757d; color:white; cursor:pointer;">Annuler</button>
      <button id="confirmDeleteMsg" style="padding:10px 18px; border-radius:10px; border:0; background:#f72585; color:white; cursor:pointer;">Supprimer</button>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="editMsgModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center; z-index:1000;">
  <div class="modal" style="background:white; border-radius: 12px; width:90%; max-width: 520px; overflow:hidden; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
    <div class="modal-header" style="padding:18px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
      <h3>Modifier le message</h3>
      <button class="close-modal" style="border:none; background:transparent; font-size:26px; cursor:pointer;">&times;</button>
    </div>
    <div class="modal-body" style="padding:20px;">
      <textarea id="editMessageText" rows="4" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:10px;"></textarea>
    </div>
    <div class="modal-footer" style="padding:18px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px;">
      <button class="close-modal" style="padding:10px 18px; border-radius:10px; border:0; background:#6c757d; color:white; cursor:pointer;">Annuler</button>
      <button id="confirmEditMsg" style="padding:10px 18px; border-radius:10px; border:0; background:#4361ee; color:white; cursor:pointer;">Enregistrer</button>
    </div>
  </div>
</div>

<script>
let currentConversationId = null;
let currentConversationUser = null;
let messageToDelete = null;
let messageToEdit = null;

function showModal(modalId){ document.getElementById(modalId).style.display = 'flex'; }
function hideAllModals(){ document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none'); }
function scrollToBottom(){ const chatBox = document.getElementById('chatBox'); chatBox.scrollTop = chatBox.scrollHeight; }

document.querySelectorAll('.chat-user').forEach(user => {
  user.addEventListener('click', function(e){
    if(e.target.closest('.delete-conv')) return;
    e.preventDefault();
    document.querySelectorAll('.chat-user').forEach(u => u.classList.remove('active'));
    this.classList.add('active');
    const id = this.dataset.id;
    const username = this.querySelector('.username').textContent;
    loadConversation(id, username);
  });
});
// ===== Sidebar Toggle =====
const burgerBtn = document.getElementById('burger');
const sideBar   = document.getElementById('sidebar');
const sideOv    = document.getElementById('sideOverlay');

function openSidebar(){
  sideBar.classList.add('open');
  sideOv.classList.add('active');
  document.body.classList.add('sidebar-open');
}
function closeSidebar(){
  sideBar.classList.remove('open');
  sideOv.classList.remove('active');
  document.body.classList.remove('sidebar-open');
}

burgerBtn.addEventListener('click', () => {
  if(sideBar.classList.contains('open')) closeSidebar();
  else openSidebar();
});

sideOv.addEventListener('click', closeSidebar);

document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closeSidebar();
});

function loadConversation(id, username){
  currentConversationId = id;
  currentConversationUser = username;

  document.getElementById('currentChatTitle').textContent = `Conversation avec ${username}`;
  document.getElementById('chatActions').style.display = 'flex';
  document.getElementById('sendForm').classList.add('active');
  document.getElementById('conv_id').value = id;

  markAsRead(id);

  fetch(`../user/load_messages.php?conv=${id}`)
    .then(r => r.text())
    .then(html => {
      document.getElementById('chatBox').innerHTML = html;
      attachMessageActions();
      scrollToBottom();
    })
    .catch(() => {
      document.getElementById('chatBox').innerHTML = '<p style="padding:20px;color:#c00">Erreur chargement messages</p>';
    });
}

document.getElementById('searchConversations').addEventListener('input', function(){
  const search = this.value.trim();
  if(search.length < 2 && search.length !== 0) return;

  fetch('admin_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=search&search=${encodeURIComponent(search)}`
  })
  .then(r => r.json())
  .then(data => {
    if(data.status === 'success'){
      document.getElementById('conversationsList').innerHTML = data.html;

      document.querySelectorAll('.chat-user').forEach(user => {
        user.addEventListener('click', function(e){
          if(e.target.closest('.delete-conv')) return;
          e.preventDefault();
          document.querySelectorAll('.chat-user').forEach(u => u.classList.remove('active'));
          this.classList.add('active');
          loadConversation(this.dataset.id, this.querySelector('.username').textContent);
        });
      });

      document.querySelectorAll('.delete-conv').forEach(btn => {
        btn.addEventListener('click', function(e){
          e.preventDefault(); e.stopPropagation();
          showDeleteConversationModal(this.dataset.id);
        });
      });
    }
  });
});

document.getElementById('sendForm').addEventListener('submit', function(e){
  e.preventDefault();
  const message = document.getElementById('message').value.trim();
  const convId = document.getElementById('conv_id').value;
  if(!message || !convId) return;

  fetch('admin_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=send&conversation_id=${convId}&message=${encodeURIComponent(message)}`
  })
  .then(r => r.json())
  .then(data => {
    if(data.status === 'success'){
      document.getElementById('message').value = '';
      loadConversation(convId, currentConversationUser);
    } else {
      alert('Erreur: ' + (data.message || 'Échec de l\'envoi'));
    }
  });
});

function markAsRead(convId){
  fetch('admin_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=mark_read&conversation_id=${convId}`
  })
  .then(r => r.json())
  .then(data => {
    if(data.status === 'success'){
      const badge = document.querySelector(`.chat-user[data-id="${convId}"] .unread-badge`);
      if(badge) badge.remove();
    }
  });
}

document.getElementById('markReadBtn').addEventListener('click', function(){
  if(currentConversationId) markAsRead(currentConversationId);
});

function showDeleteConversationModal(convId){
  showModal('deleteConvModal');
  document.getElementById('confirmDeleteConv').onclick = function(){
    fetch('admin_chat.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: `action=delete_conversation&conversation_id=${convId}`
    })
    .then(r => r.json())
    .then(data => {
      if(data.status === 'success'){
        const userEl = document.querySelector(`.chat-user[data-id="${convId}"]`);
        if(userEl) userEl.remove();

        if(currentConversationId == convId){
          document.getElementById('currentChatTitle').textContent = 'Sélectionnez une conversation';
          document.getElementById('chatActions').style.display = 'none';
          document.getElementById('sendForm').classList.remove('active');
          document.getElementById('chatBox').innerHTML = `<div style="text-align:center; padding:60px 20px; color:#6c757d;">
            <i class="fas fa-comments" style="font-size:60px; color:#4361ee; opacity:.35;"></i>
            <h3 style="margin-top:12px;">Conversation supprimée</h3>
            <p style="max-width:420px; margin:10px auto 0;">Sélectionnez une autre conversation.</p>
          </div>`;
          currentConversationId = null;
        }
        hideAllModals();
      }
    });
  };
}

document.querySelectorAll('.delete-conv').forEach(btn => {
  btn.addEventListener('click', function(e){
    e.preventDefault(); e.stopPropagation();
    showDeleteConversationModal(this.dataset.id);
  });
});

function attachMessageActions(){
  document.querySelectorAll('.delete-msg-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      messageToDelete = this.dataset.id;
      showModal('deleteMsgModal');
    });
  });

  document.querySelectorAll('.edit-msg-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      messageToEdit = this.dataset.id;
      const bubble = document.querySelector(`[data-msg-id="${messageToEdit}"] .msg-content`);
      const timeEl = bubble.querySelector('.msg-time');
      const textOnly = bubble.cloneNode(true);
      textOnly.querySelector('.msg-time')?.remove();
      textOnly.querySelector('.msg-actions')?.remove();
      document.getElementById('editMessageText').value = textOnly.textContent.trim();
      showModal('editMsgModal');
    });
  });
}

document.getElementById('confirmDeleteMsg').addEventListener('click', function(){
  if(!messageToDelete) return;

  fetch('admin_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=delete_message&message_id=${messageToDelete}`
  })
  .then(r => r.json())
  .then(data => {
    if(data.status === 'success'){
      const msgEl = document.querySelector(`[data-msg-id="${messageToDelete}"]`);
      if(msgEl){
        msgEl.style.opacity = '0';
        msgEl.style.transform = 'translateY(-10px)';
        setTimeout(() => msgEl.remove(), 250);
      }
      hideAllModals();
      messageToDelete = null;
    }
  });
});

document.getElementById('confirmEditMsg').addEventListener('click', function(){
  if(!messageToEdit) return;
  const newContent = document.getElementById('editMessageText').value.trim();
  if(!newContent) return alert('Le message ne peut pas être vide');

  fetch('admin_chat.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=update_message&message_id=${messageToEdit}&message=${encodeURIComponent(newContent)}`
  })
  .then(r => r.json())
  .then(data => {
    if(data.status === 'success'){
      // reload conversation => simple & safe
      loadConversation(currentConversationId, currentConversationUser);
      hideAllModals();
      messageToEdit = null;
    }
  });
});

document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', hideAllModals));
document.querySelectorAll('.modal-overlay').forEach(modal => {
  modal.addEventListener('click', function(e){ if(e.target === this) hideAllModals(); });
});
</script>

</body>
</html>
