<?php
// Fichier : connection.php

// Configuration de la base de données
$host = 'localhost';
$db   = 'ama_sis_db';
$user = 'ama_user';
$pass = 'JesuispartievoirMarwalundi'; 
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
     // Si la connexion échoue, on arrête tout et on affiche l'erreur
     die("<h1>❌ ERREUR DE CONNEXION BASE DE DONNÉES</h1><p>Veuillez vérifier les identifiants ou si la DB 'ama_sis_db' existe. Détails: " . $e->getMessage() . "</p>");
}
?>