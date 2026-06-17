<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$recherche= trim($_GET['recherche'] ?? '');

if (!empty($recherche)) {
    $stmt=$pdo->prepare("SELECT * FROM anciens_stagiaires WHERE nom LIKE ? OR prenom LIKE ? OR ecole LIKE ? OR secteur LIKE ? ORDER BY nom ASC");
    $terme="%". $recherche . "%";
    $stmt->execute([$terme, $terme, $terme, $terme]);
    $stagiaires=$stmt->fetchAll();
} else {
    $stmt=$pdo->prepare("SELECT * FROM anciens_stagiaires ORDER BY nom ASC");
    $stmt->execute();
    $stagiaires=$stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Annuaire des anciens</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Annuaire des anciens stagiaires</h2>
        <p>Retrouvez les contacts de ceux qui sont passés par là avant vous !</p>
        <div class="filtre-container" style="margin-bottom: 20px; background: #f3f4f6; padding: 15px; border-radius: 8px; display: flex; align-items:center; gap: 15px;">
            <form action="annuaire.php" method="GET" style="display: flex; gap: 10px; margin-bottom: 25px; max-width: 500px;">
                <input type="text" name="recherche" value="<?= htmlspecialchars($recherche) ?>"
                    placeholder="Rechercher une école ou un secteur (ex: EMSI, IT...)"
                    style="flex: 1; padding: 10px 15px; border:1px solid #ccc; border-raidus: 8px; font-size: 0.95rem;">
                <input type="submit" value="Rechercher" class="btn-primary" style="padding: 10px 20px; cursor: pointer;">
                <?php if (!empty($recherche)): ?>
                    <a href="annuaire.php" style="display: inline-flex; align-items: center; text-decoration: none; color: #666; font-size: 0.9rem; padding:0 10px;"> Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nom &amp; Prénom</th>
                    <th>Email</th>
                    <th>École</th>
                    <th>Année de stage</th>
                    <th>Durée du stage</th>
                    <th>Secteur dans l'entreprise</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stagiaires as $stagiaire): ?>
                <tr>
                    <td>
                        <a href="profil.php?id=<?= $stagiaire['id'] ?>" style="font-weight: bold; text-decoration: none; color: var(--color-accent);">
                            <?= htmlspecialchars($stagiaire['prenom'] . '' . $stagiaire['nom']) ?>
                        </a>
                    </td>
                    <td><a href="mailto:<?= htmlspecialchars($stagiaire['email']) ?>"><?= htmlspecialchars($stagiaire['email'])?></a></td>
                    <td><?= htmlspecialchars($stagiaire['ecole'] ?? 'Non renseignée') ?></td>
                    <td><?= htmlspecialchars($stagiaire['annee_stage']) ?></td>
                    <td><?= htmlspecialchars($stagiaire['duree_stage']) ?> </td>
                    <td><?= htmlspecialchars($stagiaire['secteur'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
