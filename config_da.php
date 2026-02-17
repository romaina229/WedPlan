<?php
/**
 * Configuration pour le système de parrains/conseillers
 * Compatible Render + Local
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================
   Base de données — compatible variables d'environnement
   ===================================================== */

defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'root');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'railway');
defined('DB_CHARSET') || define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ===== Session =====
define('SESSION_TIMEOUT', 1800);
define('SESSION_NAME',    'wedding_session');

// ===== Application =====
define('APP_NAME',     'WedPlan');
define('APP_VERSION',  '2.0.3');
define('APP_URL',      getenv('APP_URL') ?: 'http://localhost');
define('APP_CURRENCY', 'FCFA');
define('APP_LOCALE',   'fr-FR');
define('APP_TIMEZONE', 'Africa/Porto-Novo');

// Configuration du système de parrains
define('SPONSOR_SESSION_KEY', 'wedding_sponsor_logged_in');
define('SPONSOR_ID_KEY', 'sponsor_id');
define('SPONSOR_WEDDING_ID_KEY', 'sponsor_wedding_dates_id');
define('SPONSOR_NAME_KEY', 'sponsor_name');
define('SPONSOR_ROLE_KEY', 'sponsor_role');

// Permissions des parrains
define('SPONSOR_CAN_VIEW', true);
define('SPONSOR_CAN_CREATE', false);
define('SPONSOR_CAN_UPDATE', false);
define('SPONSOR_CAN_DELETE', false);
define('SPONSOR_CAN_COMMENT', true);
define('SPONSOR_CAN_VIEW_STATS', true);

/**
 * Classe de gestion de la connexion à la base de données
 */
class DatabaseConnection {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=" . getenv('DB_CHARSET');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . getenv('DB_CHARSET') . " COLLATE " . getenv('DB_CHARSET') . "_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), $options);
        } catch (PDOException $e) {
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
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

/**
 * Fonction pour vérifier si un parrain est connecté
 */
function isSponsorLoggedIn() {
    return isset($_SESSION[SPONSOR_SESSION_KEY]) && $_SESSION[SPONSOR_SESSION_KEY] === true;
}

/**
 * Fonction pour obtenir l'ID du parrain connecté
 */
function getSponsorId() {
    return $_SESSION[SPONSOR_ID_KEY] ?? null;
}

/**
 * Fonction pour obtenir l'ID du mariage supervisé
 */
function getSponsorWeddingId() {
    return $_SESSION[SPONSOR_WEDDING_ID_KEY] ?? null;
}

/**
 * Fonction pour obtenir le nom du parrain connecté
 */
function getSponsorName() {
    return $_SESSION[SPONSOR_NAME_KEY] ?? 'Parrain';
}

/**
 * Fonction pour obtenir le rôle du parrain
 */
function getSponsorRole() {
    return $_SESSION[SPONSOR_ROLE_KEY] ?? 'parrain';
}

/**
 * Fonction pour rediriger si le parrain n'est pas connecté
 */
function requireSponsorLogin($redirectUrl = 'sponsor_login.php') {
    if (!isSponsorLoggedIn()) {
        header("Location: $redirectUrl");
        exit();
    }
}

/**
 * Fonction pour vérifier les permissions du parrain
 */
function sponsorCanComment() {
    return isSponsorLoggedIn() && SPONSOR_CAN_COMMENT;
}

/**
 * Fonction pour déconnecter un parrain
 */
function logoutSponsor() {
    // Enregistrer l'activité de déconnexion
    if (isSponsorLoggedIn()) {
        logSponsorActivity(getSponsorId(), getSponsorWeddingId(), 'deconnexion');
    }
    
    // Détruire les variables de session
    unset($_SESSION[SPONSOR_SESSION_KEY]);
    unset($_SESSION[SPONSOR_ID_KEY]);
    unset($_SESSION[SPONSOR_WEDDING_ID_KEY]);
    unset($_SESSION[SPONSOR_NAME_KEY]);
    unset($_SESSION[SPONSOR_ROLE_KEY]);
    
    // Détruire la session si vide
    if (empty($_SESSION)) {
        session_destroy();
    }
}

/**
 * Fonction pour enregistrer l'activité d'un parrain
 */
function logSponsorActivity($sponsorId, $weddingDatesId, $actionType, $details = null) {
    // Ne pas enregistrer si les IDs ne sont pas valides
    if (!$sponsorId || !$weddingDatesId) {
        return false;
    }
    
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        $sql = "INSERT INTO sponsor_activity_log 
                (sponsor_id, wedding_dates_id, action_type, details, ip_address, user_agent) 
                VALUES (:sponsor_id, :wedding_dates_id, :action_type, :details, :ip_address, :user_agent)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':sponsor_id' => $sponsorId,
            ':wedding_dates_id' => $weddingDatesId,
            ':action_type' => $actionType,
            ':details' => $details,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de l'activité: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour sécuriser les sorties HTML
 */
function escapeHtml($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction pour formater une date
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Fonction pour formater un montant
 */
function formatMontant($montant, $devise = 'FCFA') {
    return number_format($montant, 0, ',', ' ') . ' ' . $devise;
}

/**
 * Fonction pour calculer le montant total d'une dépense
 * Selon la structure: quantity * unit_price * frequency
 */
function calculerMontantTotal($quantity, $unit_price, $frequency) {
    return $quantity * $unit_price * $frequency;
}

/**
 * Fonction pour récupérer les informations d'un parrain
 */
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
        error_log("Erreur récupération info parrain: " . $e->getMessage());
        return null;
    }
}

/**
 * Fonction pour notifier les fiancés d'un nouveau commentaire
 */
function notifyNewSponsorComment($weddingDatesId, $sponsorId, $commentaire) {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        // Utiliser la procédure stockée si elle existe
        $sql = "CALL sp_notify_new_sponsor_comment(:wedding_dates_id, :sponsor_id, :commentaire)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':wedding_dates_id' => $weddingDatesId,
            ':sponsor_id' => $sponsorId,
            ':commentaire' => $commentaire
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Si la procédure n'existe pas, créer la notification manuellement
        try {
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
                
                // Insérer la notification
                $sql = "INSERT INTO notifications (user_id, wedding_dates_id, type_notification, message)
                        VALUES (:user_id, :wedding_dates_id, 'nouveau_commentaire_parrain', :message)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    ':user_id' => $result['user_id'],
                    ':wedding_dates_id' => $weddingDatesId,
                    ':message' => 'Nouveau commentaire de ' . ($sponsor['sponsor_nom_complet'] ?? 'un parrain') . ': ' . substr($commentaire, 0, 100)
                ]);
                
                return true;
            }
        } catch (PDOException $e2) {
            error_log("Erreur notification: " . $e2->getMessage());
        }
        
        return false;
    }
}

/**
 * Fonction pour vérifier si un email de parrain existe déjà
 */
function sponsorEmailExists($email, $excludeId = null) {
    try {
        $db = DatabaseConnection::getInstance()->getConnection();
        
        $sql = "SELECT id FROM wedding_sponsors WHERE email = :email";
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        $sql .= " LIMIT 1";
        
        $stmt = $db->prepare($sql);
        $params = [':email' => $email];
        if ($excludeId) {
            $params[':exclude_id'] = $excludeId;
        }
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Erreur vérification email: " . $e->getMessage());
        return false;
    }
}