<?php
/**
 * Page de connexion pour les parrains/conseillers
 * 
 * @package WeddingManagement
 * @subpackage SponsorSystem
 */

require_once '/config_da.php';

// Si déjà connecté, rediriger vers le tableau de bord
if (isSponsorLoggedIn()) {
    header("Location: sponsor_dashboard.php");
    exit();
}

$error = '';
$success = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_sponsor'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $db = DatabaseConnection::getInstance()->getConnection();
            
            // CORRECTION: Utiliser les bons noms de colonnes
            $sql = "SELECT ws.*, wd.fiance_nom_complet, wd.fiancee_nom_complet 
                    FROM wedding_sponsors ws
                    INNER JOIN wedding_dates wd ON ws.wedding_dates_id = wd.id  -- CORRIGÉ: wedding_dates_id au lieu de wedding_id
                    WHERE ws.email = :email AND ws.statut = 'actif'
                    LIMIT 1";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $sponsor = $stmt->fetch();
            
            // CORRECTION: Utiliser password_hash au lieu de md5
            if ($sponsor && password_verify($password, $sponsor['password_hash'])) {  // CORRIGÉ: password_hash au lieu de password
                // Connexion réussie
                $_SESSION[SPONSOR_SESSION_KEY] = true;
                $_SESSION[SPONSOR_ID_KEY] = $sponsor['id'];
                $_SESSION[SPONSOR_WEDDING_ID_KEY] = $sponsor['wedding_dates_id'];  // CORRIGÉ: wedding_dates_id
                $_SESSION[SPONSOR_NAME_KEY] = $sponsor['sponsor_nom_complet'];
                $_SESSION[SPONSOR_ROLE_KEY] = $sponsor['role'];
                
                // Enregistrer l'activité de connexion
                logSponsorActivity($sponsor['id'], $sponsor['wedding_dates_id'], 'connexion');
                
                header("Location: sponsor_dashboard.php");
                exit();
            } else {
                $error = "Identifiants incorrects.";
            }
        } catch (PDOException $e) {
            error_log("Erreur de connexion parrain: " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Parrain/Conseiller - Gestion Mariage</title>
    <link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="assets/css/loginsponsor.css">    
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon-wrapper">
                <svg viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>
            <h1>Espace Parrain/Conseiller</h1>
            <p class="subtitle">Accompagnez les futurs mariés dans leur préparation</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <strong>⚠️ Erreur :</strong> <?php echo escapeHtml($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>✓ Succès :</strong> <?php echo escapeHtml($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">📧 Adresse email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="votre.email@example.com"
                    required
                    value="<?php echo escapeHtml($_POST['email'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password">🔒 Mot de passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
                    required
                >
            </div>
            
            <button type="submit" name="login_sponsor" class="btn-login">
                Se connecter
            </button>
        </form>
        
        <div class="info-box">
            <strong>💡 Note importante :</strong><br>
            En tant que parrain/conseiller, vous pouvez consulter les dépenses et l'avancement des préparatifs, 
            ainsi qu'apporter des commentaires et suggestions aux futurs mariés.
        </div>
        
        <div class="footer-links">
            <a href="index.php">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>