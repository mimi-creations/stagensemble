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

// Convertir la photo en base64 (stockée directement en BDD)
$image_base64 = null;
if (!empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext      = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $autorise = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($ext, $autorise)) {
        $mime_types = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime         = $mime_types[$ext];
        $contenu      = file_get_contents($_FILES['photo']['tmp_name']);
        $image_base64 = 'data:' . $mime . ';base64,' . base64_encode($contenu);
    }
}

// Créer le groupe
$stmt = $pdo->prepare("INSERT INTO groupes (nom, image, createur_id) VALUES (?, ?, ?)");
$stmt->execute([$nom_groupe, $image_base64, $mon_id]);
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
