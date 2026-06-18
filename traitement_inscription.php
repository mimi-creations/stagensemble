<?php
ini_set ('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom =trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ecole = trim($_POST['ecole'] ?? '');
    $annee_stage = trim($_POST['annee_stage'] ?? '');  
    $duree_stage = trim($_POST['duree_stage'] ?? '');
    $motdepasse = password_hash ($_POST['motdepasse'], PASSWORD_DEFAULT) ?? '';
    if (empty($nom) || empty($prenom) || empty($email) || empty($motdepasse)) {
        echo "Erreur : veuillez remplir tous les champs obligatoires. <a href='inscription.php'>Retour</a>";
        exit;
    }
    try {
        $stmt =$pdo->prepare("SELECT id FROM anciens_stagiaires WHERE prenom=? AND nom=?");
        $stmt->execute([$prenom, $nom]);
        if ($stmt->fetch()){
            echo "Erreur: Ce nom d'utilisateur est déjà utilisé. <a href='inscription.php'>Retour</a>";
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO anciens_stagiaires (nom, prenom, email, ecole, annee_stage, duree_stage, motdepasse) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nom, $prenom, $email, $ecole, $annee_stage, $duree_stage, $motdepasse]);
        header("Location: login.php?inscription=success");
        exit;
    } 
    catch (PDOException $e) {
        echo "Erreur de Base de Données: " . $e->getMessage() . "<a href='inscription.php'>Retour</a>";
        exit;
    }
}else {
    header("Location: inscription.php");
    exit;
}
?>
