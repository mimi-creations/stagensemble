<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$erreur = "";

if (isset($_SESSION['utilisateur'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['email'], $_POST['motdepasse'])) {

    $email = $_POST['email'];
    $motdepasse = $_POST['motdepasse'];

    $stmt = $pdo->prepare("SELECT * FROM anciens_stagiaires WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['motdepasse'] === $motdepasse) {
        $_SESSION['utilisateur'] = $user;
        $_SESSION['utilisateur_id'] = $user['id'];

        header("Location: index.php"); ✅
        exit();
    } else {
        $erreur = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — StagEnsemble</title>
    
    <link rel="stylesheet" href="style.css">
    
    <style>
        html, body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #faf7f2 !important; 
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }
        
        .login-main-container {
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            width: 100% !important;
            max-width: 400px !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }

        .auth-wrapper {
            display: block !important;
            width: 100% !important;
            min-height: auto !important;
        }

        .auth-card {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .auth-logo {
            text-align: center !important;
            font-size: 2rem !important;
            font-weight: 700 !important;
            color: #1a1a1a !important;
            margin-bottom: 24px !important; 
            font-family: 'Poppins', sans-serif !important;
        }
        .auth-logo span {
            display: block !important;
            font-size: 0.85rem !important;
            color: #e85d04 !important; 
            font-weight: 600 !important;
            margin-top: 6px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
        }
    </style>
</head>
<body>
    
    <body>

    <div class="login-main-container">
        
        <div class="auth-logo">
            🎓 StagEnsemble
            <span>Attijariwafa Bank — Espace Stagiaires</span>
        </div>
        
        <div class="auth-wrapper">
            <div class="card auth-card">
                
                <h1 style="text-align: center; font-size: 1.4rem; margin-bottom: 20px; color: #333; margin-top: 5px;">Connexion</h1>
                
                <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'success'): ?>
                    <div style="background: #ecfdf5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; text-align: center; border: 1px solid #a7f3d0; font-weight: 500;">
                         Inscription réussie ! Vous pouvez maintenant vous connecter.
                    </div>
                <?php endif; ?>
                
                <?php if ($erreur): ?>
                    <div class="erreur"> <?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>
                
                <form method="POST" action="login.php">
                    <label for="nom">Identifiant (prenom.nom)</label>
                    <input type="text" id="nom" name="nom" placeholder="ex : john.doe" required autocomplete="username">

                    <label for="motdepasse">Mot de passe</label>
                    <input type="password" id="motdepasse" name="motdepasse" placeholder="••••••••" required autocomplete="current-password">

                    <button type="submit">Se connecter</button>
                </form>
                
                <div class="auth-link">
                    Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
                </div>
            </div>
        </div>
        
    </div>
</body>
</html>
