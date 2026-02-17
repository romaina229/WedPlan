<?php
    /**
     * Tableau de bord pour les parrains/conseillers
     * Vue en lecture seule avec possibilité de commenter
     * 
     * @package WeddingManagement
     * @subpackage SponsorSystem
     */

    require_once __DIR__ . '/config_da.php';
    requireSponsorLogin();

    $db = DatabaseConnection::getInstance()->getConnection();
    $sponsorId = getSponsorId();
    $weddingId = getSponsorWeddingId();

    // Vérifier que l'ID du mariage est valide
    if (!$weddingId) {
        logoutSponsor();
        header("Location: sponsor_login.php?error=session_invalide");
        exit();
    }

    // Enregistrer l'activité de consultation
    logSponsorActivity($sponsorId, $weddingId, 'consultation', 'Consultation du tableau de bord');

    // Récupérer les informations du parrain
    $sponsorInfo = getSponsorInfo($sponsorId);
    if (!$sponsorInfo) {
        logoutSponsor();
        header("Location: sponsor_login.php?error=compte_inactif");
        exit();
    }

    // Traitement de l'ajout de commentaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && sponsorCanComment()) {
        $commentaire = trim($_POST['commentaire'] ?? '');
        $typeCommentaire = $_POST['type_commentaire'] ?? 'general';
        $depenseId = !empty($_POST['depense_id']) ? intval($_POST['depense_id']) : null;
        
        if (!empty($commentaire)) {
            try {
                $sql = "INSERT INTO sponsor_comments 
                (wedding_dates_id, sponsor_id, expense_id, commentaire, type_commentaire) 
                VALUES (:wedding_dates_id, :sponsor_id, :expense_id, :commentaire, :type_commentaire)";

                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':wedding_dates_id' => $weddingId,
                    ':sponsor_id' => $sponsorId,
                    ':expense_id' => $depenseId,
                    ':commentaire' => $commentaire,
                    ':type_commentaire' => $typeCommentaire
                ]);
                
                logSponsorActivity($sponsorId, $weddingId, 'commentaire', 'Nouveau commentaire ajouté');
                
                $success = "Commentaire ajouté avec succès.";
            } catch (PDOException $e) {
                error_log("Erreur ajout commentaire: " . $e->getMessage());
                $error = "Erreur lors de l'ajout du commentaire.";
            }
        }
    }

    // Récupérer les informations du mariage
    $sql = "SELECT * FROM wedding_dates WHERE id = :wedding_dates_id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':wedding_dates_id' => $weddingId]);
    $wedding = $stmt->fetch();

    if (!$wedding) {
        logoutSponsor();
        header("Location: sponsor_login.php?error=mariage_introuvable");
        exit();
    }

    // Initialiser les statistiques
$stats = [
    'budget_total' => $wedding['budget_total'] ?? 0,
    'total_depense' => 0,
    'budget_restant' => $wedding['budget_total'] ?? 0,
    'nombre_depenses' => 0,
    'nombre_payes' => 0,
    'pourcentage_paye' => 0,
    'total_paye' => 0,
    'total_non_paye' => 0
];

// REQUÊTE CORRIGÉE : Récupérer le user_id depuis wedding_dates, puis les dépenses
$sql = "SELECT 
            COUNT(e.id) as nb,
            SUM(CASE WHEN e.paid = 1 THEN 1 ELSE 0 END) as nb_payes,
            COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0) as total_depense,
            COALESCE(SUM(CASE WHEN e.paid = 1 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END), 0) as total_paye,
            COALESCE(SUM(CASE WHEN e.paid = 0 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END), 0) as total_non_paye
        FROM expenses e
        WHERE e.user_id = :user_id";

$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $wedding['user_id']]);
$calc = $stmt->fetch();

