-- ============================================================
--  wedding_install.sql
--  Base de données : wedding
--  Généré depuis install.php — Budget Mariage PJPM v3.0
--  Inclut : tables, vues, procédures stockées, données de démo
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Création / sélection de la base de données
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `wedding`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `wedding`;

-- ------------------------------------------------------------
-- 2. Table : users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)      NOT NULL UNIQUE,
    `email`      VARCHAR(100)     NOT NULL UNIQUE,
    `password`   VARCHAR(255)     NOT NULL,
    `full_name`  VARCHAR(100)     NULL,
    `role`       ENUM('admin','user') DEFAULT 'user',
    `created_at` DATETIME         DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME         NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Table : categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`          VARCHAR(255) NOT NULL UNIQUE,
    `color`         VARCHAR(7)   DEFAULT '#3498db',
    `icon`          VARCHAR(50)  DEFAULT 'fas fa-folder',
    `display_order` INT          DEFAULT 0,
    `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Table : expenses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
    `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED     NOT NULL,
    `category_id`  INT UNSIGNED     NOT NULL,
    `name`         VARCHAR(255)     NOT NULL,
    `quantity`     INT              NOT NULL DEFAULT 1,
    `unit_price`   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `frequency`    INT              NOT NULL DEFAULT 1,
    `paid`         BOOLEAN          DEFAULT FALSE,
    `payment_date` DATE             NULL,
    `notes`        TEXT             NULL,
    `created_at`   DATETIME         DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)     REFERENCES `users`(`id`)       ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)  ON DELETE CASCADE,
    INDEX `idx_user`         (`user_id`),
    INDEX `idx_category`     (`category_id`),
    INDEX `idx_paid`         (`paid`),
    INDEX `idx_expense_date` (`created_at`),
    INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Table : wedding_dates
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wedding_dates` (
    `id`                  INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT UNSIGNED   NOT NULL UNIQUE,
    `fiance_nom_complet`  VARCHAR(200)   NULL,
    `fiancee_nom_complet` VARCHAR(200)   NULL,
    `budget_total`        DECIMAL(15,2)  NULL DEFAULT 0.00,
    `wedding_date`        DATE           NOT NULL,
    `created_at`          DATETIME       DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_wedding_user` (`user_id`),
    INDEX `idx_wedding_date` (`wedding_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Table : wedding_sponsors
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wedding_sponsors` (
    `id`                           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `wedding_dates_id`             INT UNSIGNED  NOT NULL,
    `sponsor_nom_complet`          VARCHAR(200)  NOT NULL,
    `sponsor_conjoint_nom_complet` VARCHAR(200)  NOT NULL,
    `email`                        VARCHAR(150)  NOT NULL,
    `password_hash`                VARCHAR(255)  NOT NULL,
    `telephone`                    VARCHAR(20)   NULL,
    `role`                         ENUM('parrain','conseiller') NOT NULL DEFAULT 'parrain',
    `statut`                       ENUM('actif','inactif')      NOT NULL DEFAULT 'actif',
    `created_at`                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sponsor_email`   (`email`),
    KEY `idx_wedding_sponsor`       (`wedding_dates_id`),
    KEY `idx_statut`                (`statut`),
    CONSTRAINT `fk_sponsor_wedding`
        FOREIGN KEY (`wedding_dates_id`)
        REFERENCES `wedding_dates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. Table : sponsor_comments
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sponsor_comments` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `wedding_dates_id` INT UNSIGNED NOT NULL,
    `sponsor_id`       INT UNSIGNED NOT NULL,
    `expense_id`       INT UNSIGNED NULL,
    `commentaire`      TEXT         NOT NULL,
    `type_commentaire` ENUM('general','depense','suggestion') NOT NULL DEFAULT 'general',
    `statut`           ENUM('public','prive')                 NOT NULL DEFAULT 'public',
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_wedding_comments` (`wedding_dates_id`),
    KEY `idx_sponsor_comments` (`sponsor_id`),
    KEY `idx_expense_comments` (`expense_id`),
    KEY `idx_created`          (`created_at`),
    CONSTRAINT `fk_comment_wedding`
        FOREIGN KEY (`wedding_dates_id`)
        REFERENCES `wedding_dates`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comment_sponsor`
        FOREIGN KEY (`sponsor_id`)
        REFERENCES `wedding_sponsors`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comment_expense`
        FOREIGN KEY (`expense_id`)
        REFERENCES `expenses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. Table : sponsor_activity_log
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sponsor_activity_log` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sponsor_id`       INT UNSIGNED NOT NULL,
    `wedding_dates_id` INT UNSIGNED NOT NULL,
    `action_type`      ENUM('connexion','consultation','commentaire','deconnexion') NOT NULL,
    `details`          TEXT         NULL,
    `ip_address`       VARCHAR(45)  NULL,
    `user_agent`       VARCHAR(255) NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sponsor_activity` (`sponsor_id`, `created_at`),
    KEY `idx_wedding_activity` (`wedding_dates_id`, `created_at`),
    KEY `idx_action_type`      (`action_type`),
    CONSTRAINT `fk_activity_sponsor`
        FOREIGN KEY (`sponsor_id`)
        REFERENCES `wedding_sponsors`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_wedding`
        FOREIGN KEY (`wedding_dates_id`)
        REFERENCES `wedding_dates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. Table : notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED NOT NULL,
    `wedding_dates_id`  INT UNSIGNED NULL,
    `type_notification` VARCHAR(50)  NOT NULL,
    `message`           TEXT         NOT NULL,
    `is_read`           TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_notif`    (`user_id`, `is_read`),
    KEY `idx_wedding_notif` (`wedding_dates_id`),
    KEY `idx_created`       (`created_at`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_wedding`
        FOREIGN KEY (`wedding_dates_id`)
        REFERENCES `wedding_dates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES D'INSERTION
-- ============================================================

-- ------------------------------------------------------------
-- 10. Catégories de base
-- ------------------------------------------------------------
INSERT IGNORE INTO `categories` (`name`, `color`, `icon`, `display_order`) VALUES
    ('Connaissance',         '#3498db', 'fas fa-handshake',            1),
    ('Dot',                  '#9b59b6', 'fas fa-gift',                 2),
    ('Mariage civil',        '#e74c3c', 'fas fa-landmark',             3),
    ('Bénédiction nuptiale', '#2ecc71', 'fas fa-church',               4),
    ('Logistique',           '#1abc9c', 'fas fa-truck',                5),
    ('Réception',            '#f39c12', 'fas fa-glass-cheers',         6),
    ('Coût indirect et impévu',        '#95a5a6', 'fas fa-exclamation-triangle', 7);

-- ------------------------------------------------------------
-- 11. Compte administrateur
--     login    : Administrateur
--     password : Admin@1312  (hash bcrypt ci-dessous)
-- ------------------------------------------------------------
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
    (
        'Administrateur',
        'liferopro@gmail.com',
        '$2y$10$exampleHashReplaceWithRealBcryptOfAdmin1312xxxxxxxxxx',
        'Administrateur Principal',
        'admin'
    );
-- NOTE : remplacez le hash ci-dessus par le résultat réel de :
--        password_hash('Admin@1312', PASSWORD_DEFAULT)

-- ------------------------------------------------------------
-- 12. Date de mariage pour l'administrateur
--     (wedding_date fixée à +6 mois depuis la date d'install)
-- ------------------------------------------------------------
INSERT IGNORE INTO `wedding_dates`
    (`user_id`, `fiance_nom_complet`, `fiancee_nom_complet`, `budget_total`, `wedding_date`)
VALUES
    (
        (SELECT `id` FROM `users` WHERE `username` = 'Administrateur' LIMIT 1),
        'Rom',
        'Geral',
        1500000.00,
        DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
    );

-- ------------------------------------------------------------
-- 13. Parrain de démonstration
--     login    : jonas.marie@example.com
--     password : Sponsor@123  (hash bcrypt ci-dessous)
-- ------------------------------------------------------------
INSERT IGNORE INTO `wedding_sponsors`
    (`wedding_dates_id`, `sponsor_nom_complet`, `sponsor_conjoint_nom_complet`,
     `email`, `password_hash`, `telephone`, `role`, `statut`)
VALUES
    (
        (SELECT `id` FROM `wedding_dates`
         WHERE `user_id` = (SELECT `id` FROM `users` WHERE `username` = 'Administrateur' LIMIT 1)
         LIMIT 1),
        'Jonas AGBOKO',
        'Marie AGBOKO',
        'jonas.marie@example.com',
        '$2y$10$exampleHashReplaceWithRealBcryptOfSponsor123xxxxxxxx',
        '612345678',
        'parrain',
        'actif'
    );
-- NOTE : remplacez le hash ci-dessus par le résultat réel de :
--        password_hash('Sponsor@123', PASSWORD_DEFAULT)

-- ------------------------------------------------------------
-- 14. Dépenses de démonstration
-- ------------------------------------------------------------
INSERT IGNORE INTO `expenses`
    (`user_id`, `category_id`, `name`, `quantity`, `unit_price`, `frequency`, `paid`)
SELECT
    u.id,
    c.id,
    d.name,
    d.quantity,
    d.unit_price,
    d.frequency,
    d.paid
FROM (
    -- ---- Connaissance ----
    SELECT 'Connaissance' AS cat, 'Enveloppe symbolique'        AS name, 2   AS quantity, 2000   AS unit_price, 1 AS frequency, 1 AS paid UNION ALL
    SELECT 'Connaissance',        'Boissons (jus de raisins)',             2,              5000,                 1,              1 UNION ALL
    SELECT 'Connaissance',        'Déplacement',                           1,              5000,                 1,              1 UNION ALL
    -- ---- Dot ----
    SELECT 'Dot', 'Bible',                       1,   6000, 1, 0 UNION ALL
    SELECT 'Dot', 'Valise',                      1,  10000, 1, 0 UNION ALL
    SELECT 'Dot', 'Pagne vlisco demi-pièce',     2,  27000, 1, 0 UNION ALL
    SELECT 'Dot', 'Pagne côte d''ivoire',        5,   6500, 1, 0 UNION ALL
    SELECT 'Dot', 'Pagne Ghana demi-pièce',      4,   6500, 1, 0 UNION ALL
    SELECT 'Dot', 'Ensemble chaînes',            3,   3000, 1, 0 UNION ALL
    SELECT 'Dot', 'Chaussures',                  3,   3000, 1, 0 UNION ALL
    SELECT 'Dot', 'Sac à main',                  2,   3500, 1, 0 UNION ALL
    SELECT 'Dot', 'Montre et bracelet',          2,   3000, 1, 0 UNION ALL
    SELECT 'Dot', 'Série de bols',               3,   5500, 1, 0 UNION ALL
    SELECT 'Dot', 'Assiettes verre (demi-doz.)', 2,   4800, 1, 0 UNION ALL
    SELECT 'Dot', 'Assiettes plastique (doz.)',  2,   3000, 1, 0 UNION ALL
    SELECT 'Dot', 'Série de casseroles',         1,   7000, 1, 0 UNION ALL
    SELECT 'Dot', 'Marmites (1-3 kg)',           1,  11000, 1, 0 UNION ALL
    SELECT 'Dot', 'Ustensiles de cuisine',       1,   8000, 1, 0 UNION ALL
    SELECT 'Dot', 'Gaz + accessoires',           1,  25000, 1, 0 UNION ALL
    SELECT 'Dot', 'Seau soins corporels',        1,  10000, 1, 0 UNION ALL
    SELECT 'Dot', 'Enveloppe fille',             1, 100000, 1, 0 UNION ALL
    SELECT 'Dot', 'Enveloppe famille',           1,  25000, 1, 0 UNION ALL
    SELECT 'Dot', 'Enveloppe frères/sœurs',      1,  10000, 1, 0 UNION ALL
    SELECT 'Dot', 'Liqueurs',                    2,  10000, 1, 0 UNION ALL
    SELECT 'Dot', 'Jus de raisins',             10,   2500, 1, 0 UNION ALL
    SELECT 'Dot', 'Collation spirituelle',       1,  45000, 1, 0 UNION ALL
    -- ---- Mariage civil ----
    SELECT 'Mariage civil', 'Frais dossier mairie',    1, 50000, 1, 0 UNION ALL
    SELECT 'Mariage civil', 'Petite réception mairie', 1, 50000, 1, 0 UNION ALL
    -- ---- Bénédiction nuptiale ----
    SELECT 'Bénédiction nuptiale', 'Robe de mariée',         1, 20000, 1, 0 UNION ALL
    SELECT 'Bénédiction nuptiale', 'Costume marié',          1, 25000, 1, 0 UNION ALL
    SELECT 'Bénédiction nuptiale', 'Chaussures mariés',      2, 25000, 1, 0 UNION ALL
    SELECT 'Bénédiction nuptiale', 'Alliances',              1, 15000, 1, 0 UNION ALL
    SELECT 'Bénédiction nuptiale', 'Tenues cortège (homme)', 3, 15000, 1, 0 UNION ALL
    SELECT 'Bénédiction nuptiale', 'Tenues cortège (femme)', 4, 15000, 1, 0 UNION ALL
    -- ---- Logistique ----
    SELECT 'Logistique', 'Location de salle',           1, 150000, 1, 0 UNION ALL
    SELECT 'Logistique', 'Location de véhicule',        2,  35000, 1, 0 UNION ALL
    SELECT 'Logistique', 'Carburant',                  20,    680, 1, 0 UNION ALL
    SELECT 'Logistique', 'Prise de vue (photo/vidéo)', 1,  30000, 1, 0 UNION ALL
    SELECT 'Logistique', 'Sonorisation',                1,  20000, 1, 0 UNION ALL
    SELECT 'Logistique', 'Conception flyers/programmes',1,   2000, 1, 0 UNION ALL
    -- ---- Réception ----
    SELECT 'Réception', 'Boissons (200 personnes)', 200,   600, 1, 0 UNION ALL
    SELECT 'Réception', 'Poulets',                   30,  2500, 1, 0 UNION ALL
    SELECT 'Réception', 'Porc',                       1, 30000, 1, 0 UNION ALL
    SELECT 'Réception', 'Poissons',                   2, 35000, 1, 0 UNION ALL
    SELECT 'Réception', 'Sacs de riz',                1, 32000, 1, 0 UNION ALL
    SELECT 'Réception', 'Farine d''igname',          20,   500, 1, 0 UNION ALL
    SELECT 'Réception', 'Maïs pour akassa',          20,   200, 1, 0 UNION ALL
    SELECT 'Réception', 'Ingrédients cuisine',        1, 30000, 1, 0 UNION ALL
    SELECT 'Réception', 'Gâteau de mariage',          1, 25000, 1, 0 UNION ALL
    -- ---- Coût indirect ----
    SELECT 'Coût indirect', 'Imprévus divers', 1, 75000, 1, 0
) AS d
INNER JOIN `categories` c ON c.name = d.cat
CROSS JOIN (SELECT `id` FROM `users` WHERE `username` = 'Administrateur' LIMIT 1) AS u;

-- ============================================================
-- VUES
-- ============================================================

-- ------------------------------------------------------------
-- 15. Vue : v_wedding_stats_for_sponsors
-- ------------------------------------------------------------
DROP VIEW IF EXISTS `v_wedding_stats_for_sponsors`;
CREATE OR REPLACE VIEW `v_wedding_stats_for_sponsors` AS
    SELECT
        wd.id                                                                       AS wedding_dates_id,
        wd.user_id,
        wd.fiance_nom_complet,
        wd.fiancee_nom_complet,
        wd.wedding_date,
        wd.budget_total,
        COUNT(DISTINCT e.id)                                                        AS nombre_depenses,
        COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0)                  AS total_depense,
        (wd.budget_total - COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0)) AS budget_restant,
        CASE
            WHEN wd.budget_total > 0 THEN
                ROUND((COALESCE(SUM(e.quantity * e.unit_price * e.frequency), 0) / wd.budget_total * 100), 2)
            ELSE 0
        END                                                                         AS pourcentage_utilise,
        SUM(CASE WHEN e.paid = 1 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END) AS total_paye,
        SUM(CASE WHEN e.paid = 0 THEN (e.quantity * e.unit_price * e.frequency) ELSE 0 END) AS total_non_paye
    FROM `wedding_dates` wd
    LEFT JOIN `expenses` e ON wd.user_id = e.user_id
    GROUP BY wd.id, wd.user_id, wd.fiance_nom_complet, wd.fiancee_nom_complet, wd.wedding_date, wd.budget_total;

-- ------------------------------------------------------------
-- 16. Vue : v_expenses_with_details
-- ------------------------------------------------------------
DROP VIEW IF EXISTS `v_expenses_with_details`;
CREATE OR REPLACE VIEW `v_expenses_with_details` AS
    SELECT
        e.id,
        e.user_id,
        e.category_id,
        c.name  AS category_name,
        c.icon  AS category_icon,
        c.color AS category_color,
        e.name  AS expense_name,
        e.quantity,
        e.unit_price,
        e.frequency,
        (e.quantity * e.unit_price * e.frequency) AS montant_total,
        e.paid,
        e.payment_date,
        e.notes,
        e.created_at,
        e.updated_at
    FROM `expenses` e
    INNER JOIN `categories` c ON e.category_id = c.id;

-- ============================================================
-- PROCÉDURES STOCKÉES
-- ============================================================

-- ------------------------------------------------------------
-- 17. Procédure : sp_notify_new_sponsor_comment
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_notify_new_sponsor_comment`;
DELIMITER $$
CREATE PROCEDURE `sp_notify_new_sponsor_comment`(
    IN p_wedding_dates_id INT UNSIGNED,
    IN p_sponsor_id       INT UNSIGNED,
    IN p_commentaire      TEXT
)
BEGIN
    DECLARE v_user_id        INT UNSIGNED;
    DECLARE v_notification_id INT UNSIGNED;
    DECLARE v_sponsor_nom    VARCHAR(200);

    SELECT user_id INTO v_user_id
    FROM `wedding_dates`
    WHERE id = p_wedding_dates_id
    LIMIT 1;

    SELECT sponsor_nom_complet INTO v_sponsor_nom
    FROM `wedding_sponsors`
    WHERE id = p_sponsor_id
    LIMIT 1;

    IF v_user_id IS NOT NULL THEN
        INSERT INTO `notifications` (
            user_id,
            wedding_dates_id,
            type_notification,
            message,
            created_at
        ) VALUES (
            v_user_id,
            p_wedding_dates_id,
            'nouveau_commentaire_parrain',
            CONCAT(
                'Nouveau commentaire de ', v_sponsor_nom, ': ',
                LEFT(p_commentaire, 100),
                CASE WHEN LENGTH(p_commentaire) > 100 THEN '...' ELSE '' END
            ),
            NOW()
        );

        SET v_notification_id = LAST_INSERT_ID();
        SELECT v_notification_id AS notification_id, 'Notification créée' AS status;
    ELSE
        SELECT 0 AS notification_id, 'Mariage non trouvé' AS status;
    END IF;
END$$
DELIMITER ;

-- ------------------------------------------------------------
-- 18. Procédure : sp_get_wedding_summary_for_sponsor
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `sp_get_wedding_summary_for_sponsor`;
DELIMITER $$
CREATE PROCEDURE `sp_get_wedding_summary_for_sponsor`(
    IN p_sponsor_id INT UNSIGNED
)
BEGIN
    DECLARE v_wedding_dates_id INT UNSIGNED;

    SELECT wedding_dates_id INTO v_wedding_dates_id
    FROM `wedding_sponsors`
    WHERE id = p_sponsor_id AND statut = 'actif'
    LIMIT 1;

    IF v_wedding_dates_id IS NOT NULL THEN
        SELECT * FROM `v_wedding_stats_for_sponsors`
        WHERE wedding_dates_id = v_wedding_dates_id;

        SELECT
            e.*,
            c.name  AS category_name,
            c.color AS category_color
        FROM `expenses` e
        INNER JOIN `categories`    c  ON e.category_id = c.id
        INNER JOIN `wedding_dates` wd ON e.user_id = wd.user_id
        WHERE wd.id = v_wedding_dates_id
        ORDER BY e.created_at DESC
        LIMIT 20;
    ELSE
        SELECT 'Parrain non trouvé ou inactif' AS error_message;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- Réactivation des clés étrangères
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================