<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$id_profil = $_GET['id'];

if (!$id_profil) {
    header("Location: annuaire.php");
    exit;
}


$stmt = $pdo->prepare("SELECT * FROM anciens_stagiaires WHERE id= ?");
$stmt->execute([$id_profil]);
$stagiaire = $stmt->fetch();

if(!$stagiaire) {
    echo "Stagiaire introuvable.";
    exit;
}

    // ✅ récupérer les ressources de ce stagiaire
$stmt = $pdo->prepare("SELECT * FROM ressources WHERE utilisateur_id = ? ORDER BY id DESC");
$stmt->execute([$id_profil]);
$ressources = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Profil de <?= htmlspecialchars($stagiaire['prenom'] . '' . $stagiaire['nom']) ?></title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php include 'navbar.php';?>
        <div class="container" style="max-width: 750px; margin-top: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <img src="<?= htmlspecialchars($stagiaire['avatar'] ?? 'default_avatar.png') ?>" alt="Avatar" 
                    style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #667eea; box-shadow: var(--shadow-card);">
                <h2 style="margin-top: 15px; font-size: 1.8rem; color: var(--color-dark);">
                    <?= htmlspecialchars($stagiaire['prenom'] . ' ' . $stagiaire['nom']) ?>
                </h2>
                <?php if (!empty($stagiaire['secteur'])): ?>
                    <span style="background: #fff3f0; color: var(--color-accent); padding: 4px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; border: 1px solid rgba(200,16,46,0.2); display: inline-block; margin-top: 5px;">
                         Secteur <?= htmlspecialchars($stagiaire['secteur']) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #faf7f2; padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px; border: 1px solid #f0e6da;">
                <div>
                    <p style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 2px;"> École / Université</p>
                    <p style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($stagiaire['ecole'] ?? 'Non renseignée') ?></p>
                </div>
                <div>
                    <p style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 2px;"> Année du stage</p>
                    <p style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($stagiaire['annee_stage'] ?? '—') ?></p>
                </div>
                <div>
                    <p style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 2px;"> Durée du stage</p>
                    <p style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($stagiaire['duree_stage'] ?? '—') ?></p>
                </div>
                <div>
                    <p style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 2px;"> Téléphone</p>
                    <p style="font-weight: 600; color: var(--color-text);"><?= htmlspecialchars($stagiaire['telephone'] ?? 'Non renseigné') ?></p>
                </div>
                <div style="grid-column: span 2;">
                    <p style="font-size: 0.8rem; color: var(--color-muted); margin-bottom: 2px;"> Adresse Email</p>
                    <p style="font-weight: 600;"><a href="mailto:<?= htmlspecialchars($stagiaire['email']) ?>" style="color: var(--color-accent2); text-decoration: none;"><?= htmlspecialchars($stagiaire['email']) ?></a></p>
                </div>
                <?php if (!empty($stagiaire['linkedin'])): ?>
                    <div style="grid-column: span 2; background: #e8f2ff; padding: 10px; border-radius: 8px; border: 1px solid #d0e3ff;">
                        <p style="font-size: 0.8rem; color: #006699; margin-bottom: 2px;">Réseau Professionnel</p>
                        <p style="font-weight: 600;">
                            <a href="<?= strpos($stagiaire['linkedin'], 'http') === 0 ? htmlspecialchars($stagiaire['linkedin']) : 'https://' . htmlspecialchars($stagiaire['linkedin']) ?>" 
                                target="_blank" 
                                style="color: #0077b5; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                Voir le profil LinkedIn
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-bottom: 25px; border-left: 4px solid var(--color-accent); padding-left: 15px;">
                <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: var(--color-dark);"> À propos / Biographie</h3>
                <p style="font-size: 0.95rem; color: #4a4a4a; line-height: 1.5; font-style: <?= empty($stagiaire['biographie']) ? 'italic' : 'normal' ?>;">
                    <?= !empty($stagiaire['biographie']) ? nl2br(htmlspecialchars($stagiaire['biographie'])) : "Ce stagiaire n'a pas encore rédigé de biographie." ?>
                </p>
            </div>
            <div style="margin-bottom: 35px; border-left: 4px solid var(--color-gold); padding-left: 15px;">
                <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: var(--color-dark);"> Parcours Scolaire détaillé</h3>
                <p style="font-size: 0.95rem; color: #4a4a4a; line-height: 1.5; font-style: empty($stagiaire['parcours_scolaire']) ? 'italic' : 'normal' ?>;">
                    <?= !empty($stagiaire['parcours_scolaire']) ? nl2br(htmlspecialchars($stagiaire['parcours_scolaire'])) : "Aucun détail sur le parcours scolaire fourni." ?>
                </p>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="messagerie.php?contact_id=<?= $stagiaire['id'] ?>" style="display: inline-block; padding: 12px 30px; background: var(--gradient-btn); color: white; text-decoration: none; font-weight: 600; border-radius: 50px; box-shadow: var(--shadow-btn);">
                     Envoyer un message privé
                </a>
            </div>
            
            <div style="margin-top: 40px;">
                <h3 style="font-size: 1.2rem; color: var(--color-dark); margin-bottom: 15px;">
                    📚 Ressources partagées
                </h3>
                <?php if (!empty($ressources)): ?>
                    <?php foreach ($ressources as $res): ?>
                        <div style="
                            background: white;
                            padding: 15px;
                            border-radius: 10px;
                            margin-bottom: 15px;
                            border: 1px solid #eee;
                            box-shadow: var(--shadow-card);
                        ">
                            <h4 style="margin-bottom: 5px; color: var(--color-accent);">
                                <?= htmlspecialchars($res['titre']) ?>
                            </h4>
            
                            <p style="font-size: 0.9rem; color: #555;">
                                <?= htmlspecialchars($res['sujet']) ?>
                            </p>
            
                            <?php if (!empty($res['probleme_rencontre'])): ?>
                                <p style="font-size: 0.85rem; color: #777;">
                                    <strong>Problème :</strong> <?= htmlspecialchars($res['probleme_rencontre']) ?>
                                </p>
                            <?php endif; ?>
            
                            <?php if (!empty($res['solution_utilisee'])): ?>
                                <p style="font-size: 0.85rem; color: #777;">
                                    <strong>Solution :</strong> <?= htmlspecialchars($res['solution_utilisee']) ?>
                                </p>
                            <?php endif; ?>
            
                            <small style="color: #aaa;">
                                📅 <?= $res['date_publication'] ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777;">Ce stagiaire n’a pas encore partagé de ressources.</p>
                <?php endif; ?>
            </div>
        </div>
    </body>
</html>
