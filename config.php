<?php
declare(strict_types=1);

// ================================================================
// config.php — Configuration centrale UNIFIÉE
// ================================================================

// ===== Base de données =====
defined('DB_HOST') || define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
defined('DB_USER') || define('DB_USER', getenv('MYSQL_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
defined('DB_NAME') || define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'wedding');
defined('DB_PORT') || define('DB_PORT', getenv('MYSQL_PORT') ?: '3306');
defined('DB_CHARSET') || define('DB_CHARSET', getenv('MYSQL_CHARSET') ?: 'utf8mb4');

// ===== Session =====
define('SESSION_TIMEOUT', 1800);
define('SESSION_NAME',    'wedding_session');

// ===== Application =====
define('APP_NAME',     'WedPlan');
define('APP_VERSION',  '2.0.3');
define('APP_URL',      getenv('APP_URL') ?: 'https://wedplan-production.up.railway.app/');
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

// ===== Configuration PARRAINS =====
define('SPONSOR_SESSION_KEY', 'wedding_sponsor_logged_in');
define('SPONSOR_ID_KEY', 'sponsor_id');
define('SPONSOR_WEDDING_ID_KEY', 'sponsor_wedding_dates_id');
define('SPONSOR_NAME_KEY', 'sponsor_name');
define('SPONSOR_ROLE_KEY', 'sponsor_role');

define('SPONSOR_CAN_VIEW', true);
define('SPONSOR_CAN_CREATE', false);
define('SPONSOR_CAN_UPDATE', false);
define('SPONSOR_CAN_DELETE', false);
define('SPONSOR_CAN_COMMENT', true);
define('SPONSOR_CAN_VIEW_STATS', true);

date_default_timezone_set(APP_TIMEZONE);

// Créer les dossiers
foreach ([BACKUP_DIR, EXPORT_DIR, LOG_DIR, UPLOAD_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// ================================================================
// Classe DatabaseConnection (SINGLETON)
// ================================================================
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci"
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die("Erreur de connexion à la base de données.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

// ================================================================
// FONCTIONS PARRAINS (anciennement dans config_da.php)
// ================================================================

// Gestion session parrain
function isSponsorLoggedIn() {
    return isset($_SESSION[SPONSOR_SESSION_KEY]) && $_SESSION[SPONSOR_SESSION_KEY] === true;
}

function getSponsorId() {
    return $_SESSION[SPONSOR_ID_KEY] ?? null;
}

function getSponsorWeddingId() {
    return $_SESSION[SPONSOR_WEDDING_ID_KEY] ?? null;
}

function getSponsorName() {
    return $_SESSION[SPONSOR_NAME_KEY] ?? 'Parrain';
}

function getSponsorRole() {
    return $_SESSION[SPONSOR_ROLE_KEY] ?? 'parrain';
}

function requireSponsorLogin($redirectUrl = 'sponsor_login.php') {
    if (!isSponsorLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}

function sponsorCanComment() {
    return isSponsorLoggedIn() && SPONSOR_CAN_COMMENT;
}

function logoutSponsor() {
    if (isSponsorLoggedIn()) {
        logSponsorActivity(getSponsorId(), getSponsorWeddingId(), 'deconnexion');
    }
    
    unset($_SESSION[SPONSOR_SESSION_KEY]);
    unset($_SESSION[SPONSOR_ID_KEY]);
    unset($_SESSION[SPONSOR_WEDDING_ID_KEY]);
    unset($_SESSION[SPONSOR_NAME_KEY]);
    unset($_SESSION[SPONSOR_ROLE_KEY]);
    
    if (empty($_SESSION)) session_destroy();
}

function logSponsorActivity($sponsorId, $weddingDatesId, $actionType, $details = null) {
    if (!$sponsorId || !$weddingDatesId) return false;
    
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        $sql = "INSERT INTO sponsor_activity_log 
                (sponsor_id, wedding_dates_id, action_type, details, ip_address, user_agent) 
                VALUES (:sponsor_id, :wedding_dates_id, :action_type, :details, :ip_address, :user_agent)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':sponsor_id' => $sponsorId,
            ':wedding_dates_id' => $weddingDatesId,
            ':action_type' => $actionType,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

function getSponsorInfo($sponsorId) {
    if (!$sponsorId) return null;
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        $sql = "SELECT ws.*, wd.fiance_nom_complet, wd.fiancee_nom_complet, wd.wedding_date, wd.budget_total
                FROM wedding_sponsors ws
                INNER JOIN wedding_dates wd ON ws.wedding_dates_id = wd.id
                WHERE ws.id = :sponsor_id AND ws.statut = 'actif'
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sponsor_id' => $sponsorId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get sponsor info error: " . $e->getMessage());
        return null;
    }
}

function sponsorEmailExists($email, $excludeId = null) {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        $sql = "SELECT id FROM wedding_sponsors WHERE email = :email";
        if ($excludeId) $sql .= " AND id != :exclude_id";
        $sql .= " LIMIT 1";
        
        $params = [':email' => $email];
        if ($excludeId) $params[':exclude_id'] = $excludeId;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Email check error: " . $e->getMessage());
        return false;
    }
}

function notifyNewSponsorComment($weddingDatesId, $sponsorId, $commentaire) {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        // Récupérer l'user_id
        $sql = "SELECT user_id FROM wedding_dates WHERE id = :wedding_dates_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':wedding_dates_id' => $weddingDatesId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Récupérer le nom du parrain
            $sql = "SELECT sponsor_nom_complet FROM wedding_sponsors WHERE id = :sponsor_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':sponsor_id' => $sponsorId]);
            $sponsor = $stmt->fetch();
            
            $sql = "INSERT INTO notifications (user_id, wedding_dates_id, type_notification, message)
                    VALUES (:user_id, :wedding_dates_id, 'nouveau_commentaire_parrain', :message)";
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $result['user_id'],
                ':wedding_dates_id' => $weddingDatesId,
                ':message' => 'Nouveau commentaire de ' . ($sponsor['sponsor_nom_complet'] ?? 'un parrain') . ': ' . substr($commentaire, 0, 100)
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur notification: " . $e->getMessage());
        return false;
    }
}

// ================================================================
// FONCTIONS GÉNÉRALES (anciennement dans config.php)
// ================================================================
function getDBConnection(): PDO {
    return DatabaseConnection::getInstance()->getConnection();
}

function formatCurrency(float|int $amount): string {
    return number_format((float)$amount, 0, ',', ' ') . ' FCFA';
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function escapeHtml($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function formatMontant($montant, $devise = 'FCFA') {
    return number_format($montant, 0, ',', ' ') . ' ' . $devise;
}

function calculerMontantTotal($quantity, $unit_price, $frequency) {
    return $quantity * $unit_price * $frequency;
}

function ensureDir(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
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

function formatSize(int $bytes): string {
    if ($bytes === 0) return '0 o';
    $u = ['o','Ko','Mo','Go'];
    $i = (int)floor(log($bytes)/log(1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $u[$i];
}