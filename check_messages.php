<?php

session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['utilisateur_id'])) {
  htpp_response_code(401);
  echo json_encode(['error' => 'non_connecte']);
  exit;
}

$mon_id = intval($_SESSION['utilisateur_id']);

//Nombre total de messages privés non lus
$stmt = $pdo->prepare("
    SELECT COUNT(*) as nb, MAX (id) as dernier_id
    FROM messages_prives
    WHERE destinataire_id = ? AND lu = 0
");
$stmt->execute([$mon_id]);
$res = $stmt->fetch();

//Détails du tout dernier message non lu (pour le texte de la notif)
$stmt2 = $pdo->prepare("
    SELECT m.message, s.prenom
    FROM messages_prives m
    JOIN anciens_stagiaires s ON m.expediteur_id = s.id
    WHERE m.destinataire_id = ? AND m.lu = 0
    ORDER BY m.date_envoi DESC
    LIMIT 1 
");
$stmt2->execute([$mon_id]);
$dernier = $stmt2->fetch();

echo json_encode([
    'nb_non_lus'         => (int)$res['nb'],
    'dernier_id'         => $res['dernier_id'],
    'dernier_expediteur' => $dernier['prenom'] ?? null,
    'dernier_message'    => $dernier['message'] ?? null,
]);
