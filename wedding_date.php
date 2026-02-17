<?php
declare(strict_types=1);

// Définir ROOT_PATH avant tout
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}

require_once ROOT_PATH . 'config.php';
require_once ROOT_PATH . 'AuthManager.php';

// DÉMARRER LA SESSION CORRECTEMENT
AuthManager::startSession();

// VÉRIFICATION DE CONNEXION - SIMPLE ET EFFICACE
if (!AuthManager::isLoggedIn()) {
    // Rediriger vers la page de connexion
    header('Location: ' . APP_URL . '/auth/login.php?redirect=' . urlencode('wedding_date.php'));
    exit;
}

// Récupérer l'utilisateur connecté
$currentUser = AuthManager::getCurrentUser();
$userId = $currentUser['id'] ?? 0;
$isAdmin = ($currentUser['role'] ?? 'user') === 'admin';

// Récupérer la connexion PDO directement depuis config.php
$db = getDBConnection();

// Récupérer la date actuelle et les informations des fiancés si elles existent
$weddingInfo = [];
$weddingDate = null;
$fiance_nom_complet = '';
$fiancee_nom_complet = '';
$budget_total = 0;
$wedding_id = 0;

try {
    $sql = "SELECT * FROM wedding_dates WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $wedding = $stmt->fetch();
    
    if ($wedding) {
        $weddingDate = $wedding['wedding_date'] ?? null;
        $fiance_nom_complet = $wedding['fiance_nom_complet'] ?? '';
        $fiancee_nom_complet = $wedding['fiancee_nom_complet'] ?? '';
        $budget_total = $wedding['budget_total'] ?? 0;
        $wedding_id = $wedding['id'] ?? 0;
    }
} catch (PDOException $e) {
    error_log("Erreur récupération wedding_dates: " . $e->getMessage());
}

// Récupérer les commentaires des parrains si l'ID du mariage existe
$recentComments = [];
if ($wedding_id > 0) {
    try {
        // Vérifier si les tables existent
        $sql = "SELECT sc.*, ws.sponsor_nom_complet, ws.role 
                FROM sponsor_comments sc
                INNER JOIN wedding_sponsors ws ON sc.sponsor_id = ws.id
                WHERE sc.wedding_dates_id = :wedding_id AND sc.statut = 'public'
                ORDER BY sc.created_at DESC
                LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute([':wedding_id' => $wedding_id]);
        $recentComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Les tables n'existent pas encore - ignorer silencieusement
        error_log("Info: Tables de commentaires non disponibles - " . $e->getMessage());
    }
}

// Traitement du formulaire POST pour enregistrer les informations
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_wedding_info'])) {
    $fiance_nom = trim($_POST['fiance_nom_complet'] ?? '');
    $fiancee_nom = trim($_POST['fiancee_nom_complet'] ?? '');
    $wedding_date = trim($_POST['wedding_date'] ?? '');
    $budget = floatval($_POST['budget_total'] ?? 0);
    
    if (empty($fiance_nom) || empty($fiancee_nom) || empty($wedding_date)) {
        $error_message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        try {
            // Vérifier si le mariage existe déjà
            $sql = "SELECT id FROM wedding_dates WHERE user_id = :user_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Mise à jour
                $sql = "UPDATE wedding_dates SET 
                        fiance_nom_complet = :fiance_nom,
                        fiancee_nom_complet = :fiancee_nom,
                        wedding_date = :wedding_date,
                        budget_total = :budget
                        WHERE user_id = :user_id";
            } else {
                // Insertion
                $sql = "INSERT INTO wedding_dates 
                        (user_id, fiance_nom_complet, fiancee_nom_complet, wedding_date, budget_total) 
                        VALUES (:user_id, :fiance_nom, :fiancee_nom, :wedding_date, :budget)";
            }
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':fiance_nom' => $fiance_nom,
                ':fiancee_nom' => $fiancee_nom,
                ':wedding_date' => $wedding_date,
                ':budget' => $budget
            ]);
            
            if ($result) {
                $success_message = "Informations enregistrées avec succès !";
                
                // Recharger les informations
                $stmt = $db->prepare("SELECT * FROM wedding_dates WHERE user_id = :user_id LIMIT 1");
                $stmt->execute([':user_id' => $userId]);
                $wedding = $stmt->fetch();
                
                if ($wedding) {
                    $weddingDate = $wedding['wedding_date'] ?? null;
                    $fiance_nom_complet = $wedding['fiance_nom_complet'] ?? '';
                    $fiancee_nom_complet = $wedding['fiancee_nom_complet'] ?? '';
                    $budget_total = $wedding['budget_total'] ?? 0;
                    $wedding_id = $wedding['id'] ?? 0;
                }
            } else {
                $error_message = "Erreur lors de l'enregistrement.";
            }
            
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde: " . $e->getMessage());
            $error_message = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// Fonction d'échappement HTML
function escapeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Fonction pour formater la date en français
function formatDateFrancais($dateString) {
    if (empty($dateString)) return '';
    try {
        $timestamp = strtotime($dateString);
        if ($timestamp === false) return $dateString;
        
        setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR', 'fr', 'French');
        return strftime('%A %d %B %Y', $timestamp);
    } catch (Exception $e) {
        return $dateString;
    }
}    
// AJOUTER CETTE SECTION AVANT LE HTML POUR DEBUG
$showComments = ($wedding_id > 0 && !empty($recentComments));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date du Mariage & Informations</title>
    <link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/weddingdate.css">
</head>
<body>
     <div class="container">
        <!-- Messages d'alerte -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= escapeHtml($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= escapeHtml($error_message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Badge défilant avec date -->
        <div id="wedding-date-banner">
            <div class="banner-content">
                <div class="countdown-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="countdown-text">
                    <span class="label">🎉 Date prévue pour le Mariage :</span>
                    <span class="date" id="wedding-date-display">
                        <?= $weddingDate ? formatDateFrancais($weddingDate) : 'Non définie' ?>
                    </span>
                    <span class="countdown" id="wedding-countdown">
                        <?= $weddingDate ? 'Calcul en cours...' : 'Définissez votre date !' ?>
                    </span>
                </div>
                <button class="edit-date-btn" onclick="openWeddingModal()" title="Modifier les informations">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        </div>

        <!-- En-tête avec noms des fiancés -->
        <div class="wedding-header">
            <h1 class="couple-names">
                <?php if (!empty($fiance_nom_complet) && !empty($fiancee_nom_complet)): ?>
                    💑 <?= escapeHtml($fiance_nom_complet . ' & ' . $fiancee_nom_complet) ?>
                <?php else: ?>
                    💑 Bienvenue sur votre espace mariage
                <?php endif; ?>
            </h1>
            
            <?php if ($weddingDate): ?>
                <div class="wedding-date-info">
                    <i class="fas fa-calendar-alt"></i> 
                    Mariage prévu le <strong><?= formatDateFrancais($weddingDate) ?></strong>
                </div>
            <?php endif; ?>
            
            <?php if ($budget_total > 0): ?>
                <div class="budget-info">
                    <div class="budget-card">
                        <span class="label">💰 Budget total</span>
                        <span class="value"><?= number_format((float)$budget_total, 0, ',', ' ') ?></span>
                        <span class="small">FCFA</span>
                    </div>
                    <div class="budget-card">
                        <span class="label">📊 Statut</span>
                        <span class="value" style="color: #27ae60;">En préparation</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($fiance_nom_complet) || empty($fiancee_nom_complet)): ?>
                <div style="margin-top: 20px;">
                    <button class="btn btn-primary" onclick="openWeddingModal()">
                        <i class="fas fa-user-plus"></i> Compléter vos informations
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Actions du menu -->
        <div class="menu-actions">
            <a href="index.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
            </a>
            
            <?php if ($isAdmin && $wedding_id > 0): ?>
                <a href="admin_sponsors.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Gérer les parrains
                </a>
            <?php endif; ?>
        </div>

        <!-- Image du couple -->
        <div class="image-container">
            <img src="assets/images/toanmda-couple.jpg" alt="Couple heureux" class="couple-image">
        </div>

        <!-- Citation -->
        <div class="quote">
            "Le mariage est une alliance sacrée, un engagement d'amour et de fidélité. 
            En planifiant votre mariage avec soin, vous posez les fondations d'une vie commune épanouissante. 
            Que votre union soit bénie et remplie de bonheur !"
        </div>

        <!-- SECTION COMMENTAIRES DES PARRAINS - AJOUTÉE ICI -->
        <?php if ($wedding_id > 0): ?>
            <div class="sponsor-comments-section">
                <div class="section-title">
                    <i class="fas fa-comments fa-2x"></i>
                    <h2>💬 Messages de vos parrains</h2>
                </div>
                
                <?php if (!empty($recentComments)): ?>
                    <?php foreach ($recentComments as $comment): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <div>
                                    <strong><?= escapeHtml($comment['sponsor_nom_complet'] ?? 'Parrain') ?></strong>
                                    <span class="sponsor-badge">
                                        <?= ($comment['role'] ?? 'parrain') === 'parrain' ? '👔 Parrain' : '🎓 Conseiller' ?>
                                    </span>
                                </div>
                                <span class="comment-date">
                                    <i class="far fa-clock"></i> 
                                    <?= isset($comment['created_at']) ? date('d/m/Y à H:i', strtotime($comment['created_at'])) : 'Date inconnue' ?>
                                </span>
                            </div>
                            <div class="comment-body">
                                <?= nl2br(escapeHtml($comment['commentaire'] ?? 'Aucun commentaire')) ?>
                            </div>
                            <?php if (!empty($comment['type_commentaire']) && $comment['type_commentaire'] !== 'general'): ?>
                                <div style="margin-top: 10px;">
                                    <span class="sponsor-badge" style="background: #e8f5e9; color: #2e7d32;">
                                        <?php if ($comment['type_commentaire'] === 'suggestion'): ?>
                                            💡 Suggestion
                                        <?php elseif ($comment['type_commentaire'] === 'depense'): ?>
                                            💰 À propos d'une dépense
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!--<div style="text-align: center; margin-top: 25px;">
                        <a href="view_all_comments.php?wedding_id=<?= $wedding_id ?>" class="btn-link">
                            Voir tous les commentaires <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>-->
                <?php else: ?>
                    <div class="empty-comments">
                        <i class="fas fa-comments"></i>
                        <h3>Aucun commentaire pour le moment</h3>
                        <p>
                            Les commentaires de vos parrains apparaîtront ici.<br>
                            <?php if ($isAdmin): ?>
                                <a href="admin_sponsors.php" style="display: inline-block; margin-top: 15px;" class="btn btn-outline">
                                    <i class="fas fa-user-plus"></i> Inviter des parrains
                                </a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL POUR TOUTES LES INFORMATIONS DU MARIAGE -->
    <div id="wedding-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-heart"></i> 
                    Informations du mariage
                </h2>
                <button class="close-btn" onclick="closeWeddingModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="fiance_nom_complet">
                        <i class="fas fa-user"></i> Nom complet du fiancé <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="fiance_nom_complet" 
                        name="fiance_nom_complet" 
                        class="form-control"
                        placeholder="Ex: Pierre KOUAKOU"
                        value="<?= escapeHtml($fiance_nom_complet) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="fiancee_nom_complet">
                        <i class="fas fa-user"></i> Nom complet de la fiancée <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="fiancee_nom_complet" 
                        name="fiancee_nom_complet" 
                        class="form-control"
                        placeholder="Ex: Marie KONE"
                        value="<?= escapeHtml($fiancee_nom_complet) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="wedding-date-input">
                        <i class="fas fa-calendar-alt"></i> Date du mariage <span class="required">*</span>
                    </label>
                    <input 
                        type="date" 
                        id="wedding-date-input" 
                        name="wedding_date" 
                        class="form-control"
                        value="<?= $weddingDate ? date('Y-m-d', strtotime($weddingDate)) : '' ?>"
                        min="<?= date('Y-m-d') ?>"
                        required
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Choisissez le grand jour !
                    </small>
                </div>

                <div class="form-group">
                    <label for="budget_total">
                        <i class="fas fa-coins"></i> Budget total (FCFA)
                    </label>
                    <input 
                        type="number" 
                        id="budget_total" 
                        name="budget_total" 
                        class="form-control"
                        placeholder="Ex: 1500000"
                        value="<?= $budget_total > 0 ? $budget_total : '' ?>"
                        min="0"
                        step="1000"
                    >
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Budget prévisionnel pour l'ensemble du mariage
                    </small>
                </div>

                <div class="preview-card">
                    <h4 style="margin-bottom: 15px; color: #8b4f8d;">
                        <i class="fas fa-eye"></i> Aperçu
                    </h4>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: #8b4f8d; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fas fa-church"></i>
                        </div>
                        <div>
                            <p style="margin: 0; font-weight: bold; font-size: 18px;" id="couple-preview-text">
                                <?= !empty($fiance_nom_complet) && !empty($fiancee_nom_complet) ? 
                                    escapeHtml($fiance_nom_complet . ' & ' . $fiancee_nom_complet) : 
                                    'Pierre & Marie' ?>
                            </p>
                            <p style="margin: 5px 0 0 0; color: #8b4f8d;" id="date-preview-text">
                                <?= $weddingDate ? formatDateFrancais($weddingDate) : 'Sélectionnez une date' ?>
                            </p>
                            <p style="margin: 5px 0 0 0; color: #27ae60;" id="budget-preview-text">
                                <?= $budget_total > 0 ? 'Budget: ' . number_format((float)$budget_total, 0, ',', ' ') . ' FCFA' : '' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="btn btn-outline" onclick="closeWeddingModal()">
                        Annuler
                    </button>
                    <button type="submit" name="save_wedding_info" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // ============================================================
    // DONNÉES DU MARIAGE
    // ============================================================
    let weddingDate = <?= $weddingDate ? json_encode($weddingDate) : 'null' ?>;
    let countdownInterval = null;

    // ============================================================
    // INITIALISATION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        if (weddingDate) {
            weddingDate = new Date(weddingDate);
            updateBannerDisplay();
            startCountdown();
        }
        
        // Écouter les changements dans le formulaire pour l'aperçu
        document.getElementById('wedding-date-input')?.addEventListener('change', updatePreview);
        document.getElementById('fiance_nom_complet')?.addEventListener('input', updatePreview);
        document.getElementById('fiancee_nom_complet')?.addEventListener('input', updatePreview);
        document.getElementById('budget_total')?.addEventListener('input', updatePreview);
    });

    // ============================================================
    // MISE À JOUR DE L'AFFICHAGE
    // ============================================================
    function updateBannerDisplay() {
        if (!weddingDate) return;
        
        const dateElement = document.getElementById('wedding-date-display');
        if (!dateElement) return;
        
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        const formattedDate = weddingDate.toLocaleDateString('fr-FR', options);
        
        dateElement.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
        updateCountdown();
    }

    function updateCountdown() {
        if (!weddingDate) return;
        
        const now = new Date();
        const weddingDateTime = weddingDate.getTime();
        const nowTime = now.getTime();
        const timeDiff = weddingDateTime - nowTime;
        
        if (timeDiff <= 0) {
            document.getElementById('wedding-countdown').innerHTML = 
                '<span style="color:#4caf50">🎉 Jour J ! Félicitations !</span>';
            if (countdownInterval) clearInterval(countdownInterval);
            return;
        }
        
        const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
        
        let countdownText = '';
        
        if (days > 30) {
            const months = Math.floor(days / 30);
            const remainingDays = days % 30;
            countdownText = `${months} mois ${remainingDays} jours`;
        } else if (days > 0) {
            countdownText = `${days}j ${hours}h ${minutes}m`;
        } else {
            countdownText = `${hours}h ${minutes}m`;
        }
        
        const countdownEl = document.getElementById('wedding-countdown');
        countdownEl.textContent = countdownText;
        
        // Changer la couleur selon l'urgence
        if (days < 7) {
            countdownEl.style.color = '#ff6b6b';
        } else if (days < 30) {
            countdownEl.style.color = '#ffa726';
        } else {
            countdownEl.style.color = '#ffd700';
        }
    }

    function startCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);
        updateCountdown(); // Mise à jour immédiate
        countdownInterval = setInterval(updateCountdown, 60000); // Puis toutes les minutes
    }

    // ============================================================
    // APERÇU DU FORMULAIRE
    // ============================================================
    function updatePreview() {
        const fiance = document.getElementById('fiance_nom_complet')?.value || 'Pierre';
        const fiancee = document.getElementById('fiancee_nom_complet')?.value || 'Marie';
        const dateInput = document.getElementById('wedding-date-input')?.value;
        const budget = document.getElementById('budget_total')?.value;
        
        // Mettre à jour les noms des fiancés
        const couplePreview = document.getElementById('couple-preview-text');
        if (couplePreview) {
            couplePreview.textContent = fiance && fiancee ? `${fiance} & ${fiancee}` : 'Pierre & Marie';
        }
        
        // Mettre à jour la date
        if (dateInput) {
            const date = new Date(dateInput + 'T12:00:00');
            if (!isNaN(date.getTime())) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = date.toLocaleDateString('fr-FR', options);
                document.getElementById('date-preview-text').textContent = 
                    formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
            }
        } else {
            document.getElementById('date-preview-text').textContent = 'Sélectionnez une date';
        }
        
        // Mettre à jour le budget
        const budgetPreview = document.getElementById('budget-preview-text');
        if (budgetPreview) {
            if (budget && budget > 0) {
                budgetPreview.textContent = `Budget: ${Number(budget).toLocaleString('fr-FR')} FCFA`;
            } else {
                budgetPreview.textContent = '';
            }
        }
    }

    // ============================================================
    // GESTION DU MODAL
    // ============================================================
    function openWeddingModal() {
        const modal = document.getElementById('wedding-modal');
        if (modal) {
            modal.style.display = 'flex';
            updatePreview();
        }
    }

    function closeWeddingModal() {
        const modal = document.getElementById('wedding-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // ============================================================
    // FERMETURE DU MODAL AVEC ÉCHAP
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeWeddingModal();
        }
    });

    // ============================================================
    // EXPOSITION DES FONCTIONS GLOBALES
    // ============================================================
    window.openWeddingModal = openWeddingModal;
    window.closeWeddingModal = closeWeddingModal;
    window.updatePreview = updatePreview;
    </script>
</body>
</html>