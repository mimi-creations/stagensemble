<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])){
    header("Location: login.php");
    exit;
}

$id_utilisateur = $_SESSION['utilisateur_id'];
$erreur = "";
$success = "";

$stmt = $pdo->prepare("SELECT * FROM anciens_stagiaires WHERE id = ?");
$stmt->execute([$id_utilisateur]);
$user = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] === "POST"){
    $biographie = trim($_POST['biographie'] ?? '');
    $parcours = trim($_POST['parcours_scolaire'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $ecole =trim($_POST['ecole'] ?? '');
    $annee_stage = trim($_POST['annee_stage'] ?? '');
    $duree_stage =trim($_POST['duree_stage'] ?? '');
    $secteur = trim($_POST['secteur'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $nom_avatar=$user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $dossier_cible ="";
        $extension =strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $extension_autorisees= ['jpg','jpeg', 'png', 'gif'];
        if (in_array($extension, $extension_autorisees)) {
            $nom_avatar = "avatar_" . $id_utilisateur . "." . $extension;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $dossier_cible . $nom_avatar);
        }else{
            $erreur = "Format d'image non valide (JPG, PNG, GIF uniquement).";
        }
    }
    if (empty($erreur)) {
        $stmt = $pdo->prepare("UPDATE anciens_stagiaires SET biographie = ?, parcours_scolaire = ?, telephone =? , ecole = ?, annee_stage = ?, duree_stage = ?, secteur = ?, linkedin = ?, avatar = ? WHERE id = ?");
        $stmt ->execute([$biographie, $parcours, $telephone, $ecole, $annee_stage, $duree_stage, $secteur, $linkedin, $nom_avatar, $id_utilisateur]);
        $succes="Profil mis à jour avec succès !";
    }
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '' ; 

    if (!empty($ancien_mdp) && !empty($nouveau_mdp)) {
        if (password_verify($ancien_mdp, $user['mot_de_pass'])) {
            $nouveau_mdp_hache = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
            $stmt=$pdo->prepare("UPDATE anciens_stagiaires SET mot_de_pass = ? WHERE id =?");
            $stmt->execute([$nouveau_mdp_hache, $id_utilisateur]);
            $succes .= " Mot de passe modifié avec succès !";
        } else {
            $erreur .= "L'ancien mot de passe est incorrect.";
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM anciens_stagiaires WHERE id= ?");
    $stmt->execute([$id_utilisateur]);
    $user = $stmt->fetch();
}

    $stmt = $pdo->prepare("SELECT * FROM ressources WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $mesRessources = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paramètres du compte</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container">
        <h1>Paramètres de mon compte</h1>
        <p>Gérez vos informations personnelles et votre visibilité sur la plateforme.</p>
        <?php if (!empty($erreur)): ?>
            <div class="alert-error"><?= $erreur ?></div>
        <?php endif; ?>
        <?php if (!empty($succes)): ?>
            <div class="alert-success"><?= $succes ?></div>
        <?php endif; ?>
        <form action="parametres.php" method="POST" enctype="multipart/form-data">
            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
                <img src="<?= htmlspecialchars($user['avatar'] ?? 'default_avatar.png') ?>" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #667eea;">
                <div>
                    <label for ="avatar"> Changer la photo de profil</label>
                    <input type="file" name="avatar" id="avatar">
                </div>
            </div>
            <hr>
            <h2>Informations personnelles</h2>
            <label for="ecole">Ecole / Université d'origine</label>
            <input type="text" name="ecole" id="ecole" value="<?= htmlspecialchars($user['ecole'] ?? '') ?>" placeholder="Ex: Université Mohammed V, EMSI...">
            <label for="annee_stage">Année du stage</label>
            <input type="text" name="annee_stage" id="annee_stage" value="<?= htmlspecialchars($user['annee_stage'] ?? '') ?>" placeholder="Ex: 2024, 2025...">
            <label for="duree_stage">Durée du stage</label>
            <input type="text" name="duree_stage" id="duree_stage" value="<?= htmlspecialchars($user['duree_stage'] ?? '')?>" placeholder="Ex: 6 mois, 1 ans...">
            <label for="secteur"> Secteur</label>
            <input type="text" name="secteur" id="secteur" value="<?= htmlspecialchars($user['secteur'] ?? '')?>"placeholder="Ex: Finance...">
            <label for="telephone">Numéro de téléphone</label>
            <input type="text" name="telephone" id="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" placeholder="Ex: +212 600 000 000">
            <label for="linkedin">Lien du profil LinkedIn</label>
            <input type="text" name="linkedin" id="linkedin" value="<?= htmlspecialchars($user['linked'] ?? '') ?>" placeholder="Ex: https://www.linkedin.com/in/votre-nom">
            <label for="biographie">Mini Biographie (Présentez vous en quelques mots)</label>
            <textarea name="biographie" id="biographie" placeholder="Décrivez vos passions, votre domaine d'expertise..."><?= htmlspecialchars($user['biographie'] ?? '')?></textarea>
            <label fro="parcours_scolaire">Parcours scolaire détaillé</label>
            <textarea name="parcours_scolaire" id="parcours_scolaire" placeholder="Diplômes, mentions, projets marquants..."><?= htmlspecialchars($user['parcours_scolaire'] ?? '')?></textarea>
            <hr>
            <h2> Sécurité </h2>
            <p style="font-size: 0.85rem; color: grey; margin-bottom: 10px;">Laisser ces champs vides si vous ne souhaitez pas modifier votre mot de passe.</p>
            <label for="ancien_mdp">Ancien mot de passe</label>
            <input type="password" name="ancien_mdp" id="ancien_mdp">
            <label for="nouveau_mdp">Nouveau mot de passe</label>
            <input type="password" name="nouveau_mdp" id="nouveau_mdp">
            <div style="margin-top: 20px;">
                <input type="submit" class="btn-primary" value="Enregistrer les modifications">
            </div>
        </form>
        <h3>📚 Mes ressources</h3>
        <?php if (count($mesRessources) > 0): ?>
            <?php foreach ($mesRessources as $res): ?>
                <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
                    <h4><?= htmlspecialchars($res['titre']) ?></h4>
                    <p><?= htmlspecialchars($res['sujet']) ?></p>
                    <small>Publié le : <?= $res['date_publication'] ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Vous n'avez encore partagé aucune ressource.</p>
        <?php endif; ?>
    </div>
</body>
</html>
