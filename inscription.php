<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Stagiaire</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f0f0; margin: 0; padding: 20px 0; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.1); width: 350px; }
        h1 { text-align: center; margin-bottom: 20px; font-size: 22px; }
        label { display: block; font-size: 13px; margin-bottom: 4px; color: #555; }
        input { width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        button { width: 100%; padding: 11px; background: #28a745; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #218838; }
        .erreur { background: #fee; color: #c00; padding: 10px; border-radius: 6px; margin-bottom: 12px; font-size: 13px; }
        .succes { background: #e6f4ea; color: #137333; padding: 10px; border-radius: 6px; margin-bottom: 12px; font-size: 13px; }
        .link { text-align: center; margin-top: 15px; font-size: 13px; }
        .link a { color: #6c63ff; text-decoration: none; }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="box">
        <h2>Inscription</h2>
        
        <form action="traitement_inscription.php" method="POST">
            <label>Prénom *</label>
            <input type="text" name="prenom" required>
        
            <label>Nom *</label>
            <input type="text" name="nom" required>
        
            <label>Adresse Email *</label>
            <input type="email" name="email" placeholder="ex:john.doe@entreprise.com" required>
        
            <label>Mot de passe *</label>
            <input type="password" name="motdepasse" required>
        
            <label>Ecole / Université</label>
            <input type="text" name="ecole">
        
            <label>Année de stage</label>
            <input type="text" name="annee_stage" placeholder="ex:2024">
        
            <label>Durée du stage</label>
            <input type="text" name="duree_stage" placeholder="ex:6 mois">
      
            <label style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="recontacter" value="1">
                Je souhaite être recontacté par l’entreprise
            </label>
    
            <input type="submit" value="Envoyer mon inscription">
        </form>


        <div class="link">
            <a href="login.php">Déjà un compte ? Seconnecter</a>
        </div>
    </div>
</body>
</html>
