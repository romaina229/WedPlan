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

// Logs système - CORRIGER LES CHEMINS
$logFiles = [
    'error' => __DIR__ . '/../logs/error.log',     // ✅ Chemin absolu
    'access' => __DIR__ . '/../logs/access.log',   // ✅ Chemin absolu
    'activity' => __DIR__ . '/../logs/activity.log' // ✅ Chemin absolu
];
// Statistiques des logs
$logStats = [
    'total_lines' => 0,
    'today_lines' => 0,
    'error_count' => 0,
    'warning_count' => 0
];
$logContents = [];
foreach ($logFiles as $type => $file) {
    $logContents[$type] = file_exists($file) ? file_get_contents($file) : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journaux SystÃ¨me - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .logs-container {
            max-width: 1400px;
            margin: 90px auto 20px;
            padding: 20px;
        }

        .logs-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #5d2f5f 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .logs-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .logs-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: var(--bg-card);
            padding: 10px;
            border-radius: 15px;
            box-shadow: 0 4px 15px var(--shadow);
            flex-wrap: wrap;
        }

        .log-tab {
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

        .log-tab:hover {
            background: var(--bg-main);
            color: var(--primary);
        }

        .log-tab.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .log-content {
            background: var(--bg-card);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px var(--shadow);
            margin-bottom: 30px;
        }

        .log-section {
            display: none;
        }

        .log-section.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .log-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .log-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .log-stat-card {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow);
            text-align: center;
        }

        .log-stat-card.error {
            border-left: 4px solid var(--danger);
        }

        .log-stat-card.warning {
            border-left: 4px solid var(--warning);
        }

        .log-stat-card.info {
            border-left: 4px solid var(--primary);
        }

        .log-stat-card.success {
            border-left: 4px solid var(--success);
        }

        .log-stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .log-stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9rem;
            line-height: 1.5;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .log-line {
            padding: 2px 0;
            border-bottom: 1px solid #2d2d2d;
        }

        .log-line.error {
            color: #f48771;
            background: rgba(244, 135, 113, 0.1);
        }

        .log-line.warning {
            color: #ffcc00;
            background: rgba(255, 204, 0, 0.1);
        }

        .log-line.info {
            color: #9cdcfe;
        }

        .log-line.debug {
            color: #888;
            font-style: italic;
        }

        .log-timestamp {
            color: #6a9955;
        }

        .log-message {
            color: #d4d4d4;
        }

        .log-empty {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-style: italic;
        }

        .log-search {
            margin-bottom: 20px;
        }

        .log-search input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Lato', sans-serif;
        }

        .log-search input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 79, 141, 0.1);
        }
    </style>
