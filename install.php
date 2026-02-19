#!/usr/bin/env php
<?php
/**
 * Script d'importation de la base de données
 * Utilisation : php scripts/import-db.php
 */

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('MYSQL_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'wedding';
$sqlFile = __DIR__ . 'includes/database.sql';

echo "🔧 Importation de la base de données WedPlan\n";
echo "============================================\n";

if (!file_exists($sqlFile)) {
    die("❌ Fichier SQL introuvable : $sqlFile\n");
}

try {
    // Connexion sans sélectionner de base
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la base si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données '$dbName' prête\n";
    
    // Sélectionner la base
    $pdo->exec("USE `$dbName`");
    
    // Vérifier si des tables existent
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "⚠️  Des tables existent déjà (" . count($tables) . " tables)\n";
        echo "Voulez-vous réinitialiser la base ? (oui/non) : ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        
        if (strtolower($line) !== 'oui') {
            echo "❌ Importation annulée\n";
            exit(0);
        }
        
        // Supprimer toutes les tables
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
            echo "   Table supprimée : $table\n";
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Lire et exécuter le fichier SQL
    $sql = file_get_contents($sqlFile);
    
    // Diviser les requêtes (gestion des procédures stockées)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                $count++;
            } catch (PDOException $e) {
                echo "⚠️  Erreur sur une requête : " . $e->getMessage() . "\n";
                echo "Requête : " . substr($query, 0, 100) . "...\n";
            }
        }
    }
    
    echo "✅ Importation terminée : $count requêtes exécutées\n";
    
    // Enregistrer la version
    $pdo->exec("CREATE TABLE IF NOT EXISTS db_version (
        id INT PRIMARY KEY AUTO_INCREMENT,
        version VARCHAR(50),
        imported_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $stmt = $pdo->prepare("INSERT INTO db_version (version) VALUES (?)");
    $stmt->execute(['1.0.0']);
    
} catch (PDOException $e) {
    die("❌ Erreur de connexion : " . $e->getMessage() . "\n");
}