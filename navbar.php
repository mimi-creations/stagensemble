<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages_prives WHERE destinataire_id = ? AND lu = 0");
$stmt->execute([$_SESSION['utilisateur_id'] ?? 0]);
$nbMessages = $stmt->fetchColumn();
?>

<nav>
    <div class="nav-logo-wrapper" style="display: flex; align-items: center;">
        <img src="logo-attijari.png" alt="Attijariwafa Bank" class="nav-logo" style="max-height: 40px; margin-right: 10px;">
    </div>
    <span class="nav-brand" style="display: flex; align-items: center; gap: 5px; font-weight: bold; color: #fff; margin-right: 20px;">
        🎓 StagEnsemble
    </span>
    
    <a href="index.php">Accueil</a>
    <a href="annuaire.php">Annuaire</a>
    <a href="chat.php">Chat Stagiaires</a>
    <a href="messagerie.php">
        Messagerie
        <?php if ($nbMessages > 0): ?>
            <span style="
                background:red;
                color:white;
                border-radius:50%;
                padding:4px 8px;
                margin-left:5px;
                font-size:12px;
            ">
                <?php echo $nbMessages; ?>
            </span>
        <?php endif; ?>
    </a>
    <a href="ressources.php">Ressources &amp; Problèmes</a>
    <a href="parametres.php">Mon Compte</a>
    <button id="theme-toggle" onclick="toggleTheme()" title= "Changer le thème">🌙 / ☀️</button> 
    <a href="deconnexion.php" class="nav-logout" style="color:red;">Déconnexion</a>
</nav>
