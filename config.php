<?php
declare(strict_types=1);

// ================================================================
// config.php — Configuration centrale — Budget Mariage PJPM v2.1
// Compatible Render + Local
// ================================================================

// ===== Base de données (Render compatible) =====
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'railway');

// ===== Session =====
define('SESSION_TIMEOUT', 1800);
define('SESSION_NAME',    'railway_session');

// ===== Application =====
define('APP_NAME',     'WedPlan');
define('APP_VERSION',  '2.0.3');
define('APP_URL',      getenv('APP_URL') ?: 'http://localhost');
define('APP_CURRENCY', 'FCFA');
define('APP_LOCALE',   'fr-FR');
define('APP_TIMEZONE', 'Africa/Porto-Novo');

// ===== Chemins =====
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}

define('BACKUP_DIR',  ROOT_PATH . 'backups/');
define('EXPORT_DIR',  ROOT_PATH . 'exports/');
define('LOG_DIR',     ROOT_PATH . 'logs/');
define('UPLOAD_DIR',  ROOT_PATH . 'uploads/');

// ===== Limites =====
define('MAX_EXPENSES_PER_USER', 500);
define('MAX_CATEGORIES',        50);
define('BACKUP_MAX_FILES',      10);

date_default_timezone_set(APP_TIMEZONE);

// Créer les dossiers nécessaires
foreach ([BACKUP_DIR, EXPORT_DIR, LOG_DIR, UPLOAD_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ----------------------------------------------------------------
// Connexion PDO (singleton)
// ----------------------------------------------------------------
function getDBConnection(): PDO {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );
        } catch (PDOException $e) {
            error_log("[DB] " . $e->getMessage());
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion base de données.'],
                JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $conn;
}

// ----------------------------------------------------------------
// Helpers
// ----------------------------------------------------------------
function formatCurrency(float|int $amount): string {
    return number_format((float)$amount, 0, ',', ' ') . ' FCFA';
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function ensureDir(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
}

function formatSize(int $bytes): string {
    if ($bytes === 0) return '0 o';
    $u = ['o','Ko','Mo','Go'];
    $i = (int)floor(log($bytes)/log(1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $u[$i];
}

function jsonResponse(bool $success, string $message = '', mixed $data = null, int $code = 200): never {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
    }
    $r = ['success' => $success, 'message' => $message];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}