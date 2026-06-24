<?php
session_start();
require_once 'db.php';

$groupe_id = $_GET['id'];

// envoyer message
if (isset($_POST['message'])) {
    $stmt = $pdo->prepare("
    INSERT INTO messages_groupes (groupe_id, expediteur_id, message)
    VALUES (?, ?, ?)
    ");
    $stmt->execute([$groupe_id, $_SESSION['utilisateur_id'], $_POST['message']]);
}

// récupérer messages
$stmt = $pdo->prepare("
SELECT m.*, a.prenom FROM messages_groupes m
JOIN anciens_stagiaires a ON a.id = m.expediteur_id
WHERE groupe_id = ?
ORDER BY date_envoi ASC
");

$stmt->execute([$groupe_id]);
$messages = $stmt->fetchAll();
?>

<h2>Chat du groupe</h2>

<?php foreach ($messages as $m): ?>
    <p><b><?= $m['prenom'] ?> :</b> <?= $m['message'] ?></p>
<?php endforeach; ?>

<form method="POST">
    <input type="text" name="message" placeholder="Message">
    <button>Envoyer</button>
</form>
