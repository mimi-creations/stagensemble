<?php
session_start();

if (!isset($_SESSION['utilisateur'])) {
    header("Location: login.php");
    exit;
}
require_once 'db.php';
$utilisateur = htmlspecialchars($_SESSION['utilisateur']);
$stmtStagiaires = $pdo->query("SELECT COUNT(*) FROM anciens_stagiaires");
$totalStagiaires = $stmtStagiaires->fetchColumn();
$stmtRessources = $pdo->query("SELECT COUNT(*) FROM ressources");
$totalRessources = $stmtRessources->fetchColumn();


$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages_prives WHERE destinataire_id = ? AND lu = 0");
$stmt->execute([$_SESSION['utilisateur_id']]);
$nbMessages = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div id="page-accueil">
    <div class="container">
        <div class="hero">
            <h1>Bienvenue, <span id="welcome-name">Stagiaire</span> ! 👋</h1>
            <p>Vous êtes connecté à l'espace stagiaire d'Attijariwafa Bank. Explorez, échangez et apprenez ensemble.</p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <strong ><?= $totalStagiaires ?></strong>
                    <span>Stagiaires inscrits</span>
                </div>
                <div class="hero-stat">
                    <strong ><?= $totalRessources ?></strong>
                    <span>Ressources partagées</span>
                </div>
            </div>
        </div>

        <div class="features-grid">
            <a class="feature-card" href="annuaire.php">
                <span class="icon">📋</span>
                <h3>Annuaire</h3>
                <p>Retrouvez tous les anciens stagiaires et leurs contacts.</p>
            </a>
            <a class="feature-card" href="chat.php">
                <span class="icon">💬</span>
                <h3>Chat Stagiaires</h3>
                <p>Discutez en temps réel avec tous les stagiaires.</p>
            </a>
            <a class="feature-card" href="messagerie.php">
                <span class="icon">🔒</span>
                <h3>Chat Privé</h3>
                <p>Envoyez des messages privés à un stagiaire en particulier.</p>
            </a>
            <a class="feature-card" href="ressources.php">
                <span class="icon">📚</span>
                <h3>Ressources</h3>
                <p>Partagez vos projets, problèmes résolus et conseils.</p>
            </a>
        </div>
    </div>
    </div>

<script>
    function toggleTheme() {
        let current = document.body.classList.toggle("dark");
    
        if (current) {
            localStorage.setItem("theme", "dark");
        } else {
            localStorage.setItem("theme", "light");
        }
    }
    
    // Charger le thème sauvegardé
    window.onload = function () {
        if (localStorage.getItem("theme") === "dark") {
            document.body.classList.add("dark");
        }
    };
</script>

<?php include 'footer.php';?>

</body>
</html>
