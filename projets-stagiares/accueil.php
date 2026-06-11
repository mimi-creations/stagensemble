<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}

$utilisateur = htmlspecialchars($_SESSION['utilisateur']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f0f0; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.1); text-align: center; }
        h1 { margin-bottom: 10px; }
        p  { color: #555; margin-bottom: 24px; }
        a  { display: inline-block; padding: 10px 24px; background: #6c63ff; color: white; border-radius: 6px; text-decoration: none; }
        a:hover { background: #5a52d5; }
    </style>
</head>
<body>
<div class="card">
    <h1>Bienvenue, <?= $utilisateur ?> ! </h1>
    <p>Vous êtes connecté avec succès.</p>
    <a href="login.php?logout=1">Se déconnecter</a>
</div>
</body>
</html>