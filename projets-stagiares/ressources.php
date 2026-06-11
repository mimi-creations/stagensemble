<?php
session_start(); 
 
if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}
 
require_once 'db.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre    = $_POST['titre'];
    $sujet    = $_POST['sujet'];
    $probleme = $_POST['probleme'];
    $solution = $_POST['solution'];
    $auteur   = $_SESSION['utilisateur'];
    $stmt     = $pdo->prepare("INSERT INTO ressources (titre, sujet, probleme_rencontre, solution_utilisee, auteur, date_publication) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$titre, $sujet, $probleme, $solution, $auteur]);
    header('Location: ressources.php');
    exit();
}
 
$query      = $pdo->query("SELECT * FROM ressources ORDER BY id DESC");
$ressources = $query->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ressources</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="annuaire.php">Annuaire</a> |
        <a href="chat.php">Chat Stagiaires</a> |
        <a href="ressources.php" class="active">Ressources &amp; Problèmes</a> |
        <a href="deconnexion.php">Déconnexion</a>
    </nav>
    <div class="container">
        <h2>Base de connaissances et Ressources</h2>
 
        <!-- Formulaire d'ajout -->
        <div class="form-ressource">
            <h3>Partager un travail ou un problème résolu</h3>
            <form action="ressources.php" method="POST">
                <input type="text" name="titre" placeholder="Titre de votre projet / sujet" required><br>
                <textarea name="sujet" placeholder="Description du travail réalisé..." required></textarea><br>
                <textarea name="probleme" placeholder="Décrivez le problème rencontré" required></textarea><br>
                <textarea name="solution" placeholder="Décrivez la solution utilisée" required></textarea><br>
                <button type="submit">Partager</button>
            </form>
        </div>
        <hr>
 
        <h3>Contributions des stagiaires</h3>
        <?php foreach ($ressources as $res): ?>
        <div class="card">
            <h4><?= htmlspecialchars($res['titre']) ?></h4>
            <p><small>Publié par <strong><?= htmlspecialchars($res['auteur']) ?></strong> le <?= $res['date_publication'] ?></small></p>
            <p><strong>Travail fait :</strong> <?= nl2br(htmlspecialchars($res['sujet'])) ?></p>
            <?php if (!empty($res['probleme_rencontre'])): ?>
            <div class="alert-error">
                <strong>Problème :</strong> <?= nl2br(htmlspecialchars($res['probleme_rencontre'])) ?>
            </div>
            <div class="alert-success">
                <strong>Solution :</strong> <?= nl2br(htmlspecialchars($res['solution_utilisee'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>