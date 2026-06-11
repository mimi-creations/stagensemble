<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$query   = $pdo->query("SELECT * FROM anciens_stagiaires ORDER BY annee_stage DESC");
$anciens = $query->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire des anciens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="annuaire.php" class="active">Annuaire</a> |
        <a href="chat.php">Chat Stagiaires</a> |
        <a href="ressources.php">Ressources &amp; Problèmes</a> |
        <a href="deconnexion.php" style="color:red;">Déconnexion</a>
    </nav>
    <div class="container">
        <h2>Annuaire des anciens stagiaires</h2>
        <p>Retrouvez les contacts de ceux qui sont passés par là avant vous !</p>
        <table>
            <thead>
                <tr>
                    <th>Nom &amp; Prénom</th>
                    <th>Email</th>
                    <th>École</th>
                    <th>Année de stage</th>
                    <th>Durée du stage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anciens as $ancien): ?>
                <tr>
                    <td><?= htmlspecialchars($ancien['prenom'] . ' ' . $ancien['nom']) ?></td>
                    <td><a href="mailto:<?= htmlspecialchars($ancien['email']) ?>"><?= htmlspecialchars($ancien['email']) ?></a></td>
                    <td><?= htmlspecialchars($ancien['ecole']) ?></td>
                    <td><?= htmlspecialchars($ancien['annee_stage']) ?></td>
                    <td><?= htmlspecialchars($ancien['duree_stage']) ?> mois</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>