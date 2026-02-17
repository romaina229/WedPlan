<?php
declare(strict_types=1);
// ================================================================
// admin_api.php — API Administration — Budget Mariage PJPM v2.0
// ================================================================

error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../AuthManager.php';
require_once __DIR__ . '/../ExpenseManager.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ── Vérification admin obligatoire ───────────────────────────
if (!AuthManager::isLoggedIn()) {
    jsonResponse(false, 'Non authentifié.', null, 401);
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    jsonResponse(false, 'Accès refusé. Droits administrateur requis.', null, 403);
}

$auth       = new AuthManager();
$manager    = new ExpenseManager();
$currentUid = (int)$_SESSION['user_id'];
$action     = $_GET['action'] ?? '';

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    return !empty($raw) && ($d = json_decode($raw, true)) && is_array($d) ? $d : [];
}

try {
    switch ($action) {

        // ──────────────── Statistiques globales ──────────────
        case 'global_stats':
            $users   = $auth->getAllUsers();
            $admins  = array_filter($users, fn($u) => $u['role'] === 'admin');
            $totalExp = 0;
            $totalBudget = 0.0;
            foreach ($users as $u) {
                $s = $manager->getStats($u['id']);
                $totalExp    += $s['total_items'];
                $totalBudget += $manager->getGrandTotal($u['id']);
            }
            jsonResponse(true, '', [
                'total_users'    => count($users),
                'total_admins'   => count($admins),
                'total_expenses' => $totalExp,
                'total_budget'   => $totalBudget,
            ]);

        // ──────────────── Utilisateurs ────────────────────────
        case 'get_users':
            jsonResponse(true, '', $auth->getAllUsers());

        case 'get_user':
            $id   = (int)($_GET['id'] ?? 0);
            $user = $auth->getUserById($id);
            if (!$user) jsonResponse(false, 'Utilisateur introuvable.', null, 404);
            jsonResponse(true, '', $user);

        case 'add_user':
            $data = getJsonBody();
            if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
                jsonResponse(false, 'Champs requis manquants.', null, 422);
            }
            $result = $auth->register($data['username'], $data['email'], $data['password'], $data['full_name'] ?? null);
            if ($result['success'] && !empty($data['role']) && $data['role'] === 'admin') {
                // Promouvoir en admin
                $conn = getDBConnection();
                $conn->prepare("UPDATE users SET role = 'admin' WHERE username = ?")->execute([$data['username']]);
            }
            $auth->logActivity($currentUid, "Ajout utilisateur : " . ($data['username'] ?? ''), 'admin');
            jsonResponse($result['success'], $result['message']);

        case 'edit_user':
            $data   = getJsonBody();
            $uid    = (int)($data['user_id'] ?? 0);
            $role   = $data['role'] ?? '';
            if ($uid <= 0 || !in_array($role, ['admin','user'], true)) {
                jsonResponse(false, 'Données invalides.', null, 422);
            }
            // Empêcher de retirer le seul admin
            if ($uid === $currentUid && $role === 'user') {
                $conn   = getDBConnection();
                $admins = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($admins <= 1) jsonResponse(false, 'Impossible : vous êtes le seul administrateur.', null, 409);
            }
            $conn = getDBConnection();
            $updates = ["role = ?"];
            $params  = [$role];
            if (!empty($data['full_name'])) { $updates[] = "full_name = ?"; $params[] = $data['full_name']; }
            if (!empty($data['email']))     { $updates[] = "email = ?";     $params[] = $data['email']; }
            $params[] = $uid;
            $conn->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
            // Nouveau mot de passe éventuel
            if (!empty($data['password']) && strlen($data['password']) >= 6) {
                $conn->prepare("UPDATE users SET password = ? WHERE id = ?")
                    ->execute([password_hash($data['password'], PASSWORD_DEFAULT), $uid]);
            }
            $auth->logActivity($currentUid, "Modification utilisateur #$uid", 'admin', "Rôle: $role");
            jsonResponse(true, 'Utilisateur mis à jour avec succès.');

        case 'delete_user':
            $data = getJsonBody();
            $uid  = (int)($data['user_id'] ?? 0);
            if ($uid <= 0)              jsonResponse(false, 'ID invalide.',                              null, 422);
            if ($uid === $currentUid)   jsonResponse(false, 'Vous ne pouvez pas supprimer votre compte.', null, 409);
            // Dernier admin ?
            $conn    = getDBConnection();
            $userRow = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
            $userRow->execute([$uid]);
            $userRole = $userRow->fetchColumn();
            if ($userRole === 'admin') {
                $admins = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                if ($admins <= 1) jsonResponse(false, 'Impossible de supprimer le dernier administrateur.', null, 409);
            }
            $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
            $auth->logActivity($currentUid, "Suppression utilisateur #$uid", 'admin');
            jsonResponse(true, 'Utilisateur supprimé avec succès.');

        case 'delete_inactive_users':
            $conn  = getDBConnection();
            $count = (int)$conn->query("
                SELECT COUNT(*) FROM users
                WHERE role = 'user'
                  AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 90 DAY))
                  AND id != $currentUid
            ")->fetchColumn();
            $conn->exec("
                DELETE FROM users
                WHERE role = 'user'
                  AND (last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 90 DAY))
                  AND id != $currentUid
            ");
            $auth->logActivity($currentUid, "Suppression $count utilisateurs inactifs", 'admin');
            jsonResponse(true, "$count utilisateur(s) inactif(s) supprimé(s).");

        // ──────────────── Export CSV ──────────────────────────
        case 'export_users':
            ensureDir(EXPORT_DIR);
            $filename = 'export_utilisateurs_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = EXPORT_DIR . $filename;
            $fp = fopen($filepath, 'w');
            // BOM UTF-8
            fwrite($fp, "\xEF\xBB\xBF");
            fputcsv($fp, ['ID','Nom utilisateur','Email','Nom complet','Rôle','Inscrit le','Dernière connexion'], ';');
            foreach ($auth->getAllUsers() as $u) {
                fputcsv($fp, [
                    $u['id'], $u['username'], $u['email'], $u['full_name'] ?? '',
                    $u['role'], $u['created_at'], $u['last_login'] ?? '',
                ], ';');
            }
            fclose($fp);
            $auth->logActivity($currentUid, "Export CSV utilisateurs", 'admin');
            jsonResponse(true, 'Export terminé.', ['file_url' => $filepath, 'file_name' => $filename]);

        // ──────────────── Sauvegarde BDD ─────────────────────
        case 'create_backup':
            ensureDir(BACKUP_DIR);
            $conn     = getDBConnection();
            $dbName   = DB_NAME;
            $filename = "backup_{$dbName}_" . date('Y-m-d_H-i-s') . '.sql';
            $filepath = BACKUP_DIR . $filename;

            $sql  = "-- Backup: $dbName\n-- Date: " . date('Y-m-d H:i:s') . "\n-- Budget Mariage PJPM v2.0\n\n";
            $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

            $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch();
                $sql   .= "-- Table: $table\nDROP TABLE IF EXISTS `$table`;\n";
                $sql   .= $create['Create Table'] . ";\n\n";
                $rows = $conn->query("SELECT * FROM `$table`")->fetchAll();
                foreach ($rows as $row) {
                    $values = array_map(fn($v) => $v === null ? 'NULL' : $conn->quote((string)$v), $row);
                    $sql   .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            file_put_contents($filepath, $sql);

            // Rotation : garder seulement BACKUP_MAX_FILES fichiers
            $files = glob(BACKUP_DIR . '*.sql');
            if ($files && count($files) > BACKUP_MAX_FILES) {
                usort($files, fn($a,$b) => filemtime($a) - filemtime($b));
                $toDelete = array_slice($files, 0, count($files) - BACKUP_MAX_FILES);
                foreach ($toDelete as $old) unlink($old);
            }

            $auth->logActivity($currentUid, "Sauvegarde créée : $filename", 'admin');
            jsonResponse(true, 'Sauvegarde créée avec succès.', [
                'file_url'  => $filepath,
                'file_name' => $filename,
                'file_size' => formatSize(filesize($filepath)),
            ]);

        case 'list_backups':
            ensureDir(BACKUP_DIR);
            $files   = glob(BACKUP_DIR . '*.sql') ?: [];
            usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
            $backups = array_map(fn($f) => [
                'name' => basename($f),
                'path' => $f,
                'size' => formatSize(filesize($f)),
                'date' => date('d/m/Y H:i:s', filemtime($f)),
            ], $files);
            jsonResponse(true, '', $backups);

        case 'delete_backup':
            $data = getJsonBody();
            $name = basename($data['filename'] ?? '');
            if (!preg_match('/^backup_[a-zA-Z0-9_\-]+\.sql$/', $name)) {
                jsonResponse(false, 'Nom de fichier invalide.', null, 422);
            }
            $path = BACKUP_DIR . $name;
            if (!file_exists($path)) jsonResponse(false, 'Fichier introuvable.', null, 404);
            unlink($path);
            $auth->logActivity($currentUid, "Suppression sauvegarde : $name", 'admin');
            jsonResponse(true, 'Sauvegarde supprimée.');

        // ──────────────── Journaux ────────────────────────────
        case 'get_logs':
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $type   = $_GET['type']   ?? '';
            $search = $_GET['search'] ?? '';
            jsonResponse(true, '', $auth->getLogs($page, 50, $type, $search));

        case 'clear_logs':
            $auth->clearLogs();
            jsonResponse(true, 'Journaux effacés avec succès.');

        // ──────────────── Catégories ──────────────────────────
        case 'get_categories':
            jsonResponse(true, '', $manager->getAllCategories());

        case 'update_category':
            $data = getJsonBody();
            $id   = (int)($data['id'] ?? 0);
            if ($id <= 0 || empty($data['name'])) jsonResponse(false, 'Données invalides.', null, 422);
            $conn = getDBConnection();
            $conn->prepare("UPDATE categories SET name=?, color=?, icon=?, display_order=? WHERE id=?")
                ->execute([
                    trim($data['name']),
                    $data['color'] ?? '#8b4f8d',
                    $data['icon']  ?? 'fas fa-folder',
                    (int)($data['display_order'] ?? 0),
                    $id,
                ]);
            $auth->logActivity($currentUid, "Modification catégorie #$id", 'admin');
            jsonResponse(true, 'Catégorie mise à jour.');

        // ──────────────── Infos système ───────────────────────
        case 'system_info':
            ensureDir(BACKUP_DIR);
            $backups    = glob(BACKUP_DIR . '*.sql') ?: [];
            $lastBackup = $backups ? date('d/m/Y H:i', filemtime(end(usort($backups, fn($a,$b)=>filemtime($b)-filemtime($a)) ? $backups[0] : $backups[0]))) : 'Jamais';
            $logResult  = $auth->getLogs(1, 1);
            jsonResponse(true, '', [
                'php_version'     => PHP_VERSION,
                'db_name'         => DB_NAME,
                'app_version'     => APP_VERSION,
                'backup_count'    => count($backups),
                'last_backup'     => $lastBackup,
                'log_count'       => $logResult['total'],
                'memory_usage'    => formatSize(memory_get_usage(true)),
                'memory_peak'     => formatSize(memory_get_peak_usage(true)),
                'server_time'     => date('d/m/Y H:i:s'),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            ]);

        default:
            jsonResponse(false, 'Action non reconnue.', null, 404);
    }
} catch (Throwable $e) {
    error_log("[ADMIN_API] " . $e->getMessage() . " — " . $e->getFile() . ":" . $e->getLine());
    jsonResponse(false, 'Erreur interne du serveur.', null, 500);
}
