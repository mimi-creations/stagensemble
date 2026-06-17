<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])){
    header("Location: login.php");
    exit;
}

$mon_id = $_SESSION['utilisateur_id'];
$destinataire_id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT prenom, nom FROM anciens_stagiaires WHERE id=?");
$stmt->execute([$destinataire_id]);
$destinataire =$stmt->fetch();

if (!$destinataire){
    echo "Stagiaire introuvable. <a href='messagerie.php'>Retour</a>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"]==="POST" && !empty(trim($_POST['message'] ?? ''))){
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO messages_prives (expediteur_id, destinataire_id, message) VALUES (?,?,?)");
    $stmt->execute([$mon_id, $destinataire_id, $message]);
    header("Location: chat_prive.php?id=" . $destinataire_id);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.*, s.prenom AS nom_expediteur
    FROM messages_prives m
    JOIN anciens_stagiaires s ON m.expediteur_id = s.id
    WHERE (m.expediteur_id = ? AND m.destinataire_id= ?)
        OR (m.expediteur_id = ? AND m.destinataire_id = ?)
    ORDER BY m.date_envoi ASC
");
$stmt->execute([$mon_id, $destinataire_id, $destinataire_id, $mon_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Discussion avec <?= htmlspecialchars($destinataire['prenom']) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9;}
            .container  { max-width: 600px; margin: 30px auto; background: white; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: 500px}
            .chat-header { background: #6c63ff; color: white; padding: 15px; border-top-left-raidus: 8px; font-weight: bold; display: flex; justify-content: space-between; }
            .chat-header a { color: white; text-decoration: none; font-size: 14px; }
            .chat-box { flex: 1; padding: 20px; overflow-y: auto; background: #fafafa; }
            .bubble { padding: 10px 14px; border-radius: 15px; margin-bottom: 10px; max-width: 70%; word-wrap: break-word; font-size: 14px; }
            .bubble.me { background: #6c63ff; color: white; margin-left: auto; border-bottom-right-radius: 0; }
            .bubble.other { background: #e9ecef; color: #333; margin-right: auto; border-bottom-left-radius: 0; }
            .chat-form { padding: 15px; display: flex; border-top: 1px solid #eee; }
            .chat-form input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
            .chat-form input[type="submit"] { background: #28a745; color: white; border: none; padding: 0 20px; margin-left: 10px; border-radius: 5px; cursor: pointer; }
        </style>
    </head>
    <body>
        <nav>
        <a href="accueil.php" class="active">Accueil</a>
        <a href="annuaire.php">Annuaire</a>
        <a href="chat.php"> Chat Stagaire</a>
        <a href="chat_prive.php" class="active"> Chat Privé</a>
        <a href="ressources.php">Ressources &amp; Problèmes</a>
        <a href="deconnexion.php" style="color:red">Déconnexion</a>
    </nav>
        <div class="container">
            <div class="chat-header">
                <span>Discussion avec <?= htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']) ?></span>
                <a href="messagerie.php">Liste</a>
            </div>
            <div class="chat-box">
                <?php foreach ($messages as $msg): ?>
                    <div class="bubble <?= $msg['expediteur_id'] == $mon_id ? 'me' : 'other' ?>">
                        <strong><?= $msg['expediteur_id'] == $mon_id ? 'Moi' : htmlspecialchars($msg['nom_expediteur']) ?> :</strong>
                        <p style="margin: 5px 0 0 0;"><?= htmlspecialchars($msg['message']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <form class="chat-form" method="POST" action="">
                <input type="text" name="message" placeholder="Ecrivez votre message privé..." required autocomplete="off">
                <input type="submit" value="Envoyer">
            </form>
        </div>
        <script>
            var chatBox = document.querySelector('.chat-box');
            chatBox.scrollTop= chatBox.scrollHeight;
        </script>
    </body>
</html>
