<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

$page_actuelle = basename($_SERVER['PHP_SELF']);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages_prives WHERE destinataire_id = ? AND lu = 0");
$stmt->execute([$_SESSION['utilisateur_id'] ?? 0]);
$nbMessages = $stmt->fetchColumn();
?>

<nav>
    <!-- Logo -->
    <div class="nav-logo-wrapper">
        <img src="logo-attijari.png" alt="Attijariwafa Bank" class="nav-logo">
    </div>

    <!-- Brand -->
    <span class="nav-brand">🎓 StagEnsemble</span>

    <!-- Burger (visible uniquement mobile) -->
    <button class="nav-burger" id="navBurger" aria-label="Menu" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Liens -->
    <div class="nav-links" id="navLinks">
        <a href="index.php"      class="<?= $page_actuelle === 'index.php'      ? 'active' : '' ?>">Accueil</a>
        <a href="annuaire.php"   class="<?= $page_actuelle === 'annuaire.php'   ? 'active' : '' ?>">Annuaire</a>
        <a href="chat.php"       class="<?= $page_actuelle === 'chat.php'       ? 'active' : '' ?>">Chat Stagiaires</a>
        <a href="messagerie.php" class="<?= $page_actuelle === 'messagerie.php' ? 'active' : '' ?>">
            Messagerie
            <?php if ($nbMessages > 0): ?>
                <span style="background:red; color:#fff; border-radius:50%; padding:2px 7px; margin-left:4px; font-size:11px; font-weight:700;">
                    <?= $nbMessages ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="ressources.php" class="<?= $page_actuelle === 'ressources.php' ? 'active' : '' ?>">Ressources &amp; Problèmes</a>
        <a href="parametres.php" class="<?= $page_actuelle === 'parametres.php' ? 'active' : '' ?>">Mon Compte</a>

        <button id="theme-toggle" onclick="toggleTheme()" title="Changer le thème">🌙 / ☀️</button>

        <a href="deconnexion.php" class="nav-logout">Déconnexion</a>
    </div>
</nav>

<style>
/* ── Burger ── */
.nav-burger {
    display: none;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    margin-left: auto;
    box-shadow: none;
    z-index: 201;
}
.nav-burger span {
    display: block;
    width: 24px;
    height: 2px;
    background: #fff;
    border-radius: 2px;
    transition: transform 0.3s, opacity 0.3s;
}
/* Burger animé quand ouvert */
.nav-burger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.nav-burger.open span:nth-child(2) { opacity: 0; }
.nav-burger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

/* ── Nav links ── */
.nav-links {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    /* Cacher les liens par défaut */
    .nav-links {
        display: none;
        position: absolute;
        top: 62px;
        left: 0; right: 0;
        background: #1a1a1a;
        flex-direction: column;
        align-items: stretch;
        padding: 10px 0 16px;
        gap: 2px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        z-index: 199;
    }
    .nav-links.open { display: flex; }
    .nav-links a,
    .nav-links button { 
        width: 100%;
        text-align: left;
        padding: 12px 20px;
        border-radius: 0;
        font-size: 0.95rem;
    }
    .nav-links #theme-toggle {
        background: none;
        color: #fff;
        box-shadow: none;
        border-radius: 0;
        font-size: 1rem;
    }

    /* Afficher burger */
    .nav-burger { display: flex; }

    /* Logo plus petit */
    .nav-logo { max-height: 46px !important; }
    .nav-logo-wrapper { padding: 4px 10px 4px 0; }
}
</style>

<script>
function toggleMenu() {
    const links  = document.getElementById('navLinks');
    const burger = document.getElementById('navBurger');
    links.classList.toggle('open');
    burger.classList.toggle('open');
}

// Fermer le menu si on clique en dehors
document.addEventListener('click', function(e) {
    const nav    = document.querySelector('nav');
    const links  = document.getElementById('navLinks');
    const burger = document.getElementById('navBurger');
    if (!nav.contains(e.target)) {
        links.classList.remove('open');
        burger.classList.remove('open');
    }
});

// Thème
function toggleTheme() {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    document.getElementById('theme-toggle').textContent = isDark ? '☀️' : '🌙 / ☀️';
}
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark') {
    document.body.classList.add('dark');
    document.getElementById('theme-toggle').textContent = '☀️';
}
</script>
