<?php
session_start();

$utilisateurs = [
    "admin" => "motdepasse123",
    "alice" => "alice2024",
];

$erreur = "";

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (isset($_SESSION['utilisateur'])) {
    header("Location: accueil.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom        = trim($_POST['nom'] ?? '');
    $motdepasse = $_POST['motdepasse'] ?? '';

    if (empty($nom) || empty($motdepasse)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (isset($utilisateurs[$nom]) && $utilisateurs[$nom] === $motdepasse) {
        $_SESSION['utilisateur'] = $nom;
        header("Location: accueil.php");
        exit;
    } else {
        $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f0f0; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.1); width: 300px; }
        h1 { text-align: center; margin-bottom: 24px; font-size: 22px; }
        label { display: block; font-size: 13px; margin-bottom: 6px; color: #555; }
        input { width: 100%; padding: 10px; margin-bottom: 16px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        button { width: 100%; padding: 11px; background: #6c63ff; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; }
        button:hover { background: #5a52d5; }
        .erreur { background: #fee; color: #c00; padding: 10px; border-radius: 6px; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Connexion</h1>
    <?php if ($erreur): ?>
        <div class="erreur">⚠️ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <label for="nom">Nom d'utilisateur</label>
        <input type="text" id="nom" name="nom" placeholder="ex : admin" required>

        <label for="motdepasse">Mot de passe</label>
        <input type="password" id="motdepasse" name="motdepasse" placeholder="••••••••" required>

        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>