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
    <title>Paramètres - Budget Mariage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/settings.css">
</head>
<body>
    <div class="settings-container">
        <a href="profile.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au profil
        </a>

        <div class="settings-header">
            <h1><i class="fas fa-cog"></i> Paramètres</h1>
            <p>Personnalisez votre expérience</p>
        </div>

        <div class="settings-tabs">
            <button class="settings-tab active" onclick="switchSettingsTab('general')">
                <i class="fas fa-user-cog"></i> Général
            </button>
            <button class="settings-tab" onclick="switchSettingsTab('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </button>
            <button class="settings-tab" onclick="switchSettingsTab('security')">
                <i class="fas fa-shield-alt"></i> Sécurité
            </button>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <button class="settings-tab" onclick="switchSettingsTab('admin')">
                <i class="fas fa-shield-alt"></i> Administration
            </button>
            <?php endif; ?>
        </div>

        <div class="settings-content">
            <!-- Onglet Général -->
            <div id="general-tab" class="settings-section active">
                <div class="form-section">
                    <h3>Informations personnelles</h3>
                    <form id="profile-form">
                        <div class="form-group">
                            <label>Nom complet</label>
                            <input type="text" id="fullname" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary" id="save-profile-btn">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <div class="form-section">
                    <h3>Préférences d'affichage</h3>
                    <div class="toggle-switch">
                        <label class="switch">
                            <input type="checkbox" id="dark-mode" checked>
                            <span class="slider"></span>
                        </label>
                        <span>Mode sombre</span>
                    </div>
                    <div class="toggle-switch">
                        <label class="switch">
                            <input type="checkbox" id="compact-view">
                            <span class="slider"></span>
                        </label>
                        <span>Vue compacte des tableaux</span>
                    </div>
                </div>
            </div>

            <!-- Onglet Notifications -->
            <div id="notifications-tab" class="settings-section">
                <div class="form-section">
                    <h3>Préférences de notifications</h3>
                    <ul class="notification-list">
                        <li>
                            <span>Notifications par email</span>
                            <label class="switch">
                                <input type="checkbox" id="email-notifications" checked>
                                <span class="slider"></span>
                            </label>
                        </li>
                        <li>
                            <span>Rappels de paiement</span>
                            <label class="switch">
                                <input type="checkbox" id="payment-reminders" checked>
                                <span class="slider"></span>
                            </label>
                        </li>
                        <li>
                            <span>Mises à jour de l'application</span>
                            <label class="switch">
                                <input type="checkbox" id="app-updates" checked>
                                <span class="slider"></span>
                            </label>
                        </li>
                        <li>
                            <span>Notifications push</span>
                            <label class="switch">
                                <input type="checkbox" id="push-notifications">
                                <span class="slider"></span>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Onglet Sécurité -->
            <div id="security-tab" class="settings-section">
                <div class="form-section">
                    <h3>Changer le mot de passe</h3>
                    <form id="password-form">
                        <div class="form-group">
                            <label>Ancien mot de passe</label>
                            <input type="password" id="old-password" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" id="new-password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirmer le nouveau mot de passe</label>
                            <input type="password" id="confirm-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary" id="save-password-btn">
                            <i class="fas fa-key"></i> Changer le mot de passe
                        </button>
                    </form>
                </div>

                <div class="form-section">
                    <h3>Sessions actives</h3>
                    <div class="info-group">
                        <div class="info-label">Session actuelle</div>
                        <div class="info-value">Démarrée le <?php echo date('d/m/Y à H:i'); ?></div>
                    </div>
                    <button class="btn btn-danger" onclick="logoutAllSessions()">
                        <i class="fas fa-sign-out-alt"></i> Déconnecter toutes les sessions
                    </button>
                </div>
            </div>

            <!-- Onglet Administration -->
            <?php if ($currentUser['role'] === 'admin'): ?>
            <div id="admin-tab" class="settings-section">
                <div class="form-section">
                    <h3><i class="fas fa-shield-alt"></i> Administration système</h3>
                    <p>En tant qu'administrateur, vous avez accès à toutes les fonctionnalités de gestion de la plateforme.</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                        <a href="admin_users.php" class="btn btn-primary">
                            <i class="fas fa-users"></i> Gérer les utilisateurs
                        </a>
                        <a href="admin_backup.php" class="btn btn-warning">
                            <i class="fas fa-database"></i> Sauvegarde base de données
                        </a>
                        <a href="admin_logs.php" class="btn btn-danger">
                            <i class="fas fa-clipboard-list"></i> Voir les logs
                        </a>
                    </div>

                    <div class="form-section" style="margin-top: 30px;">
                        <h4>Configuration système</h4>
                        <div class="toggle-switch">
                            <label class="switch">
                                <input type="checkbox" id="maintenance-mode">
                                <span class="slider"></span>
                            </label>
                            <span>Mode maintenance</span>
                        </div>
                        <div class="toggle-switch">
                            <label class="switch">
                                <input type="checkbox" id="new-registrations" checked>
                                <span class="slider"></span>
                            </label>
                            <span>Autoriser les nouvelles inscriptions</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function switchSettingsTab(tabName) {
        // Masquer tous les contenus
        document.querySelectorAll('.settings-section').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // DÃ©sactiver tous les boutons
        document.querySelectorAll('.settings-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activer l'onglet et le bouton correspondant
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }

    // Gestion du formulaire de profil
    document.getElementById('profile-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('save-profile-btn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        
        // Ici, vous implémenteriez l'appel API pour sauvegarder
        setTimeout(() => {
            alert('Modifications enregistrées avec succès !');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }, 1000);
    });

    // Gestion du formulaire de mot de passe
    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const oldPassword = document.getElementById('old-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;
        
        if (newPassword !== confirmPassword) {
            alert('Les mots de passe ne correspondent pas');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Le mot de passe doit contenir au moins 6 caractères');
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
                document.getElementById('password-form').reset();
            }
        })
        .catch(error => {
            alert('Erreur lors du changement de mot de passe');
        });
    });

    function logoutAllSessions() {
        if (confirm('Êtes-vous sûr de vouloir déconnecter toutes les sessions ?')) {
            fetch('<?= APP_URL ?>/api/auth_api.php?action=logout_all')
            .then(response => response.json())
            .then(result => {
                alert(result.message);
                if (result.success) {
                    window.location.href = 'login.php';
                }
            });
        }
    }
    </script>
</body>
</html>