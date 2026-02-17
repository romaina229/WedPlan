<?php
// admin_users.php - CORRIGÉ
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';

if (!AuthManager::isLoggedIn()) {
    header('Location: ' . APP_URL . '/auth/login.php');
    exit;
}

$currentUser = AuthManager::getCurrentUser();
if (($currentUser['role'] ?? '') !== 'admin') {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

// Liste des sauvegardes existantes
$backups = [];
$backupDir = 'backups/';
if (is_dir($backupDir)) {
    $files = scandir($backupDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . $file;
            $backups[] = [
                'name' => $file,
                'path' => $filepath,
                'size' => filesize($filepath),
                'date' => date('d/m/Y H:i:s', filemtime($filepath))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sauvegarde Base de Données - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .backup-container {
            max-width: 1200px;
            margin: 90px auto 20px;
            padding: 20px;
        }

        .backup-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #5d2f5f 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .backup-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .backup-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .backup-card {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow);
            text-align: center;
        }

        .backup-card h3 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .backup-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .backup-info {
            margin-top: 20px;
            padding: 15px;
            background: var(--bg-main);
            border-radius: 10px;
            text-align: left;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .info-value {
            color: var(--text-primary);
        }

        .file-size {
            color: var(--primary);
            font-weight: 600;
        }

        .backup-list {
            margin-top: 20px;
        }

        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-card);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .backup-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px var(--shadow);
        }

        .backup-details {
            flex: 1;
        }

        .backup-name {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .backup-meta {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .backup-actions-btns {
            display: flex;
            gap: 10px;
        }

        .no-backups {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="backup-container">
        <div class="backup-header">
            <h1><i class="fas fa-database"></i> Sauvegarde Base de Données</h1>
            <p>Protégez vos données avec des sauvegardes régulières</p>
        </div>

        <div class="backup-actions">
            <div class="backup-card">
                <div class="backup-icon">
                    <i class="fas fa-download"></i>
                </div>
                <h3>Sauvegarde manuelle</h3>
                <p>Créez une copie complète de la base de données</p>
                <button class="btn btn-primary" onclick="createBackup()" style="margin-top: 20px;">
                    <i class="fas fa-database"></i> Créer une sauvegarde
                </button>
                <div class="backup-info">
                    <div class="info-item">
                        <span class="info-label">Dernière sauvegarde</span>
                        <span class="info-value">
                            <?php echo !empty($backups) ? $backups[0]['date'] : 'Jamais'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Taille totale</span>
                        <span class="info-value file-size">
                            <?php
                            $totalSize = 0;
                            foreach ($backups as $backup) {
                                $totalSize += $backup['size'];
                            }
                            echo formatSize($totalSize);
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="backup-card">
                <div class="backup-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h3>Restaurer</h3>
                <p>Restaurer la base de donnÃ©es depuis un fichier</p>
                <div style="margin-top: 20px;">
                    <input type="file" id="restore-file" accept=".sql" style="margin-bottom: 15px; width: 100%; padding: 10px;">
                    <button class="btn btn-warning" onclick="restoreBackup()">
                        <i class="fas fa-history"></i> Restaurer la sauvegarde
                    </button>
                </div>
                <div class="backup-info">
                    <div class="info-item">
                        <span class="info-label">Format accepté</span>
                        <span class="info-value">.sql (MySQL)</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Sauvegardes disponibles</span>
                        <span class="info-value"><?php echo count($backups); ?> fichiers</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-history"></i> Historique des sauvegardes</h2>
            <div class="backup-list">
                <?php if (!empty($backups)): ?>
                    <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div class="backup-details">
                            <div class="backup-name">
                                <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($backup['name']); ?>
                            </div>
                            <div class="backup-meta">
                                Créée le <?php echo $backup['date']; ?> • 
                                Taille : <?php echo formatSize($backup['size']); ?>
                            </div>
                        </div>
                        <div class="backup-actions-btns">
                            <button class="btn btn-sm btn-primary" onclick="downloadBackup('<?php echo $backup['name']; ?>')">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="restoreSpecificBackup('<?php echo $backup['name']; ?>')">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteBackup('<?php echo $backup['name']; ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-backups">
                        <i class="fas fa-database" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p>Aucune sauvegarde disponible</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    const API_ADMIN = '<?= APP_URL ?>/api/admin_api.php';
    function formatSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function createBackup() {
        if (!confirm('Créer une nouvelle sauvegarde de la base de données ?')) {
            return;
        }

        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';

        fetch(API_ADMIN + '?action=create_backup')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Créer un lien de télécargement
                const link = document.createElement('a');
                link.href = result.file_url;
                link.download = result.file_name;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                alert('Sauvegarde créée avec succès !');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert('Erreur : ' + result.message);
            }
            btn.disabled = false;
            btn.innerHTML = originalText;
        })
        .catch(error => {
            alert('Erreur lors de la création de la sauvegarde');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    function restoreBackup() {
        const fileInput = document.getElementById('restore-file');
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Veuillez sélectionner un fichier de sauvegarde');
            return;
        }

        if (!confirm('ATTENTION : Cette action va écraser la base de données actuelle. Êtes-vous sûr ?')) {
            return;
        }

        const formData = new FormData();
        formData.append('backup_file', file);

        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restauration...';

        fetch(API_ADMIN + '?action=restore_backup', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                setTimeout(() => window.location.reload(), 2000);
            }
            btn.disabled = false;
            btn.innerHTML = originalText;
        })
        .catch(error => {
            alert('Erreur lors de la restauration');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    function downloadBackup(filename) {
        window.location.href = 'backups/' + filename;
    }

    function restoreSpecificBackup(filename) {
        if (!confirm(`Restaurer la sauvegarde "${filename}" ? Cette action va éraser la base de données actuelle.`)) {
            return;
        }

        fetch(API_ADMIN + '?action=restore_specific_backup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ filename: filename })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                setTimeout(() => window.location.reload(), 2000);
            }
        });
    }

    function deleteBackup(filename) {
        if (!confirm(`Supprimer la sauvegarde "${filename}" ?`)) {
            return;
        }

        fetch(API_ADMIN + '?action=delete_backup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ filename: filename })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                window.location.reload();
            }
        });
    }
    </script>
</body>
</html>