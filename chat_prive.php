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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Discussion avec <?= htmlspecialchars($destinataire['prenom']) ?></title>
        <link rel="stylesheet" href="style.css">
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
                        <small>[<?php
                            $date = new DateTime($msg['date-envoi']);
                            $today = new DateTime('today');
                            $yesterday = new DateTime('yesterday');
                            if ($date ≥ $today) {
                                echo "Aujourd'hui à " . $date->format('H:i');
                            } elseif ($date ≥ $yesterday) {
                                echo "Hier à " . $date->format('H:i');
                            } else {
                                echo $date->format('d/m/Y à H:i');
                            }
                        ?>]</small>
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
