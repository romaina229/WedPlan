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
$users = $auth->getAllUsers();

// Récupérer la date actuelle si elle existe
$weddingDate = $expenseManager->getWeddingDate($userId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-users-container {
            max-width: 1400px;
            margin: 90px auto 20px;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #5d2f5f 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .admin-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .user-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .user-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .user-stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow);
            margin-bottom: 20px;
        }

        .user-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .user-stat-item {
            text-align: center;
            padding: 15px;
            background: var(--bg-main);
            border-radius: 8px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>

    <div class="admin-users-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>
            <p>Gérez les comptes utilisateurs et leurs permissions</p>
        </div>

        <div class="user-actions">
            <button class="btn btn-primary" onclick="addUserModal()">
                <i class="fas fa-user-plus"></i> Ajouter un utilisateur
            </button>
            <button class="btn btn-warning" onclick="exportUsers()">
                <i class="fas fa-file-export"></i> Exporter au format CSV
            </button>
            <button class="btn btn-danger" onclick="deleteInactiveUsers()">
                <i class="fas fa-user-slash"></i> Supprimer les inactifs
            </button>
        </div>

        <div class="user-filters">
            <div class="filter-group">
                <label>Rechercher</label>
                <input type="text" id="user-search" placeholder="Nom, email..." onkeyup="filterUsers()">
            </div>
            <div class="filter-group">
                <label>Rôle</label>
                <select id="role-filter" onchange="filterUsers()">
                    <option value="">Tous les rôles</option>
                    <option value="admin">Administrateurs</option>
                    <option value="user">Utilisateurs</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Statut</label>
                <select id="status-filter" onchange="filterUsers()">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select>
            </div>
        </div>

        <div class="user-stat-card">
            <div class="user-stat-grid">
                <?php
                $adminCount = 0;
                $userCount = 0;
                $activeCount = 0;
                $totalExpenses = 0;
                $totalBudget = 0;

                foreach ($users as $user) {
                    if ($user['role'] === 'admin') $adminCount++;
                    else $userCount++;

                    if (!empty($user['last_login']) && strtotime($user['last_login']) > strtotime('-30 days')) {
                        $activeCount++;
                    }

                    $userStats = $manager->getStats($user['id']);
                    $totalExpenses += $userStats['total_items'];
                    $totalBudget += $manager->getGrandTotal($user['id']);
                }
                ?>
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Utilisateurs totaux</div>
                </div>
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo $adminCount; ?></div>
                    <div class="stat-label">Administrateurs</div>
                </div>
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo $userCount; ?></div>
                    <div class="stat-label">Utilisateurs</div>
                </div>
                <div class="user-stat-item">
                    <div class="stat-number"><?php echo $activeCount; ?></div>
                    <div class="stat-label">Utilisateurs actifs</div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Nom complet</th>
                            <th>Rôle</th>
                            <th>Inscrit le</th>
                            <th>Dernière connexion</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $isActive = !empty($user['last_login']) && strtotime($user['last_login']) > strtotime('-30 days');
                            $userExpenses = $manager->getStats($user['id']);
                        ?>
                        <tr class="user-row" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $isActive ? 'active' : 'inactive'; ?>">
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['id'] == $currentUser['id']): ?>
                                <span class="badge badge-paid" style="margin-left: 5px;">Vous</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-paid' : 'badge-unpaid'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if (!empty($user['last_login']) && $user['last_login'] !== '0000-00-00 00:00:00'): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">Jamais</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $isActive ? 'badge-paid' : 'badge-unpaid'; ?>">
                                    <?php echo $isActive ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="viewUserStats(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-chart-bar"></i>
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
    </div>

    <!-- Modal Ajout/Modification Utilisateur -->
    <div id="user-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="user-modal-title">Nouvel Utilisateur</h2>
                <button class="btn btn-sm btn-danger" onclick="closeUserModal()">✕</button>
            </div>
            <form id="user-form" onsubmit="saveUser(event)">
                <input type="hidden" id="user-id">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nom d'utilisateur *</label>
                        <input type="text" id="username" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" id="fullname">
                    </div>
                    <div class="form-group">
                        <label>Mot de passe <span id="password-required">*</span></label>
                        <input type="password" id="password">
                        <small id="password-help">Laissez vide pour ne pas modifier</small>
                    </div>
                    <div class="form-group">
                        <label>Confirmer le mot de passe</label>
                        <input type="password" id="confirm-password">
                    </div>
                    <div class="form-group">
                        <label>Rôle*</label>
                        <select id="role" required>
                            <option value="user">Utilisateur</option>
                            <option value="admin">Administrateur</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" id="save-user-btn">
                        Enregistrer
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeUserModal()">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
    const API_ADMIN = '<?= APP_URL ?>/api/admin_api.php';
    let editingUserId = null;

    function addUserModal() {
        editingUserId = null;
        document.getElementById('user-modal-title').textContent = 'Nouvel Utilisateur';
        document.getElementById('user-id').value = '';
        document.getElementById('user-form').reset();
        document.getElementById('password').required = true;
        document.getElementById('password-help').textContent = 'Minimum 6 caractÃ¨res';
        document.getElementById('password-required').style.display = 'inline';
        document.getElementById('user-modal').style.display = 'flex';
    }

    function editUser(userId) {
        fetch(`${API_ADMIN}?action=get_user&id=${userId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const user = result.data;
                editingUserId = userId;
                document.getElementById('user-modal-title').textContent = 'Modifier l\'Utilisateur';
                document.getElementById('user-id').value = userId;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('fullname').value = user.full_name || '';
                document.getElementById('role').value = user.role;
                document.getElementById('password').required = false;
                document.getElementById('password-help').textContent = 'Laissez vide pour ne pas modifier';
                document.getElementById('password-required').style.display = 'none';
                document.getElementById('user-modal').style.display = 'flex';
            }
        });
    }

    function closeUserModal() {
        document.getElementById('user-modal').style.display = 'none';
        editingUserId = null;
    }

    function saveUser(event) {
        event.preventDefault();
        
        const formData = {
            username: document.getElementById('username').value,
            email: document.getElementById('email').value,
            full_name: document.getElementById('fullname').value,
            role: document.getElementById('role').value
        };

        const password = document.getElementById('password').value;
        if (password) {
            if (password.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères');
                return;
            }
            if (password !== document.getElementById('confirm-password').value) {
                alert('Les mots de passe ne correspondent pas');
                return;
            }
            formData.password = password;
        }

        if (editingUserId) {
            formData.user_id = editingUserId;
        }

        const action = editingUserId ? 'edit_user' : 'add_user';
        
        fetch(`${API_ADMIN}?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                closeUserModal();
                setTimeout(() => window.location.reload(), 500);
            }
        })
        .catch(error => {
            alert('Erreur lors de l\'enregistrement');
        });
    }

    function deleteUser(userId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Toutes ses données seront perdues.')) {
            return;
        }

        fetch(`${API_ADMIN}?action=delete_user`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                window.location.reload();
            }
        });
    }

    function viewUserStats(userId) {
        window.location.href = `user_stats.php?id=${userId}`;
    }

    function filterUsers() {
        const search = document.getElementById('user-search').value.toLowerCase();
        const roleFilter = document.getElementById('role-filter').value;
        const statusFilter = document.getElementById('status-filter').value;

        document.querySelectorAll('.user-row').forEach(row => {
            const username = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.dataset.role;
            const status = row.dataset.status;

            const matchesSearch = search === '' || 
                username.includes(search) || 
                email.includes(search);
            const matchesRole = roleFilter === '' || role === roleFilter;
            const matchesStatus = statusFilter === '' || status === statusFilter;

            row.style.display = matchesSearch && matchesRole && matchesStatus ? '' : 'none';
        });
    }

    function exportUsers() {
        window.location.href = 'admin_api.php?action=export_users';
    }

    function deleteInactiveUsers() {
        if (!confirm('Supprimer tous les utilisateurs inactifs depuis plus de 90 jours ?')) {
            return;
        }

        fetch(`${API_ADMIN}?action=delete_inactive_users`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                window.location.reload();
            }
        });
    }

    // Fermer le modal en cliquant en dehors
    window.onclick = function(event) {
        const modal = document.getElementById('user-modal');
        if (event.target === modal) {
            closeUserModal();
        }
    }
    </script>
</body>
</html>