<?php
// Remplis avec tes infos Railway
$host = getenv('MYSQL_HOST') ?: 'roundhouse.proxy.rlwy.net';
$port = getenv('MYSQL_PORT') ?: '3306';
$dbname = getenv('MYSQL_DATABASE') ?: 'railway';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Lire le fichier SQL
    $sql = file_get_contents('includes/database.sql');
    
    // Exécuter les requêtes
    $pdo->exec($sql);
    
    echo "✅ Base de données importée avec succès !";
} catch (PDOException $e) {
    die("❌ Erreur : " . $e->getMessage());
}