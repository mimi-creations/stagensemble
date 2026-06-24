<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$mon_id = $_SESSION['utilisateur_id'];
$destinataire_id = intval($_GET['id'] ?? 0);

// ✅ ENVOYER MESSAGE PRIVÉ

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['message']) && $destinataire_id > 0) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("
            INSERT INTO messages_prives (expediteur_id, destinataire_id, message, lu)
            VALUES (?, ?, ?, 0)
        ");
        $stmt->execute([$mon_id, $destinataire_id, $message]);
    }

    header("Location: messagerie.php?id=" . $destinataire_id);
    exit;
}

// ✅ CRÉER GROUPE

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['creer_groupe'])) {

    $nom = trim($_POST['nom_groupe']);

    if (!empty($nom)) {

        // créer groupe
        $stmt = $pdo->prepare("INSERT INTO groupes (nom, createur_id) VALUES (?, ?)");
        $stmt->execute([$nom, $mon_id]);

        $groupe_id = $pdo->lastInsertId();

        // ajouter créateur
        $stmt_add = $pdo->prepare("INSERT INTO groupe_membres (groupe_id, utilisateur_id) VALUES (?, ?)");
        $stmt_add->execute([$groupe_id, $mon_id]);

        // ajouter membres
        if (!empty($_POST['membres'])) {
            foreach ($_POST['membres'] as $id) {
                if ($id != $mon_id) {
                    $stmt_add->execute([$groupe_id, $id]);
                }
            }
        }
    }

    header("Location: messagerie.php");
    exit;
}

// ✅ MARQUER LU

if ($destinataire_id > 0) {
    $stmt_update = $pdo->prepare("
        UPDATE messages_prives 
        SET lu = 1 
        WHERE expediteur_id = ? AND destinataire_id = ? AND lu = 0
    ");
    $stmt_update->execute([$destinataire_id, $mon_id]);
}

// ✅ CONVERSATIONS

$stmt = $pdo->prepare("
SELECT s.id, s.nom, s.prenom, s.avatar,
(
    SELECT message FROM messages_prives
    WHERE (expediteur_id = s.id AND destinataire_id = ?)
    OR (expediteur_id = ? AND destinataire_id = s.id)
    ORDER BY date_envoi DESC LIMIT 1
) as dernier_message
FROM anciens_stagiaires s
WHERE s.id != ?
");

$stmt->execute([$mon_id, $mon_id, $mon_id]);
$conversations = $stmt->fetchAll();

// ✅ GROUPES

$stmt = $pdo->prepare("
SELECT g.* FROM groupes g
JOIN groupe_membres gm ON g.id = gm.groupe_id
WHERE gm.utilisateur_id = ?
");

$stmt->execute([$mon_id]);
$groupes = $stmt->fetchAll();

// ✅ MESSAGES PRIVÉS

$messages = [];
$destinataire = null;

if ($destinataire_id > 0) {

    $stmt = $pdo->prepare("SELECT prenom, nom FROM anciens_stagiaires WHERE id=?");
    $stmt->execute([$destinataire_id]);
    $destinataire = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT m.*, s.prenom 
        FROM messages_prives m
        JOIN anciens_stagiaires s ON m.expediteur_id = s.id
        WHERE (m.expediteur_id = ? AND m.destinataire_id = ?)
        OR (m.expediteur_id = ? AND m.destinataire_id = ?)
        ORDER BY m.date_envoi ASC
    ");

    $stmt->execute([$mon_id, $destinataire_id, $destinataire_id, $mon_id]);
    $messages = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Messagerie</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<h2>Messagerie</h2>

<!-- ✅ BUTTON GROUP -->
<button onclick="document.getElementById('popup').style.display='block'">
    ➕ Nouveau groupe
</button>

<!-- ✅ POPUP -->
<div id="popup" style="display:none; background:white; padding:20px; border:1px solid #ccc;">
    <button onclick="document.getElementById('popup').style.display='none'">❌</button>

    <form method="POST">
        <input type="text" name="nom_groupe" placeholder="Nom du groupe" required>

        <h4>Membres</h4>

        <?php
        $stmt = $pdo->query("SELECT id, prenom FROM anciens_stagiaires");
        while ($u = $stmt->fetch()):
        ?>
            <label>
                <input type="checkbox" name="membres[]" value="<?= $u['id'] ?>">
                <?= htmlspecialchars($u['prenom']) ?>
            </label><br>
        <?php endwhile; ?>

        <button name="creer_groupe">Créer</button>
    </form>
</div>

<!-- ✅ SIDEBAR -->
<h3>Contacts</h3>

<?php foreach ($conversations as $c): ?>
    <a href="messagerie.php?id=<?= $c['id'] ?>">
        <?= htmlspecialchars($c['prenom']) ?>
    </a><br>
<?php endforeach; ?>

<!-- ✅ GROUPES -->
<h3>Groupes</h3>

<?php foreach ($groupes as $g): ?>
    <a href="groupe.php?id=<?= $g['id'] ?>">
        <?= htmlspecialchars($g['nom']) ?>
    </a><br>
<?php endforeach; ?>

<!-- ✅ CHAT -->
<?php if ($destinataire): ?>

<h3>Discussion avec <?= htmlspecialchars($destinataire['prenom']) ?></h3>

<?php foreach ($messages as $msg): ?>
    <p>
        <strong><?= $msg['expediteur_id'] == $mon_id ? 'Moi' : $msg['prenom'] ?> :</strong>
        <?= htmlspecialchars($msg['message']) ?>
    </p>
<?php endforeach; ?>

<form method="POST">
    <input type="text" name="message" required>
    <button>Envoyer</button>
</form>

<?php endif; ?>

</body>
</html>
