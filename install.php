<?php
$host = getenv('MYSQL_HOST') ?: 'roundhouse.proxy.rlwy.net';
$port = getenv('MYSQL_PORT') ?: '3306';
$dbname = getenv('MYSQL_DATABASE') ?: 'railway';
$user = getenv('MYSQL_USER') ?: 'root';
$pass = getenv('MYSQL_PASSWORD') ?: '';

// Connexion avec MySQLi
$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("❌ Connexion échouée: " . $conn->connect_error);
}

// Lire et exécuter le fichier SQL
$sql = file_get_contents('database.sql');
if ($conn->multi_query($sql)) {
    echo "✅ Base de données importée avec succès !";
} else {
    echo "❌ Erreur: " . $conn->error;
}

$conn->close();