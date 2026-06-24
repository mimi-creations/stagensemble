<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messagerie.php");
    exit;
}

$mon_id     = intval($_SESSION['utilisateur_id']);
$nom_groupe = trim($_POST['nom_groupe'] ?? '');
$membres    = $_POST['membres'] ?? [];

if (empty($nom_groupe) || empty($membres)) {
    header("Location: messagerie.php?erreur=groupe_incomplet");
    exit;
}

// Upload photo (colonne "image" dans ta table groupes)
$image_path = null;
if (!empty($_FILES['photo']['name'])) {
    $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $autorise = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($ext, $autorise)) {
        $dossier = 'uploads/groupes/';
        if (!is_dir($dossier)) mkdir($dossier, 0755, true);

        $nom_fichier = 'groupe_' . uniqid() . '.' . $ext;
        $destination = $dossier . $nom_fichier;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            $image_path = $destination;
        }
    }
}

// Créer le groupe
$stmt = $pdo->prepare("INSERT INTO groupes (nom, image, createur_id) VALUES (?, ?, ?)");
$stmt->execute([$nom_groupe, $image_path, $mon_id]);
$groupe_id = $pdo->lastInsertId();

// Ajouter le créateur comme membre
$stmtM = $pdo->prepare("INSERT IGNORE INTO groupe_membres (groupe_id, membre_id) VALUES (?, ?)");
$stmtM->execute([$groupe_id, $mon_id]);

// Ajouter les membres cochés
foreach ($membres as $membre_id) {
    $membre_id = intval($membre_id);
    if ($membre_id > 0) {
        $stmtM->execute([$groupe_id, $membre_id]);
    }
}

header("Location: groupe.php?id=" . $groupe_id);
exit;
