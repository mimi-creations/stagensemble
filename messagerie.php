<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}

$mon_id = intval($_SESSION['utilisateur_id']);
$destinataire_id = intval($_GET['id'] ?? 0);

// Envoi message privé
if ($_SERVER["REQUEST_METHOD"] === "POST" && $destinataire_id > 0 && !empty(trim($_POST['message'] ?? ''))) {
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO messages_prives (expediteur_id, destinataire_id, message, lu) VALUES (?,?,?,0)");
    $stmt->execute([$mon_id, $destinataire_id, $message]);
    header("Location: messagerie.php?id=" . $destinataire_id);
    exit;
}

// Marquer messages comme lus
if ($destinataire_id > 0) {
    $stmt_update = $pdo->prepare("UPDATE messages_prives SET lu = 1 WHERE expediteur_id = ? AND destinataire_id = ? AND lu = 0");
    $stmt_update->execute([$destinataire_id, $mon_id]);
}

// Liste des conversations privées
$stmt = $pdo->prepare("
    SELECT s.id, s.nom, s.prenom, s.avatar,
        (SELECT message FROM messages_prives
            WHERE (expediteur_id = s.id AND destinataire_id = $mon_id)
            OR (expediteur_id = $mon_id AND destinataire_id = s.id)
            ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
        (SELECT MAX(date_envoi) FROM messages_prives
            WHERE (expediteur_id = s.id AND destinataire_id = $mon_id)
            OR (expediteur_id = $mon_id AND destinataire_id = s.id)) as date_dernier_msg
    FROM anciens_stagiaires s
    WHERE s.id != $mon_id
    ORDER BY date_dernier_msg DESC, s.nom ASC
");
$stmt->execute();
$conversations = $stmt->fetchAll();

// Indicateurs en ligne
$idsContacts = array_column($conversations, 'id');
$enLigneIds = [];
if (!empty($idsContacts)) {
    $placeholders = implode(',', array_fill(0, count($idsContacts), '?'));
    $stmtOnline = $pdo->prepare("
        SELECT utilisateur_id FROM utilisateurs_connectes
        WHERE utilisateur_id IN ($placeholders)
        AND derniere_activite > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmtOnline->execute(array_values($idsContacts));
    $enLigneIds = $stmtOnline->fetchAll(PDO::FETCH_COLUMN);
}

// Liste des groupes dont je suis membre
$stmtGroupes = $pdo->prepare("
    SELECT g.id, g.nom, g.image,
        (SELECT message FROM messages_groupes WHERE groupe_id = g.id ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
        (SELECT MAX(date_envoi) FROM messages_groupes WHERE groupe_id = g.id) as date_dernier_msg
    FROM groupes g
    JOIN groupe_membres gm ON gm.groupe_id = g.id
    WHERE gm.utilisateur_id = ?
    ORDER BY date_dernier_msg DESC, g.nom ASC
");
$stmtGroupes->execute([$mon_id]);
$mesGroupes = $stmtGroupes->fetchAll();

// Liste de tous les stagiaires (pour la modale de création)
$stmtTous = $pdo->prepare("SELECT id, nom, prenom, avatar FROM anciens_stagiaires WHERE id != ? ORDER BY prenom ASC");
$stmtTous->execute([$mon_id]);
$tousStagiaires = $stmtTous->fetchAll();

// Conversation privée
$destinataire = null;
$messages = [];
if ($destinataire_id > 0) {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM anciens_stagiaires WHERE id = ?");
    $stmt->execute([$destinataire_id]);
    $destinataire = $stmt->fetch();
    if ($destinataire) {
        $stmt = $pdo->prepare("
            SELECT m.*, s.prenom, s.nom
            FROM messages_prives m
            JOIN anciens_stagiaires s ON m.expediteur_id = s.id
            WHERE (m.expediteur_id = ? AND m.destinataire_id = ?)
               OR (m.expediteur_id = ? AND m.destinataire_id = ?)
            ORDER BY m.date_envoi ASC
        ");
        $stmt->execute([$mon_id, $destinataire_id, $destinataire_id, $mon_id]);
        $messages = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Privée</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Modale Création de Groupe ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px 32px;
            width: 460px;
            max-width: 95vw;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 8px 40px rgba(0,0,0,0.18);
        }
        .modal-box h2 {
            margin: 0 0 20px;
            font-size: 1.15rem;
            color: var(--color-primary, #0077b5);
        }
        .modal-box label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .modal-box input[type="text"],
        .modal-box input[type="file"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 16px;
            box-sizing: border-box;
        }
        .membres-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .membre-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }
        .membre-item:hover { background: #f3f4f6; }
        .membre-item input[type="checkbox"] { accent-color: var(--color-primary, #0077b5); width: 16px; height: 16px; }
        .membre-item img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-annuler {
            padding: 9px 20px; border-radius: 8px; border: 1px solid #d1d5db;
            background: #fff; cursor: pointer; font-size: 0.9rem; color: #374151;
        }
        .btn-creer {
            padding: 9px 20px; border-radius: 8px; border: none;
            background: var(--color-accent, #0077b5); color: #fff;
            cursor: pointer; font-weight: bold; font-size: 0.9rem;
        }
        .btn-creer:hover { opacity: 0.88; }

        /* ── Bouton + ── */
        .btn-nouveau-groupe {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 10px 15px;
            padding: 8px 14px;
            background: var(--color-accent, #0077b5);
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: opacity 0.15s;
        }
        .btn-nouveau-groupe:hover { opacity: 0.85; }

        /* ── Onglets sidebar ── */
        .sidebar-tabs {
            display: flex;
            border-bottom: 2px solid #eef0ff;
        }
        .sidebar-tab {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.15s;
        }
        .sidebar-tab.active {
            color: var(--color-primary, #0077b5);
            border-bottom-color: var(--color-primary, #0077b5);
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── Groupe tag ── */
        .groupe-badge {
            font-size:11px;
            opacity:0.7;
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 7px;
            border-radius: 10px;
            margin-left: 5px;
        }

        /* ── Photo preview ── */
        #preview-photo {
            width: 64px; height: 64px;
            border-radius: 50%;
            object-fit: cover;
            display: none;
            margin-bottom: 12px;
            border: 2px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="whatsapp-container">

        <!-- ══════════════ SIDEBAR ══════════════ -->
        <div class="sidebar">
            <div class="sidebar-header">Messagerie</div>

            <!-- Onglets -->
            <div class="sidebar-tabs">
                <div class="sidebar-tab active" onclick="switchTab('prives')">💬 Privés</div>
                <div class="sidebar-tab" onclick="switchTab('groupes')">👥 Groupes</div>
            </div>

            <!-- Recherche -->
            <div style="padding: 10px 15px; border-bottom: 1px solid #eef0ff;">
                <input type="text" id="searchStagiaire" placeholder="Rechercher..."
                    style="width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 20px; outline: none; font-size: 0.88rem; box-sizing: border-box;">
            </div>

            <!-- ── Panel : Conversations privées ── -->
            <div class="tab-panel active" id="panel-prives">
                <div class="contact-list">
                    <?php if (empty($conversations)): ?>
                        <p style="text-align:center; color:#aaa; padding:20px; font-size:0.9rem;">
                            Aucun stagiaire trouvé.
                        </p>
                    <?php else: ?>
                       <?php
                            $lastDate = null; // ✅ pour éviter répéter "Aujourd’hui"
                            ?>
                            
                            <?php foreach ($messages as $msg): ?>
                            
                                <?php
                                $date = new DateTime($msg['date_envoi']);
                            
                                // date sans heure
                                $dateKey = $date->format('Y-m-d');
                            
                                $today = (new DateTime())->format('Y-m-d');
                                $yesterday = (new DateTime('-1 day'))->format('Y-m-d');
                            
                                // ✅ afficher séparateur seulement si nouvelle date
                                if ($dateKey !== $lastDate):
                            
                                    if ($dateKey === $today) {
                                        $label = "Aujourd’hui";
                                    } elseif ($dateKey === $yesterday) {
                                        $label = "Hier";
                                    } else {
                                        $label = $date->format('d/m/Y');
                                    }
                                ?>
                            
                                    <div style="
                                        text-align:center;
                                        margin:15px 0;
                                        color:#666;
                                        font-size:13px;
                                    ">
                                        ─── <?= $label ?> ───
                                    </div>
                            
                                <?php
                                $lastDate = $dateKey;
                                endif;
                                ?>
                            
                                <div style="
                                    position:relative;
                                    padding:10px 15px 25px;
                                    border-radius:12px;
                                    max-width:60%;
                                    width:fit-content;
                                    <?= $msg['expediteur_id'] == $mon_id
                                        ? 'align-self:flex-end; background:#d9fdd3;'
                                        : 'align-self:flex-start; background:#fff; border:1px solid #e2e8f0;' ?>
                                ">
                            
                                    <strong>
                                        <?= $msg['expediteur_id'] == $mon_id ? 'Moi' : htmlspecialchars($msg['prenom']) ?> :
                                    </strong>
                            
                                    <p style="margin:5px 0 0; word-break:break-word;">
                                        <?= htmlspecialchars($msg['message']) ?>
                                    </p>
                            
                                    <!-- ✅ heure seulement -->
                                    <span style="
                                        position:absolute;
                                        bottom:4px;
                                        right:10px;
                                        font-size:11px;
                                        color:rgba(0,0,0,0.5);
                                    ">
                                        <?= $date->format('H:i') ?>
                                    </span>
                            
                                </div>
                            
                            <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Panel : Groupes ── -->
            <div class="tab-panel" id="panel-groupes">

                <!-- Bouton créer un groupe -->
                <button class="btn-nouveau-groupe" onclick="ouvrirModale()">
                    ＋ Nouveau groupe
                </button>

                <div class="contact-list">
                    <?php if (empty($mesGroupes)): ?>
                        <p style="text-align:center; color:#aaa; padding:20px; font-size:0.9rem; font-style:italic;">
                            Vous n'avez pas encore de groupe.
                        </p>
                    <?php else: ?>
                        <?php foreach ($mesGroupes as $g): ?>
                            <a href="groupe.php?id=<?= $g['id'] ?>"
                                style="display:flex; align-items:center; gap:10px; padding:10px 15px; text-decoration:none; color:inherit; border-bottom:1px solid #f3f4f6;">

                                <div style="width:40px; height:40px; flex-shrink:0;">
                                    <?php if (!empty($g['photo'])): ?>
                                        <img src="<?= htmlspecialchars($g['photo']) ?>"
                                            style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:40px; height:40px; border-radius:50%; background:#e0f2fe; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">👥</div>
                                    <?php endif; ?>
                                </div>

                                <div style="flex:1; min-width:0;">
                                    <h3 style="margin:0; font-size:0.95rem;">
                                        <?= htmlspecialchars($g['nom']) ?>
                                        <span class="groupe-badge">Groupe</span>
                                    </h3>
                                    <p style="margin:3px 0 0; font-size:0.82rem; color:#888; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= !empty($g['dernier_message']) ? htmlspecialchars($g['dernier_message']) : '<i>Aucun message</i>' ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ══════════════ ZONE DE CHAT ══════════════ -->
        <div class="chat-area">
            <?php if ($destinataire): ?>
                <div class="chat-header" style="padding:15px 20px; background:#fff; border-bottom:1px solid #eef0ff; font-weight:bold; font-size:1.1rem; color:var(--color-primary);">
                    Discussion avec <?= htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']) ?>
                </div>

                <div class="chat-box" style="flex:1; padding:20px; overflow-y:auto; background:#f8fafc; display:flex; flex-direction:column; gap:12px;">
                    <?php if (empty($messages)): ?>
                        <p style="text-align:center; color:#aaa; margin-top:20px; font-style:italic;">
                            Envoyez un message pour démarrer la discussion !
                        </p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div style="position:relative; padding:10px 15px 25px; border-radius:12px; max-width:60%; width:fit-content;
                                <?= $msg['expediteur_id'] == $mon_id
                                    ? 'align-self:flex-end; background:#e0f2fe; color:#0369a1;'
                                    : 'align-self:flex-start; background:#fff; border:1px solid #e2e8f0; color:#334155;' ?>">
                                <strong><?= $msg['expediteur_id'] == $mon_id ? 'Moi' : htmlspecialchars($msg['prenom']) ?> :</strong>
                                <p style="margin:5px 0 0; word-break:break-word;"><?= htmlspecialchars($msg['message']) ?></p>
                                <?php
                                    $rawDate = $msg['date_envoi'] ?? null;
                                    
                                    if ($rawDate) {
                                        $date = new DateTime($rawDate);
                                    
                                        $today = (new DateTime())->setTime(0, 0);
                                        $yesterday = (new DateTime('-1 day'))->setTime(0, 0);
                                        $compare = (clone $date)->setTime(0, 0);
                                    
                                        if ($compare == $today) {
                                            $texteDate = "Aujourd'hui à " . $date->format('H:i');
                                        } elseif ($compare == $yesterday) {
                                            $texteDate = "Hier à " . $date->format('H:i');
                                        } else {
                                            $texteDate = $date->format('d/m/Y à H:i');
                                        }
                                    } else {
                                        $texteDate = "";
                                    }
                                    ?>
                                    
                                    <span style="
                                        position:absolute;
                                        bottom:4px;
                                        right:10px;
                                        font-size:0.7rem;
                                        color:rgba(0,0,0,0.5);
                                    ">
                                        <?= $texteDate ?>
                                    </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="POST" action="" style="padding:15px 20px; background:#fff; border-top:1px solid #eef0ff; display:flex; gap:10px;">
                    <input type="text" name="message" placeholder="Écrivez votre message privé..." required autocomplete="off"
                        style="flex:1; padding:12px 15px; border:1px solid #e2e8f0; border-radius:25px; outline:none;">
                    <input type="submit" value="Envoyer"
                        style="padding:12px 25px; background:var(--color-accent, #0077b5); color:#fff; border:none; border-radius:25px; cursor:pointer; font-weight:bold;">
                </form>

            <?php else: ?>
                <div class="chat-blank" style="flex:1; display:flex; align-items:center; justify-content:center; color:#94a3b8; background:#f8fafc; font-size:0.95rem;">
                    Sélectionnez un stagiaire ou un groupe pour démarrer une conversation.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════ MODALE CRÉATION GROUPE ══════════════ -->
    <div class="modal-overlay" id="modaleGroupe">
        <div class="modal-box">
            <h2>✨ Créer un nouveau groupe</h2>

            <form action="creer_groupe.php" method="POST" enctype="multipart/form-data">

                <!-- Photo de groupe -->
                <label>Photo du groupe</label>
                <img id="preview-photo" src="" alt="Aperçu">
                <input type="file" name="photo" id="inputPhoto" accept="image/*"
                    onchange="previewPhoto(this)">

                <!-- Nom du groupe -->
                <label>Nom du groupe *</label>
                <input type="text" name="nom_groupe" placeholder="Ex : Promo 2025, Équipe Dev..." required>

                <!-- Sélection des membres -->
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
        // ── Scroll auto chat ──
        const chatBox = document.querySelector('.chat-box');
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

        // ── Recherche ──
        document.getElementById('searchStagiaire').addEventListener('input', function () {
            const saisie = this.value.toLowerCase();
            document.querySelectorAll('.contact-list .contact-item').forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(saisie) ? 'flex' : 'none';
            });
        });

        // ── Onglets ──
        function switchTab(tab) {
            document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('panel-' + tab).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        // ── Modale ──
        function ouvrirModale() {
            document.getElementById('modaleGroupe').classList.add('active');
        }
        function fermerModale() {
            document.getElementById('modaleGroupe').classList.remove('active');
        }
        // Fermer en cliquant dehors
        document.getElementById('modaleGroupe').addEventListener('click', function (e) {
            if (e.target === this) fermerModale();
        });

        // ── Preview photo ──
        function previewPhoto(input) {
            const preview = document.getElementById('preview-photo');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
