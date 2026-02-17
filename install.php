<?php
declare(strict_types=1);
ini_set('default_charset', 'UTF-8');
header('Content-Type: text/html; charset=UTF-8');
// FIX: Vérifier avant de définir
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
/**
 * install.php — Installation de la base de données avec système parrain
 * Exécutez ce fichier UNE SEULE FOIS pour initialiser l'application.
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'wedding';

$steps  = [];
$errors = [];

function step(string $msg, array &$steps): void {
    $steps[] = '✓ ' . $msg;
    echo "✓ $msg<br>\n";
    flush();
}
function fail(string $msg, array &$errors): void {
    $errors[] = '✗ ' . $msg;
    echo "<span style='color:red'>✗ $msg</span><br>\n";
    flush();
}

try {
    // 1. Connexion MySQL
    $conn = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"]
    );
    step("Connexion MySQL établie", $steps);

    // 2. Création base de données
    $conn->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE `$dbname`");
    step("Base de données '$dbname' prête", $steps);
    
    // Désactiver les vérifications de clés étrangères pendant l'installation
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    step("Vérifications des clés étrangères désactivées", $steps);

    // 3. Table users
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username   VARCHAR(50)  NOT NULL UNIQUE,
        email      VARCHAR(100) NOT NULL UNIQUE,
        password   VARCHAR(255) NOT NULL,
        full_name  VARCHAR(100) NULL,
        role       ENUM('admin','user') DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL,
        INDEX idx_username (username),
        INDEX idx_email    (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'users' créée", $steps);

    // 4. Table categories
    $conn->exec("CREATE TABLE IF NOT EXISTS categories (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(255) NOT NULL UNIQUE,
        color         VARCHAR(7)   DEFAULT '#3498db',
        icon          VARCHAR(50)  DEFAULT 'fas fa-folder',
        display_order INT          DEFAULT 0,
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'categories' créée", $steps);

    // 5. Table expenses
    $conn->exec("CREATE TABLE IF NOT EXISTS expenses (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id      INT UNSIGNED NOT NULL,
        category_id  INT UNSIGNED NOT NULL,
        name         VARCHAR(255)  NOT NULL,
        quantity     INT           NOT NULL DEFAULT 1,
        unit_price   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        frequency    INT           NOT NULL DEFAULT 1,
        paid         BOOLEAN       DEFAULT FALSE,
        payment_date DATE          NULL,
        notes        TEXT          NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_user     (user_id),
        INDEX idx_category (category_id),
        INDEX idx_paid     (paid),
        INDEX idx_expense_date (created_at),
        INDEX idx_payment_date (payment_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'expenses' créée avec index optimisés", $steps);

    // 6. Table wedding_dates (améliorée avec colonnes parrain)
    $conn->exec("CREATE TABLE IF NOT EXISTS wedding_dates (
        id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id             INT UNSIGNED NOT NULL UNIQUE,
        fiance_nom_complet  VARCHAR(200) NULL,
        fiancee_nom_complet VARCHAR(200) NULL,
        budget_total        DECIMAL(15,2) NULL DEFAULT 0.00,
        wedding_date        DATE NOT NULL,
        created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_wedding_user (user_id),
        INDEX idx_wedding_date (wedding_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'wedding_dates' créée avec colonnes pour les fiancés et budget", $steps);

    // 7. Table wedding_sponsors (NOUVELLE)
    $conn->exec("CREATE TABLE IF NOT EXISTS wedding_sponsors (
        id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        wedding_dates_id            INT UNSIGNED NOT NULL,
        sponsor_nom_complet         VARCHAR(200) NOT NULL,
        sponsor_conjoint_nom_complet VARCHAR(200) NOT NULL,
        email                       VARCHAR(150) NOT NULL,
        password_hash               VARCHAR(255) NOT NULL,
        telephone                   VARCHAR(20)  NULL,
        role                        ENUM('parrain', 'conseiller') NOT NULL DEFAULT 'parrain',
        statut                      ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
        created_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_sponsor_email (email),
        KEY idx_wedding_sponsor (wedding_dates_id),
        KEY idx_statut (statut),
        CONSTRAINT fk_sponsor_wedding FOREIGN KEY (wedding_dates_id) 
            REFERENCES wedding_dates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'wedding_sponsors' créée (gestion des parrains/conseillers)", $steps);

    // 8. Table sponsor_comments (NOUVELLE)
    $conn->exec("CREATE TABLE IF NOT EXISTS sponsor_comments (
        id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
        wedding_dates_id  INT UNSIGNED NOT NULL,
        sponsor_id        INT UNSIGNED NOT NULL,
        expense_id        INT UNSIGNED NULL,
        commentaire       TEXT NOT NULL,
        type_commentaire  ENUM('general', 'depense', 'suggestion') NOT NULL DEFAULT 'general',
        statut            ENUM('public', 'prive') NOT NULL DEFAULT 'public',
        created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_wedding_comments (wedding_dates_id),
        KEY idx_sponsor_comments (sponsor_id),
        KEY idx_expense_comments (expense_id),
        KEY idx_created (created_at),
        CONSTRAINT fk_comment_wedding FOREIGN KEY (wedding_dates_id) 
            REFERENCES wedding_dates(id) ON DELETE CASCADE,
        CONSTRAINT fk_comment_sponsor FOREIGN KEY (sponsor_id) 
            REFERENCES wedding_sponsors(id) ON DELETE CASCADE,
        CONSTRAINT fk_comment_expense FOREIGN KEY (expense_id) 
            REFERENCES expenses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'sponsor_comments' créée (commentaires des parrains)", $steps);

    // 9. Table sponsor_activity_log (NOUVELLE)
    $conn->exec("CREATE TABLE IF NOT EXISTS sponsor_activity_log (
        id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
        sponsor_id       INT UNSIGNED NOT NULL,
        wedding_dates_id INT UNSIGNED NOT NULL,
        action_type      ENUM('connexion', 'consultation', 'commentaire', 'deconnexion') NOT NULL,
        details          TEXT NULL,
        ip_address       VARCHAR(45) NULL,
        user_agent       VARCHAR(255) NULL,
        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_sponsor_activity (sponsor_id, created_at),
        KEY idx_wedding_activity (wedding_dates_id, created_at),
        KEY idx_action_type (action_type),
        CONSTRAINT fk_activity_sponsor FOREIGN KEY (sponsor_id) 
            REFERENCES wedding_sponsors(id) ON DELETE CASCADE,
        CONSTRAINT fk_activity_wedding FOREIGN KEY (wedding_dates_id) 
            REFERENCES wedding_dates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'sponsor_activity_log' créée (journal d'activité des parrains)", $steps);

    // 10. Table notifications (NOUVELLE)
    $conn->exec("CREATE TABLE IF NOT EXISTS notifications (
        id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id          INT UNSIGNED NOT NULL,
        wedding_dates_id INT UNSIGNED NULL,
        type_notification VARCHAR(50) NOT NULL,
        message          TEXT NOT NULL,
        is_read          TINYINT(1) NOT NULL DEFAULT 0,
        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_notif (user_id, is_read),
        KEY idx_wedding_notif (wedding_dates_id),
        KEY idx_created (created_at),
        CONSTRAINT fk_notif_user FOREIGN KEY (user_id) 
            REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_notif_wedding FOREIGN KEY (wedding_dates_id) 
            REFERENCES wedding_dates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    step("Table 'notifications' créée (système de notifications)", $steps);

    // 11. Catégories par défaut
    $cats = [
        ['Connaissance',         '#3498db', 'fas fa-handshake',           1],
        ['Dot',                  '#9b59b6', 'fas fa-gift',                2],
        ['Mariage civil',        '#e74c3c', 'fas fa-landmark',            3],
        ['Bénédiction nuptiale', '#2ecc71', 'fas fa-church',              4],
        ['Logistique',           '#1abc9c', 'fas fa-truck',               5],
        ['Réception',            '#f39c12', 'fas fa-glass-cheers',        6],
        ['Coût indirect',        '#95a5a6', 'fas fa-exclamation-triangle',7],
    ];
    $stmtCat = $conn->prepare("INSERT IGNORE INTO categories (name,color,icon,display_order) VALUES (?,?,?,?)");
    foreach ($cats as $c) $stmtCat->execute($c);
    step("Catégories de base insérées (" . count($cats) . ")", $steps);

    // 12. Utilisateur admin par défaut
    $adminPass = password_hash('Admin@1312', PASSWORD_DEFAULT);
    $stmtUser  = $conn->prepare("INSERT IGNORE INTO users (username,email,password,full_name,role) VALUES (?,?,?,?,?)");
    $stmtUser->execute(['Administrateur', 'liferopro@gmail.com', $adminPass, 'Administrateur Principal', 'admin']);
    step("Compte admin créé (login: Administrateur / pass: Admin@1312)", $steps);

    // 13. Récupérer l'ID de l'admin
    $uid = (int)$conn->query("SELECT id FROM users WHERE username='Administrateur' LIMIT 1")->fetchColumn();
    
    // 14. Créer une entrée wedding_dates pour l'admin
    $weddingDate = date('Y-m-d', strtotime('+6 months'));
    $stmtWedding = $conn->prepare("INSERT IGNORE INTO wedding_dates (user_id, fiance_nom_complet, fiancee_nom_complet, budget_total, wedding_date) VALUES (?, ?, ?, ?, ?)");
    $stmtWedding->execute([$uid, 'Rom', 'Geral', 1500000.00, $weddingDate]);
    $wedding_dates_id = (int)$conn->lastInsertId();
    step("Date de mariage créée pour l'administrateur", $steps);

    // 15. Créer des parrains de démonstration
    $sponsorPass = password_hash('Sponsor@123', PASSWORD_DEFAULT);
    $stmtSponsor = $conn->prepare("INSERT IGNORE INTO wedding_sponsors 
        (wedding_dates_id, sponsor_nom_complet, sponsor_conjoint_nom_complet, email, password_hash, telephone, role, statut) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $demoSponsors = [
        [$wedding_dates_id, 'Jonas AGBOKO', 'Marie AGBOKO', 'jonas.marie@example.com', $sponsorPass, '612345678', 'parrain', 'actif'],
    ];
    
    foreach ($demoSponsors as $s) {
        $stmtSponsor->execute($s);
    }
    step("Parrains de démonstration créés (" . count($demoSponsors) . ")", $steps);

    // 16. Données de démonstration pour expenses
    $catIds = [];
    foreach ($conn->query("SELECT id, display_order FROM categories ORDER BY display_order") as $row) {
        $catIds[$row['display_order']] = $row['id'];
    }

    $demo = [
        // Connaissance (1)
        [$uid,$catIds[1],'Enveloppe symbolique',        2,  2000,1,1],
        [$uid,$catIds[1],'Boissons (jus de raisins)',   2,  5000,1,1],
        [$uid,$catIds[1],'Déplacement',                 1,  5000,1,1],
        // Dot (2)
        [$uid,$catIds[2],'Bible',                       1,  6000,1,0],
        [$uid,$catIds[2],'Valise',                      1, 10000,1,0],
        [$uid,$catIds[2],'Pagne vlisco demi-pièce',     2, 27000,1,0],
        [$uid,$catIds[2],'Pagne côte d\'ivoire',        5,  6500,1,0],
        [$uid,$catIds[2],'Pagne Ghana demi-pièce',      4,  6500,1,0],
        [$uid,$catIds[2],'Ensemble chaînes',            3,  3000,1,0],
        [$uid,$catIds[2],'Chaussures',                  3,  3000,1,0],
        [$uid,$catIds[2],'Sac à main',                  2,  3500,1,0],
        [$uid,$catIds[2],'Montre et bracelet',          2,  3000,1,0],
        [$uid,$catIds[2],'Série de bols',               3,  5500,1,0],
        [$uid,$catIds[2],'Assiettes verre (demi-doz.)', 2,  4800,1,0],
        [$uid,$catIds[2],'Assiettes plastique (doz.)',  2,  3000,1,0],
        [$uid,$catIds[2],'Série de casseroles',         1,  7000,1,0],
        [$uid,$catIds[2],'Marmites (1-3 kg)',           1, 11000,1,0],
        [$uid,$catIds[2],'Ustensiles de cuisine',       1,  8000,1,0],
        [$uid,$catIds[2],'Gaz + accessoires',           1, 25000,1,0],
        [$uid,$catIds[2],'Seau soins corporels',        1, 10000,1,0],
        [$uid,$catIds[2],'Enveloppe fille',             1,100000,1,0],
        [$uid,$catIds[2],'Enveloppe famille',           1, 25000,1,0],
        [$uid,$catIds[2],'Enveloppe frères/sœurs',      1, 10000,1,0],
        [$uid,$catIds[2],'Liqueurs',                    2, 10000,1,0],
        [$uid,$catIds[2],'Jus de raisins',             10,  2500,1,0],
        [$uid,$catIds[2],'Collation spirituelle',       1, 45000,1,0],
        // Mairie (3)
        [$uid,$catIds[3],'Frais dossier mairie',        1, 50000,1,0],
        [$uid,$catIds[3],'Petite réception mairie',     1, 50000,1,0],
        // Église (4)
        [$uid,$catIds[4],'Robe de mariée',              1, 20000,1,0],
        [$uid,$catIds[4],'Costume marié',               1, 25000,1,0],
        [$uid,$catIds[4],'Chaussures mariés',           2, 25000,1,0],
        [$uid,$catIds[4],'Alliances',                   1, 15000,1,0],
        [$uid,$catIds[4],'Tenues cortège (homme)',      3, 15000,1,0],
        [$uid,$catIds[4],'Tenues cortège (femme)',      4, 15000,1,0],
        // Logistique (5)
        [$uid,$catIds[5],'Location de salle',           1,150000,1,0],
        [$uid,$catIds[5],'Location de véhicule',        2, 35000,1,0],
        [$uid,$catIds[5],'Carburant',                  20,   680,1,0],
        [$uid,$catIds[5],'Prise de vue (photo/vidéo)', 1, 30000,1,0],
        [$uid,$catIds[5],'Sonorisation',                1, 20000,1,0],
        [$uid,$catIds[5],'Conception flyers/programmes',1,  2000,1,0],
        // Réception (6)
        [$uid,$catIds[6],'Boissons (200 personnes)',  200,   600,1,0],
        [$uid,$catIds[6],'Poulets',                    30,  2500,1,0],
        [$uid,$catIds[6],'Porc',                        1, 30000,1,0],
        [$uid,$catIds[6],'Poissons',                    2, 35000,1,0],
        [$uid,$catIds[6],'Sacs de riz',                 1, 32000,1,0],
        [$uid,$catIds[6],'Farine d\'igname',           20,   500,1,0],
        [$uid,$catIds[6],'Maïs pour akassa',           20,   200,1,0],
        [$uid,$catIds[6],'Ingrédients cuisine',         1, 30000,1,0],
        [$uid,$catIds[6],'Gâteau de mariage',           1, 25000,1,0],
        // Coût indirect (7)
        [$uid,$catIds[7],'Imprévus divers',             1, 75000,1,0],
    ];

    $stmtExp = $conn->prepare("INSERT IGNORE INTO expenses
        (user_id,category_id,name,quantity,unit_price,frequency,paid) VALUES (?,?,?,?,?,?,?)");
    foreach ($demo as $d) $stmtExp->execute($d);
    step("Données de démonstration insérées (" . count($demo) . " dépenses)", $steps);

    // 17. Création des VUES
    $conn->exec("DROP VIEW IF EXISTS v_wedding_stats_for_sponsors");
    $conn->exec("CREATE OR REPLACE VIEW v_wedding_stats_for_sponsors AS
        SELECT 
            wd.id AS wedding_dates_id,
            wd.user_id,
            wd.fiance_nom_complet,
            wd.fiancee_nom_complet,
            wd.wedding_date,
            wd.budget_total,
            COUNT(DISTINCT e.id) AS nombre_depenses,
            COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0) AS total_depense,
            (wd.budget_total - COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0)) AS budget_restant,
            CASE 
                WHEN wd.budget_total > 0 THEN 
                    ROUND((COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0) / wd.budget_total * 100), 2)
                ELSE 0 
            END AS pourcentage_utilise,
            SUM(CASE WHEN e.paid = 1 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END) AS total_paye,
            SUM(CASE WHEN e.paid = 0 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END) AS total_non_paye
        FROM wedding_dates wd
        LEFT JOIN expenses e ON wd.user_id = e.user_id
        GROUP BY wd.id, wd.user_id, wd.fiance_nom_complet, wd.fiancee_nom_complet, wd.wedding_date, wd.budget_total");
    step("Vue 'v_wedding_stats_for_sponsors' créée", $steps);

    $conn->exec("DROP VIEW IF EXISTS v_expenses_with_details");
    $conn->exec("CREATE OR REPLACE VIEW v_expenses_with_details AS
        SELECT 
            e.id,
            e.user_id,
            e.category_id,
            c.name AS category_name,
            c.icon AS category_icon,
            c.color AS category_color,
            e.name AS expense_name,
            e.quantity,
            e.unit_price,
            e.frequency,
            (e.quantity * e.unit_price * e.frequency) AS montant_total,
            e.paid,
            e.payment_date,
            e.notes,
            e.created_at,
            e.updated_at
        FROM expenses e
        INNER JOIN categories c ON e.category_id = c.id");
    step("Vue 'v_expenses_with_details' créée", $steps);

    // 18. Création des PROCÉDURES STOCKÉES
    $conn->exec("DROP PROCEDURE IF EXISTS sp_notify_new_sponsor_comment");
    $conn->exec("
        CREATE PROCEDURE sp_notify_new_sponsor_comment(
            IN p_wedding_dates_id INT UNSIGNED,
            IN p_sponsor_id INT UNSIGNED,
            IN p_commentaire TEXT
        )
        BEGIN
            DECLARE v_user_id INT UNSIGNED;
            DECLARE v_notification_id INT UNSIGNED;
            DECLARE v_sponsor_nom VARCHAR(200);
            
            SELECT user_id INTO v_user_id 
            FROM wedding_dates 
            WHERE id = p_wedding_dates_id 
            LIMIT 1;
            
            SELECT sponsor_nom_complet INTO v_sponsor_nom
            FROM wedding_sponsors
            WHERE id = p_sponsor_id
            LIMIT 1;
            
            IF v_user_id IS NOT NULL THEN
                INSERT INTO notifications (
                    user_id,
                    wedding_dates_id,
                    type_notification,
                    message,
                    created_at
                ) VALUES (
                    v_user_id,
                    p_wedding_dates_id,
                    'nouveau_commentaire_parrain',
                    CONCAT('Nouveau commentaire de ', v_sponsor_nom, ': ', LEFT(p_commentaire, 100), 
                           CASE WHEN LENGTH(p_commentaire) > 100 THEN '...' ELSE '' END),
                    NOW()
                );
                
                SET v_notification_id = LAST_INSERT_ID();
                SELECT v_notification_id AS notification_id, 'Notification créée' AS status;
            ELSE
                SELECT 0 AS notification_id, 'Mariage non trouvé' AS status;
            END IF;
        END");
    step("Procédure 'sp_notify_new_sponsor_comment' créée", $steps);

    $conn->exec("DROP PROCEDURE IF EXISTS sp_get_wedding_summary_for_sponsor");
    $conn->exec("
        CREATE PROCEDURE sp_get_wedding_summary_for_sponsor(
            IN p_sponsor_id INT UNSIGNED
        )
        BEGIN
            DECLARE v_wedding_dates_id INT UNSIGNED;
            
            SELECT wedding_dates_id INTO v_wedding_dates_id
            FROM wedding_sponsors
            WHERE id = p_sponsor_id AND statut = 'actif'
            LIMIT 1;
            
            IF v_wedding_dates_id IS NOT NULL THEN
                SELECT * FROM v_wedding_stats_for_sponsors
                WHERE wedding_dates_id = v_wedding_dates_id;
                
                SELECT 
                    e.*,
                    c.name AS category_name,
                    c.color AS category_color
                FROM expenses e
                INNER JOIN categories c ON e.category_id = c.id
                INNER JOIN wedding_dates wd ON e.user_id = wd.user_id
                WHERE wd.id = v_wedding_dates_id
                ORDER BY e.created_at DESC
                LIMIT 20;
            ELSE
                SELECT 'Parrain non trouvé ou inactif' AS error_message;
            END IF;
        END");
    step("Procédure 'sp_get_wedding_summary_for_sponsor' créée", $steps);

    // 19. Réactiver les vérifications de clés étrangères
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    step("Vérifications des clés étrangères réactivées", $steps);

    echo "<br><strong style='color:green;font-size:1.2rem'>✅ Installation terminée avec succès !</strong><br>";
    echo "<strong style='color:#8b4f8d;'>🎯 Système parrain et améliorations installés</strong><br><br>";
    
    echo "<div style='background:#f0e6f0;padding:15px;border-radius:8px;margin-bottom:20px;'>";
    echo "<strong>📋 Récapitulatif des nouvelles fonctionnalités :</strong><br>";
    echo "• Gestion des parrains et conseillers<br>";
    echo "• Commentaires et suggestions des parrains<br>";
    echo "• Journal d'activité complet<br>";
    echo "• Notifications en temps réel<br>";
    echo "• Vues statistiques pour les parrains<br>";
    echo "• Budget total et suivi financier<br>";
    echo "</div>";
    
    echo "<div style='background:#e8f4e8;padding:15px;border-radius:8px;margin-bottom:20px;'>";
    echo "<strong>🔐 Accès démo :</strong><br>";
    echo "• Admin : Administrateur / Admin@1312<br>";
    echo "• Parrain : jonas.marie@example.com / Sponsor@123<br>";
    echo "</div>";
    
    echo "<a href='index.php' style='display:inline-block;margin-top:15px;padding:12px 30px;background:#8b4f8d;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>🚀 Accéder à l'application</a>";
    echo " &nbsp; ";
    echo "<a href='auth/login.php' style='display:inline-block;margin-top:15px;padding:12px 30px;background:#5d2f5f;color:white;text-decoration:none;border-radius:8px;font-weight:600;'>🔑 Se connecter</a>";

} catch(PDOException $e) {
    fail("Erreur MySQL : " . $e->getMessage(), $errors);
    
    // Tentative de réactivation des clés étrangères en cas d'erreur
    try {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch(Exception $ex) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Installation — Budget Mariage PJPM (Système Parrain)</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#faf8f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;max-width:800px;width:100%;border-radius:16px;box-shadow:0 8px 30px rgba(139,79,141,.15);overflow:hidden}
.box-header{background:linear-gradient(135deg,#8b4f8d,#5d2f5f);color:white;padding:30px;text-align:center}
.box-header h1{font-size:1.8rem;margin-bottom:8px}
.box-header p{opacity:.9}
.box-body{padding:30px;line-height:1.8;font-size:.95rem}
.error-box{background:#fdeded;border-left:4px solid #e74c3c;padding:15px;margin-bottom:20px;border-radius:4px}
.success-badge{display:inline-block;background:rgba(46,204,113,0.2);color:#27ae60;padding:5px 12px;border-radius:20px;font-size:0.85rem;margin-top:10px}
</style>
</head>
<body>
<div class="box">
<div class="box-header">
  <h1>💍 Budget Mariage PJPM</h1>
  <p>Installation avancée avec système de parrainage</p>
  <?php if(empty($errors)): ?>
  <span class="success-badge">✅ Version 3.0 - Améliorations parrain</span>
  <?php endif; ?>
</div>
<div class="box-body">
  <?php if(!empty($errors)): ?>
    <div class="error-box">
      <strong style="color:#c0392b;">⚠️ Des erreurs sont survenues :</strong>
      <ul style="margin-top:10px;list-style-type:none;">
        <?php foreach($errors as $error): ?>
          <li style="color:#e74c3c;margin-bottom:5px;"><?= $error ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>
</div>
</body>
</html>