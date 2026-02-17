<?php
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
$manager = new ExpenseManager();
$auth = new AuthManager();

// Récupérer la date actuelle si elle existe
$weddingDate = $expenseManager->getWeddingDate($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration <?php echo htmlspecialchars($currentUser['name'] ?? 'Utilisateur'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Roboto:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 90px auto 20px;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #5d2f5f 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .admin-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow);
            text-align: center;
        }

        .admin-stat-card.warning {
            border-left: 4px solid var(--warning);
        }

        .admin-stat-card.success {
            border-left: 4px solid var(--success);
        }

        .admin-stat-card.danger {
            border-left: 4px solid var(--danger);
        }

        .admin-stat-card.primary {
            border-left: 4px solid var(--primary);
        }

        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: var(--bg-card);
            padding: 10px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow);
            flex-wrap: wrap;
        }

        .admin-tab {
            flex: 1;
            min-width: 150px;
            padding: 15px 25px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .admin-tab:hover {
            background: var(--bg-main);
            color: var(--primary);
        }

        .admin-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .admin-content {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px var(--shadow);
            margin-bottom: 30px;
        }

        .admin-section {
            display: none;
        }

        .admin-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .user-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
    <div class="admin-container">
        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour au tableau de bord
        </a>

        <div class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Administration</h1>
            <p>Gestion complète de la plateforme Budget Mariage</p>
        </div>

        <div class="admin-stats">
            <div class="admin-stat-card primary">
                <div class="stat-value">
                    <?php 
                    $users = $auth->getAllUsers();
                    echo count($users);
                    ?>
                </div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="admin-stat-card success">
                <div class="stat-value">
                    <?php
                    $totalExpenses = 0;
                    foreach ($users as $user) {
                        $totalExpenses += $manager->getStats($user['id'])['total_items'];
                    }
                    echo $totalExpenses;
                    ?>
                </div>
                <div class="stat-label">Dépenses totales</div>
            </div>
            <div class="admin-stat-card warning">
                <div class="stat-value">
                    <?php
                    $totalBudget = 0;
                    foreach ($users as $user) {
                        $totalBudget += $manager->getGrandTotal($user['id']);
                    }
                    echo formatCurrency($totalBudget);
                    ?>
                </div>
                <div class="stat-label">Budget total</div>
            </div>
            <div class="admin-stat-card danger">
                <div class="stat-value">
                    <?php
                    $admins = array_filter($users, function($user) {
                        return $user['role'] === 'admin';
                    });
                    echo count($admins);
                    ?>
                </div>
                <div class="stat-label">Administrateurs</div>
            </div>
        </div>

        <div class="admin-tabs">
            <button class="admin-tab active" onclick="switchAdminTab('users')">
                <i class="fas fa-users"></i> Utilisateurs
            </button>
            <button class="admin-tab" onclick="switchAdminTab('categories')">
                <i class="fas fa-folder"></i> Catégories
            </button>
            <button class="admin-tab" onclick="switchAdminTab('backup')">
                <i class="fas fa-database"></i> Sauvegarde
            </button>
            <button class="admin-tab" onclick="switchAdminTab('settings')">
                <i class="fas fa-cog"></i> Configuration
            </button>
        </div>

        <div class="admin-content">
            <!-- Onglet Utilisateurs -->
            <div id="users-tab" class="admin-section active">
                <h2><i class="fas fa-users"></i> Gestion des utilisateurs</h2>
                <div class="user-actions">
                    <button class="btn btn-primary" onclick="addUser()">
                        <i class="fas fa-user-plus"></i> Ajouter un utilisateur
                    </button>
                    <button class="btn btn-warning" onclick="exportUsers()">
                        <i class="fas fa-file-export"></i> Exporter les utilisateurs
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Nom complet</th>
                                <th>RÃ´le</th>
                                <th>Inscrit le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-paid' : 'badge-unpaid'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] != $currentUser['id']): ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Onglet Catégories -->
            <div id="categories-tab" class="admin-section">
                <h2><i class="fas fa-folder"></i> Gestion des catégories</h2>
                <p>Les catégories sont partagées par tous les utilisateurs. Modifiez-les avec prudence.</p>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Couleur</th>
                                <th>Icône</th>
                                <th>Ordre</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $categories = $manager->getAllCategories();
                            foreach ($categories as $cat): 
                            ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td>
                                    <div style="width: 20px; height: 20px; background: <?php echo $cat['color']; ?>; border-radius: 3px;"></div>
                                </td>
                                <td><i class="<?php echo $cat['icon']; ?>"></i> <?php echo $cat['icon']; ?></td>
                                <td><?php echo $cat['display_order']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $cat['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Onglet Sauvegarde -->
            <div id="backup-tab" class="admin-section">
                <h2><i class="fas fa-database"></i> Sauvegarde des données</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
                    <div class="admin-stat-card">
                        <h3>Sauvegarde manuelle</h3>
                        <p>Créez une sauvegarde complète de la base de données</p>
                        <button class="btn btn-primary" onclick="createBackup()">
                            <i class="fas fa-download"></i> Créer une sauvegarde
                        </button>
                    </div>
                    <div class="admin-stat-card">
                        <h3>Restauration</h3>
                        <p>Restaurer la base de données depuis un fichier de sauvegarde</p>
                        <input type="file" id="backup-file" accept=".sql,.json" style="margin-bottom: 10px;">
                        <button class="btn btn-warning" onclick="restoreBackup()">
                            <i class="fas fa-upload"></i> Restaurer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Onglet Configuration -->
            <div id="settings-tab" class="admin-section">
                <h2><i class="fas fa-cog"></i> Configuration système</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nom de l'application</label>
                        <input type="text" value="Budget Mariage PJPM">
                    </div>
                    <div class="form-group">
                        <label>Devise</label>
                        <select>
                            <option value="FCFA" selected>FCFA (Franc CFA)</option>
                            <option value="EUR">Euro (€)</option>
                            <option value="USD">Dollar ($)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mode maintenance</label>
                        <div class="toggle-switch">
                            <label class="switch">
                                <input type="checkbox" id="maintenance-mode-admin">
                                <span class="slider"></span>
                            </label>
                            <span>Activer le mode maintenance</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nouvelles inscriptions</label>
                        <div class="toggle-switch">
                            <label class="switch">
                                <input type="checkbox" id="new-registrations-admin" checked>
                                <span class="slider"></span>
                            </label>
                            <span>Autoriser les nouvelles inscriptions</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Limite de dépenses par utilisateur</label>
                        <input type="number" value="100" min="10" max="1000">
                    </div>
                    <div class="form-group">
                        <label>Message de bienvenue</label>
                        <textarea rows="3">Bienvenue sur la plateforme de gestion de budget de mariage. Planifiez votre mariage en toute sérénité !</textarea>
                    </div>
                </div>
                <button class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-save"></i> Enregistrer les paramètres
                </button>
            </div>
        </div>
    </div>
    <script>
    // FIX: URLs absolues
    const API_ADMIN = '<?= APP_URL ?>/api/admin_api.php';
    function switchAdminTab(tabName) {
        // Masquer tous les contenus
        document.querySelectorAll('.admin-section').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons
        document.querySelectorAll('.admin-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }

    function addUser() {
        const username = prompt('Nom d\'utilisateur :');
        if (!username) return;
        
        const email = prompt('Email :');
        if (!email) return;
        
        const password = prompt('Mot de passe (minimum 6 caractères) :');
        if (!password || password.length < 6) {
            alert('Le mot de passe doit contenir au moins 6 caractères');
            return;
        }
        
        const role = confirm('Ce compte sera-t-il administrateur ?') ? 'admin' : 'user';
        
        fetch(API_ADMIN + '?action=add_user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                email: email,
                password: password,
                role: role
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
            alert('Erreur lors de l\'ajout de l\'utilisateur');
        });
    }

    function editUser(userId) {
        const newRole = prompt('Nouveau rôle (admin/user) :');
        if (!newRole || !['admin', 'user'].includes(newRole)) {
            alert('Rôle invalide');
            return;
        }
        
        fetch(API_ADMIN + '?action=edit_user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                role: newRole
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
            alert('Erreur lors de la modification de l\'utilisateur');
        });
    }

    function deleteUser(userId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Toutes ses données seront perdues.')) {
            return;
        }
        
        fetch(API_ADMIN + '?action=delete_user', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId
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
            alert('Erreur lors de la suppression de l\'utilisateur');
        });
    }

    function createBackup() {
        if (!confirm('Créer une sauvegarde de la base de données ?')) {
            return;
        }
        
        fetch(API_ADMIN + '?action=create_backup')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Créer un lien de téléchargement
                const link = document.createElement('a');
                link.href = result.file_url;
                link.download = result.file_name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                alert('Sauvegarde créée avec succès !');
            } else {
                alert('Erreur : ' + result.message);
            }
        })
        .catch(error => {
            alert('Erreur lors de la création de la sauvegarde');
        });
    }
   //Éditer une catégorie avec une modal simple
    function editCategory(categoryId) {
        // Vous pouvez créer une modal HTML ici
        // Pour l'instant, version simple
        const row = event.target.closest('tr');
        const currentName = row.cells[1].textContent.trim();
        const currentColor = row.cells[2].querySelector('div')?.style.background || '#8b4f8d';
        const currentIcon = row.cells[3].querySelector('i')?.className || 'fas fa-folder';
        const currentOrder = row.cells[4].textContent.trim();
        
        const newName = prompt('Modifier le nom de la catégorie :', currentName);
        if (newName && newName !== currentName) {
            updateCategory(categoryId, { name: newName });
        }
    }

    // Mettre à jour une catégorie
    function updateCategory(categoryId, data) {
        fetch(API_ADMIN + '?action=update_category', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: categoryId,
                ...data
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(' Catégorie mise à jour avec succès');
                window.location.reload();
            } else {
                alert('❌ ' + (result.message || 'Erreur lors de la mise à jour'));
            }
        })
        .catch(error => {
            alert('❌ Erreur de connexion');
        });
    }
    function exportUsers() {
        fetch(API_ADMIN + '?action=export_users')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Créer un lien de téléchargement
                const link = document.createElement('a');
                link.href = result.file_url;
                link.download = result.file_name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });
    }
    </script>
</body>
</html>