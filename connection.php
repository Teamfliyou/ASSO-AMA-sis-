<?php
// Fichier : connection.php modifié pour LWS
$host = '127.0.0.1'; 
$db   = 'assoa2754152';
$user = 'assoa2754152';
$pass = '4qbarhp4me'; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Erreur de connexion : " . $e->getMessage());
}
?>