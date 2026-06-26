<?php
session_start();
require_once 'db.php';

if (!esset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}
$mon-id    = intval($-SESSION['utilisateur-id']);
$groupe_id = intval($-GET['id'] ?? 0);

if ($groupe_id ≤ 0) {
    header("Location: messagerie.php");
    exit;
}

//Vérifier que l'utilisateur est bien membre du groupe
$stmtCheck = $pdo ->prepare("SELECT id FROM groupe_membres WHERE groupe_id = ? AND membre_id = ?");
$stmtCheck->execute([$groupe_id, $mon_id]);
if (!$stmtCheck->fetch()) {
    header("Location: messagerie.php");
    exit;
}

// Envoi d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message'] ?? ''))) {
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO messages_groupes (groupe_id, expediteur_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$groupe_id, $mon_id, $message]);
    header("Location: groupe.php?id=" . $groupe_id);
    exit;
}

// Infos du groupe
$stmtGroupe = $pdo->prepare("SELECT * FROM groupes WHERE id = ?");
$stmtGroupe->execute([$groupe_id]);
$groupe = $stmtGroupe->fetch();

if (!$groupe) {
    header("Location: messagerie.php");
    exit;
}

// Membres du groupe
$stmtMembres = $pdo->prepare("
    SELECT a.id, a.prenom, a.nom, a.avatar
    FROM groupe_membres gm
    JOIN anciens_stagiaires a ON a.id = gm.membre_id
    WHERE gm.groupe_id = ?
    ORDER BY a.prenom ASC
");
$stmtMembres->execute([$groupe_id]);
$membres = $stmtMembres->fetchAll();

// Messages du groupe
$stmtMessages = $pdo->prepare("
    SELECT m.*, a.prenom, a.nom, a.avatar
    FROM messages_groupes m
    JOIN anciens_stagiaires a ON a.id = m.expediteur_id
    WHERE m.groupe_id = ?
    ORDER BY m.date_envoi ASC
");
$stmtMessages->execute([$groupe_id]);
$messages = $stmtMessages->fetchAll();

// Mes groupes pour la sidebar
$stmtGroupes = $pdo->prepare("
    SELECT g.id, g.nom, g.image,
        (SELECT message FROM messages_groupes WHERE groupe_id = g.id ORDER BY date_envoi DESC LIMIT 1) as dernier_message
    FROM groupes g
    JOIN groupe_membres gm ON gm.groupe_id = g.id
    WHERE gm.membre_id = ?
    ORDER BY g.date_creation DESC
");
$stmtGroupes->execute([$mon_id]);
$mesGroupes = $stmtGroupes->fetchAll();

// Tous les stagiaires (pour la modale)
$stmtTous = $pdo->prepare("SELECT id, nom, prenom, avatar FROM anciens_stagiaires WHERE id != ? ORDER BY prenom ASC");
$stmtTous->execute([$mon_id]);
$tousStagiaires = $stmtTous->fetchAll();

// IDs déjà membres (pour pré-cocher)
$idsMembres = array_column($membres, 'id');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($groupe['nom']) ?> — Groupe</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Layout ── */
        .whatsapp-container { display: flex; height: calc(100vh - 60px); }

        .sidebar {
            width: 300px;
            min-width: 260px;
            border-right: 1px solid #eef0ff;
            display: flex;
            flex-direction: column;
            background: #fff;
            overflow: hidden;
        }
        .sidebar-header {
            padding: 16px 18px;
            font-weight: 700;
            font-size: 1rem;
            color: var(--color-primary, #0077b5);
            border-bottom: 1px solid #eef0ff;
        }

        .chat-area { flex: 1; display: flex; flex-direction: column; overflow: hidden; }

        /* ── Contact list ── */
        .contact-list { overflow-y: auto; flex: 1; }
        .contact-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 15px;
            text-decoration: none; color: inherit;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.12s;
        }
        .contact-item:hover { background: #f8fafc; }
        .contact-item.active { background: #e0f2fe; }

        /* ── Membres tooltip ── */
        .membres-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 18px;
            border-bottom: 1px solid #eef0ff;
            background: #f8fafc;
        }
        .chip {
            display: flex; align-items: center; gap: 5px;
            background: #e0f2fe;
            border-radius: 20px;
            padding: 3px 10px 3px 5px;
            font-size: 0.78rem;
            color: #0369a1;
        }
        .chip img { width: 20px; height: 20px; border-radius: 50%; object-fit: cover; }

        /* ── Chat header ── */
        .chat-header {
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid #eef0ff;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .chat-header img {
            width: 42px; height: 42px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid #e0f2fe;
        }
        .chat-header-emoji {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: #e0f2fe;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
        }
        .chat-header-info { flex: 1; }
        .chat-header-info h2 {
            margin: 0;
            font-size: 1rem;
            color: var(--color-primary, #0077b5);
        }
        .chat-header-info span {
            font-size: 0.78rem;
            color: #6b7280;
        }

        /* ── Chat box ── */
        .chat-box {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* ── Bulles ── */
        .bubble { position: relative; padding: 10px 15px 26px; border-radius: 14px; max-width: 62%; width: fit-content; }
        .bubble.me  { align-self: flex-end;   background: #e0f2fe; color: #0369a1; border-bottom-right-radius: 4px; }
        .bubble.other { align-self: flex-start; background: #fff; border: 1px solid #e2e8f0; color: #334155; border-bottom-left-radius: 4px; }
        .bubble .sender-name { font-size: 0.78rem; font-weight: 700; margin-bottom: 3px; color: #0077b5; }
        .bubble p { margin: 0; word-break: break-word; font-size: 0.92rem; }
        .bubble .bubble-time {
            position: absolute; bottom: 5px; right: 10px;
            font-size: 0.67rem; color: rgba(0,0,0,0.38);
        }
        .bubble .bubble-avatar {
            width: 26px; height: 26px;
            border-radius: 50%; object-fit: cover;
            position: absolute; bottom: -5px; left: -30px;
        }

        /* ── Formulaire ── */
        .chat-form {
            padding: 14px 20px;
            background: #fff;
            border-top: 1px solid #eef0ff;
            display: flex; gap: 10px;
        }
        .chat-form input[type="text"] {
            flex: 1;
            padding: 11px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            outline: none;
            font-size: 0.92rem;
        }
        .chat-form input[type="text"]:focus { border-color: #0077b5; }
        .chat-form button {
            padding: 11px 24px;
            background: var(--color-accent, #0077b5);
            color: #fff; border: none;
            border-radius: 25px;
            cursor: pointer; font-weight: bold;
            font-size: 0.9rem;
            transition: opacity 0.15s;
        }
        .chat-form button:hover { opacity: 0.85; }

        /* ── Bouton retour ── */
        .btn-retour {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 14px;
            background: #f1f5f9;
            color: #374151;
            border: none; border-radius: 8px;
            text-decoration: none;
            font-size: 0.83rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-retour:hover { background: #e2e8f0; }

        /* ── Groupe badge ── */
        .groupe-badge {
            font-size: 0.7rem;
            background: #e0f2fe; color: #0369a1;
            padding: 2px 7px; border-radius: 10px;
            margin-left: 5px;
        }

        /* ── Modale création groupe ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff; border-radius: 16px;
            padding: 28px 32px; width: 460px; max-width: 95vw;
            max-height: 85vh; overflow-y: auto;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
        }
        .modal-box h2 { margin: 0 0 20px; font-size: 1.1rem; color: var(--color-primary, #0077b5); }
        .modal-box label { display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .modal-box input[type="text"],
        .modal-box input[type="file"] {
            width: 100%; padding: 9px 12px;
            border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 0.9rem; margin-bottom: 16px; box-sizing: border-box;
        }
        .membres-list {
            display: flex; flex-direction: column; gap: 8px;
            max-height: 200px; overflow-y: auto;
            border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 10px; margin-bottom: 20px;
        }
        .membre-item {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 8px; border-radius: 8px; cursor: pointer;
            transition: background 0.15s;
        }
        .membre-item:hover { background: #f3f4f6; }
        .membre-item input[type="checkbox"] { accent-color: var(--color-primary, #0077b5); width: 16px; height: 16px; }
        .membre-item img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-annuler { padding: 9px 20px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; color: #374151; }
        .btn-creer { padding: 9px 20px; border-radius: 8px; border: none; background: var(--color-accent, #0077b5); color: #fff; cursor: pointer; font-weight: bold; }

        #preview-photo { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; display: none; margin-bottom: 12px; border: 2px solid #e5e7eb; }

        /* ── Sidebar tabs ── */
        .sidebar-tabs { display: flex; border-bottom: 2px solid #eef0ff; }
        .sidebar-tab { flex: 1; padding: 10px; text-align: center; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: #6b7280; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .sidebar-tab.active { color: var(--color-primary, #0077b5); border-bottom-color: var(--color-primary, #0077b5); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .btn-nouveau-groupe {
            display: flex; align-items: center; gap: 6px;
            margin: 10px 15px; padding: 8px 14px;
            background: var(--color-accent, #0077b5); color: #fff;
            border: none; border-radius: 20px;
            cursor: pointer; font-size: 0.85rem; font-weight: 600;
        }
        .btn-nouveau-groupe:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="whatsapp-container">

        <!-- ══════════ SIDEBAR ══════════ -->
        <div class="sidebar">
            <div class="sidebar-header">Messagerie</div>

            <div class="sidebar-tabs">
                <div class="sidebar-tab" onclick="switchTab('prives', this)">
                    <a href="messagerie.php" style="text-decoration:none; color:inherit;">💬 Privés</a>
                </div>
                <div class="sidebar-tab active" onclick="switchTab('groupes', this)">👥 Groupes</div>
            </div>

            <div class="tab-panel active" id="panel-groupes">
                <button class="btn-nouveau-groupe" onclick="ouvrirModale()">＋ Nouveau groupe</button>

                <div class="contact-list">
                    <?php if (empty($mesGroupes)): ?>
                        <p style="text-align:center; color:#aaa; padding:20px; font-size:0.88rem; font-style:italic;">
                            Aucun groupe.
                        </p>
                    <?php else: ?>
                        <?php foreach ($mesGroupes as $g): ?>
                            <a href="groupe.php?id=<?= $g['id'] ?>"
                                class="contact-item <?= $g['id'] == $groupe_id ? 'active' : '' ?>">

                                <div style="width:40px; height:40px; flex-shrink:0;">
                                    <?php if (!empty($g['image'])): ?>
                                        <img src="<?= htmlspecialchars($g['image']) ?>"
                                            style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px;height:40px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">👥</div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex:1; min-width:0;">
                                    <h3 style="margin:0; font-size:0.92rem;">
                                        <?= htmlspecialchars($g['nom']) ?>
                                        <span class="groupe-badge">Groupe</span>
                                    </h3>
                                    <p style="margin:3px 0 0; font-size:0.8rem; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= !empty($g['dernier_message']) ? htmlspecialchars($g['dernier_message']) : '<i>Aucun message</i>' ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-panel" id="panel-prives">
                <!-- Vide ici, clic redirige vers messagerie.php -->
            </div>
        </div>

        <!-- ══════════ ZONE CHAT ══════════ -->
        <div class="chat-area">

            <!-- Header du groupe -->
            <div class="chat-header">
                <?php if (!empty($groupe['image'])): ?>
                    <img src="<?= htmlspecialchars($groupe['image']) ?>" alt="Photo groupe">
                <?php else: ?>
                    <div class="chat-header-emoji">👥</div>
                <?php endif; ?>

                <div class="chat-header-info">
                    <h2><?= htmlspecialchars($groupe['nom']) ?></h2>
                    <span><?= count($membres) ?> membre<?= count($membres) > 1 ? 's' : '' ?></span>
                </div>

                <a href="messagerie.php" class="btn-retour">← Retour</a>
            </div>

            <!-- Chips des membres -->
            <div class="membres-chips">
                <?php foreach ($membres as $m): ?>
                    <div class="chip">
                        <img src="<?= htmlspecialchars($m['avatar'] ?? 'default_avatar.png') ?>"
                            alt="<?= htmlspecialchars($m['prenom']) ?>">
                        <?= htmlspecialchars($m['prenom']) ?>
                        <?= $m['id'] == $groupe['createur_id'] ? ' 👑' : '' ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Messages -->
            <div class="chat-box" id="chatBox">
                <?php if (empty($messages)): ?>
                    <p style="text-align:center; color:#aaa; margin-top:30px; font-style:italic;">
                        Soyez le premier à écrire dans ce groupe !
                    </p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php $estMoi = ($msg['expediteur_id'] == $mon_id); ?>
                        <div class="bubble <?= $estMoi ? 'me' : 'other' ?>" style="position:relative; <?= !$estMoi ? 'margin-left: 36px;' : '' ?>">

                            <?php if (!$estMoi): ?>
                                <img class="bubble-avatar"
                                    src="<?= htmlspecialchars($msg['avatar'] ?? 'default_avatar.png') ?>"
                                    alt="<?= htmlspecialchars($msg['prenom']) ?>">
                                <div class="sender-name"><?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?></div>
                            <?php endif; ?>

                            <p><?= htmlspecialchars($msg['message']) ?></p>

                            <span class="bubble-time">
                                <?= (new DateTime($msg['date_envoi']))->format('H:i') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Formulaire envoi -->
            <form class="chat-form" method="POST" action="">
                <input type="text" name="message" placeholder="Écrire un message dans le groupe..." required autocomplete="off">
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </div>

    <!-- ══════════ MODALE CRÉER GROUPE ══════════ -->
    <div class="modal-overlay" id="modaleGroupe">
        <div class="modal-box">
            <h2>✨ Créer un nouveau groupe</h2>
            <form action="creer_groupe.php" method="POST" enctype="multipart/form-data">

                <label>Photo du groupe</label>
                <img id="preview-photo" src="" alt="Aperçu">
                <input type="file" name="photo" id="inputPhoto" accept="image/*" onchange="previewPhoto(this)">

                <label>Nom du groupe *</label>
                <input type="text" name="nom_groupe" placeholder="Ex : Promo 2025, Équipe Dev..." required>

                <label>Ajouter des membres *</label>
                <div class="membres-list">
                    <?php foreach ($tousStagiaires as $s): ?>
                        <label class="membre-item">
                            <input type="checkbox" name="membres[]" value="<?= $s['id'] ?>">
                            <img src="<?= htmlspecialchars($s['avatar'] ?? 'default_avatar.png') ?>"
                                alt="<?= htmlspecialchars($s['prenom']) ?>">
                            <span><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-annuler" onclick="fermerModale()">Annuler</button>
                    <button type="submit" class="btn-creer">Créer le groupe</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Scroll auto vers le bas
        const chatBox = document.getElementById('chatBox');
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

        // Onglets sidebar
        function switchTab(tab, el) {
            document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            if (el) el.classList.add('active');
            const panel = document.getElementById('panel-' + tab);
            if (panel) panel.classList.add('active');
        }

        // Modale
        function ouvrirModale() { document.getElementById('modaleGroupe').classList.add('active'); }
        function fermerModale() { document.getElementById('modaleGroupe').classList.remove('active'); }
        document.getElementById('modaleGroupe').addEventListener('click', function(e) {
            if (e.target === this) fermerModale();
        });

        // Preview photo
        function previewPhoto(input) {
            const preview = document.getElementById('preview-photo');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
