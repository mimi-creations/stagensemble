<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header("Location: login.php");
    exit;
}



$mon_id = intval($_SESSION['utilisateur_id']);
$destinataire_id = intval($_GET['id'] ?? 0);
if ($_SERVER["REQUEST_METHOD"]==="POST" && $destinataire_id > 0 && !empty(trim($_POST['message'] ?? ''))) {
    $message = trim($_POST['message']);
    $stmt = $pdo->prepare("INSERT INTO messages_prives (expediteur_id, destinataire_id, message, lu) VALUES (?,?,?,0)");
    $stmt->execute([$mon_id, $destinataire_id, $message]);
    header("Location: messagerie.php?id=" . $destinataire_id);
    exit;
}

if ($destinataire_id >0) {
    $stmt_update = $pdo->prepare("UPDATE messages_prives SET lu = 1 WHERE expediteur_id = ? AND destinataire_id = ? AND lu =0");
    $stmt_update->execute([$destinataire_id, $mon_id]);
}

$stmt = $pdo->prepare("
    SELECT s.id, s.nom, s.prenom, s.avatar,
        (SELECT message FROM messages_prives
        WHERE (expediteur_id = s.id AND destinataire_id = $mon_id )
        OR (expediteur_id = $mon_id  AND destinataire_id = s.id)
        ORDER BY date_envoi DESC LIMIT 1) as dernier_message,
        (SELECT MAX(date_envoi) FROM messages_prives
        WHERE (expediteur_id = s.id AND destinataire_id = $mon_id )
        OR (expediteur_id = $mon_id  AND destinataire_id = s.id)) as date_dernier_msg
    FROM anciens_stagiaires s
    WHERE s.id != $mon_id 
    ORDER BY date_dernier_msg DESC, s.nom ASC
");
$stmt->execute();
$conversations = $stmt->fetchAll();

$destinataire = null;
$messages =[];
if ($destinataire_id > 0) {
    $stmt = $pdo->prepare("SELECT prenom, nom FROM anciens_stagiaires WHERE id=?");
    $stmt->execute([$destinataire_id]);
    $destinataire = $stmt->fetch();
    if ($destinataire) {
        $stmt = $pdo->prepare("
            SELECT m.* , s.prenom, s.nom 
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
        <title>Messagerie Privée</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <?php include 'navbar.php'; ?>
        <div class="whatsapp-container">
        
        <div class="sidebar">
            <div class="sidebar-header">Stagiaires</div>
            <div style="padding: 10px 15px; border-bottom: 1px solid #eef0ff;">
                <input type="text" id="searchStagiaire" placeholder="Rechercher un stagiaire..."
                style="width: 100%; padding: 8px 12px; border: 1px solid #e5e7eb; border-raidus: 20px; outline: none; font-size: 0.88rem;">
            </div>
            <div class="contact-list">
                <?php if (empty($conversations)): ?>
                    <p style="text-align: center; color: var(--color-muted); padding: 20px; font-size: 0.9rem;">
                        Aucun stagiaire trouvé.
                    </p>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <a href="messagerie.php?id=<?= $c['id'] ?>" class="contact-item <?= ($destinataire_id == $c['id']) ? 'active' : '' ?>" style="display: flex; align-items: center; gap: 10px; padding: 10px 15px; text-decoration: none; color: inherit; border-bottom: 1px solid #f3f4f6;">
                            <img src="<?= htmlspecialchars($c['avatar'] ?? 'default_avatar.png') ?>" alt="Avatar" class="contact-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                    <h3 style="margin: 0; font-size: 0.95rem; text-transform: capitalize;"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></h3>
                                </div>
                                <p style="margin: 3px 0 0 0; font-size: 0.82rem; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= !empty($c['dernier_message']) ? htmlspecialchars($c['dernier_message']) : '<i>Aucun message émis</i>' ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-area">
            <?php if ($destinataire): ?>
                <div class="chat-header" style="padding: 15px 20px; background: #fff; border-bottom: 1px solid #eef0ff; font-weight: bold; font-size: 1.1rem; color: var(--color-primary);">
                    Discussion avec <?= htmlspecialchars($destinataire['prenom'] . ' ' . $destinataire['nom']) ?>
                </div>
                
                <div class="chat-box" style="flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 12px;">
                    <?php if (empty($messages)): ?>
                        <p style="text-align: center; color: #aaa; margin-top: 20px; font-style: italic;">Envoyez un message pour démarrer la discussion !</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="bubble <?= $msg['expediteur_id'] == $mon_id ? 'me' : 'other' ?>" style="position: relative; padding: 10px 15px 25px 15px; border-radius: 12px; max-width: 60%; width: fit-content; <?= $msg['expediteur_id'] == $mon_id ? 'align-self: flex-end; background: #e0f2fe; color: #0369a1;' : 'align-self: flex-start; background: #fff; border: 1px solid #e2e8f0; color: #334155;' ?>">
                                <strong><?= $msg['expediteur_id'] == $mon_id ? 'Moi' : htmlspecialchars($msg['prenom']) ?> :</strong>
                                <p style="margin: 5px 0 0 0; word-break: break-word;"><?= htmlspecialchars($msg['message']) ?></p>
                                
                                <span class="chat-time" style="position: absolute; bottom: 4px; right: 10px; font-size: 0.68rem; color: rgba(0, 0, 0, 0.4);">
                                    <?php 
                                        $date = new DateTime($msg['date_envoi']);
                                        echo $date->format('H:i'); 
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form class="chat-form" method="POST" action="" style="padding: 15px 20px; background: #fff; border-top: 1px solid #eef0ff; display: flex; gap: 10px;">
                    <input type="text" name="message" placeholder="Écrivez votre message privé..." required autocomplete="off" style="flex: 1; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 25px; outline: none;">
                    <input type="submit" value="Envoyer" style="padding: 12px 25px; background: var(--color-accent, #0077b5); color: #fff; border: none; border-radius: 25px; cursor: pointer; font-weight: bold;">
                </form>
            <?php else: ?>
                <div class="chat-blank" style="flex: 1; display: flex; align-items: center; justify-content: center; color: #94a3b8; background: #f8fafc; font-size: 0.95rem;">
                     Sélectionnez un stagiaire à gauche pour démarrer une conversation privée.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        var chatBox = document.querySelector('.chat-box');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
    <script>
        document.getElementById('searchStagiaire').addEventListener('input', function() {
            let saisie = this.value.toLowerCase();
            let contacts = document.querySelectorAll('.contact-list .contact-item');
            contacts.forEach(function(contact){
                let nomStagiaire = contact.textContent.toLowerCase();
                if (nomStagiaire.includes(saisie)){
                    contact.style.display = 'flex';
                }
                else{
                    contact.style.display ='none';
                }
            });
        });
    </script>
    </body>
</html>
