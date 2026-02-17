<?php
/**
 * wedding_date.php — Gestion de la date du mariage
 * Budget Mariage PJPM v2.1
 */
declare(strict_types=1);

// Définir ROOT_PATH avant tout
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}

require_once ROOT_PATH . '../config.php';
require_once ROOT_PATH . '../AuthManager.php';

// DÉMARRER LA SESSION CORRECTEMENT
AuthManager::startSession();

// VÉRIFICATION DE CONNEXION - SIMPLE ET EFFICACE
if (!AuthManager::isLoggedIn()) {
    // Rediriger vers la page de connexion
    header('Location: ' . APP_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Récupérer l'utilisateur connecté
$currentUser = AuthManager::getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// Inclure ExpenseManager pour accéder aux fonctions
require_once ROOT_PATH . '../ExpenseManager.php';
$expenseManager = new ExpenseManager();

// Récupérer la date actuelle si elle existe
$weddingDate = $expenseManager->getWeddingDate($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Budget Mariage</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .profile-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px var(--shadow);
        }

        .profile-card h2 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-left: 10px;
        }

        .role-admin {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .role-user {
            background: var(--text-secondary);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--bg-main);
            border-radius: 10px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: var(--bg-card);
            color: var(--primary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px var(--shadow);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--shadow);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>

        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h1><?php echo htmlspecialchars($currentUser['full_name'] ?: $currentUser['username']); ?></h1>
            <p>Membre depuis <?php echo date('d/m/Y', strtotime($currentUser['created_at'] ?? 'now')); ?></p>
            <span class="role-badge <?php echo $currentUser['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                <?php echo $currentUser['role'] === 'admin' ? 'Administrateur' : 'Utilisateur'; ?>
            </span>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <h2><i class="fas fa-info-circle"></i> Informations Personnelles</h2>
                <div class="info-group">
                    <div class="info-label">Nom d'utilisateur</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['username']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Adresse email</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Nom complet</div>
                    <div class="info-value"><?php echo htmlspecialchars($currentUser['full_name'] ?: 'Non spÃ©cifiÃ©'); ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Rôle</div>
                    <div class="info-value"><?php echo $currentUser['role'] === 'admin' ? 'Administrateur' : 'Utilisateur standard'; ?></div>
                </div>
                <div class="info-group">
                    <div class="info-label">Dernière connexion</div>
                    <div class="info-value">
                        <?php 
                        if (!empty($currentUser['last_login']) && $currentUser['last_login'] !== '0000-00-00 00:00:00') {
                            echo date('d/m/Y H:i', strtotime($currentUser['last_login']));
                        } else {
                            echo 'Jamais';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <h2><i class="fas fa-chart-line"></i> Statistiques</h2>
                <?php
                require_once __DIR__ . '/../ExpenseManager.php';
                $manager = new ExpenseManager();
                $stats = $manager->getStats($currentUser['id']);
                $grandTotal = $manager->getGrandTotal($currentUser['id']);
                $paidTotal = $manager->getPaidTotal($currentUser['id']);
                ?>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total_items']; ?></div>
                        <div class="stat-label">Dépenses totales</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: var(--success);"><?php echo $stats['paid_items']; ?></div>
                        <div class="stat-label">Dépenses payées</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: var(--warning);"><?php echo $stats['unpaid_items']; ?></div>
                        <div class="stat-label">Dépenses en attente</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo formatCurrency($grandTotal); ?></div>
                        <div class="stat-label">Budget total</div>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <h3><i class="fas fa-cog"></i> Actions</h3>
                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                        <a href="settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <button onclick="changePassword()" class="btn btn-warning">
                            <i class="fas fa-key"></i> Changer mot de passe
                        </button>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="admin.php" class="btn btn-danger">
                            <i class="fas fa-shield-alt"></i> Administration
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function changePassword() {
        const oldPassword = prompt('Ancien mot de passe :');
        if (!oldPassword) return;
        
        const newPassword = prompt('Nouveau mot de passe (minimum 6 caractères) :');
        if (!newPassword || newPassword.length < 6) {
            alert('Le nouveau mot de passe doit contenir au moins 6 caractères');
            return;
        }
        
        const confirmPassword = prompt('Confirmer le nouveau mot de passe :');
        if (newPassword !== confirmPassword) {
            alert('Les mots de passe ne correspondent pas');
            return;
        }
        
        fetch('<?= APP_URL ?>/api/auth_api.php?action=change_password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                old_password: oldPassword,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                window.location.reload();
            }
        })
        .catch(error => {
            alert('Erreur lors du changement de mot de passe');
        });
    }
    </script>
</body>
</html>