if ($calc) {
    $stats['nombre_depenses'] = (int)$calc['nb'];
    $stats['nombre_payes'] = (int)$calc['nb_payes'];
    $stats['total_depense'] = (float)$calc['total_depense'];
    $stats['total_paye'] = (float)$calc['total_paye'];
    $stats['total_non_paye'] = (float)$calc['total_non_paye'];
    $stats['budget_restant'] = $stats['total_depense'] - $stats['total_paye'];
    
    // CALCUL CORRECT du pourcentage
    if ($stats['total_depense'] > 0) {
        $stats['pourcentage_paye'] = round(($stats['total_paye'] / $stats['total_depense']) * 100, 2);
    } else {
        $stats['pourcentage_paye'] = 0;
    }
}
    
    // Récupérer les dépenses
    $sql = "SELECT 
                e.*,
                c.name as category_name,
                c.color as category_color,
                c.icon as category_icon,
                (e.quantity * e.unit_price * e.frequency) as montant_total
            FROM expenses e
            INNER JOIN categories c ON e.category_id = c.id
            INNER JOIN wedding_dates wd ON e.user_id = wd.user_id
            WHERE wd.id = :wedding_dates_id
            ORDER BY e.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':wedding_dates_id' => $weddingId]);
    $depenses = $stmt->fetchAll();

    // Récupérer les commentaires
    $sql = "SELECT sc.*, ws.sponsor_nom_complet, ws.role 
            FROM sponsor_comments sc
            INNER JOIN wedding_sponsors ws ON sc.sponsor_id = ws.id
            WHERE sc.wedding_dates_id = :wedding_dates_id
            ORDER BY sc.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':wedding_dates_id' => $weddingId]);
    $commentaires = $stmt->fetchAll();

    // Récupérer la liste des dépenses pour le sélecteur dans les commentaires
    $sql = "SELECT e.id, e.name FROM expenses e
            INNER JOIN wedding_dates wd ON e.user_id = wd.user_id
            WHERE wd.id = :wedding_dates_id
            ORDER BY e.name"; // Il est aussi plus sûr de préciser e.name
    $stmt = $db->prepare($sql);
    $stmt->execute([':wedding_dates_id' => $weddingId]);
    $listeDepenses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Parrain - <?php echo escapeHtml($wedding['fiance_nom_complet'] . ' & ' . $wedding['fiancee_nom_complet']); ?></title>
    <link rel="shortcut icon" href="assets/images/wedding.jpg" type="image/jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboardsponsor.css">
    
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-users"></i> Espace Parrain/Conseiller</h1>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo escapeHtml(getSponsorName()); ?></span>
                <span class="badge" style="background: <?php echo getSponsorRole() === 'parrain' ? '#8b4f8d' : '#3498db'; ?>; color: white;">
                    <?php echo ucfirst(getSponsorRole()); ?>
                </span>
                <a href="sponsor_logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Bannière des fiancés -->
        <div class="wedding-info-banner">
            <h2><i class="fas fa-heart" style="color: #8b4f8d;"></i> <?php echo escapeHtml($wedding['fiance_nom_complet'] . ' & ' . $wedding['fiancee_nom_complet']); ?></h2>
            <p class="date"><i class="far fa-calendar-alt"></i> Mariage prévu le <?php echo formatDate($wedding['wedding_date'], 'd F Y'); ?></p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo escapeHtml($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo escapeHtml($success); ?></div>
        <?php endif; ?>
        
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i> <strong>Mode lecture seule :</strong> Vous pouvez consulter les informations et ajouter des commentaires, 
            mais vous ne pouvez pas modifier ou supprimer les dépenses.
        </div>
        
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card budget-total">
                <div class="label"><i class="fas fa-wallet"></i> Budget Total</div>
                <div class="value"><?php echo formatMontant($stats['total_depense'] ?? 0); ?></div>
            </div>
            <div class="stat-card depense">
                <div class="label"><i class="fas fa-shopping-cart"></i> Total Dépensé</div>
                <div class="value"><?php echo formatMontant($stats['total_paye'] ?? 0); ?></div>
            </div>
            <div class="stat-card restant">
                <div class="label"><i class="fas fa-piggy-bank"></i> Budget Restant</div>
                <div class="value"><?php echo formatMontant(max(0, $stats['total_non_paye'] ?? 0)); ?></div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="fas fa-chart-line"></i> Progression</div>
                <div class="value"><?php echo number_format($stats['nombre_payes'] ?? 0, 0); ?>/<?php echo number_format($stats['nombre_depenses'] ?? 0, 0); ?> payé</div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo min($stats['pourcentage_paye'] ?? 0, 100); ?>%"></div>
                </div>
                <div style="text-align: center; margin-top: 10px; font-size: 14px; color: #666;">
                     <?php echo number_format($stats['pourcentage_paye'], 2); ?>% payé
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="content-grid">
            <!-- Liste des dépenses -->
            <div>
                <div class="card">
                    <h3>
                        <span><i class="fas fa-receipt"></i> Liste des dépenses</span>
                        <span class="readonly-badge"><i class="fas fa-eye"></i> Lecture seule</span>
                    </h3>
                    
                    <?php if (count($depenses) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Catégorie</th>
                                        <th>Description</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($depenses as $depense): ?>
                                        <tr>
                                            <td><?php echo formatDate($depense['payment_date'] ?? $depense['created_at']); ?></td>
                                            <td>
                                                <span style="color: <?php echo escapeHtml($depense['category_color'] ?? '#666'); ?>">
                                                    <i class="<?php echo escapeHtml($depense['category_icon'] ?? 'fas fa-folder'); ?>"></i>
                                                    <?php echo escapeHtml($depense['category_name'] ?? 'Non catégorisé'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo escapeHtml($depense['name']); ?>
                                                <?php if (!empty($depense['notes'])): ?>
                                                    <br><small style="color: #666;"><i class="fas fa-sticky-note"></i> <?php echo escapeHtml($depense['notes']); ?></small>
                                                <?php endif; ?>
                                                <br><small style="color: #999;">Qté: <?php echo $depense['quantity']; ?> x <?php echo formatMontant($depense['unit_price']); ?></small>
                                            </td>
                                            <td><strong><?php echo formatMontant($depense['montant_total']); ?></strong></td>
                                            <td>
                                                <?php if ($depense['paid'] == 1): ?>
                                                    <span class="badge badge-payee"><i class="fas fa-check-circle"></i> Payée</span>
                                                <?php else: ?>
                                                    <span class="badge badge-non-payee"><i class="fas fa-hourglass-half"></i> Non payée</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-receipt" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                            <p>Aucune dépense enregistrée pour le moment</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Commentaires -->
            <div>
                <div class="card">
                    <h3><i class="fas fa-comments"></i> Commentaires</h3>
                    
                    <?php if (sponsorCanComment()): ?>
                        <div class="comment-form">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="type_commentaire"><i class="fas fa-tag"></i> Type de commentaire</label>
                                    <select id="type_commentaire" name="type_commentaire">
                                        <option value="general">📝 Général</option>
                                        <option value="suggestion">💡 Suggestion</option>
                                    </select>
                                </div>
                                
                                <?php if (count($listeDepenses) > 0): ?>
                                <div class="form-group">
                                    <label for="depense_id"><i class="fas fa-link"></i> Lier à une dépense (optionnel)</label>
                                    <select id="depense_id" name="depense_id">
                                        <option value="">-- Commentaire général --</option>
                                        <?php foreach ($listeDepenses as $dep): ?>
                                            <option value="<?php echo $dep['id']; ?>"><?php echo escapeHtml($dep['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="commentaire"><i class="fas fa-pencil-alt"></i> Votre commentaire</label>
                                    <textarea id="commentaire" name="commentaire" placeholder="Écrivez votre commentaire ici..." required></textarea>
                                </div>
                                
                                <button type="submit" name="add_comment" class="btn-submit">
                                    <i class="fas fa-paper-plane"></i> Ajouter le commentaire
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px; color: #4a5568;"><i class="fas fa-history"></i> Historique des commentaires</h4>
                        
                        <?php if (count($commentaires) > 0): ?>
                            <?php foreach ($commentaires as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <span class="comment-author">
                                            <i class="fas fa-user-circle"></i> <?php echo escapeHtml($comment['sponsor_nom_complet']); ?>
                                            <small style="color: #999; margin-left: 5px;">(<?php echo ucfirst($comment['role']); ?>)</small>
                                        </span>
                                        <span style="color: #999; font-size: 12px;">
                                            <i class="far fa-clock"></i> <?php echo formatDate($comment['created_at'], 'd/m/Y H:i'); ?>
                                        </span>
                                    </div>
                                    <div class="comment-body">
                                        <?php echo nl2br(escapeHtml($comment['commentaire'])); ?>
                                    </div>
                                    <?php if (!empty($comment['type_commentaire']) && $comment['type_commentaire'] !== 'general'): ?>
                                        <div style="margin-top: 8px;">
                                            <span class="badge" style="background: <?php echo $comment['type_commentaire'] === 'suggestion' ? '#f39c12' : '#3498db'; ?>; color: white;">
                                                <?php if ($comment['type_commentaire'] === 'suggestion'): ?>
                                                    <i class="fas fa-lightbulb"></i> Suggestion
                                                <?php else: ?>
                                                    <i class="fas fa-tag"></i> <?php echo ucfirst($comment['type_commentaire']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comments" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                                <p>Aucun commentaire pour le moment</p>
                                <p style="font-size: 14px; color: #999;">Soyez le premier à commenter !</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>