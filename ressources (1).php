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

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajouter_commentaire'])) {
    $id_resspurce = $_POST['id_ressource'];
    $contenu_commentaire = trim($_POST['contenu_commentaire']);
    $user_id = $_SESSION['utilisateur_id'] ?? $_SESSION['utilisateur']['id'] ?? 1;
    if (!empty($contenu_commentaire)) {
        $stmt = $pdo->prepare("INSERT INTO commentaires (id_ressource; id_auteur, contenu, date_publication) VALUES (?,?,?, NOW())");
        $stmt->execute([$id_ressource, $user_id, $contenu_commentaire]);
    }
    header("Location: ressources.php");
    exit;
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
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h2>Base de connaissances et Ressources</h2>
 
        <div class="form-ressource">
            <h3>Partager un travail ou un problème résolu</h3>
            <form action="ressources.php" method="POST" enctype="multipart/form-data">
                <input type="text" name="titre" placeholder="Titre de votre projet / sujet" required><br>
                <textarea name="sujet" placeholder="Description du travail réalisé..." required></textarea><br>
                <textarea name="probleme" placeholder="Décrivez le problème rencontré" required></textarea><br>
                <textarea name="solution" placeholder="Décrivez la solution utilisée" required></textarea><br>
                
                <div style="margin-bottom: 15px; text-align: left;">
                    <label for="image_ressource" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.9rem; color: var(--color-text);">
                        📸 Ajouter une capture d'écran ou une image (optionnel) :
                    </label>
                    <input type="file" name="image_ressource" id="image_ressource" accept="image/*" style="padding: 5px 0;">
                </div>
                
                <button type="submit">Partager</button>
            </form>
        </div>
        <hr>
 
        <h3>Contributions des stagiaires</h3>
        <?php foreach ($ressources as $res): ?>
        <div class="ressource-card" style="background: white; padding: 20px; border-radius: var(--radius-md); margin-bottom: 20px; box-shadow: var(--shadow-sm);">
            <h3><?= htmlspecialchars($res['titre']) ?></h3>
            <p><?= nl2br(htmlspecialchars($res['contenu'] ?? $r['texte'] ?? '')) ?></p
             <?php if (!empty($res['image'])): ?>
                <div style="margin-top: 15px; max-width: 100%;">
                    <a href="<?= htmlspecialchars($res['image']) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($res['image']) ?>" alt="Image jointe"
                            style="max-width: 100%; max-height: 350px; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: contain;">
                    </a>
                    <p style="font-size: 0.75rem; color: var(--color-muted); margin-top: 5px;">💡 Cliquez sur l'image pour l'ouvrir en grand</p>
                </div>
            <?php endif; ?>
        </div>
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
        <div style="margin-top: 25px; border-top: 1px solid #e5e7eb; padding-top: 15px; background: #f9fafb; margin: 20px -20px -20px -20px; padding: 20px; border-bottom-left-radius: var(--radius-md); border-bottom-right-radius: var(--radius-md);">
            <h4 style="margin-bottom: 10px; font-size: 0.95rem; color: var(--color-text); display: flex; align-items: center; gap: 5px;">
                Commentaires
            </h4>
            <?php
            $stmt_com = $pdo->prepare("SELECT c.*, s.prenom, s.nom FROM commentaires c JOIN anciens_stagiaires s ON c.id_auteur = s.id WHERE c.id_ressource = ? ORDER BY c.date_publication ASC");
            $stmt_com->execute([$res['id']]);
            $commentaires= $stmt_com->fetchAll();
            ?>
            <div class="margin-bottom: 15px; max-height: 250px; overflow-y: auto;">
                <?php if (empty($commentaires)): ?>
                    <p style="font-size: 0.85rem; color: var(--color-muted); font-style: italic;">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>
                <?php else: ?>
                    <?php foreach ($commentaires as $com): ?>
                        <div style= "background: white; padding: 10px 12px; border-radius: 6px; margin-bottom: 8px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <p style="margin: 0; font-size: 0.8rem; font-weight: 600; color: var(--color-accent2);">
                                <?= htmlspecialchars($com['prenom'] .''. $com['nom']) ?>
                                <span style="font-weight: 400; color: var(--color-muted); font-size: 0.75rem;">-Le <?= date('d/m à H:i', strtotime($com['date_publication'])) ?></span>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form action="ressources.php" method="POST" style="display: flex; gap: 10px; margin-top: 10px;">
                <input type="hidden" name="id_ressource" value="<?= $r['id'] ?>"> 
                <input type="text" name="contents_commentaire" placeholder="Écrire un commentaire d'aide ou d'encouragement..." required 
                    style="flex: 1; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; outline: none;">
                <button type="submit" name="ajouter_commentaire" 
                        style="padding: 8px 15px; background: var(--color-accent2, #4f46e5); color: white; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; font-weight: 600;">
                    Envoyer
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>