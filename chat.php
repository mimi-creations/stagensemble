<?php
session_start();
 
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}
 
require_once 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $pseudo  = $_SESSION['utilisateur'];
    $message = $_POST['message'];
    $stmt    = $pdo->prepare("INSERT INTO chat (pseudo, message) VALUES (?, ?)");
    $stmt->execute([$pseudo, $message]);
    header('Location: chat.php');
    exit();
}
 
$query    = $pdo->query("SELECT * FROM chat ORDER BY date_envoi DESC LIMIT 30");
$messages = array_reverse($query->fetchAll());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Stagiaires</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Chat direct entre stagiaires</h2>
        <div class="chat-box">
            <?php foreach ($messages as $msg): ?>
            <div class="message">
                <small>[<?= date('H:i', strtotime($msg['date_envoi'])) ?>]</small>
                <strong><?= htmlspecialchars($msg['pseudo']) ?> :</strong>
                <span><?= htmlspecialchars($msg['message']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <form action="chat.php" method="POST" class="chat-form">
            <input type="text" name="message" placeholder="Votre message..." required autocomplete="off">
            <button type="submit">Envoyer</button>
        </form>
    </div>
<?php include 'footer.php';?>
</body>
</html>
