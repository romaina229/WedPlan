<?php
/**
 * @package WeddingManagement
 * @subpackage AdminPanel
 */

require_once __DIR__ . '/config.php';

// Démarrer la session correctement (avec le bon nom)
AuthManager::startSession();

// Vérifier que l'utilisateur est connecté
AuthManager::requireLogin();

// Récupérer l'utilisateur connecté
$currentUser = AuthManager::getCurrentUser();

if (!$currentUser || empty($currentUser['id'])) {
    header("Location: auth/login.php");
    exit;
}

$userId = (int)$currentUser['id'];

$db = DatabaseConnection::getInstance()->getConnection();

$error = '';
$success = '';

// Récupérer le mariage de l'utilisateur connecté
$sql = "SELECT * FROM wedding_dates WHERE user_id = :user_id LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $userId]);
$wedding = $stmt->fetch();

if (!$wedding) {
    die("Aucun mariage trouvé pour cet utilisateur.");
}

$weddingId = $wedding['id'];

// Traitement de l'ajout d'un parrain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sponsor'])) {
    $sponsorNom = trim($_POST['sponsor_nom_complet'] ?? '');
    $sponsorConjoint = trim($_POST['sponsor_conjoint_nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role = $_POST['role'] ?? 'parrain';
    $password = $_POST['password'] ?? '';
    
    if (empty($sponsorNom) || empty($sponsorConjoint) || empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        try {
            // Vérifier si l'email existe déjà pour ce mariage
            $checkSql = "SELECT id FROM wedding_sponsors WHERE email = :email AND wedding_dates_id = :wedding_id";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->execute([
                ':email' => $email,
                ':wedding_id' => $weddingId
            ]);
            
            if ($checkStmt->fetch()) {
                $error = "Cet email est déjà utilisé pour ce mariage.";
            } else {
                $sql = "INSERT INTO wedding_sponsors 
                        (wedding_dates_id, sponsor_nom_complet, sponsor_conjoint_nom_complet, email, password_hash, telephone, role, statut) 
                        VALUES (:wedding_id, :sponsor_nom, :sponsor_conjoint, :email, :password_hash, :telephone, :role, 'actif')";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':wedding_id' => $weddingId,
                    ':sponsor_nom' => $sponsorNom,
                    ':sponsor_conjoint' => $sponsorConjoint,
                    ':email' => $email,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':telephone' => $telephone,
                    ':role' => $role
                ]);
                
                $success = "Parrain/Conseiller ajouté avec succès.";
            }
        } catch (PDOException $e) {
            error_log("Erreur ajout parrain: " . $e->getMessage());
            $error = "Erreur lors de l'ajout du parrain.";
        }
    }
}

// Traitement de la désactivation d'un parrain
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['sponsor_id'])) {
    $sponsorId = intval($_GET['sponsor_id']);
    
    try {
        $sql = "UPDATE wedding_sponsors 
                SET statut = IF(statut = 'actif', 'inactif', 'actif') 
                WHERE id = :id AND wedding_dates_id = :wedding_dates_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $sponsorId,
            ':wedding_dates_id' => $weddingId
        ]);
        
        $success = "Statut du parrain modifié avec succès.";
    } catch (PDOException $e) {
        error_log("Erreur modification statut: " . $e->getMessage());
        $error = "Erreur lors de la modification du statut.";
    }
}

// Récupérer la liste des parrains
$sql = "SELECT * FROM wedding_sponsors WHERE wedding_dates_id = :wedding_dates_id ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':wedding_dates_id' => $weddingId]);
$sponsors = $stmt->fetchAll();

// Récupérer les statistiques d'activité
$sql = "SELECT ws.sponsor_nom_complet, COUNT(sc.id) as nb_commentaires
        FROM wedding_sponsors ws
        LEFT JOIN sponsor_comments sc ON ws.id = sc.sponsor_id
        WHERE ws.wedding_dates_id = :wedding_dates_id
        GROUP BY ws.id";
