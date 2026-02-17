<?php
// Chargement du fichier SQL
$sql = file_get_contents('includes/database.sql');

// Vérification si le fichier existe
if ($sql === false) {
    die('Erreur lors du chargement du fichier SQL.');
}

// Exécution des requêtes SQL
try {
    // Connexion à la base de données
    $dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=' . getenv('DB_CHARSET');
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Exécution du fichier SQL
    $pdo->exec($sql);
    echo "Base de données initialisée avec succès!";
} catch (PDOException $e) {
    echo "Erreur lors de l'exécution des requêtes SQL: " . $e->getMessage();
}