</head>
<body>
    <div class="logs-container">
        <div class="logs-header">
            <h1><i class="fas fa-clipboard-list"></i> Journaux Système</h1>
            <p>Surveillance et analyse des activités du système</p>
        </div>

        <div class="log-stats">
            <div class="log-stat-card error">
                <div class="log-stat-value"><?php echo $logStats['error_count']; ?></div>
                <div class="log-stat-label">Erreurs critiques</div>
            </div>
            <div class="log-stat-card warning">
                <div class="log-stat-value"><?php echo $logStats['warning_count']; ?></div>
                <div class="log-stat-label">Avertissements</div>
            </div>
            <div class="log-stat-card info">
                <div class="log-stat-value"><?php echo $logStats['total_lines']; ?></div>
                <div class="log-stat-label">Lignes de log</div>
            </div>
            <div class="log-stat-card success">
                <div class="log-stat-value"><?php echo $logStats['today_lines']; ?></div>
                <div class="log-stat-label">Aujourd'hui</div>
            </div>
        </div>

        <div class="logs-tabs">
            <button class="log-tab active" onclick="switchLogTab('error')">
                <i class="fas fa-exclamation-triangle"></i> Logs d'Erreurs
            </button>
            <button class="log-tab" onclick="switchLogTab('access')">
                <i class="fas fa-door-open"></i> Logs d'Accès
            </button>
            <button class="log-tab" onclick="switchLogTab('activity')">
                <i class="fas fa-user-check"></i> Logs d'Activité
            </button>
            <button class="log-tab" onclick="switchLogTab('system')">
                <i class="fas fa-server"></i> Logs Système
            </button>
        </div>

        <div class="log-content">
            <div class="log-actions">
                <button class="btn btn-primary" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
                <button class="btn btn-warning" onclick="clearCurrentLog()">
                    <i class="fas fa-trash-alt"></i> Effacer ce journal
                </button>
                <button class="btn btn-danger" onclick="clearAllLogs()">
                    <i class="fas fa-broom"></i> Effacer tous les journaux
                </button>
                <button class="btn btn-success" onclick="downloadCurrentLog()">
                    <i class="fas fa-download"></i> Télécharger
                </button>
            </div>

            <div class="log-search">
                <input type="text" id="log-search" placeholder="Rechercher dans les logs..." onkeyup="filterLogs()">
            </div>

            <!-- Onglet Erreurs -->
            <div id="error-tab" class="log-section active">
                <h3><i class="fas fa-exclamation-triangle"></i> Logs d'Erreurs</h3>
                <div class="log-viewer" id="error-log">
                    <?php if (!empty($logContents['error'])): ?>
                        <?php echo htmlspecialchars($logContents['error']); ?>
                    <?php else: ?>
                        <div class="log-empty">
                            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                            <p>Aucune erreur enregistrée</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Onglet Accès -->
            <div id="access-tab" class="log-section">
                <h3><i class="fas fa-door-open"></i> Logs d'Accès</h3>
                <div class="log-viewer" id="access-log">
                    <?php if (!empty($logContents['access'])): ?>
                        <?php echo htmlspecialchars($logContents['access']); ?>
                    <?php else: ?>
                        <div class="log-empty">
                            <i class="fas fa-door-closed" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                            <p>Aucun log d'accès enregistré</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Onglet ActivitÃ© -->
            <div id="activity-tab" class="log-section">
                <h3><i class="fas fa-user-check"></i> Logs d'Activité</h3>
                <div class="log-viewer" id="activity-log">
                    <?php if (!empty($logContents['activity'])): ?>
                        <?php echo htmlspecialchars($logContents['activity']); ?>
                    <?php else: ?>
                        <div class="log-empty">
                            <i class="fas fa-user-clock" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                            <p>Aucune activité enregistrée</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Onglet Système -->
            <div id="system-tab" class="log-section">
                <h3><i class="fas fa-server"></i> Informations Système</h3>
                <div class="log-viewer">
                    <div class="log-line info">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">=== INFORMATIONS SYSTÈME ===</span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">PHP Version: <?php echo phpversion(); ?></span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">Serveur: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">Base de donnÃ©es: <?php echo DB_NAME; ?></span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">Utilisateurs connectÃ©s: <?php echo count($logContents['activity'] ? explode("\n", $logContents['activity']) : []); ?></span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">Mémoire utilisée: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</span>
                    </div>
                    <div class="log-line">
                        <span class="log-timestamp">[<?php echo date('Y-m-d H:i:s'); ?>]</span>
                        <span class="log-message">Temps d'exécution: <?php echo round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3); ?> secondes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const API_ADMIN = '<?= APP_URL ?>/api/admin_api.php';
    let currentLogTab = 'error';

    function switchLogTab(tabName) {
        currentLogTab = tabName;
        
        // Masquer tous les contenus
        document.querySelectorAll('.log-section').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // DÃ©sactiver tous les boutons
        document.querySelectorAll('.log-tab').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
    }

    function refreshLogs() {
        window.location.reload();
    }

    function clearCurrentLog() {
        if (!confirm(`Effacer le journal ${currentLogTab} ?`)) {
            return;
        }

        fetch(API_ADMIN + '?action=clear_log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ log_type: currentLogTab })
        })
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                refreshLogs();
            }
        });
    }

    function clearAllLogs() {
        if (!confirm('Effacer tous les journaux ? Cette action est irrÃ©versible.')) {
            return;
        }

        fetch(API_ADMIN + '?action=clear_all_logs')
        .then(response => response.json())
        .then(result => {
            alert(result.message);
            if (result.success) {
                refreshLogs();
            }
        });
    }

    function downloadCurrentLog() {
        const logContent = document.getElementById(currentLogTab + '-log').textContent;
        const blob = new Blob([logContent], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${currentLogTab}_log_${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    function filterLogs() {
        const search = document.getElementById('log-search').value.toLowerCase();
        const logViewer = document.getElementById(currentLogTab + '-log');
        
        if (!search) {
            // Afficher tous les logs
            const lines = logViewer.textContent.split('\n');
            logViewer.innerHTML = lines.map(line => 
                `<div class="log-line ${getLogLineClass(line)}">${escapeHtml(line)}</div>`
            ).join('');
            return;
        }

        const lines = logViewer.textContent.split('\n');
        const filteredLines = lines.filter(line => line.toLowerCase().includes(search));
        
        logViewer.innerHTML = filteredLines.map(line => {
            const highlighted = escapeHtml(line).replace(
                new RegExp(search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi'),
                match => `<span style="background: yellow; color: black;">${match}</span>`
            );
            return `<div class="log-line ${getLogLineClass(line)}">${highlighted}</div>`;
        }).join('');
    }

    function getLogLineClass(line) {
        if (line.toLowerCase().includes('error') || line.toLowerCase().includes('exception')) {
            return 'error';
        } else if (line.toLowerCase().includes('warning') || line.toLowerCase().includes('avertissement')) {
            return 'warning';
        } else if (line.toLowerCase().includes('debug')) {
            return 'debug';
        }
        return 'info';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-refresh toutes les 30 secondes
    setInterval(() => {
        if (!document.hidden) {
            fetch(API_ADMIN + '?action=check_log_updates')
            .then(response => response.json())
            .then(result => {
                if (result.updated) {
                    refreshLogs();
                }
            });
        }
    }, 30000);
    </script>
</body>
</html>