$stmt = $db->prepare($sql);
$stmt->execute([':wedding_dates_id' => $weddingId]);
$statsCommentaires = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Parrains - Administration</title>
    <link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400..700;1,400..700&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Roboto+Serif:ital,opsz,wght@0,8..144,100..900;1,8..144,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/spondh.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-users-cog"></i> Gestion des Parrains/Conseillers</h1>
            <p>Gérez les personnes qui peuvent suivre et commenter vos préparatifs de mariage</p>
            <div class="header-actions">
                <a href="index.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="wedding_date.php"><i class="fas fa-calendar-alt"></i> Informations mariage</a>
            </div>
        </div>
    </div>
    
    <div class="container">      
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo escapeHtml($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo escapeHtml($success); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> À propos du système de parrains :</strong><br>
            Les parrains/conseillers peuvent consulter vos dépenses et l'évolution de votre budget en temps réel. 
            Ils peuvent également ajouter des commentaires et suggestions, mais ne peuvent pas modifier ou supprimer vos données.
        </div>
        
        <!-- Formulaire d'ajout -->
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> Ajouter un Parrain/Conseiller</h2>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="sponsor_nom_complet">
                            <i class="fas fa-user"></i> Nom complet du parrain <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="sponsor_nom_complet" 
                            name="sponsor_nom_complet" 
                            placeholder="Ex: Jean DUPONT"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="sponsor_conjoint_nom_complet">
                            <i class="fas fa-user-friends"></i> Nom complet du/de la conjoint(e) <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="sponsor_conjoint_nom_complet" 
                            name="sponsor_conjoint_nom_complet" 
                            placeholder="Ex: Marie DUPONT"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Adresse email <span class="required">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="parrain@example.com"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">
                            <i class="fas fa-phone"></i> Téléphone
                        </label>
                        <input 
                            type="tel" 
                            id="telephone" 
                            name="telephone" 
                            placeholder="+229 97 00 00 00"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-tag"></i> Rôle <span class="required">*</span>
                        </label>
                        <select id="role" name="role" required>
                            <option value="parrain">👔 Parrain</option>
                            <option value="conseiller">🎓 Conseiller</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Mot de passe <span class="required">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••"
                            required
                        >
                        <small style="color: #666;">Minimum 6 caractères</small>
                    </div>
                </div>
                
                <button type="submit" name="add_sponsor" class="btn btn-primary">
                    <i class="fas fa-save"></i> Ajouter le parrain/conseiller
                </button>
            </form>
        </div>
        
        <!-- Liste des parrains -->
        <div class="card">
            <h2><i class="fas fa-list"></i> Liste des Parrains/Conseillers</h2>
            
            <?php if (count($sponsors) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Couple Parrain</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Commentaires</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sponsors as $sponsor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo escapeHtml($sponsor['sponsor_nom_complet']); ?></strong>
                                        <br>
                                        <small style="color: #666;">& <?php echo escapeHtml($sponsor['sponsor_conjoint_nom_complet']); ?></small>
                                    </td>
                                    <td><?php echo escapeHtml($sponsor['email']); ?></td>
                                    <td><?php echo escapeHtml($sponsor['telephone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $sponsor['role']; ?>">
                                            <?php echo $sponsor['role'] === 'parrain' ? '👔 Parrain' : '🎓 Conseiller'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $nbComments = $statsCommentaires[$sponsor['id']] ?? 0;
                                        echo $nbComments . ' commentaire' . ($nbComments > 1 ? 's' : '');
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $sponsor['statut']; ?>">
                                            <?php echo $sponsor['statut'] === 'actif' ? '✅ Actif' : '❌ Inactif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?action=toggle_status&sponsor_id=<?php echo $sponsor['id']; ?>"
                                               onclick="return confirm('Voulez-vous vraiment modifier le statut de ce parrain ?')">
                                                <i class="fas <?php echo $sponsor['statut'] === 'actif' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                <?php echo $sponsor['statut'] === 'actif' ? 'Désactiver' : 'Activer'; ?>
                                            </a>
                                            <a href="?action=delete&sponsor_id=<?php echo $sponsor['id']; ?>"
                                               onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce parrain ? Tous ses commentaires seront également supprimés.')"
                                               class="delete-link">
                                                <i class="fas fa-trash-alt"></i> Supprimer
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 40px 20px; color: #666;">
                    <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                    <p>Aucun parrain/conseiller ajouté pour le moment</p>
                    <p style="font-size: 14px; color: #999; margin-top: 10px;">
                        Utilisez le formulaire ci-dessus pour inviter vos premiers parrains.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Informations complémentaires -->
        <div class="card" style="background: #f8f9fa;">
            <h3><i class="fas fa-key"></i> Accès des parrains</h3>
            <p>Les parrains peuvent se connecter à l'adresse : <a href="sponsor_login.php" target="_blank">se connecter</a></p>
            <p>Ils utiliseront leur email et le mot de passe que vous avez défini.</p>
            <div style="margin-top: 15px; background: #fff3cd; padding: 15px; border-radius: 5px;">
                <i class="fas fa-lightbulb" style="color: #856404;"></i>
                <strong>Conseil :</strong> Notez bien les mots de passe que vous définissez et communiquez-les 
                de manière sécurisée à vos parrains.
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>